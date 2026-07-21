<?php
/**
 * LOCAL VENDOR API DISPATCHER
 * Handles Indent Retrieval & Quotation Submission
 */
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Diagnostic Route (No Session Required)
if ($action === 'test') {
    echo json_encode(['success' => true, 'message' => 'Local API is reachable']);
    exit;
}

if (!isset($_SESSION['vendor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$db = getDB();

try {
    if ($method === 'GET' && $action === 'getIndents') {
        $indents = $db->fetchAll(
            "SELECT ir.*, p.purchase_rate as rate, p.tax_percent 
             FROM ph_indent_requests ir
             LEFT JOIN ph_product p ON ir.product_id = p.product_id
             WHERE ir.supplier_id = ? AND ir.status IN ('pending', 'approved', 'ordered')
               AND ir.indent_no NOT IN (
                   SELECT indent_no FROM ph_quotations WHERE supplier_id = ?
               )
             ORDER BY ir.id DESC",
            [$_SESSION['vendor_id'], $_SESSION['vendor_id']]
        );
        echo json_encode(['success' => true, 'data' => $indents]);
        exit;
    }

    // ── Get Quotations for an Indent (filtered by status if provided) ──
    if ($method === 'GET' && $action === 'getQuotations') {
        $indent_no = $_GET['indent_no'] ?? '';
        $status    = $_GET['status']    ?? '';  // optional filter

        if (empty($indent_no)) {
            echo json_encode(['success' => false, 'message' => 'indent_no is required.']);
            exit;
        }

        if ($status !== '') {
            // Filter by exact status value
            $rows = $db->fetchAll(
                "SELECT * FROM ph_quotations 
                 WHERE indent_no = ? AND supplier_id = ? AND status = ? 
                 ORDER BY id DESC",
                [$indent_no, $_SESSION['vendor_id'], $status]
            );
        } else {
            // Return all statuses
            $rows = $db->fetchAll(
                "SELECT * FROM ph_quotations 
                 WHERE indent_no = ? AND supplier_id = ? 
                 ORDER BY id DESC",
                [$indent_no, $_SESSION['vendor_id']]
            );
        }

        echo json_encode(['success' => true, 'data' => $rows, 'total' => count($rows)]);
        exit;
    }

    if ($method === 'POST' && $action === 'submitQuotation') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['items'])) {
            throw new Exception("No items received.");
        }

        $date = date('Y-m-d');
        $time = date('H:i:s');
        $vendor_id = $_SESSION['vendor_id'];
        $vendor_name = $_SESSION['vendor_name'];

        $lastIdRow = $db->fetchOne("SELECT MAX(id) as max_id FROM ph_quotations");
        $lastId = $lastIdRow['max_id'] ?? 0;
        
        $i = 1;
        $quotations = [];

        foreach ($data['items'] as $item) {
            $unique_qtn_no = "QTN-" . date('Ymd') . "-" . str_pad($lastId + $i, 4, '0', STR_PAD_LEFT);
            
            $validity = (!empty($item['validity_date'])) ? $item['validity_date'] : null;
            $qty = floatval($item['qty'] ?? 0);
            $rate = floatval($item['rate'] ?? 0);
            $discount_percent = floatval($item['discount_percent'] ?? 0);
            $scheme = $item['scheme'] ?? '';
            $tax_percent = floatval($item['tax_percent'] ?? 0);
            $tax_amount = floatval($item['tax_amount'] ?? 0);
            $total = floatval($item['total_amount'] ?? ($qty * $rate));

            $db->execute(
                "INSERT INTO ph_quotations (
                    quotation_no, indent_no, quotation_date, time, validity_date, 
                    supplier_id, supplier_name, product_id, item_name, 
                    qty, rate, discount_percent, scheme, tax_percent, tax_amount, total_amount, 
                    delivery_days, remarks, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $unique_qtn_no,
                    $item['indent_no'] ?? 'N/A',
                    $date,
                    $time,
                    $validity,
                    $vendor_id,
                    $vendor_name,
                    $item['product_id'] ?? 'N/A',
                    $item['item_name'] ?? 'N/A',
                    $qty,
                    $rate,
                    $discount_percent,
                    $scheme,
                    $tax_percent,
                    $tax_amount,
                    $total,
                    0, '', 'pending'
                ]
            );

            // Update ph_indent_requests status to 'ordered'
            if (!empty($item['indent_no'])) {
                $db->execute(
                    "UPDATE ph_indent_requests SET status = 'ordered' WHERE indent_no = ?",
                    [$item['indent_no']]
                );
            }

            $quotations[] = $unique_qtn_no;
            $i++;
        }

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => count($quotations) . ' Quotations submitted successfully!',
            'quotations' => $quotations
        ]);
        exit;
    }
    if ($method === 'POST' && $action === 'submitOrder') {
        $po_no         = $_POST['po_no']         ?? '';
        $po_date       = $_POST['po_date']       ?? date('Y-m-d');
        $expected_date = $_POST['expected_date'] ?? '';
        $subtotal      = floatval($_POST['subtotal']   ?? 0);
        $tax_total     = floatval($_POST['tax_total']  ?? 0);
        $grand_total   = floatval($_POST['grand_total'] ?? ($subtotal + $tax_total));
        $remarks       = $_POST['remarks']       ?? '';
        $vendor_id     = $_SESSION['vendor_id'];
        $vendor_name   = $_SESSION['vendor_name'];

        if (empty($po_no)) {
            throw new Exception("PO Number is required.");
        }
        $expected_date = $po_date; // Default to PO Date if not provided

        $attachment_path = '';
        if (!empty($_FILES['attachment']['name'])) {
            $file = $_FILES['attachment'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($ext !== 'pdf') {
                throw new Exception("Only PDF files are allowed.");
            }
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload failed with error code: " . $file['error']);
            }

            // Generate unique filename
            $unique_name = "PO_" . time() . "_" . bin2hex(random_bytes(4)) . ".pdf";
            $target_dir  = $_SERVER['DOCUMENT_ROOT'] . "/GM_HMS/assets/po_item/";
            $target_file = $target_dir . $unique_name;

            if (!move_uploaded_file($file['tmp_name'], $target_file)) {
                throw new Exception("Failed to move uploaded file to destination.");
            }
            $attachment_path = "assets/po_item/" . $unique_name;
        } else {
            throw new Exception("PDF attachment is required.");
        }

        $invoice_no = trim($_POST['invoice_no'] ?? '');
        if (empty($invoice_no)) {
            throw new Exception("Invoice Number is required.");
        }

        $db->execute(
            "INSERT INTO ph_purchase_orders (
                po_no, po_date, supplier_id, supplier_name, expected_date, 
                subtotal, tax_total, grand_total, attachment, status, remarks, invoice_no
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $po_no, $po_date, $vendor_id, $vendor_name, $po_date,
                $subtotal, $tax_total, $grand_total, $attachment_path, 'ordered', $remarks, $invoice_no
            ]
        );

        // --- Insert Individual Items into ph_stock_receive & Update Quotation Status ---
        $items = json_decode($_POST['items'] ?? '[]', true);
        $receive_date = date('Y-m-d');

        foreach ($items as $item) {
            // Get original quotation details to populate ph_stock_receive
            $qtn = $db->fetchOne("SELECT * FROM ph_quotations WHERE id = ?", [$item['id']]);
            if ($qtn) {
                // Update Product Master with Vendor's Specs & Rates
                if (!empty($qtn['product_id']) && $qtn['product_id'] !== 'N/A') {
                    $db->execute(
                        "UPDATE ph_product SET 
                            content = ?, strength = ?, form = ?, therapeutic = ?, 
                            purchase_rate = ?, pack_rate = ?, individual_rate = ?, mrp = ?, 
                            pack = ?, unit = ?, pack_size = ?
                         WHERE product_id = ?",
                        [
                            $item['content'] ?? '', $item['strength'] ?? '', $item['form'] ?? '', $item['therapeutic'] ?? '',
                            floatval($item['purchase_rate'] ?? 0), floatval($item['pack_rate'] ?? 0), floatval($item['individual_rate'] ?? 0), floatval($item['mrp'] ?? 0),
                            $item['pack'] ?? '', $item['unit'] ?? '', $item['pack_size'] ?? '',
                            $qtn['product_id']
                        ]
                    );
                }

                // Fetch product details to populate missing fields (like manufacturer, hsn_code, min_stock)
                $prod = $db->fetchOne("SELECT * FROM ph_product WHERE product_id = ?", [$qtn['product_id']]);

                // 1. Insert into Stock Receive (Auto GRN from vendor)
                $receive_no = "REC-" . time() . "-" . $item['id'];
                $db->execute(
                    "INSERT INTO ph_stock_receive (
                        receive_no, receive_date, po_no, supplier_id, supplier_name,
                        invoice_no, product_id, item_name, content, strength, form, therapeutic, 
                        hsn_code, manufacturer, batch_no, expiry_date, received_qty, damaged_qty, net_qty, rate, 
                        mrp, tax_percent, pack_rate, individual_rate, pack, unit, pack_size, 
                        min_stock, max_stock, rack_location, status, remarks
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $receive_no, 
                        $receive_date, 
                        $po_no, 
                        $vendor_id, 
                        $vendor_name,
                        $invoice_no, 
                        $qtn['product_id'], 
                        $qtn['item_name'], 
                        $item['content'] ?? ($prod['content'] ?? ''), 
                        $item['strength'] ?? ($prod['strength'] ?? ''), 
                        $item['form'] ?? ($prod['form'] ?? ''), 
                        $item['therapeutic'] ?? ($prod['therapeutic'] ?? ''),
                        $prod['hsn_code'] ?? '',
                        $prod['manufacturer'] ?? '',
                        $item['batch_no'] ?? '', 
                        $qtn['validity_date'] ?? null, 
                        $item['qty'], 
                        0, // damaged_qty
                        $item['qty'], // net_qty
                        $item['rate'], 
                        $item['mrp'] ?? ($prod['mrp'] ?? 0.00), 
                        $item['tax_percent'] ?? ($prod['tax_percent'] ?? 12.00), 
                        $item['pack_rate'] ?? ($prod['pack_rate'] ?? 0.00), 
                        $item['individual_rate'] ?? ($prod['individual_rate'] ?? 0.00), 
                        $item['pack'] ?? ($prod['pack'] ?? ''), 
                        $item['unit'] ?? ($prod['unit'] ?? 'Tablet'), 
                        $item['pack_size'] ?? ($prod['pack_size'] ?? 10),
                        $prod['min_stock'] ?? 20,
                        $prod['max_stock'] ?? 500,
                        $prod['rack_location'] ?? '',
                        0, 
                        'Auto-generated from Vendor Portal'
                    ]
                );

                // Update ph_quotations status → 'order sent'
                $db->execute(
                    "UPDATE ph_quotations SET status = 'order sent' WHERE id = ?",
                    [$item['id']]
                );
            }
        }

        echo json_encode(['success' => true, 'message' => "Order $po_no submitted and items registered successfully!"]);
        exit;
    }

    if ($method === 'POST' && $action === 'saveRow') {
        $id = $_POST['id'] ?? '';
        $batch_no = trim($_POST['batch_no'] ?? '');
        if (!$id) throw new Exception("Quotation ID is required.");
        
        try {
            $db->execute("ALTER TABLE ph_quotations ADD COLUMN batch_no VARCHAR(100) NULL");
        } catch (Exception $e) {}
        
        $db->execute("UPDATE ph_quotations SET batch_no = ? WHERE id = ? AND supplier_id = ?", [$batch_no, $id, $_SESSION['vendor_id']]);
        
        // Update product master if specs are provided
        $qtn = $db->fetchOne("SELECT product_id FROM ph_quotations WHERE id = ?", [$id]);
        if ($qtn && !empty($qtn['product_id']) && $qtn['product_id'] !== 'N/A') {
            $db->execute(
                "UPDATE ph_product SET 
                    content = ?, strength = ?, form = ?, therapeutic = ?, 
                    purchase_rate = ?, pack_rate = ?, individual_rate = ?, mrp = ?, tax_percent = ?,
                    pack = ?, unit = ?, pack_size = ?
                 WHERE product_id = ?",
                [
                    $_POST['content'] ?? '',
                    $_POST['strength'] ?? '',
                    $_POST['form'] ?? '',
                    $_POST['therapeutic'] ?? '',
                    (float)($_POST['purchase_rate'] ?? 0),
                    (float)($_POST['pack_rate'] ?? 0),
                    (float)($_POST['individual_rate'] ?? 0),
                    (float)($_POST['mrp'] ?? 0),
                    (float)($_POST['tax_percent'] ?? 0),
                    $_POST['pack'] ?? '',
                    $_POST['unit'] ?? '',
                    $_POST['pack_size'] ?? '',
                    $qtn['product_id']
                ]
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Row details updated successfully']);
        exit;
    }

    throw new Exception("Invalid action or method.");

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
