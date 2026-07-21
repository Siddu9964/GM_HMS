<?php
/**
 * ============================================================
 * PharmacyGrnController — API Reference (Goods Receipt Notes)
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/grn
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/grn
 *    Query: supplier_id, date_from, date_to, status
 *
 * 2. GET /api/pharmacy/grn/{id}
 *    Returns GRN with all line items
 *
 * 3. POST /api/pharmacy/grn
 *    Body:
 *      { "supplier_id":3, "invoice_no":"INV-SUP-001", "invoice_date":"2026-06-26",
 *        "items":[{ "product_id":15, "batch_no":"B2026A", "expiry_date":"2028-12-31",
 *                   "qty":500, "purchase_price":4.50, "selling_price":6.00 }] }
 *
 * 4. DELETE /api/pharmacy/grn/{id}
 *
 * 5. DELETE /api/pharmacy/grn-item/{id}
 *    Deletes individual GRN line item
 *
 * 6. GET /api/pharmacy/grn/{id}/check-delete
 *    Checks if GRN can be safely deleted
 *
 * 7. GET /api/pharmacy/grn-item/{id}/check-delete
 *    Checks if line item can be safely deleted
 *
 * 8. POST /api/pharmacy/grn/bulk-submit
 *    Body: { "grn_ids":[1,2,3] }  — bulk confirm GRNs
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyGrnController (Stock Receive / GRN)
 * Table: ph_stock_receive, ph_stock_receive_items
 */
