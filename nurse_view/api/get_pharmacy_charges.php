<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admissionId = $_GET['admission_id'] ?? '';

if (empty($admissionId)) {
    echo json_encode(['success' => false, 'message' => 'Missing admission ID']);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    
    // Fetch from ipd_clinical_records
    $sql = "SELECT id, record_date, pharmacy_orders 
            FROM ipd_clinical_records 
            WHERE admission_id = ? 
            ORDER BY record_date ASC";
            
    $results = $db->fetchAll($sql, [$admissionId]);
    
    // Build a map of actual billed rates from ipd_billing_items
    $billingRates = [];
    $billingSql = "SELECT items_json FROM ipd_billing_items WHERE admission_id = ? AND charge_type = 'PHARMACY' AND status != 'CANCELLED'";
    $billRows = $db->fetchAll($billingSql, [$admissionId]);
    foreach ($billRows as $brow) {
        if (empty($brow['items_json'])) continue;
        $bitems = json_decode($brow['items_json'], true);
        if (is_array($bitems)) {
            foreach ($bitems as $bitem) {
                $pid = $bitem['test_id'] ?? '';
                $rate = (float)($bitem['unit_price'] ?? 0);
                if ($pid && $rate > 0) {
                    $billingRates[$pid] = $rate;
                }
            }
        }
    }

    $charges = [];
    foreach ($results as $row) {
        if (empty($row['pharmacy_orders'])) continue;
        
        $orders = json_decode($row['pharmacy_orders'], true);
        if (!is_array($orders)) continue;
        
        foreach ($orders as $idx => $orderBlock) {
            $data = $orderBlock['data'] ?? [];
            if (empty($data)) continue;
            
            // Generate a unique virtual item_id for UI tracking if not present
            $virtualItemId = $row['id'] . '_' . $idx;
            
            $productId = $data['product_id'] ?? $data['id'] ?? '';
            // Get actual billed rate if available, fallback to indent mrp/rate
            $unitPrice = $billingRates[$productId] ?? (float)($data['unit_price'] ?? $data['rate'] ?? $data['mrp'] ?? 0);
            
            $charges[] = [
                'item_id' => $data['item_id'] ?? $virtualItemId,
                'charge_date' => $row['record_date'],
                'product_name' => $data['product_name'] ?? $data['name'] ?? 'Unknown Medicine',
                'product_id' => $productId,
                'batch_no' => $data['batch'] ?? $data['batch_no'] ?? '',
                'quantity' => (float)($data['qty'] ?? $data['quantity'] ?? 1),
                'returned_qty' => (float)($data['returned_qty'] ?? 0),
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * (float)($data['qty'] ?? $data['quantity'] ?? 1)
            ];
        }
    }
    
    // Fetch the bill_id and patient_id associated with this admission
    $adm = $db->fetchOne("SELECT patient_id, bill_id FROM ipd_billing_master WHERE admission_id = ? AND billing_status != 'CANCELLED'", [$admissionId]);
    
    // If master doesn't exist, just get patient_id from admissions
    if (!$adm) {
        $adm = $db->fetchOne("SELECT patient_id, NULL as bill_id FROM ipd_admissions WHERE admission_id = ?", [$admissionId]);
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $charges,
        'patient_id' => $adm ? $adm['patient_id'] : '',
        'bill_id' => $adm ? $adm['bill_id'] : ''
    ]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
