<?php
require_once __DIR__ . '/../core/Autoloader.php';
require_once __DIR__ . '/../Database/SecureDatabase.php';
require_once __DIR__ . '/../models/OpdBillingModel.php';

use GM_HMS\Database\SecureDatabase;
use GM_HMS\Models\OpdBillingModel;

// Authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

$billId = $_GET['bill_id'] ?? '';

if (empty($billId)) {
    die("Invalid Parameters");
}

try {
    $billingModel = new OpdBillingModel();
    $bill = $billingModel->getBillDetails($billId);
    
    if (!$bill) {
        die("Bill not found");
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Bill - <?php echo htmlspecialchars($bill['bill_id']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* Header */
        .invoice-header {
            border-bottom: 3px solid #144d34;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .hospital-info {
            text-align: center;
        }
        
        .hospital-name {
            font-size: 28px;
            font-weight: 700;
            color: #144d34;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .hospital-tagline {
            font-size: 14px;
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }
        
        .hospital-address {
            font-size: 13px;
            color: #555;
            line-height: 1.8;
        }
        
        .invoice-title {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: #144d34;
            margin: 20px 0;
            padding: 10px;
            background: #f0f9fa;
            border-radius: 5px;
        }
        
        /* Bill Info Grid */
        .bill-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            font-size: 14px;
            color: #144d34;
            font-weight: 600;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .info-row {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px dotted #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            width: 140px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #333;
            flex-grow: 1;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table thead {
            background: linear-gradient(135deg, #144d34 0%, #078799 100%);
            color: white;
        }
        
        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .items-table tbody tr:hover {
            background: #f0f9fa;
        }
        
        .items-table td {
            padding: 10px 12px;
            font-size: 13px;
        }
        
        .item-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-consultation { background: #e3f2fd; color: #1976d2; }
        .type-investigation { background: #f3e5f5; color: #7b1fa2; }
        .type-procedure { background: #e8f5e9; color: #388e3c; }
        .type-medication { background: #fff3e0; color: #f57c00; }
        .type-other { background: #f5f5f5; color: #616161; }
        
        /* Summary Section */
        .summary-section {
            margin-left: auto;
            width: 350px;
            background: #f9f9f9;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ccc;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            font-weight: 500;
            color: #666;
        }
        
        .summary-value {
            font-weight: 600;
            color: #333;
        }
        
        .summary-total {
            margin-top: 10px;
            padding-top: 15px;
            border-top: 3px solid #144d34;
            font-size: 18px;
        }
        
        .summary-total .summary-label {
            color: #144d34;
            font-weight: 700;
        }
        
        .summary-total .summary-value {
            color: #144d34;
            font-weight: 700;
        }
        
        /* Payment Info */
        .payment-info {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .payment-info h4 {
            color: #2e7d32;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .payment-item {
            display: flex;
            flex-direction: column;
        }
        
        .payment-item-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
        }
        
        .payment-item-value {
            font-size: 15px;
            color: #2e7d32;
            font-weight: 700;
        }
        
        /* Footer */
        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .terms {
            font-size: 11px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .terms h4 {
            font-size: 12px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            width: 200px;
            border-top: 2px solid #333;
            margin: 50px auto 10px;
        }
        
        .signature-label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
        }
        
        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #999;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        /* Print Button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #144d34;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .print-button:hover {
            background: #044d5a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        /* Print Styles */
        @media print {
            body {
                padding: 0;
            }
            
            .print-button {
                display: none;
            }
            
            .items-table tbody tr:hover {
                background: inherit;
            }
        }
    </style>
</head>
<body>
    
    <button class="print-button" onclick="window.print()">
        🖨️ Print Invoice
    </button>
    
    <!-- Header -->
    <div class="invoice-header">
        <div class="hospital-info">
            <div class="hospital-name">GM Hospital & Research Centre</div>
            <div class="hospital-tagline">Excellence in Healthcare</div>
            <div class="hospital-address">
                123 Medical Enclave, Health City, Karnataka - 560001<br>
                Phone: +91 123 456 7890 | Email: info@gmhospital.com<br>
                Website: www.gmhospital.com
            </div>
        </div>
    </div>
    
    <div class="invoice-title">OPD BILL / INVOICE</div>
    
    <!-- Bill Information Grid -->
    <div class="bill-info-grid">
        <div class="info-section">
            <h3>PATIENT INFORMATION</h3>
            <div class="info-row">
                <span class="info-label">Patient Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($bill['patient_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Patient ID:</span>
                <span class="info-value"><?php echo htmlspecialchars($bill['patient_id']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Age / Sex:</span>
                <span class="info-value"><?php echo htmlspecialchars($bill['age'] . ' / ' . $bill['sex']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?php echo htmlspecialchars($bill['phone']); ?></span>
            </div>
        </div>
        
        <div class="info-section">
            <h3>BILL INFORMATION</h3>
            <div class="info-row">
                <span class="info-label">Bill No:</span>
                <span class="info-value"><strong><?php echo htmlspecialchars($bill['bill_id']); ?></strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">Bill Date:</span>
                <span class="info-value"><?php echo date('d-M-Y', strtotime($bill['bill_date'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Bill Time:</span>
                <span class="info-value"><?php echo date('h:i A', strtotime($bill['bill_time'])); ?></span>
            </div>
            <?php if (!empty($bill['doctor_name'])): ?>
            <div class="info-row">
                <span class="info-label">Doctor:</span>
                <span class="info-value"><?php echo htmlspecialchars($bill['doctor_name']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">Type</th>
                <th style="width: 40%;">Description</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 15%;">Rate (₹)</th>
                <th style="width: 15%;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $itemNo = 1;
            foreach ($bill['items'] as $item): 
                $typeClass = 'type-' . strtolower($item['item_type']);
            ?>
            <tr>
                <td><?php echo $itemNo++; ?></td>
                <td>
                    <span class="item-type <?php echo $typeClass; ?>">
                        <?php echo htmlspecialchars($item['item_type']); ?>
                    </span>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                    <?php if (!empty($item['item_description'])): ?>
                    <br><small style="color: #666;"><?php echo htmlspecialchars($item['item_description']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo number_format($item['quantity'], 2); ?></td>
                <td>₹<?php echo number_format($item['unit_price'], 2); ?></td>
                <td>₹<?php echo number_format($item['total_price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-row">
            <span class="summary-label">Subtotal:</span>
            <span class="summary-value">₹<?php echo number_format($bill['subtotal'], 2); ?></span>
        </div>
        
        <?php if ($bill['discount_amount'] > 0): ?>
        <div class="summary-row">
            <span class="summary-label">Discount:</span>
            <span class="summary-value">- ₹<?php echo number_format($bill['discount_amount'], 2); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="summary-row">
            <span class="summary-label">Taxable Amount:</span>
            <span class="summary-value">₹<?php echo number_format($bill['taxable_amount'], 2); ?></span>
        </div>
        
        <div class="summary-row">
            <span class="summary-label">GST (<?php echo number_format($bill['tax_percentage'], 0); ?>%):</span>
            <span class="summary-value">₹<?php echo number_format($bill['tax_amount'], 2); ?></span>
        </div>
        
        <div class="summary-row summary-total">
            <span class="summary-label">GRAND TOTAL:</span>
            <span class="summary-value">₹<?php echo number_format($bill['grand_total'], 2); ?></span>
        </div>
    </div>
    
    <!-- Payment Information -->
    <?php if (!empty($bill['payments']) && count($bill['payments']) > 0): 
        $lastPayment = $bill['payments'][0];
    ?>
    <div class="payment-info">
        <h4>💳 Payment Information</h4>
        <div class="payment-grid">
            <div class="payment-item">
                <span class="payment-item-label">Amount Paid</span>
                <span class="payment-item-value">₹<?php echo number_format($bill['amount_paid'], 2); ?></span>
            </div>
            <div class="payment-item">
                <span class="payment-item-label">Payment Method</span>
                <span class="payment-item-value"><?php echo htmlspecialchars($lastPayment['payment_method']); ?></span>
            </div>
            <div class="payment-item">
                <span class="payment-item-label">Balance Due</span>
                <span class="payment-item-value">₹<?php echo number_format($bill['balance_due'], 2); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="invoice-footer">
        <div class="terms">
            <h4>Terms & Conditions:</h4>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>This is a computer-generated invoice and does not require a signature.</li>
                <li>Payment once made is non-refundable.</li>
                <li>Please retain this invoice for future reference.</li>
                <li>For any queries, please contact our billing department.</li>
            </ul>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Patient Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Authorized Signatory</div>
            </div>
        </div>
        
        <div class="footer-note">
            Generated on <?php echo date('d-M-Y h:i A'); ?> | 
            Served by: <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Reception'); ?><br>
            Thank you for choosing GM Hospital & Research Centre
        </div>
    </div>
    
</body>
</html>
