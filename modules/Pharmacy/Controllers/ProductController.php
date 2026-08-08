<?php
namespace GM_HMS\Modules\Pharmacy\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Pharmacy\Repositories\ProductRepository;

/**
 * ProductController
 * Handles Product CRUD API requests
 */
class ProductController extends BaseController {
    private $repository;

    public function __construct() {
        parent::__construct();
        $this->repository = new ProductRepository();
    }

    /** GET /api/pharmacy/products */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $filters = [
                'search'      => $_GET['search'] ?? '',
                'form'        => $_GET['form'] ?? '',
                'therapeutic' => $_GET['therapeutic'] ?? ''
            ];
            $products = $this->repository->list($filters);
            $this->respondSuccess($products);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/products */
    public function create(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            unset($data['action']);
            unset($data['sl_no']);
            // TODO: Add Request Validation
            $id = $this->repository->create($data);
            $this->respondCreated(['sl_no' => $id]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** PUT /api/pharmacy/products/{sl_no} */
    public function update(string $slNo): void {
        $this->restrictMethod(['PUT', 'POST']); // Allow POST if sent via _method=PUT
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            unset($data['action']);
            unset($data['sl_no']);
            $this->repository->update((int)$slNo, $data);
            $this->respondSuccess(null, "Product updated.");
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** DELETE /api/pharmacy/products/{sl_no} */
    public function delete(string $slNo): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $this->repository->delete((int)$slNo);
            $this->respondSuccess(null, "Product deleted.");
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/products/autocomplete */
    public function autoComplete(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            if (empty($data['product_name'])) {
                throw new Exception("Product name is required.");
            }
            
            require_once __DIR__ . '/../../../config/gemini_config.php';
            
            $productName = $data['product_name'];
            $prompt = "You are a medical data assistant. For the following commercial medical product name: '{$productName}', provide the full detailed generic composition (e.g. Paracetamol IP 500mg + Caffeine 50mg), strength (e.g. 500mg), formulation (e.g. Tablet, Syrup, Injection), therapeutic class (e.g. Analgesic, Antibiotic), and standard pack details (e.g. 10x10 Strips, 100ml Bottle). Return ONLY a JSON object with keys: 'content', 'strength', 'form', 'therapeutic', 'pack'. Do not wrap it in markdown or add any extra text.";
            
            $payload = [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature' => GEMINI_TEMPERATURE,
                    'responseMimeType' => 'application/json'
                ]
            ];
            
            $ch = curl_init(getGeminiApiUrl());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, GEMINI_TIMEOUT);
            
            $response = curl_exec($ch);
            if(curl_errno($ch)){
                throw new Exception("Gemini API Error: " . curl_error($ch));
            }
            curl_close($ch);
            
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $jsonStr = $result['candidates'][0]['content']['parts'][0]['text'];
                $parsed = json_decode($jsonStr, true);
                if ($parsed) {
                    $this->respondSuccess($parsed, "Fetched details via AI.");
                    return;
                }
            }
            
            throw new Exception("Could not parse AI response.");
        } catch (Exception $e) { $this->handleException($e); }
    }
}
