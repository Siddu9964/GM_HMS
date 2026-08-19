<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../receptionist_login.php");
    exit();
}
$billId = htmlspecialchars($_POST['bill_id'] ?? $_GET['bill_id'] ?? '');
if (!$billId) { echo "No Bill ID provided."; exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?= $billId ?></title>
    <!-- Include html2pdf for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #e2e8f0; color: #111; font-size: 12px; }

        .print-container {
            width: 210mm; min-height: 148mm; /* Half A4 */
            margin: 20px auto; background: white;
            padding: 20mm 15mm; position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Header */
        .hospital-header { text-align: center; margin-bottom: 20px; }
        .hospital-brand {
            font-size: 26px;
            font-weight: 800;
            color: #1793a5;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .hospital-branches {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 13px;
            color: #4b5563;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .hospital-branches::before, .hospital-branches::after {
            content: '';
            display: inline-block;
            width: 45px;
            height: 1px;
            background: #6b7280;
        }
        .hospital-header p { font-size: 11px; line-height: 1.4; color: #333; }
        
        .bill-title { text-align: center; margin: 20px 0; font-size: 15px; font-weight: bold; text-decoration: underline; }

        /* Meta Grid 1 */
        .meta-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px;
            margin-bottom: 30px; font-size: 12px; line-height: 2;
            border: 1px solid #000; padding: 15px;
        }
        .meta-row { display: flex; }
        .meta-label { width: 140px; font-weight: bold; }
        .meta-val { flex: 1; }

        /* Payments Table */
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.items-table th {
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            padding: 8px 5px; text-align: left; font-weight: bold;
        }
        table.items-table th.right, table.items-table td.right { text-align: right; }
        table.items-table td { padding: 8px 5px; vertical-align: top; border-bottom: 1px dashed #ccc; }
        
        /* Watermark */
        .watermark {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px; font-weight: bold; color: rgba(0,0,0,0.03);
            pointer-events: none; white-space: nowrap; z-index: 1;
        }
        .print-content { position: relative; z-index: 2; }

        /* Print controls */
        .no-print {
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: #1f2937; padding: 15px; text-align: center; z-index: 1000;
        }
        .no-print button {
            background: #3b82f6; color: white; border: none;
            padding: 10px 25px; margin: 0 10px; border-radius: 5px;
            font-size: 14px; cursor: pointer; font-weight: bold;
        }
        .no-print button.btn-pdf { background: #ef4444; }
        .no-print button.btn-close { background: #6b7280; }
        
        .loading { text-align: center; padding: 50px; font-size: 16px; color: #666; }

        @media print {
            body { background: white; margin: 0; }
            .print-container { box-shadow: none; margin: 0; padding: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div id="loading" class="loading">Loading receipt data...</div>

<div class="print-container" id="invoiceContainer" style="display:none;">
    <div class="watermark">GM Hospital</div>
    <div class="print-content">
        <div class="hospital-header">
            <div class="hospital-brand">GM HOSPITALS</div>
            <div class="hospital-branches">Nagarabhavi | Basaveshwaranagar</div>
            <p>(A Unit of PANNAGARABHAVI HOSPITALS PVT LTD)<br>
               No. 335, 3rd Stage, 4th Block, Siddaiah Puranik Road,<br>
               Basaveshwara nagar, Bengaluru - 560079<br>
               Tel. No - 0802221160 Mob. No - 9900003527<br>
               GST NO: 29AAFCP8756N3ZE</p>
        </div>

        <div class="bill-title">PAYMENT RECEIPT</div>

        <div class="meta-grid" id="metaDetails"></div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:20%">Date</th>
                    <th style="width:30%">Receipt No</th>
                    <th style="width:30%">Payment Mode</th>
                    <th style="width:20%" class="right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody id="itemsBody"></tbody>
        </table>

        <div style="font-weight: bold; margin-bottom: 30px; font-size: 13px;">
            Amount in Words: <span id="amountInWords" style="font-weight: normal; text-transform: uppercase;"></span>
        </div>
        
        <div style="margin-top: 50px; display: flex; justify-content: space-between; font-size: 11px;">
            <div>Print Date & Time : <span id="printDateTime"></span></div>
            <div style="text-align: center; width: 200px;">
                <div style="border-bottom: 1px solid #000; margin-bottom: 5px; height: 30px;"></div>
                Authorized Signatory
            </div>
        </div>
    </div>
</div>

<div class="no-print">
    <button onclick="window.print()">🖨️ Print Receipt</button>
    <button class="btn-pdf" onclick="downloadPDF()">📄 Download PDF</button>
    <button class="btn-close" onclick="window.close()">✕ Close</button>
</div>

<script>
const BILL_ID = '<?= $billId ?>';

// Formatting helpers
function fmt(n) { return parseFloat(n||0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function fmtDate(d) {
    if(!d) return '';
    const dt = new Date(d);
    return dt.toLocaleDateString('en-GB') + ' ' + dt.toLocaleTimeString('en-US', {hour: 'numeric', minute:'2-digit'});
}
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
    let result = rupees > 0 ? convert(rupees) + ' Rupees' : '';
    return result ? result + ' Only' : 'Zero Rupees Only';
}


async function loadBill() {
    try {
        const [masterRes, itemsRes, pmtsRes] = await Promise.all([
            fetch(`/GM_HMS/api/ipd-billing-master?action=get&bill_id=${encodeURIComponent(BILL_ID)}`),
            fetch(`/GM_HMS/api/ipd-billing-items?bill_id=${encodeURIComponent(BILL_ID)}`),
            fetch(`/GM_HMS/api/ipd-payment?bill_id=${encodeURIComponent(BILL_ID)}`)
        ]);
        
        const m = await masterRes.json();
        const i = await itemsRes.json();
        const p = await pmtsRes.json();
        
        if (!m.success) throw new Error(m.message || "Failed to load bill master");
        
        const bill = m.data;
        bill.items = (i.data && i.data.items) ? i.data.items : [];
        bill.payments = (p.data && p.data.payments) ? p.data.payments : (p.data || []);
        
        renderBill(bill);
    } catch(e) {
        document.getElementById('loading').innerHTML = `<span style="color:red">Error: ${e.message}</span>`;
    }
}

function renderBill(b) {
    document.getElementById('loading').style.display = 'none';
    document.getElementById('invoiceContainer').style.display = 'block';

    const now = new Date();
    document.getElementById('printDateTime').innerText = now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString('en-US', {hour: 'numeric', minute:'2-digit'});

    // Meta Details
    document.getElementById('metaDetails').innerHTML = `
        <div>
            <div class="meta-row"><div class="meta-label">Patient Name</div><div class="meta-val">: ${b.patient_name || ''}</div></div>
            <div class="meta-row"><div class="meta-label">Reg.No</div><div class="meta-val">: ${b.patient_id || ''}</div></div>
        </div>
        <div>
            <div class="meta-row"><div class="meta-label">Bill No</div><div class="meta-val">: ${b.bill_id}</div></div>
            <div class="meta-row"><div class="meta-label">Total Billed Amt</div><div class="meta-val">: ₹${fmt(b.grand_total)}</div></div>
            <div class="meta-row"><div class="meta-label">Balance Due</div><div class="meta-val">: ₹${fmt(b.balance_due)}</div></div>
        </div>
    `;

    // Payments Grouping (Only show latest payment or all if receipt)
    const pmts = b.payments || [];
    let html = '';
    let totalPaid = 0;
    pmts.forEach(p => {
        totalPaid += parseFloat(p.amount);
        html += `
        <tr>
            <td>${fmtDate(p.payment_date)}</td>
            <td>${p.receipt_no || p.payment_id}</td>
            <td>${p.payment_mode || 'Cash'} ${p.reference_no ? '('+p.reference_no+')' : ''}</td>
            <td class="right" style="font-weight:bold;">${fmt(p.amount)}</td>
        </tr>`;
    });
    
    if (pmts.length === 0) {
        html = `<tr><td colspan="4" style="text-align:center;">No payments recorded yet.</td></tr>`;
    }
    
    document.getElementById('itemsBody').innerHTML = html;
    document.getElementById('amountInWords').innerText = numberToWords(totalPaid);
}

function downloadPDF() {
    const element = document.getElementById('invoiceContainer');
    const opt = {
        margin:       0,
        filename:     `Receipt_${BILL_ID}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'a5', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}

window.onload = loadBill;
</script>
</body>
</html>