class PharmacyGrnController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/grn */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $results = $this->db->fetchAll(
                "SELECT MAX(id) as id, receive_no, MAX(receive_date) as receive_date, MAX(po_no) as po_no, 
                        MAX(supplier_name) as supplier_name, MAX(invoice_no) as invoice_no, 
                        SUM(net_qty) as total_qty, SUM(net_qty * rate) as total_amount,
                        MAX(status) as status
                 FROM ph_stock_receive 
                 GROUP BY receive_no 
                 ORDER BY receive_date DESC, receive_no DESC LIMIT 100"
            );

            // Fetch and attach nested items for the expandable master-detail view
            $receiveNos = array_column($results, 'receive_no');
            if (!empty($receiveNos)) {
                $placeholders = implode(',', array_fill(0, count($receiveNos), '?'));
                $items = $this->db->fetchAll(
                    "SELECT id, receive_no, product_id, item_name, batch_no, expiry_date, net_qty, rate, (net_qty * rate) as subtotal, status 
                     FROM ph_stock_receive 
                     WHERE receive_no IN ($placeholders)", 
                    $receiveNos
                );
                
                foreach ($results as &$res) {
                    $res['items'] = array_values(array_filter($items, function($i) use ($res) {
                        return $i['receive_no'] === $res['receive_no'];
                    }));
                }
            }

            $this->respondSuccess($results);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/grn/{id} */
    public function show(string $id): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $grn = $this->db->fetchOne(
                "SELECT receive_no, MAX(receive_date) as receive_date, MAX(po_no) as po_no, 
                        MAX(supplier_id) as supplier_id, MAX(supplier_name) as supplier_name, 
                        MAX(invoice_no) as invoice_no, SUM(net_qty * rate) as total_amount 
                 FROM ph_stock_receive 
                 WHERE id = ? OR receive_no = ?
                 GROUP BY receive_no", 
                [$id, $id]
            );
            if (!$grn) { $this->respondNotFound("GRN Record not found"); return; }
            
            $items = $this->db->fetchAll(
                "SELECT * FROM ph_stock_receive WHERE receive_no = ?", 
                [$grn['receive_no']]
            );
            
            // Calculate subtotal dynamically as it's not stored in the flat schema
            foreach($items as &$item) {
                $item['subtotal'] = $item['net_qty'] * $item['rate'];
            }
            
            $this->respondSuccess(['grn' => $grn, 'items' => $items]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/grn */
    public function create(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        $d = $this->getJsonInput();
        
        $items = $d['items'] ?? [];
        if (empty($items)) { $this->respondBadRequest('No items in GRN'); return; }

        try {
            $conn = $this->db->getConnection();
            mysqli_begin_transaction($conn);

            $receiveNo = $d['receive_no'] ?? null;

            if ($receiveNo) {
                // Ensure it's a draft
                $draftCheck = $this->db->fetchOne("SELECT status FROM ph_stock_receive WHERE receive_no = ? LIMIT 1", [$receiveNo]);
                if (!$draftCheck) {
                    $this->respondBadRequest('GRN not found.');
                    return;
                }
                if ($draftCheck['status'] == 1) {
                    $this->respondBadRequest('Cannot edit a submitted GRN.');
                    return;
                }
                // Delete existing draft items
                $this->db->execute("DELETE FROM ph_stock_receive WHERE receive_no = ?", [$receiveNo]);
            } else {
                // Generate Receive No based on highest existing number
                $row = $this->db->fetchOne("SELECT MAX(CAST(SUBSTRING(receive_no, 5) AS UNSIGNED)) as mx FROM ph_stock_receive");
                $nextId = ($row['mx'] ?? 0) + 1;
                $receiveNo = 'GRN-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            }

            // Insert Items & Update Stock into the flat ph_stock_receive table
            foreach ($items as $item) {
                $this->db->execute(
                    "INSERT INTO ph_stock_receive (
                        receive_no, receive_date, po_no, supplier_id, supplier_name, invoice_no, 
                        product_id, item_name, content, strength, form, therapeutic,
                        hsn_code, manufacturer, batch_no, expiry_date, 
                        received_qty, damaged_qty, net_qty, rate, mrp, tax_percent,
                        pack_rate, individual_rate, pack, unit, pack_size,
                        min_stock, max_stock, rack_location, status
                    ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)",
                    [
                        $receiveNo, 
                        $d['po_no'] ?? null, 
                        $d['supplier_id'] ?? null, 
                        $d['supplier_name'] ?? null, 
                        $d['invoice_no'] ?? null,
                        $item['product_id'],
                        $item['item_name'],
                        $item['content'] ?? null,
                        $item['strength'] ?? null,
                        $item['form'] ?? null,
                        $item['therapeutic'] ?? null,
                        $item['hsn_code'] ?? null,
                        $item['manufacturer'] ?? null,
                        $item['batch_no'] ?? null,
                        $item['expiry_date'] ?: null,
                        (int)($item['received_qty'] ?? 0),
                        (int)($item['damaged_qty'] ?? 0),
                        (int)($item['net_qty'] ?? 0),
                        (float)($item['rate'] ?? 0),
                        (float)($item['mrp'] ?? 0.00),
                        (float)($item['tax_percent'] ?? 12.00),
                        (float)($item['pack_rate'] ?? 0.00),
                        (float)($item['individual_rate'] ?? 0.00),
                        $item['pack'] ?? null,
                        $item['unit'] ?? 'Tablet',
                        (int)($item['pack_size'] ?? 10),
                        (int)($item['min_stock'] ?? 20),
                        (int)($item['max_stock'] ?? 500),
                        $item['rack_location'] ?? null
                    ]
                );
            }

            mysqli_commit($conn);
            $this->respondCreated(['receive_no' => $receiveNo]);

        } catch (Exception $e) {
            mysqli_rollback($this->db->getConnection());
            $this->handleException($e);
        }
    }

    /** POST /api/pharmacy/grn/bulk-submit */
    public function bulkSubmit(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            // Debug incoming data
            error_log("[DEBUG] bulkSubmit - Raw POST: " . json_encode($_POST));
            error_log("[DEBUG] bulkSubmit - Raw php://input: " . file_get_contents('php://input'));

            // Robustly parse IDs from JSON body, standard POST, or FormData comma-separated string
            $ids = [];
            if (!empty($_POST['ids'])) {
                $rawIds = $_POST['ids'];
                if (is_string($rawIds)) {
                    $ids = explode(',', $rawIds);
                } else if (is_array($rawIds)) {
                    $ids = $rawIds;
                }
            } else {
                // Try JSON input
                $json = file_get_contents('php://input');
                if (!empty($json)) {
                    $d = json_decode($json, true);
                    $ids = $d['ids'] ?? [];
                }
            }

            $ids = array_filter(array_map('intval', $ids), fn($v) => $v > 0);
            error_log("[DEBUG] bulkSubmit - Parsed IDs: " . json_encode($ids));
            if (empty($ids)) { $this->respondBadRequest('No valid IDs selected'); return; }

            $conn = $this->db->getConnection();
            mysqli_begin_transaction($conn);

            // Fetch unique receive_nos of the selected drafts first (backward compatible with NULL/empty status)
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $draftGRNs = $this->db->fetchAll(
                "SELECT DISTINCT receive_no FROM ph_stock_receive WHERE id IN ($placeholders) AND (status = 0 OR status IS NULL OR status = '')",
                array_values($ids)
            );

            error_log("[DEBUG] bulkSubmit - Draft GRNs found: " . json_encode($draftGRNs));

            if (empty($draftGRNs)) {
                mysqli_commit($conn);
                $this->respondSuccess('Selected GRNs were already submitted.');
                return;
            }

            $receiveNos = array_column($draftGRNs, 'receive_no');
            $receiveNoPlaceholders = implode(',', array_fill(0, count($receiveNos), '?'));

            // Fetch ALL items belonging to these receive_nos that are drafts
            $draftItems = $this->db->fetchAll(
                "SELECT * FROM ph_stock_receive WHERE receive_no IN ($receiveNoPlaceholders) AND (status = 0 OR status IS NULL OR status = '')",
                array_values($receiveNos)
            );

            // 1. Update status to 1 for all items in these GRNs
            $this->db->execute(
                "UPDATE ph_stock_receive SET status = 1 WHERE receive_no IN ($receiveNoPlaceholders)",
                array_values($receiveNos)
            );

            // 2. Perform Stock & Batch update for each item
            foreach ($draftItems as $item) {
                // Check if a row with the exact product_id AND batch_number exists in ph_product
                $existingBatchRow = $this->db->fetchOne(
                    "SELECT * FROM ph_product WHERE product_id = ? AND batch_number = ? LIMIT 1",
                    [$item['product_id'], $item['batch_no']]
                );

                $newProductId = $item['product_id'];

                if ($existingBatchRow) {
                    // Update this specific batch row's stock
                    $this->db->execute(
                        "UPDATE ph_product SET 
                            quantity = quantity + ?,
                            expiry_date = ?,
                            purchase_rate = ?,
                            content = COALESCE(NULLIF(?, ''), content),
                            strength = COALESCE(NULLIF(?, ''), strength),
                            form = COALESCE(NULLIF(?, ''), form),
                            therapeutic = COALESCE(NULLIF(?, ''), therapeutic),
                            hsn_code = COALESCE(NULLIF(?, ''), hsn_code),
                            manufacturer = COALESCE(NULLIF(?, ''), manufacturer),
                            mrp = COALESCE(NULLIF(?, 0.00), mrp),
                            tax_percent = COALESCE(NULLIF(?, 0.00), tax_percent),
                            pack_rate = COALESCE(NULLIF(?, 0.00), pack_rate),
                            individual_rate = COALESCE(NULLIF(?, 0.00), individual_rate),
                            pack = COALESCE(NULLIF(?, ''), pack),
                            unit = COALESCE(NULLIF(?, ''), unit),
                            pack_size = COALESCE(NULLIF(?, 0), pack_size),
                            min_stock = COALESCE(NULLIF(?, 0), min_stock),
                            max_stock = COALESCE(NULLIF(?, 0), max_stock),
                            rack_location = COALESCE(NULLIF(?, ''), rack_location)
                         WHERE product_id = ? AND batch_number = ?",
                        [
                            (int)($item['net_qty'] ?? 0),
                            $item['expiry_date'] ?: null,
                            (float)($item['rate'] ?? 0),
                            $item['content'],
                            $item['strength'],
                            $item['form'],
                            $item['therapeutic'],
                            $item['hsn_code'],
                            $item['manufacturer'],
                            (float)($item['mrp'] ?? 0.00),
                            (float)($item['tax_percent'] ?? 12.00),
                            (float)($item['pack_rate'] ?? 0.00),
                            (float)($item['individual_rate'] ?? 0.00),
                            $item['pack'],
                            $item['unit'],
                            (int)($item['pack_size'] ?? 10),
                            (int)($item['min_stock'] ?? 20),
                            (int)($item['max_stock'] ?? 500),
                            $item['rack_location'],
                            $item['product_id'],
                            $item['batch_no']
                        ]
                    );
                } else {
                    // Generate a brand new unique product_id so that each batch gets a different product_id
                    do {
                        $newProductId = 'PRD-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                        $exists = $this->db->fetchOne("SELECT product_id FROM ph_product WHERE product_id = ?", [$newProductId]);
                    } while ($exists);

                    // Fetch existing base product template to copy metadata
                    $baseProduct = $this->db->fetchOne(
                        "SELECT * FROM ph_product WHERE product_id = ? LIMIT 1",
                        [$item['product_id']]
                    );
                    
                    if ($baseProduct) {
                        // Insert a new batch row copying metadata with the new unique product_id
                        $this->db->execute(
                            "INSERT INTO ph_product (
                                product_id, product_name, content, strength, form, therapeutic, 
                                hsn_code, manufacturer, purchase_rate, mrp, tax_percent, quantity, 
                                pack, unit, pack_size, min_stock, max_stock, rack_location, 
                                is_active, batch_number, expiry_date, attachment, pack_rate, individual_rate
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [
                                $newProductId,
                                $baseProduct['product_name'],
                                !empty($item['content']) ? $item['content'] : $baseProduct['content'],
                                !empty($item['strength']) ? $item['strength'] : $baseProduct['strength'],
                                !empty($item['form']) ? $item['form'] : $baseProduct['form'],
                                !empty($item['therapeutic']) ? $item['therapeutic'] : $baseProduct['therapeutic'],
                                !empty($item['hsn_code']) ? $item['hsn_code'] : $baseProduct['hsn_code'],
                                !empty($item['manufacturer']) ? $item['manufacturer'] : $baseProduct['manufacturer'],
                                (float)($item['rate'] ?? 0),
                                (float)($item['mrp'] ?? 0) ?: (float)($baseProduct['mrp'] ?? 0),
                                (float)($item['tax_percent'] ?? 0) ?: (float)($baseProduct['tax_percent'] ?? 12.00),
                                (int)($item['net_qty'] ?? 0),
                                !empty($item['pack']) ? $item['pack'] : $baseProduct['pack'],
                                !empty($item['unit']) ? $item['unit'] : $baseProduct['unit'],
                                (int)($item['pack_size'] ?? 0) ?: (int)($baseProduct['pack_size'] ?? 10),
                                (int)($item['min_stock'] ?? 0) ?: (int)($baseProduct['min_stock'] ?? 20),
                                (int)($item['max_stock'] ?? 0) ?: (int)($baseProduct['max_stock'] ?? 500),
                                !empty($item['rack_location']) ? $item['rack_location'] : $baseProduct['rack_location'],
                                1, // is_active
                                $item['batch_no'],
                                $item['expiry_date'] ?: null,
                                $baseProduct['attachment'] ?? '',
                                (float)($item['pack_rate'] ?? 0.00) ?: (float)($baseProduct['pack_rate'] ?? 0.00),
                                (float)($item['individual_rate'] ?? 0.00) ?: (float)($baseProduct['individual_rate'] ?? 0.00)
                            ]
                        );
                    } else {
                        // Fallback: If no base product template exists, insert a clean row with all metadata from item
                        $this->db->execute(
                            "INSERT INTO ph_product (
                                product_id, product_name, content, strength, form, therapeutic,
                                hsn_code, manufacturer, purchase_rate, mrp, tax_percent, quantity,
                                pack, unit, pack_size, min_stock, max_stock, rack_location,
                                is_active, batch_number, expiry_date, attachment, pack_rate, individual_rate
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?)",
                            [
                                $newProductId,
                                $item['item_name'],
                                $item['content'] ?? null,
                                $item['strength'] ?? null,
                                $item['form'] ?? null,
                                $item['therapeutic'] ?? null,
                                $item['hsn_code'] ?? null,
                                $item['manufacturer'] ?? null,
                                (float)($item['rate'] ?? 0),
                                (float)($item['mrp'] ?? 0.00),
                                (float)($item['tax_percent'] ?? 12.00),
                                (int)($item['net_qty'] ?? 0),
                                $item['pack'] ?? null,
                                $item['unit'] ?? 'Tablet',
                                (int)($item['pack_size'] ?? 10),
                                (int)($item['min_stock'] ?? 20),
                                (int)($item['max_stock'] ?? 500),
                                $item['rack_location'] ?? null,
                                1, // is_active
                                $item['batch_no'],
                                $item['expiry_date'] ?: null,
                                (float)($item['pack_rate'] ?? 0.00),
                                (float)($item['individual_rate'] ?? 0.00)
                            ]
                        );
                    }

                    // Update the product_id in ph_stock_receive for this item so it links to the new unique product_id row
                    $this->db->execute(
                        "UPDATE ph_stock_receive SET product_id = ? WHERE id = ?",
                        [$newProductId, $item['id']]
                    );
                }

                // Upsert Batch into ph_product_batches
                $batchNo = $item['batch_no'] ?: 'DEFAULT-BATCH';
                $this->db->execute(
                    "INSERT INTO ph_product_batches (product_id, batch_number, expiry_date, quantity)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE quantity = quantity + ?",
                    [
                        $newProductId,
                        $batchNo,
                        $item['expiry_date'] ?: null,
                        (int)($item['net_qty'] ?? 0),
                        (int)($item['net_qty'] ?? 0)
                    ]
                );
            }

            mysqli_commit($conn);
            $this->respondSuccess(null, count($receiveNos) . ' GRN(s) submitted and stock committed successfully!');

        } catch (Exception $e) {
            mysqli_rollback($this->db->getConnection());
            $this->handleException($e);
        }
    }

    /** GET /api/pharmacy/grn/{receive_no}/check-delete */
    public function checkDelete(string $receive_no): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $items = $this->db->fetchAll("SELECT * FROM ph_stock_receive WHERE receive_no = ?", [$receive_no]);
            if (empty($items)) {
                $this->respondNotFound("GRN not found");
                return;
            }

            // Check if submitted
            $isSubmitted = false;
            foreach ($items as $item) {
                if ($item['status'] == 1) {
                    $isSubmitted = true;
                    break;
                }
            }

            if (!$isSubmitted) {
                $this->respondSuccess(['warning' => false]);
                return;
            }

            // If submitted, check inventory and sales
            $hasUsage = false;
            $breakdown = [];
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $netQty = (int)$item['net_qty'];
                
                // Fetch current stock from ph_product
                $prod = $this->db->fetchOne("SELECT quantity, mrp FROM ph_product WHERE product_id = ?", [$productId]);
                $currentStock = $prod ? (int)$prod['quantity'] : 0;
                
                // Check if sales exist
                $salesCount = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM ph_sales_items WHERE product_id = ?", [$productId]);
                $hasSales = ($salesCount && $salesCount['cnt'] > 0);

                // Reversal logic warning trigger
                if ($currentStock < $netQty || $hasSales) {
                    $hasUsage = true;
                }

                $breakdown[] = [
                    'item_name' => $item['item_name'],
                    'batch_no' => $item['batch_no'],
                    'grn_qty' => $netQty,
                    'current_stock' => $currentStock,
                    'has_sales' => $hasSales,
                    'product_id' => $productId,
                    'mrp' => $prod ? (float)$prod['mrp'] : (float)$item['mrp']
                ];
            }

            $this->respondSuccess([
                'warning' => $hasUsage, 
                'message' => $hasUsage 
                    ? 'WARNING: Stock from this GRN has already been sold or consumed! Reversing it may cause negative inventory.' 
                    : 'This GRN is submitted. Deleting it will reverse the stock quantities from live inventory. Proceed?',
                'breakdown' => $breakdown
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** DELETE /api/pharmacy/grn/{receive_no} */
    public function delete(string $receive_no): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $conn = $this->db->getConnection();
            mysqli_begin_transaction($conn);

            $items = $this->db->fetchAll("SELECT * FROM ph_stock_receive WHERE receive_no = ?", [$receive_no]);
            if (empty($items)) {
                mysqli_rollback($conn);
                $this->respondNotFound("GRN not found");
                return;
            }

            $isSubmitted = false;
            foreach ($items as $item) {
                if ($item['status'] == 1) {
                    $isSubmitted = true;
                    break;
                }
            }

            if ($isSubmitted) {
                foreach ($items as $item) {
                    $productId = $item['product_id'];
                    $netQty = (int)$item['net_qty'];
                    $batchNo = $item['batch_no'];

                    // Deduct from ph_product
                    $this->db->execute(
                        "UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ?",
                        [$netQty, $productId]
                    );

                    // Check if it's an auto-generated batch row that is now empty
                    if (str_starts_with($productId, 'PRD-')) {
                        $prod = $this->db->fetchOne("SELECT quantity FROM ph_product WHERE product_id = ?", [$productId]);
                        if ($prod && $prod['quantity'] <= 0) {
                            $hasSales = $this->db->fetchOne("SELECT id FROM ph_sales_items WHERE product_id = ? LIMIT 1", [$productId]);
                            if (!$hasSales) {
                                $this->db->execute("DELETE FROM ph_product WHERE product_id = ?", [$productId]);
                            }
                        }
                    }

                    // Deduct from ph_product_batches
                    if ($batchNo) {
                        $this->db->execute(
                            "UPDATE ph_product_batches SET quantity = quantity - ? WHERE product_id = ? AND batch_number = ?",
                            [$netQty, $productId, $batchNo]
                        );
                        $this->db->execute(
                            "DELETE FROM ph_product_batches WHERE product_id = ? AND batch_number = ? AND quantity <= 0",
                            [$productId, $batchNo]
                        );
                    }
                }
            }

            $this->db->execute("DELETE FROM ph_stock_receive WHERE receive_no = ?", [$receive_no]);

            mysqli_commit($conn);
            $this->respondSuccess(null, "GRN deleted successfully.");
        } catch (Exception $e) {
            mysqli_rollback($this->db->getConnection());
            $this->handleException($e);
        }
    }

    /** GET /api/pharmacy/grn-item/{id}/check-delete */
    public function checkDeleteItem(string $id): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $item = $this->db->fetchOne("SELECT * FROM ph_stock_receive WHERE id = ?", [$id]);
            if (!$item) { $this->respondNotFound("Item not found"); return; }
            if ($item['status'] != 1) { $this->respondSuccess(['warning' => false]); return; }
            
            $productId = $item['product_id'];
            $netQty = (int)$item['net_qty'];
            
            $prod = $this->db->fetchOne("SELECT quantity, mrp FROM ph_product WHERE product_id = ?", [$productId]);
            $currentStock = $prod ? (int)$prod['quantity'] : 0;
            
            $salesCount = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM ph_sales_items WHERE product_id = ?", [$productId]);
            $hasSales = ($salesCount && $salesCount['cnt'] > 0);
            
            $hasUsage = ($currentStock < $netQty || $hasSales);
            
            $this->respondSuccess([
                'warning' => $hasUsage,
                'message' => $hasUsage ? 'WARNING: Stock from this item has already been sold or consumed! Reversing it may cause negative inventory.' : 'This item is submitted. Deleting it will reverse the stock quantities. Proceed?',
                'item_name' => $item['item_name'],
                'batch_no' => $item['batch_no'],
                'grn_qty' => $netQty,
                'current_stock' => $currentStock,
                'has_sales' => $hasSales,
                'post_delete' => $currentStock - $netQty
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** DELETE /api/pharmacy/grn-item/{id} */
    public function deleteItem(string $id): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $conn = $this->db->getConnection();
            mysqli_begin_transaction($conn);
            
            $item = $this->db->fetchOne("SELECT * FROM ph_stock_receive WHERE id = ?", [$id]);
            if (!$item) { mysqli_rollback($conn); $this->respondNotFound("Item not found"); return; }
            
            if ($item['status'] == 1) {
                $productId = $item['product_id'];
                $netQty = (int)$item['net_qty'];
                $batchNo = $item['batch_no'];
                
                $this->db->execute("UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ?", [$netQty, $productId]);
                
                if (str_starts_with($productId, 'PRD-')) {
                    $prod = $this->db->fetchOne("SELECT quantity FROM ph_product WHERE product_id = ?", [$productId]);
                    if ($prod && $prod['quantity'] <= 0) {
                        $hasSales = $this->db->fetchOne("SELECT id FROM ph_sales_items WHERE product_id = ? LIMIT 1", [$productId]);
                        if (!$hasSales) {
                            $this->db->execute("DELETE FROM ph_product WHERE product_id = ?", [$productId]);
                        }
                    }
                }
                
                if ($batchNo) {
                    $this->db->execute("UPDATE ph_product_batches SET quantity = quantity - ? WHERE product_id = ? AND batch_number = ?", [$netQty, $productId, $batchNo]);
                    $this->db->execute("DELETE FROM ph_product_batches WHERE product_id = ? AND batch_number = ? AND quantity <= 0", [$productId, $batchNo]);
                }
            }
            
            $this->db->execute("DELETE FROM ph_stock_receive WHERE id = ?", [$id]);
            mysqli_commit($conn);
            $this->respondSuccess(null, "Item deleted successfully.");
        } catch (Exception $e) {
            mysqli_rollback($this->db->getConnection());
            $this->handleException($e);
        }
    }
}

