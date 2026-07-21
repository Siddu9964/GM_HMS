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

            // ── Duplicate check: same patient, same date, same item names ──
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
                    if (in_array($newName, $existingItemNames)) {
                        $existingBillId = $existingBills[0]['bill_id'];
                        throw new Exception("Duplicate entry: a bill ({$existingBillId}) for this patient already exists today with the same item(s). Please check Recent Bills.");
                    }
                }
            }

            $billId = $this->generateBillId();

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
                $billData['doctor_id']      ?? '',
                $billData['doctor_name']    ?? 'Walking',
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

            if (!empty($items)) {
                foreach ($items as $item) {
                    $this->addBillingItem($billId, $item, $receiptNo);
                }
            }

            $this->calculateTotals($billId);

            $this->logBillingAction($billId, 'Created', 'OPD bill created');

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
        $valid = ['Consultation', 'Investigation', 'Procedure', 'Radiology', 'Scan', 'X-Ray', 'Blood Test', 'Medicine', 'Other'];
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

        $sql .= " ORDER BY obm.bill_date DESC, obm.bill_id DESC";
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

    public function getConsultationFeeByPatient($patientId)
    {
        $sql = "SELECT d.consultation_fee 
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.doctor_id
                WHERE a.patient_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 1";
        $result = $this->db->fetchOne($sql, [$patientId]);
        
        return $result ? (float)$result['consultation_fee'] : 0.00;
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
                SELECT NULL         AS service_id,
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
}
