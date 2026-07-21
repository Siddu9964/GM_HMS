<?php
namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class InvoiceModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Get all invoices with optional filters
     * 
     * @param array $filters Filter criteria
     * @return array List of invoices
     */
    public function getAllInvoices($filters = []) {
        $sql = "SELECT p.*, 
                       pa.first_name, 
                       pa.last_name, 
                       pa.phone
                FROM payments p 
                LEFT JOIN patient pa ON p.patient_id COLLATE utf8mb4_unicode_ci = pa.patient_id 
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['payment_method'])) {
            $sql .= " AND i.payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY p.payment_date DESC, p.payment_id DESC";
        
        $invoices = $this->db->fetchAll($sql, $params);
        
        $formattedInvoices = [];
        foreach ($invoices as $row) {
            $formattedInvoices[] = $this->formatInvoiceData($row);
        }
        
        return $formattedInvoices;
    }
    
    /**
     * Get single invoice by ID
     * 
     * @param string $invoiceId Invoice ID
     * @return array|null Invoice data or null if not found
     */
    public function getInvoiceById($invoiceId) {
        $sql = "SELECT i.*, 
                       p.first_name, 
                       p.last_name, 
                       p.phone,
                       d.full_name as doctor_name
                FROM invoice i 
                LEFT JOIN patient p ON i.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id 
                LEFT JOIN doctors d ON i.doctor_id = d.doctor_id
                WHERE i.invoice_id = ?";
        
        $row = $this->db->fetchOne($sql, [$invoiceId]);
        
        if (!$row) {
            return null;
        }
        
        return $this->formatInvoiceData($row);
    }
    
    /**
     * Get invoices by patient ID
     * 
     * @param string $patientId Patient ID
     * @return array List of invoices
     */
    public function getInvoicesByPatient($patientId) {
        $sql = "SELECT i.*, 
                       p.first_name, 
                       p.last_name, 
                       p.phone,
                       d.full_name as doctor_name
                FROM invoice i 
                LEFT JOIN patient p ON i.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id 
                LEFT JOIN doctors d ON i.doctor_id = d.doctor_id
                WHERE i.patient_id = ?
                ORDER BY i.date DESC";
        
        $invoices = $this->db->fetchAll($sql, [$patientId]);
        
        $formattedInvoices = [];
        foreach ($invoices as $row) {
            $formattedInvoices[] = $this->formatInvoiceData($row);
        }
        
        return $formattedInvoices;
    }
    
    /**
     * Create new invoice
     * 
     * @param array $data Invoice data
     * @return string New invoice ID
     */
    public function createInvoice($data) {
        // Generate invoice ID
        $invoiceId = $this->generateInvoiceId();
        
        $sql = "INSERT INTO invoice (
                    invoice_id, patient_id, doctor_id, title, amount,
                    date, status, payment_method
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $invoiceId,
            $data['patient_id'],
            $data['doctor_id'] ?? null,
            $data['title'] ?? 'General Service',
            $data['amount'],
            date('Y-m-d'),
            $data['status'] ?? 'Pending',
            $data['payment_method'] ?? 'Cash'
        ];
        
        $this->db->execute($sql, $params);
        
        return $invoiceId;
    }
    
    /**
     * Update existing invoice
     * 
     * @param string $invoiceId Invoice ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public function updateInvoice($invoiceId, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = [
            'patient_id', 'doctor_id', 'title', 'amount',
            'status', 'payment_method'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            throw new Exception('No fields to update');
        }
        
        $params[] = $invoiceId;
        
        $sql = "UPDATE invoice SET " . implode(', ', $fields) . " WHERE invoice_id = ?";
        
        $this->db->execute($sql, $params);
        
        return true;
    }
    
    /**
     * Delete invoice
     * 
     * @param string $invoiceId Invoice ID
     * @return bool Success status
     */
    public function deleteInvoice($invoiceId) {
        $sql = "DELETE FROM invoice WHERE invoice_id = ?";
        
        $this->db->execute($sql, [$invoiceId]);
        
        return true;
    }
    
    /**
     * Get billing statistics
     * 
     * @return array Statistics data
     */
    public function getStatistics() {
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');
        
        $stats = [];
        
        // Today's Revenue
        $result = $this->db->fetchOne(
            "SELECT SUM(amount) as total FROM payments WHERE payment_date = ?",
            [$today]
        );
        $stats['today_revenue'] = $result['total'] ?? 0;
        
        // Monthly Revenue
        $result = $this->db->fetchOne(
            "SELECT SUM(amount) as total FROM payments WHERE payment_date >= ?",
            [$startOfMonth]
        );
        $stats['monthly_revenue'] = $result['total'] ?? 0;
        
        // Pending Bills Count (Estimated from appointments or other sources if needed)
        $stats['pending_bills'] = 0; 
        
        // Total Invoices/Payments
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM payments"
        );
        $stats['total_invoices'] = $result['cnt'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Search patients for invoice creation
     * 
     * @param string $term Search term
     * @return array List of patients
     */
    public function searchPatients($term) {
        $sql = "SELECT 
                    patient_id as id, 
                    CONCAT(first_name, ' ', last_name, ' (', patient_id, ')') as text,
                    first_name, last_name, birth_date, age, blood_group, phone, sex as gender
                FROM patient 
                WHERE patient_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? 
                LIMIT 20";
        
        $param = "%$term%";
        return $this->db->fetchAll($sql, [$param, $param, $param]);
    }
    
    /**
     * Search doctors for invoice creation
     * 
     * @param string $term Search term
     * @return array List of doctors
     */
    public function searchDoctors($term) {
        $sql = "SELECT 
                    doctor_id as id, 
                    CONCAT(full_name, ' - ', specialization) as text,
                    full_name, specialization, consultation_fee, 
                    room_number, shift_type, available_days, in_time, out_time
                FROM doctors 
                WHERE full_name LIKE ? OR specialization LIKE ? 
                LIMIT 20";
        
        $param = "%$term%";
        return $this->db->fetchAll($sql, [$param, $param]);
    }
    
    /**
     * Format invoice data for API response
     * 
     * @param array $row Database row
     * @return array Formatted invoice data
     */
    private function formatInvoiceData($row) {
        return [
            'invoice_id' => $row['invoice_id'],
            'patient_id' => $row['patient_id'] ?? null,
            'patient_name' => isset($row['first_name']) && isset($row['last_name']) 
                ? trim($row['first_name'] . ' ' . $row['last_name']) 
                : null,
            'patient_phone' => $row['phone'] ?? null,
            'doctor_id' => $row['doctor_id'] ?? null,
            'doctor_name' => $row['doctor_name'] ?? null,
            'title' => $row['title'] ?? null,
            'amount' => $row['amount'] ?? 0,
            'date' => $row['date'] ?? null,
            'status' => $row['status'] ?? 'Pending',
            'payment_method' => $row['payment_method'] ?? 'Cash'
        ];
    }
    
    /**
     * Get today's revenue from invoices
     * 
     * @return float Today's total revenue
     */
    public function getTodayRevenue() {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as revenue 
                    FROM opd_invoice 
                    WHERE DATE(date) = CURDATE()";
            $result = $this->db->fetchOne($sql);
            return (float)($result['revenue'] ?? 0);
        } catch (\Exception $e) {
            error_log("InvoiceModel::getTodayRevenue Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Generate unique invoice ID
     * 
     * @return string Invoice ID
     */
    private function generateInvoiceId() {
        $prefix = 'INV';
        $date = date('Ymd');
        
        // Get last invoice ID for today
        $sql = "SELECT invoice_id FROM invoice 
                WHERE invoice_id LIKE ? 
                ORDER BY invoice_id DESC LIMIT 1";
        
        $row = $this->db->fetchOne($sql, [$prefix . '-' . $date . '%']);
        
        if ($row) {
            $lastId = $row['invoice_id'];
            $number = intval(substr($lastId, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . '-' . $date . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
