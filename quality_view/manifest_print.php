<?php
/**
 * manifest_print.php — Printable BMW Dispatch Manifest
 * Opens in a new tab via: window.open('/GM_HMS/quality_view/manifest_print.php?id=X', '_blank')
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /GM_HMS/login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo 'Invalid record ID.'; exit; }

require_once __DIR__ . '/../core/Autoloader.php';
$repo = new \GM_HMS\Modules\Quality\Repositories\BMWRepository();
$data = $repo->findById($id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BMW Manifest — #<?= $id ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; background: #f3efe6; }
    .manifest { max-width: 780px; margin: 20px auto; padding: 30px; border: 2px solid #1f6b4a; border-radius: 10px; background:#fff; }
    .header   { border-bottom: 2px solid #1f6b4a; padding-bottom: 16px; margin-bottom: 20px; }
    .sign-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 40px; }
    .sign-box { border-top: 1px solid #d9d2c2; padding-top: 8px; text-align: center; font-size: 0.8rem; color: #5a4e3a; }
    table thead th { background: #1f6b4a !important; color: #f3efe6 !important; }
    table tfoot td  { background: #eaf4ef !important; font-weight: 700; color: #0f3324; }
    @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
  </style>
</head>
<body>
<?php if (!$data): ?>
<div class="alert alert-danger m-4">Record #<?= $id ?> not found.</div>
<?php else: ?>
<div class="manifest">

  <!-- Hospital Header -->
  <div class="header d-flex align-items-start justify-content-between">
    <div>
      <h4 style="color:#1f6b4a;font-weight:800;margin:0;">GM Hospital</h4>
      <div style="color:#64748b;font-size:0.85rem;">Biomedical Waste Dispatch Manifest</div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($data['reference_no'] ?? 'N/A') ?></div>
      <div style="color:#64748b;font-size:0.8rem;">Receipt / Manifest No.</div>
    </div>
  </div>

  <!-- Record Details -->
  <table class="table table-bordered" style="font-size:0.875rem;">
    <tbody>
      <tr>
        <th style="background:#f8fafc;width:35%;">Record ID</th>
        <td>#<?= $data['id'] ?></td>
        <th style="background:#f8fafc;">Status</th>
        <td><span class="badge" style="background:<?= $data['status']==='Completed'?'#16a34a':'#1d4ed8' ?>"><?= $data['status'] ?></span></td>
      </tr>
      <tr>
        <th style="background:#f8fafc;">Location</th>
        <td><?= htmlspecialchars($data['location']) ?></td>
        <th style="background:#f8fafc;">Collection Time</th>
        <td><?= $data['collection_at'] ?></td>
      </tr>
      <tr>
        <th style="background:#f8fafc;">Vendor</th>
        <td><?= htmlspecialchars($data['vendor_name'] ?? '—') ?></td>
        <th style="background:#f8fafc;">Vehicle No.</th>
        <td><?= htmlspecialchars($data['vehicle_number'] ?? '—') ?></td>
      </tr>
      <tr>
        <th style="background:#f8fafc;">Driver</th>
        <td><?= htmlspecialchars($data['driver_name'] ?? '—') ?></td>
        <th style="background:#f8fafc;">Dispatch Time</th>
        <td><?= $data['dispatch_at'] ?? '—' ?></td>
      </tr>
      <tr>
        <th style="background:#f8fafc;">Logged By</th>
        <td><?= htmlspecialchars($data['logged_by_user'] ?? '—') ?></td>
        <th style="background:#f8fafc;">Supervisor</th>
        <td><?= htmlspecialchars($data['supervisor_user_name'] ?? '—') ?></td>
      </tr>
    </tbody>
  </table>

  <!-- Bin Weight Comparison -->
  <h6 style="color:#1f6b4a;font-weight:700;margin-top:20px;">Weight Comparison</h6>
  <table class="table table-bordered text-center" style="font-size:0.875rem;">
    <thead style="background:#1f6b4a;color:#fff;">
      <tr><th>Category</th><th>Green</th><th>Yellow</th><th>Red</th><th>Blue</th><th>White</th><th>TOTAL</th></tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Hospital (H.)</strong></td>
        <td><?= number_format((float)$data['h_green_weight'],2) ?></td>
        <td><?= number_format((float)$data['h_yellow_weight'],2) ?></td>
        <td><?= number_format((float)$data['h_red_weight'],2) ?></td>
        <td><?= number_format((float)$data['h_blue_weight'],2) ?></td>
        <td><?= number_format((float)$data['h_white_weight'],2) ?></td>
        <td><strong><?= number_format((float)$data['h_total_weight'],2) ?> Kg</strong></td>
      </tr>
      <tr>
        <td><strong>Vendor (V.)</strong></td>
        <td><?= number_format((float)$data['v_green_weight'],2) ?></td>
        <td><?= number_format((float)$data['v_yellow_weight'],2) ?></td>
        <td><?= number_format((float)$data['v_red_weight'],2) ?></td>
        <td><?= number_format((float)$data['v_blue_weight'],2) ?></td>
        <td><?= number_format((float)$data['v_white_weight'],2) ?></td>
        <td><strong><?= number_format((float)$data['v_total_weight'],2) ?> Kg</strong></td>
      </tr>
      <tr style="background:#f8fafc;">
        <td colspan="6" class="text-end"><strong>Weight Variance (V − H)</strong></td>
        <td><strong style="color:<?= (float)$data['weight_difference'] > 0 ? '#dc2626' : '#16a34a' ?>">
          <?= ((float)$data['weight_difference'] >= 0 ? '+' : '') . number_format((float)$data['weight_difference'], 2) ?> Kg
        </strong></td>
      </tr>
    </tbody>
  </table>

  <?php if ($data['remarks']): ?>
  <p style="font-size:0.85rem;color:#64748b;"><strong>Remarks:</strong> <?= htmlspecialchars($data['remarks']) ?></p>
  <?php endif; ?>

  <!-- Signature Block -->
  <div class="sign-row">
    <div class="sign-box">Hospital In-charge</div>
    <div class="sign-box">Vendor Representative</div>
    <div class="sign-box">Quality Supervisor</div>
  </div>

</div>

<div class="text-center mt-3 d-print-none">
  <button class="btn btn-success" onclick="window.print()">
    🖨️ Print Manifest
  </button>
  <button class="btn btn-outline-secondary ms-2" onclick="window.close()">Close</button>
</div>

<script>
// Auto-trigger print dialog
window.onload = () => setTimeout(() => window.print(), 500);
</script>
<?php endif; ?>
</body>
</html>
