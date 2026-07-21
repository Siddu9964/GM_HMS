<?php
namespace GM_HMS\Modules\Pharmacy\Requests;

use Exception;

/**
 * BillingCreateRequest
 * Validates POS checkout data
 */
class BillingCreateRequest {
    public static function validate(array $data): void {
        if (empty($data['cart'])) {
            throw new Exception("Cart cannot be empty.");
        }
        
        foreach ($data['cart'] as $item) {
            if (empty($item['product_id'])) throw new Exception("Invalid product in cart.");
            if (empty($item['qty']) || $item['qty'] <= 0) throw new Exception("Quantity must be greater than zero.");
            if (!isset($item['rate'])) throw new Exception("Rate is missing for item: " . ($item['product_name'] ?? 'Unknown'));
        }

        if (!isset($data['paid_amount'])) {
            throw new Exception("Paid amount is required.");
        }
    }
}
