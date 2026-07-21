<?php
namespace GM_HMS\Modules\Pharmacy\Services;

use GM_HMS\Modules\Pharmacy\Repositories\BillingRepository;
use Exception;

/**
 * BillingService
 * Orchestrates POS workflows and business rules
 */
class BillingService {
    private $repository;

    public function __construct() {
        $this->repository = new BillingRepository();
    }

    /**
     * Search patients
     */
    public function searchPatients(string $q): array {
        return $this->repository->searchPatients($q);
    }

    /**
     * Search products
     */
    public function searchProducts(string $q): array {
        return $this->repository->searchProducts($q);
    }

    /**
     * Get patient prescriptions
     */
    public function getPatientPrescriptions(string $patientId): array {
        return $this->repository->getPatientPrescriptions($patientId);
    }

    /**
     * Process a new sale
     */
    public function processSale(array $data): array {
        // 1. Validation
        if (empty($data['items'])) {
            throw new Exception("Cannot process empty sale.");
        }

        // 2. Calculate Totals (Server-side validation)
        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = (float)($data['discount_amount'] ?? 0);
        
        foreach ($data['items'] as &$item) {
            $lineSub = (float)$item['rate'] * (int)$item['qty'];
            $lineDisc = $lineSub * ((float)($item['discount_percent'] ?? 0) / 100);
            $taxable = $lineSub - $lineDisc;
            $lineTax = $taxable - ($taxable / (1 + ((float)($item['tax_percent'] ?? 12) / 100)));
            
            $item['tax_amount'] = round($lineTax, 2);
            $item['subtotal']   = round($taxable, 2);
            
            $subtotal += $lineSub;
            $taxTotal += $lineTax;
        }

        $grandTotal = round($subtotal - $discountTotal, 2);
        $paidAmount = (float)($data['paid_amount'] ?? $grandTotal);
        $balance    = round($paidAmount - $grandTotal, 2);

        $invoiceNo = $this->repository->generateInvoiceNo();

        $master = [
            'invoice_no'      => $invoiceNo,
            'customer_id'     => $data['customer_id'] ?? '',
            'customer_name'   => $data['customer_name'] ?? 'Walk-in',
            'customer_age'    => $data['customer_age'] ?? null,
            'customer_sex'    => $data['customer_gender'] ?? null,
            'customer_phone'  => $data['customer_phone'] ?? '',
            'doctor_name'     => $data['doctor_name'] ?? null,
            'patient_type'    => $data['patient_type'] ?? 'WALK-IN',
            'subtotal'        => $subtotal,
            'discount_amount' => $discountTotal,
            'tax_total'       => $taxTotal,
            'grand_total'     => $grandTotal,
            'paid_amount'     => $paidAmount,
            'balance'         => $balance,
            'payment_method'  => $data['payment_method'] ?? 'cash',
            'sponsor'         => $data['sponsor'] ?? null,
            'created_by'      => $_SESSION['username'] ?? $_SESSION['full_name'] ?? ''
        ];

        // 3. Persist
        $payments = $data['payments'] ?? [];
        $this->repository->saveSale($master, $data['items'], $payments);

        return $master;
    }
}
