<?php
/**
 * ============================================================
 * PharmacyExportController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/export/csv
 *    Query: type (products|sales|grn|suppliers)
 *    Returns: CSV file download
 *
 * 2. GET /api/pharmacy/export/print
 *    Query: type, date_from, date_to
 *    Returns: HTML printable view
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyExportController
 * Routes:
 *   GET /api/pharmacy/export/csv?type=      â†’ Export products to CSV
 *   GET /api/pharmacy/export/print?type=    â†’ Get HTML for printing
 */
class PharmacyExportController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/export/csv?type=lowstock|expiry|all */
    public function csv(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $type = $_GET['type'] ?? 'all';
            $sql = "SELECT * FROM ph_product";
            $filename = 'all_products';

            if ($type === 'lowstock') {
                $row_t = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = 'low_stock_threshold'");
                $threshold = (int)($row_t['setting_value'] ?? 20);
                $sql .= " WHERE quantity <= $threshold ORDER BY quantity ASC";
                $filename = 'low_stock_products';
            } elseif ($type === 'expiry') {
                $row_e = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = 'expiry_alert_days'");
                $days = (int)($row_e['setting_value'] ?? 60);
                $sql .= " WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL $days DAY) ORDER BY expiry_date ASC";
                $filename = 'expiry_alert_products';
            } else {
                $sql .= " ORDER BY sl_no ASC";
            }

            $rows = $this->db->fetchAll($sql);

            $date = date('Y-m-d');
            $outFile = $filename . '_' . $date . '.csv';

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $outFile . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // BOM for Excel UTF-8
            echo "\xEF\xBB\xBF";

            $out = fopen('php://output', 'w');

            $headers = [
                'sl_no'          => 'S.No',
                'product_id'     => 'Product ID',
                'product_name'   => 'Product Name',
                'content'        => 'Content / Generic Name',
                'strength'       => 'Strength',
                'form'           => 'Form',
                'therapeutic'    => 'Therapeutic Category',
                'hsn_code'       => 'HSN Code',
                'manufacturer'   => 'Manufacturer',
                'purchase_rate'  => 'Purchase Rate (â‚¹)',
                'pack_rate'      => 'Pack Rate (â‚¹)',
                'individual_rate'=> 'Individual Rate (â‚¹)',
                'mrp'            => 'MRP (â‚¹)',
                'tax_percent'    => 'Tax %',
                'quantity'       => 'Stock Quantity',
                'pack'           => 'Pack Description',
                'unit'           => 'Unit',
                'pack_size'      => 'Pack Size',
                'min_stock'      => 'Min Stock Level',
                'max_stock'      => 'Max Stock Level',
                'rack_location'  => 'Rack / Location',
                'batch_number'   => 'Batch Number',
                'expiry_date'    => 'Expiry Date',
                'last_update'    => 'Last Updated',
            ];

            fputcsv($out, array_values($headers));

            foreach ($rows as $row) {
                $line = [];
                foreach (array_keys($headers) as $col) {
                    $v = $row[$col] ?? '';
                    if ($col === 'expiry_date' && $v && $v !== '0000-00-00') {
                        $v = date('d-M-Y', strtotime($v));
                    }
                    if ($col === 'last_update' && $v) {
                        $v = date('d-M-Y H:i', strtotime($v));
                    }
                    $line[] = $v;
                }
                fputcsv($out, $line);
            }
            fclose($out);
            exit;
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/export/print?type=all */
    public function printView(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $rows = $this->db->fetchAll("SELECT * FROM ph_product ORDER BY sl_no");
            $row_co = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = 'company_name'");
            $co = $row_co['setting_value'] ?? 'GM Pharmacy';

            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html><head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
<meta charset="UTF-8">
            <title>Product Catalogue</title>
            <style>
            body{font-family:Arial,sans-serif;font-size:11px;margin:20px;}
            h2{text-align:center;font-size:16px;margin-bottom:2px;}
            p.sub{text-align:center;font-size:10px;color:#555;margin-bottom:12px;}
            table{width:100%;border-collapse:collapse;}
            th{background:#024D55;color:#fff;padding:5px 6px;font-size:10px;text-align:left;}
            td{padding:4px 6px;border-bottom:1px solid #e5e7eb;font-size:10px;}
            tr:nth-child(even){background:#f9fafb;}
            .badge-danger{background:#fee2e2;color:#dc2626;padding:1px 5px;border-radius:4px;}
            .badge-warn{background:#fef9c3;color:#b45309;padding:1px 5px;border-radius:4px;}
            @media print{button{display:none!important;}}
            </style></head><body>
            <button onclick="window.print()" style="float:right;margin-bottom:10px;padding:6px 14px;background:#024D55;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:11px;">ðŸ–¨ï¸ Print</button>
            <h2>' . htmlspecialchars($co) . ' â€” Product Catalogue</h2>
            <p class="sub">Generated: ' . date('d M Y, h:i A') . ' &nbsp;|&nbsp; Total: ' . count($rows) . ' products</p>
            <table><thead><tr>
            <th>#</th><th>Product ID</th><th>Name</th><th>Form</th><th>Strength</th>
            <th>Manufacturer</th><th>Batch</th><th>Expiry</th><th>Qty</th>
            <th>MRP (â‚¹)</th><th>Pack Rate (â‚¹)</th><th>Location</th>
            </tr></thead><tbody>';
            foreach ($rows as $i => $r) {
                $exp = $r['expiry_date'] && $r['expiry_date'] !== '0000-00-00' ? date('d/m/y', strtotime($r['expiry_date'])) : 'â€”';
                $qty = (int)$r['quantity'];
                $qtyHtml = $qty === 0
                    ? '<span class="badge-danger">Out of Stock</span>'
                    : ($qty <= 20 ? '<span class="badge-warn">' . $qty . '</span>' : $qty);
                echo '<tr>
                    <td>' . ($i+1) . '</td>
                    <td>' . htmlspecialchars($r['product_id']) . '</td>
                    <td><strong>' . htmlspecialchars($r['product_name']) . '</strong></td>
                    <td>' . htmlspecialchars($r['form'] ?? 'â€”') . '</td>
                    <td>' . htmlspecialchars($r['strength'] ?? 'â€”') . '</td>
                    <td>' . htmlspecialchars($r['manufacturer'] ?? 'â€”') . '</td>
                    <td>' . htmlspecialchars($r['batch_number'] ?? 'â€”') . '</td>
                    <td>' . $exp . '</td>
                    <td>' . $qtyHtml . '</td>
                    <td>' . (isset($r['mrp']) ? 'â‚¹'.number_format($r['mrp'],2) : 'â€”') . '</td>
                    <td>' . (isset($r['pack_rate']) ? 'â‚¹'.number_format($r['pack_rate'],2) : 'â€”') . '</td>
                    <td>' . htmlspecialchars($r['rack_location'] ?? 'â€”') . '</td>
                </tr>';
            }
            echo '</tbody></table></body></html>';
            exit;
        } catch (Exception $e) { $this->handleException($e); }
    }
}

