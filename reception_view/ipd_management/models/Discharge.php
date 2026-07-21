<?php
/**
 * Discharge Model
 * 
 * Manages patient discharge details and summaries
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Discharge extends BaseModel {
    protected $table = 'discharge_details';
    protected $primaryKey = 'sl_no';
    protected $timestamps = true;
    
    /**
     * Get discharge details by admission ID
     */
    public function getByAdmission($admissionId) {
        $query = "SELECT d.* FROM discharge_details d WHERE d.admission_id = ?";
        
        return $this->fetchOne($query, [$admissionId]);
    }
    
    /**
     * Create discharge record
     */
    public function createDischarge($data) {
        $required = ['admission_id', 'discharge_date'];
        $errors = $this->validateRequired($data, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if discharge already exists
        $existing = $this->fetchOne(
            "SELECT sl_no FROM discharge_details WHERE admission_id = ?",
            [$data['admission_id']]
        );
        
        // Format dates
        $dischargeDate = date('Y-m-d', strtotime($data['discharge_date']));
        $followUpDate = !empty($data['follow_up_date']) ? date('Y-m-d', strtotime($data['follow_up_date'])) : null;

        // Map form fields to database columns
        $dbData = [
            'admission_id' => $data['admission_id'],
            'discharge_date' => $dischargeDate,
            'discharge_type' => $data['discharge_type'] ?? null,
            'discharged_by' => $data['discharged_by_doctor_id'] ?? null,
            'summary' => $data['discharge_summary'] ?? null,
            'follow_up_date' => $followUpDate,
            'final_diagnosis' => $data['final_diagnosis'] ?? null,
            'treatment_given' => $data['treatment_given'] ?? null,
            'follow_up_instructions' => $data['follow_up_instructions'] ?? null,
            'medications_prescribed' => $data['medications_prescribed'] ?? null
        ];
        
        if ($existing) {
            // Update existing record
            $this->update($existing['sl_no'], $dbData);
            $dischargeId = $existing['sl_no'];
        } else {
            // Create new record
            $dischargeId = $this->create($dbData);
        }
        
        return ['success' => true, 'discharge_id' => $dischargeId];
    }
    
    /**
     * Generate comprehensive discharge summary
     */
    public function generateSummary($admissionId) {
        // Get admission details
        require_once __DIR__ . '/Admission.php';
        $admissionModel = new Admission();
        $admission = $admissionModel->getByIdWithDetails($admissionId);
        
        if (!$admission) {
            return null;
        }
        
        // Get discharge details
        $discharge = $this->getByAdmission($admissionId);
        
        // Get procedures
        require_once __DIR__ . '/Procedure.php';
        $procedureModel = new Procedure();
        $procedures = $procedureModel->getByAdmission($admissionId);
        
        // Get financial summary
        $financials = $admissionModel->getBalance($admissionId);
        
        // Get charges breakdown
        require_once __DIR__ . '/Charge.php';
        $chargeModel = new Charge();
        $charges = $chargeModel->getByAdmission($admissionId);
        
        // Get payments
        require_once __DIR__ . '/Payment.php';
        $paymentModel = new Payment();
        $payments = $paymentModel->getByAdmission($admissionId);
        
        return [
            'admission' => $admission,
            'discharge' => $discharge,
            'procedures' => $procedures,
            'charges' => $charges,
            'payments' => $payments,
            'financials' => $financials
        ];
    }
}
