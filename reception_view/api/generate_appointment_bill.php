<?php
/**
 * API: Generate Appointment Bill
 * Saves to opd_billing_master + opd_billing_items (bill_purpose = Registration/Appointment)
 */
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit();
}

try {
    require_once __DIR__ . '/../../models/Database.php';
    $db = new Database();
    $db->connect();

    $today   = date('Y-m-d');
    $nowTime = date('H:i:s');

    /* ── 1. Generate bill_id in OPB-YYYYMMDD-NNNN format ─────────────── */
    $prefix  = 'OPB';
    $dateStr = date('Ymd');
    $lastRow = $db->fetchOne(
        "SELECT bill_id FROM opd_billing_master WHERE bill_id LIKE ? ORDER BY bill_id DESC LIMIT 1",
        ["{$prefix}-{$dateStr}%"]
    );
    $newNum  = $lastRow ? (intval(substr($lastRow['bill_id'], -4)) + 1) : 1;
    $billId  = sprintf("%s-%s-%04d", $prefix, $dateStr, $newNum);

    $patientId     = $data['patient_id']     ?? null;
    $patientName   = $data['patient_name']   ?? null;
    $appointmentId = $data['appointment_id'] ?? null;
    $doctorId      = $data['doctor_id']      ?? null;
    $consultFee    = (float)($data['consultation_fee'] ?? 0);
    $regFee        = (float)($data['registration_fee'] ?? 0);
    $subtotal      = $consultFee + $regFee;
    
    /* ── 1a. Generate unique receipt_no (ORC + 6 digits) ───────────── */
    $orcPrefix = 'ORC';
    $lastOrc = $db->fetchOne(
        "SELECT receipt_no FROM opd_billing_master WHERE receipt_no LIKE 'ORC%' ORDER BY receipt_no DESC LIMIT 1"
    );
    if ($lastOrc && !empty($lastOrc['receipt_no'])) {
        $lastNum = intval(substr($lastOrc['receipt_no'], 3));
        $newOrcNum = $lastNum + 1;
    } else {
        $newOrcNum = 1;
    }
    $receiptNo = sprintf("%s%06d", $orcPrefix, $newOrcNum);
    
    $discAmt       = (float)($data['discount_amount'] ?? 0);
    $discPct       = (float)($data['discount_percentage'] ?? 0);
    $grandTotal    = max(0, $subtotal - $discAmt);
    
    $payMode       = $data['payment_mode']   ?? 'Cash';
    $isFirstTime   = !empty($data['is_first_time']);

    /* ── 2. Insert into opd_billing_master ────────────────────────────── */
    $db->execute("
        INSERT INTO opd_billing_master
            (bill_id, patient_id, appointment_id, doctor_id,
             bill_date, bill_time, purpose, subtotal, taxable_amount, 
             tax_amount, discount_amount, discount_percentage, grand_total, amount_paid,
             balance_due, payment_status, payment_mode, created_by, receipt_no)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ", [
        $billId,
        $patientId,
        $appointmentId,
        $doctorId,
        $today,
        $nowTime,
        'Registration/Appointment', // purpose
        $subtotal,                  // subtotal
        $grandTotal,               // taxable_amount
        0,                         // tax_amount
        $discAmt,                  // discount_amount
        $discPct,                  // discount_percentage
        $grandTotal,               // grand_total
        $grandTotal,               // amount_paid (paid upfront)
        0,                         // balance_due
        'Paid',
        $payMode,
        $_SESSION['full_name'] ?? $_SESSION['user_id'] ?? 'system',
        $receiptNo
    ]);

    /* ── 2a. Record in payment_receipts ──────────────────────────────── */
    $receiptPrefix = 'RCP-OPB';
    $receiptDate   = date('Ymd');
    $lastRcp = $db->fetchOne(
        "SELECT receipt_id FROM payment_receipts WHERE receipt_id LIKE ? ORDER BY receipt_id DESC LIMIT 1",
        ["{$receiptPrefix}-{$receiptDate}%"]
    );
    $rcpNum = $lastRcp ? (intval(substr($lastRcp['receipt_id'], -4)) + 1) : 1;
    $receiptId = sprintf("%s-%s-%04d", $receiptPrefix, $receiptDate, $rcpNum);

    $db->execute("
        INSERT INTO payment_receipts 
            (receipt_id, bill_id, bill_type, patient_id, 
             payment_date, payment_time, amount, payment_method, received_by)
        VALUES (?,?,?,?,?,?,?,?,?)
    ", [
        $receiptId, $billId, 'OPD', $patientId, 
        $today, $nowTime, $grandTotal, $payMode,
        $_SESSION['full_name'] ?? $_SESSION['user_id'] ?? 'system'
    ]);

    /* ── 3. Insert items into opd_billing_items ───────────────────────── */
    // Always insert consultation fee row
    if ($consultFee > 0) {
        $db->execute("
            INSERT INTO opd_billing_items
                (bill_id, receipt_no, bill_purpose, item_type, item_code, item_name, item_description,
                 quantity, unit_price, total_price,
                 is_taxable, tax_percentage, discount_amount)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ", [
            $billId,
            $receiptNo,
            'Registration/Appointment',
            'Consultation', // item_type
            'CONSULT-FEE',
            'Consultation Fee',
            'Doctor consultation charge',
            1,
            $consultFee,
            $consultFee,
            0, 0.00, 0.00
        ]);
    }

    // Insert registration fee row if applicable
    if ($regFee > 0) {
        $db->execute("
            INSERT INTO opd_billing_items
                (bill_id, receipt_no, bill_purpose, item_type, item_code, item_name, item_description,
                 quantity, unit_price, total_price,
                 is_taxable, tax_percentage, discount_amount)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ", [
            $billId,
            $receiptNo,
            'Registration/Appointment',
            'Registration Fee', // item_type
            'REG-FEE',
            'New Patient Registration Fee',
            'One-time registration charge for new patients',
            1,
            $regFee,
            $regFee,
            0, 0.00, 0.00
        ]);
    }

    /* ── 4. Return full bill data for print ───────────────────────────── */
    echo json_encode([
        'success'      => true,
        'bill_id'      => $billId,
        'patient_id'   => $patientId,
        'patient_name' => $patientName,
        'appointment_id' => $appointmentId,
        'doctor_id'    => $doctorId,
        'consult_fee'  => $consultFee,
        'reg_fee'      => $regFee,
        'total'        => $total,
        'pay_mode'     => $payMode,
        'receipt_id'   => $receiptId,
        'receipt_no'   => $receiptNo,
        'bill_date'    => $today,
        'bill_time'    => $nowTime,
        'is_first_time' => $isFirstTime,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
