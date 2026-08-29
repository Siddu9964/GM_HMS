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
        const [masterRes, itemsRes, pmtsRes, insRes] = await Promise.all([
            fetch(`/GM_HMS/api/ipd-billing-master?action=get&bill_id=${encodeURIComponent(BILL_ID)}`),
            fetch(`/GM_HMS/api/ipd-billing-items?bill_id=${encodeURIComponent(BILL_ID)}`),
            fetch(`/GM_HMS/api/ipd-payment?bill_id=${encodeURIComponent(BILL_ID)}`),
            fetch(`/GM_HMS/api/ipd-insurance?bill_id=${encodeURIComponent(BILL_ID)}`).catch(() => null)
        ]);
        
        const m = await masterRes.json();
        const i = await itemsRes.json();
        const p = await pmtsRes.json();
        let insData = null;
        if (insRes) {
            try {
                const insJson = await insRes.json();
                if (insJson.success && insJson.data) insData = insJson.data;
            } catch (_) {}
        }
        
        if (!m.success) throw new Error(m.message || "Failed to load bill master");
        
        const bill = m.data;
        bill.items = (i.data && i.data.items) ? i.data.items : [];
        bill.payments = (p.data && p.data.payments) ? p.data.payments : (p.data || []);
        bill.insurance = insData;
        
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

    // Sponsor Details Resolution
    let sponsorType = 'SELF';
    let sponsorName = 'SELF';
    let policyNo = '';
    let claimNo = '';

    // 1. Check insurance record from ipd_insurance (via bill.insurance or joined master fields)
    const ins = b.insurance || (b.insurance_company_name ? {
        insurance_type: b.insurance_type,
        company_name: b.insurance_company_name,
        tpa_name: b.tpa_name,
        policy_number: b.policy_number,
        claim_number: b.claim_number,
        approval_number: b.approval_number,
        approved_amount: b.insurance_approved_amount
    } : null);

    if (ins && (ins.company_name || ins.tpa_name || ins.insurance_type)) {
        if (ins.tpa_name && ins.tpa_name.trim() !== '') {
            sponsorType = 'TPA';
            sponsorName = ins.company_name ? `${ins.company_name} (TPA: ${ins.tpa_name})` : ins.tpa_name;
        } else if (ins.insurance_type) {
            sponsorType = ins.insurance_type.toUpperCase();
            sponsorName = ins.company_name || ins.tpa_name || sponsorType;
        } else if (ins.company_name) {
            sponsorType = 'INSURANCE';
            sponsorName = ins.company_name;
        }
        policyNo = ins.policy_number || '';
        claimNo = ins.claim_number || ins.approval_number || '';
    }

    // 2. If not found in insurance, check payment remarks (where inline/modal sponsor is recorded)
    if (sponsorType === 'SELF' || sponsorName === 'SELF') {
        const pmts = b.payments || [];
        for (const p of pmts) {
            if (p.payment_mode === 'INSURANCE' || (p.remarks && p.remarks.includes('Sponsor:'))) {
                const match = (p.remarks || '').match(/Sponsor:\s*([^(|]+)(?:\(([^)]+)\))?/i);
                if (match) {
                    sponsorName = match[1].trim();
                    if (match[2]) {
                        sponsorType = match[2].trim().toUpperCase();
                    } else {
                        sponsorType = 'INSURANCE';
                    }
                    if (!claimNo && p.reference_no) {
                        claimNo = p.reference_no;
                    }
                    break;
                }
            }
        }
    }

    // 3. Check admission sponsor and credit_type
    if (sponsorType === 'SELF' && b.credit_type && b.credit_type.toUpperCase() !== 'CASH') {
        sponsorType = b.credit_type.toUpperCase();
    }
    if (sponsorName === 'SELF' && b.sponsor && b.sponsor.trim() !== '' && b.sponsor.toUpperCase() !== 'SELF') {
        sponsorName = b.sponsor.trim();
        if (sponsorType === 'SELF') {
            sponsorType = (b.credit_type && b.credit_type.toUpperCase() !== 'CASH') ? b.credit_type.toUpperCase() : 'INSURANCE';
        }
    }

    // 4. Check bill_type
    if (sponsorType === 'SELF' && (b.bill_type === 'INSURANCE' || b.bill_type === 'CORPORATE')) {
        sponsorType = b.bill_type;
    }

    const policyClaimDetails = [policyNo ? `Pol: ${policyNo}` : '', claimNo ? `Claim/Appr: ${claimNo}` : ''].filter(Boolean).join(' | ');

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
            <div class="meta-row"><div class="meta-label">Sponsor Name</div><div class="meta-val">: ${sponsorName}</div></div>
            ${policyClaimDetails ? `<div class="meta-row"><div class="meta-label">Policy / Claim No</div><div class="meta-val">: ${policyClaimDetails}</div></div>` : ''}
        </div>
    `;

    // Financial Summary Category Breakdown
    const catMap = {
        'ROOM_RENT':         { name: 'Room Rent & Bed Charges', amt: 0 },
        'NURSING':           { name: 'Nursing Charges', amt: 0 },
        'DUTY_DR':           { name: 'Duty Doctor Charges', amt: 0 },
        'SERVICE':           { name: 'Service Charges', amt: 0 },
        'DOCTOR_VISIT':      { name: 'Doctor Consultation & Round Visits', amt: 0 },
        'LAB':               { name: 'Laboratory Investigations', amt: 0 },
        'RADIOLOGY':         { name: 'Radiology & Imaging Services', amt: 0 },
        'PHARMACY':          { name: 'Pharmacy Medicines & Drugs', amt: 0 },
        'OT':                { name: 'Operation Theatre (OT) Charges', amt: 0 },
        'PROCEDURE':         { name: 'Hospital Procedures', amt: 0 },
        'DIALYSIS':          { name: 'Dialysis Services', amt: 0 },
        'OXYGEN':            { name: 'Oxygen Therapy', amt: 0 },
        'VENTILATION':       { name: 'Ventilator Support', amt: 0 },
        'BLOOD_TRANSFUSION': { name: 'Blood Transfusion Charges', amt: 0 },
        'WARD_TRANSFER':     { name: 'Ward Transfer Charges', amt: 0 },
        'CONSUMABLE':        { name: 'Medical Consumables & Disposables', amt: 0 },
        'OTHER':             { name: 'Other & Miscellaneous Services', amt: 0 }
    };

    // Aggregate from individual items to ensure complete coverage
    const items = b.items || [];
    if (items.length > 0) {
        const itemTotals = {};
        items.forEach(it => {
            if (it.status !== 'CANCELLED') {
                let cType = it.charge_type || 'OTHER';
                const desc = (it.description || '').toLowerCase();
                if (desc.includes('nursing charge')) cType = 'NURSING';
                else if (desc.includes('duty doctor')) cType = 'DUTY_DR';
                else if (desc.includes('service charge')) cType = 'SERVICE';
                else if (cType === 'MISC') cType = 'OTHER';
                itemTotals[cType] = (itemTotals[cType] || 0) + parseFloat(it.total_amount || it.total_price || 0);
            }
        });

        for (const [type, sum] of Object.entries(itemTotals)) {
            if (catMap[type]) {
                catMap[type].amt = sum;
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
    const insReceived = parseFloat(b.insurance_received_amount) || 0;
    const insApproved = parseFloat(b.insurance_approved_amount) || 0;
    const balanceDue = parseFloat(b.balance_due) || Math.max(0, grandTotal - amountPaid - insReceived);

    let totalsHtml = `
        <div class="total-row"><span>Total Gross Amount</span><span>${fmt(subtotal)}</span></div>
        <div class="total-row"><span>Discount</span><span>${fmt(discount)}</span></div>
        <div class="total-row" style="font-weight: 600;"><span>Net Amount</span><span>${fmt(grandTotal)}</span></div>
    `;

    if (insApproved > 0) {
        totalsHtml += `<div class="total-row" style="color:#0369a1; font-weight:600;"><span>Insurance Approved Amount</span><span>${fmt(insApproved)}</span></div>`;
    }
    if (insReceived > 0) {
        totalsHtml += `<div class="total-row" style="color:#15803d; font-weight:600;"><span>Insurance Received Amount</span><span>${fmt(insReceived)}</span></div>`;
    }

    totalsHtml += `
        <div class="total-row" style="color: #166534; font-weight: 600;"><span>Advance / Total Paid</span><span>${fmt(amountPaid)}</span></div>
        <div class="total-row grand"><span>Balance Due</span><span>${fmt(balanceDue)}</span></div>
    `;

    document.getElementById('totalsBox').innerHTML = totalsHtml;
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
