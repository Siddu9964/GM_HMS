<?php
namespace GM_HMS\Modules\Payment\Repositories;

use GM_HMS\Database\SecureDatabase;

class PaymentRepository
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    public function getMasterBillInfo(string $admissionId)
    {
        return $this->db->fetchOne(
            "SELECT bm.bill_id, hb.room_type 
             FROM ipd_billing_master bm
             JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE bm.admission_id = ?",
            [$admissionId]
        );
    }

    public function getClinicalRecordsForDate(string $admissionId, string $recordDate)
    {
        return $this->db->fetchOne(
            "SELECT consultant_visits, lab_tests, radiology_tests, other_tests, pharmacy_orders, patient_id 
             FROM ipd_clinical_records 
             WHERE admission_id = ? AND record_date = ?",
            [$admissionId, $recordDate]
        );
    }

    public function getLabServicePrice(string $testId, string $roomType)
    {
        $service = $this->db->fetchOne(
            "SELECT `General Ward`, `Semi Private Room`, `Private Room` FROM lab_services WHERE service_id = ?", 
            [$testId]
        );
        if ($service) {
            return (float)($service[$roomType] ?? $service['General Ward'] ?? 0);
        }
        return 0.0;
    }

    public function getRadiologyServicePrice(string $testId, string $roomType)
    {
        $service = $this->db->fetchOne(
            "SELECT `General Ward`, `Semi Private Room`, `Private Room` FROM radiology_services WHERE service_id = ?", 
            [$testId]
        );
        if ($service) {
            return (float)($service[$roomType] ?? $service['General Ward'] ?? 0);
        }
        return 0.0;
    }

    public function getOtherServicePrice(string $testId, string $roomType)
    {
        $service = $this->db->fetchOne(
            "SELECT `op_gw_price`, `Semi Private Room`, `Private Room` FROM other_services WHERE service_id = ?", 
            [$testId]
        );
        if ($service) {
            if ($roomType === 'General Ward' || empty($service[$roomType])) {
                return (float)($service['op_gw_price'] ?? 0);
            } else {
                return (float)($service[$roomType]);
            }
        }
        return 0.0;
    }

    public function getPharmacyPrice(string $productId)
    {
        $product = $this->db->fetchOne(
            "SELECT `mrp` FROM ph_product WHERE product_id = ?", 
            [$productId]
        );
        if ($product) {
            return (float)($product['mrp'] ?? 0);
        }
        return 0.0;
    }

    public function cancelSystemBillingItems(string $billId, string $recordDate, string $chargeType)
    {
        $this->db->execute(
            "UPDATE ipd_billing_items SET status = 'CANCELLED', updated_at = NOW() 
             WHERE bill_id = ? AND charge_date = ? AND charge_type = ? AND reference_table = 'ipd_clinical_records'",
            [$billId, $recordDate, $chargeType]
        );
    }

    public function getExistingSystemItem(string $billId, string $recordDate, string $chargeType)
    {
        return $this->db->fetchOne(
            "SELECT item_id FROM ipd_billing_items 
             WHERE bill_id = ? AND charge_date = ? AND charge_type = ? AND reference_table = 'ipd_clinical_records' AND status != 'CANCELLED'",
            [$billId, $recordDate, $chargeType]
        );
    }

    public function updateBillingItem(string $itemId, float $totalAmount, string $itemsJson, string $description = '')
    {
        if ($description) {
            $this->db->execute(
                "UPDATE ipd_billing_items 
                 SET total_amount = ?, items_json = ?, description = ?, updated_at = NOW()
                 WHERE item_id = ?",
                [$totalAmount, $itemsJson, $description, $itemId]
            );
        } else {
            $this->db->execute(
                "UPDATE ipd_billing_items 
                 SET total_amount = ?, items_json = ?, updated_at = NOW()
                 WHERE item_id = ?",
                [$totalAmount, $itemsJson, $itemId]
            );
        }
    }

    public function insertBillingItem(array $data)
    {
        return $this->db->insert('ipd_billing_items', $data);
    }

    public function recalculateMasterTotals(string $billId, string $updatedBy)
    {
        // Calculate totals directly using DB query
        $totals = $this->db->fetchOne(
            "SELECT 
                SUM(CASE WHEN charge_type = 'LAB' THEN total_amount ELSE 0 END) as lab_total,
                SUM(CASE WHEN charge_type = 'RADIOLOGY' THEN total_amount ELSE 0 END) as radio_total,
                SUM(CASE WHEN charge_type = 'PHARMACY' THEN total_amount ELSE 0 END) as pharma_total,
                SUM(CASE WHEN charge_type IN ('MISC', 'OTHER') THEN total_amount ELSE 0 END) as other_total,
                SUM(CASE WHEN charge_type = 'ROOM_RENT' THEN total_amount ELSE 0 END) as room_total,
                SUM(CASE WHEN charge_type = 'DOCTOR_VISIT' THEN total_amount ELSE 0 END) as consult_total
             FROM ipd_billing_items
             WHERE bill_id = ? AND status != 'CANCELLED'",
            [$billId]
        );

        $lab = (float)($totals['lab_total'] ?? 0);
        $radio = (float)($totals['radio_total'] ?? 0);
        $pharma = (float)($totals['pharma_total'] ?? 0);
        $other = (float)($totals['other_total'] ?? 0);
        $room = (float)($totals['room_total'] ?? 0);
        $consult = (float)($totals['consult_total'] ?? 0);
        
        // Fetch existing discount from master as we don't store it in items
        $master = $this->db->fetchOne("SELECT discount_amount FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
        $discount = (float)($master['discount_amount'] ?? 0);

        $gross = $lab + $radio + $pharma + $other + $room + $consult;
        $net = $gross - $discount;

        $this->db->execute(
            "UPDATE ipd_billing_master 
             SET lab_charges = ?, 
                 radiology_charges = ?, 
                 pharmacy_charges = ?, 
                 other_charges = ?,
                 room_charges = ?,
                 doctor_charges = ?,
                 subtotal = ?, 
                 grand_total = ?, 
                 updated_by = ?, 
                 updated_at = NOW()
             WHERE bill_id = ?",
            [$lab, $radio, $pharma, $other, $room, $consult, $gross, $net, $updatedBy, $billId]
        );
    }
}
