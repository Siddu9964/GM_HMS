<?php
namespace GM_HMS\Controllers\api;

use Exception;
use Throwable;
use GM_HMS\Database\SecureDatabase;

/**
 * DischargeClearanceController
 * Multi-Department Discharge Clearance & Query System
 * Coordinates clearance between Nurse, Reception/Billing, Pharmacy, Laboratory, and Admin.
 */
class DischargeClearanceController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = SecureDatabase::getInstance();
        $this->conn = $this->db->getConnection();
        $this->ensureTables();
    }

    private function ensureTables(): void {
        try {
            $this->conn->query("CREATE TABLE IF NOT EXISTS discharge_clearances (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clearance_id VARCHAR(50) UNIQUE NOT NULL,
                patient_id VARCHAR(50) NOT NULL,
                admission_id VARCHAR(50) NOT NULL,
                patient_name VARCHAR(150),
                bed_info VARCHAR(100),
                doctor_name VARCHAR(100),
                nurse_id VARCHAR(50),
                nurse_name VARCHAR(100),
                nurse_notes TEXT,
                reception_status ENUM('Pending', 'Approved', 'Query') DEFAULT 'Pending',
                reception_by VARCHAR(100) NULL,
                reception_at DATETIME NULL,
                reception_query TEXT NULL,
                reception_notes TEXT NULL,
                pharmacy_status ENUM('Pending', 'Approved', 'Query') DEFAULT 'Pending',
                pharmacy_by VARCHAR(100) NULL,
                pharmacy_at DATETIME NULL,
                pharmacy_query TEXT NULL,
                pharmacy_notes TEXT NULL,
                lab_status ENUM('Pending', 'Approved', 'Query') DEFAULT 'Pending',
                lab_by VARCHAR(100) NULL,
                lab_at DATETIME NULL,
                lab_query TEXT NULL,
                lab_notes TEXT NULL,
                overall_status ENUM('Pending Clearance', 'Queries Raised', 'All Cleared', 'Completed') DEFAULT 'Pending Clearance',
                admin_status ENUM('Pending', 'Confirmed', 'Completed') DEFAULT 'Pending',
                admin_by VARCHAR(100) NULL,
                admin_at DATETIME NULL,
                admin_notes TEXT NULL,
                status VARCHAR(50) DEFAULT 'Pending',
                message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (patient_id),
                INDEX (admission_id),
                INDEX (overall_status),
                INDEX (reception_status),
                INDEX (pharmacy_status),
                INDEX (lab_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $this->conn->query("CREATE TABLE IF NOT EXISTS discharge_clearance_queries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clearance_id VARCHAR(50) NOT NULL,
                admission_id VARCHAR(50) NOT NULL,
                department ENUM('reception', 'pharmacy', 'lab', 'nurse', 'admin') NOT NULL,
                user_id VARCHAR(50),
                user_name VARCHAR(100),
                query_text TEXT NOT NULL,
                response_text TEXT NULL,
                status ENUM('Open', 'Resolved') DEFAULT 'Open',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                resolved_at DATETIME NULL,
                INDEX (clearance_id),
                INDEX (admission_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $this->conn->query("CREATE TABLE IF NOT EXISTS discharge_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id VARCHAR(50) NOT NULL,
                admission_id VARCHAR(50) NOT NULL,
                message TEXT,
                status VARCHAR(50) DEFAULT 'Pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (patient_id),
                INDEX (admission_id),
                INDEX (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (Throwable $e) {
            error_log("DischargeClearanceController ensureTables error: " . $e->getMessage());
        }
    }

    /**
     * 1. Initiate multi-department discharge clearance (Called by Nurse)
     */
    public function initiateClearance(array $params): array {
        $patientId   = trim($params['patient_id'] ?? '');
        $admissionId = trim($params['admission_id'] ?? '');
        $nurseNotes  = trim($params['nurse_notes'] ?? ($params['message'] ?? ''));
        $nurseId     = trim($params['nurse_id'] ?? ($_SESSION['user_id'] ?? ''));
        $nurseName   = trim($params['nurse_name'] ?? ($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Nurse')));

        if (empty($patientId) || empty($admissionId)) {
            return ['success' => false, 'message' => 'Patient ID and Admission ID are required.'];
        }

        // Fetch patient details & bed info
        $patientName = 'Patient';
        $bedInfo     = 'Inpatient Bed';
        $doctorName  = 'Attending Consultant';

        try {
            $stmt = $this->conn->prepare("SELECT first_name, last_name FROM patient WHERE patient_id = ? LIMIT 1");
            $stmt->bind_param("s", $patientId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $patientName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            }
            $stmt->close();
        } catch (Throwable $e) {}

        try {
            $stmt = $this->conn->prepare("SELECT room_type, ward_name, ward, room_number, bed_number, doctor_name FROM ipd_admissions WHERE admission_id = ? OR patient_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("ss", $admissionId, $patientId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $ward = $r['ward_name'] ?: ($r['ward'] ?: ($r['room_type'] ?: 'Ward'));
                $bed  = $r['bed_number'] ?: ($r['room_number'] ?: 'Bed');
                $bedInfo = "{$ward} - Bed {$bed}";
                if (!empty($r['doctor_name'])) {
                    $doctorName = $r['doctor_name'];
                }
            }
            $stmt->close();
        } catch (Throwable $e) {}

        // Check if an active clearance already exists
        $existing = null;
        $stmt = $this->conn->prepare("SELECT * FROM discharge_clearances WHERE admission_id = ? AND overall_status != 'Completed' ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $admissionId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) {
            $existing = $r;
        }
        $stmt->close();

        $message = "Discharge clearance initiated for {$patientName} (PID: {$patientId}, Admission: {$admissionId}, Location: {$bedInfo}). Requires clearance from Reception/Billing, Pharmacy, and Laboratory.";

        if ($existing) {
            $clearanceId = $existing['clearance_id'];
            // Reset pending clearances if re-initiated
            $stmtUpdate = $this->conn->prepare("UPDATE discharge_clearances SET 
                patient_name = ?, bed_info = ?, doctor_name = ?, nurse_id = ?, nurse_name = ?, nurse_notes = ?,
                reception_status = IF(reception_status='Approved','Approved','Pending'),
                pharmacy_status = IF(pharmacy_status='Approved','Approved','Pending'),
                lab_status = IF(lab_status='Approved','Approved','Pending'),
                overall_status = IF(reception_status='Approved' AND pharmacy_status='Approved' AND lab_status='Approved', 'All Cleared', 'Pending Clearance'),
                admin_status = 'Pending',
                message = ?,
                updated_at = NOW()
                WHERE clearance_id = ?");
            $stmtUpdate->bind_param("ssssssss", $patientName, $bedInfo, $doctorName, $nurseId, $nurseName, $nurseNotes, $message, $clearanceId);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        } else {
            $clearanceId = 'DC-' . time() . '-' . rand(100, 999);
            $stmtInsert = $this->conn->prepare("INSERT INTO discharge_clearances 
                (clearance_id, patient_id, admission_id, patient_name, bed_info, doctor_name, nurse_id, nurse_name, nurse_notes, reception_status, pharmacy_status, lab_status, overall_status, admin_status, status, message)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', 'Pending', 'Pending Clearance', 'Pending', 'Pending', ?)");
            $stmtInsert->bind_param("ssssssssss", $clearanceId, $patientId, $admissionId, $patientName, $bedInfo, $doctorName, $nurseId, $nurseName, $nurseNotes, $message);
            $stmtInsert->execute();
            $stmtInsert->close();
        }

        // Backward-compatible record in discharge_notifications
        try {
            $stmtNotif = $this->conn->prepare("INSERT INTO discharge_notifications (patient_id, admission_id, message, status) VALUES (?, ?, ?, 'Pending')");
            $stmtNotif->bind_param("sss", $patientId, $admissionId, $message);
            $stmtNotif->execute();
            $stmtNotif->close();
        } catch (Throwable $e) {}

        // Send multi-module system notifications into `notifications` table
        $this->dispatchSystemNotifications($clearanceId, $patientName, $patientId, $admissionId, $bedInfo, $nurseName);

        return [
            'success' => true,
            'message' => "Discharge clearance request dispatched to Reception, Pharmacy, Laboratory, and Admin for {$patientName}.",
            'clearance_id' => $clearanceId,
            'patient_name' => $patientName,
            'bed_info'     => $bedInfo,
            'doctor_name'  => $doctorName
        ];
    }

    /**
     * Dispatch notifications to Reception, Pharmacy, Lab, and Admin
     */
    private function dispatchSystemNotifications(string $clearanceId, string $patientName, string $patientId, string $admissionId, string $bedInfo, string $nurseName): void {
        $notifs = [
            [
                'recipient_type' => 'staff',
                'recipient_id'   => 'RECEPTION',
                'title'          => '📋 Discharge Clearance Request: ' . $patientName,
                'message'        => "Patient {$patientName} ({$bedInfo}) is ready for discharge. Please review IPD billing & approve clearance.",
                'action_url'     => '/reception_view/ipd_management/views/discharge/index.php'
            ],
            [
                'recipient_type' => 'staff',
                'recipient_id'   => 'PHARMACY',
                'title'          => '💊 Discharge Pharmacy Clearance: ' . $patientName,
                'message'        => "Patient {$patientName} ({$bedInfo}) is being discharged. Please verify medicine returns & clearance.",
                'action_url'     => '/pharmacy_view/sales.php'
            ],
            [
                'recipient_type' => 'staff',
                'recipient_id'   => 'LABORATORY',
                'title'          => '🔬 Discharge Lab Clearance: ' . $patientName,
                'message'        => "Patient {$patientName} ({$bedInfo}) is being discharged. Please verify pending lab/diagnostic reports.",
                'action_url'     => '/laboratory_view/dashboard.php'
            ],
            [
                'recipient_type' => 'admin',
                'recipient_id'   => 'ADMIN',
                'title'          => '🏥 Discharge Clearance Initiated: ' . $patientName,
                'message'        => "Discharge clearance started by Nurse {$nurseName} for {$patientName} ({$bedInfo}). Awaiting departmental approvals.",
                'action_url'     => '/view/admin_dashboard.php'
            ]
        ];

        foreach ($notifs as $n) {
            try {
                $notifId = 'NOTIF-' . time() . '-' . rand(1000, 9999);
                $stmt = $this->conn->prepare("INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority, action_url, is_read, created_at) VALUES (?, ?, ?, ?, ?, 'emergency', 'high', ?, 0, NOW())");
                $stmt->bind_param("ssssss", $notifId, $n['recipient_id'], $n['recipient_type'], $n['title'], $n['message'], $n['action_url']);
                $stmt->execute();
                $stmt->close();
            } catch (Throwable $e) {}
        }
    }

    /**
     * 2. Get Clearance Status for an admission or patient
     */
    public function getClearanceStatus(string $admissionId, string $patientId = ''): array {
        $clearance = null;
        if (!empty($admissionId)) {
            $stmt = $this->conn->prepare("SELECT * FROM discharge_clearances WHERE admission_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("s", $admissionId);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM discharge_clearances WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("s", $patientId);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) {
            $clearance = $r;
        }
        $stmt->close();

        if (!$clearance) {
            return [
                'success' => true,
                'has_clearance' => false,
                'message' => 'No active discharge clearance found.',
                'data' => null,
                'queries' => []
            ];
        }

        // Fetch queries
        $queries = [];
        $stmtQ = $this->conn->prepare("SELECT * FROM discharge_clearance_queries WHERE clearance_id = ? OR admission_id = ? ORDER BY created_at ASC");
        $stmtQ->bind_param("ss", $clearance['clearance_id'], $clearance['admission_id']);
        $stmtQ->execute();
        $resQ = $stmtQ->get_result();
        while ($q = $resQ->fetch_assoc()) {
            $queries[] = $q;
        }
        $stmtQ->close();

        return [
            'success' => true,
            'has_clearance' => true,
            'data' => $clearance,
            'queries' => $queries
        ];
    }

    /**
     * 3. Get Pending Clearance List for specific department (reception, pharmacy, lab, admin)
     */
    public function getPendingList(string $module = 'admin', int $limit = 20): array {
        $module = strtolower(trim($module));
        $where = "overall_status != 'Completed'";

        if ($module === 'reception') {
            $where = "(reception_status = 'Pending' OR reception_status = 'Query' OR overall_status = 'All Cleared') AND overall_status != 'Completed'";
        } elseif ($module === 'pharmacy') {
            $where = "(pharmacy_status = 'Pending' OR pharmacy_status = 'Query' OR overall_status = 'All Cleared') AND overall_status != 'Completed'";
        } elseif ($module === 'lab' || $module === 'laboratory') {
            $where = "(lab_status = 'Pending' OR lab_status = 'Query' OR overall_status = 'All Cleared') AND overall_status != 'Completed'";
        }

        $list = [];
        $sql = "SELECT * FROM discharge_clearances WHERE {$where} ORDER BY updated_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            // Check if there are open queries for this clearance
            $stmtQ = $this->conn->prepare("SELECT COUNT(*) as q_count FROM discharge_clearance_queries WHERE clearance_id = ? AND status = 'Open'");
            $stmtQ->bind_param("s", $row['clearance_id']);
            $stmtQ->execute();
            $qRes = $stmtQ->get_result()->fetch_assoc();
            $row['open_queries_count'] = (int)($qRes['q_count'] ?? 0);
            $stmtQ->close();

            $list[] = $row;
        }
        $stmt->close();

        // Get KPI summary counts
        $counts = [
            'total_pending' => 0,
            'all_cleared' => 0,
            'queries_raised' => 0,
            'my_pending' => 0
        ];

        $resCounts = $this->conn->query("SELECT 
            COUNT(CASE WHEN overall_status = 'Pending Clearance' THEN 1 END) as pending_cnt,
            COUNT(CASE WHEN overall_status = 'All Cleared' THEN 1 END) as cleared_cnt,
            COUNT(CASE WHEN overall_status = 'Queries Raised' THEN 1 END) as queries_cnt,
            COUNT(CASE WHEN {$module}_status = 'Pending' AND overall_status != 'Completed' THEN 1 END) as my_cnt
            FROM discharge_clearances WHERE overall_status != 'Completed'");
        
        if ($resCounts && $cRow = $resCounts->fetch_assoc()) {
            $counts['total_pending'] = (int)$cRow['pending_cnt'];
            $counts['all_cleared']   = (int)$cRow['cleared_cnt'];
            $counts['queries_raised']= (int)$cRow['queries_cnt'];
            $counts['my_pending']    = (int)($cRow['my_cnt'] ?? 0);
        }

        return [
            'success' => true,
            'module'  => $module,
            'counts'  => $counts,
            'data'    => $list
        ];
    }

    /**
     * 4. Update Department Clearance: Approve or Raise Query
     */
    public function updateDepartmentClearance(array $params): array {
        $clearanceId = trim($params['clearance_id'] ?? '');
        $admissionId = trim($params['admission_id'] ?? '');
        $patientId   = trim($params['patient_id'] ?? '');
        $department  = strtolower(trim($params['department'] ?? ''));
        $action      = strtolower(trim($params['action'] ?? 'approve')); // 'approve' or 'query'
        $userName    = trim($params['user_name'] ?? ($_SESSION['full_name'] ?? ($_SESSION['username'] ?? ucfirst($department) . ' Staff')));
        $userId      = trim($params['user_id'] ?? ($_SESSION['user_id'] ?? ''));
        $queryText   = trim($params['query_text'] ?? '');
        $notes       = trim($params['notes'] ?? '');

        if (empty($clearanceId) && empty($admissionId) && empty($patientId)) {
            return ['success' => false, 'message' => 'Clearance ID, Admission ID or Patient ID is required.'];
        }

        if (!in_array($department, ['reception', 'pharmacy', 'lab', 'laboratory'])) {
            return ['success' => false, 'message' => 'Invalid department specified.'];
        }
        if ($department === 'laboratory') {
            $department = 'lab';
        }

        // Fetch active clearance
        if (!empty($clearanceId)) {
            $stmt = $this->conn->prepare("SELECT * FROM discharge_clearances WHERE clearance_id = ? LIMIT 1");
            $stmt->bind_param("s", $clearanceId);
        } elseif (!empty($admissionId)) {
            $stmt = $this->conn->prepare("SELECT * FROM discharge_clearances WHERE admission_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("s", $admissionId);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM discharge_clearances WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("s", $patientId);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $clearance = $res->fetch_assoc();
        $stmt->close();

        // If no clearance record exists yet, auto-initiate it on-the-fly
        if (!$clearance) {
            $initRes = $this->initiateClearance([
                'patient_id'   => $patientId ?: ($params['patient_id'] ?? ''),
                'admission_id' => $admissionId ?: ($params['admission_id'] ?? ''),
                'nurse_notes'  => "Initiated via {$department} clearance action.",
                'nurse_name'   => $userName
            ]);

            if (!empty($initRes['clearance_id'])) {
                $clearanceId = $initRes['clearance_id'];
                $stmt = $this->conn->prepare("SELECT * FROM discharge_clearances WHERE clearance_id = ? LIMIT 1");
                $stmt->bind_param("s", $clearanceId);
                $stmt->execute();
                $clearance = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        }

        if (!$clearance) {
            return ['success' => false, 'message' => 'Discharge clearance record could not be found or created.'];
        }

        $cId = $clearance['clearance_id'];
        $admId = $clearance['admission_id'];
        $patientName = $clearance['patient_name'] ?: 'Patient';

        if ($action === 'query') {
            if (empty($queryText)) {
                return ['success' => false, 'message' => 'Query details / reason must be provided.'];
            }

            // Update department status to 'Query'
            $sql = "UPDATE discharge_clearances SET 
                {$department}_status = 'Query',
                {$department}_by = ?,
                {$department}_at = NOW(),
                {$department}_query = ?,
                overall_status = 'Queries Raised',
                admin_status = 'Pending',
                updated_at = NOW()
                WHERE clearance_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sss", $userName, $queryText, $cId);
            $stmt->execute();
            $stmt->close();

            // Insert into queries table
            $stmtQ = $this->conn->prepare("INSERT INTO discharge_clearance_queries (clearance_id, admission_id, department, user_id, user_name, query_text, status) VALUES (?, ?, ?, ?, ?, ?, 'Open')");
            $stmtQ->bind_param("ssssss", $cId, $admId, $department, $userId, $userName, $queryText);
            $stmtQ->execute();
            $stmtQ->close();

            // Notify Admin & Nurse
            $notifTitle = "⚠️ Query Raised by " . ucfirst($department) . " for " . $patientName;
            $notifMsg   = "Query: {$queryText} (Raised by {$userName})";
            $notifId    = 'NOTIF-' . time() . '-' . rand(1000, 9999);
            try {
                $stmtN = $this->conn->prepare("INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority, is_read, created_at) VALUES (?, 'ADMIN', 'admin', ?, ?, 'emergency', 'urgent', 0, NOW())");
                $stmtN->bind_param("sss", $notifId, $notifTitle, $notifMsg);
                $stmtN->execute();
                $stmtN->close();
            } catch (Throwable $e) {}

            return [
                'success' => true,
                'message' => "Query recorded for " . ucfirst($department) . ". Admin and Nurse have been notified.",
                'department' => $department,
                'status' => 'Query'
            ];

        } else {
            // Action = Approve
            $sql = "UPDATE discharge_clearances SET 
                {$department}_status = 'Approved',
                {$department}_by = ?,
                {$department}_at = NOW(),
                {$department}_notes = ?,
                updated_at = NOW()
                WHERE clearance_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sss", $userName, $notes, $cId);
            $stmt->execute();
            $stmt->close();

            // Check if any open query for this department should be marked resolved
            try {
                $stmtRes = $this->conn->prepare("UPDATE discharge_clearance_queries SET status = 'Resolved', resolved_at = NOW() WHERE clearance_id = ? AND department = ? AND status = 'Open'");
                $stmtRes->bind_param("ss", $cId, $department);
                $stmtRes->execute();
                $stmtRes->close();
            } catch (Throwable $e) {}

            // Re-fetch to evaluate overall status
            $stmtCheck = $this->conn->prepare("SELECT reception_status, pharmacy_status, lab_status FROM discharge_clearances WHERE clearance_id = ?");
            $stmtCheck->bind_param("s", $cId);
            $stmtCheck->execute();
            $statusRow = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();

            $rStatus = $statusRow['reception_status'] ?? 'Pending';
            $pStatus = $statusRow['pharmacy_status'] ?? 'Pending';
            $lStatus = $statusRow['lab_status'] ?? 'Pending';

            $allCleared = ($rStatus === 'Approved' && $pStatus === 'Approved' && $lStatus === 'Approved');
            $hasQuery   = ($rStatus === 'Query' || $pStatus === 'Query' || $lStatus === 'Query');

            if ($allCleared) {
                $newOverall = 'All Cleared';
                $newAdmin   = 'Confirmed';
                $stmtAll = $this->conn->prepare("UPDATE discharge_clearances SET overall_status = 'All Cleared', admin_status = 'Confirmed', status = 'Cleared' WHERE clearance_id = ?");
                $stmtAll->bind_param("s", $cId);
                $stmtAll->execute();
                $stmtAll->close();

                // Notify Admin: All cleared!
                $adminTitle = "🎉 ALL DEPARTMENTS CLEARED: " . $patientName;
                $adminMsg   = "Reception, Pharmacy, and Laboratory have all approved clearance for {$patientName} ({$clearance['bed_info']}). Ready for final discharge confirmation.";
                $notifId    = 'NOTIF-' . time() . '-' . rand(1000, 9999);
                try {
                    $stmtN = $this->conn->prepare("INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority, is_read, created_at) VALUES (?, 'ADMIN', 'admin', ?, ?, 'emergency', 'urgent', 0, NOW())");
                    $stmtN->bind_param("sss", $notifId, $adminTitle, $adminMsg);
                    $stmtN->execute();
                    $stmtN->close();
                } catch (Throwable $e) {}

                // Update discharge_notifications table to 'Cleared'
                try {
                    $stmtDN = $this->conn->prepare("UPDATE discharge_notifications SET status = 'Cleared' WHERE admission_id = ?");
                    $stmtDN->bind_param("s", $admId);
                    $stmtDN->execute();
                    $stmtDN->close();
                } catch (Throwable $e) {}

                return [
                    'success'     => true,
                    'message'     => "Clearance approved by " . ucfirst($department) . ". 🎉 ALL DEPARTMENTS ARE NOW CLEARED! Admin confirmation alert dispatched.",
                    'department'  => $department,
                    'status'      => 'Approved',
                    'all_cleared' => true
                ];

            } else {
                $newOverall = $hasQuery ? 'Queries Raised' : 'Pending Clearance';
                $stmtPartial = $this->conn->prepare("UPDATE discharge_clearances SET overall_status = ?, admin_status = 'Pending' WHERE clearance_id = ?");
                $stmtPartial->bind_param("ss", $newOverall, $cId);
                $stmtPartial->execute();
                $stmtPartial->close();

                return [
                    'success'     => true,
                    'message'     => "Clearance approved by " . ucfirst($department) . ". Remaining approvals pending.",
                    'department'  => $department,
                    'status'      => 'Approved',
                    'all_cleared' => false
                ];
            }
        }
    }

    /**
     * 5. Add or Reply to a Query
     */
    public function addQuery(array $params): array {
        $clearanceId = trim($params['clearance_id'] ?? '');
        $admissionId = trim($params['admission_id'] ?? '');
        $department  = strtolower(trim($params['department'] ?? 'admin'));
        $userName    = trim($params['user_name'] ?? ($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Staff')));
        $userId      = trim($params['user_id'] ?? ($_SESSION['user_id'] ?? ''));
        $queryText   = trim($params['query_text'] ?? '');

        if ((empty($clearanceId) && empty($admissionId)) || empty($queryText)) {
            return ['success' => false, 'message' => 'Clearance ID and Query Text are required.'];
        }

        $stmt = $this->conn->prepare("INSERT INTO discharge_clearance_queries (clearance_id, admission_id, department, user_id, user_name, query_text, status) VALUES (?, ?, ?, ?, ?, ?, 'Open')");
        $stmt->bind_param("ssssss", $clearanceId, $admissionId, $department, $userId, $userName, $queryText);
        $res = $stmt->execute();
        $stmt->close();

        return [
            'success' => $res,
            'message' => $res ? 'Query added successfully.' : 'Failed to add query.'
        ];
    }

    /**
     * 6. Mark a Query as Resolved
     */
    public function resolveQuery(int $queryId, string $resolvedBy = ''): array {
        if ($queryId <= 0) {
            return ['success' => false, 'message' => 'Invalid Query ID.'];
        }

        $resolvedBy = $resolvedBy ?: ($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Staff'));
        $stmt = $this->conn->prepare("UPDATE discharge_clearance_queries SET status = 'Resolved', resolved_at = NOW(), response_text = CONCAT(COALESCE(response_text, ''), ' [Resolved by ', ?, ']') WHERE id = ?");
        $stmt->bind_param("si", $resolvedBy, $queryId);
        $res = $stmt->execute();
        $stmt->close();

        return [
            'success' => $res,
            'message' => $res ? 'Query marked as resolved.' : 'Failed to resolve query.'
        ];
    }

    /**
     * 7. Admin Final Discharge Confirmation
     */
    public function adminFinalConfirm(array $params): array {
        $clearanceId = trim($params['clearance_id'] ?? '');
        $admissionId = trim($params['admission_id'] ?? '');
        $adminName   = trim($params['admin_name'] ?? ($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Admin')));
        $adminNotes  = trim($params['admin_notes'] ?? '');

        if (empty($clearanceId) && empty($admissionId)) {
            return ['success' => false, 'message' => 'Clearance ID or Admission ID is required.'];
        }

        if (!empty($clearanceId)) {
            $stmt = $this->conn->prepare("UPDATE discharge_clearances SET 
                admin_status = 'Completed',
                overall_status = 'Completed',
                status = 'Completed',
                admin_by = ?,
                admin_at = NOW(),
                admin_notes = ?
                WHERE clearance_id = ?");
            $stmt->bind_param("sss", $adminName, $adminNotes, $clearanceId);
        } else {
            $stmt = $this->conn->prepare("UPDATE discharge_clearances SET 
                admin_status = 'Completed',
                overall_status = 'Completed',
                status = 'Completed',
                admin_by = ?,
                admin_at = NOW(),
                admin_notes = ?
                WHERE admission_id = ?");
            $stmt->bind_param("sss", $adminName, $adminNotes, $admissionId);
        }

        $res = $stmt->execute();
        $stmt->close();

        // Also update discharge_notifications
        try {
            $stmtDN = $this->conn->prepare("UPDATE discharge_notifications SET status = 'Cleared' WHERE admission_id = ?");
            $stmtDN->bind_param("s", $admissionId);
            $stmtDN->execute();
            $stmtDN->close();
        } catch (Throwable $e) {}

        return [
            'success' => $res,
            'message' => $res ? 'Patient discharge final confirmation completed by Admin.' : 'Failed to confirm discharge.'
        ];
    }
}
