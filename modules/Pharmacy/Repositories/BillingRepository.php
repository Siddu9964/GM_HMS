<?php
namespace GM_HMS\Modules\Pharmacy\Repositories;

use GM_HMS\Database\SecureDatabase;

/**
 * BillingRepository
 * Handles database operations for pharmacy billing and POS
 */
class BillingRepository {
    private $db;

    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get all sponsors
     */
    public function getAllSponsors(): array {
        return $this->db->fetchAll("SELECT * FROM ph_sponsor ORDER BY name ASC");
    }

    /**
     * Search patients by ID, name, or phone
     */
    public function searchPatients(string $q, int $limit = 15): array {
        $like = '%' . $q . '%';
        return $this->db->fetchAll(
            "SELECT DISTINCT UPPER(p.patient_id) AS patient_id,
                    UPPER(TRIM(CONCAT(COALESCE(p.title,''),' ',COALESCE(p.first_name,''),' ',COALESCE(p.last_name,'')))) AS patient_name,
                    UPPER(p.phone) AS phone, UPPER(COALESCE(p.age,'')) AS age, UPPER(COALESCE(p.sex,'')) AS sex
             FROM patient p
             INNER JOIN consultations c ON p.patient_id = c.patient_id
             WHERE p.patient_id LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.phone LIKE ?
             ORDER BY p.patient_id LIMIT ?",
            [$like, $like, $like, $like, $limit]
        );
    }

    /**
     * Get patient details by ID
     */
    public function getPatientById(string $patientId): ?array {
        return $this->db->fetchOne(
            "SELECT UPPER(patient_id) AS patient_id,
                    UPPER(TRIM(CONCAT(COALESCE(title,''),' ',COALESCE(first_name,''),' ',COALESCE(last_name,'')))) AS patient_name,
                    UPPER(phone) AS phone, UPPER(age) AS age, UPPER(sex) AS sex, UPPER(blood_group) AS blood_group, UPPER(aadhar) AS aadhar, UPPER(address) AS address, UPPER(city) AS city, UPPER(state) AS state
             FROM patient WHERE patient_id = ?",
            [$patientId]
        ) ?: null;
    }

    /**
     * Get patient prescriptions from consultations and prescriptions table
     */
    public function getPatientPrescriptions(string $patientId, int $limit = 5): array {
        // 1. Get from consultations (soap_plan)
        $consultations = $this->db->fetchAll(
            "SELECT c.consultation_id as id,
                    c.consultation_date as date,
                    c.soap_plan as medicines_json,
                    'consultation' as source,
                    COALESCE(d.full_name, c.doctor_id) AS doctor_name
             FROM consultations c
             LEFT JOIN doctors d ON d.doctor_id = c.doctor_id
             WHERE c.patient_id = ? AND c.soap_plan IS NOT NULL AND c.soap_plan != '' AND c.soap_plan != '[]'
             ORDER BY c.consultation_date DESC, c.consultation_time DESC
             LIMIT ?",
            [$patientId, $limit]
        );

        $all = $consultations;

        // Parse JSON
        foreach ($all as &$row) {
            $meds = json_decode($row['medicines_json'], true);
            $row['medicines'] = is_array($meds) ? $meds : [];
            unset($row['medicines_json']);
        }

        return $all;
    }

    /**
     * Search products with stock
     */
    public function searchProducts(string $q, int $limit = 20): array {
        $like = '%' . $q . '%';
        return $this->db->fetchAll(
            "SELECT UPPER(product_id) AS product_id, UPPER(product_name) AS product_name, UPPER(strength) AS strength, UPPER(form) AS form,
                    UPPER(batch_number) AS batch_number, expiry_date, quantity,
                    COALESCE(individual_rate, 0) AS individual_rate,
                    COALESCE(pack_rate, 0)       AS pack_rate,
                    COALESCE(mrp, 0)             AS mrp,
                    COALESCE(tax_percent, 12)    AS tax_percent,
                    COALESCE(pack_size, 1)       AS pack_size,
                    UPPER(COALESCE(unit, 'Tablet')) AS unit,
                    UPPER(COALESCE(hsn_code, ''))   AS hsn_code,
                    UPPER(COALESCE(manufacturer, '')) AS manufacturer,
                    COALESCE(GST_price, tax_percent) AS GST_price
             FROM ph_product
             WHERE (product_name LIKE ? OR product_id LIKE ? OR batch_number LIKE ?)
               AND quantity > 0
             ORDER BY product_name LIMIT ?",
            [$like, $like, $like, $limit]
        );
    }

