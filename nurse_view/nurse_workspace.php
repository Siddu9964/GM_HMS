<?php
session_start();
// Temporarily allow access for testing - enable auth check in production
// if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Nurse','Superintendent_Nurse','Nursing_Superintendent','admin','Admin'])) {
//     header("Location: /GM_HMS/login.php"); exit();
// }
$_SESSION['branch'] = $SESSION['branch'] ?? 'basaveshwaranagar';
$nurseId   = $_SESSION['user_id']   ?? null;
$nurseName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Nurse');
require_once __DIR__ . '/../core/Autoloader.php';

$allDoctors = [];
try {
    $db = GM_HMS\Database\SecureDatabase::getInstance();
    $conn = $db->getConnection();
    $res = $conn->query("SELECT full_name FROM doctors ORDER BY full_name ASC");
    if($res) {
        while($r = $res->fetch_assoc()) $allDoctors[] = $r['full_name'];
    }
    
    $allWards = [];
    $resWards = $conn->query("SELECT DISTINCT room_type FROM hospital_beds WHERE room_type IS NOT NULL ORDER BY room_type ASC");
    if($resWards) {
        while($r = $resWards->fetch_assoc()) {
            if(!empty($r['room_type'])) $allWards[] = $r['room_type'];
        }
    }
    
    // Fetch assigned shift and patients
    require_once __DIR__ . '/includes/nurse_auth_helper.php';
    $nurseWard = null;
    $assignedPatients = [];
    if ($nurseId) {
        $nurseWard = getCurrentNurseWard($conn, $nurseId);
        $roleId = $_SESSION['role_id'] ?? $_SESSION['user_id'] ?? null;
        $shiftModel = new \GM_HMS\Models\NurseShiftModel();
        $assignedPatients = $shiftModel->getAssignedPatientsRedesigned($nurseId, $roleId, $currentWard ?? $nurseWard);
    }

} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nurse Workspace - GM HMS</title>
<link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* ── Custom Dashboard Mockup CSS ── */
.ws-layout {
    padding: 20px;
    background: #fbfdfc;
}
.sticky-action-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #f3efe6;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(31,107,74,.3);
    margin-bottom: 20px;
}
.qa-links {
    display: flex;
    gap: 20px;
}
.qa-links a {
    color: #166534;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
}
.qa-links a i { font-size: 1rem; }
.btn-sv-main {
    background: #064e3b;
    color: #f3efe6;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ws-grid-new {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    width: 100%;
}
.card-new {
    background: #f3efe6;
    border: 1px solid rgba(31,107,74,.3);
    border-radius: 8px;
    padding: 15px;
}
.card-new.full-width {
    grid-column: 1 / -1;
}
.card-title-new {
    color: #166534;
    font-weight: 800;
    font-size: 0.9rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.split-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.split-left {
    width: 100%;
}
.split-right {
    width: 100%;
    border-top: 1px dashed rgba(31,107,74,.3);
    padding-top: 15px;
    margin-top: 5px;
}
.ht-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #064e3b;
    margin-bottom: 10px;
}
.fmg label {
    font-size: 0.7rem;
    color: #374151;
    font-weight: 600;
}
.fmg label::after {
    content: " *";
    color: #ef4444;
}
.fmg input, .fmg select {
    border: none;
    border-bottom: 1px solid rgba(31,107,74,.3);
    border-radius: 0;
    padding: 4px 0;
    background: transparent;
    font-size: 0.8rem;
}
.fmg input:focus, .fmg select:focus {
    box-shadow: none;
    border-bottom-color: #166534;
}
.btn-sv-out {
    background: transparent;
    border: 1px solid #166534;
    color: #166534;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 10px;
    width: fit-content;
}
.ht-wrap table {
    width: 100%;
    border-collapse: collapse;
}
.ht-wrap th {
    background: rgba(31,107,74,.1);
    color: #166534;
    font-size: 0.7rem;
    padding: 8px;
    text-align: left;
    border: none;
}
.ht-wrap td {
    font-size: 0.75rem;
    padding: 8px;
    border-bottom: 1px solid #f3f4f6;
}
.view-all {
    display: inline-block;
    margin-top: 10px;
    font-size: 0.75rem;
    color: #166534;
    text-decoration: none;
    font-weight: 700;
}
.treatments-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}
.t-card {
    border: 1px solid rgba(31,107,74,.3);
    border-radius: 6px;
    padding: 10px;
}
.t-title {
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.t-purple { color: #1f6b4a; }
.t-blue { color: #1f6b4a; }
.t-green { color: #1f6b4a; }
.t-orange { color: #1f6b4a; }
.t-red { color: #1f6b4a; }
</style>
<style>
:root{--primary:#1f6b4a;--pd:#1f6b4a;--pl:#1f6b4a;--pg:rgba(31,107,74,.15);--bg:#f3efe6;--sf:#f3efe6;--bd:rgba(31,107,74,.3);--mt:rgba(31,107,74,.8);--tx:#1f6b4a;--err:#1f6b4a;--ok:#1f6b4a;--warn:#1f6b4a;--r:12px;--rg:16px}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Plus Jakarta Sans',sans-serif}
body{background:var(--bg);color:var(--tx)}

/* Patient Banner */
.pt-banner{background:linear-gradient(135deg,var(--pd),var(--pl));color:#f3efe6;padding:10px 16px;margin:12px 24px 0;border-radius:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;box-shadow:0 4px 15px rgba(31,107,74,.2);position:sticky;top:72px;z-index:90}
.pt-banner-av{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:800;flex-shrink:0;border:2px solid rgba(255,255,255,.3)}
.pt-banner-info{display:flex;flex-direction:column;justify-content:center;gap:2px}
.pt-banner-nm{font-size:.95rem;font-weight:800;display:flex;align-items:center;gap:12px;line-height:1}
.pt-banner-chips{display:flex;gap:6px;flex-wrap:wrap}
.ptchip{font-size:.7rem;background:rgba(255,255,255,.13);padding:2px 8px;border-radius:20px;display:flex;align-items:center;gap:4px;line-height:1.2}
.ptchip strong{color:#d1fae5}
.pt-banner-ac{margin-left:auto;display:flex;gap:6px;flex-shrink:0}
.pba{padding:5px 10px;border-radius:6px;font-size:.72rem;font-weight:700;border:none;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .2s}
.pba.warn{background:#f59e0b;color:#000}.pba.warn:hover{background:#d97706}
.pba.sec{background:rgba(255,255,255,.15);color:#f3efe6}.pba.sec:hover{background:rgba(255,255,255,.25)}

/* Patient Search Overlay */
#ps-overlay{position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);z-index:999;display:flex;align-items:center;justify-content:center}
#ps-box{background:#f3efe6;border-radius:20px;width:100%;max-width:560px;box-shadow:0 25px 60px rgba(0,0,0,.2);overflow:hidden}
.ps-head{background:linear-gradient(135deg,var(--pd),var(--pl));padding:20px 24px;color:#f3efe6}
.ps-head h3{font-size:1.1rem;font-weight:800;margin-bottom:4px}
.ps-head p{font-size:.8rem;opacity:.8}
.ps-body{padding:20px 24px}
.ps-inp-w{position:relative}
.ps-inp-w i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--mt)}
.ps-inp-w input{width:100%;padding:12px 14px 12px 42px;border:2px solid var(--bd);border-radius:10px;font-size:.9rem;font-weight:500;outline:none;transition:border-color .2s}
.ps-inp-w input:focus{border-color:var(--primary)}
#ps-results{margin-top:10px;max-height:280px;overflow-y:auto;border-radius:10px;border:1px solid var(--bd);display:none}
.ps-item{padding:12px 16px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f3efe6;transition:background .15s}
.ps-item:last-child{border-bottom:none}.ps-item:hover{background:rgba(31,107,74,.1)}
.ps-item-n{font-weight:700;font-size:.9rem;color:var(--tx)}
.ps-item-m{font-size:.75rem;color:var(--mt);margin-top:2px}
.ps-item-b{font-size:.7rem;font-weight:700;color:var(--primary);background:var(--pg);padding:3px 9px;border-radius:20px;white-space:nowrap}
.ps-empty{padding:20px;text-align:center;color:var(--mt);font-size:.85rem}


/* Forms */
.fg {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    align-items: end;
}
.fmg{display:flex;flex-direction:column;gap:4px}
.fmg label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--mt)}
.fmg input,.fmg select,.fmg textarea{padding:9px 12px;border:1.5px solid var(--bd);border-radius:8px;font-size:.85rem;background:#f3efe6;color:var(--tx);transition:border-color .2s;outline:none;font-family:inherit;width:100%}
.fmg input:focus,.fmg select:focus,.fmg textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--pg)}
.fmg input[readonly]{background:#f3efe6;color:var(--mt);cursor:not-allowed}
.fra{display:flex;gap:8px;align-items:center;margin-top:16px;flex-wrap:wrap}
.btn-sv{padding:9px 20px;background:var(--primary);color:#f3efe6;border:none;border-radius:8px;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .2s}
.btn-sv:hover{background:var(--pd);transform:translateY(-1px)}.btn-sv:disabled{opacity:.6;cursor:not-allowed;transform:none}
.btn-cl{padding:9px 14px;background:#f3efe6;color:var(--mt);border:none;border-radius:8px;font-size:.82rem;font-weight:700;cursor:pointer;transition:background .2s}
.btn-cl:hover{background:#e2e8f0}
.span2{grid-column:span 2}


/* History table */
.ht-wrap{overflow-x:auto;border-radius:8px;border:1px solid var(--bd);margin-top:16px}
.ht{width:100%;border-collapse:collapse;font-size:.8rem}
.ht thead th{background:#f3efe6;padding:9px 14px;text-align:left;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--mt);border-bottom:1px solid var(--bd);white-space:nowrap}
.ht tbody td{padding:10px 14px;border-bottom:1px solid #f3efe6;color:var(--tx);vertical-align:top}
.ht tbody tr:last-child td{border-bottom:none}.ht tbody tr:hover td{background:#f3efe6}
.et td{text-align:center;color:var(--mt);padding:24px;font-size:.85rem}
.divider{border:none;border-top:1px solid var(--bd);margin:20px 0}
.sub-hd{font-size:.85rem;font-weight:700;color:var(--pd);margin-bottom:12px;display:flex;align-items:center;gap:8px}
.sub-hd i{color:var(--primary)}
.badge{padding:3px 9px;border-radius:20px;font-size:.68rem;font-weight:700}
.bl{background:#e0f2fe;color:#0284c7}.br{background:#fef3c7;color:#d97706}.bo{background:#f3e8ff;color:#1f6b4a}.bg{background:#dcfce7;color:#1f6b4a}

/* Tests search */
#ts-results{position:absolute;top:calc(100% + 4px);left:0;right:0;background:#f3efe6;border:1.5px solid var(--bd);border-radius:10px;z-index:300;display:none;box-shadow:0 8px 24px rgba(0,0,0,.1);max-height:240px;overflow-y:auto}
.ts-item{padding:10px 14px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f3efe6;transition:background .15s}
.ts-item:hover{background:rgba(31,107,74,.1)}.ts-item:last-child{border-bottom:none}
.cart-row{display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f3efe6;border-radius:8px;margin-bottom:6px;border:1px solid var(--bd)}
.cart-row-n{flex:1;font-size:.85rem;font-weight:600}
.cart-row input[type=number]{width:56px;padding:5px 8px;border:1.5px solid var(--bd);border-radius:6px;text-align:center;font-size:.82rem}
.cart-row .rm-btn{background:none;border:none;color:var(--err);cursor:pointer;font-size:.9rem;padding:4px}

/* Pharmacy search */
#ph-results{position:absolute;top:calc(100% + 4px);left:0;right:0;background:#f3efe6;border:1.5px solid var(--bd);border-radius:10px;z-index:300;display:none;box-shadow:0 8px 24px rgba(0,0,0,.1);max-height:260px;overflow-y:auto}
.ph-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid #f3efe6;transition:background .15s}
.ph-item:hover{background:rgba(31,107,74,.1)}.ph-item:last-child{border-bottom:none}
.ph-item-n{font-weight:700;font-size:.85rem}
.ph-item-m{font-size:.72rem;color:var(--mt);margin-top:2px}

/* Toast */
#toast{position:fixed;bottom:28px;right:28px;background:#10b981;color:#f3efe6;padding:12px 22px;border-radius:10px;font-size:.85rem;font-weight:700;z-index:9999;display:none;box-shadow:0 6px 20px rgba(0,0,0,.15);max-width:320px}

/* Hide Individual Saves */
.btn-sv[data-ct], #ts-save-btn, #ph-save-btn { display: none !important; }

/* No patient state */
.nopt{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 24px;background:var(--sf);border-radius:var(--rg);border:2px dashed var(--bd);text-align:center;gap:12px;margin:20px 24px}
.nopt-ic{width:72px;height:72px;border-radius:50%;background:var(--pg);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:var(--primary)}
.nopt h3{font-size:1.1rem;font-weight:700;color:var(--tx)}
.nopt p{font-size:.85rem;color:var(--mt);max-width:300px;line-height:1.5}
/* Select2 Theme Overrides */
.select2-container .select2-selection--single {
    height: 44px;
    border: 2px solid var(--bd);
    border-radius: 10px;
    background-color: #f3efe6;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: var(--tx);
    font-size: 0.9rem;
    font-weight: 500;
    line-height: normal;
    padding-left: 14px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 42px;
    right: 8px;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--primary);
    outline: none;
}
.select2-dropdown {
    border: 2px solid var(--primary);
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    overflow: hidden;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1px solid var(--bd);
    border-radius: 6px;
    padding: 8px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: var(--primary);
    color: #f3efe6;
}

/* Comprehensive Responsive View */
@media (max-width: 1024px) {
    .ws-layout { margin: 15px 16px !important; }
    .pt-banner { margin: 15px 16px 0; }
    .fg { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
}

@media (max-width: 768px) {
    .ws-layout { margin: 10px 12px !important; }
    .pt-banner { margin: 10px 12px 0; flex-direction: column; align-items: flex-start; }
    .pt-banner-ac { width: 100%; justify-content: space-between; margin-top: 10px; margin-left: 0; }
    .card-new { padding: 16px; }
    .split-right { padding-top: 10px; }
}

@media (max-width: 480px) {
    .fg, #f-tr .fg { grid-template-columns: 1fr !important; }
    .card-title-new { font-size: 0.85rem; flex-wrap: wrap; }
    .t-card { padding: 8px; }
    .fmg label { font-size: 0.65rem; }
    .fmg input, .fmg select, .fmg textarea { padding: 8px 10px; font-size: 0.8rem; }
    .btn-sv-main { width: 100%; justify-content: center; margin-top: 15px; }
    .btn-sv-main i { font-size: 1rem; }
    .pt-banner-chips { gap: 4px; }
    .ptchip { padding: 2px 6px; font-size: 0.65rem; }
}
</style>
</head>
<body>

<datalist id="doctor-list">
  <?php foreach($allDoctors as $doc): ?>
    <option value="<?php echo htmlspecialchars($doc); ?>">
  <?php endforeach; ?>
</datalist>

<datalist id="ward-list">
  <?php foreach($allWards as $ward): ?>
    <option value="<?php echo htmlspecialchars($ward); ?>">
  <?php endforeach; ?>
</datalist>

<div class="main-layout" style="display:flex;width:100%;">
  <?php include 'includes/nurse_sidebar.php'; ?>

  <div class="content-wrapper" style="flex:1;display:block!important;overflow-x:hidden!important;overflow-y:auto!important;height:100%;">
    <?php $pageTitle = 'Nurse Workspace'; include 'includes/nurse_navbar.php'; ?>



    <!-- Assigned Patients Dashboard -->
    <div class="nopt" id="nopt-state" style="display:block; padding: 0 24px;">
      <?php if (!empty($assignedPatients)): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
              <h3 style="margin: 0; color: var(--primary); text-align: left;"><i class="fas fa-users"></i> Your Assigned Patients (<?php echo htmlspecialchars($nurseWard['floor_name'] . ($nurseWard['room_type'] ? ' - '.$nurseWard['room_type'] : '')); ?>)</h3>
              
              <!-- Integrated Dashboard Search -->
              <div style="display: flex; align-items: center; background: white; border: 1px solid var(--bd); border-radius: 8px; padding: 4px 12px; width: 100%; max-width: 350px;">
                  <i class="fas fa-search" style="color: var(--mt); margin-right: 8px;"></i>
                  <input type="text" id="dash-search" placeholder="Search by name, ID, room..." style="border: none; outline: none; padding: 8px 0; width: 100%; font-size: 0.9rem; background: transparent;">
              </div>
          </div>

          <div id="dash-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; text-align: left;">
              <?php foreach($assignedPatients as $p): ?>
                  <div class="card-new dash-card" data-search="<?php echo htmlspecialchars(strtolower($p['first_name'].' '.$p['last_name'].' '.$p['patient_id'].' '.$p['room_type'].' '.$p['room_number'])); ?>" style="cursor: pointer; transition: transform 0.2s;" onclick='selectPatient(<?php echo json_encode($p); ?>)' onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                      <div style="font-weight: 800; font-size: 1.05rem; color: var(--primary); margin-bottom: 8px;">
                          <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>
                      </div>
                      <div style="font-size: 0.8rem; color: var(--mt); display: flex; flex-direction: column; gap: 4px;">
                          <span><strong>PID:</strong> <?php echo htmlspecialchars($p['patient_id'] ?? ''); ?></span>
                          <span><strong>Bed:</strong> <?php echo htmlspecialchars(($p['room_type'] ?? '') . ' / ' . ($p['room_number'] ?? '')); ?></span>
                          <span><strong>Age/Sex:</strong> <?php echo htmlspecialchars(($p['age'] ?? '?') . 'Y / ' . ($p['sex'] ?? '?')); ?></span>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
          <div id="dash-no-res" style="display: none; padding: 40px; text-align: center; color: var(--mt);">
              <i class="fas fa-search-minus" style="font-size: 3rem; margin-bottom: 10px; opacity: 0.5;"></i>
              <h4>No patients match your search.</h4>
          </div>
      <?php else: ?>
          <div class="nopt-ic" style="margin-top: 40px;"><i class="fas fa-user-injured"></i></div>
          <h3 style="text-align: center;">No Assigned Patients</h3>
          <p style="text-align: center;">You have no active shift or no patients assigned to your ward today.</p>
      <?php endif; ?>
    </div>

    <!-- Patient Banner (shown after selection) -->
    <div class="pt-banner" id="pt-banner" style="display:none">
      <div class="pt-banner-av" id="pt-av">PT</div>
      <div class="pt-banner-info">
        <div class="pt-banner-nm" id="pt-nm">–</div>
        <div class="pt-banner-chips" id="pt-chips"></div>
      </div>
      <div class="pt-banner-ac">
        <button class="pba warn" onclick="openDischargeModal()"><i class="fas fa-bell"></i> Notify Discharge</button>
        <button class="pba sec" onclick="openSearch()"><i class="fas fa-exchange-alt"></i> Change Patient</button>
      </div>
    </div>

    <!-- Main Workspace Layout -->
    <div class="ws-layout" id="ws-layout" style="display:none; margin: 20px 24px;">

      <div class="ws-grid-new">

<!-- 1. Activity Record -->
        <div class="card-new full-width" id="s-act">
          <div class="card-title-new">1. Activity Record</div>
          <div class="split-card card-body" id="f-act">
            <div class="split-left">
              <div class="fg c2">
                <div class="fmg"><label>Activity Type</label><select name="status"><option>Active Treatment</option><option>Discharged</option><option>LAMA</option><option>Referred Out</option></select></div>
                <div class="fmg"><label>Current Ward / Room</label><input type="text" name="ward_room" id="act-wr" placeholder="Enter Ward/Room..."></div>
                <div class="fmg"><label>Admission Date & Time</label><input type="datetime-local" name="adm_date"></div>
                <div class="fmg"><label>Discharge Date & Time</label><input type="datetime-local" name="dis_date"></div>
                <div class="fmg"><label>Primary Consultant</label>
                  <select name="consultant">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach($allDoctors as $doc): ?><option value="<?php echo htmlspecialchars($doc); ?>"><?php echo htmlspecialchars($doc); ?></option><?php endforeach; ?>
                  </select>
                </div>
                <div class="fmg"><label>Reference Doctor</label>
                  <select name="ref_doctor">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach($allDoctors as $doc): ?><option value="<?php echo htmlspecialchars($doc); ?>"><?php echo htmlspecialchars($doc); ?></option><?php endforeach; ?>
                  </select>
                </div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="activity_record" data-f="f-act">+ Add Activity</button>
            </div>
          </div>
        </div>

        
<!-- 2. Vitals (BP Chart) -->
        <div class="card-new" id="s-bp">
          <div class="card-title-new">2. Vitals (BP Chart)</div>
          <div class="split-card card-body" id="f-bp">
            <div class="split-left">
              <div class="fg c2">
                <div class="fmg"><label>Date</label><input type="date" name="bp_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="bp_time"></div>
                <div class="fmg"><label>BP (mmHg)</label><input type="text" name="bp_value" placeholder="e.g. 120/80"></div>
                <div class="fmg"><label>Pulse (bpm)</label><input type="number" name="bp_pulse" placeholder="e.g. 72"></div>
                <div class="fmg"><label>Temp (°F)</label><input type="number" name="bp_temp" step="0.1" placeholder="e.g. 98.6"></div>
                <div class="fmg"><label>SpO2 (%)</label><input type="number" name="bp_spo2" placeholder="e.g. 98"></div>
                <div class="fmg" style="grid-column: 1 / -1"><label>Nurse Name</label><input type="text" name="bp_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="bp_chart" data-f="f-bp">+ Add Vitals</button>
            </div>
            <div class="split-right">
              <div class="ht-title">Recent Vitals</div>
              <div class="ht-wrap"><table class="ht">
                <thead><tr><th>Date & Time</th><th>BP</th><th>Pulse</th><th>Temp</th><th>Recorded By</th></tr></thead>
                <tbody id="h-bp"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
              </table></div>
              <a href="#" class="view-all"><i class="fas fa-eye"></i> View Full Chart</a>
            </div>
          </div>
        </div>

        
<!-- 3. GRBS Chart -->
        <div class="card-new" id="s-gr">
          <div class="card-title-new">3. GRBS Chart</div>
          <div class="split-card card-body" id="f-gr">
            <div class="split-left">
              <div class="fg c2">
                <div class="fmg"><label>Date</label><input type="date" name="grbs_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="grbs_time"></div>
                <div class="fmg"><label>GRBS (mg/dL)</label><input type="number" name="grbs_value" placeholder="e.g. 120"></div>
                <div class="fmg"><label>Nurse Name</label><input type="text" name="grbs_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="grbs_chart" data-f="f-gr">+ Add GRBS</button>
            </div>
            <div class="split-right">
              <div class="ht-title">Recent GRBS</div>
              <div class="ht-wrap"><table class="ht">
                <thead><tr><th>Date & Time</th><th>GRBS (mg/dL)</th><th>Recorded By</th></tr></thead>
                <tbody id="h-gr"><tr class="et"><td colspan="3">No records yet.</td></tr></tbody>
              </table></div>
              <a href="#" class="view-all"><i class="fas fa-eye"></i> View All GRBS</a>
            </div>
          </div>
        </div>

        
<!-- 4. Doctor Visits -->
        <div class="card-new" id="s-vi">
          <div class="card-title-new">4. Doctor Visits</div>
          <div class="split-card card-body" id="f-vi">
            <div class="split-left">
              <div class="fg c2">
                <div class="fmg"><label>Visit Date</label><input type="date" name="date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="time"></div>
                <div class="fmg"><label>Doctor Name</label>
                  <select name="consultant">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach($allDoctors as $doc): ?><option value="<?php echo htmlspecialchars($doc); ?>"><?php echo htmlspecialchars($doc); ?></option><?php endforeach; ?>
                  </select>
                </div>
                <div class="fmg"><label>Shift</label><select name="shift"><option value="">-- Select Shift --</option><option>Morning</option><option>Afternoon</option><option>Evening</option><option>Night</option></select></div>
                <div class="fmg" style="grid-column: 1 / -1"><label>Remarks</label><input type="text" name="remarks" placeholder="Enter doctor remarks..."></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="consultant_visit" data-f="f-vi">+ Add Visit</button>
            </div>
            <div class="split-right">
              <div class="ht-title">Doctor Visit History</div>
              <div class="ht-wrap"><table class="ht">
                <thead><tr><th>Date & Time</th><th>Doctor Name</th><th>Remarks</th><th>Recorded By</th></tr></thead>
                <tbody id="h-vi"><tr class="et"><td colspan="4">No records yet.</td></tr></tbody>
              </table></div>
              <a href="#" class="view-all"><i class="fas fa-eye"></i> View All Visits</a>
            </div>
          </div>
        </div>

        
<!-- 5. Ward Transfer -->
        <div class="card-new" id="s-tr">
          <div class="card-title-new">5. Ward Transfer</div>
          <div class="split-card card-body" id="f-tr">
            <div class="split-left">
              <div class="fg c2">
                <div class="fmg"><label>Transfer Date & Time</label><input type="datetime-local" name="transfer_date"></div>
                <div class="fmg"><label>Reason for Transfer</label><input type="text" name="transfer_remarks" placeholder="Enter reason for transfer..."></div>
                <div class="fmg"><label>From Ward / Room</label>
                  <select name="from_ward">
                    <option value="">-- Select Ward --</option>
                    <?php foreach($allWards as $ward): ?><option value="<?php echo htmlspecialchars($ward); ?>"><?php echo htmlspecialchars($ward); ?></option><?php endforeach; ?>
                  </select>
                </div>
                <div class="fmg"><label>To Ward / Room</label>
                  <select name="to_ward">
                    <option value="">-- Select Ward --</option>
                    <?php foreach($allWards as $ward): ?><option value="<?php echo htmlspecialchars($ward); ?>"><?php echo htmlspecialchars($ward); ?></option><?php endforeach; ?>
                  </select>
                </div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="ward_transfer" data-f="f-tr">+ Add Transfer</button>
            </div>
            <div class="split-right">
              <div class="ht-title">Ward Transfer History</div>
              <div class="ht-wrap"><table class="ht">
                <thead><tr><th>Date & Time</th><th>From</th><th>To</th><th>Reason</th><th>Transferred By</th></tr></thead>
                <tbody id="h-tr"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
              </table></div>
              <a href="#" class="view-all"><i class="fas fa-eye"></i> View All Transfers</a>
            </div>
          </div>
        </div>

        
<!-- 7. Nursing Notes -->
        <div class="card-new" id="s-nn">
          <div class="card-title-new">7. Nursing Notes</div>
          <div class="split-card card-body" id="f-nn">
            <div class="split-left">
              <div class="fg c2">
                <div class="fmg"><label>Date</label><input type="date" name="nurse_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="nurse_time"></div>
                <div class="fmg"><label>Units</label><input type="text" name="nurse_units"></div>
                <div class="fmg"><label>Signature</label><input type="text" name="nurse_sign"></div>
                <div class="fmg" style="grid-column: 1 / -1"><label>Nursing Note</label><input type="text" name="nurse_part" placeholder="Enter nursing notes..."></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="nurse_record" data-f="f-nn">+ Add Note</button>
            </div>
            <div class="split-right">
              <div class="ht-title">Nursing Notes History</div>
              <div class="ht-wrap"><table class="ht">
                <thead><tr><th>Date & Time</th><th>Note</th><th>Recorded By</th></tr></thead>
                <tbody id="h-nn"><tr class="et"><td colspan="3">No records yet.</td></tr></tbody>
              </table></div>
              <a href="#" class="view-all"><i class="fas fa-eye"></i> View All Notes</a>
            </div>
          </div>
        </div>

        
<!-- 8. Tests Order -->
        <div class="card-new" id="s-ts">
          <div class="card-title-new">8. Tests Order</div>
          <div class="split-card card-body" id="f-ts">
            <div class="split-left">
              <div class="fg c2">
                <div class="fmg"><label>Test Name</label><input type="text" id="ts-input" placeholder="-- Select Test --"></div>
                <div class="fmg"><label>Order Date</label><input type="date" name="test_date"></div>
              </div>
              <button class="btn-sv-out btn-sv" id="ts-save-btn" onclick="saveTests()">+ Add Test Order</button>
              <div id="ts-results" style="position:absolute;background:#f3efe6;z-index:10;width:100%;max-height:200px;overflow-y:auto;border:1px solid #ccc;display:none;"></div>
            </div>
            <div class="split-right">
              <div class="ht-title">Tests Order History</div>
              <div class="ht-wrap"><table class="ht">
                <thead><tr><th>Date & Time</th><th>Test Name</th><th>Status</th><th>Ordered By</th></tr></thead>
                <tbody id="h-ts"><tr class="et"><td colspan="4">No records yet.</td></tr></tbody>
              </table></div>
              <a href="#" class="view-all"><i class="fas fa-eye"></i> View All Orders</a>
            </div>
          </div>
        </div>

        
<!-- 9. Pharmacy Order -->
        <div class="card-new" id="s-ph">
          <div class="card-title-new">9. Pharmacy Order</div>
          <div class="split-card card-body" id="f-ph">
            <div class="split-left">
              <div class="fg c2">
                <div class="fmg"><label>Medicine Name</label><input type="text" id="ph-input" placeholder="-- Select Medicine --"></div>
                <div class="fmg"><label>Quantity</label><input type="number" id="ph-qty" placeholder="1"></div>
              </div>
              <button class="btn-sv-out btn-sv" id="ph-save-btn" onclick="savePharmacy()">+ Add Medicine Order</button>
              <div id="ph-results" style="position:absolute;background:#f3efe6;z-index:10;width:100%;max-height:200px;overflow-y:auto;border:1px solid #ccc;display:none;"></div>
            </div>
            <div class="split-right">
              <div class="ht-title">Pharmacy Order History</div>
              <div class="ht-wrap"><table class="ht">
                <thead><tr><th>Date & Time</th><th>Medicine Name</th><th>Qty</th><th>Status</th><th>Ordered By</th></tr></thead>
                <tbody id="h-ph"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
              </table></div>
              <a href="#" class="view-all"><i class="fas fa-eye"></i> View All Orders</a>
            </div>
          </div>
        </div>

        
<!-- 10. Treatments (Mega Card) -->
        <div class="card-new full-width">
          <div class="card-title-new">10. Treatments</div>
          <div class="treatments-grid">
            
            <!-- Nebulization -->
            <div class="t-card card-body" id="f-nb">
              <div class="t-title t-purple"><i class="fas fa-wind"></i> Nebulization</div>
              <div class="fg c1">
                <div class="fmg"><label>Date</label><input type="date" name="nebu_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="nebu_time"></div>
                <div class="fmg"><label>Medicine</label><input type="text" name="nebu_drug"></div>
                <div class="fmg"><label>Route</label><input type="text" name="nebu_route"></div>
                <div class="fmg"><label>Frequency</label><input type="text" name="nebu_freq"></div>
                <div class="fmg"><label>Remarks</label><input type="text" name="nebu_remarks" placeholder="Enter remarks..."></div>
                <div class="fmg"><label>Nurse Name</label><input type="text" name="nebu_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="nebulization_chart" data-f="f-nb">+ Add</button>
              <div class="ht-wrap" style="margin-top:10px;"><table class="ht">
                <thead><tr><th>Recent</th></tr></thead>
                <tbody id="h-nb"><tr class="et"><td>No records.</td></tr></tbody>
              </table></div>
            </div>

            <!-- Dialysis -->
            <div class="t-card card-body" id="f-di">
              <div class="t-title t-blue"><i class="fas fa-filter"></i> Dialysis</div>
              <div class="fg c1">
                <div class="fmg"><label>Date</label><input type="date" name="dia_date"></div>
                <div class="fmg"><label>Start Time</label><input type="time" name="dia_start" class="tcs" onchange="calcDur(this)"></div>
                <div class="fmg"><label>End Time</label><input type="time" name="dia_end" class="tce" onchange="calcDur(this)"></div>
                <div class="fmg"><label>Duration</label><input type="text" name="dia_dur" class="tcd" readonly></div>
                <div class="fmg"><label>Remarks</label><input type="text" name="dia_remarks" placeholder="Enter remarks..."></div>
                <div class="fmg"><label>Nurse Name</label><input type="text" name="dia_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="dialysis_chart" data-f="f-di">+ Add</button>
              <div class="ht-wrap" style="margin-top:10px;"><table class="ht">
                <thead><tr><th>Recent</th></tr></thead>
                <tbody id="h-di"><tr class="et"><td>No records.</td></tr></tbody>
              </table></div>
            </div>

            <!-- Oxygen Therapy -->
            <div class="t-card card-body" id="f-ox">
              <div class="t-title t-green"><i class="fas fa-lungs"></i> Oxygen Therapy</div>
              <div class="fg c1">
                <div class="fmg"><label>Date</label><input type="date" name="oxy_date"></div>
                <div class="fmg"><label>Start Time</label><input type="time" name="oxy_start" class="tcs" onchange="calcDur(this)"></div>
                <div class="fmg"><label>End Time</label><input type="time" name="oxy_end" class="tce" onchange="calcDur(this)"></div>
                <div class="fmg"><label>Duration</label><input type="text" name="oxy_dur" class="tcd" readonly></div>
                <div class="fmg"><label>Flow (L/min)</label><input type="text" name="oxy_flow" placeholder="e.g. 2.5"></div>
                <div class="fmg"><label>Remarks</label><input type="text" name="oxy_remarks" placeholder="Enter remarks..."></div>
                <div class="fmg"><label>Nurse Name</label><input type="text" name="oxy_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="oxygen_chart" data-f="f-ox">+ Add</button>
              <div class="ht-wrap" style="margin-top:10px;"><table class="ht">
                <thead><tr><th>Recent</th></tr></thead>
                <tbody id="h-ox"><tr class="et"><td>No records.</td></tr></tbody>
              </table></div>
            </div>

            <!-- Ventilator -->
            <div class="t-card card-body" id="f-ve">
              <div class="t-title t-orange"><i class="fas fa-procedures"></i> Ventilator</div>
              <div class="fg c1">
                <div class="fmg"><label>Date</label><input type="date" name="vent_date"></div>
                <div class="fmg"><label>Start Time</label><input type="time" name="vent_start" class="tcs" onchange="calcDur(this)"></div>
                <div class="fmg"><label>End Time</label><input type="time" name="vent_end" class="tce" onchange="calcDur(this)"></div>
                <div class="fmg"><label>Duration</label><input type="text" name="vent_dur" class="tcd" readonly></div>
                <div class="fmg"><label>Mode</label><select name="vent_mode"><option>-- Select Mode --</option><option>CMV</option><option>SIMV</option><option>CPAP</option></select></div>
                <div class="fmg"><label>Remarks</label><input type="text" name="vent_remarks" placeholder="Enter remarks..."></div>
                <div class="fmg"><label>Nurse Name</label><input type="text" name="vent_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="ventilation_chart" data-f="f-ve">+ Add</button>
              <div class="ht-wrap" style="margin-top:10px;"><table class="ht">
                <thead><tr><th>Recent</th></tr></thead>
                <tbody id="h-ve"><tr class="et"><td>No records.</td></tr></tbody>
              </table></div>
            </div>

            <!-- Blood Transfusion -->
            <div class="t-card card-body" id="f-bl">
              <div class="t-title t-red"><i class="fas fa-syringe"></i> Blood Transfusion</div>
              <div class="fg c1">
                <div class="fmg"><label>Date</label><input type="date" name="trans_date"></div>
                <div class="fmg"><label>Blood Group</label><input type="text" name="blood_group" placeholder="e.g. O+"></div>
                <div class="fmg"><label>Bag Number</label><input type="text" name="bag_number"></div>
                <div class="fmg"><label>Quantity (ml)</label><input type="number" name="quantity"></div>
                <div class="fmg"><label>Time Started</label><input type="time" name="time_started"></div>
                <div class="fmg"><label>Time Ended</label><input type="time" name="time_ended"></div>
                <div class="fmg"><label>Vitals During Transfusion</label><input type="text" name="vitals_during" placeholder="BP, Pulse..."></div>
                <div class="fmg"><label>Remarks</label><input type="text" name="trans_remarks" placeholder="Enter remarks..."></div>
                <div class="fmg"><label>Nurse Signature</label><input type="text" name="nurse_sign"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="blood_transfusion" data-f="f-bl">+ Add</button>
              <div class="ht-wrap" style="margin-top:10px;"><table class="ht">
                <thead><tr><th>Recent</th></tr></thead>
                <tbody id="h-bl"><tr class="et"><td>No records.</td></tr></tbody>
              </table></div>
            </div>

          </div>
        </div>

<!-- Global Save Button -->
        <div style="background:#f3efe6;padding:20px;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,0.05);display:flex;align-items:center;justify-content:space-between;margin-top:10px;grid-column:1/-1;">
          <div>
            <h3 style="margin:0;font-size:1.05rem;color:var(--tx)">Done filling forms?</h3>
            <p style="margin:4px 0 0 0;font-size:.8rem;color:var(--mt)">Click to save all entered data across all sections at once.</p>
          </div>
          <button id="btn-save-all" class="btn-sv-main" style="font-size:1rem;padding:12px 28px;" onclick="saveAllRecords()">
            <i class="fas fa-save" style="font-size:1.1rem"></i> Save All Entered Records
          </button>
        </div>

      </div><!-- /ws-grid-new -->
    </div><!-- /ws-layout -->
  </div><!-- /content-wrapper -->
</div><!-- /main-layout -->

<!-- Discharge Modal -->
<div id="dis-modal" style="position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);z-index:9000;display:none;align-items:center;justify-content:center">
  <div style="background:#f3efe6;border-radius:16px;max-width:400px;width:90%;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,.2)">
    <div style="background:#f59e0b;padding:16px 20px;font-weight:800;font-size:.95rem;display:flex;align-items:center;gap:8px"><i class="fas fa-exclamation-triangle"></i> Confirm Discharge Notification</div>
    <div style="padding:20px;font-size:.9rem;color:var(--tx);line-height:1.6">Are you sure you want to send a discharge notification to the Admin block?</div>
    <div style="padding:14px 20px;background:#f8fafc;border-top:1px solid var(--bd);display:flex;gap:10px;justify-content:flex-end">
      <button onclick="closeDischargeModal()" style="padding:8px 18px;border-radius:8px;font-weight:700;font-size:.82rem;border:1px solid var(--bd);background:#f3efe6;color:var(--mt);cursor:pointer">Cancel</button>
      <button id="dis-btn" onclick="doDischarge()" style="padding:8px 18px;border-radius:8px;font-weight:700;font-size:.82rem;border:none;background:var(--pd);color:#fff;cursor:pointer">Yes, Notify</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const NN = "<?php echo addslashes($nurseName); ?>";
let cp = null;
let tsCart = [], phCart = [];

/* ── Patient Search / Dashboard ── */
function openSearch(){
  // If changing patient, simply hide the workspace and show the dashboard again!
  document.getElementById('ws-layout').style.display='none';
  document.getElementById('pt-banner').style.display='none';
  document.getElementById('nopt-state').style.display='block';
  cp = null; // Clear current patient
  
  const searchInput = document.getElementById('dash-search');
  if(searchInput) {
      searchInput.value = '';
      searchInput.dispatchEvent(new Event('input')); // Reset filter
      searchInput.focus();
  }
}

// Live filter for the dashboard cards
const ds = document.getElementById('dash-search');
if(ds) {
    ds.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.dash-card');
        let visibleCount = 0;
        cards.forEach(c => {
            const text = c.getAttribute('data-search');
            if(text.includes(q)) {
                c.style.display = 'block';
                visibleCount++;
            } else {
                c.style.display = 'none';
            }
        });
        const noRes = document.getElementById('dash-no-res');
        if(noRes) noRes.style.display = (visibleCount === 0) ? 'block' : 'none';
    });
}

function selectPatient(p){
  cp=p;
  document.getElementById('nopt-state').style.display='none';
  // Update banner
  const ini=((p.first_name||'')[0]||'')+((p.last_name||'')[0]||'')||'PT';
  document.getElementById('pt-av').textContent=ini.toUpperCase();
  document.getElementById('pt-nm').textContent=`${p.first_name} ${p.last_name||''}`;
  document.getElementById('pt-chips').innerHTML=[
    {ic:'fa-id-card',l:'PID',v:p.patient_id},
    {ic:'fa-file-invoice',l:'IP#',v:p.admission_id},
    {ic:'fa-bed',l:'Ward',v:`${p.room_type||'N/A'}/Rm:${p.room_number||'N/A'}`},
    {ic:'fa-user',l:'Age/Sex',v:`${p.age||'?'}Y / ${p.sex||'?'}`},
    {ic:'fa-tint',l:'Blood',v:p.blood_group||'N/A'}
  ].map(c=>`<span class="ptchip"><i class="fas ${c.ic}"></i><strong>${c.l}:</strong> ${c.v}</span>`).join('');
  document.getElementById('pt-banner').style.display='flex';
  document.getElementById('ws-layout').style.display='flex';
  // Pre-fill consultant and ward in activity form
  const ci=document.querySelector('#f-act select[name="consultant"]');
  if(ci&&p.doctor_name){ Array.from(ci.options).forEach(o=>{if(o.value===p.doctor_name)o.selected=true;}); }
  const wi=document.getElementById('act-wr');
  if(wi&&(p.room_type||p.room_number))wi.value=`${p.room_type||''}/${p.room_number||''}`;
  autoFill();
  loadAllRecords();
}


/* ── Menu scroll ── */
function scrollToSection(id){
  const el=document.getElementById(id); if(!el) return;
  el.scrollIntoView({behavior:'smooth',block:'start'});
  // Open section if closed
  const head=el.querySelector('.ac-head');
  const body=el.querySelector('.ac-body');
  if(head&&body&&!head.classList.contains('open')){head.classList.add('open');body.classList.add('open');}
  // Highlight menu item
  document.querySelectorAll('.sec-mn-item').forEach(i=>i.classList.remove('act'));
  const mn=document.getElementById('mn-'+id); if(mn)mn.classList.add('act');
}

/* ── Auto-fill ── */
function autoFill(ctx=document){
  const now=new Date();
  const ym=`${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
  const hm=`${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
  ctx.querySelectorAll('input[type=date]').forEach(i=>{if(!i.value)i.value=ym;});
  ctx.querySelectorAll('input[type=time]').forEach(i=>{if(!i.value)i.value=hm;});
  ctx.querySelectorAll('input[type=datetime-local]').forEach(i=>{if(!i.value)i.value=ym+'T'+hm;});
  if(NN) ctx.querySelectorAll('input[name*="_nurse"],input[name*="_sign"],input[name="nurse_sign"]').forEach(i=>{if(!i.value)i.value=NN;});
}

/* ── Clear form ── */
function clrF(id){
  const c=document.getElementById(id); if(!c)return;
  c.querySelectorAll('input:not([type=hidden]),select,textarea').forEach(e=>e.value='');
  autoFill(c);
}

/* ── Duration auto calc ── */
function calcDur(el){
  const c=el.closest('.card-body');
  const s=c?.querySelector('.tcs'), e=c?.querySelector('.tce'), d=c?.querySelector('.tcd');
  if(!s?.value||!e?.value)return;
  let st=new Date('1970-01-01T'+s.value+':00'), en=new Date('1970-01-01T'+e.value+':00');
  if(en<st)en.setDate(en.getDate()+1);
  const ms=en-st; d.value=Math.floor(ms/3600000)+'h '+Math.round((ms%3600000)/60000)+'m';
}

/* ── Universal Save (Clinical Records) ── */
// Track dirty forms when user types
document.addEventListener('input', e => { const f=e.target.closest('.card-body'); if(f) f.classList.add('is-dirty'); });
document.addEventListener('change', e => { const f=e.target.closest('.card-body'); if(f) f.classList.add('is-dirty'); });

async function saveAllRecords(){
  if(!cp){showToast('Please select a patient first!',true);return;}
  
  const saveTasks = [];
  const results = [];
  const dirtyForms = document.querySelectorAll('.card-body.is-dirty');
  
  dirtyForms.forEach(f => {
    const btn = f.querySelector('.btn-sv[data-ct]');
    if(btn){
      const ct = btn.getAttribute('data-ct');
      const sectionName = ct.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
      const fd = new FormData();
      fd.append('patient_id',cp.patient_id); fd.append('admission_id',cp.admission_id); fd.append('chart_type',ct);
      let i=0; f.querySelectorAll('input:not([type=hidden]),select,textarea').forEach(inp=>{
        const k=inp.name||('f'+i);
        if(inp.type==='file'){ if(inp.files.length>0)fd.append(k,inp.files[0]); }
        else fd.append(k,inp.value);
        i++;
      });
      saveTasks.push(async () => {
        try {
          const r = await fetch('api/save_clinical_record.php',{method:'POST',body:fd});
          const text = await r.text();
          let res;
          try { res = JSON.parse(text); } catch(e) { throw new Error('Invalid server response: ' + text.substring(0, 50)); }
          
          if(res.success) { f.classList.remove('is-dirty'); clrF(f.id); }
          results.push({ section: sectionName, success: res.success, err: res.message });
        } catch(e) { results.push({ section: sectionName, success: false, err: e.message }); }
      });
    }
  });

  if(tsCart.length > 0) {
    saveTasks.push(async () => {
      try {
        const r = await fetch('api/save_tests.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:cp.patient_id,admission_id:cp.admission_id,cart:tsCart})});
        const res = await r.json();
        if(res.success){tsCart=[];renderTestCart();}
        results.push({ section: 'Tests Order', success: res.success, err: res.message });
      } catch(e) { results.push({ section: 'Tests Order', success: false, err: e.message }); }
    });
  }
  if(phCart.length > 0) {
    saveTasks.push(async () => {
      try {
        const r = await fetch('api/save_pharmacy_order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:cp.patient_id,admission_id:cp.admission_id,cart:phCart})});
        const res = await r.json();
        if(res.success){phCart=[];renderPhCart();}
        results.push({ section: 'Pharmacy Order', success: res.success, err: res.message });
      } catch(e) { results.push({ section: 'Pharmacy Order', success: false, err: e.message }); }
    });
  }

  if(saveTasks.length === 0){ showToast('No new data entered to save.', true); return; }

  const btn = document.getElementById('btn-save-all');
  const oh = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving All...'; btn.disabled = true;

  try {
    for (const task of saveTasks) { await task(); }
    loadAllRecords();
    
    // Build summary message
    const successCount = results.filter(r => r.success).length;
    const failedCount = results.length - successCount;
    
    let msg = ``;
    if(successCount > 0) msg += `✅ Saved: ` + results.filter(r => r.success).map(r => r.section).join(', ') + `. <br><br>`;
    if(failedCount > 0) {
      msg += `❌ Failed:<br>` + results.filter(r => !r.success).map(r => `- ${r.section}: ${r.err || 'Unknown error'}`).join('<br>');
    }
    
    showToast(msg, failedCount > 0);
  } catch(e) { 
    showToast('Network error while saving.',true); 
  }
  finally { btn.innerHTML = oh; btn.disabled = false; }
}

/* ── Load All Records ── */
async function loadAllRecords(){
  if(!cp)return;
  try{
    const r=await fetch(`api/get_clinical_records.php?patient_id=${cp.patient_id}&admission_id=${cp.admission_id}`);
    const d=(await r.json())?.data||{};
    rH('h-tr',d.bed_trasfer||d.ward_transfer||[],r=>`<td>${r.transfer_date||r.date||r.created_date||''}</td><td>${r.from_ward||''}</td><td>${r.to_ward||''}</td><td>${r.transfer_remarks||r.remarks||''}</td>`,4);
    rH('h-vi',d.consultant_visits||[],r=>`<td>${r.date||r.created_date||''}</td><td>${r.time||''}</td><td>${r.consultant||''}</td><td>${r.shift||''}</td>`,4);
    rH('h-gr',d.grbs_chart||[],r=>`<td>${r.grbs_date||r.created_date||''}</td><td>${r.grbs_time||''}</td><td><strong>${r.grbs_value||''} mg/dL</strong></td><td>${r.grbs_nurse||''}</td>`,4);
    rH('h-nb',d.nebulization_chart||[],r=>`<td>${r.nebu_date||r.created_date||''}</td><td>${r.nebu_time||''}</td><td>${r.nebu_drug||''}</td><td>${r.nebu_route||''}</td><td>${r.nebu_freq||''}</td><td>${r.nebu_nurse||''}</td>`,6);
    rH('h-bp',d.bp_test||d.bp_chart||[],r=>`<td>${r.bp_date||r.created_date||''}</td><td>${r.bp_time||''}</td><td><strong>${r.bp_value||((r.bp_systolic||'')+'/'+(r.bp_diastolic||''))}</strong></td><td>${r.bp_pulse||''} bpm</td><td>${r.bp_temp||''}</td><td>${r.bp_spo2||''}%</td><td>${r.bp_nurse||''}</td>`,7);
    rH('h-di',d.dialysis_chart||[],r=>`<td>${r.dia_date||r.created_date||''}</td><td>${r.dia_start||''}</td><td>${r.dia_end||''}</td><td>${r.dia_dur||''}</td><td>${r.dia_nurse||''}</td>`,5);
    rH('h-ox',d.oxygen_chart||[],r=>`<td>${r.oxy_date||r.created_date||''}</td><td>${r.oxy_start||''}</td><td>${r.oxy_end||''}</td><td>${r.oxy_dur||''}</td><td>${r.oxy_flow||''}</td><td>${r.oxy_nurse||''}</td>`,6);
    rH('h-ve',d.ventilation_chart||[],r=>`<td>${r.vent_date||r.created_date||''}</td><td>${r.vent_start||''}</td><td>${r.vent_end||''}</td><td>${r.vent_dur||''}</td><td>${r.vent_nurse||''}</td>`,5);
    rH('h-bl',d.blood_transfusion_chart||[],r=>`<td>${r.trans_date||r.date||r.created_date||''}</td><td>${r.bag_number||''}</td><td>${r.quantity||''} ml</td><td>${r.time_started||''}</td><td>${r.time_ended||''}</td><td>${r.vitals_during||''}</td>`,6);
    rH('h-nn',d.nurses_record||[],r=>`<td>${r.nurse_date||r.date||''}</td><td>${r.nurse_time||r.time||''}</td><td>${r.nurse_part||r.particulars||''}</td><td>${r.nurse_units||r.units||''}</td><td>${r.nurse_sign||r.signature||''}</td>`,5);
    // Tests history
    let at=[];
    (d.lab_tests||[]).forEach(t=>{const i=t.data||t;at.push({dt:t.created_date||'',nm:i.name||i.test_name||'',cat:'LAB',qty:i.qty||1,by:t.created_by||''});});
    (d.radiology_tests||[]).forEach(t=>{const i=t.data||t;at.push({dt:t.created_date||'',nm:i.name||i.test_name||'',cat:'RADIOLOGY',qty:i.qty||1,by:t.created_by||''});});
    (d.other_tests||[]).forEach(t=>{const i=t.data||t;at.push({dt:t.created_date||'',nm:i.name||i.test_name||'',cat:'OTHER',qty:i.qty||1,by:t.created_by||''});});
    at.sort((a,b)=>new Date(b.dt)-new Date(a.dt));
    document.getElementById('h-ts').innerHTML=at.length?at.map(t=>`<tr><td>${t.dt}</td><td><strong>${t.nm}</strong></td><td><span class="badge ${t.cat==='LAB'?'bl':t.cat==='RADIOLOGY'?'br':'bo'}">${t.cat}</span></td><td>${t.qty}</td><td>${t.by}</td></tr>`).join(''):'<tr class="et"><td colspan="5">No tests ordered yet.</td></tr>';
    // Pharmacy history
    const phHistory=d.pharmacy_orders||[];
    document.getElementById('h-ph').innerHTML=phHistory.length?phHistory.map(o=>{
      const i=o.data||o;
      return `<tr><td>${o.created_date||i.date||''}</td><td>${i.medicine||i.name||''}</td><td>${i.batch||''}</td><td>${i.qty||''}</td><td>${o.created_by||i.dispensed_by||''}</td></tr>`;
    }).join(''):'<tr class="et"><td colspan="5">No pharmacy orders yet.</td></tr>';
  }catch(er){console.error('loadAllRecords:',er);}
}

function rH(tid,rows,fn,cols){
  const tb=document.getElementById(tid); if(!tb)return;
  tb.innerHTML=rows&&rows.length?[...rows].reverse().map(r=>`<tr>${fn(r)}</tr>`).join(''):`<tr class="et"><td colspan="${cols}">No records yet.</td></tr>`;
}

/* ── Tests Cart ── */
let tsT=null;
document.getElementById('ts-input').addEventListener('input',function(){
  clearTimeout(tsT); const q=this.value.trim(), res=document.getElementById('ts-results');
  if(q.length<2){res.style.display='none';return;}
  tsT=setTimeout(()=>{
    fetch('api/search_tests.php?type=all&q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{
      res.innerHTML='';
      if(d.success&&d.data.length>0){
        d.data.forEach(item=>{
          const cl=item.category?.toLowerCase().includes('lab')?'bl':item.category==='Other'?'bo':'br';
          const el=document.createElement('div'); el.className='ts-item';
          el.innerHTML=`<div><strong>${item.name}</strong><br><small style="color:var(--mt)">Cat: ${item.category}</small></div><span class="badge ${cl}">${item.category}</span>`;
          el.onclick=()=>addToTestCart(item); res.appendChild(el);
        });
      } else { res.innerHTML='<div style="padding:12px;text-align:center;color:var(--mt);font-size:.82rem">No tests found.</div>'; }
      res.style.display='block';
    });
  },280);
});
document.addEventListener('click',e=>{if(!document.getElementById('ts-input').contains(e.target)&&!document.getElementById('ts-results').contains(e.target))document.getElementById('ts-results').style.display='none';});

function addToTestCart(item){
  document.getElementById('ts-results').style.display='none'; document.getElementById('ts-input').value='';
  const ex=tsCart.find(x=>x.id===item.id);
  if(ex)ex.qty++; else tsCart.push({id:item.id,name:item.name,category:item.category,qty:1});
  renderTestCart();
}
function renderTestCart(){
  const ca=document.getElementById('ts-cart');
  if(!tsCart.length){ca.innerHTML='<p style="color:var(--mt);font-size:.82rem;text-align:center;padding:12px;background:#f8fafc;border-radius:8px">No tests added yet.</p>';return;}
  ca.innerHTML=tsCart.map(t=>`<div class="cart-row"><div class="cart-row-n">${t.name} <span class="badge ${t.category?.toLowerCase().includes('lab')?'bl':t.category==='Other'?'bo':'br'}">${t.category}</span></div><input type="number" value="${t.qty}" min="1" onchange="tsCart.find(x=>x.id==='${t.id}').qty=parseInt(this.value)||1;renderTestCart()"><button class="rm-btn" onclick="tsCart=tsCart.filter(x=>x.id!=='${t.id}');renderTestCart()"><i class="fas fa-times"></i></button></div>`).join('');
}
async function saveTests(){
  if(!cp){showToast('No patient selected!',true);return;}
  if(!tsCart.length){showToast('Add at least one test.',true);return;}
  const b=document.getElementById('ts-save-btn'); b.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving...'; b.disabled=true;
  try{
    const r=await fetch('api/save_tests.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:cp.patient_id,admission_id:cp.admission_id,cart:tsCart})});
    const res=await r.json();
    if(res.success){showToast('Tests saved!');tsCart=[];renderTestCart();loadAllRecords();}
    else showToast('Error: '+(res.message||'Unknown'),true);
  }catch{showToast('Network error!',true);}
  finally{b.innerHTML='<i class="fas fa-save"></i> Save Test Order';b.disabled=false;}
}

/* ── Pharmacy Cart ── */
let phT=null;
document.getElementById('ph-input').addEventListener('input',function(){
  clearTimeout(phT); const q=this.value.trim(), res=document.getElementById('ph-results');
  if(q.length<2){res.style.display='none';return;}
  phT=setTimeout(()=>{
    fetch('api/search_medicine.php?q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{
      res.innerHTML='';
      const items=d.data||d.medicines||d||[];
      if(Array.isArray(items)&&items.length>0){
        items.forEach(item=>{
          const el=document.createElement('div'); el.className='ph-item';
          el.innerHTML=`<div class="ph-item-n">${item.name||item.medicine_name||item.product_name}</div><div class="ph-item-m">Batch: ${item.batch_number||'N/A'} | Stock: ${item.quantity||item.stock||item.available_stock||'?'}</div>`;
          el.onclick=()=>addToPhCart(item); res.appendChild(el);
        });
      } else { res.innerHTML='<div style="padding:12px;text-align:center;color:var(--mt);font-size:.82rem">No medicines found.</div>'; }
      res.style.display='block';
    });
  },280);
});
document.addEventListener('click',e=>{if(!document.getElementById('ph-input').contains(e.target)&&!document.getElementById('ph-results').contains(e.target))document.getElementById('ph-results').style.display='none';});

function addToPhCart(item){
  document.getElementById('ph-results').style.display='none'; document.getElementById('ph-input').value='';
  const id=item.id||item.medicine_id||item.product_id;
  const ex=phCart.find(x=>x.id===id);
  if(ex)ex.qty++; else phCart.push({id,name:item.name||item.medicine_name||item.product_name,batch:item.batch_number||'',stock:item.quantity||item.stock||item.available_stock||'?',qty:1});
  renderPhCart();
}
function renderPhCart(){
  const ca=document.getElementById('ph-cart');
  if(!phCart.length){ca.innerHTML='<p style="color:var(--mt);font-size:.82rem;text-align:center;padding:12px;background:#f8fafc;border-radius:8px">No medicines added yet.</p>';return;}
  ca.innerHTML=phCart.map(m=>`<div class="cart-row"><div class="cart-row-n">${m.name} <span class="badge bg">Batch: ${m.batch||'N/A'}</span></div><input type="number" value="${m.qty}" min="1" onchange="phCart.find(x=>x.id==='${m.id}').qty=parseInt(this.value)||1;renderPhCart()"><button class="rm-btn" onclick="phCart=phCart.filter(x=>x.id!=='${m.id}');renderPhCart()"><i class="fas fa-times"></i></button></div>`).join('');
}
async function savePharmacy(){
  if(!cp){showToast('No patient selected!',true);return;}
  if(!phCart.length){showToast('Add at least one medicine.',true);return;}
  const b=document.getElementById('ph-save-btn'); b.innerHTML='<i class="fas fa-spinner fa-spin"></i> Submitting...'; b.disabled=true;
  try{
    const r=await fetch('api/save_pharmacy_order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:cp.patient_id,admission_id:cp.admission_id,items:phCart})});
    const res=await r.json();
    if(res.success){showToast('Pharmacy order submitted!');phCart=[];renderPhCart();loadAllRecords();}
    else showToast('Error: '+(res.message||'Unknown'),true);
  }catch{showToast('Network error!',true);}
  finally{b.innerHTML='<i class="fas fa-save"></i> Submit Pharmacy Order';b.disabled=false;}
}

/* ── Discharge ── */
function openDischargeModal(){document.getElementById('dis-modal').style.display='flex';}
function closeDischargeModal(){document.getElementById('dis-modal').style.display='none';}
async function doDischarge(){
  if(!cp)return;
  const b=document.getElementById('dis-btn'); b.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; b.disabled=true;
  const fd=new FormData(); fd.append('patient_id',cp.patient_id); fd.append('admission_id',cp.admission_id);
  try{const r=await fetch('api/send_discharge_notification.php',{method:'POST',body:fd});const res=await r.json();showToast(res.success?(res.message||'Notification sent!'):'Error: '+res.message,!res.success);}
  catch{showToast('Network error!',true);}
  finally{b.innerHTML='Yes, Notify';b.disabled=false;closeDischargeModal();}
}

/* ── Toast ── */
function showToast(msg,err=false){
  const t=document.getElementById('toast');
  t.innerHTML=msg; t.style.background=err?'#EF4444':'#10B981';
  t.style.display='block'; clearTimeout(t._t); t._t=setTimeout(()=>t.style.display='none',5000);
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded',() => {
    <?php if (empty($assignedPatients)): ?>
        openSearch();
    <?php endif; ?>
});

// Bind individual save buttons
document.querySelectorAll('.btn-sv[data-ct]').forEach(b => {
  b.addEventListener('click', () => {
    const f = document.getElementById(b.dataset.f);
    if(f) f.classList.add('is-dirty');
    saveAllRecords();
  });
});

$(document).ready(function() {
    $('select[name="consultant"], select[name="ref_doctor"]').select2({
        placeholder: "-- Search Doctor --",
        allowClear: true,
        width: '100%'
    });
    $('select[name="from_ward"], select[name="to_ward"]').select2({
        placeholder: "-- Search Ward --",
        allowClear: true,
        width: '100%'
    });
    
    // Make Select2 trigger the dirty state
    $('select').on('change', function() {
        const f = this.closest('.card-body');
        if(f) f.classList.add('is-dirty');
    });
});
</script>
</body>
</html>
