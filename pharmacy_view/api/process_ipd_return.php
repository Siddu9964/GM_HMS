<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
require_once __DIR__ . '/../../models/IpdBillingMaster.php';
use GM_HMS\Database\SecureDatabase;
use GM_HMS\Models\IpdBillingMaster;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pharmacistId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$requestId = $input['request_id'] ?? '';
$action = $input['action'] ?? ''; // 'ACCEPT' or 'REJECT'

if (empty($requestId) || !in_array($action, ['ACCEPT', 'REJECT'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    $db->beginTransaction();
    
    // Fetch request
    $req = $db->fetchOne("SELECT * FROM ipd_pharmacy_return_requests WHERE request_id = ? AND status = 'PENDING' FOR UPDATE", [$requestId]);
    
    if (!$req) {
        throw new Exception("Return request not found or already processed.");
    }
    
    $newStatus = ($action === 'ACCEPT') ? 'ACCEPTED' : 'REJECTED';
    $now = date('Y-m-d H:i:s');
    
    // Update request status
    $db->execute("UPDATE ipd_pharmacy_return_requests SET status = ?, processed_by = ?, processed_at = ? WHERE request_id = ?", [
        $newStatus, $pharmacistId, $now, $requestId
    ]);
    
    if ($action === 'ACCEPT') {
        $itemId = $req['item_id'];
        
        // 1. Mark as returned in ipd_clinical_records (so the Nurse UI reflects it)
        $parts = explode('_', $itemId);
        if (count($parts) === 2) {
            $recordId = $parts[0];
            $idx = $parts[1];
            $record = $db->fetchOne("SELECT pharmacy_orders FROM ipd_clinical_records WHERE id = ? FOR UPDATE", [$recordId]);
            if ($record && $record['pharmacy_orders']) {
                $orders = json_decode($record['pharmacy_orders'], true);
                if (isset($orders[$idx]['data'])) {
                    $currentReturned = (float)($orders[$idx]['data']['returned_qty'] ?? 0);
                    $orders[$idx]['data']['returned_qty'] = $currentReturned + (float)$req['return_qty'];
                    
                    $currentReturnedAmt = (float)($orders[$idx]['data']['returned_amount'] ?? 0);
                    $orders[$idx]['data']['returned_amount'] = $currentReturnedAmt + (float)$req['return_amount'];
                    
                    $db->execute("UPDATE ipd_clinical_records SET pharmacy_orders = ? WHERE id = ?", [json_encode($orders), $recordId]);
                }
            }
        }

        // 2. Find corresponding item in ipd_billing_items to reduce the bill
        // If the nurse returned using a virtual ID, we don't have the direct item_id of the billing record
        $matchedBillingItemId = null;
        $matchedBillingItemData = null;
        $matchedItemIndex = -1;

        if (count($parts) === 2) {
            $sqlBilling = "SELECT item_id, items_json, total_amount, bill_id FROM ipd_billing_items WHERE admission_id = ? AND charge_type = 'PHARMACY' AND status != 'CANCELLED'";
            $billingItems = $db->fetchAll($sqlBilling, [$req['admission_id']]);
            foreach ($billingItems as $bItem) {
                $bitemsArr = json_decode($bItem['items_json'], true);
                if (is_array($bitemsArr)) {
                    foreach ($bitemsArr as $bidx => $singleItem) {
                        $pName = $singleItem['test_name'] ?? $singleItem['product_name'] ?? $singleItem['name'] ?? '';
                        if ($pName === $req['medicine_name']) {
                            $matchedBillingItemId = $bItem['item_id'];
                            $matchedBillingItemData = $bItem;
                            $matchedItemIndex = $bidx;
                            break 2;
                        }
                    }
                }
            }
        } else {
            // It was a real item_id fallback
            $matchedBillingItemData = $db->fetchOne("SELECT items_json, total_amount, bill_id FROM ipd_billing_items WHERE item_id = ? FOR UPDATE", [$itemId]);
            $matchedBillingItemId = $itemId;
            // Assuming fallback was a single item array or object, just find the index
            if ($matchedBillingItemData) {
                $bitemsArr = json_decode($matchedBillingItemData['items_json'], true);
                if (is_array($bitemsArr)) {
                    // if it's sequential array, find it
                    if (isset($bitemsArr[0])) {
                        foreach ($bitemsArr as $bidx => $singleItem) {
                            $pName = $singleItem['test_name'] ?? $singleItem['product_name'] ?? $singleItem['name'] ?? '';
                            if ($pName === $req['medicine_name']) {
                                $matchedItemIndex = $bidx;
                                break;
                            }
                        }
                    } else {
                        // it might be an object, wrap in array
                        $bitemsArr = [$bitemsArr];
                        $matchedItemIndex = 0;
                    }
                }
            }
        }
        
        if ($matchedBillingItemId && $matchedBillingItemData && $matchedItemIndex >= 0) {
            $metaArray = json_decode($matchedBillingItemData['items_json'], true) ?: [];
            
            // In case it wasn't a sequential array initially, ensure we can access by index
            if (!isset($metaArray[0]) && count($metaArray) > 0) {
                $metaArray = [$metaArray];
            }
            
            $targetItem = &$metaArray[$matchedItemIndex];
            
            $currentReturnedQty = (float)($targetItem['returned_qty'] ?? 0);
            $currentReturnedAmt = (float)($targetItem['returned_amount'] ?? 0);
            
            $targetItem['returned_qty'] = $currentReturnedQty + (float)$req['return_qty'];
            $targetItem['returned_amount'] = $currentReturnedAmt + (float)$req['return_amount'];
            
            // Audit log in JSON
            if (!isset($targetItem['return_history'])) {
                $targetItem['return_history'] = [];
            }
            $targetItem['return_history'][] = [
                'date' => $now,
                'qty' => $req['return_qty'],
                'amount' => $req['return_amount'],
                'processed_by' => $pharmacistId
            ];
            
            // Update billing item
            $newTotalAmt = (float)$matchedBillingItemData['total_amount'] - (float)$req['return_amount'];
            if ($newTotalAmt < 0) $newTotalAmt = 0;
            
            $db->execute("UPDATE ipd_billing_items SET total_amount = ?, items_json = ?, updated_at = ? WHERE item_id = ?", [
                $newTotalAmt, json_encode($metaArray), $now, $matchedBillingItemId
            ]);
            
            // Recalculate Master Bill
            $billingMaster = new IpdBillingMaster();
            $billingMaster->recalculateMaster($matchedBillingItemData['bill_id'], $pharmacistId);
            
            // Increase stock back
            $productId = $targetItem['test_id'] ?? $targetItem['product_id'] ?? $targetItem['id'] ?? '';
            $batchNo = $req['batch_no'] ?? '';
            
            if ($productId) {
                if (!empty($batchNo)) {
                    $db->execute("UPDATE ph_product SET quantity = quantity + ? WHERE product_id = ? AND batch_number = ?", [$req['return_qty'], $productId, $batchNo]);
                } else {
                    // Fallback if no batch number is provided
                    $db->execute("UPDATE ph_product SET quantity = quantity + ? WHERE product_id = ?", [$req['return_qty'], $productId]);
                }
            }
        }
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => "Request {$newStatus} successfully."]);
} catch (\Throwable $e) {
    if (isset($db)) $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