    /**
     * Generate next invoice number
     */
    public function generateInvoiceNo(): string {
        $row = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(invoice_no, 5) AS UNSIGNED)) AS mx FROM ph_sales_master"
        );
        $next = (($row['mx'] ?? 0) + 1);
        return 'INV-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Save sales record
     */
    public function saveSale(array $master, array $items, array $payments = []): bool {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();

        try {
            // Insert Master
            if ($master['payment_method'] === 'split' && !empty($payments)) {
                foreach ($payments as $payment) {
                    $this->db->execute(
                         "INSERT INTO ph_sales_master
                         (invoice_no, invoice_date, invoice_time, customer_id, customer_name, customer_age, customer_sex, customer_phone, doctor_name, patient_type,
                          subtotal, discount_amount, tax_total, grand_total, paid_amount, balance, payment_method, sponsor, status, created_by)
                         VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)",
                        [
                            $master['invoice_no'],
                            $master['customer_id'] ?? '',
                            $master['customer_name'],
                            $master['customer_age'] ?? null,
                            $master['customer_sex'] ?? null,
                            $master['customer_phone'] ?? '',
                            $master['doctor_name'] ?? null,
                            $master['patient_type'] ?? 'WALK-IN',
                            $master['subtotal'],
                            $master['discount_amount'],
                            $master['tax_total'],
                            $master['grand_total'],
                            $payment['amount'], // the split paid amount
                            $master['balance'],
                            $payment['method'], // the split payment method
                            $master['sponsor'] ?? null,
                            $master['created_by'] ?? ''
                        ]
                    );
                }
            } else {
                $this->db->execute(
                     "INSERT INTO ph_sales_master
                     (invoice_no, invoice_date, invoice_time, customer_id, customer_name, customer_age, customer_sex, customer_phone, doctor_name, patient_type,
                      subtotal, discount_amount, tax_total, grand_total, paid_amount, balance, payment_method, sponsor, status, created_by)
                     VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)",
                    [
                        $master['invoice_no'],
                        $master['customer_id'] ?? '',
                        $master['customer_name'],
                        $master['customer_age'] ?? null,
                        $master['customer_sex'] ?? null,
                        $master['customer_phone'] ?? '',
                        $master['doctor_name'] ?? null,
                        $master['patient_type'] ?? 'WALK-IN',
                        $master['subtotal'],
                        $master['discount_amount'],
                        $master['tax_total'],
                        $master['grand_total'],
                        $master['paid_amount'],
                        $master['balance'],
                        $master['payment_method'],
                        $master['sponsor'] ?? null,
                        $master['created_by'] ?? ''
                    ]
                );
            }

            // Insert Items and Deduct Stock
            foreach ($items as $item) {
                $this->db->execute(
                    "INSERT INTO ph_sales_items
                     (invoice_no, paient_id, product_id, product_name, batch_no, qty, rate,
                      discount_percent, tax_percent, tax_amount, total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $master['invoice_no'],
                        $master['customer_id'] ?? '',
                        $item['product_id'],
                        $item['product_name'],
                        $item['batch_no'] ?? '',
                        $item['qty'],
                        $item['rate'],
                        $item['discount_percent'] ?? 0,
                        $item['tax_percent'] ?? 12,
                        $item['tax_amount'] ?? 0,
                        $item['subtotal']
                    ]
                );

                // Deduct Aggregate Stock
                $this->db->execute(
                    "UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ?",
                    [$item['qty'], $item['product_id']]
                );

                // FIFO Batch Deduction
                $qtyToDeduct = (int)$item['qty'];
                $batches = $this->db->fetchAll(
                    "SELECT id, quantity FROM ph_product_batches 
                     WHERE product_id = ? AND quantity > 0 
                     ORDER BY COALESCE(expiry_date, '2099-12-31') ASC",
                    [$item['product_id']]
                );

                foreach ($batches as $batch) {
                    if ($qtyToDeduct <= 0) break;
                    $batchQty = (int)$batch['quantity'];
                    $deduct = min($qtyToDeduct, $batchQty);
                    
                    $this->db->execute(
                        "UPDATE ph_product_batches SET quantity = quantity - ? WHERE id = ?",
                        [$deduct, $batch['id']]
                    );
                    $qtyToDeduct -= $deduct;
                }
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
