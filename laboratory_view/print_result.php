<?php
/**
 * LIS - Print Lab Result (Redesigned matching Pharmacy Module)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /GM_HMS/login.php');
    exit;
}

$orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
if (!$orderId) die('No Order ID provided.');

// Get user name for printing meta
$printedBy = $_SESSION['username'] ?? 'Technician';

// Determine Branch Name and Address
$branch = strtolower($_SESSION['hospital_branch'] ?? $_SESSION['branch'] ?? 'nagarabhavi');
if ($branch === 'basaveshwaranagar') {
    $hospitalTitle = 'GM HOSPITAL BASAVESHWARANAGAR';
    $branchSub = '(A unit of pan NAGARABHAVI Hospitalals pvt ltd)';
    $branchAddr = 'No. 335, 3rd Stage, 4th Block, Siddaiah Puranik Road, Basaveshwara nagar, Bengaluru 560079';
} else {
    $hospitalTitle = 'GM HOSPITAL NAGARABHAVI';
    $branchSub = '(A unit of pan NAGARABHAVI Hospitalals pvt ltd)';
    $branchAddr = 'Bengaluru';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lab Result - <?= htmlspecialchars($orderId) ?></title>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #1f6b4a; 
            --primary-dark: #096b6b; 
            --text-main: #1e293b; 
            --text-muted: #64748b; 
            --bg-light: #f8fafc; 
            --border: #e2e8f0; 
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; color: var(--text-main); font-size: 11px; line-height: 1.4; }
        
        @media screen {
            body { background: #e2e8f0; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
            .invoice-wrapper { width: 100%; max-width: 850px; }
            .action-bar { display: flex; justify-content: flex-end; margin-bottom: 20px; gap: 12px; }
            .btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 12px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; font-family: 'Inter', sans-serif; text-decoration: none; }
            .btn-print { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(31, 107, 74,0.25); }
            .btn-print:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 6px 15px rgba(31, 107, 74,0.3); }
            .btn-a5 { background: #334155; color: #fff; }
            .btn-pdf { background: #0ea5e9; color: #fff; box-shadow: 0 4px 12px rgba(14, 165, 233,0.25); }
            .btn-pdf:hover { background: #0284c7; transform: translateY(-1px); }
            .btn-close { background: #fff; color: var(--text-muted); border: 1px solid var(--border); }
            .btn-close:hover { background: #f1f5f9; color: var(--text-main); }
            .invoice-container { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        }
        
        @media print {
            body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .action-bar { display: none !important; }
            .invoice-container { padding: 0; box-shadow: none; border-radius: 0; max-width: 100%; position: relative; z-index: 1; }
            @page { margin: 5mm; size: A4 portrait; }
        }
        body.is-a5 @media print {
            @page { size: A5 landscape; }
            .invoice-container { font-size: 10px; }
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 100px;
            font-weight: 900;
            color: rgba(31, 107, 74, 0.08);
            z-index: 9999;
            pointer-events: none;
            white-space: nowrap;
            user-select: none;
        }
        @media print {
            .watermark { color: rgba(31, 107, 74, 0.15) !important; }
        }

        .hdr { text-align: center; padding-bottom: 8px; border-bottom: 1px dashed var(--border); margin-bottom: 12px; }
        .cn { font-size: 18px; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px; }
        .hdr-sub { font-size: 11px; font-weight: 700; color: var(--text-main); margin-bottom: 2px; }
        .hdr-addr { font-size: 10px; color: var(--text-muted); margin-bottom: 2px; }

        .pt { margin-bottom: 12px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 15px; padding: 4px 0; }
        .pf { display: flex; flex-direction: row; align-items: center; gap: 6px; }
        .pl { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 85px; flex-shrink: 0; }
        .pl::after { content: ':' }
        .pv { font-size: 11.5px; font-weight: 600; color: var(--text-main); }

        .st { font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: var(--primary); margin-bottom: 8px; border-bottom: 2px solid var(--border); padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; }
        thead tr { background: #f1f5f9; }
        thead th { color: #1e293b; padding: 6px 4px; font-size: 9px; font-weight: 800; text-transform: uppercase; text-align: left; border-bottom: 1px solid #cbd5e1; }
        tbody td { border: none; font-size: 11px; padding: 6px 4px; }

        .aw { background: var(--bg-light); border: 1px dashed var(--border); border-radius: 6px; padding: 8px 12px; font-size: 11px; margin-bottom: 15px; font-weight: 500; display: flex; align-items: center; gap: 8px; color: #0284c7; }
        .aw strong { font-size: 10px; text-transform: uppercase; color: #fff; background: #0ea5e9; padding: 2px 6px; border-radius: 4px; }
        
        .bottom-meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: var(--bg-light); border: 1px solid var(--border); border-radius: 8px; padding: 10px 15px; margin-bottom: 20px; }
        .bottom-meta-item { display: flex; flex-direction: column; gap: 3px; }
        .bottom-meta-label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
        .bottom-meta-value { font-size: 11.5px; font-weight: 600; color: var(--text-main); }

        .ft { display: flex; justify-content: space-between; border-top: 2px dashed var(--border); padding-top: 15px; align-items: flex-end; }
        .fn { font-size: 12px; color: var(--text-muted); font-style: italic; font-weight: 500; text-align: center; width: 100%; margin-top: 20px;}
        
        .sign-box { text-align: center; width: 160px; }
        .sign-line { border-top: 1px solid var(--text-muted); padding-top: 4px; font-weight: 700; font-size: 10px; color: var(--text-main); text-transform: uppercase; margin-top: 5px; }

    </style>
</head>
<body>

<div class="invoice-wrapper">
    <div class="watermark">GM HOSPITAL</div>
    
    <div class="action-bar">
        <button class="btn btn-close" onclick="window.history.back()">
            Close
        </button>
        <a id="btn-view-pdf" href="#" target="_blank" class="btn btn-pdf" style="display:none;">
            View PDF Report
        </a>
        <button class="btn btn-a5" onclick="document.body.classList.toggle('is-a5')">
            Toggle A5 Print
        </button>
        <button class="btn btn-print" onclick="window.print()">
            Print Result
        </button>
    </div>
    
    <div id="loading" style="text-align:center;padding:50px;font-family:'Inter',sans-serif;color:#64748b;background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
        <h2>Loading Result Data...</h2>
    </div>

    <div class="invoice-container" id="slip-content" style="display:none;">
        <div class="hdr">
            <div class="cn"><?= htmlspecialchars($hospitalTitle) ?></div>
            <div class="hdr-sub"><?= htmlspecialchars($branchSub) ?></div>
            <div class="hdr-addr"><?= htmlspecialchars($branchAddr) ?></div>
        </div>
        
        <div class="pt">
            <div class="pf"><span class="pl">Patient Name</span><span class="pv" id="p-name">—</span></div>
            <div class="pf"><span class="pl">Patient ID</span><span class="pv" id="p-id">—</span></div>
            <div class="pf"><span class="pl">Age &amp; Sex</span><span class="pv" id="p-age-sex">—</span></div>
            
            <div class="pf"><span class="pl">Order ID</span><span class="pv" id="b-order-id">—</span></div>
            <div class="pf"><span class="pl">Phone</span><span class="pv" id="p-phone">—</span></div>
            <div class="pf"><span class="pl">Doctor</span><span class="pv" style="color:var(--primary);" id="p-doctor">—</span></div>
            
            <div class="pf"><span class="pl">Test Name</span><span class="pv" id="t-name" style="font-weight:800">—</span></div>
            <div class="pf"><span class="pl">Order Date</span><span class="pv" id="o-date">—</span></div>
            <div class="pf"><span class="pl">Status</span><span class="pv" id="t-status" style="text-transform:uppercase;">—</span></div>
        </div>
        
        <div class="st">Test Results</div>
        <table id="results-table">
            <thead>
                <tr>
                    <th style="width:5%;text-align:center">SL NO</th>
                    <th style="width:40%;text-align:left">PARAMETER</th>
                    <th style="width:20%;text-align:center">RESULT</th>
                    <th style="width:15%;text-align:center">UNIT</th>
                    <th style="width:20%;text-align:center">NORMAL RANGE</th>
                </tr>
            </thead>
            <tbody id="results-tbody">
                <!-- Dynamic Rows -->
            </tbody>
        </table>
        
        <div class="aw" id="pdf-notice" style="display:none;">
            <strong>PDF Attached</strong> A scanned PDF document has been uploaded as part of this report.
        </div>
        
        <div class="bottom-meta-grid">
            <div class="bottom-meta-item">
                <span class="bottom-meta-label">Report Date & Time</span>
                <span class="bottom-meta-value" id="b-date">—</span>
            </div>
            <div class="bottom-meta-item">
                <span class="bottom-meta-label">Processed By</span>
                <span class="bottom-meta-value" id="b-technician"><?= htmlspecialchars($printedBy) ?></span>
            </div>
            <div class="bottom-meta-item">
                <span class="bottom-meta-label">Verified By</span>
                <span class="bottom-meta-value">Authorized Signatory</span>
            </div>
        </div>
        
        <div class="ft">
            <div class="sign-box">
                <div style="font-weight:700; font-size:12px; margin-bottom:5px; color:var(--primary);"><?= htmlspecialchars($printedBy) ?></div>
                <div class="sign-line">Laboratory Technician</div>
            </div>
        </div>
        
        <div class="fn">
            This is a computer generated report. End of Report.
        </div>
    </div>
</div>

<script>
async function loadOrderAndResult() {
  const oid = <?= json_encode($orderId) ?>;
  try {
    const orderRes = await fetch('/GM_HMS/api/laboratory/orders?all=1&search=' + encodeURIComponent(oid));
    const orderData = await orderRes.json();
    let order = null;
    if (orderData.success && orderData.data && orderData.data.length > 0) {
      order = orderData.data.find(x => x.order_id === oid) || orderData.data[0];
    }
    
    const resRes = await fetch('/GM_HMS/api/laboratory/orders/' + encodeURIComponent(oid) + '/result');
    const resData = await resRes.json();

    if (!order && !resData.success) {
      document.getElementById('loading').innerHTML = '<h2 style="color:#ef4444;">Result not found</h2><p>Invalid Order ID or results not entered yet.</p>';
      return;
    }

    const result = resData.success ? resData.data : null;

    document.getElementById('b-order-id').textContent = oid;
    document.getElementById('b-date').textContent = result ? (result.result_date + ' ' + result.result_time.slice(0,5)) : '--';
    if(order) {
       document.getElementById('o-date').textContent = order.order_date || '--';
    } else {
       document.getElementById('o-date').textContent = '--';
    }

    if (order) {
      document.getElementById('p-name').textContent = order.patient_name || '—';
      document.getElementById('p-id').textContent = order.patient_id || '—';
      document.getElementById('p-phone').textContent = order.phone || '—';
      
      let dName = order.doctor_name || 'Walk-in / Self';
      if (dName !== 'Walk-in / Self' && !dName.toLowerCase().startsWith('dr')) dName = 'Dr. ' + dName;
      document.getElementById('p-doctor').textContent = dName;
      
      let ageSex = [];
      if (order.age) ageSex.push(order.age + 'y');
      if (order.sex) ageSex.push(order.sex);
      document.getElementById('p-age-sex').textContent = ageSex.length ? ageSex.join(' / ') : '—';
      
      let displayTestName = order.test_name || '—';
      try {
        const parsed = JSON.parse(order.test_name);
        if (Array.isArray(parsed)) displayTestName = parsed.join(', ');
      } catch(e) {}
      document.getElementById('t-name').textContent = displayTestName;
    } else if (result) {
      document.getElementById('p-id').textContent = result.patient_id || '—';
      
      let displayTestName = result.test_name || '—';
      try {
        const parsed = JSON.parse(result.test_name);
        if (Array.isArray(parsed)) displayTestName = parsed.join(', ');
      } catch(e) {}
      document.getElementById('t-name').textContent = displayTestName;
    }

    document.getElementById('t-status').textContent = result ? result.status : 'Pending';

    if (result) {
      let params = [];
      try { params = JSON.parse(result.result_data); } catch(e) {}
      
      if (params && params.length > 0) {
        let html = '';
        
        // Detect Radiology text-area formats
        const isTextReport = params.length === 1 && !params[0].unit && !params[0].range && params[0].value.length > 30;
        
        if (isTextReport) {
           html = `<tr>
             <td colspan="5" style="padding:15px 10px;white-space:pre-wrap;line-height:1.6;font-size:11.5px;color:var(--text-main);"><strong style="font-size:12px;color:var(--primary);text-transform:uppercase;display:block;margin-bottom:8px;">Findings:</strong>${params[0].value}</td>
           </tr>`;
        } else {
           html = params.map((p, i) => `
             <tr style="background:${i%2 ? '#f8fafc' : '#fff'}">
               <td style="text-align:center;padding:8px 4px;color:var(--text-muted);font-weight:500;border-bottom:1px solid #e2e8f0;">${i+1}</td>
               <td style="padding:8px 4px;font-weight:700;color:var(--text-main);border-bottom:1px solid #e2e8f0;">${p.name || ''}</td>
               <td style="text-align:center;padding:8px 4px;font-weight:800;font-size:12px;color:var(--primary-dark);border-bottom:1px solid #e2e8f0;">${p.value || ''}</td>
               <td style="text-align:center;padding:8px 4px;color:var(--text-muted);border-bottom:1px solid #e2e8f0;">${p.unit || '—'}</td>
               <td style="text-align:center;padding:8px 4px;color:var(--text-muted);border-bottom:1px solid #e2e8f0;">${p.range || '—'}</td>
             </tr>
           `).join('');
        }
        
        document.getElementById('results-tbody').innerHTML = html;
      }

      if (result.report_file) {
        document.getElementById('pdf-notice').style.display = 'flex';
        const pdfBtn = document.getElementById('btn-view-pdf');
        if(pdfBtn) {
           pdfBtn.style.display = 'inline-flex';
           pdfBtn.href = '/GM_HMS/' + result.report_file;
        }
      }
      
    } else {
        document.getElementById('results-tbody').innerHTML = `<tr><td colspan="5" style="text-align:center;color:#ef4444;padding:25px;font-weight:600;">No results entered yet for this order.</td></tr>`;
    }

    document.getElementById('loading').style.display = 'none';
    document.getElementById('slip-content').style.display = 'block';

  } catch(e) {
    document.getElementById('loading').innerHTML = '<h2 style="color:#ef4444;">Error loading result</h2><p>Please check your connection and try again.</p>';
    console.error(e);
  }
}
loadOrderAndResult();
</script>
</body>
</html>
