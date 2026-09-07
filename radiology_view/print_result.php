<?php
/**
 * Radiology Diagnostic Imaging Report (Letterhead Design)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /GM_HMS/login.php');
    exit;
}

$orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
$source = strtoupper($_GET['source'] ?? 'OPD');
if (!$orderId) die('No Order ID provided.');

$printedBy = $_SESSION['username'] ?? 'Radiographer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Radiology Report - <?= htmlspecialchars($orderId) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; color: #111; font-size: 11px; line-height: 1.35; }
        
        @media screen {
            body { background: #e2e8f0; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
            .invoice-wrapper { width: 100%; max-width: 850px; background: #fff; padding: 0 45px 45px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
            .action-bar { display: flex; justify-content: flex-end; margin-bottom: 20px; gap: 12px; width: 100%; max-width: 850px; }
            .btn { padding: 8px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
            .btn-print { background: #0284c7; color: #fff; }
            .btn-close { background: #fff; color: #64748b; border: 1px solid #cbd5e1; }
        }
        
        @media print {
            body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; line-height: 1.3; }
            .action-bar { display: none !important; }
            .invoice-wrapper { padding: 0 20px; box-shadow: none; max-width: 100%; }
            @page { margin: 10mm; size: A4 portrait; }
        }

        /* Top spacing for pre-printed hospital letterhead */
        .letterhead-space { height: 120px; position: relative; }

        .report-header-banner {
            text-align: center;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            padding: 6px 0;
            margin-bottom: 12px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* Patient Grid */
        .patient-grid {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
            width: 100%;
        }
        .pg-col { width: 48%; display: flex; flex-direction: column; gap: 3px; }
        .pf { display: flex; align-items: flex-start; }
        .pl { font-weight: 700; color: #111; width: 135px; flex-shrink: 0; font-size: 11px; }
        .pl::after { content: ' :'; }
        .pv { font-weight: 500; color: #111; flex-grow: 1; word-break: break-word; padding-left: 5px; font-size: 11px; }

        /* Report Body Sections */
        .report-section {
            margin-bottom: 14px;
        }
        .sec-title {
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            border-bottom: 1px dashed #94a3b8;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .sec-content {
            font-size: 12px;
            color: #1e293b;
            line-height: 1.5;
            white-space: pre-wrap;
            padding-left: 2px;
        }
        .impression-box {
            background: #f8fafc;
            border-left: 3px solid #0284c7;
            padding: 10px 14px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .footer-line { text-align: center; width: 100%; padding-top: 20px; margin-top: 25px; font-size: 11px; color: #64748b; }
        .sign-area { display: flex; justify-content: space-between; margin-top: 60px; align-items: flex-end; }
        .sign-box { width: 220px; text-align: center; }
        .sign-line { border-top: 1px solid #333; padding-top: 5px; font-weight: 700; font-size: 11px; }
        
        #loading { text-align: center; padding: 50px; color: #64748b; font-size: 15px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body>

<div class="action-bar">
    <button class="btn btn-close" onclick="window.history.back()">Back</button>
    <a id="btn-view-scan" href="#" target="_blank" class="btn btn-close" style="display:none;">View Scan File</a>
    <button class="btn btn-print" onclick="window.print()">Print Report</button>
</div>

<div id="loading">Loading Radiology Report Data...</div>

<div class="invoice-wrapper" id="slip-content" style="display:none;">
    
    <div class="letterhead-space"></div>
    
    <div class="report-header-banner">
        Department of Radiodiagnosis & Imaging
    </div>

    <div class="patient-grid">
        <div class="pg-col">
            <div class="pf"><span class="pl">Patient Name</span><span class="pv"><strong id="p-name">—</strong></span></div>
            <div class="pf"><span class="pl">Age / Gender</span><span class="pv" id="p-age-sex">—</span></div>
            <div class="pf"><span class="pl">UHID / Reg No</span><span class="pv" id="p-id">—</span></div>
            <div class="pf"><span class="pl">Scan Accession No</span><span class="pv" id="b-order-id">—</span></div>
            <div class="pf"><span class="pl">Source / Bed</span><span class="pv" id="p-source">—</span></div>
            <div class="pf"><span class="pl">Referred By</span><span class="pv" id="p-doctor">HOSPITAL MEDICAL STAFF</span></div>
            <div class="pf"><span class="pl">Barcode ID</span><span class="pv" id="p-barcode-num">—</span></div>
        </div>
        <div class="pg-col">
            <div class="pf"><span class="pl">Modality</span><span class="pv"><strong id="r-modality">RADIOLOGY</strong></span></div>
            <div class="pf"><span class="pl">Procedure</span><span class="pv"><strong id="r-procedure">—</strong></span></div>
            <div class="pf"><span class="pl">Scan Date & Time</span><span class="pv" id="o-date">—</span></div>
            <div class="pf"><span class="pl">Report Generated</span><span class="pv" id="b-date">—</span></div>
            <div class="pf"><span class="pl">Contact No</span><span class="pv" id="p-phone">—</span></div>
            <div class="pf" style="align-items: flex-start; margin-top: 4px;">
                <span class="pl">Scan & Barcode</span>
                <span class="pv" style="display: flex; gap: 15px; align-items: center;">
                    <svg id="barcode"></svg>
                    <canvas id="qrcode" title="Patient Profile QR"></canvas>
                </span>
            </div>
        </div>
    </div>

    <!-- Examination Title -->
    <div style="text-align:center;margin-bottom:15px;">
        <h3 id="exam-title" style="font-weight:800;font-size:14px;color:#0f172a;text-decoration:underline;">—</h3>
    </div>

    <!-- Clinical History -->
    <div class="report-section" id="sec-history-wrap">
        <div class="sec-title">Clinical History / Indication:</div>
        <div class="sec-content" id="r-history">Not specified</div>
    </div>

    <!-- Technique -->
    <div class="report-section" id="sec-tech-wrap">
        <div class="sec-title">Technique / Protocol:</div>
        <div class="sec-content" id="r-technique">Standard diagnostic projection / acquisition.</div>
    </div>

    <!-- Findings -->
    <div class="report-section">
        <div class="sec-title">Findings / Observations:</div>
        <div class="sec-content" id="r-findings">Pending radiologist transcription.</div>
    </div>

    <!-- Impression -->
    <div class="impression-box" id="sec-imp-wrap">
        <div style="font-weight:800;font-size:12px;color:#0369a1;text-transform:uppercase;margin-bottom:4px;">Impression / Conclusion:</div>
        <div style="font-size:12px;font-weight:700;color:#0f172a;line-height:1.4;" id="r-impression">—</div>
    </div>

    <div class="footer-line">
        * * * * * End of Radiology Diagnostic Report * * * * *
    </div>
    
    <div class="sign-area">
        <div class="sign-box">
            <div style="font-size:10px;color:#64748b;margin-bottom:4px;">Technologist: <?= htmlspecialchars($printedBy) ?></div>
            <div class="sign-line">Radiographer In-Charge</div>
        </div>
        <div class="sign-box">
            <div style="font-size:10px;color:#0284c7;margin-bottom:4px;" id="sign-doctor">Dr. Consultant Radiologist, MD</div>
            <div class="sign-line">Consultant Radiologist</div>
        </div>
    </div>
    
</div>

<script>
async function loadRadReport() {
    const oid = <?= json_encode($orderId) ?>;
    const src = <?= json_encode($source) ?>;

    try {
        const apiBase = (src === 'IPD') ? '/GM_HMS/api/radiology/ipd-orders/' : '/GM_HMS/api/radiology/orders/';
        
        // Fetch order details
        const orderRes = await fetch(apiBase + encodeURIComponent(oid));
        const orderData = await orderRes.json();
        const order = (orderData.success && orderData.data) ? orderData.data : null;

        // Fetch result details
        const resRes = await fetch(apiBase + encodeURIComponent(oid) + '/result');
        const resData = await resRes.json();
        const result = (resData.success && resData.data) ? resData.data : null;

        if (!order && !result) {
            document.getElementById('loading').innerHTML = 'Radiology report not found for Order #' + oid;
            return;
        }

        // Fill Demographics
        document.getElementById('b-order-id').textContent = oid;
        document.getElementById('p-barcode-num').textContent = oid;

        // Render Barcode
        try {
            JsBarcode("#barcode", oid, {
                format: "CODE128",
                lineColor: "#000",
                width: 1,
                height: 30,
                displayValue: false,
                margin: 0
            });
        } catch(e) {}

        // Render QR
        let pId = (order && order.patient_id) ? order.patient_id : (result && result.patient_id ? result.patient_id : '');
        if (pId) {
            try {
                const profileUrl = window.location.origin + '/GM_HMS/reception_view/patient_profile.php?id=' + encodeURIComponent(pId);
                QRCode.toCanvas(document.getElementById('qrcode'), profileUrl, { width: 44, margin: 0 });
            } catch(e) {}
        }

        if (order) {
            document.getElementById('p-name').textContent = order.patient_name || '—';
            document.getElementById('p-id').textContent = order.patient_id || '—';
            const sexAge = `${order.gender || order.patient_gender || '—'} / ${order.age || order.patient_age || '—'} Yrs`;
            document.getElementById('p-age-sex').textContent = sexAge;
            document.getElementById('p-phone').textContent = order.patient_phone || order.phone || '—';
            document.getElementById('o-date').textContent = order.order_date || order.created_at || '—';
            
            if (src === 'IPD') {
                const bed = (order.bed_number ? `Bed ${order.bed_number}` : '') + (order.ward_name ? ` (${order.ward_name})` : '');
                document.getElementById('p-source').textContent = 'IPD • ' + (bed || 'Inpatient');
            } else {
                document.getElementById('p-source').textContent = 'OPD • Outpatient';
            }

            if (order.doctor_name) {
                document.getElementById('p-doctor').textContent = 'Dr. ' + order.doctor_name;
            }
        }

        function esc(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Test name & Modality
        let testName = (result && result.test_name) ? result.test_name : (order ? (order.test_name || order.item_name) : 'Radiology Scan');
        let testList = [];
        try {
            const d = JSON.parse(testName);
            if (Array.isArray(d)) testList = d;
        } catch(e){}

        if (testList.length === 0 && typeof testName === 'string') {
            if (testName.includes('|||')) {
                testList = testName.split('|||').map(s => s.trim()).filter(Boolean);
            } else if (/\),\s*/.test(testName)) {
                testList = testName.split(/(?<=\))\s*,\s*/).map(s => s.trim()).filter(Boolean);
            } else {
                testList = [testName];
            }
        }
        // Filter out any lab tests if mixed in
        testList = testList.filter(it => {
            const s = (typeof it === 'object' ? (it.name || it.test_name || '') : String(it)).toUpperCase();
            if (/\bLAB\d+\b/i.test(s)) return false;
            if (/\b(CBC|ELECTROLYTE|UREA|CREATININE|URINE|SUGAR|GLUCOSE|LIPID|BILIRUBIN|WIDAL|CULTURE|HEMOGLOBIN|BLOOD GAS)\b/i.test(s) && !/\bRDS\d*\b/i.test(s)) return false;
            return true;
        });

        const testDisplayStr = testList.length > 0 ? testList.join(', ') : testName;
        if (testList.length > 1) {
            document.getElementById('r-procedure').innerHTML = testList.map((t, idx) => `<div style="margin-bottom:3px;font-weight:600;"><span style="color:#0284c7;">${idx + 1}.</span> ${esc(t)}</div>`).join('');
            document.getElementById('exam-title').innerHTML = testList.map((t, idx) => `${idx + 1}. ${esc(t.toUpperCase())}`).join(' &nbsp;&bull;&nbsp; ');
        } else {
            document.getElementById('r-procedure').textContent = testDisplayStr;
            document.getElementById('exam-title').textContent = testDisplayStr.toUpperCase();
        }

        let mod = result ? (result.modality || '') : '';
        if (!mod) {
            const ut = testName.toUpperCase();
            if (ut.includes('CT')) mod = 'CT SCAN';
            else if (ut.includes('DOPPLER')) mod = 'COLOR DOPPLER';
            else if (ut.includes('USG') || ut.includes('ULTRASOUND')) mod = 'ULTRASOUND (USG)';
            else mod = 'DIGITAL RADIOGRAPHY (X-RAY)';
        }
        document.getElementById('r-modality').textContent = mod.toUpperCase();

        // Clinical details
        if (result) {
            document.getElementById('b-date').textContent = (result.result_date || '') + ' ' + (result.result_time ? result.result_time.slice(0,5) : '');
            if (result.clinical_history) document.getElementById('r-history').textContent = result.clinical_history;
            if (result.technique) document.getElementById('r-technique').textContent = result.technique;
            
            // Format findings (Ensure laboratory parameters are NEVER rendered as radiology findings)
            let f = result.findings || '';
            if (!f && result.result_data) {
                try {
                    const parsed = typeof result.result_data === 'string' ? JSON.parse(result.result_data) : result.result_data;
                    if (Array.isArray(parsed)) {
                        // Check if this is laboratory blood/urine data
                        const isLabData = parsed.some(p => p.range !== undefined || /\b(hemoglobin|pcv|wbc|rbc|platelet|urine|sugar|electrolyte|urea|creatinine|bilirubin|neutrophil|lymphocyte|monocyte|eosinophil|basophil|blood)\b/i.test(p.name || ''));
                        if (!isLabData) {
                            f = parsed.map(p => `${p.name}: ${p.value} ${p.unit || ''}`).join('\n');
                        }
                    } else if (typeof parsed === 'string' && parsed.trim() !== '') {
                        f = parsed;
                    }
                } catch(e){}
            }
            document.getElementById('r-findings').textContent = f || 'No acute radiologic abnormality detected.';
            document.getElementById('r-impression').textContent = result.impression || 'Normal study for age. No significant radiologic abnormality noted.';
            
            if (result.reviewed_by) {
                document.getElementById('sign-doctor').textContent = result.reviewed_by;
            }

            if (result.report_file) {
                const btnScan = document.getElementById('btn-view-scan');
                btnScan.href = '/GM_HMS/' + result.report_file;
                btnScan.style.display = 'inline-flex';
            }
        } else {
            document.getElementById('b-date').textContent = 'Pending';
            document.getElementById('r-findings').textContent = 'Examination in progress. Radiologist provisional report pending.';
            document.getElementById('r-impression').textContent = 'Pending validation.';
        }

        document.getElementById('loading').style.display = 'none';
        document.getElementById('slip-content').style.display = 'block';

    } catch(err) {
        document.getElementById('loading').innerHTML = '<div style="color:red;">Error fetching report details.</div>';
    }
}

document.addEventListener('DOMContentLoaded', loadRadReport);
</script>

</body>
</html>
