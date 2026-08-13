<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;
use GM_HMS\Models\NurseClinicalModel;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$nurseId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['returns'])) {
    echo json_encode(['success' => false, 'message' => 'No return data provided']);
    exit;
}

$patientId = $input['patient_id'] ?? '';
$admissionId = $input['admission_id'] ?? '';
$billId = $input['bill_id'] ?? '';
$returns = $input['returns'];

if (empty($patientId) || empty($admissionId) || empty($billId)) {
    echo json_encode(['success' => false, 'message' => 'Missing patient or admission context']);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    $db->beginTransaction();
    
    foreach ($returns as $ret) {
        $itemId = $ret['item_id'];
        $returnQty = (float)$ret['return_qty'];
        
        if ($returnQty <= 0) continue;
        
        $parts = explode('_', $itemId);
        if (count($parts) !== 2) {
            // Fallback for real item_id if passed
            $item = $db->fetchOne("SELECT items_json FROM ipd_billing_items WHERE item_id = ? AND admission_id = ?", [$itemId, $admissionId]);
            if (!$item) throw new Exception("Item ID {$itemId} not found.");
            $meta = json_decode($item['items_json'], true) ?: [];
            $originalQty = (float)($meta['quantity'] ?? 1);
            $alreadyReturned = (float)($meta['returned_qty'] ?? 0);
            $unitPrice = (float)($meta['unit_price'] ?? 0);
            $medicineName = $meta['product_name'] ?? 'Unknown';
            $batchNo = $meta['batch'] ?? $meta['batch_no'] ?? '';
        } else {
            // Virtual item ID from clinical records
            $recordId = $parts[0];
            $idx = $parts[1];
            
            $record = $db->fetchOne("SELECT pharmacy_orders FROM ipd_clinical_records WHERE id = ? AND admission_id = ?", [$recordId, $admissionId]);
            if (!$record) throw new Exception("Record not found for {$itemId}");
            
            $orders = json_decode($record['pharmacy_orders'], true);
            $orderBlock = $orders[$idx] ?? null;
            if (!$orderBlock) throw new Exception("Order not found at index {$idx}");
            
            $meta = $orderBlock['data'] ?? [];
            $productId = $meta['product_id'] ?? $meta['id'] ?? '';
            
            // Fetch actual unit_price from ipd_billing_items
            $actualRate = 0;
            $billingSql = "SELECT items_json FROM ipd_billing_items WHERE admission_id = ? AND charge_type = 'PHARMACY' AND status != 'CANCELLED'";
            $billRows = $db->fetchAll($billingSql, [$admissionId]);
            foreach ($billRows as $brow) {
                if (!empty($brow['items_json'])) {
                    $bitems = json_decode($brow['items_json'], true);
                    if (is_array($bitems)) {
                        foreach ($bitems as $bitem) {
                            if (($bitem['test_id'] ?? '') === $productId && (float)($bitem['unit_price'] ?? 0) > 0) {
                                $actualRate = (float)$bitem['unit_price'];
                                break 2;
                            }
                        }
                    }
                }
            }
            
            $originalQty = (float)($meta['qty'] ?? $meta['quantity'] ?? 1);
            $alreadyReturned = (float)($meta['returned_qty'] ?? 0);
            $unitPrice = $actualRate > 0 ? $actualRate : (float)($meta['unit_price'] ?? $meta['rate'] ?? $meta['mrp'] ?? 0);
            $medicineName = $meta['product_name'] ?? $meta['name'] ?? 'Unknown';
            $batchNo = $meta['batch'] ?? $meta['batch_no'] ?? '';
        }
        
        $availableQty = $originalQty - $alreadyReturned;
        
        if ($returnQty > $availableQty) {
            throw new Exception("Return quantity ({$returnQty}) exceeds available quantity ({$availableQty}) for {$medicineName}.");
        }
        
        $returnAmount = $returnQty * $unitPrice;
        
        $sql = "INSERT INTO ipd_pharmacy_return_requests (
            patient_id, admission_id, bill_id, item_id, medicine_name, batch_no, 
            original_qty, return_qty, return_amount, status, requested_by, requested_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, ?)";
        
        $db->execute($sql, [
            $patientId, $admissionId, $billId, $itemId, $medicineName, $batchNo,
            $originalQty, $returnQty, $returnAmount, $nurseId, date('Y-m-d H:i:s')
        ]);
        
        $clinicalModel = new NurseClinicalModel();
        $clinicalModel->appendToDailyRecord($patientId, $admissionId, 'pharmacy_returns', [
            'item_id' => $itemId,
            'medicine_name' => $medicineName,
            'batch_no' => $batchNo,
            'original_qty' => $originalQty,
            'return_qty' => $returnQty,
            'return_amount' => $returnAmount,
            'status' => 'PENDING',
            'requested_at' => date('Y-m-d H:i:s')
        ], $nurseId);
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Return requests submitted for pharmacy verification.']);
} catch (\Throwable $e) {
    if (isset($db)) $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
