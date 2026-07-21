<?php
require_once __DIR__ . '/../core/Autoloader.php';
require_once __DIR__ . '/../Database/SecureDatabase.php';

use GM_HMS\Database\SecureDatabase;

// Simple authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

$patientId = $_GET['patient_id'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

if (empty($patientId)) {
    die("Invalid Parameters");
}

try {
    $db = SecureDatabase::getInstance();
    
    // Fetch latest invoice for this patient on this date
    $sql = "SELECT i.*, 
                   p.first_name, p.last_name, p.age, p.sex, p.phone, p.address,
                   d.full_name as doctor_name, d.specialization
            FROM opd_invoice i
            JOIN patient p ON i.patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci
            LEFT JOIN doctors d ON i.doctor_id COLLATE utf8mb4_general_ci = d.doctor_id COLLATE utf8mb4_general_ci
            WHERE i.patient_id = ? AND i.date = ?
            ORDER BY i.created_at DESC 
            LIMIT 1";
            
            
    $invoice = $db->fetchOne($sql, [$patientId, $date]);
    
    // --- FALLBACK: If invoice not found, try to generate it from Appointment ---
    if (!$invoice) {
        $aptSql = "SELECT * FROM appointments 
                   WHERE patient_id = ? AND appointment_date = ? 
                   ORDER BY created_at DESC LIMIT 1";
        $appointment = $db->fetchOne($aptSql, [$patientId, $date]);

        if ($appointment) {
            // Generate Invoice ID
            $prefix = 'INV-OPD';
            $dateStr = date('Ymd');
            $idSql = "SELECT invoice_id FROM opd_invoice WHERE invoice_id LIKE ? ORDER BY invoice_id DESC LIMIT 1";
            $lastRow = $db->fetchOne($idSql, ["$prefix-$dateStr%"]);
            
            if ($lastRow) {
                $parts = explode('-', $lastRow['invoice_id']);
                $newNum = intval(end($parts)) + 1;
            } else {
                $newNum = 1;
            }
            $newInvoiceId = sprintf("%s-%s-%04d", $prefix, $dateStr, $newNum);

            // Determine Status
            $status = ($appointment['payment_mode'] === 'Cash') ? '1' : '0';

            // Insert into opd_invoice
            $insertSql = "INSERT INTO opd_invoice (
                            invoice_id, patient_id, doctor_id, title, 
                            amount, date, payment_method, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $db->execute($insertSql, [
                $newInvoiceId,
                $appointment['patient_id'],
                $appointment['doctor_id'],
                'OPD Consultation',
                $appointment['total_amount'] ?? 0,
                $appointment['appointment_date'],
                $appointment['payment_mode'] ?? 'Cash',
                $status
            ]);

            // Retry Fetch
            $invoice = $db->fetchOne($sql, [$patientId, $date]);
        }
    }

    if (!$invoice) {
        die("Invoice not found for Patient ID: $patientId on Date: $date");
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <title>OPD Receipt - <?php echo htmlspecialchars($invoice['invoice_id']); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #144d34; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #144d34; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 14px; color: #666; }
        
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .invoice-details table { width: 100%; border-collapse: collapse; }
        .invoice-details th { text-align: left; color: #666; font-weight: normal; width: 120px; vertical-align: top; }
        .invoice-details td { font-weight: bold; padding-bottom: 5px; }
        
        .receipt-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .receipt-table th, .receipt-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .receipt-table th { background-color: #f8f9fa; color: #144d34; }
        .amount-row td { font-size: 16px; font-weight: bold; }
        
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        .print-btn { background: #144d34; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; }
        
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div style="text-align: right; margin-bottom: 10px;">
        <button onclick="window.print()" class="print-btn">Print Receipt</button>
    </div>

    <div class="header">
        <h1>GM Hospital & Research Centre</h1>
        <p>123 Medical Enclave, Health City, State - 560001</p>
        <p>Phone: +91 123 456 7890 | Email: info@gmhms.com</p>
    </div>

    <div class="invoice-info">
        <div class="invoice-details" style="flex: 1;">
            <table>
                <tr><th>Patient Name:</th><td><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></td></tr>
                <tr><th>Patient ID:</th><td><?php echo htmlspecialchars($invoice['patient_id']); ?></td></tr>
                <tr><th>Age/Sex:</th><td><?php echo htmlspecialchars($invoice['age'] . ' / ' . $invoice['sex']); ?></td></tr>
                <tr><th>Phone:</th><td><?php echo htmlspecialchars($invoice['phone']); ?></td></tr>
            </table>
        </div>
        <div class="invoice-details" style="flex: 1; text-align: right;">
            <table style="float: right; text-align: left;">
                <tr><th>Invoice No:</th><td><?php echo htmlspecialchars($invoice['invoice_id']); ?></td></tr>
                <tr><th>Date:</th><td><?php echo date('d-M-Y', strtotime($invoice['date'])); ?></td></tr>
                <tr><th>Doctor:</th><td><?php echo htmlspecialchars($invoice['doctor_name']); ?></td></tr>
                <tr><th>Department:</th><td><?php echo htmlspecialchars($invoice['specialization']); ?></td></tr>
            </table>
        </div>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo htmlspecialchars($invoice['title']); ?></td>
                <td style="text-align: right;"><?php echo number_format($invoice['amount'], 2); ?></td>
            </tr>
            <!-- We could add Tax rows here if needed -->
            <tr class="amount-row">
                <td style="text-align: right;">Total Amount</td>
                <td style="text-align: right;"><?php echo number_format($invoice['amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <p><strong>Payment Mode:</strong> <?php echo htmlspecialchars($invoice['payment_method']); ?></p>
        <p><strong>Status:</strong> 
            <?php if($invoice['status'] == '1'): ?>
                <span style="color: green; font-weight: bold;">PAID</span>
            <?php else: ?>
                <span style="color: red; font-weight: bold;">PENDING</span>
            <?php endif; ?>
        </p>
    </div>

    <div class="footer">
        <p>This is a computer-generated receipt and does not require a signature.</p>
        <p>Served by: <?php echo $_SESSION['user_name'] ?? 'Reception'; ?> | Time: <?php echo date('h:i A'); ?></p>
    </div>

    <script>
        // Auto print when opened via pop-up
        if (window.opener) {
            window.print();
        }
    </script>
</body>
</html>
