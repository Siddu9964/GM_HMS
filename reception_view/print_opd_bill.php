<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../receptionist_login.php");
    exit();
}
$billId    = htmlspecialchars($_POST['bill_id'] ?? $_GET['bill_id'] ?? '');
$receiptId = htmlspecialchars($_POST['receipt_id'] ?? $_GET['receipt_id'] ?? '');

// If we only have a receipt ID, lookup the Bill ID first
if ($receiptId && !$billId) {
    try {
        require_once __DIR__ . '/../models/Database.php';
        $db = new Database();
        $db->connect();
        $rcp = $db->fetchOne("SELECT bill_id FROM payment_receipts WHERE receipt_id = ?", [$receiptId]);
        if ($rcp) {
            $billId = $rcp['bill_id'];
        }
    } catch (Exception $e) {
        // Fallback or error handled in JS loading
    }
}

if (!$billId && !$receiptId) { echo "No billing identifiers provided."; exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Bill — <?= $billId ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; font-size: 13px; }

        .print-page {
            width: 210mm; min-height: 297mm;
            margin: 20px auto; background: white;
            padding: 28px 32px; position: relative;
        }

        /* Header */
        .bill-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 18px; border-bottom: 2.5px solid #1f6b4a; margin-bottom: 20px; }
        .hospital-info h1 { font-size: 22px; font-weight: 700; color: #1f6b4a; letter-spacing: -.3px; }
        .hospital-info p  { font-size: 11px; color: #64748b; margin-top: 2px; }
        .bill-meta { text-align: right; }
        .bill-meta h2 { font-size: 18px; font-weight: 700; color: #1e293b; letter-spacing: 1px; text-transform: uppercase; }
        .bill-meta .bid { font-size: 13px; font-weight: 600; color: #1f6b4a; margin-top: 4px; }
        .bill-meta .bdate { font-size: 11px; color: #64748b; margin-top: 2px; }

        /* Patient Info */
        .patient-section {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 8px 24px; background: #f8fafc;
            border-radius: 8px; padding: 14px 18px;
            margin-bottom: 20px; border: 1px solid #e2e8f0;
        }
        .patient-section .info-row { display: flex; gap: 6px; align-items: baseline; }
        .patient-section .info-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #94a3b8; min-width: 80px; }
        .patient-section .info-val   { font-size: 12px; font-weight: 500; color: #1e293b; }

        /* Items Table */
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-bottom: 8px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items thead th {
            background: #1f6b4a; color: white;
            padding: 7px 10px; text-align: left;
            font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px;
        }
        table.items thead th:last-child { text-align: right; }
        table.items tbody td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
        table.items tbody td:last-child { text-align: right; font-weight: 500; }
        table.items tbody tr:nth-child(even) { background: #f8fafc; }
        table.items tfoot td { padding: 6px 10px; font-size: 12px; }
        .type-badge {
            display: inline-block; padding: 1px 7px; border-radius: 999px;
            font-size: 10px; font-weight: 600; background: rgba(31, 107, 74,.1); color: #1f6b4a;
        }

        /* Totals */
        .totals-section { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .totals-box { width: 260px; }
        .total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; color: #475569; }
        .total-row.grand { border-top: 2px solid #1f6b4a; margin-top: 6px; padding-top: 8px; font-size: 15px; font-weight: 700; color: #1f6b4a; }
        .total-row.paid  { color: #16a34a; font-weight: 600; }
        .total-row.balance { color: #dc2626; font-weight: 600; }

        /* Payment Info */
        .payment-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 20px; display: flex; gap: 24px; }
        .payment-box .pitem label { font-size: 9px; text-transform: uppercase; letter-spacing: .4px; color: #64748b; display: block; }
        .payment-box .pitem span  { font-size: 12px; font-weight: 600; color: #16a34a; }

        /* Footer */
        .bill-footer { border-top: 1px dashed #e2e8f0; padding-top: 14px; display: flex; justify-content: space-between; align-items: flex-end; margin-top: auto; }
        .footer-note { font-size: 10px; color: #94a3b8; line-height: 1.6; }
        .sign-box { text-align: center; }
        .sign-box .sign-line { width: 120px; border-top: 1px solid #1e293b; margin-bottom: 4px; }
        .sign-box span { font-size: 10px; color: #64748b; }

        .status-stamp {
            position: absolute; top: 120px; right: 40px;
            font-size: 32px; font-weight: 900; text-transform: uppercase;
            letter-spacing: 4px; opacity: .08; transform: rotate(-20deg);
            pointer-events: none;
        }
        .stamp-paid    { color: #16a34a; }
        .stamp-pending { color: #dc2626; }

        /* Loading */
        .loading { text-align: center; padding: 80px; color: #94a3b8; }
        .loading i { font-size: 2.5rem; animation: spin .8s linear infinite; display: block; margin-bottom: 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Print */
        .no-print { background: white; padding: 12px 20px; display: flex; gap: 10px; justify-content: center; position: fixed; bottom: 0; left: 0; right: 0; box-shadow: 0 -4px 16px rgba(0,0,0,.1); z-index: 100; }
        .btn-print { background: linear-gradient(135deg,#1f6b4a,#144d34); color: white; border: none; padding: 9px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 8px; }
        .btn-close  { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 9px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; }

        @media print {
            body { background: white; }
            .print-page { margin: 0; padding: 16mm 18mm; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div id="billWrapper">
    <div class="loading">
        <i class="fas fa-spinner"></i>
        <p>Loading bill...</p>
    </div>
</div>

<!-- Print Controls -->
<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Print Bill</button>
    <button class="btn-close" onclick="window.close()">✕ Close</button>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
const BILL_ID    = '<?= $billId ?>';
const RECEIPT_ID = '<?= $receiptId ?>';
const PRINTED_BY = '<?= htmlspecialchars($_SESSION["full_name"] ?? $_SESSION["username"] ?? $_SESSION["user_id"] ?? "System") ?>';
const HOSPITAL_BRANCH = '<?= addslashes($_SESSION["hospital_branch"] ?? "Nagarabhavi") ?>';

async function loadBill() {
    try {
        const res  = await fetch(`/GM_HMS/api/index.php/api/billing/opd/${encodeURIComponent(BILL_ID)}`, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Not found');
        renderBill(json.data);
    } catch (e) {
        document.getElementById('billWrapper').innerHTML =
            `<div class="print-page" style="text-align:center;padding:60px;"><h2 style="color:#dc2626;">Error loading bill</h2><p style="color:#64748b;margin-top:8px;">${e.message}</p></div>`;
    }
}

function fmt(n) { return parseFloat(n||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) : '—'; }
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function numberToWords(amount) {
    const ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                  'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                  'Seventeen','Eighteen','Nineteen'];
    const tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    function convert(n) {
        if (n === 0) return '';
        if (n < 20)  return ones[n];
        if (n < 100) return tens[Math.floor(n/10)] + (n%10 ? ' '+ones[n%10] : '');
        if (n < 1000)    return ones[Math.floor(n/100)]   + ' Hundred'  + (n%100    ? ' '+convert(n%100)    : '');
        if (n < 100000)  return convert(Math.floor(n/1000))  + ' Thousand' + (n%1000   ? ' '+convert(n%1000)   : '');
        if (n < 10000000)return convert(Math.floor(n/100000)) + ' Lakh'    + (n%100000 ? ' '+convert(n%100000) : '');
        return convert(Math.floor(n/10000000)) + ' Crore' + (n%10000000 ? ' '+convert(n%10000000) : '');
    }
    const rupees = Math.floor(amount);
    const paise  = Math.round((amount - rupees) * 100);
    if (rupees === 0 && paise === 0) return 'Zero Rupees Only';
    let result = rupees > 0 ? convert(rupees) + ' Rupees' : '';
    if (paise  > 0) result += (result ? ' and ' : '') + convert(paise) + ' Paise';
    return result + ' Only';
}

function renderBill(b) {
    const items  = b.items   || [];
    let pmts     = b.payments|| [];
    
    // Filter by receipt if requested
    if (RECEIPT_ID && pmts.length > 0) {
        pmts = pmts.filter(p => p.receipt_id === RECEIPT_ID);
    }

    const isReceiptPrint = RECEIPT_ID && pmts.length > 0;
    const status = (b.payment_status||'Pending');
    const stampClass = status.toLowerCase() === 'paid' ? 'stamp-paid' : 'stamp-pending';

    const itemRows = items.map((it, i) => `
        <tr>
            <td style="color:#94a3b8;font-size:11px;">${i+1}</td>
            <td>${esc(it.item_name)}${it.item_description?`<br><span style="color:#94a3b8;font-size:10px;">${esc(it.item_description)}</span>`:''}</td>
            <td style="text-align:center;">${it.quantity}</td>
            <td style="text-align:right;">₹${fmt(it.unit_price)}</td>
            <td>₹${fmt(it.discount_amount||0)}</td>
            <td>₹${fmt(it.total_price)}</td>
        </tr>`).join('');

    const pmtRows = pmts.map((p, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${esc(b.receipt_no || '—')}</td>
            <td>${fmtDate(p.payment_date)}</td>
            <td>${esc(p.payment_method || 'Cash')}</td>
            <td style="text-align:right;font-weight:600;">₹${fmt(p.amount)}</td>
        </tr>`).join('');

    document.getElementById('billWrapper').innerHTML = `
    <div class="print-page">
        <div class="status-stamp ${stampClass}">${status}</div>

        <!-- Header -->
        <div class="bill-header">
            <div class="hospital-info">
                ${(HOSPITAL_BRANCH || '').toLowerCase() === 'basaveshwaranagar' ? `
                    <h1>GM HOSPITAL (Basaveshwar Nagar)</h1>
                    <p>No. 335, 3rd Stage, 4th Block, Siddaiah Puranik Road,</p>
                    <p>Basaveshwara nagar, Bengaluru 560079</p>
                    <p>Tel. No 0802221160 Mob. No 9900003527</p>
                    <p>GST NO: 29AAFCP8756N3ZE</p>
                ` : `
                    <h1>GM Hospital</h1>
                    <p>612, Nagarabhavi Main Rd, Vinayaka Layout,</p>
                    <p>Papreddy Palya, 2nd Stage, Naagarabhaavi,</p>
                    <p>Bengaluru, Karnataka 560072</p>
                    <p>OPD Billing Department</p>
                `}
            </div>
            <div class="bill-meta">
                <h2>${isReceiptPrint ? 'Payment Receipt' : 'OPD Invoice'}</h2>
                <div class="bid">${esc(b.bill_id)}</div>
                
                <div class="bdate">${fmtDate(b.bill_date)} ${b.bill_time ? '· ' + b.bill_time.substring(0,5) : ''}</div>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="patient-section">
            <div class="info-row"><span class="info-label">Patient</span><span class="info-val">${esc(b.patient_name || b.patient_id)}</span></div>
            <div class="info-row"><span class="info-label">Patient ID</span><span class="info-val">${esc(b.patient_id)}</span></div>
            <div class="info-row"><span class="info-label">Phone</span><span class="info-val">${esc(b.patient_phone || b.phone || '—')}</span></div>
            <div class="info-row"><span class="info-label">Doctor</span><span class="info-val">${esc(b.doctor_name || '—')}</span></div>
            <div class="info-row"><span class="info-label">Appointment</span><span class="info-val">${esc(b.appointment_id || '—')}</span></div>
            <div class="info-row"><span class="info-label">Created By</span><span class="info-val">${esc(b.created_by || '—')}</span></div>
        </div>

        <!-- Items -->
        <p class="section-title">Billing Items</p>
        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Rate (₹)</th>
                    <th>Discount (₹)</th>
                    <th style="text-align:right;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>${itemRows || '<tr><td colspan="6" style="text-align:center;padding:16px;color:#94a3b8;">No items</td></tr>'}</tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row"><span>Subtotal</span><span>₹${fmt(b.subtotal)}</span></div>
                <div class="total-row"><span>Discount</span><span>₹${fmt(b.discount_amount)}</span></div>
                <div class="total-row grand"><span>${isReceiptPrint ? 'Receipt Amount' : 'Grand Total'}</span><span>₹${fmt(isReceiptPrint ? pmts[0].amount : b.grand_total)}</span></div>
                <div class="total-row paid"><span>${isReceiptPrint ? 'Payment Mode' : 'Amount Paid'}</span><span>${isReceiptPrint ? esc(pmts[0].payment_method) : '₹' + fmt(b.amount_paid)}</span></div>
                ${isReceiptPrint && pmts[0].amount < b.grand_total ? `<div class="total-row balance"><span>Balance After This</span><span>₹${fmt(b.grand_total - b.amount_paid)}</span></div>` : ''}
                ${!isReceiptPrint ? `<div class="total-row balance"><span>Balance Due</span><span>₹${fmt(b.balance_due)}</span></div>` : ''}
            </div>
        </div>

        <!-- Amount in Words -->
        <div style="margin-bottom:16px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:12px;">
            <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#64748b;">Amount in Words: </span>
            <span style="font-weight:600;color:#1e293b;">${numberToWords(parseFloat(b.grand_total||0))}</span>
        </div>

        ${pmts.length > 0 ? `
        <!-- Receipt Details -->
        <p class="section-title">Receipt Details</p>
        <table class="items" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Receipt No.</th>
                    <th>Date</th>
                    <th>Mode of Payment</th>
                    <th style="text-align:right;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>${pmtRows}</tbody>
        </table>` : ''}

        <!-- Print Info -->
        <div style="margin-top:12px;padding-top:10px;border-top:1px dashed #e2e8f0;
                    display:flex;flex-direction:column;gap:4px;font-size:10px;color:#94a3b8;">
            <span>Printed on: <b style="color:#64748b;">${new Date().toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})}</b></span>
            <span>Printed by: <b style="color:#64748b;">${esc(PRINTED_BY)}</b></span>
        </div>

        <!-- Footer -->
        <div class="bill-footer" style="margin-top:10px; border-top:none;">
            <div class="footer-note">
                Thank you for choosing GM Hospital.<br>
                This is a computer-generated bill and does not require a signature.
                ${b.notes ? '<br><em>Note: ' + esc(b.notes) + '</em>' : ''}
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <span>Authorised Signatory</span>
            </div>
        </div>
    </div>`;
}

loadBill();
</script>
</body>
</html>
