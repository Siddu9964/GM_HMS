<?php
/**
 * API: Search patient for Appointment Bill
 * Uses exact column/table names confirmed from AppointmentModel.php
 */
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$keyword = trim($_GET['q'] ?? '');
if (strlen($keyword) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit();
}

try {
    require_once __DIR__ . '/../../models/Database.php';
    $db = new Database();
    $db->connect();

    $like = "%$keyword%";

    /* ── 1. Search appointments table ───────────────────────────────
       Confirmed column names from AppointmentModel.php:
       - PK: appointment_id  (NOT `id`)
       - consultation_fee is ON appointments table directly
       - doctor JOIN needs COLLATE utf8mb4_unicode_ci
    ──────────────────────────────────────────────────────────────── */
    $rows = $db->fetchAll("
        SELECT
            a.appointment_id,
            a.patient_id,
            a.patient_name,
            a.phone,
            a.appointment_date,
            a.appointment_time,
            a.appointment_status,
            a.consultation_fee,
            a.doctor_id,
            d.full_name     AS doctor_name,
            d.specialization,
            'appointment'   AS source
        FROM appointments a
        LEFT JOIN doctors d
               ON a.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id
        WHERE (
            a.patient_id   LIKE ? OR
            a.patient_name LIKE ? OR
            a.phone        LIKE ?
        )
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 10
    ", [$like, $like, $like]);

    /* ── 2. Fall back to `patient` table if nothing found ─────────── */
    if (empty($rows)) {
        $rows = $db->fetchAll("
            SELECT
                p.patient_id,
                CONCAT(
                    COALESCE(p.first_name, ''),
                    ' ',
                    COALESCE(p.last_name, '')
                )                AS patient_name,
                p.phone,
                p.created_at     AS appointment_date,
                NULL             AS appointment_time,
                NULL             AS appointment_status,
                NULL             AS appointment_id,
                NULL             AS doctor_id,
                NULL             AS doctor_name,
                NULL             AS specialization,
                NULL             AS consultation_fee,
                'patient'        AS source
            FROM patient p
            WHERE (
                p.patient_id LIKE ? OR
                CONCAT(
                    COALESCE(p.first_name,''),
                    ' ',
                    COALESCE(p.last_name,'')
                ) LIKE ? OR
                p.phone LIKE ?
            )
            ORDER BY p.created_at DESC
            LIMIT 10
        ", [$like, $like, $like]);
    }

    if (empty($rows)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit();
    }

    /* ── 3. First-time check & fee calculation ────────────────────── */
    foreach ($rows as &$row) {
        $pid = $row['patient_id'] ?? null;

        if ($pid && $row['source'] === 'patient') {
            // No appointment found earlier → check if any appointment exists NOW
            $cnt = $db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM appointments WHERE patient_id = ?",
                [$pid]
            );
            $row['is_first_time'] = (int)($cnt['cnt'] ?? 0) === 0;
        } else {
            // Came from appointments table → definitely returning
            $row['is_first_time'] = false;
        }

        // --- FALLBACK LOGIC ---
        // If consultation_fee is 0 on appointment, try to fetch from doctor table
        if (floatval($row['consultation_fee'] ?? 0) <= 0 && !empty($row['doctor_id'])) {
            $doc = $db->fetchOne("SELECT consultation_fee FROM doctors WHERE doctor_id = ?", [$row['doctor_id']]);
            if ($doc) {
                $row['consultation_fee'] = $doc['consultation_fee'];
            }
        }

        $row['registration_fee'] = $row['is_first_time'] ? 100 : 0;
        $row['consultation_fee'] = (float)($row['consultation_fee'] ?? 0);
        $row['total_bill']       = $row['consultation_fee'] + $row['registration_fee'];
    }
    unset($row);

    echo json_encode(['success' => true, 'data' => $rows]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
