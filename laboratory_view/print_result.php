<?php
/**
 * LIS - Print Lab Result (Letterhead Design)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /GM_HMS/login.php');
    exit;
}

$orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
$source = $_GET['source'] ?? 'OPD';
if (!$orderId) die('No Order ID provided.');

// Get user name for printing meta
$printedBy = $_SESSION['username'] ?? 'Technician';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lab Result - <?= htmlspecialchars($orderId) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; color: #111; font-size: 11px; line-height: 1.2; }
        
        @media screen {
            body { background: #e2e8f0; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
            .invoice-wrapper { width: 100%; max-width: 850px; background: #fff; padding: 0 40px 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
            .action-bar { display: flex; justify-content: flex-end; margin-bottom: 20px; gap: 12px; width: 100%; max-width: 850px; }
            .btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
            .btn-print { background: #1f6b4a; color: #fff; }
            .btn-close { background: #fff; color: #64748b; border: 1px solid #e2e8f0; }
        }
        
        @media print {
            body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; line-height: 1.15; }
            .action-bar { display: none !important; }
            /* Add guaranteed 20px side padding so hardware printers NEVER clip the first letters */
            .invoice-wrapper { padding: 0 20px; box-shadow: none; max-width: 100%; }
            @page { margin: 10mm; size: A4 portrait; }
        }

        /* Top spacing for pre-printed letterhead */
        .letterhead-space { height: 130px; position: relative; }

        /* Patient Grid */
        .patient-grid {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            padding: 8px 0;
            margin-bottom: 15px;
            width: 100%;
        }
        .pg-col { width: 48%; display: flex; flex-direction: column; gap: 3px; }
        .pf { display: flex; align-items: flex-start; }
        .pl { font-weight: 700; color: #111; width: 140px; flex-shrink: 0; font-size: 11px; }
        .pl::after { content: ' :'; }
        .pv { font-weight: 500; color: #111; flex-grow: 1; word-break: break-word; padding-left: 5px; font-size: 11px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: none; }
        thead th { border-bottom: 1px solid #333; padding: 10px 6px; font-size: 13px; font-weight: 700; color: #111; text-align: center; }
        thead th:first-child { text-align: left; }
        
        tbody td { padding: 8px 6px; font-size: 13px; text-align: center; color: #111; line-height: 1.4; }
        tbody td:first-child { text-align: left; padding-left: 0; }

        .dept-header { font-size: 14px; font-weight: 700; text-decoration: underline; text-align: left !important; padding: 16px 0 4px 0; }
        .sample-type { font-size: 13px; font-style: italic; color: #333; padding-bottom: 12px; text-align: left !important; }
        .test-title { font-weight: 700; font-size: 13px; padding: 10px 0 6px 0; color: #111; text-align: left !important; }

        .abnormal { font-weight: 800; color: #000; }
        
        .footer-line { text-align: center; width: 100%; padding-top: 20px; margin-top: 30px; font-size: 11px; color: #333; }
        .sign-area { display: flex; justify-content: space-between; margin-top: 80px; align-items: flex-end;}
        .sign-box { width: 200px; text-align: center; }
        .sign-line { border-top: 1px solid #333; padding-top: 5px; font-weight: 700; font-size: 12px; }
        
        #loading { text-align: center; padding: 50px; color: #64748b; font-size: 16px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body>

<div class="action-bar">
    <button class="btn btn-close" onclick="window.history.back()">Close</button>
    <a id="btn-view-pdf" href="#" target="_blank" class="btn btn-close" style="display:none;">View PDF Report</a>
    <button class="btn btn-print" onclick="window.print()">Print Result</button>
</div>

<div id="loading">Loading Result Data...</div>

<div class="invoice-wrapper" id="slip-content" style="display:none;">
    
    <div class="letterhead-space"></div>
    
    <div class="patient-grid">
        <div class="pg-col">
            <div class="pf"><span class="pl">Patient Name</span><span class="pv" id="p-name">—</span></div>
            <div class="pf"><span class="pl">Age / Sex</span><span class="pv" id="p-age-sex">—</span></div>
            <div class="pf"><span class="pl">IP/Reg.No/UHID</span><span class="pv" id="p-id">—</span></div>
            <div class="pf"><span class="pl">Lab No</span><span class="pv" id="b-order-id">—</span></div>
            <div class="pf"><span class="pl">Referred By</span><span class="pv">HOSPITAL STAFF</span></div>
            <div class="pf"><span class="pl">Bedcategory/Bed N</span><span class="pv">—</span></div>
            <div class="pf"><span class="pl">Consultant</span><span class="pv" id="p-doctor">—</span></div>
            <div class="pf"><span class="pl">Barcode No.</span><span class="pv" id="p-barcode-num">—</span></div>
        </div>
        <div class="pg-col">
            <div class="pf"><span class="pl">Bill No</span><span class="pv" id="b-bill-no">—</span></div>
            <div class="pf"><span class="pl">Sample Collected</span><span class="pv" id="o-date">—</span></div>
            <div class="pf"><span class="pl">Sample Received</span><span class="pv" id="o-date-2">—</span></div>
            <div class="pf"><span class="pl">Report On</span><span class="pv" id="b-date">—</span></div>
            <div class="pf"><span class="pl">Bill Creation Date</span><span class="pv" id="o-date-3">—</span></div>
            <div class="pf"><span class="pl">Mobile No</span><span class="pv" id="p-phone">—</span></div>
            <div class="pf" style="align-items: flex-start; margin-top: 5px;">
                <span class="pl">Bar Code & QR</span>
                <span class="pv" style="display: flex; flex-direction: column; gap: 4px;">
                    <strong id="bc-patient-name" style="font-size:11px;"></strong>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <svg id="barcode"></svg>
                        <canvas id="qrcode" title="Scan for Patient Profile"></canvas>
                    </div>
                </span>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width:40%">Test Name</th>
                <th style="width:20%">Results</th>
                <th style="width:20%">Units</th>
                <th style="width:20%">Bio. Ref. Interval</th>
            </tr>
        </thead>
        <tbody id="results-tbody">
            <!-- Dynamic Injection -->
        </tbody>
    </table>

    <div class="footer-line">
        --------------------End of the Report--------------------
    </div>
    
    <div class="sign-area">
        <div class="sign-box" style="visibility:hidden;">
            <!-- Placeholder for alignment -->
        </div>
        <div class="sign-box">
            <div class="sign-line">Authorised Signatory</div>
        </div>
    </div>
    
</div>

<script>
async function loadOrderAndResult() {
  const oid = <?= json_encode($orderId) ?>;
  const src = <?= json_encode($source) ?>;
  try {
    const apiBase = (src === 'IPD') ? '/GM_HMS/api/laboratory/ipd-orders/' : '/GM_HMS/api/laboratory/orders/';
    
    const orderRes = await fetch(apiBase + encodeURIComponent(oid));
    const orderData = await orderRes.json();
    let order = null;
    if (orderData.success && orderData.data) {
      order = orderData.data;
    }
    
    const resRes = await fetch(apiBase + encodeURIComponent(oid) + '/result');
    const resData = await resRes.json();

    if (!order && !resData.success) {
      document.getElementById('loading').innerHTML = 'Result not found';
      return;
    }

    const result = resData.success ? resData.data : null;

    // Populate Fields
    document.getElementById('b-order-id').textContent = oid;
    document.getElementById('b-bill-no').textContent = oid;
    
    // Use the full Order ID (e.g., OPB-20260805-0001) for the barcode so it is uniquely scannable
    const barcodeVal = oid;
    document.getElementById('p-barcode-num').textContent = barcodeVal;
    
    // Render Barcode
    try {
        JsBarcode("#barcode", barcodeVal, {
            format: "CODE128",
            lineColor: "#000",
            width: 1,
            height: 35,
            displayValue: false,
            margin: 0
        });
    } catch(e) { console.error('Barcode rendering failed', e); }
    
    // Render QR Code for complete patient details
    try {
        let pId = (order && order.patient_id) ? order.patient_id : (result && result.patient_id ? result.patient_id : '');
        if(pId) {
            const profileUrl = window.location.origin + '/GM_HMS/reception_view/patient_profile.php?id=' + encodeURIComponent(pId);
            QRCode.toCanvas(document.getElementById('qrcode'), profileUrl, { 
                width: 50, 
                margin: 0,
                color: {
                    dark: '#000000',
                    light: '#ffffff'
                }
            });
        }
    } catch(e) { console.error('QR rendering failed', e); }
    
    const repDate = result ? (result.result_date + ' ' + result.result_time.slice(0,5)) : '--';
    document.getElementById('b-date').textContent = repDate;
    
    if(order) {
       const oDate = order.order_date || order.updated_at || '--';
       document.getElementById('o-date').textContent = oDate;
       document.getElementById('o-date-2').textContent = oDate;
       document.getElementById('o-date-3').textContent = oDate;
    } else {
       document.getElementById('o-date').textContent = '--';
       document.getElementById('o-date-2').textContent = '--';
       document.getElementById('o-date-3').textContent = '--';
    }

    if (order) {
      document.getElementById('p-name').textContent = order.patient_name || '—';
      const bcPatientName = document.getElementById('bc-patient-name');
      if (bcPatientName) bcPatientName.textContent = order.patient_name || '—';
      document.getElementById('p-id').textContent = order.patient_id || '—';
      document.getElementById('p-phone').textContent = order.phone || '—';
      
      let dName = order.doctor_name || 'Walk-in / Self';
      if (dName !== 'Walk-in / Self' && !dName.toLowerCase().startsWith('dr')) dName = 'Dr. ' + dName;
      document.getElementById('p-doctor').textContent = dName;
      
      let ageSex = [];
      if (order.age) ageSex.push(order.age + 'Y');
      if (order.sex) ageSex.push(order.sex);
      document.getElementById('p-age-sex').textContent = ageSex.length ? ageSex.join('/') : '—';
    } else if (result) {
      document.getElementById('p-id').textContent = result.patient_id || '—';
    }

    // Results Rendering
    if (result) {
      let params = [];
      try { params = JSON.parse(result.result_data); } catch(e) {}
      
      let displayTestName = result.test_name || (order ? order.test_name : '—');
      try {
        const parsed = JSON.parse(displayTestName);
        if (Array.isArray(parsed)) displayTestName = parsed.join(', ');
      } catch(e) {}
      
      if (params && params.length > 0) {
        let html = '';
        
        // Department Header (assuming General if not specified)
        html += `<tr><td colspan="4" class="dept-header">Laboratory Report</td></tr>`;
        html += `<tr><td colspan="4" class="sample-type">Type Of Sample : Default Sample</td></tr>`;
        html += `<tr><td colspan="4" class="test-title">${displayTestName}</td></tr>`;
        
        const isTextReport = params.length === 1 && !params[0].unit && !params[0].range && params[0].value.length > 30;
        
        if (isTextReport) {
           html += `<tr>
             <td colspan="4" style="padding:15px 0;white-space:pre-wrap;line-height:1.6;font-size:11.5px;"><strong style="text-decoration:underline;margin-bottom:8px;display:block;">Findings:</strong><br>${params[0].value}</td>
           </tr>`;
        } else {
           html += params.map((p, i) => {
             let val = p.value ?? p.result ?? '';
             let numVal = parseFloat(val);
             let rangeStr = p.range ?? p.normal_range ?? '';
             
             let arrow = '';
             let isAbnormal = false;
             
             // Logic to parse range e.g. "13.0 - 17.0"
             if(!isNaN(numVal) && rangeStr.includes('-')) {
                 const parts = rangeStr.split('-');
                 if(parts.length === 2) {
                     const min = parseFloat(parts[0].trim());
                     const max = parseFloat(parts[1].trim());
                     if(!isNaN(min) && !isNaN(max)) {
                         if(numVal < min) { arrow = '↓ '; isAbnormal = true; }
                         if(numVal > max) { arrow = '↑ '; isAbnormal = true; }
                     }
                 }
             }
             
             return `
             <tr>
               <td>${p.name || ''}</td>
               <td class="${isAbnormal ? 'abnormal' : ''}">${arrow}${val}</td>
               <td>${p.unit ?? '—'}</td>
               <td>${rangeStr || '—'}</td>
             </tr>`;
           }).join('');
        }
        
        document.getElementById('results-tbody').innerHTML = html;
      }

      if (result.report_file) {
        const pdfBtn = document.getElementById('btn-view-pdf');
        if(pdfBtn) {
           pdfBtn.style.display = 'inline-flex';
           pdfBtn.href = '/GM_HMS/' + result.report_file;
        }
      }
      
    } else {
        document.getElementById('results-tbody').innerHTML = `<tr><td colspan="4" style="text-align:center;padding:25px;">No results entered yet for this order.</td></tr>`;
    }

    document.getElementById('loading').style.display = 'none';
    document.getElementById('slip-content').style.display = 'block';

    // Auto print only if user hasn't opened PDF view
    setTimeout(() => { if(!window.openedPDF) window.print(); }, 500);

  } catch(e) {
    document.getElementById('loading').innerHTML = 'Error loading result';
    console.error(e);
  }
}
loadOrderAndResult();
</script>
</body>
</html>
