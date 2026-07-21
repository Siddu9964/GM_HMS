<?php
namespace GM_HMS\Models;

use Exception;
use GM_HMS\Database\SecureDatabase;

class VendorModel
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get pending indents for a specific vendor
     */
    public function getPendingIndents($vendorId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM ph_indent_requests WHERE supplier_id = ? ORDER BY id DESC",
            [$vendorId]
        );
    }

    /**
     * Submit quotations in bulk
     */
    public function submitBulkQuotation($vendorId, $vendorName, $items)
    {
        $date = date('Y-m-d');
        $time = date('H:i:s');
        
        // Get last ID for sequential quotation numbers
        $lastIdRow = $this->db->fetchOne("SELECT MAX(id) as max_id FROM ph_quotations");
        $lastId = $lastIdRow['max_id'] ?? 0;
        
        $results = [];
        $i = 1;
        
        foreach ($items as $item) {
            $unique_qtn_no = "QTN-" . date('Ymd') . "-" . str_pad($lastId + $i, 4, '0', STR_PAD_LEFT);
            
            // Sanitize inputs
            $validity = (!empty($item['validity_date'])) ? $item['validity_date'] : null;
            $qty = floatval($item['qty'] ?? 0);
            $rate = floatval($item['rate'] ?? 0);
            $total = $qty * $rate;
            $product_id = $item['product_id'] ?? 'N/A';
            $item_name = $item['item_name'] ?? 'Unknown Item';
            $indent_no = $item['indent_no'] ?? 'N/A';

            $this->db->execute(
                "INSERT INTO ph_quotations (
                    quotation_no, indent_no, quotation_date, time, validity_date, 
                    supplier_id, supplier_name, product_id, item_name, 
                    qty, rate, tax_percent, tax_amount, total_amount, 
                    delivery_days, remarks, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $unique_qtn_no,
                    $indent_no,
                    $date,
                    $time,
                    $validity,
                    $vendorId,
                    $vendorName,
                    $product_id,
                    $item_name,
                    $qty,
                    $rate,
                    0, // tax_percent
                    0, // tax_amount
                    $total,
                    0, // delivery_days
                    '', // remarks
                    'pending'
                ]
            );
            
            $results[] = $unique_qtn_no;
            $i++;
        }
        
        return $results;
    }
}
