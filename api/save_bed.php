<?php
session_start();
header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../core/Autoloader.php';
require_once __DIR__ . '/../Database/SecureDatabase.php';

try {
    $db = \GM_HMS\Database\SecureDatabase::getInstance();
} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'create';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ─────────────────────────────────────────────────────────────────────────────
    // 1. QUICK STATUS UPDATE (Available, Occupied, Cleaning, Maintenance)
    // ─────────────────────────────────────────────────────────────────────────────
    if ($action === 'update_status') {
        $sl_no = intval($_POST['sl_no'] ?? 0);
        $new_status = trim($_POST['bed_status'] ?? '');
        
        $validStatuses = ['Available', 'Occupied', 'Cleaning', 'Maintenance', 'Reserved', 'Blocked'];
        if (!$sl_no || !in_array($new_status, $validStatuses)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid bed status.']);
            exit;
        }

        $bed = $db->fetchOne("SELECT * FROM hospital_beds WHERE sl_no = ?", [$sl_no]);
        if (!$bed) {
            echo json_encode(['status' => 'error', 'message' => 'Bed record not found']);
            exit;
        }

        $updateData = ['bed_status' => $new_status];
        if ($new_status === 'Available') {
            // Releasing the bed makes it vacant, clears patient assignment, and records release timestamp
            $updateData['patient_id'] = null;
            $updateData['admission_id'] = null;
            $updateData['released_at'] = date('Y-m-d H:i:s');
        }

        $db->update('hospital_beds', $updateData, 'sl_no = ?', [$sl_no]);
        $msg = $new_status === 'Available' 
            ? "Bed {$bed['bed_number']} has been released and is now Available for new patient admissions."
            : "Bed {$bed['bed_number']} status changed to {$new_status}.";
            
        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 2. DELETE BED
    // ─────────────────────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $sl_no = intval($_POST['sl_no'] ?? 0);
        if (!$sl_no) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid bed ID']);
            exit;
        }

        $bed = $db->fetchOne("SELECT * FROM hospital_beds WHERE sl_no = ?", [$sl_no]);
        if (!$bed) {
            echo json_encode(['status' => 'error', 'message' => 'Bed not found']);
            exit;
        }

        if (!empty($bed['patient_id']) || $bed['bed_status'] === 'Occupied') {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete an occupied bed. Discharge or transfer the patient first.']);
            exit;
        }

        $db->execute("DELETE FROM hospital_beds WHERE sl_no = ?", [$sl_no]);
        echo json_encode(['status' => 'success', 'message' => 'Bed removed successfully']);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 3. BATCH UPDATE ENTIRE ROOM (ROOM DETAILS & PRICING ACROSS ALL BEDS IN ROOM)
    // ─────────────────────────────────────────────────────────────────────────────
    if ($action === 'update_room') {
        $orig_room_number = trim($_POST['orig_room_number'] ?? '');
        $orig_ward_name   = trim($_POST['orig_ward_name'] ?? '');

        if (empty($orig_room_number) || empty($orig_ward_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Original Room Number and Ward are required.']);
            exit;
        }

        $new_room_number  = trim($_POST['room_number'] ?? $orig_room_number);
        $new_room_name    = trim($_POST['room_name'] ?? $new_room_number);
        $new_ward_name    = trim($_POST['ward_name'] ?? $orig_ward_name);
        $new_room_type    = trim($_POST['room_type'] ?? 'General Ward');
        $new_floor_number = trim($_POST['floor_number'] ?? '0');
        $new_floor_name   = trim($_POST['floor_name'] ?? '');

        $amount_per_day   = floatval($_POST['amount_per_day'] ?? 0);
        $nursig_charge    = floatval($_POST['nursig_charge'] ?? 0);
        $doctor_charge    = floatval($_POST['doctor_charge'] ?? 0);
        $service_charge   = floatval($_POST['service_charge'] ?? 0);
        $total_bed_amount = $amount_per_day + $nursig_charge + $doctor_charge + $service_charge;

        $updatePayload = [
            'room_number'      => $new_room_number,
            'room_name'        => $new_room_name,
            'ward_name'        => $new_ward_name,
            'room_type'        => $new_room_type,
            'floor_number'     => $new_floor_number,
            'floor_name'       => $new_floor_name,
            'amount_per_day'   => $amount_per_day,
            'nursig_charge'    => $nursig_charge,
            'doctor_charge'    => $doctor_charge,
            'service_charge'   => $service_charge,
            'total_bed_amount' => $total_bed_amount
        ];

        $affected = $db->update(
            'hospital_beds',
            $updatePayload,
            'room_number = ? AND ward_name = ?',
            [$orig_room_number, $orig_ward_name]
        );

        echo json_encode(['status' => 'success', 'message' => "Successfully updated {$affected} bed(s) in Room {$new_room_number}."]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 4. ADD NEXT BED TO AN EXISTING ROOM
    // ─────────────────────────────────────────────────────────────────────────────
    if ($action === 'add_bed_to_room') {
        $room_number = trim($_POST['room_number'] ?? '');
        $ward_name   = trim($_POST['ward_name'] ?? '');

        if (empty($room_number) || empty($ward_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Room number and Ward are required.']);
            exit;
        }

        // Get existing beds in this room to copy specs & determine next bed letter
        $existingBeds = $db->fetchAll(
            "SELECT * FROM hospital_beds WHERE room_number = ? AND ward_name = ? ORDER BY bed_number ASC",
            [$room_number, $ward_name]
        );

        if (empty($existingBeds)) {
            echo json_encode(['status' => 'error', 'message' => 'Room not found.']);
            exit;
        }

        $templateBed = $existingBeds[0];
        $count = count($existingBeds);

        // Compute next bed letter (A, B, C, D, E, F...)
        $nextChar = chr(ord('A') + $count);
        $newBedNumber = "{$room_number}-{$nextChar}";

        $db->insert('hospital_beds', [
            'floor_number'     => $templateBed['floor_number'],
            'floor_name'       => $templateBed['floor_name'],
            'ward_name'        => $templateBed['ward_name'],
            'room_type'        => $templateBed['room_type'],
            'room_number'      => $templateBed['room_number'],
            'room_name'        => $templateBed['room_name'],
            'bed_number'       => $newBedNumber,
            'amount_per_day'   => $templateBed['amount_per_day'],
            'nursig_charge'    => $templateBed['nursig_charge'],
            'doctor_charge'    => $templateBed['doctor_charge'],
            'service_charge'   => $templateBed['service_charge'],
            'total_bed_amount' => $templateBed['total_bed_amount'],
            'bed_status'       => 'Available'
        ]);

        echo json_encode(['status' => 'success', 'message' => "Bed {$newBedNumber} added to Room {$room_number} successfully."]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // 5. CREATE OR UPDATE SINGLE/BATCH BED DETAILS
    // ─────────────────────────────────────────────────────────────────────────────
    $floor_number = trim($_POST['floor_number'] ?? '0');
    $floor_name   = trim($_POST['floor_name'] ?? '');
    $ward_name    = trim($_POST['ward_name'] ?? '');
    $room_type    = trim($_POST['room_type'] ?? 'General');
    $room_number  = trim($_POST['room_number'] ?? '');
    $room_name    = trim($_POST['room_name'] ?? '');
    $bed_number   = trim($_POST['bed_number'] ?? '');
    
    // Handle custom text inputs if 'ADD_NEW_CUSTOM' was selected
    if (!empty($_POST['floor_number_custom'])) $floor_number = trim($_POST['floor_number_custom']);
    if (!empty($_POST['floor_name_custom']))   $floor_name   = trim($_POST['floor_name_custom']);
    if (!empty($_POST['ward_name_custom']))    $ward_name    = trim($_POST['ward_name_custom']);
    if (!empty($_POST['room_type_custom']))    $room_type    = trim($_POST['room_type_custom']);
    if (!empty($_POST['room_name_custom']))    $room_name    = trim($_POST['room_name_custom']);
    if (!empty($_POST['bed_number_custom']))   $bed_number   = trim($_POST['bed_number_custom']);

    $amount_per_day   = floatval($_POST['amount_per_day'] ?? 0);
    $nursig_charge    = floatval($_POST['nursig_charge'] ?? 0);
    $doctor_charge    = floatval($_POST['doctor_charge'] ?? 0);
    $service_charge   = floatval($_POST['service_charge'] ?? 0);
    $total_bed_amount = $amount_per_day + $nursig_charge + $doctor_charge + $service_charge;
    $bed_status       = in_array(trim($_POST['bed_status'] ?? ''), ['Available', 'Occupied', 'Cleaning', 'Maintenance', 'Reserved', 'Blocked']) ? trim($_POST['bed_status']) : 'Available';

    if (empty($ward_name) || empty($room_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Ward Name and Room Number are required.']);
        exit;
    }

    if ($action === 'update') {
        $sl_no = intval($_POST['sl_no'] ?? 0);
        if (!$sl_no) {
            echo json_encode(['status' => 'error', 'message' => 'Bed record ID (sl_no) missing for update.']);
            exit;
        }

        $existing = $db->fetchOne("SELECT * FROM hospital_beds WHERE sl_no = ?", [$sl_no]);
        if (!$existing) {
            echo json_encode(['status' => 'error', 'message' => 'Bed record not found.']);
            exit;
        }

        $updatePayload = [
            'floor_number'     => $floor_number,
            'floor_name'       => $floor_name,
            'ward_name'        => $ward_name,
            'room_type'        => $room_type,
            'room_number'      => $room_number,
            'room_name'        => $room_name ?: $room_number,
            'bed_number'       => $bed_number ?: $existing['bed_number'],
            'amount_per_day'   => $amount_per_day,
            'nursig_charge'    => $nursig_charge,
            'doctor_charge'    => $doctor_charge,
            'service_charge'   => $service_charge,
            'total_bed_amount' => $total_bed_amount,
            'bed_status'       => $bed_status
        ];

        if ($bed_status === 'Available') {
            $updatePayload['patient_id'] = null;
            $updatePayload['admission_id'] = null;
            $updatePayload['released_at'] = date('Y-m-d H:i:s');
        }

        $db->update('hospital_beds', $updatePayload, 'sl_no = ?', [$sl_no]);
        echo json_encode(['status' => 'success', 'message' => 'Bed details and pricing updated successfully.']);
        exit;
    }

    // CREATE (Supports single or batch creation with alphabetical sequence: 1201-A, 1201-B, 1201-C...)
    $batchCount = intval($_POST['batch_count'] ?? 1);
    if ($batchCount > 1) {
        $baseBedNum = trim($bed_number);
        $createdCount = 0;

        $prefix = $baseBedNum;
        $startCharAscii = ord('A');

        if (preg_match('/^(.*?)[-\s]*([A-Za-z])$/', $baseBedNum, $matches)) {
            $prefix = rtrim($matches[1], " -\t");
            $startCharAscii = ord(strtoupper($matches[2]));
        } else {
            $prefix = rtrim($baseBedNum, " -\t");
            $startCharAscii = ord('A');
        }

        for ($i = 0; $i < $batchCount; $i++) {
            $char = chr($startCharAscii + $i);
            $currentBedNum = $prefix ? "{$prefix}-{$char}" : "Bed-{$char}";

            $db->insert('hospital_beds', [
                'floor_number'     => $floor_number,
                'floor_name'       => $floor_name,
                'ward_name'        => $ward_name,
                'room_type'        => $room_type,
                'room_number'      => $room_number,
                'room_name'        => $room_name ?: $room_number,
                'bed_number'       => $currentBedNum,
                'amount_per_day'   => $amount_per_day,
                'nursig_charge'    => $nursig_charge,
                'doctor_charge'    => $doctor_charge,
                'service_charge'   => $service_charge,
                'total_bed_amount' => $total_bed_amount,
                'bed_status'       => $bed_status
            ]);
            $createdCount++;
        }
        $firstBed = $prefix ? "{$prefix}-" . chr($startCharAscii) : "Bed-" . chr($startCharAscii);
        $lastBed  = $prefix ? "{$prefix}-" . chr($startCharAscii + $batchCount - 1) : "Bed-" . chr($startCharAscii + $batchCount - 1);
        echo json_encode(['status' => 'success', 'message' => "Successfully created {$createdCount} beds in Room {$room_number} ({$firstBed} to {$lastBed})."]);
        exit;
    } else {
        if (empty($bed_number)) {
            echo json_encode(['status' => 'error', 'message' => 'Bed Number is required.']);
            exit;
        }

        $db->insert('hospital_beds', [
            'floor_number'     => $floor_number,
            'floor_name'       => $floor_name,
            'ward_name'        => $ward_name,
            'room_type'        => $room_type,
            'room_number'      => $room_number,
            'room_name'        => $room_name ?: $room_number,
            'bed_number'       => $bed_number,
            'amount_per_day'   => $amount_per_day,
            'nursig_charge'    => $nursig_charge,
            'doctor_charge'    => $doctor_charge,
            'service_charge'   => $service_charge,
            'total_bed_amount' => $total_bed_amount,
            'bed_status'       => $bed_status
        ]);

        echo json_encode(['status' => 'success', 'message' => "Bed {$bed_number} added successfully."]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
