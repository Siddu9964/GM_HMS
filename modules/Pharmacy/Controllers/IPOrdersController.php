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
                $prodSql = "SELECT product_id, mrp, sales_price, tax_percent, hsn_code, expiry_date FROM ph_product WHERE product_id IN ($placeholders)";
                $prodRecords = $db->fetchAll($prodSql, $productIds);
                foreach ($prodRecords as $pr) {
                    $productMap[$pr['product_id']] = [
                        'mrp' => $pr['mrp'],
                        'sales_price' => $pr['sales_price'],
                        'tax_percent' => $pr['tax_percent'],
                        'hsn_code' => $pr['hsn_code'],
                        'expiry_date' => $pr['expiry_date']
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
                    $pid = $item['data']['id'] ?? '';
                    if (isset($productMap[$pid])) {
                        $item['data']['mrp'] = $productMap[$pid]['mrp'];
                        $item['data']['sales_price'] = $productMap[$pid]['sales_price'];
                        $item['data']['tax_percent'] = $productMap[$pid]['tax_percent'];
                        $item['data']['hsn_code'] = $productMap[$pid]['hsn_code'];
                        $item['data']['expiry_date'] = $productMap[$pid]['expiry_date'];
                    }
                    if (!empty($item['created_by']) && isset($userMap[$item['created_by']])) {
                        $item['pharmacist_name'] = $userMap[$item['created_by']];
                    }
                }
                foreach ($returns as &$item) {
                    $pid = $item['data']['id'] ?? '';
                    if (isset($productMap[$pid])) {
                        $item['data']['mrp'] = $productMap[$pid]['mrp'];
                        $item['data']['sales_price'] = $productMap[$pid]['sales_price'];
                        $item['data']['tax_percent'] = $productMap[$pid]['tax_percent'];
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
            $itemsData = $data['items'] ?? [];
            
            if (!$orderId) {
                return $this->respondError('Order ID is required');
            }
            
            $db = $this->db;
            
            // Fetch the order
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
            
            // The row-level created_by might be null if created by a different script.
            // Extract the actual nurse ID from the first item's audit block.
            $nurseId = $orders[0]['created_by'] ?? $orderRow['created_by'] ?? 'staff';
            
            $db->beginTransaction();
            
            try {
                $alreadyCompleted = true;
                $subTotal = 0;
                $itemsList = [];

                foreach ($orders as $index => &$item) {
                    if (($item['status'] ?? '') !== 'Completed') {
                        // Save discounts back to order for active items
                        if (isset($itemsData[$index])) {
                            $item['data']['disc_percent'] = $itemsData[$index]['disc_percent'] ?? 0;
                        }

                        $alreadyCompleted = false;
                        $item['status'] = 'Completed';
                        $qty = (int)($item['data']['qty'] ?? 0);
                        $productId = $item['data']['id'] ?? '';
                        
                        if ($qty > 0 && !empty($productId)) {
                            $db->execute("UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ?", [$qty, $productId]);
                        }

                        // Calculate totals for billing ONLY for new items
                        $dataObj = $item['data'] ?? [];
                        
                        $rate = (float)($dataObj['sales_price'] ?? $dataObj['mrp'] ?? 0);
                        if ($rate == 0 && !empty($productId)) {
                            $prod = $db->fetchOne("SELECT sales_price, mrp FROM ph_product WHERE product_id = ?", [$productId]);
                            if ($prod) {
                                $rate = (float)($prod['sales_price'] ?? $prod['mrp'] ?? 0);
                                // Store it permanently in the JSON
                                $item['data']['sales_price'] = $rate;
                            }
                        }
                        
                        $discPercent = (float)($dataObj['disc_percent'] ?? 0);
                        
                        $rawTotal = $rate * $qty;
                        $discountedTotal = $rawTotal - ($rawTotal * ($discPercent / 100));
                        $subTotal += $discountedTotal;

                        // Populate items list for ipd_billing_items
                        $itemsList[] = [
                            'test_id' => $dataObj['id'] ?? '',
                            'test_name' => $dataObj['name'] ?? '',
                            'qty' => $qty,
                            'unit_price' => $rate,
                            'disc_percent' => $discPercent,
                            'amount' => $discountedTotal
                        ];
                    }
                }
                
                if ($alreadyCompleted) {
                    $db->rollback();
                    return $this->respondError('Order is already completed');
                }
                
                $globalDiscount = (float)($data['global_discount'] ?? 0);
                $netPayable = $subTotal - $globalDiscount;

                // Save updated JSON
                $newOrdersJson = json_encode($orders);
                $db->execute("UPDATE ipd_clinical_records SET pharmacy_orders = ? WHERE id = ?", [$newOrdersJson, $orderId]);
                
                // BILLING INTEGRATION
                $admissionId = $orderRow['admission_id'];
                $patientId = $orderRow['patient_id'];
                $recordDate = $orderRow['record_date'] ?? date('Y-m-d');
                $updatedBy = $_SESSION['username'] ?? 'system';

                $paymentRepo = new PaymentRepository();
                $master = $paymentRepo->getMasterBillInfo($admissionId);
                
                if ($master && !empty($itemsList)) {
                    $billId = $master['bill_id'];
                    $testNames = array_column($itemsList, 'test_name');
                    $descStr = implode(', ', $testNames);
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

                // Notify Nurse
                if ($nurseId) {
                    $notificationId = 'NOTIF-' . uniqid();
                    $title = "Pharmacy Order Ready";
                    $message = "Order #IPO-{$orderId} for patient {$patientName} is ready for pickup.";
                    
                    $db->execute("INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                        $notificationId, $nurseId, 'staff', $title, $message, 'system', 'normal'
                    ]);
                }
                
                $db->commit();
                $this->respondSuccess(null, 'Order completed successfully');
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            $this->respondError('Failed to complete order: ' . $e->getMessage());
        }
    }
}
