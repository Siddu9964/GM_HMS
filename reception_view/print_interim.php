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

        <div class="bill-title">Interim Bill / Statement of Account</div>

        <div class="meta-grid-top" id="metaTop"></div>
        <div class="meta-grid-details" id="metaDetails"></div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:10%">Sl.No</th>
                    <th style="width:65%">Particulars / Service Category</th>
                    <th style="width:25%" class="right">Amount (₹)</th>
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
            <div class="meta-row"><div class="meta-label">Ward / Bed</div><div class="meta-val">: ${b.ward_name || ''}/${b.room_name || ''}/${b.bed_number || ''}</div></div>
            <div class="meta-row"><div class="meta-label">Primary Dr.</div><div class="meta-val">: ${b.doctor_name || ''}</div></div>
        </div>
        <div>
            <div class="meta-row"><div class="meta-label">Age/Sex/Mobile No</div><div class="meta-val">: ${b.age||''} / ${b.sex||''} / ${b.phone||''}</div></div>
            <div class="meta-row"><div class="meta-label">Admission Date</div><div class="meta-val">: ${fmtDate(b.admission_date)}</div></div>
            <div class="meta-row"><div class="meta-label">Discharge Date</div><div class="meta-val">: ${b.discharge_date ? fmtDate(b.discharge_date) : 'Under Treatment (Admitted)'}</div></div>
            <div class="meta-row"><div class="meta-label">Sponsor Name</div><div class="meta-val">: ${b.sponsor || b.insurance_company_name || b.insurance_company_id || 'SELF'}</div></div>
        </div>
    `;

    // Financial Summary Category Breakdown
    const catMap = {
        'ROOM_RENT':         { name: 'Room Rent & Bed Charges', amt: parseFloat(b.room_charges || 0) },
        'DOCTOR_VISIT':      { name: 'Doctor Consultation & Round Visits', amt: parseFloat(b.doctor_charges || 0) },
        'LAB':               { name: 'Laboratory Investigations', amt: parseFloat(b.lab_charges || 0) },
        'RADIOLOGY':         { name: 'Radiology & Imaging Services', amt: parseFloat(b.radiology_charges || 0) },
        'PHARMACY':          { name: 'Pharmacy Medicines & Drugs', amt: parseFloat(b.pharmacy_charges || 0) },
        'OT':                { name: 'Operation Theatre (OT) Charges', amt: parseFloat(b.ot_charges || 0) },
        'PROCEDURE':         { name: 'Hospital Procedures & Nursing Care', amt: parseFloat(b.procedure_charges || 0) },
        'DIALYSIS':          { name: 'Dialysis Services', amt: 0 },
        'OXYGEN':            { name: 'Oxygen Therapy', amt: 0 },
        'VENTILATION':       { name: 'Ventilator Support', amt: 0 },
        'BLOOD_TRANSFUSION': { name: 'Blood Transfusion Charges', amt: 0 },
        'WARD_TRANSFER':     { name: 'Ward Transfer Charges', amt: 0 },
        'CONSUMABLE':        { name: 'Medical Consumables & Disposables', amt: parseFloat(b.consumable_charges || 0) },
        'OTHER':             { name: 'Other & Miscellaneous Services', amt: parseFloat(b.other_charges || 0) }
    };

    // Aggregate from individual items to ensure complete coverage
    const items = b.items || [];
    if (items.length > 0) {
        const itemTotals = {};
        items.forEach(it => {
            if (it.status !== 'CANCELLED') {
                let cType = it.charge_type || 'OTHER';
                if (cType === 'MISC') cType = 'OTHER';
                itemTotals[cType] = (itemTotals[cType] || 0) + parseFloat(it.total_amount || it.total_price || 0);
            }
        });

        for (const [type, sum] of Object.entries(itemTotals)) {
            if (catMap[type]) {
                if (catMap[type].amt === 0 || isNaN(catMap[type].amt)) {
                    catMap[type].amt = sum;
                }
            } else {
                catMap[type] = { name: type.replace(/_/g, ' '), amt: sum };
            }
        }
    }

    // Filter active categories with amount > 0
    let activeCategories = Object.values(catMap).filter(c => c.amt > 0);
    
    // If no charges yet, show placeholder
    if (activeCategories.length === 0) {
        activeCategories = [
            { name: 'Room Rent & Bed Charges', amt: 0 },
            { name: 'Doctor Consultation & Round Visits', amt: 0 },
            { name: 'Laboratory Investigations', amt: 0 },
            { name: 'Pharmacy Medicines & Drugs', amt: 0 }
        ];
    }

    let html = '';
    let slNo = 1;
    let computedGross = 0;

    activeCategories.forEach(cat => {
        computedGross += cat.amt;
        html += `
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 9px 5px; font-weight: bold;">${slNo++}</td>
            <td style="padding: 9px 5px; font-weight: 600;">${cat.name}</td>
            <td class="right" style="padding: 9px 5px; font-weight: bold;">${fmt(cat.amt)}</td>
        </tr>`;
    });

    document.getElementById('itemsBody').innerHTML = html;

    // Totals Section
    const subtotal = parseFloat(b.subtotal) || computedGross;
    const discount = parseFloat(b.discount_amount) || 0;
    const grandTotal = parseFloat(b.grand_total) || Math.max(0, subtotal - discount);
    const amountPaid = parseFloat(b.amount_paid) || 0;
    const balanceDue = parseFloat(b.balance_due) || Math.max(0, grandTotal - amountPaid);

    document.getElementById('totalsBox').innerHTML = `
        <div class="total-row"><span>Total Gross Amount</span><span>${fmt(subtotal)}</span></div>
        <div class="total-row"><span>Discount</span><span>${fmt(discount)}</span></div>
        <div class="total-row" style="font-weight: 600;"><span>Net Amount</span><span>${fmt(grandTotal)}</span></div>
        <div class="total-row" style="color: #166534; font-weight: 600;"><span>Advance / Total Paid</span><span>${fmt(amountPaid)}</span></div>
        <div class="total-row grand"><span>Balance Due</span><span>${fmt(balanceDue)}</span></div>
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
