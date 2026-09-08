<?php
namespace GM_HMS\Modules\Pharmacy\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Payment\Repositories\PaymentRepository;

class IPOrdersController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        // Typically requires Auth check, assuming BaseController handles it or session is active.
    }

    public function index() {
        try {
            $db = $this->db;
            
            // Fetch IP orders that are not null and not empty
            $sql = "
                SELECT 
                    c.id,
                    c.patient_id,
                    c.admission_id,
                    c.record_date,
                    c.pharmacy_orders,
                    c.pharmacy_returns,
                    p.first_name,
                    p.last_name,
                    a.ward_name,
                    a.room_no,
                    a.bed_id
                FROM ipd_clinical_records c
                JOIN patient p ON c.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci
                JOIN ipd_admissions a ON c.admission_id COLLATE utf8mb4_unicode_ci = a.admission_id COLLATE utf8mb4_unicode_ci
                WHERE (c.pharmacy_orders IS NOT NULL AND c.pharmacy_orders != '' AND c.pharmacy_orders != '[]')
                   OR (c.pharmacy_returns IS NOT NULL AND c.pharmacy_returns != '' AND c.pharmacy_returns != '[]')
                ORDER BY c.updated_at DESC
            ";
            
            $records = $db->fetchAll($sql);
            
            
            $formattedOrders = [];
            $productIds = [];
            
            // First pass to collect all product IDs
            foreach ($records as $row) {
                $orders = json_decode($row['pharmacy_orders'] ?? '[]', true) ?? [];
                $returns = json_decode($row['pharmacy_returns'] ?? '[]', true) ?? [];
                foreach ($orders as $item) {
                    if (!empty($item['data']['id'])) {
                        $productIds[] = $item['data']['id'];
                    }
                }
                foreach ($returns as $item) {
                    if (!empty($item['data']['id'])) {
                        $productIds[] = $item['data']['id'];
                    }
                }
            }
            
            $productMap = [];
            if (!empty($productIds)) {
                $productIds = array_unique($productIds);
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $prodSql = "SELECT product_id, product_name, mrp, sales_price, GST_price, tax_percent, hsn_code, expiry_date, quantity, is_active FROM ph_product WHERE product_id IN ($placeholders)";
                $prodRecords = $db->fetchAll($prodSql, $productIds);
                foreach ($prodRecords as $pr) {
                    $productMap[$pr['product_id']] = [
                        'product_name' => $pr['product_name'],
                        'mrp' => $pr['mrp'],
                        'sales_price' => $pr['sales_price'],
                        'GST_price' => $pr['GST_price'],
                        'tax_percent' => $pr['tax_percent'],
                        'hsn_code' => $pr['hsn_code'],
                        'expiry_date' => $pr['expiry_date'],
                        'available_stock' => (int)($pr['quantity'] ?? 0),
                        'is_active' => (int)($pr['is_active'] ?? 1)
                    ];
                }
            }
            
            $userIds = [];
            foreach ($records as $row) {
                $orders = json_decode($row['pharmacy_orders'] ?? '[]', true) ?? [];
                foreach ($orders as $item) {
                    if (!empty($item['created_by'])) $userIds[] = $item['created_by'];
                }
            }
            $userMap = [];
            if (!empty($userIds)) {
                $userIds = array_unique($userIds);
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $userSql = "SELECT sl_no, username FROM user WHERE sl_no IN ($placeholders)";
                $userRecords = $db->fetchAll($userSql, $userIds);
                foreach ($userRecords as $u) {
                    $userMap[$u['sl_no']] = $u['username'];
                }
            }

            foreach ($records as $row) {
                $orders = json_decode($row['pharmacy_orders'] ?? '[]', true) ?? [];
                $returns = json_decode($row['pharmacy_returns'] ?? '[]', true) ?? [];
                
                // Enrich orders with product details
                foreach ($orders as &$item) {
                    $pid = $item['data']['id'] ?? $item['data']['product_id'] ?? '';
                    if (isset($productMap[$pid])) {
                        $item['data']['mrp'] = $productMap[$pid]['mrp'];
                        $item['data']['sales_price'] = $productMap[$pid]['sales_price'];
                        $item['data']['GST_price'] = $productMap[$pid]['GST_price'];
                        $item['data']['tax_percent'] = $productMap[$pid]['tax_percent'];
                        $item['data']['hsn_code'] = $productMap[$pid]['hsn_code'];
                        $item['data']['expiry_date'] = $productMap[$pid]['expiry_date'];
                        $item['data']['available_stock'] = $productMap[$pid]['available_stock'];
                        $item['data']['is_active'] = $productMap[$pid]['is_active'];
                    }
                    if (!empty($item['created_by']) && isset($userMap[$item['created_by']])) {
                        $item['pharmacist_name'] = $userMap[$item['created_by']];
                    }
                }
                foreach ($returns as &$item) {
                    $pid = $item['data']['id'] ?? $item['data']['product_id'] ?? '';
                    if (isset($productMap[$pid])) {
                        $item['data']['mrp'] = $productMap[$pid]['mrp'];
                        $item['data']['sales_price'] = $productMap[$pid]['sales_price'];
                        $item['data']['GST_price'] = $productMap[$pid]['GST_price'];
                        $item['data']['tax_percent'] = $productMap[$pid]['tax_percent'];
                        $item['data']['available_stock'] = $productMap[$pid]['available_stock'];
                    }
                }
                
                $formattedOrders[] = [
                    'id' => $row['id'],
                    'patient_id' => $row['patient_id'],
                    'patient_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                    'admission_id' => $row['admission_id'],
                    'ward' => $row['ward_name'],
                    'room' => $row['room_no'],
                    'bed' => $row['bed_id'],
                    'date' => $row['record_date'],
                    'orders' => $orders,
                    'returns' => $returns,
                ];
            }
            
            $this->respondSuccess($formattedOrders, 'IP Orders fetched successfully');
            
        } catch (Exception $e) {
            $this->respondError('Failed to fetch IP orders: ' . $e->getMessage());
        }
    }

    public function complete() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $orderId = $data['order_id'] ?? null;
            $itemIndex = isset($data['item_index']) ? (int)$data['item_index'] : null;
            $itemsData = $data['items'] ?? [];
            
            if (!$orderId) {
                return $this->respondError('Order ID is required');
            }
            
            $db = $this->db;
            
            // Fetch the order with lock
            $orderRecords = $db->fetchAll("
                SELECT c.patient_id, c.admission_id, c.record_date, c.pharmacy_orders, c.created_by, p.first_name, p.last_name 
                FROM ipd_clinical_records c 
                LEFT JOIN patient p ON c.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci 
                WHERE c.id = ?
            ", [$orderId]);
            $orderRow = $orderRecords[0] ?? null;
            
            if (!$orderRow) {
                return $this->respondError('Order not found');
            }
            
            $orders = json_decode($orderRow['pharmacy_orders'] ?? '[]', true) ?? [];
            if (empty($orders)) {
                return $this->respondError('No items in this order');
            }
            
            $patientName = trim(($orderRow['first_name'] ?? '') . ' ' . ($orderRow['last_name'] ?? ''));
            
            // Check if patient is discharged
            if (!empty($orderRow['admission_id'])) {
                $adm = $db->fetchOne("SELECT status, discharge_date FROM ipd_admissions WHERE admission_id = ?", [$orderRow['admission_id']]);
                if ($adm && ($adm['status'] === 'Discharged' || !empty($adm['discharge_date']))) {
                    return $this->respondError('This patient has already been discharged.');
                }
            }

            $nurseId = $orders[0]['created_by'] ?? $orderRow['created_by'] ?? 'staff';
            
            $db->beginTransaction();
            
            try {
                $itemsList = [];
                $completedNames = [];
                $subTotal = 0;

                if ($itemIndex !== null) {
                    // ── SINGLE ITEM COMPLETION ──
                    if (!isset($orders[$itemIndex])) {
                        $db->rollback();
                        return $this->respondError('Specified item index not found in order.');
                    }

                    $item = &$orders[$itemIndex];
                    if (($item['status'] ?? '') === 'Completed') {
                        $db->rollback();
                        return $this->respondError('This item is already completed.');
                    }

                    $dataObj = &$item['data'];
                    if (!is_array($dataObj)) $dataObj = [];

                    $qty = (int)($dataObj['qty'] ?? $dataObj['quantity'] ?? $item['qty'] ?? 0);
                    if ($qty <= 0) {
                        $db->rollback();
                        return $this->respondError('Invalid item quantity.');
                    }

                    $productId = $dataObj['id'] ?? $dataObj['product_id'] ?? $item['product_id'] ?? $item['id'] ?? '';
                    if (empty($productId) && !empty($dataObj['name'] ?? $dataObj['medicine_name'] ?? '')) {
                        $pName = trim($dataObj['name'] ?? $dataObj['medicine_name'] ?? '');
                        $foundProd = $db->fetchOne("SELECT product_id FROM ph_product WHERE product_name = ? LIMIT 1", [$pName]);
                        if ($foundProd) {
                            $productId = $foundProd['product_id'];
                            $dataObj['id'] = $productId;
                        }
                    }

                    if (empty($productId)) {
                        $db->rollback();
                        return $this->respondError('Product ID not found for this item.');
                    }

                    // Lock and re-check live product stock
                    $prodRow = $db->fetchOne("SELECT product_id, product_name, quantity, mrp, sales_price, is_active FROM ph_product WHERE product_id = ? FOR UPDATE", [$productId]);
                    if (!$prodRow) {
                        $db->rollback();
                        return $this->respondError('Product not found in pharmacy inventory.');
                    }

                    if (isset($prodRow['is_active']) && (int)$prodRow['is_active'] === 0) {
                        $db->rollback();
                        return $this->respondError("Product '{$prodRow['product_name']}' is marked inactive in pharmacy inventory.");
                    }

                    $prodName = $prodRow['product_name'] ?? ($dataObj['name'] ?? 'Medicine');
                    $available = (int)($prodRow['quantity'] ?? 0);

                    if ($available <= 0) {
                        $db->rollback();
                        return $this->respondError("Out of Stock: '{$prodName}' currently has 0 available units in stock.");
                    }

                    if ($available < $qty) {
                        $db->rollback();
                        return $this->respondError("Insufficient Stock: Only {$available} units are currently available for {$prodName}, but the requested quantity is {$qty}. Please reduce the requested quantity or update the stock before completing the order.");
                    }

                    // Atomic deduction with concurrency guard
                    $affected = $db->execute("UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?", [$qty, $productId, $qty]);
                    if (empty($affected['affected_rows'])) {
                        $db->rollback();
                        return $this->respondError('Stock conflict: Available stock changed. Please retry.');
                    }

                    // Mark item as Completed
                    $item['status'] = 'Completed';
                    $item['completed_at'] = date('Y-m-d H:i:s');
                    $item['completed_by'] = $_SESSION['user_id'] ?? 0;
                    $item['pharmacist_name'] = $_SESSION['username'] ?? 'Pharmacist';

                    $discPercent = isset($data['disc_percent']) ? (float)$data['disc_percent'] : (float)($dataObj['disc_percent'] ?? 0);
                    $dataObj['disc_percent'] = $discPercent;

                    $rate = (float)($dataObj['mrp'] ?? $prodRow['mrp'] ?? $prodRow['sales_price'] ?? 0);
                    $dataObj['mrp'] = $rate;

                    $rawTotal = $rate * $qty;
                    $discountedTotal = $rawTotal - ($rawTotal * ($discPercent / 100));
                    $subTotal += $discountedTotal;

                    $itemsList[] = [
                        'test_id' => $productId,
                        'test_name' => $prodName,
                        'qty' => $qty,
                        'unit_price' => $rate,
                        'disc_percent' => $discPercent,
                        'amount' => $discountedTotal
                    ];
                    $completedNames[] = $prodName;

                } else {
                    // ── BULK / MULTI-ITEM COMPLETION (All eligible in-stock items) ──
                    $insufficientItems = [];

                    foreach ($orders as $index => &$item) {
                        if (($item['status'] ?? '') !== 'Completed') {
                            $dataObj = &$item['data'];
                            if (!is_array($dataObj)) $dataObj = [];

                            $qty = (int)($dataObj['qty'] ?? $dataObj['quantity'] ?? $item['qty'] ?? 0);
                            $productId = $dataObj['id'] ?? $dataObj['product_id'] ?? $item['product_id'] ?? $item['id'] ?? '';

                            if (empty($productId) && !empty($dataObj['name'] ?? $dataObj['medicine_name'] ?? '')) {
                                $pName = trim($dataObj['name'] ?? $dataObj['medicine_name'] ?? '');
                                $foundProd = $db->fetchOne("SELECT product_id FROM ph_product WHERE product_name = ? LIMIT 1", [$pName]);
                                if ($foundProd) {
                                    $productId = $foundProd['product_id'];
                                    $dataObj['id'] = $productId;
                                }
                            }

                            if ($qty <= 0 || empty($productId)) continue;

                            // Check live stock with lock
                            $prodRow = $db->fetchOne("SELECT product_id, product_name, quantity, mrp, sales_price, is_active FROM ph_product WHERE product_id = ? FOR UPDATE", [$productId]);
                            if (!$prodRow || (isset($prodRow['is_active']) && (int)$prodRow['is_active'] === 0)) continue;

                            $available = (int)($prodRow['quantity'] ?? 0);
                            $prodName = $prodRow['product_name'] ?? ($dataObj['name'] ?? 'Medicine');

                            if ($available < $qty) {
                                $insufficientItems[] = "{$prodName} (Need: {$qty}, Avail: {$available})";
                                continue;
                            }

                            // Atomic deduction
                            $affected = $db->execute("UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?", [$qty, $productId, $qty]);
                            if (empty($affected['affected_rows'])) continue;

                            $item['status'] = 'Completed';
                            $item['completed_at'] = date('Y-m-d H:i:s');
                            $item['completed_by'] = $_SESSION['user_id'] ?? 0;
                            $item['pharmacist_name'] = $_SESSION['username'] ?? 'Pharmacist';

                            if (isset($itemsData[$index])) {
                                $dataObj['disc_percent'] = $itemsData[$index]['disc_percent'] ?? 0;
                            }
                            $discPercent = (float)($dataObj['disc_percent'] ?? 0);

                            $rate = (float)($dataObj['mrp'] ?? $prodRow['mrp'] ?? $prodRow['sales_price'] ?? 0);
                            $dataObj['mrp'] = $rate;

                            $rawTotal = $rate * $qty;
                            $discountedTotal = $rawTotal - ($rawTotal * ($discPercent / 100));
                            $subTotal += $discountedTotal;

                            $itemsList[] = [
                                'test_id' => $productId,
                                'test_name' => $prodName,
                                'qty' => $qty,
                                'unit_price' => $rate,
                                'disc_percent' => $discPercent,
                                'amount' => $discountedTotal
                            ];
                            $completedNames[] = $prodName;
                        }
                    }

                    if (empty($itemsList)) {
                        $db->rollback();
                        if (!empty($insufficientItems)) {
                            return $this->respondError('Cannot complete items due to insufficient stock: ' . implode(', ', $insufficientItems));
                        }
                        return $this->respondError('No active items available to complete.');
                    }
                }
                
                $globalDiscount = ($itemIndex === null) ? (float)($data['global_discount'] ?? 0) : 0;
                $netPayable = max(0, $subTotal - $globalDiscount);

                // Save updated orders JSON
                $newOrdersJson = json_encode($orders);
                $db->execute("UPDATE ipd_clinical_records SET pharmacy_orders = ? WHERE id = ?", [$newOrdersJson, $orderId]);
                
                // ── BILLING INTEGRATION ──
                $admissionId = $orderRow['admission_id'];
                $patientId = $orderRow['patient_id'];
                $recordDate = $orderRow['record_date'] ?? date('Y-m-d');
                $updatedBy = $_SESSION['username'] ?? 'system';

                $paymentRepo = new PaymentRepository();
                $master = $paymentRepo->getMasterBillInfo($admissionId);
                
                if ($master && !empty($itemsList)) {
                    $billId = $master['bill_id'];
                    $descStr = implode(', ', $completedNames);
                    if (strlen($descStr) > 200) {
                        $descStr = substr($descStr, 0, 197) . '...';
                    }
                    $fullDescription = 'Pharmacy Charges - ' . date('d-M-Y', strtotime($recordDate)) . " ($descStr)";
                    if ($globalDiscount > 0) {
                        $fullDescription .= " (Global Disc: ₹{$globalDiscount})";
                    }

                    $paymentRepo->insertBillingItem([
                        'bill_id'         => $billId,
                        'patient_id'      => $patientId,
                        'admission_id'    => $admissionId,
                        'charge_date'     => date('Y-m-d'),
                        'charge_type'     => 'PHARMACY',
                        'department'      => 'PHARMACY',
                        'description'     => $fullDescription,
                        'reference_table' => 'ipd_clinical_records',
                        'reference_id'    => $orderId,
                        'total_amount'    => $netPayable,
                        'items_json'      => json_encode($itemsList),
                        'status'          => 'COMPLETED',
                        'created_by'      => $updatedBy,
                        'created_at'      => date('Y-m-d H:i:s'),
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ]);

                    $paymentRepo->recalculateMasterTotals($billId, $updatedBy);
                }

                // ── NOTIFY NURSE ──
                if ($nurseId) {
                    $notificationId = 'NOTIF-' . uniqid();
                    $title = "Pharmacy Item Ready";
                    $medListStr = implode(', ', $completedNames);
                    $message = "Order #IPO-{$orderId} for patient {$patientName}: {$medListStr} is completed and ready for pickup.";
                    
                    $db->execute("INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                        $notificationId, $nurseId, 'staff', $title, $message, 'system', 'normal'
                    ]);
                }
                
                $db->commit();
                $msg = ($itemIndex !== null)
                    ? "Item '{$completedNames[0]}' completed successfully and stock deducted."
                    : "Completed " . count($itemsList) . " items successfully and stock deducted.";
                $this->respondSuccess(['completed_items' => $completedNames], $msg);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            $this->respondError($e->getMessage());
        }
    }
}
