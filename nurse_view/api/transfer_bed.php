<?php
/**
 * API: Patient Ward / Bed Transfer
 * Executes atomic bed transfer:
 * 1. Releases previous bed in hospital_beds
 * 2. Allocates new bed in hospital_beds
 * 3. Updates ipd_admissions with new location & charges
 * 4. Logs full transfer record into ipd_clinical_records (ward_transfer column)
 * 5. Creates real-time notification alerts for duty nurses
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$patientId       = trim($input['patient_id'] ?? '');
$admissionId     = trim($input['admission_id'] ?? '');
$newBedId        = intval($input['new_bed_id'] ?? 0);
$transferDate    = trim($input['transfer_date'] ?? date('Y-m-d H:i:s'));
$transferRemarks = trim($input['transfer_remarks'] ?? '');
$isEmergency     = !empty($input['is_emergency']) ? 1 : 0;

$nurseId   = $_SESSION['user_id'] ?? 0;
$nurseName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Nurse');

if (empty($patientId) || empty($admissionId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Patient ID and Admission ID are required.']);
    exit();
}

if ($newBedId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select an available target bed.']);
    exit();
}

if (empty($transferRemarks)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Reason for transfer is required.']);
    exit();
}

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();

    // Start Transaction
    $conn->begin_transaction();

    // 1. Fetch Current Admission Record
    $admStmt = $conn->prepare("SELECT * FROM ipd_admissions WHERE patient_id = ? AND admission_id = ? FOR UPDATE");
    $admStmt->bind_param("ss", $patientId, $admissionId);
    $admStmt->execute();
    $admRes = $admStmt->get_result();
    $currentAdm = $admRes->fetch_assoc();
    $admStmt->close();

    if (!$currentAdm) {
        throw new Exception("Active admission not found for patient {$patientId} with IP {$admissionId}.");
    }

    $oldBedId = intval($currentAdm['bed_id'] ?? 0);

    if ($oldBedId === $newBedId) {
        throw new Exception("The patient is already allocated to this bed. Please select a different bed.");
    }

    // 2. Fetch Old Bed Details (if any)
    $oldBed = null;
    if ($oldBedId > 0) {
        $oldBedStmt = $conn->prepare("SELECT * FROM hospital_beds WHERE sl_no = ?");
        $oldBedStmt->bind_param("i", $oldBedId);
        $oldBedStmt->execute();
        $oldBed = $oldBedStmt->get_result()->fetch_assoc();
        $oldBedStmt->close();
    }

    // 3. Fetch New Bed Details and Verify Availability
    $newBedStmt = $conn->prepare("SELECT * FROM hospital_beds WHERE sl_no = ? FOR UPDATE");
    $newBedStmt->bind_param("i", $newBedId);
    $newBedStmt->execute();
    $newBed = $newBedStmt->get_result()->fetch_assoc();
    $newBedStmt->close();

    if (!$newBed) {
        throw new Exception("Target bed not found.");
    }

    if (strtolower($newBed['bed_status']) !== 'available') {
        throw new Exception("Target bed ({$newBed['room_type']} - Bed {$newBed['bed_number']}) is currently {$newBed['bed_status']}. Please choose an available bed.");
    }

    // 4. Release Old Bed in hospital_beds
    if ($oldBedId > 0) {
        $relStmt = $conn->prepare("UPDATE hospital_beds SET bed_status = 'Available', patient_id = NULL, admission_id = NULL, released_at = NOW() WHERE sl_no = ?");
        $relStmt->bind_param("i", $oldBedId);
        $relStmt->execute();
        $relStmt->close();
    }

    // 5. Occupy New Bed in hospital_beds
    $occStmt = $conn->prepare("UPDATE hospital_beds SET bed_status = 'Occupied', patient_id = ?, admission_id = ?, allocated_at = NOW() WHERE sl_no = ?");
    $occStmt->bind_param("ssi", $patientId, $admissionId, $newBedId);
    $occStmt->execute();
    $occStmt->close();

    // 6. Update ipd_admissions with New Bed Details
    $schemaRes = $conn->query("DESCRIBE ipd_admissions");
    $validCols = [];
    while ($colRow = $schemaRes->fetch_assoc()) {
        $validCols[] = $colRow['Field'];
    }

    $updateFields = [];
    $updateParams = [];
    $updateTypes = "";

    $mapping = [
        'bed_id'         => ['val' => $newBedId, 'type' => 'i'],
        'floor_number'   => ['val' => $newBed['floor_number'] ?? null, 'type' => 'i'],
        'floor_name'     => ['val' => $newBed['floor_name'] ?? null, 'type' => 's'],
        'ward_name'      => ['val' => $newBed['ward_name'] ?? null, 'type' => 's'],
        'ward'           => ['val' => $newBed['ward_name'] ?? null, 'type' => 's'],
        'room_no'        => ['val' => $newBed['room_number'] ?? null, 'type' => 's'],
        'room_name'      => ['val' => $newBed['room_name'] ?? null, 'type' => 's'],
        'room_type'      => ['val' => $newBed['room_type'] ?? null, 'type' => 's'],
        'ward_type'      => ['val' => $newBed['room_type'] ?? null, 'type' => 's'],
        'amount_per_day' => ['val' => $newBed['amount_per_day'] ?? null, 'type' => 'd'],
        'nursig_charge'  => ['val' => $newBed['nursig_charge'] ?? null, 'type' => 'd'],
        'doctor_charge'  => ['val' => $newBed['doctor_charge'] ?? null, 'type' => 'd'],
        'service_charge' => ['val' => $newBed['service_charge'] ?? null, 'type' => 'd']
    ];

    foreach ($mapping as $col => $info) {
        if (in_array($col, $validCols) && $info['val'] !== null) {
            $updateFields[] = "`{$col}` = ?";
            $updateParams[] = $info['val'];
            $updateTypes .= $info['type'];
        }
    }

    if (!empty($updateFields)) {
        $updateSql = "UPDATE ipd_admissions SET " . implode(", ", $updateFields) . " WHERE patient_id = ? AND admission_id = ?";
        $updateParams[] = $patientId;
        $updateParams[] = $admissionId;
        $updateTypes .= "ss";

        $upStmt = $conn->prepare($updateSql);
        $upStmt->bind_param($updateTypes, ...$updateParams);
        $upStmt->execute();
        $upStmt->close();
    }

    // 7. Log Transfer Record in ipd_clinical_records (ward_transfer column)
    $fromFloor    = $oldBed['floor_name'] ?? $currentAdm['floor_name'] ?? 'N/A';
    $fromWard     = $oldBed['ward_name'] ?? $currentAdm['ward_name'] ?? ($currentAdm['ward'] ?? 'N/A');
    $fromRoomType = $oldBed['room_type'] ?? $currentAdm['room_type'] ?? 'N/A';
    $fromRoomNo   = $oldBed['room_number'] ?? $currentAdm['room_no'] ?? '';
    $fromBedNo    = $oldBed['bed_number'] ?? ($currentAdm['bed_id'] ? 'Bed #' . $currentAdm['bed_id'] : 'N/A');

    $toFloor    = $newBed['floor_name'] ?? 'N/A';
    $toWard     = $newBed['ward_name'] ?? 'N/A';
    $toRoomType = $newBed['room_type'] ?? 'N/A';
    $toRoomNo   = $newBed['room_number'] ?? '';
    $toBedNo    = $newBed['bed_number'] ?? '';

    $transferEntry = [
        'entry_id'         => uniqid('tr_'),
        'transfer_date'    => $transferDate,
        'from_bed_id'      => $oldBedId,
        'from_floor'       => $fromFloor,
        'from_ward'        => $fromWard,
        'from_room_type'   => $fromRoomType,
        'from_room_no'     => $fromRoomNo,
        'from_bed_no'      => $fromBedNo,
        'to_bed_id'        => $newBedId,
        'to_floor'         => $toFloor,
        'to_ward'          => $toWard,
        'to_room_type'     => $toRoomType,
        'to_room_no'       => $toRoomNo,
        'to_bed_no'        => $toBedNo,
        'transfer_remarks' => $transferRemarks,
        'is_emergency'     => $isEmergency,
        'nurse_id'         => $nurseId,
        'nurse_name'       => $nurseName,
        'nurse_sign'       => $nurseName,
        'created_at'       => date('Y-m-d H:i:s'),
        'created_by_name'  => $nurseName
    ];

    $recDate = date('Y-m-d', strtotime($transferDate));
    $chkRecord = $conn->prepare("SELECT id, ward_transfer FROM ipd_clinical_records WHERE patient_id = ? AND admission_id = ? ORDER BY record_date DESC, id DESC LIMIT 1");
    $chkRecord->bind_param("ss", $patientId, $admissionId);
    $chkRecord->execute();
    $recRes = $chkRecord->get_result();

    if ($recRes->num_rows > 0) {
        $recRow = $recRes->fetch_assoc();
        $recId = $recRow['id'];
        $existingTransfers = json_decode($recRow['ward_transfer'] ?? '[]', true);
        if (!is_array($existingTransfers)) $existingTransfers = [];
        $existingTransfers[] = $transferEntry;
        $jsonTransfers = json_encode($existingTransfers);

        $upRec = $conn->prepare("UPDATE ipd_clinical_records SET ward_transfer = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $upRec->bind_param("ssi", $jsonTransfers, $nurseId, $recId);
        $upRec->execute();
        $upRec->close();
    } else {
        $initTransfers = json_encode([$transferEntry]);
        $insRec = $conn->prepare("INSERT INTO ipd_clinical_records (patient_id, admission_id, record_date, ward_transfer, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
        $insRec->bind_param("ssssss", $patientId, $admissionId, $recDate, $initTransfers, $nurseId, $nurseId);
        $insRec->execute();
        $insRec->close();
    }
    $chkRecord->close();

    // 8. Fetch Patient Name for Notifications
    $pNameStmt = $conn->prepare("SELECT first_name, last_name FROM patient WHERE patient_id = ?");
    $pNameStmt->bind_param("s", $patientId);
    $pNameStmt->execute();
    $pNameRow = $pNameStmt->get_result()->fetch_assoc();
    $pNameStmt->close();
    $patientName = trim(($pNameRow['first_name'] ?? '') . ' ' . ($pNameRow['last_name'] ?? ''));
    if (empty($patientName)) $patientName = "Patient {$patientId}";

    // 9. Generate Real-time Notification
    $notifTitle = $isEmergency 
        ? "🚨 EMERGENCY Patient Shifted: {$patientName}" 
        : "Patient Shifted to {$toWard} ({$toBedNo})";
        
    $notifMsg = "Patient {$patientName} (PID: {$patientId}, IP: {$admissionId}) transferred from [{$fromWard} - Bed {$fromBedNo}] to [{$toWard} - {$toRoomType} Bed {$toBedNo}]. Reason: {$transferRemarks}. Transferred by: {$nurseName}.";

    // Query active nurses scheduled on the target floor/ward today from shift_schedules
    $todayDate = date('Y-m-d');
    $targetNurses = [];
    $schStmt = $conn->prepare("SELECT shift_data FROM shift_schedules WHERE ? BETWEEN start_date AND end_date AND floor_name = ? AND ward_name = ?");
    if ($schStmt) {
        $schStmt->bind_param("sss", $todayDate, $toFloor, $toWard);
        $schStmt->execute();
        $schRes = $schStmt->get_result();
        while ($sRow = $schRes->fetch_assoc()) {
            $sData = json_decode($sRow['shift_data'], true);
            if (is_array($sData)) {
                foreach ($sData as $shift) {
                    if (!empty($shift['nurse_id']) && $shift['shift_date'] === $todayDate && $shift['shift_type'] !== 'Week Off') {
                        $targetNurses[] = $shift['nurse_id'];
                    }
                }
            }
        }
        $schStmt->close();
    }
    $targetNurses = array_unique($targetNurses);

    // Insert into notifications table
    $notifColsRes = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($notifColsRes && $notifColsRes->num_rows > 0) {
        $priority = $isEmergency ? 'high' : 'normal';
        $category = $isEmergency ? 'emergency' : 'system';
        
        // Broadcast to general staff/nurse recipient
        $nid = 'NOTIF-' . time() . '-' . rand(1000, 9999);
        $insNotif = $conn->prepare("INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority, action_url, is_read, created_at) VALUES (?, 'staff', 'staff', ?, ?, ?, ?, 'nurse_workspace.php', 0, NOW())");
        if ($insNotif) {
            $insNotif->bind_param("sssss", $nid, $notifTitle, $notifMsg, $category, $priority);
            $insNotif->execute();
            $insNotif->close();
        }

        // Also insert specifically for each scheduled floor nurse
        foreach ($targetNurses as $tNurseId) {
            $nidNurse = 'NOTIF-' . time() . '-' . rand(1000, 9999);
            $insNotifN = $conn->prepare("INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority, action_url, is_read, created_at) VALUES (?, ?, 'staff', ?, ?, ?, ?, 'nurse_workspace.php', 0, NOW())");
            if ($insNotifN) {
                $tNurseStr = (string)$tNurseId;
                $insNotifN->bind_param("ssssss", $nidNurse, $tNurseStr, $notifTitle, $notifMsg, $category, $priority);
                $insNotifN->execute();
                $insNotifN->close();
            }
        }
    }

    // Commit Transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Patient successfully transferred to {$toWard} ({$toRoomType} - Bed {$toBedNo}).",
        'data' => [
            'patient_id'    => $patientId,
            'admission_id'  => $admissionId,
            'bed_id'        => $newBedId,
            'floor_name'    => $toFloor,
            'ward_name'     => $toWard,
            'room_type'     => $toRoomType,
            'room_number'   => $toRoomNo,
            'bed_number'    => $toBedNo,
            'transfer_entry'=> $transferEntry
        ]
    ]);

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
