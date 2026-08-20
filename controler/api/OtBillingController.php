<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class OtBillingController extends BaseController
{
    /**
     * Create a new OT Billing Record
     * POST /api/ot-billing
     */
    public function create()
    {
        $this->restrictMethod('POST');
        $user = $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        // Basic validation
        if (empty($data['patient']['patient_id'])) {
            $this->respondBadRequest("Patient ID is required");
        }
        
        try {
            // Generate unique OT Bill No
            $datePrefix = date('Ymd');
            $countSql = "SELECT COUNT(*) as count FROM ot_billing_records WHERE ot_bill_no LIKE ?";
            $result = $this->db->fetchOne($countSql, ["OTB-{$datePrefix}-%"]);
            $nextNum = ($result['count'] ?? 0) + 1;
            $otBillNo = sprintf("OTB-%s-%03d", $datePrefix, $nextNum);

            // Prepare JSON structures
            $doctorChargesJson = json_encode($data['doctor_charges'] ?? []);
            $additionalChargesJson = json_encode($data['additional_charges'] ?? []);
            
            $sql = "INSERT INTO ot_billing_records (
                        ot_bill_no,
                        patient_id,
                        admission_id,
                        patient_name,
                        name,
                        department,
                        theatre,
                        anesthesia_type,
                        surgery_date,
                        doctor_charges_json,
                        additional_charges_json,
                        total_doctor_amount,
                        total_additional_amount,
                        grand_total,
                        status,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'COMPLETED', ?)";
                    
            $params = [
                $otBillNo,
                $data['patient']['patient_id'],
                $data['patient']['admission_id'],
                $data['patient']['patient_name'],
                $data['surgery']['name'] ?? '',
                $data['surgery']['department'] ?? '',
                $data['surgery']['theatre'] ?? '',
                $data['surgery']['anesthesia_type'] ?? '',
                $data['surgery']['surgery_date'] ?? date('Y-m-d'),
                $doctorChargesJson,
                $additionalChargesJson,
                $data['billing']['total_charges'] ?? 0, // Using total_charges for doc amount
                $data['billing']['total_charges'] ?? 0,
                $data['billing']['grand_total'] ?? 0,
                $user['username'] ?? 'admin'
            ];
            
            $result = $this->db->execute($sql, $params);
            $newId = $result['insert_id'] ?? 0;
            
            // Also update the ipd_billing_master ot_charges column
            $grandTotal = $data['billing']['grand_total'] ?? 0;
            $admissionId = $data['patient']['admission_id'];
            if (!empty($admissionId) && $grandTotal > 0) {
                // 1. Fetch IPD Bill ID
                $masterRow = $this->db->fetchOne("SELECT bill_id FROM ipd_billing_master WHERE admission_id = ?", [$admissionId]);
                $ipdBillId = $masterRow['bill_id'] ?? null;
                
                if ($ipdBillId) {
                    $amountPaid = $data['billing']['amount_paid'] ?? 0;
                    $balanceDue = max(0, $grandTotal - $amountPaid);
                    
                    $description = 'OT Bill Charge';
                    if ($amountPaid > 0) {
                        $description .= " (Paid: ₹{$amountPaid}, Due: ₹{$balanceDue})";
                    } elseif ($grandTotal > 0) {
                        $description .= " (Unpaid)";
                    }

                    // 2. Insert into ipd_billing_items (this makes it appear in the IPD bill calculation)
                    $itemSql = "INSERT INTO ipd_billing_items (
                                    bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by
                                ) VALUES (?, ?, ?, NOW(), 'OT', ?, ?, 'COMPLETED', ?)";
                    $this->db->execute($itemSql, [
                        $ipdBillId,
                        $data['patient']['patient_id'],
                        $admissionId,
                        $description,
                        $grandTotal,
                        $user['username'] ?? 'admin'
                    ]);
                    
                    // 3. Insert into ipd_payment ONLY if the patient actually paid something
                    $amountPaid = $data['billing']['amount_paid'] ?? 0;
                    if ($amountPaid > 0) {
                        $paymentSql = "INSERT INTO ipd_payment (
                                        bill_id,
                                        admission_id,
                                        patient_id,
                                        payment_date,
                                        payment_type,
                                        payment_mode,
                                        amount,
                                        remarks,
                                        created_by,
                                        is_insurance,
                                        verified_status
                                    ) VALUES (?, ?, ?, NOW(), 'PARTIAL', 'CASH', ?, 'OT Bill Payment', ?, 0, 'VERIFIED')";
                        $this->db->execute($paymentSql, [
                            $ipdBillId,
                            $admissionId,
                            $data['patient']['patient_id'],
                            $amountPaid,
                            $user['username'] ?? 'admin'
                        ]);
                    }

                    // 4. Recalculate Master (this automatically updates ot_charges, amount_paid, balance_due and payment_status in ipd_billing_master)
                    require_once __DIR__ . '/../../models/IpdBillingMaster.php';
                    (new \GM_HMS\Models\IpdBillingMaster())->recalculateMaster($ipdBillId, $user['username'] ?? 'admin');
                }
            }
            
            $this->respondCreated([
                'id' => $newId,
                'ot_bill_no' => $otBillNo,
                'message' => 'OT Bill saved successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("OT Billing Error: " . $e->getMessage());
            $this->respondServerError("Failed to save OT Bill: " . $e->getMessage());
        }
    }
}
