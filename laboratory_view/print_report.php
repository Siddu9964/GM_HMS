<?php
/**
 * LIS - Print Lab Order Slip
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /GM_HMS/login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? '';
if (!$orderId) die('No Order ID provided.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lab Order - <?= htmlspecialchars($orderId) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      color: #111;
      font-size: 13px;
      line-height: 1.5;
      margin: 0;
      background: #f0f4f8;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .print-wrapper {
      max-width: 21cm;
      margin: 40px auto;
      background: #fff;
      padding: 40px;
      border: 1px solid #ccc;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #1f6b4a;
      padding-bottom: 15px;
      margin-bottom: 20px;
    }
    .hospital-info h1 {
      margin: 0 0 5px 0;
      font-size: 24px;
      font-weight: 800;
      color: #1f6b4a;
    }
    .hospital-info p { margin: 0; font-size: 12px; color: #555; }
    .branch-badge {
      display: inline-block;
      background: #e0f2fe;
      color: #144d34;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      margin-top: 5px;
    }
    .doc-title {
      text-align: right;
    }
    .doc-title h2 { margin: 0; font-size: 20px; color: #333; text-transform: uppercase; }
    .doc-title p { margin: 0; font-weight: 700; font-family: monospace; font-size: 15px; color: #1f6b4a; }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 25px;
    }
    .info-box {
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 12px;
    }
    .info-box h3 { margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
    .info-row { display: flex; margin-bottom: 4px; }
    .info-row span.label { width: 90px; color: #6b7280; font-weight: 600; font-size: 12px; }
    .info-row span.val { font-weight: 700; color: #111; }

    .test-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 40px;
    }
    .test-table th, .test-table td {
      border: 1px solid #e5e7eb;
      padding: 10px 12px;
      text-align: left;
    }
    .test-table th { background: #f3f4f6; color: #374151; font-size: 12px; text-transform: uppercase; }
    .test-table td { font-weight: 600; font-size: 14px; }

    .priority-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .pri-urgent { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .pri-stat   { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
    .pri-routine{ background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

    .footer {
      display: flex;
      justify-content: space-between;
      margin-top: 50px;
      padding-top: 20px;
    }
    .sign-box { text-align: center; width: 200px; }
    .sign-line { border-top: 1px solid #333; margin-bottom: 5px; padding-top: 5px; font-weight: 600; font-size: 12px; }

    .no-print-bar {
      background: #1e293b;
      color: #fff;
      padding: 15px;
      text-align: center;
      margin-bottom: 20px;
    }
    .no-print-bar button {
      background: #0ea5e9; color: #fff; border: none; padding: 8px 16px; font-weight: 700; border-radius: 6px; cursor: pointer; margin: 0 10px;
    }

    @media print {
      body { background: #fff; }
      .print-wrapper { margin: 0; padding: 0; border: none; box-shadow: none; width: 100%; max-width: 100%; }
      .no-print-bar { display: none; }
    }
  </style>
</head>
<body>

<div class="no-print-bar">
  <button onclick="window.print()"><i class="fas fa-print"></i> Print Slip</button>
  <button onclick="window.close()" style="background:#475569;">Close</button>
</div>

<div class="print-wrapper" id="slip-content" style="display:none;">

  <div class="header">
    <div class="hospital-info">
      <h1>GM HOSPITAL</h1>
      <p>Laboratory Information System</p>
      <div class="branch-badge" id="b-branch">Main Branch</div>
    </div>
    <div class="doc-title">
      <h2>Lab Order Slip</h2>
      <p id="b-order-id">LAB-XXXXXX</p>
      <div style="font-size:11px;color:#555;margin-top:4px;">Date: <span id="b-date">--</span></div>
    </div>
  </div>

  <div class="info-grid">
    <div class="info-box">
      <h3>Patient Details</h3>
      <div class="info-row"><span class="label">Name:</span> <span class="val" id="p-name">—</span></div>
      <div class="info-row"><span class="label">Patient ID:</span> <span class="val" id="p-id">—</span></div>
      <div class="info-row"><span class="label">Age/Sex:</span> <span class="val" id="p-age-sex">—</span></div>
      <div class="info-row"><span class="label">Phone:</span> <span class="val" id="p-phone">—</span></div>
    </div>
    <div class="info-box">
      <h3>Clinical Details</h3>
      <div class="info-row"><span class="label">Ref Doctor:</span> <span class="val" id="d-name">—</span></div>
      <div class="info-row"><span class="label">Specialty:</span> <span class="val" id="d-spec">—</span></div>
      <div class="info-row"><span class="label">Priority:</span> <span class="val" id="o-priority">—</span></div>
      <div class="info-row"><span class="label">Status:</span> <span class="val" id="o-status">—</span></div>
    </div>
  </div>

  <table class="test-table">
    <thead>
      <tr>
        <th style="width:50px;">#</th>
        <th>Test Description</th>
        <th style="width:120px;">Priority</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td id="t-name" style="font-size:16px;">—</td>
        <td id="t-badge">—</td>
      </tr>
      <tr>
        <td colspan="3" style="font-weight:400;color:#555;font-size:12px;">
          <strong>Clinical Notes:</strong> <span id="t-notes">None</span>
        </td>
      </tr>
    </tbody>
  </table>

  <div class="footer">
    <div class="sign-box">
      <br><br><br>
      <div class="sign-line">Laboratory Technician</div>
    </div>
    <div class="sign-box">
      <br><br><br>
      <div class="sign-line">Referring Physician</div>
    </div>
  </div>

</div>

<div id="loading" style="text-align:center;padding:50px;font-family:'Inter',sans-serif;color:#64748b;">
  <h2>Loading Order Data...</h2>
</div>

<script>
async function loadOrder() {
  const oid = <?= json_encode($orderId) ?>;
  try {
    const res = await fetch('/GM_HMS/api/laboratory/orders?all=1&search=' + encodeURIComponent(oid));
    const data = await res.json();
    if (data.success && data.data && data.data.length > 0) {
      // Assuming search by exact order_id returns the one order first
      const o = data.data.find(x => x.order_id === oid) || data.data[0];

      document.getElementById('b-order-id').textContent = o.order_id;
      document.getElementById('b-date').textContent = (o.order_date||'') + ' ' + (o.order_time||'').slice(0,5);

      document.getElementById('p-name').textContent = o.patient_name || '—';
      document.getElementById('p-id').textContent = o.patient_id || '—';
      document.getElementById('p-age-sex').textContent = (o.age||'?') + 'y / ' + (o.sex||'—');
      document.getElementById('p-phone').textContent = o.phone || '—';

      document.getElementById('d-name').textContent = o.doctor_name || '—';
      document.getElementById('d-spec').textContent = o.specialization || '—';
      document.getElementById('o-priority').textContent = o.priority || 'Routine';
      document.getElementById('o-status').textContent = o.status || 'Ordered';

      document.getElementById('t-name').textContent = o.test_name;
      document.getElementById('t-notes').textContent = o.notes || 'None provided';

      let pCls = 'pri-routine';
      if(o.priority === 'Urgent') pCls = 'pri-urgent';
      if(o.priority === 'Stat') pCls = 'pri-stat';
      document.getElementById('t-badge').innerHTML = `<span class="priority-badge ${pCls}">${o.priority||'Routine'}</span>`;

      // Branch
      const sessBranch = sessionStorage.getItem('lis_branch') || 'Main Branch';
      document.getElementById('b-branch').textContent = sessBranch;

      document.getElementById('loading').style.display = 'none';
      document.getElementById('slip-content').style.display = 'block';

      // Auto print after a tiny delay for render
      setTimeout(() => window.print(), 300);

    } else {
      document.getElementById('loading').innerHTML = '<h2 style="color:#ef4444;">Order not found</h2><p>Invalid Order ID</p>';
    }
  } catch(e) {
    document.getElementById('loading').innerHTML = '<h2 style="color:#ef4444;">Error loading order</h2>';
  }
}
loadOrder();
</script>
</body>
</html>
