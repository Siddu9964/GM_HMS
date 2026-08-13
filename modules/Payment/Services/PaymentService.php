<?php
namespace GM_HMS\Modules\Payment\Services;

use GM_HMS\Modules\Payment\Repositories\PaymentRepository;

class PaymentService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new PaymentRepository();
    }

    public function syncClinicalBillingForDate(string $admissionId, string $recordDate, string $updatedBy = 'system'): void 
    {
        // 1. Get room type and bill ID
        $master = $this->repo->getMasterBillInfo($admissionId);
        if (!$master) return; // Master record doesn't exist yet

        $billId = $master['bill_id'];
        $roomType = $master['room_type'] ?? 'General Ward';

        // 2. Fetch clinical records for the day
        $clinical = $this->repo->getClinicalRecordsForDate($admissionId, $recordDate);
        if (!$clinical) return;
        
        $patientId = $clinical['patient_id'];

        $this->syncCategory($billId, $admissionId, $patientId, $recordDate, 'DOCTOR_VISIT', $clinical['consultant_visits'], $roomType, $updatedBy);
        $this->syncCategory($billId, $admissionId, $patientId, $recordDate, 'LAB', $clinical['lab_tests'], $roomType, $updatedBy);
        $this->syncCategory($billId, $admissionId, $patientId, $recordDate, 'RADIOLOGY', $clinical['radiology_tests'], $roomType, $updatedBy);
        $this->syncCategory($billId, $admissionId, $patientId, $recordDate, 'MISC', $clinical['other_tests'], $roomType, $updatedBy);

        // Finally, recalculate master
        $this->repo->recalculateMasterTotals($billId, $updatedBy);
    }

    private function syncCategory(string $billId, string $admissionId, string $patientId, string $recordDate, string $chargeType, ?string $jsonData, string $roomType, string $updatedBy) 
    {
        $items = json_decode($jsonData ?: '[]', true);
        if (!is_array($items) || empty($items)) {
            $this->repo->cancelSystemBillingItems($billId, $recordDate, $chargeType);
            return;
        }

        $totalAmount = 0;
        $itemsList = [];

        foreach ($items as $entry) {
            $data = $entry['data'] ?? $entry; // Handle new NurseClinicalModel vs flat json
            if (empty($data)) continue;
            
            if ($chargeType === 'DOCTOR_VISIT') {
                $testId = $data['entry_id'] ?? uniqid('doc_');
                $testName = "Dr. " . ($data['consultant'] ?? 'Unknown') . " (" . ($data['shift'] ?? 'Visit') . ")";
                $qty = 1;
                $price = 0; // Manual entry required
            } else {
                $testId = $data['id'] ?? $data['test_id'] ?? $data['service_id'] ?? $data['product_id'] ?? '';
                $testName = $data['name'] ?? $data['test_name'] ?? $data['product_name'] ?? '';
                $qty = (int)($data['qty'] ?? $data['quantity'] ?? 1);
                if ($qty < 1) $qty = 1;

                if (empty($testId)) continue; 

                $price = 0;
                if ($chargeType === 'LAB') {
                    $price = $this->repo->getLabServicePrice($testId, $roomType);
                } elseif ($chargeType === 'RADIOLOGY') {
                    $price = $this->repo->getRadiologyServicePrice($testId, $roomType);
                } elseif ($chargeType === 'MISC') {
                    $price = $this->repo->getOtherServicePrice($testId, $roomType);
                } elseif ($chargeType === 'PHARMACY') {
                    $price = $this->repo->getPharmacyPrice($testId);
                }
            }

            $amount = $price * $qty;
            $totalAmount += $amount;

            $itemsList[] = [
                'test_id' => $testId,
                'test_name' => $testName,
                'qty' => $qty,
                'unit_price' => $price,
                'amount' => $amount
            ];
        }

        if (empty($itemsList)) return;

        $itemsJson = json_encode($itemsList);

        $existingItem = $this->repo->getExistingSystemItem($billId, $recordDate, $chargeType);

        $testNames = array_column($itemsList, 'test_name');
        $descStr = implode(', ', $testNames);
        if (strlen($descStr) > 200) {
            $descStr = substr($descStr, 0, 197) . '...';
        }

        $descMap = [
            'DOCTOR_VISIT' => 'Consultant Visits',
            'LAB' => 'Laboratory Charges',
            'RADIOLOGY' => 'Radiology Charges',
            'MISC' => 'Other Clinical Services',
            'PHARMACY' => 'Pharmacy Charges'
        ];
        
        $fullDescription = $descMap[$chargeType] . ' - ' . date('d-M-Y', strtotime($recordDate)) . " ($descStr)";

        if ($existingItem) {
            $this->repo->updateBillingItem($existingItem['item_id'], $totalAmount, $itemsJson, $fullDescription);
        } else {
            $this->repo->insertBillingItem([
                'bill_id'         => $billId,
                'patient_id'      => $patientId,
                'admission_id'    => $admissionId,
                'charge_date'     => $recordDate,
                'charge_type'     => $chargeType,
                'department'      => 'CLINICAL',
                'description'     => $fullDescription,
                'reference_table' => 'ipd_clinical_records',
                'total_amount'    => $totalAmount,
                'items_json'      => $itemsJson,
                'status'          => 'COMPLETED',
                'created_by'      => $updatedBy,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
