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
    <title>Interim Bill - <?= $billId ?></title>
    <!-- Include html2pdf for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #e2e8f0; color: #111; font-size: 11px; }

        .print-container {
            width: 210mm; min-height: 297mm;
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
        
        .bill-title { text-align: center; margin: 25px 0 15px 0; font-size: 14px; font-weight: bold; }

        /* Meta Grid 1 */
        .meta-grid-top {
            display: flex; justify-content: space-between;
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            padding: 8px 0; margin-bottom: 15px; font-weight: bold;
        }

        /* Meta Grid 2 */
        .meta-grid-details {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px;
            margin-bottom: 30px; font-size: 11px; line-height: 1.8;
        }
        .meta-row { display: flex; }
        .meta-label { width: 120px; font-weight: bold; }
        .meta-val { flex: 1; }

        /* Items Table */
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.items-table th {
            border-top: 1px solid #000; border-bottom: 1px solid #000;
            padding: 8px 5px; text-align: left; font-weight: bold;
        }
        table.items-table th.right, table.items-table td.right { text-align: right; }
        table.items-table td { padding: 5px; vertical-align: top; }
        
        .group-header { font-weight: bold; text-transform: uppercase; padding-top: 15px !important; }

        /* Footer Totals */
        .totals-section { width: 100%; display: flex; justify-content: flex-end; margin-top: 20px; }
        .totals-box { width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 5px 0; border-top: 1px solid #ddd; }
        .total-row.grand { font-weight: bold; border-top: 2px solid #000; font-size: 13px; }

        /* Watermark */
        .watermark {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px; font-weight: bold; color: rgba(0,0,0,0.04);
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

<div id="loading" class="loading">Loading bill data...</div>

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

        <div class="bill-title">Bill of Supply</div>

        <div class="meta-grid-top" id="metaTop"></div>
        <div class="meta-grid-details" id="metaDetails"></div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:15%">Date</th>
                    <th style="width:40%">Particulars</th>
                    <th style="width:10%">HSN/SAC</th>
                    <th style="width:10%" class="right">Unit</th>
                    <th style="width:10%" class="right">Unit Amt.</th>
                    <th style="width:15%" class="right">Service Amt</th>
                </tr>
            </thead>
            <tbody id="itemsBody"></tbody>
        </table>

        <div class="totals-section">
            <div class="totals-box" id="totalsBox"></div>
        </div>
        
        <div style="margin-top: 50px; display: flex; justify-content: space-between; font-size: 11px;">
            <div>Print Date & Time : <span id="printDateTime"></span></div>
            <div>Page 1 of 1</div>
        </div>
    </div>
</div>

<div class="no-print">
    <button onclick="window.print()">🖨️ Print Bill</button>
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
function fmtDateOnly(d) {
    if(!d) return '';
    const dt = new Date(d);
    return dt.toLocaleDateString('en-GB');
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

    // Meta Top
    document.getElementById('metaTop').innerHTML = `
        <div>Bill No : ${b.bill_id}</div>
        <div>Reg.No : ${b.patient_id || ''}</div>
        <div>Bill Date : ${fmtDate(b.created_at)}</div>
    `;

    // Meta Details
    document.getElementById('metaDetails').innerHTML = `
        <div>
            <div class="meta-row"><div class="meta-label">Patient Name</div><div class="meta-val">: ${b.patient_name || ''}</div></div>
            <div class="meta-row"><div class="meta-label">Address</div><div class="meta-val">: ${b.address || ''}</div></div>
            <div class="meta-row"><div class="meta-label">Billing Category</div><div class="meta-val">: ${b.bill_type || 'GENERAL'}</div></div>
            <div class="meta-row"><div class="meta-label">Discharged Bed</div><div class="meta-val">: ${b.ward_name || ''}/${b.room_name || ''}/${b.bed_number || ''}</div></div>
            <div class="meta-row"><div class="meta-label">Primary Dr.</div><div class="meta-val">: ${b.doctor_name || ''}</div></div>
        </div>
        <div>
            <div class="meta-row"><div class="meta-label">Age/Sex/Mobile No</div><div class="meta-val">: ${b.age||''} / ${b.sex||''} / ${b.phone||''}</div></div>
            <div class="meta-row"><div class="meta-label">Admission Date</div><div class="meta-val">: ${fmtDate(b.admission_date)}</div></div>
            <div class="meta-row"><div class="meta-label">Discharge Date</div><div class="meta-val">: ${b.discharge_date ? fmtDate(b.discharge_date) : 'Not Discharged'}</div></div>
            <div class="meta-row"><div class="meta-label">Sponsor Name</div><div class="meta-val">: ${b.insurance_company_id || 'SELF'}</div></div>
        </div>
    `;

    // Items Grouping
    const items = b.items || [];
    const grouped = {};
    items.forEach(it => {
        let type = it.charge_type;
        if(type === 'ROOM_RENT') type = 'BED CHARGES';
        else if(type === 'DOCTOR_VISIT') type = 'DOCTOR FEE';
        if(!grouped[type]) grouped[type] = [];
        grouped[type].push(it);
    });

    let html = '';
    for(const [type, list] of Object.entries(grouped)) {
        html += `<tr><td colspan="6" class="group-header">${type}</td></tr>`;
        
        let typeTotal = 0;
        list.forEach(it => {
            typeTotal += parseFloat(it.total_amount || it.total_price || 0);
            html += `
            <tr>
                <td>${fmtDateOnly(it.charge_date || it.created_at)}</td>
                <td>${it.description || it.item_name}</td>
                <td></td>
                <td class="right">${it.quantity || '1.00'}</td>
                <td class="right">${fmt(it.unit_price)}</td>
                <td class="right">${fmt(it.total_amount || it.total_price)}</td>
            </tr>`;
        });
        
        html += `
        <tr>
            <td colspan="4"></td>
            <td style="border-top:1px solid #000; border-bottom:1px solid #000;"></td>
            <td class="right" style="border-top:1px solid #000; border-bottom:1px solid #000; font-weight:bold;">${fmt(typeTotal)}</td>
        </tr>`;
    }
    
    document.getElementById('itemsBody').innerHTML = html;

    // Totals
    document.getElementById('totalsBox').innerHTML = `
        <div class="total-row"><span>Total Gross Amount</span><span>${fmt(b.subtotal)}</span></div>
        <div class="total-row"><span>Discount</span><span>${fmt(b.discount_amount)}</span></div>
        <div class="total-row"><span>Net Amount</span><span>${fmt(b.grand_total)}</span></div>
        <div class="total-row"><span>Advance/Paid</span><span>${fmt(b.amount_paid)}</span></div>
        <div class="total-row grand"><span>Balance Due</span><span>${fmt(b.balance_due)}</span></div>
    `;
}

function downloadPDF() {
    const element = document.getElementById('invoiceContainer');
    const opt = {
        margin:       0,
        filename:     `Interim_Bill_${BILL_ID}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}

window.onload = loadBill;
</script>
</body>
</html>
