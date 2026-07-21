<?php
/**
 * Professional Bill Print Template (Partial)
 * This file returns the HTML content for a professional hospital invoice.
 * Used for the Print Preview modal in appointment_bill.php
 */

require_once __DIR__ . '/../../models/Database.php';
$db = new Database();
$db->connect();

$billId = $_GET['bill_id'] ?? '';
if (!$billId) {
    echo "<div style='text-align:center; padding: 2rem; color: #ef4444;'>No Bill ID specified.</div>";
    exit;
}

try {
    // Fetch Bill Details
    $bill = $db->fetchOne("
        SELECT obm.*, p.first_name, p.last_name, p.phone as patient_phone, p.age, p.sex,
               d.full_name as doctor_name, d.specialization
        FROM opd_billing_master obm
        LEFT JOIN patient p ON obm.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id
        LEFT JOIN doctors d ON obm.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id
        WHERE obm.bill_id = ?
    ", [$billId]);

    if (!$bill) {
        // Try fallback with appointments table if patient not in master patient table
        $bill = $db->fetchOne("
            SELECT obm.*, a.patient_name as first_name, '' as last_name, a.phone as patient_phone, 
                   '' as age, '' as sex, d.full_name as doctor_name, d.specialization
            FROM opd_billing_master obm
            LEFT JOIN appointments a ON obm.appointment_id COLLATE utf8mb4_unicode_ci = a.appointment_id
            LEFT JOIN doctors d ON obm.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id
            WHERE obm.bill_id = ?
        ", [$billId]);
    }

    if (!$bill) {
        throw new Exception("Bill not found: " . $billId);
    }

    // Fetch Bill Items
    $items = $db->fetchAll("SELECT * FROM opd_billing_items WHERE bill_id = ?", [$billId]);
    
    // Fetch Payments
    $payments = $db->fetchAll("SELECT * FROM payment_receipts WHERE bill_id = ? ORDER BY payment_date DESC", [$billId]);

    $patientName = trim(($bill['first_name'] ?? '') . ' ' . ($bill['last_name'] ?? ''));
    if (empty($patientName)) $patientName = $bill['patient_name'] ?? 'Unknown Patient';

} catch (Exception $e) {
    echo "<div style='text-align:center; padding: 2rem; color: #ef4444;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Format numbers
function fmt($n) { return number_format((float)$n, 2, '.', ','); }
?>

<div class="professional-invoice" style="font-family: 'Inter', sans-serif; color: #1e293b; line-height: 1.5;">
    <!-- Invoice Header -->
    <div style="display: flex; justify-content: space-between; border-bottom: 3px solid #1f6b4a; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h1 style="margin: 0; color: #1f6b4a; font-size: 28px; font-weight: 800;">GM HOSPITAL</h1>
            <p style="margin: 5px 0 0; color: #64748b; font-size: 13px;">
                Advanced Medical Care & Research Center<br>
                Papreddy Palya, 2nd Stage, Naagarabhaavi<br>
                Bengaluru, Karnataka 560072
            </p>
        </div>
        <div style="text-align: right;">
            <h2 style="margin: 0; font-size: 22px; color: #1e293b; font-weight: 700;">INVOICE</h2>
            <p style="margin: 5px 0 0; font-weight: 600; color: #1f6b4a;">#<?php echo htmlspecialchars($bill['bill_id']); ?></p>
            <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;"><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></p>
        </div>
    </div>

    <!-- Patient & Info Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
        <div>
            <h3 style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 10px;">Billed To</h3>
            <p style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($patientName); ?></p>
            <p style="margin: 5px 0 0; color: #64748b; font-size: 14px;">
                Patient ID: <?php echo htmlspecialchars($bill['patient_id']); ?><br>
                <?php if (!empty($bill['patient_phone'])) echo "Phone: " . htmlspecialchars($bill['patient_phone']) . "<br>"; ?>
                <?php if (!empty($bill['age'])) echo "Age/Sex: " . htmlspecialchars($bill['age']) . "Y / " . htmlspecialchars($bill['sex']); ?>
            </p>
        </div>
        <div style="text-align: right;">
            <h3 style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 10px;">Consulting Doctor</h3>
            <p style="margin: 0; font-size: 14px; font-weight: 700; color: #0f172a;">DR. <?php echo htmlspecialchars(strtoupper($bill['doctor_name'] ?? 'General Physician')); ?></p>
            <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;"><?php echo htmlspecialchars($bill['specialization'] ?? 'Department of Health'); ?></p>
        </div>
    </div>

    <!-- Items Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 12px; text-align: left; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Service Description</th>
                <th style="padding: 12px; text-align: center; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Qty</th>
                <th style="padding: 12px; text-align: right; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Rate</th>
                <th style="padding: 12px; text-align: right; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px 12px;">
                    <span style="display: block; font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($item['item_name']); ?></span>
                    <?php if (!empty($item['item_description'])): ?>
                    <span style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($item['item_description']); ?></span>
                    <?php endif; ?>
                </td>
                <td style="padding: 15px 12px; text-align: center; color: #475569; font-weight: 500;"><?php echo $item['quantity']; ?></td>
                <td style="padding: 15px 12px; text-align: right; color: #475569; font-weight: 500;">₹<?php echo fmt($item['unit_price']); ?></td>
                <td style="padding: 15px 12px; text-align: right; color: #0f172a; font-weight: 700;">₹<?php echo fmt($item['total_price']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals & Payment -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div style="max-width: 300px;">
            <div style="background: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; border-radius: 4px;">
                <h4 style="margin: 0 0 5px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #166534;">Payment Method</h4>
                <p style="margin: 0; font-size: 14px; font-weight: 700; color: #14532d;"><?php echo strtoupper($bill['payment_mode'] ?? 'CASH'); ?></p>
            </div>
            <?php if (!empty($bill['notes'])): ?>
            <p style="margin-top: 15px; font-size: 12px; color: #94a3b8; font-style: italic;">
                Note: <?php echo htmlspecialchars($bill['notes']); ?>
            </p>
            <?php endif; ?>
        </div>
        <div style="width: 250px;">
            <div style="display: flex; justify-content: space-between; padding: 5px 0; color: #64748b; font-size: 14px;">
                <span>Subtotal</span>
                <span>₹<?php echo fmt($bill['subtotal']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 5px 0; color: #64748b; font-size: 14px;">
                <span>Discount</span>
                <span>-₹<?php echo fmt($bill['discount_amount']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 5px 0; color: #64748b; font-size: 14px;">
                <span>Tax</span>
                <span>₹<?php echo fmt($bill['tax_amount']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 15px 0; margin-top: 10px; border-top: 2px solid #1f6b4a; color: #1f6b4a; font-size: 18px; font-weight: 800;">
                <span>Total</span>
                <span>₹<?php echo fmt($bill['grand_total']); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 5px 0; color: #16a34a; font-weight: 700;">
                <span>Amount Paid</span>
                <span>₹<?php echo fmt($bill['amount_paid']); ?></span>
            </div>
            <?php if ($bill['balance_due'] > 0): ?>
            <div style="display: flex; justify-content: space-between; padding: 5px 0; color: #ef4444; font-weight: 700; border-top: 1px dashed #e2e8f0;">
                <span>Balance Due</span>
                <span>₹<?php echo fmt($bill['balance_due']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer Information -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: space-between; align-items: flex-end;">
        <div style="text-align: left; color: #64748b; font-size: 11px; line-height: 1.6;">
            <div>Printed on: <b style="color: #1e293b;"><?php echo date('d M Y, h:i a'); ?></b></div>
            <div>Printed by: <b style="color: #1e293b;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'HMS Administrator'); ?></b></div>
            <div style="margin-top: 8px;">Thank you for choosing GM Hospital.</div>
            <div>This is a computer-generated bill and does not require a signature.</div>
        </div>
        <div style="text-align: center;">
            <div style="width: 140px; border-top: 1.5px solid #1e293b; margin-bottom: 5px;"></div>
            <span style="font-size: 11px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">Authorised Signatory</span>
        </div>
    </div>
</div>
