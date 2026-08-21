<?php
namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

/**
 * OPD Billing Model
 */
class OpdBillingModel
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    public function createBill($billData, $items = [])
    {
        try {
            $this->db->beginTransaction();

            $billDate = $billData['bill_date'] ?? date('Y-m-d');
            $billTime = $billData['bill_time'] ?? date('H:i:s');
            $patientId = $billData['patient_id'];

            // ── Duplicate check: same patient, same date, same non-registration item names ──
            $existingBills = $this->db->fetchAll(
                "SELECT obm.bill_id, obi.item_name
                 FROM opd_billing_master obm
                 JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
                 WHERE obm.patient_id = ? AND obm.bill_date = ?",
                [$patientId, $billDate]
            );

            if (!empty($existingBills) && !empty($items)) {
                $existingItemNames = array_map(fn($r) => strtolower(trim($r['item_name'])), $existingBills);
                foreach ($items as $newItem) {
                    $newName = strtolower(trim($newItem['item_name'] ?? ''));
                    // Skip registration fee and generic items when checking for duplicate consultations/services
                    if ($newName && $newName !== 'registration fee' && in_array($newName, $existingItemNames)) {
                        $existingBillId = $existingBills[0]['bill_id'];
                        throw new Exception("Duplicate entry: a bill ({$existingBillId}) for this patient already exists today with the item '{$newItem['item_name']}'. Please check Recent Bills.");
                    }
                }
            }

            $billId = $this->generateBillId();

            $doctorName = trim($billData['doctor_name'] ?? '');
            $doctorId   = trim($billData['doctor_id'] ?? '');

            // Auto-resolve doctor name and id if missing or 'Walking'
            if (empty($doctorName) || $doctorName === 'Walking' || $doctorName === 'Walk-in') {
                if (!empty($billData['appointment_id'])) {
                    $apt = $this->db->fetchOne(
                        "SELECT a.doctor_name, a.doctor_id, d.full_name 
                         FROM appointments a 
                         LEFT JOIN doctors d ON a.doctor_id = d.doctor_id 
                         WHERE a.appointment_id = ?",
                        [$billData['appointment_id']]
                    );
                    if ($apt) {
                        $doctorName = !empty($apt['doctor_name']) ? $apt['doctor_name'] : ($apt['full_name'] ?? '');
                        if (empty($doctorId)) $doctorId = $apt['doctor_id'] ?? '';
                    }
                }
                if (empty($doctorName) && !empty($doctorId)) {
                    $doc = $this->db->fetchOne("SELECT full_name FROM doctors WHERE doctor_id = ?", [$doctorId]);
                    if ($doc && !empty($doc['full_name'])) {
                        $doctorName = $doc['full_name'];
                    }
                }
            }

            if (empty($doctorName)) {
                $doctorName = 'Walk-in';
            }

            $sql = "INSERT INTO opd_billing_master (
                        bill_id, patient_id, name, mobile, appointment_id, doctor_id, doctor_name,
                        bill_date, bill_time, referral_type, referred_by, sponsor,
                        purpose, notes,
                        discount_amount, discount_percentage,
                        service_id, item_name, payment_mode, created_by,
                        receipt_no
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";

            $receiptNo = $this->generateORCNumber();

            $this->db->execute($sql, [
                $billId,
                $patientId,
                $billData['name']           ?? '',
                $billData['mobile']         ?? 0,
                $billData['appointment_id'] ?? null,
                $doctorId,
                $doctorName,
                $billDate,
                $billTime,
                $billData['referral_type']  ?? '',
                $billData['referred_by']    ?? '',
                $billData['sponsor']        ?? '',
                $billData['purpose'] ?? 'OPD Service',
                $billData['notes']   ?? '',
                $billData['discount_amount']     ?? 0,
                $billData['discount_percentage'] ?? 0,
                $billData['service_id']  ?? '',
                $billData['item_name']   ?? '',
                $billData['payment_mode'] ?? 'Cash',
                $billData['created_by']  ?? 'system',
                $receiptNo
            ]);

            $hasLabItems = false;
            if (!empty($items)) {
                foreach ($items as $item) {
                    $this->addBillingItem($billId, $item, $receiptNo);
                    $code = strtoupper(trim($item['item_code'] ?? ''));
                    if (strpos($code, 'LAB') === 0 || strpos($code, 'OTH') === 0) {
                        $hasLabItems = true;
                    }

                    // Mark corresponding appointment as Paid
                    $itemName = $item['item_name'] ?? '';
                    if (str_contains(strtolower($itemName), 'consultation')) {
                        $this->db->execute(
                            "UPDATE appointments SET payment_status = 'Paid' 
                             WHERE patient_id = ? AND appointment_date = ? AND LOCATE(doctor_name, ?) > 0",
                            [$patientId, $billDate, $itemName]
                        );
                    }
                }
            }

            if (!empty($billData['appointment_id'])) {
                $this->db->execute(
                    "UPDATE appointments SET payment_status = 'Paid' WHERE appointment_id = ?",
                    [$billData['appointment_id']]
                );
            }

            $this->calculateTotals($billId);

            $this->logBillingAction($billId, 'Created', 'OPD bill created');

            if ($hasLabItems) {
                // Insert Notification for Laboratory (staff)
                $nid = 'NOT-' . strtoupper(substr(uniqid(), -6));
                $patientName = $billData['name'] ?? 'Walking Patient';
                $title = "New OPD Test Added";
                $message = "A new test has been added for {$patientName} ({$patientId}).";
                $this->db->execute(
                    "INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority, action_url) 
                     VALUES (?, 'staff', 'staff', ?, ?, 'lab_result', 'normal', 'test_orders.php')",
                    [$nid, $title, $message]
                );
            }

            $this->db->commit();
            return $billId;

        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function resolveItemType($type)
    {
        $valid = ['Consultation', 'Investigation', 'Procedure', 'Radiology', 'Scan', 'X-Ray', 'Blood Test', 'Medicine', 'Other', 'Follow-up Fee'];
        if (in_array($type, $valid))
            return $type;
        $t = strtolower($type ?? '');
        if (str_contains($t, 'consult'))
            return 'Consultation';
        if (str_contains($t, 'x-ray') || str_contains($t, 'xray'))
            return 'X-Ray';
        if (str_contains($t, 'scan') || str_contains($t, 'usg') || str_contains($t, 'echo') || str_contains($t, 'ultrasound'))
            return 'Scan';
        if (str_contains($t, 'ct') || str_contains($t, 'mri') || str_contains($t, 'radiol') || str_contains($t, 'imaging'))
            return 'Radiology';
        if (str_contains($t, 'blood') || str_contains($t, 'lab') || str_contains($t, 'test') || str_contains($t, 'path'))
            return 'Blood Test';
        if (str_contains($t, 'medicine') || str_contains($t, 'drug') || str_contains($t, 'tablet'))
            return 'Medicine';
        if (str_contains($t, 'procedure') || str_contains($t, 'ecg') || str_contains($t, 'dressing'))
            return 'Procedure';
        return 'Investigation'; // safe fallback
    }

    public function addBillingItem($billId, $item, $receiptNo = null)
    {
        $quantity = $item['quantity'] ?? 1;
        $unitPrice = $item['unit_price'];
        $totalPrice = $quantity * $unitPrice;

        // Resolve item_type if not explicitly provided
        $providedType = $item['item_type'] ?? $item['bill_purpose'] ?? '';
        $itemType = $this->resolveItemType($providedType ?: $item['item_name']);

        return $this->db->insert('opd_billing_items', [
            'bill_id' => $billId,
            'receipt_no' => $receiptNo,
            'bill_purpose' => $item['bill_purpose'] ?? 'OPD Service',
            'item_type' => $itemType,
            'item_code' => $item['item_code'] ?? null,
            'item_name' => $item['item_name'],
            'item_description' => $item['item_description'] ?? null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'is_taxable' => $item['is_taxable'] ?? false,
            'tax_percentage' => $item['tax_percentage'] ?? 0.00,
            'discount_amount' => $item['discount_amount'] ?? 0.00
        ]);
    }

    public function calculateTotals($billId)
    {
        $items = $this->db->fetchAll("SELECT * FROM opd_billing_items WHERE bill_id = ?", [$billId]);

        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($items as $item) {
            $itemTotal = $item['total_price'] - $item['discount_amount'];
            $subtotal += $itemTotal;
            $totalDiscount += $item['discount_amount'];

            if ($item['is_taxable']) {
                $taxAmount = ($itemTotal * $item['tax_percentage']) / 100;
                $totalTax += $taxAmount;
            }
        }

        $billData = $this->db->fetchOne("SELECT discount_amount, discount_percentage FROM opd_billing_master WHERE bill_id = ?", [$billId]);

        $billDiscount = $billData['discount_amount'] ?? 0;
        if (($billData['discount_percentage'] ?? 0) > 0) {
            $billDiscount = ($subtotal * $billData['discount_percentage']) / 100;
        }

        $taxableAmount = $subtotal - $billDiscount;
        $grandTotal = $taxableAmount + $totalTax;

        $this->db->execute("UPDATE opd_billing_master SET 
                        subtotal = ?, discount_amount = ?, taxable_amount = ?, 
                        tax_amount = ?, grand_total = ?, balance_due = grand_total - amount_paid
                      WHERE bill_id = ?", [
            $subtotal, $billDiscount + $totalDiscount, $taxableAmount,
            $totalTax, $grandTotal, $billId
        ]);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'grand_total' => $grandTotal,
            'discount_amount' => $billDiscount + $totalDiscount
        ];
    }

    public function recordPayment($billId, $paymentData)
    {
        try {
            $this->db->beginTransaction();
            
            // Use provided receipt_id or generate a new ORC one
            $receiptId = $paymentData['receipt_id'] ?? null;
            
            if (!$receiptId) {
                // For the very first payment of a bill, try to use the ORC number already assigned in master
                $master = $this->db->fetchOne("SELECT receipt_no FROM opd_billing_master WHERE bill_id = ?", [$billId]);
                $countRes = $this->db->fetchOne("SELECT COUNT(*) as count FROM payment_receipts WHERE bill_id = ?", [$billId]);
                
                if ($countRes['count'] == 0 && !empty($master['receipt_no'])) {
                    $receiptId = $master['receipt_no'];
                } else {
                    $receiptId = $this->generateORCNumber();
                }
            }

            $bill = $this->db->fetchOne("SELECT patient_id, grand_total, amount_paid FROM opd_billing_master WHERE bill_id = ?", [$billId]);
            if (!$bill)
                throw new Exception("Bill not found");

            $amount = $paymentData['amount'];
            $newAmountPaid = $bill['amount_paid'] + $amount;
            $balanceDue = $bill['grand_total'] - $newAmountPaid;

            $paymentStatus = ($balanceDue <= 0) ? 'Paid' : 'Pending';

            $this->db->insert('payment_receipts', [
                'receipt_id' => $receiptId,
                'bill_id' => $billId,
                'bill_type' => 'OPD',
                'patient_id' => $bill['patient_id'],
                'payment_date' => $paymentData['payment_date'] ?? date('Y-m-d'),
                'payment_time' => $paymentData['payment_time'] ?? date('H:i:s'),
                'amount' => $amount,
                'payment_method' => $paymentData['payment_mode'] ?? 'Cash',
                'transaction_id' => $paymentData['reference_no'] ?? null,
                'received_by' => $paymentData['received_by'] ?? 'system',
                'notes' => $paymentData['notes'] ?? null
            ]);

            $this->db->execute("UPDATE opd_billing_master SET 
                            amount_paid = ?, balance_due = ?, payment_status = ?
                          WHERE bill_id = ?", [$newAmountPaid, $balanceDue, $paymentStatus, $billId]);

            $this->logBillingAction($billId, 'Payment Received', "Payment of ₹{$amount} received");
            $this->db->commit();
            return $receiptId;
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getBillDetails($billId)
    {
        $bill = $this->db->fetchOne("SELECT obm.*,
                           COALESCE(p.first_name, a.patient_name) AS first_name,
                           p.last_name,
                           COALESCE(p.phone, a.phone) AS patient_phone,
                           a.appointment_date,
                           a.appointment_time AS apt_time,
                           a.reason,
                           COALESCE(obm.doctor_name, d.full_name, a.doctor_name) AS doctor_name,
                           d.specialization
                    FROM opd_billing_master obm
                    LEFT JOIN appointments a ON obm.appointment_id COLLATE utf8mb4_unicode_ci = a.appointment_id
                    LEFT JOIN patient p ON obm.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id
                    LEFT JOIN doctors d ON obm.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id
                    WHERE obm.bill_id = ?", [$billId]);

        if (!$bill)
            return null;

        // Construct full patient name if not already set or if we have first/last name
        if (!empty($bill['first_name'])) {
            $bill['patient_name'] = trim($bill['first_name'] . ' ' . ($bill['last_name'] ?? ''));
        }
        else {
            $bill['patient_name'] = 'Walking Patient';
        }

        $bill['items'] = $this->db->fetchAll("SELECT * FROM opd_billing_items WHERE bill_id = ? ORDER BY item_id", [$billId]);
        $bill['payments'] = $this->db->fetchAll("SELECT * FROM payment_receipts WHERE bill_id = ? ORDER BY payment_date DESC", [$billId]);

        return $bill;
    }

    public function getAllBills($filters = [])
    {
        $sql = "SELECT 
                    obm.*,
                    COALESCE(NULLIF(TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))), ''), a.patient_name, 'Walking Patient') AS patient_name,
                    COALESCE(p.phone, a.phone, obm.mobile) AS patient_phone,
                    p.email AS patient_email,
                    (SELECT COUNT(*) FROM opd_billing_master WHERE patient_id = obm.patient_id AND patient_id IS NOT NULL AND patient_id != '') AS visit_count,
                    a.appointment_date,
                    a.appointment_time,
                    a.reason,
                    COALESCE(obm.doctor_name, d.full_name, a.doctor_name) AS doctor_name,
                    d.specialization,
                    (SELECT receipt_id FROM payment_receipts WHERE bill_id = obm.bill_id ORDER BY (amount = obm.grand_total) DESC, receipt_id DESC LIMIT 1) AS primary_receipt_id
                FROM opd_billing_master obm
                LEFT JOIN appointments a ON obm.appointment_id COLLATE utf8mb4_unicode_ci = a.appointment_id
                LEFT JOIN patient p ON obm.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id
                LEFT JOIN doctors d ON obm.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['payment_status'])) {
            $sql .= " AND obm.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND obm.bill_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND obm.bill_date <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['patient_id'])) {
            $sql .= " AND obm.patient_id = ?";
            $params[] = $filters['patient_id'];
        }
        if (!empty($filters['purpose'])) {
            $sql .= " AND obm.purpose = ?";
            $params[] = $filters['purpose'];
        }
        if (!empty($filters['exclude_purpose'])) {
            $sql .= " AND (obm.purpose IS NULL OR obm.purpose != ?)";
            $params[] = $filters['exclude_purpose'];
        }
        if (!empty($filters['created_by'])) {
            $sql .= " AND obm.created_by = ?";
            $params[] = $filters['created_by'];
        }
        if (!empty($filters['payment_mode'])) {
            $sql .= " AND obm.payment_mode = ?";
            $params[] = $filters['payment_mode'];
        }

        $sql .= " ORDER BY obm.bill_date DESC, obm.bill_id DESC";
        
        if (isset($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    private function generateBillId()
    {
        $prefix = 'OPB';
        $dateStr = date('Ymd');
        $lastBill = $this->db->fetchOne("SELECT bill_id FROM opd_billing_master WHERE bill_id LIKE ? ORDER BY bill_id DESC LIMIT 1", ["{$prefix}-{$dateStr}%"]);
        $newNum = $lastBill ? (intval(substr($lastBill['bill_id'], -4)) + 1) : 1;
        return sprintf("%s-%s-%04d", $prefix, $dateStr, $newNum);
    }

    /**
     * Generate ORC + 6 digits unique receipt number
     * Checks both opd_billing_master and payment_receipts to ensure uniqueness
     */
    private function generateORCNumber()
    {
        $prefix = 'ORC';
        
        // Find max from master
        $lastMaster = $this->db->fetchOne(
            "SELECT receipt_no FROM opd_billing_master WHERE receipt_no LIKE 'ORC%' ORDER BY receipt_no DESC LIMIT 1"
        );
        $num1 = ($lastMaster && !empty($lastMaster['receipt_no'])) ? intval(substr($lastMaster['receipt_no'], 3)) : 0;

        // Find max from receipts
        $lastReceipt = $this->db->fetchOne(
            "SELECT receipt_id FROM payment_receipts WHERE receipt_id LIKE 'ORC%' ORDER BY receipt_id DESC LIMIT 1"
        );
        $num2 = ($lastReceipt && !empty($lastReceipt['receipt_id'])) ? intval(substr($lastReceipt['receipt_id'], 3)) : 0;

        $newNum = max($num1, $num2) + 1;
        
        // Ensure 6 digits padding
        return sprintf("%s%06d", $prefix, $newNum);
    }

    private function logBillingAction($billId, $action, $remarks = null)
    {
        $this->db->insert('billing_audit_log', [
            'bill_id' => $billId,
            'bill_type' => 'OPD',
            'action' => $action,
            'action_by' => 'system',
            'remarks' => $remarks
        ]);
    }

    public function updateBill($billId, $billData, $items = [])
    {
        try {
            $this->db->beginTransaction();

            $billDate = $billData['bill_date'] ?? date('Y-m-d');
            $billTime = $billData['bill_time'] ?? date('H:i:s');

            $sql = "UPDATE opd_billing_master SET 
                        patient_id = ?, appointment_id = ?, doctor_id = ?, doctor_name = ?,
                        bill_date = ?, bill_time = ?, purpose = ?, notes = ?,
                        discount_amount = ?, discount_percentage = ?,
                        service_id = ?, item_name = ?, payment_mode = ?
                    WHERE bill_id = ?";

            $this->db->execute($sql, [
                $billData['patient_id'],
                $billData['appointment_id'] ?? null,
                $billData['doctor_id']      ?? null,
                $billData['doctor_name']    ?? null,
                $billDate,
                $billTime,
                $billData['purpose'] ?? 'OPD Service',
                $billData['notes']   ?? null,
                $billData['discount_amount']     ?? 0,
                $billData['discount_percentage'] ?? 0,
                $billData['service_id']  ?? null,
                $billData['item_name']   ?? null,
                $billData['payment_mode'] ?? 'Cash',
                $billId
            ]);

            // Fetch the existing receipt_no for this bill
            $master = $this->db->fetchOne("SELECT receipt_no FROM opd_billing_master WHERE bill_id = ?", [$billId]);
            $receiptNo = $master['receipt_no'] ?? null;

            // Clear existing items and re-add to handle changes cleanly
            $this->db->execute("DELETE FROM opd_billing_items WHERE bill_id = ?", [$billId]);

            if (!empty($items)) {
                foreach ($items as $item) {
                    $this->addBillingItem($billId, $item, $receiptNo);
                }
            }

            $this->calculateTotals($billId);
            $this->logBillingAction($billId, 'Updated', 'OPD bill updated from UI');

            $this->db->commit();
            return true;
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function deleteBill($billId)
    {
        try {
            $this->db->beginTransaction();

            // Log deletion before data is removed
            $this->logBillingAction($billId, 'Deleted', 'OPD bill deleted from UI');

            // Delete payments and items first due to foreign keys (if strict)
            $this->db->execute("DELETE FROM payment_receipts WHERE bill_id = ?", [$billId]);
            $this->db->execute("DELETE FROM opd_billing_items WHERE bill_id = ?", [$billId]);
            $this->db->execute("DELETE FROM opd_billing_master WHERE bill_id = ?", [$billId]);

            $this->db->commit();
            return true;
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getStatistics()
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $todayRev = $this->db->fetchOne("SELECT SUM(amount_paid) as rev FROM opd_billing_master WHERE bill_date = ?", [$today]);
        $monthRev = $this->db->fetchOne("SELECT SUM(amount_paid) as rev FROM opd_billing_master WHERE bill_date >= ?", [$monthStart]);
        $pendingCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM opd_billing_master WHERE payment_status IN ('Pending', 'Partial')");
        $outstanding = $this->db->fetchOne("SELECT SUM(balance_due) as amount FROM opd_billing_master WHERE payment_status IN ('Pending', 'Partial')");

        return [
            'today_revenue' => $todayRev['rev'] ?? 0,
            'month_revenue' => $monthRev['rev'] ?? 0,
            'pending_bills' => $pendingCount['count'] ?? 0,
            'outstanding_amount' => $outstanding['amount'] ?? 0
        ];
    }

    /**
     * Get Daily Stats for Appointment Billing Dashboard
     */
    public function getDailyStats()
    {
        $today = date('Y-m-d');

        try {
            $totalBills = $this->db->fetchOne("SELECT COUNT(*) as count FROM opd_billing_master WHERE bill_date = ? AND purpose = 'Registration/Appointment'", [$today]);
            $totalRev   = $this->db->fetchOne("SELECT SUM(amount_paid) as rev FROM opd_billing_master WHERE bill_date = ? AND purpose = 'Registration/Appointment'", [$today]);
            $pending    = $this->db->fetchOne("SELECT COUNT(*) as count FROM opd_billing_master WHERE bill_date = ? AND purpose = 'Registration/Appointment' AND payment_status IN ('Pending', 'Partial')", [$today]);
            
            // Count registrations by checking items table for 'Registration Fee'
            $newReg = $this->db->fetchOne("
                SELECT COUNT(DISTINCT obm.bill_id) as count 
                FROM opd_billing_master obm
                JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
                WHERE obm.bill_date = ? 
                AND obm.purpose = 'Registration/Appointment' 
                AND (obi.item_name LIKE '%Registration%' OR obi.item_name LIKE '%Reg%')
            ", [$today]);

            return [
                'total_bills'       => (int)($totalBills['count'] ?? 0),
                'total_amount'      => (float)($totalRev['rev'] ?? 0),
                'pending_count'     => (int)($pending['count'] ?? 0),
                'new_registrations' => (int)($newReg['count'] ?? 0)
            ];
        } catch (\Exception $e) {
            // Log error but return zeros so UI doesn't crash
            return [
                'total_bills'       => 0,
                'total_amount'      => 0,
                'pending_count'     => 0,
                'new_registrations' => 0
            ];
        }
    }

    public function getConsultationFeeByPatient($patientId, $currentAppointmentId = '')
    {
        $fee = 0.00;
        $isFollowup = false;
        $consultations = [];

        // 1. Check if patient has already paid Registration Fee today or in prior bills
        $regCheck = $this->db->fetchOne(
            "SELECT obi.item_id 
             FROM opd_billing_master obm
             JOIN opd_billing_items obi ON obm.bill_id COLLATE utf8mb4_unicode_ci = obi.bill_id COLLATE utf8mb4_unicode_ci
             WHERE obm.patient_id COLLATE utf8mb4_unicode_ci = ? 
               AND (obi.item_type = 'Registration Fee' OR LOWER(obi.item_name) LIKE '%registration%')
             LIMIT 1",
            [$patientId]
        );
        $isRegistrationPaid = !empty($regCheck);

        // 2. Determine target appointment date and doctor context
        $targetDate = null;
        $currentDocId = null;
        $currentDocName = null;
        if (!empty($currentAppointmentId)) {
            $currentApt = $this->db->fetchOne(
                "SELECT d.consultation_fee, a.appointment_date, a.doctor_id, d.full_name as doctor_name 
                 FROM appointments a
                 JOIN doctors d ON a.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id COLLATE utf8mb4_unicode_ci
                 WHERE a.appointment_id = ?",
                [$currentAppointmentId]
            );
            if ($currentApt) {
                $fee = (float)$currentApt['consultation_fee'];
                $targetDate = $currentApt['appointment_date'];
                $currentDocId = $currentApt['doctor_id'];
                $currentDocName = $currentApt['doctor_name'];
            }
        }

        if (empty($targetDate)) {
            // Find most recent appointment date for this patient
            $latest = $this->db->fetchOne(
                "SELECT a.appointment_date, a.doctor_id, d.full_name as doctor_name, d.consultation_fee 
                 FROM appointments a
                 JOIN doctors d ON a.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id COLLATE utf8mb4_unicode_ci
                 WHERE a.patient_id COLLATE utf8mb4_unicode_ci = ? 
                 ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                 LIMIT 1",
                [$patientId]
            );
            if ($latest) {
                $targetDate = $latest['appointment_date'];
                $currentDocId = $latest['doctor_id'];
                $currentDocName = $latest['doctor_name'];
                $fee = (float)$latest['consultation_fee'];
            } else {
                $targetDate = date('Y-m-d');
            }
        }

        // 3. Fetch ONLY PENDING/UNPAID non-cancelled appointments for this patient on this target date
        $sqlDateApts = "SELECT a.appointment_id, a.doctor_id, d.full_name as doctor_name, d.consultation_fee, a.appointment_date 
                        FROM appointments a
                        JOIN doctors d ON a.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id COLLATE utf8mb4_unicode_ci
                        WHERE a.patient_id COLLATE utf8mb4_unicode_ci = ? 
                          AND a.appointment_date = ? 
                          AND a.appointment_status != 'Cancelled'
                          AND (a.payment_status IS NULL OR a.payment_status = 'Pending' OR a.payment_status = '')
                        ORDER BY a.appointment_time ASC, a.appointment_id ASC";
        $dateApts = $this->db->fetchAll($sqlDateApts, [$patientId, $targetDate]);

        if (!empty($dateApts)) {
            foreach ($dateApts as $apt) {
                $docId = $apt['doctor_id'];
                $docName = $apt['doctor_name'];

                // Check if this SAME DOCTOR was already billed for this patient in the last 0-3 days
                $sameDocBill = $this->db->fetchOne(
                    "SELECT obm.bill_date 
                     FROM opd_billing_master obm 
                     JOIN opd_billing_items obi ON obm.bill_id COLLATE utf8mb4_unicode_ci = obi.bill_id COLLATE utf8mb4_unicode_ci
                     WHERE obm.patient_id COLLATE utf8mb4_unicode_ci = ? 
                       AND (obm.doctor_id COLLATE utf8mb4_unicode_ci = ? OR obm.doctor_name COLLATE utf8mb4_unicode_ci = ? OR LOCATE(?, obi.item_name) > 0)
                       AND obm.bill_date <= ?
                     ORDER BY obm.bill_date DESC, obm.bill_time DESC LIMIT 1",
                    [$patientId, $docId, $docName, $docName, $targetDate]
                );

                $docIsFollowup = false;
                $docFee = (float)$apt['consultation_fee'];

                if ($sameDocBill) {
                    $targetTime = strtotime($targetDate);
                    $billTime = strtotime($sameDocBill['bill_date']);
                    $daysDiff = ($targetTime - $billTime) / 86400;

                    if ($daysDiff >= 0 && $daysDiff <= 3) {
                        // SAME DOCTOR within 0-3 days -> Follow-up Fee
                        $docIsFollowup = true;
                        $docFee = 300.00;
                    }
                }

                $consultations[] = [
                    'appointment_id'   => $apt['appointment_id'],
                    'doctor_id'        => $apt['doctor_id'],
                    'doctor_name'      => $apt['doctor_name'],
                    'consultation_fee' => $docFee,
                    'is_followup'      => $docIsFollowup
                ];
            }
        } elseif (!empty($currentDocId)) {
            // Walk-in with specific doctor
            $sameDocBill = $this->db->fetchOne(
                "SELECT obm.bill_date 
                 FROM opd_billing_master obm 
                 JOIN opd_billing_items obi ON obm.bill_id COLLATE utf8mb4_unicode_ci = obi.bill_id COLLATE utf8mb4_unicode_ci
                 WHERE obm.patient_id COLLATE utf8mb4_unicode_ci = ? 
                   AND (obm.doctor_id COLLATE utf8mb4_unicode_ci = ? OR obm.doctor_name COLLATE utf8mb4_unicode_ci = ? OR LOCATE(?, obi.item_name) > 0)
                   AND obm.bill_date <= ?
                 ORDER BY obm.bill_date DESC, obm.bill_time DESC LIMIT 1",
                [$patientId, $currentDocId, $currentDocName, $currentDocName, $targetDate]
            );

            if ($sameDocBill) {
                $targetTime = strtotime($targetDate);
                $billTime = strtotime($sameDocBill['bill_date']);
                $daysDiff = ($targetTime - $billTime) / 86400;

                if ($daysDiff >= 0 && $daysDiff <= 3) {
                    $isFollowup = true;
                    $fee = 300.00;
                }
            }
        }

        return [
            'fee'                  => $fee,
            'is_followup'          => $isFollowup,
            'is_registration_paid' => $isRegistrationPaid,
            'consultations'        => $consultations
        ];
    }

    /**
     * Search patients from appointments+patient tables
     */
    public function searchPatients($query)
    {
        $like = '%' . $query . '%';
        $sql = "SELECT 
                    p.patient_id,
                    TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))) AS patient_name,
                    p.phone,
                    p.age,
                    p.sex,
                    p.blood_group,
                    COALESCE(a.appointment_id, CONCAT('NOAPT-', p.patient_id)) as appointment_id,
                    COALESCE(a.appointment_date, obm.last_date) as appointment_date,
                    COALESCE(a.appointment_time, obm.last_time) as appointment_time,
                    COALESCE(a.doctor_id, obm.last_doctor_id) as doctor_id,
                    COALESCE(a.doctor_name, obm.last_doctor_name) as doctor_name,
                    d.consultation_fee as doctor_fee,
                    a.reason,
                    a.appointment_type,
                    COALESCE(a.appointment_status, obm.last_status) as appointment_status
                FROM patient p
                LEFT JOIN (
                    -- Get latest appointment per patient
                    SELECT a1.*
                    FROM appointments a1
                    JOIN (
                        SELECT patient_id, MAX(appointment_date) as max_date, MAX(appointment_id) as max_id
                        FROM appointments
                        GROUP BY patient_id
                    ) a2 ON a1.patient_id = a2.patient_id AND a1.appointment_id = a2.max_id
                ) a ON p.patient_id = a.patient_id
                LEFT JOIN (
                    -- Get latest bill per patient
                    SELECT obm1.patient_id, obm1.doctor_id as last_doctor_id, obm1.doctor_name as last_doctor_name, 
                           obm1.status as last_status, obm1.bill_date as last_date, obm1.bill_time as last_time
                    FROM opd_billing_master obm1
                    JOIN (
                        SELECT patient_id, MAX(bill_id) as max_bill_id 
                        FROM opd_billing_master 
                        GROUP BY patient_id
                    ) obm2 ON obm1.bill_id = obm2.max_bill_id
                ) obm ON p.patient_id = obm.patient_id
                LEFT JOIN doctors d ON d.doctor_id = COALESCE(a.doctor_id, obm.last_doctor_id)
                WHERE (
                    p.patient_id LIKE ? OR
                    p.phone LIKE ? OR
                    TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))) LIKE ?
                )
                ORDER BY COALESCE(a.appointment_date, obm.last_date) DESC, p.patient_id ASC
                LIMIT 20";
        return $this->db->fetchAll($sql, [$like, $like, $like]);
    }

    /**
     /**
     * Get all services from lab_services, other_services, radiology_services
     * Returns unified shape: service_id, billing_name, modality_name, opd_price
     */
    public function getAllServices()
    {
        try {
            $sql = "
                SELECT service_id   AS service_id,
                       test_name    AS billing_name,
                       'Lab'        AS modality_name,
                       opd_rate     AS opd_price
                FROM lab_services
                WHERE opd_rate IS NOT NULL AND opd_rate > 0

                UNION ALL

                SELECT service_id,
                       billing_name,
                       'Other'      AS modality_name,
                       op_gw_price  AS opd_price
                FROM other_services
                WHERE billing_name IS NOT NULL

                UNION ALL

                SELECT service_id,
                       billing_name,
                       modality_name,
                       opd_price
                FROM radiology_services
                WHERE billing_name IS NOT NULL

                ORDER BY modality_name ASC, billing_name ASC
            ";
            return $this->db->fetchAll($sql);
        }
        catch (\Exception $e) {
            return [];
        }
    }
    public function saveReferral($name, $mobile, $addBy) {
        try {
            $sql = "INSERT INTO referral_data (name, mobile, add_by) VALUES (?, ?, ?)";
            return $this->db->execute($sql, [$name, $mobile, $addBy]);
        } catch (\Exception $e) {
            error_log("Error in saveReferral: " . $e->getMessage());
            return false;
        }
    }

    public function saveSponsor($name, $type = null) {
        try {
            // Check if the sponsor already exists to prevent duplicate entries
            $existing = $this->db->fetchOne("SELECT sponsor_name FROM sponsors_data WHERE sponsor_name = ?", [$name]);
            if ($existing) {
                // If it already exists, we return true so the UI can proceed and just use the existing one
                return true; 
            }

            $sql = "INSERT INTO sponsors_data (sponsor_name, sponsor_type) VALUES (?, ?)";
            return $this->db->execute($sql, [$name, $type]);
        } catch (\Exception $e) {
            error_log("Error in saveSponsor: " . $e->getMessage());
            return false;
        }
    }
    public function searchReferrals($query) {
        try {
            // Search dedicated referral_data table
            $sql = "SELECT name, mobile FROM referral_data 
                    WHERE name LIKE ? OR mobile LIKE ?
                    ORDER BY name ASC LIMIT 10";
            return $this->db->fetchAll($sql, ["%$query%", "%$query%"]);
        } catch (\Exception $e) {
            error_log("Error in searchReferrals: " . $e->getMessage());
            return [];
        }
    }

    public function searchSponsors($query) {
        try {
            // Search dedicated sponsors_data table
            $sql = "SELECT DISTINCT sponsor_name as name FROM sponsors_data 
                    WHERE sponsor_name LIKE ? 
                    AND sponsor_name IS NOT NULL 
                    AND sponsor_name != '' 
                    ORDER BY sponsor_name ASC LIMIT 10";
            return $this->db->fetchAll($sql, ["%$query%"]);
        } catch (\Exception $e) {
            error_log("Error in searchSponsors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get comprehensive analytics data for OPD Billing Dashboard
     */
    public function getAnalyticsData($filters = []) {
        try {
            $conditions = [];
            $params = [];

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $conditions[] = "DATE(bill_date) BETWEEN ? AND ?";
                // Ensure dates are in YYYY-MM-DD format
                $params[] = date('Y-m-d', strtotime(str_replace('/', '-', $filters['start_date'])));
                $params[] = date('Y-m-d', strtotime(str_replace('/', '-', $filters['end_date'])));
            }

            if (!empty($filters['receptionist'])) {
                $conditions[] = "created_by = ?";
                $params[] = $filters['receptionist'];
            }

            if (!empty($filters['payment_mode'])) {
                $conditions[] = "payment_mode = ?";
                $params[] = $filters['payment_mode'];
            }

            if (!empty($filters['payment_status'])) {
                $conditions[] = "payment_status = ?";
                $params[] = $filters['payment_status'];
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            // 1. Key Metrics
            $metricsSql = "SELECT 
                COUNT(*) as total_bills,
                SUM(grand_total) as total_billing_amount,
                SUM(amount_paid) as total_collected,
                SUM(balance_due) as total_pending,
                SUM(discount_amount) as total_discount,
                SUM(CASE WHEN payment_status = 'Cancelled' THEN grand_total ELSE 0 END) as total_refund,
                COUNT(CASE WHEN payment_status = 'Cancelled' THEN 1 END) as cancelled_bills
            FROM opd_billing_master $whereClause";
            
            $metrics = $this->db->fetchOne($metricsSql, $params) ?: [
                'total_bills' => 0, 'total_billing_amount' => 0, 'total_collected' => 0, 
                'total_pending' => 0, 'total_discount' => 0, 'total_refund' => 0, 'cancelled_bills' => 0
            ];

            // 2. Receptionist Performance
            $receptionistSql = "SELECT 
                created_by as receptionist,
                COUNT(*) as bills_generated,
                SUM(grand_total) as total_billing,
                SUM(amount_paid) as collected,
                SUM(balance_due) as pending
            FROM opd_billing_master 
            $whereClause 
            GROUP BY created_by 
            ORDER BY collected DESC";
            
            $receptionistPerformance = $this->db->fetchAll($receptionistSql, $params) ?: [];

            // 3. Payment Method Breakdown
            $paymentMethodWhere = !empty($whereClause) ? $whereClause . " AND payment_status != 'Cancelled'" : "WHERE payment_status != 'Cancelled'";
            
            $paymentMethodSql = "SELECT 
                IFNULL(payment_mode, 'Unspecified') as method,
                SUM(amount_paid) as total
            FROM opd_billing_master 
            $paymentMethodWhere
            GROUP BY payment_mode
            ORDER BY total DESC";
            
            $paymentMethods = $this->db->fetchAll($paymentMethodSql, $params) ?: [];

            // 4. Trends (Daily/Monthly)
            // If date range is > 31 days, group by month, else group by day
            $groupByFormat = "%Y-%m-%d";
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $start = new \DateTime($filters['start_date']);
                $end = new \DateTime($filters['end_date']);
                if ($start->diff($end)->days > 31) {
                    $groupByFormat = "%Y-%m";
                }
            }

            $trendsSql = "SELECT 
                DATE_FORMAT(bill_date, '$groupByFormat') as trend_date,
                SUM(grand_total) as revenue,
                SUM(amount_paid) as collections
            FROM opd_billing_master 
            $whereClause 
            GROUP BY trend_date 
            ORDER BY trend_date ASC";
            
            $trends = $this->db->fetchAll($trendsSql, $params) ?: [];

            return [
                'metrics' => $metrics,
                'receptionist_performance' => $receptionistPerformance,
                'payment_methods' => $paymentMethods,
                'trends' => $trends
            ];

        } catch (\Exception $e) {
            error_log("Error in getAnalyticsData: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Advanced Receipts Query Engine
     * Supports multidimensional filtering, pagination, KPI summaries, and shift handover breakdown
     */
    public function getAdvancedReceipts($filters = [])
    {
        try {
            $conditions = [];
            $params = [];

            // 1. Date Range Handling (Presets & Custom)
            $dateFrom = $filters['date_from'] ?? null;
            $dateTo = $filters['date_to'] ?? null;
            $preset = $filters['date_preset'] ?? null;

            if ($preset) {
                $today = date('Y-m-d');
                switch ($preset) {
                    case 'today':
                        $dateFrom = $today;
                        $dateTo = $today;
                        break;
                    case 'yesterday':
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        $dateFrom = $yesterday;
                        $dateTo = $yesterday;
                        break;
                    case 'this_week':
                        $dateFrom = date('Y-m-d', strtotime('monday this week'));
                        $dateTo = date('Y-m-d', strtotime('sunday this week'));
                        break;
                    case 'this_month':
                        $dateFrom = date('Y-m-01');
                        $dateTo = date('Y-m-t');
                        break;
                    case 'this_year':
                        $dateFrom = date('Y-01-01');
                        $dateTo = date('Y-12-31');
                        break;
                }
            }

            if (!empty($dateFrom) && !empty($dateTo)) {
                $conditions[] = "obm.bill_date BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            } elseif (!empty($dateFrom)) {
                $conditions[] = "obm.bill_date >= ?";
                $params[] = $dateFrom;
            } elseif (!empty($dateTo)) {
                $conditions[] = "obm.bill_date <= ?";
                $params[] = $dateTo;
            }

            // 2. Omni-Search (Bill ID, Receipt No, Patient ID, Patient Name, Mobile, Doctor, Appointment)
            if (!empty($filters['search'])) {
                $q = '%' . trim($filters['search']) . '%';
                $conditions[] = "(
                    obm.bill_id LIKE ? 
                    OR obm.receipt_no LIKE ? 
                    OR pr.receipt_id LIKE ? 
                    OR obm.patient_id LIKE ? 
                    OR obm.name LIKE ? 
                    OR p.first_name LIKE ? 
                    OR p.last_name LIKE ? 
                    OR a.patient_name LIKE ? 
                    OR obm.mobile LIKE ? 
                    OR p.phone LIKE ? 
                    OR a.phone LIKE ? 
                    OR obm.appointment_id LIKE ? 
                    OR obm.doctor_name LIKE ? 
                    OR d.full_name LIKE ?
                )";
                for ($i = 0; $i < 14; $i++) {
                    $params[] = $q;
                }
            }

            // 3. User / Cashier Filter
            if (!empty($filters['created_by'])) {
                $conditions[] = "obm.created_by = ?";
                $params[] = $filters['created_by'];
            }

            // 4. Department / Medical Specialization Filter
            if (!empty($filters['department'])) {
                $conditions[] = "(dept.department_name = ? OR d.specialization = ? OR obm.purpose = ? OR obi_dept.item_type = ?)";
                $params[] = $filters['department'];
                $params[] = $filters['department'];
                $params[] = $filters['department'];
                $params[] = $filters['department'];
            }

            // 5. Doctor Filter
            if (!empty($filters['doctor'])) {
                $conditions[] = "(obm.doctor_id = ? OR obm.doctor_name LIKE ? OR d.full_name LIKE ?)";
                $params[] = $filters['doctor'];
                $params[] = '%' . $filters['doctor'] . '%';
                $params[] = '%' . $filters['doctor'] . '%';
            }

            // 6. Payment Status Filter
            if (!empty($filters['payment_status'])) {
                $conditions[] = "obm.payment_status = ?";
                $params[] = $filters['payment_status'];
            }

            // 7. Payment Mode Filter
            if (!empty($filters['payment_mode'])) {
                $conditions[] = "obm.payment_mode = ?";
                $params[] = $filters['payment_mode'];
            }

            // 8. Outstanding Balance Filter
            if (!empty($filters['has_outstanding']) && $filters['has_outstanding'] !== 'false') {
                $conditions[] = "obm.balance_due > 0";
            }

            // 9. High Value Filter (> 5000)
            if (!empty($filters['high_value']) && $filters['high_value'] !== 'false') {
                $conditions[] = "obm.grand_total >= 5000";
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            // Base query with joins
            $baseFrom = "
                FROM opd_billing_master obm
                LEFT JOIN appointments a ON obm.appointment_id COLLATE utf8mb4_unicode_ci = a.appointment_id
                LEFT JOIN patient p ON obm.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id
                LEFT JOIN doctors d ON (
                    (obm.doctor_id IS NOT NULL AND obm.doctor_id != '' AND obm.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id)
                    OR (obm.doctor_name IS NOT NULL AND obm.doctor_name != '' AND (
                        d.full_name COLLATE utf8mb4_unicode_ci = obm.doctor_name 
                        OR obm.doctor_name LIKE CONCAT('%', d.full_name, '%')
                        OR d.full_name LIKE CONCAT('%', obm.doctor_name, '%')
                    ))
                )
                LEFT JOIN departments dept ON d.department_id = dept.department_id
                LEFT JOIN (
                    SELECT bill_id, GROUP_CONCAT(DISTINCT item_type SEPARATOR ', ') as item_type, COUNT(*) as item_count
                    FROM opd_billing_items 
                    GROUP BY bill_id
                ) obi_dept ON obm.bill_id = obi_dept.bill_id
                LEFT JOIN (
                    SELECT bill_id, MAX(receipt_id) as receipt_id, MAX(payment_date) as payment_date, MAX(payment_time) as payment_time, MAX(payment_method) as payment_method, SUM(amount) as amount
                    FROM payment_receipts
                    GROUP BY bill_id
                ) pr ON obm.bill_id = pr.bill_id
            ";

            // Count total records for pagination
            $countSql = "SELECT COUNT(DISTINCT obm.bill_id) as total_count $baseFrom $whereClause";
            $countRes = $this->db->fetchOne($countSql, $params);
            $totalRecords = $countRes ? (int)$countRes['total_count'] : 0;

            // Summary KPIs across the filtered dataset
            $kpiSql = "
                SELECT 
                    COUNT(DISTINCT obm.bill_id) as total_bills,
                    COALESCE(SUM(obm.grand_total), 0) as total_billing,
                    COALESCE(SUM(obm.amount_paid), 0) as total_collected,
                    COALESCE(SUM(obm.balance_due), 0) as total_pending,
                    COALESCE(SUM(obm.discount_amount), 0) as total_discount,
                    COALESCE(SUM(CASE WHEN obm.payment_status = 'Cancelled' THEN obm.grand_total ELSE 0 END), 0) as total_refunds,
                    COUNT(CASE WHEN obm.payment_status = 'Paid' THEN 1 END) as paid_bills_count,
                    COUNT(CASE WHEN obm.payment_status = 'Partial' THEN 1 END) as partial_bills_count,
                    COUNT(CASE WHEN obm.payment_status = 'Pending' THEN 1 END) as pending_bills_count,
                    COUNT(CASE WHEN obm.payment_status = 'Cancelled' THEN 1 END) as cancelled_bills_count
                $baseFrom 
                $whereClause
            ";
            $kpiRes = $this->db->fetchOne($kpiSql, $params) ?: [];
            
            $totalBills = (int)($kpiRes['total_bills'] ?? 0);
            $totalBilling = (float)($kpiRes['total_billing'] ?? 0);
            $totalCollected = (float)($kpiRes['total_collected'] ?? 0);
            $avgBillValue = $totalBills > 0 ? round($totalBilling / $totalBills, 2) : 0;

            // Today's Collection KPI for Quick Comparison
            $todayStats = $this->db->fetchOne("
                SELECT 
                    COALESCE(SUM(amount_paid), 0) as today_collection,
                    COUNT(*) as today_bills
                FROM opd_billing_master 
                WHERE bill_date = CURDATE() AND payment_status != 'Cancelled'
            ") ?: ['today_collection' => 0, 'today_bills' => 0];

            $summaryKPIs = [
                'total_bills' => $totalBills,
                'total_collection' => $totalCollected,
                'total_pending' => (float)($kpiRes['total_pending'] ?? 0),
                'total_discount' => (float)($kpiRes['total_discount'] ?? 0),
                'total_refunds' => (float)($kpiRes['total_refunds'] ?? 0),
                'avg_bill_value' => $avgBillValue,
                'paid_bills_count' => (int)($kpiRes['paid_bills_count'] ?? 0),
                'partial_bills_count' => (int)($kpiRes['partial_bills_count'] ?? 0),
                'pending_bills_count' => (int)($kpiRes['pending_bills_count'] ?? 0),
                'cancelled_bills_count' => (int)($kpiRes['cancelled_bills_count'] ?? 0),
                'today_collection' => (float)($todayStats['today_collection'] ?? 0),
                'today_bills' => (int)($todayStats['today_bills'] ?? 0)
            ];

            // Sorting logic
            $sortBy = $filters['sort_by'] ?? 'date_desc';
            $orderClause = "ORDER BY obm.bill_date DESC, obm.bill_time DESC, obm.bill_id DESC";
            switch ($sortBy) {
                case 'date_asc':
                    $orderClause = "ORDER BY obm.bill_date ASC, obm.bill_time ASC, obm.bill_id ASC";
                    break;
                case 'amount_desc':
                    $orderClause = "ORDER BY obm.grand_total DESC";
                    break;
                case 'amount_asc':
                    $orderClause = "ORDER BY obm.grand_total ASC";
                    break;
                case 'patient_name':
                    $orderClause = "ORDER BY patient_name ASC";
                    break;
            }

            // Pagination limit and offset
            $limit = isset($filters['limit']) ? (int)$filters['limit'] : 25;
            if ($limit <= 0) $limit = 25;
            if ($limit > 500) $limit = 500;
            
            $page = isset($filters['page']) ? (int)$filters['page'] : 1;
            if ($page <= 0) $page = 1;
            $offset = ($page - 1) * $limit;

            // Fetch Main Data Records
            $dataSql = "
                SELECT 
                    obm.bill_id,
                    COALESCE(obm.receipt_no, pr.receipt_id, REPLACE(obm.bill_id, 'OPB-', 'ORC-'), REPLACE(obm.bill_id, 'BILL-', 'REC-')) as receipt_id,
                    obm.patient_id,
                    COALESCE(NULLIF(TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))), ''), a.patient_name, obm.name, 'Walking Patient') AS patient_name,
                    COALESCE(p.phone, a.phone, obm.mobile, '-') AS patient_phone,
                    p.sex,
                    p.age,
                    p.blood_group,
                    obm.bill_date,
                    obm.bill_time,
                    obm.created_at,
                    obm.subtotal,
                    obm.discount_amount,
                    obm.discount_percentage,
                    obm.taxable_amount,
                    obm.tax_amount,
                    obm.grand_total,
                    obm.amount_paid,
                    obm.balance_due,
                    obm.payment_status,
                    COALESCE(obm.payment_mode, pr.payment_method, 'Cash') as payment_mode,
                    obm.created_by,
                    obm.purpose,
                    COALESCE(obm.doctor_name, d.full_name, a.doctor_name, 'Direct Service') as doctor_name,
                    COALESCE(d.specialization, dept.department_name, 'General Medicine') as doctor_specialization,
                    COALESCE(dept.department_name, d.specialization, 'General Medicine') as department_name,
                    COALESCE(obi_dept.item_count, 1) as item_count,
                    obm.notes
                $baseFrom
                $whereClause
                GROUP BY obm.bill_id
                $orderClause
                LIMIT $limit OFFSET $offset
            ";

            $rows = $this->db->fetchAll($dataSql, $params) ?: [];

            // Duplicate Detection Algorithm:
            // Identify multiple bills for same patient on same date with exact same grand total
            $duplicateCheckSql = "
                SELECT patient_id, bill_date, grand_total, COUNT(*) as dup_count
                FROM opd_billing_master
                WHERE payment_status != 'Cancelled' AND patient_id IS NOT NULL AND patient_id != ''
                GROUP BY patient_id, bill_date, grand_total
                HAVING dup_count > 1
            ";
            $dupRows = $this->db->fetchAll($duplicateCheckSql) ?: [];
            $dupMap = [];
            foreach ($dupRows as $dr) {
                $dupMap[$dr['patient_id'] . '_' . $dr['bill_date'] . '_' . number_format((float)$dr['grand_total'], 2)] = true;
            }

            foreach ($rows as &$r) {
                $key = ($r['patient_id'] ?? '') . '_' . ($r['bill_date'] ?? '') . '_' . number_format((float)($r['grand_total'] ?? 0), 2);
                $r['is_potential_duplicate'] = isset($dupMap[$key]);
            }

            // Breakdowns for Analytics & Filters
            $deptBreakdown = $this->db->fetchAll("
                SELECT 
                    COALESCE(dept.department_name, d.specialization, 'General Medicine') as department,
                    COUNT(DISTINCT obm.bill_id) as bills_count,
                    SUM(obm.amount_paid) as collected_amount,
                    SUM(obm.grand_total) as total_amount
                $baseFrom
                $whereClause
                GROUP BY department
                ORDER BY collected_amount DESC
            ", $params) ?: [];

            $modeBreakdown = $this->db->fetchAll("
                SELECT 
                    COALESCE(obm.payment_mode, 'Cash') as payment_mode,
                    COUNT(DISTINCT obm.bill_id) as bills_count,
                    SUM(obm.amount_paid) as collected_amount
                $baseFrom
                $whereClause
                GROUP BY payment_mode
                ORDER BY collected_amount DESC
            ", $params) ?: [];

            // 1. Staff Users and Role Lookup
            $allUsers = $this->db->fetchAll("SELECT username, role FROM user") ?: [];
            $userRoleMap = [];
            foreach ($allUsers as $u) {
                $userRoleMap[strtolower(trim($u['username']))] = $u['role'];
            }

            $allStaff = $this->db->fetchAll("SELECT username, designation, full_name, mobile_number FROM staff") ?: [];
            $staffInfoMap = [];
            foreach ($allStaff as $st) {
                if (!empty($st['username'])) {
                    $staffInfoMap[strtolower(trim($st['username']))] = $st;
                }
            }

            // Fetch all doctors from database for exact individual lookup
            $allDoctorsList = $this->db->fetchAll("
                SELECT 
                    d.doctor_id,
                    d.full_name,
                    d.qualification,
                    d.room_number,
                    COALESCE(dept.department_name, d.specialization, 'General Medicine') as department,
                    COALESCE(d.specialization, dept.department_name, 'General Medicine') as specialization
                FROM doctors d
                LEFT JOIN departments dept ON d.department_id = dept.department_id
            ") ?: [];

            $docLookup = [];
            foreach ($allDoctorsList as $docItem) {
                $cKey = strtolower(preg_replace('/[^a-z0-9]/i', '', preg_replace('/^Dr\.?\s+/i', '', trim($docItem['full_name']))));
                $docLookup[$cKey] = $docItem;
                if (!empty($docItem['doctor_id'])) {
                    $docLookup[strtolower(trim($docItem['doctor_id']))] = $docItem;
                }
            }

            // Fetch filtered bills for individual doctor splitting, cashier itemization, and date-wise patient lists
            $filteredBillsForDocs = $this->db->fetchAll("
                SELECT 
                    obm.bill_id,
                    COALESCE(obm.receipt_no, pr.receipt_id, REPLACE(obm.bill_id, 'OPB-', 'ORC-')) as receipt_id,
                    obm.patient_id,
                    COALESCE(NULLIF(TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))), ''), a.patient_name, obm.name, 'Walking Patient') AS patient_name,
                    COALESCE(p.phone, a.phone, obm.mobile, '-') AS patient_phone,
                    obm.bill_date,
                    obm.bill_time,
                    obm.doctor_id,
                    obm.doctor_name,
                    COALESCE(NULLIF(TRIM(obm.created_by), ''), 'System Admin') as created_by,
                    obm.grand_total,
                    obm.amount_paid,
                    obm.balance_due,
                    obm.discount_amount,
                    COALESCE(obm.payment_mode, 'Cash') as payment_mode,
                    COALESCE(obm.payment_status, 'Paid') as payment_status,
                    obm.purpose,
                    COALESCE(obi_dept.item_count, 1) as item_count
                $baseFrom
                $whereClause
                GROUP BY obm.bill_id
                ORDER BY obm.bill_date DESC, obm.bill_time DESC
            ", $params) ?: [];

            // Compute Cashier Shift Handover and Multi-Mode Breakdown from unique bills
            $staffMap = [];
            foreach ($filteredBillsForDocs as $fb) {
                $cUser = $fb['created_by'];
                $paid = (float)$fb['amount_paid'];
                $billed = (float)$fb['grand_total'];
                $due = (float)$fb['balance_due'];
                $disc = (float)$fb['discount_amount'];
                $mode = $fb['payment_mode'];
                $bDate = $fb['bill_date'];
                $bTime = $fb['bill_time'];

                if (!isset($staffMap[$cUser])) {
                    $sKey = strtolower(trim($cUser));
                    $stInfo = $staffInfoMap[$sKey] ?? null;
                    $staffMap[$cUser] = [
                        'staff_username' => $cUser,
                        'full_name' => $stInfo ? $stInfo['full_name'] : $cUser,
                        'role' => $userRoleMap[$sKey] ?? ($stInfo ? $stInfo['designation'] : 'Receptionist'),
                        'phone' => $stInfo ? $stInfo['mobile_number'] : '',
                        'bills_count' => 0,
                        'total_billed' => 0.0,
                        'collected_amount' => 0.0,
                        'pending_amount' => 0.0,
                        'discount_amount' => 0.0,
                        'cash_collected' => 0.0,
                        'upi_collected' => 0.0,
                        'card_collected' => 0.0,
                        'other_collected' => 0.0,
                        'shift_start_date' => $bDate,
                        'shift_end_date' => $bDate,
                        'shift_start_time' => $bTime,
                        'shift_end_time' => $bTime,
                        'bills_list' => []
                    ];
                }

                $staffMap[$cUser]['bills_count']++;
                $staffMap[$cUser]['total_billed'] += $billed;
                $staffMap[$cUser]['collected_amount'] += $paid;
                $staffMap[$cUser]['pending_amount'] += $due;
                $staffMap[$cUser]['discount_amount'] += $disc;

                if ($mode === 'Cash') {
                    $staffMap[$cUser]['cash_collected'] += $paid;
                } else if ($mode === 'UPI') {
                    $staffMap[$cUser]['upi_collected'] += $paid;
                } else if ($mode === 'Card') {
                    $staffMap[$cUser]['card_collected'] += $paid;
                } else {
                    $staffMap[$cUser]['other_collected'] += $paid;
                }

                if ($bDate < $staffMap[$cUser]['shift_start_date'] || ($bDate == $staffMap[$cUser]['shift_start_date'] && $bTime < $staffMap[$cUser]['shift_start_time'])) {
                    $staffMap[$cUser]['shift_start_date'] = $bDate;
                    $staffMap[$cUser]['shift_start_time'] = $bTime;
                }
                if ($bDate > $staffMap[$cUser]['shift_end_date'] || ($bDate == $staffMap[$cUser]['shift_end_date'] && $bTime > $staffMap[$cUser]['shift_end_time'])) {
                    $staffMap[$cUser]['shift_end_date'] = $bDate;
                    $staffMap[$cUser]['shift_end_time'] = $bTime;
                }

                $staffMap[$cUser]['bills_list'][] = [
                    'bill_id' => $fb['bill_id'],
                    'receipt_id' => $fb['receipt_id'],
                    'patient_id' => $fb['patient_id'],
                    'patient_name' => $fb['patient_name'],
                    'patient_phone' => $fb['patient_phone'],
                    'doctor_name' => $fb['doctor_name'],
                    'bill_date' => $fb['bill_date'],
                    'bill_time' => $fb['bill_time'],
                    'grand_total' => $billed,
                    'amount_paid' => $paid,
                    'balance_due' => $due,
                    'discount_amount' => $disc,
                    'payment_mode' => $mode,
                    'payment_status' => $fb['payment_status'],
                    'purpose' => $fb['purpose']
                ];
            }

            $staffBreakdown = array_values($staffMap);
            usort($staffBreakdown, function($a, $b) {
                return $b['collected_amount'] <=> $a['collected_amount'];
            });

            // Fetch line items for the filtered bills
            $billIds = array_column($filteredBillsForDocs, 'bill_id');
            $itemsByBill = [];
            if (!empty($billIds)) {
                $inClause = implode(',', array_fill(0, count($billIds), '?'));
                $rawItems = $this->db->fetchAll("
                    SELECT bill_id, item_name, unit_price, total_price, bill_purpose, item_type
                    FROM opd_billing_items
                    WHERE bill_id IN ($inClause)
                ", $billIds) ?: [];
                foreach ($rawItems as $it) {
                    $itemsByBill[$it['bill_id']][] = $it;
                }
            }

            $docAgg = [];
            $deptHierMap = [];

            foreach ($filteredBillsForDocs as $b) {
                $bId = $b['bill_id'];
                $bDate = $b['bill_date'];
                $rawNames = $b['doctor_name'] ?? '';
                $nameParts = array_filter(array_map('trim', explode(',', $rawNames)));
                if (empty($nameParts)) {
                    $nameParts = ['Direct Hospital Service'];
                }

                $items = $itemsByBill[$bId] ?? [];
                $grandTotal = (float)($b['grand_total'] ?? 0);
                $totalPaidRatio = ($grandTotal > 0) ? ((float)$b['amount_paid'] / $grandTotal) : 1.0;
                $totalDueRatio = ($grandTotal > 0) ? ((float)$b['balance_due'] / $grandTotal) : 0.0;
                $totalDiscRatio = ($grandTotal > 0) ? ((float)$b['discount_amount'] / $grandTotal) : 0.0;

                // Resolve all doctors on this bill
                $resolvedDocs = [];
                foreach ($nameParts as $singleName) {
                    $clean = trim($singleName);
                    $cleanKey = strtolower(preg_replace('/[^a-z0-9]/i', '', preg_replace('/^Dr\.?\s+/i', '', $clean)));
                    
                    $matched = $docLookup[$cleanKey] ?? null;
                    if (!$matched) {
                        foreach ($allDoctorsList as $cand) {
                            $candKey = strtolower(preg_replace('/[^a-z0-9]/i', '', preg_replace('/^Dr\.?\s+/i', '', trim($cand['full_name']))));
                            if (strpos($candKey, $cleanKey) !== false || strpos($cleanKey, $candKey) !== false) {
                                $matched = $cand;
                                break;
                            }
                        }
                    }

                    $dId = $matched ? $matched['doctor_id'] : ('DOC_' . $cleanKey);
                    $dName = $matched ? $matched['full_name'] : $clean;
                    $dDept = $matched ? $matched['department'] : 'General Medicine';
                    $dSpec = $matched ? $matched['specialization'] : 'General Medicine';
                    $dQual = $matched ? $matched['qualification'] : 'Consultant Specialist';
                    $dRoom = $matched ? $matched['room_number'] : '';

                    $resolvedDocs[$dId] = [
                        'doctor_id' => $dId,
                        'doctor_name' => $dName,
                        'clean_key' => $cleanKey,
                        'department' => $dDept,
                        'specialization' => $dSpec,
                        'qualification' => $dQual,
                        'room_number' => $dRoom,
                        'item_total' => 0.0,
                        'item_names' => []
                    ];
                }

                // Allocate line item fees to each respective doctor
                if (count($resolvedDocs) === 1) {
                    $dKey = array_key_first($resolvedDocs);
                    $resolvedDocs[$dKey]['item_total'] = $grandTotal;
                    $resolvedDocs[$dKey]['item_names'][] = $b['purpose'] ?: 'Clinical Consultation';
                } else {
                    $unassignedTotal = 0.0;
                    foreach ($items as $it) {
                        $itName = $it['item_name'] ?? '';
                        $itPrice = (float)($it['total_price'] ?? $it['unit_price'] ?? 0);
                        $itClean = strtolower(preg_replace('/[^a-z0-9]/i', '', preg_replace('/^Dr\.?\s+/i', '', $itName)));
                        $matchedDoc = false;

                        foreach ($resolvedDocs as $dId => &$rDoc) {
                            if (strpos($itClean, $rDoc['clean_key']) !== false || strpos($itName, $rDoc['doctor_name']) !== false) {
                                $rDoc['item_total'] += $itPrice;
                                $rDoc['item_names'][] = $itName;
                                $matchedDoc = true;
                                break;
                            }
                        }
                        unset($rDoc);

                        if (!$matchedDoc) {
                            $unassignedTotal += $itPrice;
                        }
                    }

                    // Share unassigned charges (like Registration fee) equally across the bill's doctors
                    if ($unassignedTotal > 0 && count($resolvedDocs) > 0) {
                        $split = $unassignedTotal / count($resolvedDocs);
                        foreach ($resolvedDocs as $dId => &$rDoc) {
                            $rDoc['item_total'] += $split;
                        }
                        unset($rDoc);
                    }
                }

                // Add each doctor's specific portion to aggregations
                foreach ($resolvedDocs as $dId => $rDoc) {
                    $docBilled = round($rDoc['item_total'], 2);
                    $docCollected = round($docBilled * $totalPaidRatio, 2);
                    $docDue = round($docBilled * $totalDueRatio, 2);
                    $docDisc = round($docBilled * $totalDiscRatio, 2);

                    if (!isset($docAgg[$dId])) {
                        $docAgg[$dId] = [
                            'doctor_id' => $dId,
                            'doctor_name' => $rDoc['doctor_name'],
                            'qualification' => $rDoc['qualification'],
                            'room_number' => $rDoc['room_number'],
                            'department' => $rDoc['department'],
                            'specialization' => $rDoc['specialization'],
                            'bills_count' => 0,
                            'total_billed' => 0.0,
                            'collected_amount' => 0.0,
                            'pending_amount' => 0.0,
                            'discount_amount' => 0.0,
                            'dates' => []
                        ];
                    }

                    $docAgg[$dId]['bills_count']++;
                    $docAgg[$dId]['total_billed'] += $docBilled;
                    $docAgg[$dId]['collected_amount'] += $docCollected;
                    $docAgg[$dId]['pending_amount'] += $docDue;
                    $docAgg[$dId]['discount_amount'] += $docDisc;

                    if (!isset($docAgg[$dId]['dates'][$bDate])) {
                        $docAgg[$dId]['dates'][$bDate] = [
                            'date' => $bDate,
                            'bills_count' => 0,
                            'total_collected' => 0.0,
                            'total_billed' => 0.0,
                            'total_due' => 0.0,
                            'total_discount' => 0.0,
                            'bills' => []
                        ];
                    }

                    $docAgg[$dId]['dates'][$bDate]['bills_count']++;
                    $docAgg[$dId]['dates'][$bDate]['total_collected'] += $docCollected;
                    $docAgg[$dId]['dates'][$bDate]['total_billed'] += $docBilled;
                    $docAgg[$dId]['dates'][$bDate]['total_due'] += $docDue;
                    $docAgg[$dId]['dates'][$bDate]['total_discount'] += $docDisc;
                    $docAgg[$dId]['dates'][$bDate]['bills'][] = [
                        'bill_id' => $bId,
                        'receipt_id' => $b['receipt_id'],
                        'patient_id' => $b['patient_id'],
                        'patient_name' => $b['patient_name'],
                        'patient_phone' => $b['patient_phone'],
                        'bill_date' => $b['bill_date'],
                        'bill_time' => $b['bill_time'],
                        'grand_total' => $docBilled,
                        'amount_paid' => $docCollected,
                        'balance_due' => $docDue,
                        'discount_amount' => $docDisc,
                        'payment_mode' => $b['payment_mode'],
                        'payment_status' => $b['payment_status'],
                        'purpose' => !empty($rDoc['item_names']) ? implode(', ', $rDoc['item_names']) : $b['purpose']
                    ];

                    // Department Hierarchy Structure
                    $dDept = $rDoc['department'];
                    if (!isset($deptHierMap[$dDept])) {
                        $deptHierMap[$dDept] = [
                            'department_name' => $dDept,
                            'total_revenue' => 0.0,
                            'total_billed' => 0.0,
                            'total_due' => 0.0,
                            'total_bills' => 0,
                            'doctors' => []
                        ];
                    }

                    if (!isset($deptHierMap[$dDept]['doctors'][$dId])) {
                        $deptHierMap[$dDept]['doctors'][$dId] = &$docAgg[$dId];
                    }
                }
            }

            // Convert and sort doctor breakdown
            $doctorBreakdown = array_values($docAgg);
            foreach ($doctorBreakdown as &$dItem) {
                $dItem['avg_bill_amount'] = $dItem['bills_count'] > 0 ? round($dItem['total_billed'] / $dItem['bills_count'], 2) : 0;
                $dItem['dates_list'] = array_values($dItem['dates']);
                unset($dItem['bill_ids']);
            }

            usort($doctorBreakdown, function($a, $b) {
                return $b['collected_amount'] <=> $a['collected_amount'];
            });

            // Convert department hierarchy
            $departmentHierarchy = [];
            foreach ($deptHierMap as $deptName => $deptData) {
                $docArray = array_values($deptData['doctors']);
                $depRevenue = array_reduce($docArray, fn($acc, $d) => $acc + (float)$d['collected_amount'], 0);
                $depBilled = array_reduce($docArray, fn($acc, $d) => $acc + (float)$d['total_billed'], 0);
                $depDue = array_reduce($docArray, fn($acc, $d) => $acc + (float)$d['pending_amount'], 0);
                $depBills = array_reduce($docArray, fn($acc, $d) => $acc + (int)$d['bills_count'], 0);

                $departmentHierarchy[] = [
                    'department_name' => $deptName,
                    'total_revenue' => $depRevenue,
                    'total_billed' => $depBilled,
                    'total_due' => $depDue,
                    'total_bills' => $depBills,
                    'doctors_count' => count($docArray),
                    'doctors' => $docArray
                ];
            }

            usort($departmentHierarchy, function($a, $b) {
                return $b['total_revenue'] <=> $a['total_revenue'];
            });

            // Hourly Shift Handover Distribution (00:00 to 23:00)
            $hourlySql = "
                SELECT 
                    HOUR(obm.bill_time) as hour_num,
                    COUNT(DISTINCT obm.bill_id) as bills_count,
                    SUM(obm.amount_paid) as total_collected,
                    SUM(CASE WHEN obm.payment_mode = 'Cash' THEN obm.amount_paid ELSE 0 END) as cash_collected,
                    SUM(CASE WHEN obm.payment_mode = 'UPI' THEN obm.amount_paid ELSE 0 END) as upi_collected,
                    SUM(CASE WHEN obm.payment_mode = 'Card' THEN obm.amount_paid ELSE 0 END) as card_collected,
                    SUM(CASE WHEN obm.payment_mode NOT IN ('Cash', 'UPI', 'Card') THEN obm.amount_paid ELSE 0 END) as other_collected
                $baseFrom
                $whereClause
                GROUP BY hour_num
                ORDER BY hour_num ASC
            ";
            $hourlyRaw = $this->db->fetchAll($hourlySql, $params) ?: [];
            $hourlyMap = [];
            foreach ($hourlyRaw as $hr) {
                $hourlyMap[(int)$hr['hour_num']] = $hr;
            }

            $hourlyShift = [];
            for ($h = 7; $h <= 22; $h++) { // Operating clinical hours 07:00 to 22:00
                $label = sprintf("%02d:00 - %02d:00", $h, $h + 1);
                $entry = $hourlyMap[$h] ?? null;
                $hourlyShift[] = [
                    'hour' => $h,
                    'time_label' => $label,
                    'bills_count' => $entry ? (int)$entry['bills_count'] : 0,
                    'total_collected' => $entry ? (float)$entry['total_collected'] : 0.0,
                    'cash_collected' => $entry ? (float)$entry['cash_collected'] : 0.0,
                    'upi_collected' => $entry ? (float)$entry['upi_collected'] : 0.0,
                    'card_collected' => $entry ? (float)$entry['card_collected'] : 0.0,
                    'other_collected' => $entry ? (float)$entry['other_collected'] : 0.0,
                ];
            }

            $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

            return [
                'records' => $rows,
                'pagination' => [
                    'total_records' => $totalRecords,
                    'total_pages' => $totalPages,
                    'current_page' => $page,
                    'limit' => $limit
                ],
                'summary_kpis' => $summaryKPIs,
                'hourly_shift' => $hourlyShift,
                'breakdowns' => [
                    'department' => $deptBreakdown,
                    'department_hierarchy' => $departmentHierarchy,
                    'payment_mode' => $modeBreakdown,
                    'staff' => $staffBreakdown,
                    'doctor' => $doctorBreakdown
                ]
            ];

        } catch (\Exception $e) {
            error_log("Error in getAdvancedReceipts: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel or Refund Bill with audit trail
     */
    public function cancelOrRefundBill($billId, $action, $reason, $userId, $refundAmount = 0)
    {
        try {
            $this->db->beginTransaction();

            $bill = $this->db->fetchOne("SELECT * FROM opd_billing_master WHERE bill_id = ?", [$billId]);
            if (!$bill) {
                throw new Exception("Bill not found: {$billId}");
            }

            $status = ($action === 'refund') ? 'Refunded' : 'Cancelled';
            $auditNote = "Status changed to {$status} by {$userId}. Reason: {$reason}";
            if ($refundAmount > 0) {
                $auditNote .= ". Refunded Amount: ₹{$refundAmount}";
            }

            // Update master
            $this->db->execute(
                "UPDATE opd_billing_master SET payment_status = ?, notes = CONCAT(IFNULL(notes, ''), '\n[', CURRENT_TIMESTAMP, '] ', ?) WHERE bill_id = ?",
                [$status, $auditNote, $billId]
            );

            // Log billing action
            $this->logBillingAction($billId, $status, $auditNote);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}

