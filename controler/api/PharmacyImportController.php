<?php
/**
 * ============================================================
 * PharmacyImportController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. POST /api/pharmacy/import/products    [multipart/form-data]
 *    File field: file (CSV format)
 *    Expected CSV columns: product_name, category, unit, reorder_level, selling_price
 *    Response: { imported:45, skipped:2, errors:[...] }
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyImportController
 * Routes:
 *   POST /api/pharmacy/import/products  â†’ Import products from JSON data
 */
class PharmacyImportController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** POST /api/pharmacy/import/products */
    public function products(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $body = $this->getJsonInput();
            $rows = $body['rows'] ?? [];

            if (empty($rows)) {
                $this->respondBadRequest('No data received.');
                return;
            }

            $inserted      = 0;
            $updated       = 0;
            $errors        = 0;
            $error_details = [];

            // Allowed DB columns
            $allowed = [
                'product_id','product_name','content','strength','form','therapeutic',
                'hsn_code','manufacturer','purchase_rate','pack_rate','individual_rate',
                'mrp','sales_price','GST_price','total_MRP','tax_no','t_sale_price',
                'total_cost','tax_percent','quantity','pack','unit','pack_size','min_stock',
                'max_stock','rack_location','batch_number','expiry_date','product_image',
                'attachment'
            ];

            // Fetch total count once for product ID auto-generation
            $countRow = $this->db->fetchOne("SELECT COUNT(*) as total FROM ph_product");
            $totalProducts = (int)($countRow['total'] ?? 0);

            // Fetch all existing products for fast memory matching instead of querying per row
            $existingProducts = $this->db->fetchAll("SELECT sl_no, product_id, product_name, batch_number, expiry_date FROM ph_product");
            
            // Build fast lookup maps
            $mapByIdAndBatch = [];
            $mapByNameBatchExp = [];
            foreach ($existingProducts as $ep) {
                $pId = $ep['product_id'];
                $batch = $ep['batch_number'] ?? '';
                $exp = $ep['expiry_date'] ?? '';
                $name = strtolower(trim($ep['product_name']));
                
                $mapByIdAndBatch["{$pId}_{$batch}"] = $ep;
                $mapByNameBatchExp["{$name}_{$batch}_{$exp}"] = $ep;
            }

            // Start bulk transaction for massive speed boost
            $this->db->beginTransaction();

            foreach ($rows as $idx => $row) {
                $rowNum = $idx + 2; // row 1 = header in typical CSV use cases

                // Clean and filter only allowed columns
                $data = [];
                foreach ($row as $k => $v) {
                    $k = trim((string)$k);
                    $v = is_string($v) ? trim($v) : $v;
                    if (in_array($k, $allowed) && $v !== '' && $v !== null) {
                        $data[$k] = (string)$v;
                    }
                }

                // Silently skip completely empty rows (common at the end of Excel files)
                if (empty($data)) {
                    continue;
                }

                // Required fields check: Product Name is MANDATORY
                if (empty($data['product_name'])) {
                    $errors++;
                    $error_details[] = "Row $rowNum: Missing product name â€” skipped.";
                    continue;
                }

                // AUTO-GENERATE Product ID if missing
                if (empty($data['product_id'])) {
                    $data['product_id'] = 'PRD-' . (1000 + $totalProducts + $inserted + $updated + $errors); // Use dynamic offset
                }

                // Sanitize quantity (handle "30 units", "30.00", etc.)
                if (isset($data['quantity'])) {
                    // First convert to float to handle decimals like "30.00", then to int
                    $cleanVal = preg_replace('/[^0-9.]/', '', (string)$data['quantity']);
                    $data['quantity'] = (int)floor((float)$cleanVal);
                }

                // Sanitize date (handle "13 days", "3 months", or normal dates)
                if (!empty($data['expiry_date'])) {
                    $val = strtolower($data['expiry_date']);
                    if (strpos($val, 'day') !== false) {
                        $days = (int)preg_replace('/[^0-9]/', '', $val);
                        $data['expiry_date'] = date('Y-m-d', strtotime("+$days days"));
                    } elseif (strpos($val, 'month') !== false) {
                        $months = (int)preg_replace('/[^0-9]/', '', $val);
                        $data['expiry_date'] = date('Y-m-d', strtotime("+$months months"));
                    } else {
                        $val = trim($data['expiry_date']);
                        // Handle MM/YYYY or MM-YYYY
                        if (preg_match('/^(\d{1,2})[\/\-](\d{4})$/', $val, $matches)) {
                            $month = $matches[1];
                            $year  = $matches[2];
                            // Default to last day of the month
                            $data['expiry_date'] = date('Y-m-t', strtotime("$year-$month-01"));
                        } else {
                            // PHP date_create treats xx/xx/xxxx as MM/DD/YYYY. 
                            // By replacing '/' with '-', it treats it as DD-MM-YYYY (or YYYY-MM-DD).
                            $dateStr = str_replace('/', '-', $val);
                            $d = date_create($dateStr);
                            $data['expiry_date'] = $d ? date_format($d, 'Y-m-d') : null;
                        }
                    }
                    if (!$data['expiry_date']) unset($data['expiry_date']);
                }

                // Sanitize numeric
                foreach (['pack_size','min_stock','max_stock'] as $nf) {
                    if (isset($data[$nf])) {
                        $cleanVal = preg_replace('/[^0-9.]/', '', (string)$data[$nf]);
                        $data[$nf] = (int)floor((float)$cleanVal);
                    }
                }
                foreach (['purchase_rate','pack_rate','individual_rate','mrp','tax_percent'] as $df) {
                    if (isset($data[$df])) {
                        $data[$df] = round((float)preg_replace('/[^0-9.]/', '', (string)$data[$df]), 2);
                    }
                }

                error_log("[IMPORT DEBUG] Row $rowNum: ID={$data['product_id']}, Name={$data['product_name']}, Qty=" . ($data['quantity']??'N/A'));

                try {
                    $batchNum = $data['batch_number'] ?? '';
                    $expDate  = $data['expiry_date'] ?? '';
                    $nameKey  = strtolower(trim($data['product_name']));
                    
                    $exists = null;

                    // Match logic using memory maps instead of DB query
                    if (!empty($data['product_id']) && strpos($data['product_id'], 'PRD-') === false) {
                        $key = "{$data['product_id']}_{$batchNum}";
                        if (isset($mapByIdAndBatch[$key])) {
                            $exists = $mapByIdAndBatch[$key];
                        }
                    } else {
                        // Match by name, batch, and expiry
                        $keyWithExp = "{$nameKey}_{$batchNum}_{$expDate}";
                        $keyNoExp   = "{$nameKey}_{$batchNum}_"; // Fallback if DB exp is null/empty
                        
                        if (isset($mapByNameBatchExp[$keyWithExp])) {
                            $exists = $mapByNameBatchExp[$keyWithExp];
                        } elseif (!$expDate && isset($mapByNameBatchExp[$keyNoExp])) {
                            $exists = $mapByNameBatchExp[$keyNoExp];
                        }
                    }

                    if ($exists) {
                        // UPDATE existing: Increment quantity, overwrite others
                        $p_id  = $exists['product_id'];
                        $qty   = (int)($data['quantity'] ?? 0);
                        unset($data['product_id'], $data['quantity']); 
                        
                        $sets = ["`quantity` = `quantity` + ?"]; 
                        $params = [$qty];
                        
                        foreach ($data as $k => $v) {
                            $sets[] = "`$k` = ?";
                            $params[] = $v;
                        }
                        $params[] = $p_id;
                        $params[] = $batchNum;
                        
                        $this->db->execute("UPDATE ph_product SET " . implode(', ', $sets) . " WHERE product_id = ? AND batch_number = ?", $params);
                        $updated++;
                    } else {
                        // INSERT new row (New batch or new product)
                        // attachment is NOT NULL without default in DB, so set to empty string if missing
                        if (!isset($data['attachment'])) {
                            $data['attachment'] = '';
                        }
                        
                        $cols = '`' . implode('`, `', array_keys($data)) . '`';
                        $phs  = implode(', ', array_fill(0, count($data), '?'));
                        $this->db->execute("INSERT INTO ph_product ($cols) VALUES ($phs)", array_values($data));
                        $inserted++;
                    }
                } catch (Exception $e) {
                    $errors++;
                    $msg = "Row $rowNum ({$data['product_id']}): " . $e->getMessage();
                    $error_details[] = $msg;
                    error_log("[IMPORT ERROR] " . $msg);
                }
            }

            // Commit the bulk transaction
            $this->db->commit();

            $this->respondSuccess([
                'inserted'      => $inserted,
                'updated'       => $updated,
                'errors'        => $errors,
                'error_details' => array_slice($error_details, 0, 20),
                'total'         => count($rows),
            ], 'Import completed.');
        } catch (Exception $e) { $this->handleException($e); }
    }
}

