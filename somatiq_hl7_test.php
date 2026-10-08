<?php
/**
 * Somatiq HL7 Integration — Frontend Test Page
 * ─────────────────────────────────────────────
 * Open this in browser: http://localhost/GM_HMS/somatiq_hl7_test.php
 *
 * ⚠️  DELETE THIS FILE after testing in production!
 */

// ── Basic auth check (only admins) ────────────────────────────────────────────
session_start();
if (empty($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin', 'administrator', 'radiologist'])) {
    // Allow access if not logged in (for initial setup testing)
    // Remove this block in production
}

$result   = null;
$hl7Msg   = null;
$testMode = $_POST['test_mode'] ?? 'config';   // config | build | send

// ── Run test on form submit ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_once __DIR__ . '/core/Autoloader.php';

    $configPath = __DIR__ . '/config/somatiq_hl7.php';
    $config     = file_exists($configPath) ? require $configPath : [];

    // ── TEST 1: Config Check ──────────────────────────────────────────────────
    if ($testMode === 'config') {
        $result = [
            'step'   => 'Config Check',
            'checks' => [
                'config_file_exists' => file_exists($configPath),
                'host'               => $config['host']    ?? 'MISSING',
                'port'               => $config['port']    ?? 'MISSING',
                'timeout'            => $config['timeout'] ?? 'MISSING',
                'hl7_version'        => $config['hl7_version'] ?? 'MISSING',
                'sending_application'=> $config['sending_application'] ?? 'MISSING',
                'sending_facility'   => $config['sending_facility'] ?? 'MISSING',
                'receiving_application' => $config['receiving_application'] ?? 'MISSING',
                'openssl_loaded'     => extension_loaded('openssl'),
                'logs_writable'      => is_writable(__DIR__ . '/logs/'),
                'hl7builder_exists'  => file_exists(__DIR__ . '/modules/Radiology/Services/HL7Builder.php'),
                'mllpclient_exists'  => file_exists(__DIR__ . '/modules/Radiology/Services/MLLPClient.php'),
                'somatiqservice_exists' => file_exists(__DIR__ . '/modules/Radiology/Services/SomatiqHL7Service.php'),
            ],
        ];
    }

    // ── TEST 2: Build HL7 Message (no network call) ───────────────────────────
    elseif ($testMode === 'build') {
        try {
            require_once __DIR__ . '/modules/Radiology/Services/HL7Builder.php';

            $builder = new \GM_HMS\Modules\Radiology\Services\HL7Builder($config);

            $hl7Msg = $builder->buildOrder(
                [
                    'id'            => $_POST['patient_id']   ?? 'PID-20261007-001',
                    'name'          => $_POST['patient_name'] ?? 'Test Patient',
                    'date_of_birth' => $_POST['dob']          ?? '1990-01-01',
                    'sex'           => $_POST['sex']          ?? 'M',
                    'phone'         => $_POST['phone']        ?? '9999999999',
                ],
                [
                    'id'             => 'ORD-TEST-' . date('YmdHis'),
                    'procedure_code' => $_POST['proc_code']   ?? 'RDS171',
                    'procedure_name' => $_POST['proc_name']   ?? 'CT SCAN OF BRAIN',
                    'modality'       => $_POST['modality']    ?? 'CT',
                    'priority'       => $_POST['priority']    ?? 'R',
                ],
                [
                    'type'             => $_POST['visit_type'] ?? 'O',
                    'referring_doctor' => $_POST['doctor']     ?? 'Dr. Test Doctor',
                ]
            );

            $result = [
                'step'    => 'HL7 Message Build',
                'success' => true,
                'message' => 'HL7 ORM^O01 message built successfully!',
                'hl7'     => $hl7Msg,
            ];
        } catch (\Throwable $e) {
            $result = [
                'step'    => 'HL7 Message Build',
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ── TEST 3: Full Send to Somatiq (real network call) ─────────────────────
    elseif ($testMode === 'send') {
        try {
            require_once __DIR__ . '/modules/Radiology/Services/HL7Builder.php';
            require_once __DIR__ . '/modules/Radiology/Services/MLLPClient.php';
            require_once __DIR__ . '/modules/Radiology/Services/SomatiqHL7Service.php';

            $builder = new \GM_HMS\Modules\Radiology\Services\HL7Builder($config);

            $hl7Msg = $builder->buildOrder(
                [
                    'id'            => $_POST['patient_id']   ?? 'PID-TEST-001',
                    'name'          => $_POST['patient_name'] ?? 'Test Patient',
                    'date_of_birth' => $_POST['dob']          ?? '1990-01-01',
                    'sex'           => $_POST['sex']          ?? 'M',
                    'phone'         => $_POST['phone']        ?? '9999999999',
                ],
                [
                    'id'             => 'ORD-TEST-' . date('YmdHis'),
                    'procedure_code' => $_POST['proc_code'] ?? 'RDS171',
                    'procedure_name' => $_POST['proc_name'] ?? 'CT SCAN OF BRAIN',
                    'modality'       => $_POST['modality']  ?? 'CT',
                    'priority'       => $_POST['priority']  ?? 'R',
                ],
                [
                    'type'             => $_POST['visit_type'] ?? 'O',
                    'referring_doctor' => $_POST['doctor']     ?? 'Dr. Test Doctor',
                ]
            );

            $client = new \GM_HMS\Modules\Radiology\Services\MLLPClient(
                $config['host'],
                $config['port'],
                $config['timeout']
            );

            $ack     = $client->send($hl7Msg);
            $success = (bool) preg_match('/MSA\|AA\|/', $ack);
            $isError = (bool) preg_match('/MSA\|AE\|/', $ack);

            $result = [
                'step'    => 'Full HL7 Send to Somatiq',
                'success' => $success,
                'ack'     => $ack,
                'message' => $success
                    ? '✅ Somatiq ACCEPTED the order! (ACK: AA)'
                    : ($isError
                        ? '❌ Somatiq REJECTED the order (ACK: AE) — check your CENTRE_CODE'
                        : '⚠️ Response received but ACK status unclear'),
                'hl7'     => $hl7Msg,
            ];
        } catch (\Throwable $e) {
            $result = [
                'step'    => 'Full HL7 Send to Somatiq',
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage(),
                'hl7'     => $hl7Msg,
            ];
        }
    }
}

// ── Read log file ─────────────────────────────────────────────────────────────
$logFile  = __DIR__ . '/logs/somatiq_hl7.log';
$logLines = [];
if (file_exists($logFile)) {
    $all      = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logLines = array_reverse(array_slice($all, -20)); // last 20 entries, newest first
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Somatiq HL7 — Integration Test | GM_HMS</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=JetBrains+Mono:wght@400;600&display=swap">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;padding:30px 20px}
.wrap{max-width:900px;margin:0 auto}

/* Header */
.page-header{background:linear-gradient(135deg,#1f6b4a 0%,#0284c7 100%);border-radius:16px;padding:28px 32px;margin-bottom:28px;display:flex;align-items:center;gap:18px}
.page-icon{width:56px;height:56px;background:rgba(255,255,255,.18);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.6rem}
.page-title{font-size:1.6rem;font-weight:900;color:white}
.page-sub{font-size:.82rem;color:rgba(255,255,255,.8);margin-top:4px}
.warn-badge{background:#ef4444;color:white;font-size:.7rem;font-weight:800;padding:3px 10px;border-radius:20px;margin-left:10px;vertical-align:middle}

/* Cards */
.card{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:24px;margin-bottom:22px}
.card-title{font-size:1rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.card-title i{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem}

/* Tabs */
.tabs{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:24px}
.tab-btn{background:#1e293b;border:1.5px solid #334155;color:#94a3b8;padding:14px 10px;border-radius:12px;font-family:'Inter',sans-serif;font-size:.82rem;font-weight:700;cursor:pointer;text-align:center;transition:all .2s}
.tab-btn:hover{border-color:#0284c7;color:#38bdf8}
.tab-btn.active{background:linear-gradient(135deg,#1f6b4a,#0284c7);border-color:transparent;color:white}
.tab-num{display:block;font-size:1.6rem;font-weight:900;line-height:1;margin-bottom:4px}

/* Form */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
label{font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em}
input,select{background:#0f172a;border:1.5px solid #334155;color:#e2e8f0;padding:10px 14px;border-radius:8px;font-family:'Inter',sans-serif;font-size:.85rem;outline:none;transition:border .2s}
input:focus,select:focus{border-color:#0284c7}
select option{background:#1e293b}

/* Buttons */
.btn-row{display:flex;gap:10px;margin-top:18px}
.btn{padding:11px 24px;border-radius:10px;font-family:'Inter',sans-serif;font-size:.85rem;font-weight:700;cursor:pointer;border:none;transition:all .2s;display:inline-flex;align-items:center;gap:8px}
.btn-primary{background:linear-gradient(135deg,#1f6b4a,#0284c7);color:white}
.btn-primary:hover{opacity:.88;transform:translateY(-1px)}
.btn-danger{background:#ef4444;color:white}
.btn-outline{background:transparent;border:1.5px solid #334155;color:#94a3b8}

/* Result panels */
.result-panel{margin-top:20px;border-radius:12px;overflow:hidden}
.result-header{padding:14px 18px;font-size:.85rem;font-weight:800;display:flex;align-items:center;gap:8px}
.result-success .result-header{background:#14532d;color:#86efac}
.result-error   .result-header{background:#450a0a;color:#fca5a5}
.result-warn    .result-header{background:#422006;color:#fed7aa}
.result-body{background:#0f172a;padding:16px 18px}

/* Check grid */
.check-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.check-item{background:#0f172a;border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center}
.check-label{font-size:.78rem;color:#94a3b8;font-weight:600}
.check-val{font-size:.8rem;font-weight:700;padding:3px 10px;border-radius:20px}
.val-ok  {background:#14532d;color:#86efac}
.val-fail{background:#450a0a;color:#fca5a5}
.val-info{background:#1e3a5f;color:#7dd3fc}

/* HL7 viewer */
.hl7-box{background:#020617;border:1px solid #1e3a5f;border-radius:10px;padding:16px;font-family:'JetBrains Mono',monospace;font-size:.78rem;line-height:1.8;overflow-x:auto;white-space:pre;color:#7dd3fc;max-height:280px;overflow-y:auto}
.seg-MSH{color:#fbbf24}
.seg-PID{color:#86efac}
.seg-PV1{color:#c4b5fd}
.seg-ORC{color:#f9a8d4}
.seg-OBR{color:#67e8f9}

/* Log */
.log-entry{background:#0f172a;border-left:3px solid #334155;border-radius:0 8px 8px 0;padding:10px 14px;margin-bottom:8px;font-family:'JetBrains Mono',monospace;font-size:.72rem;color:#94a3b8;word-break:break-all}
.log-ok  {border-color:#22c55e;color:#86efac}
.log-fail{border-color:#ef4444;color:#fca5a5}
.log-empty{color:#475569;font-size:.82rem;padding:16px;text-align:center}
</style>
</head>
<body>
<div class="wrap">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-icon">📡</div>
    <div>
      <div class="page-title">
        Somatiq HL7 Integration Test
        <span class="warn-badge">DEV ONLY</span>
      </div>
      <div class="page-sub">Test your HL7 v2.3 ORM^O01 connection to Somatiq RIS — delete this file before production</div>
    </div>
  </div>

  <!-- Tab Selector -->
  <div class="tabs">
    <button class="tab-btn <?= $testMode==='config'?'active':'' ?>" onclick="setTab('config')">
      <span class="tab-num">1</span>Config Check
    </button>
    <button class="tab-btn <?= $testMode==='build'?'active':'' ?>" onclick="setTab('build')">
      <span class="tab-num">2</span>Build HL7 Message
    </button>
    <button class="tab-btn <?= $testMode==='send'?'active':'' ?>" onclick="setTab('send')">
      <span class="tab-num">3</span>Send to Somatiq
    </button>
  </div>

  <!-- ── FORM ─────────────────────────────────────────────────────────── -->
  <form method="POST">
    <input type="hidden" name="test_mode" id="test_mode" value="<?= htmlspecialchars($testMode) ?>">

    <!-- Step 1: Config Check -->
    <div class="card" id="tab-config" <?= $testMode!=='config'?'style="display:none"':'' ?>>
      <div class="card-title">
        <div class="card-icon" style="background:#164e63;color:#38bdf8">⚙</div>
        Step 1 — Check Configuration & Environment
      </div>
      <p style="color:#64748b;font-size:.85rem;line-height:1.6;margin-bottom:16px">
        This checks that all required files exist, OpenSSL is enabled, and your <code style="color:#38bdf8">config/somatiq_hl7.php</code> is correctly set up.
      </p>
      <div class="btn-row">
        <button class="btn btn-primary" type="submit">▶ Run Config Check</button>
      </div>
    </div>

    <!-- Step 2: Build HL7 -->
    <div class="card" id="tab-build" <?= $testMode!=='build'?'style="display:none"':'' ?>>
      <div class="card-title">
        <div class="card-icon" style="background:#1e3a5f;color:#7dd3fc">🏗</div>
        Step 2 — Build HL7 Message (No Network Call)
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Patient ID</label>
          <input name="patient_id" value="<?= $_POST['patient_id']??'PID-20261007-001' ?>">
        </div>
        <div class="form-group">
          <label>Patient Name</label>
          <input name="patient_name" value="<?= $_POST['patient_name']??'JINI THOMAS' ?>">
        </div>
        <div class="form-group">
          <label>Date of Birth (YYYY-MM-DD)</label>
          <input name="dob" type="date" value="<?= $_POST['dob']??'1990-01-01' ?>">
        </div>
        <div class="form-group">
          <label>Sex</label>
          <select name="sex">
            <option value="M" <?= ($_POST['sex']??'M')==='M'?'selected':'' ?>>Male (M)</option>
            <option value="F" <?= ($_POST['sex']??'')==='F'?'selected':'' ?>>Female (F)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input name="phone" value="<?= $_POST['phone']??'9980506871' ?>">
        </div>
        <div class="form-group">
          <label>Procedure Code</label>
          <input name="proc_code" value="<?= $_POST['proc_code']??'RDS171' ?>">
        </div>
        <div class="form-group">
          <label>Procedure Name</label>
          <input name="proc_name" value="<?= $_POST['proc_name']??'CT SCAN OF BRAIN' ?>">
        </div>
        <div class="form-group">
          <label>Modality</label>
          <select name="modality">
            <?php foreach(['CT','MRI','X-RAY','USG','DOPPLER','PET','NM'] as $m): ?>
            <option value="<?=$m?>" <?= ($_POST['modality']??'CT')===$m?'selected':'' ?>><?=$m?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Priority</label>
          <select name="priority">
            <option value="R" <?= ($_POST['priority']??'R')==='R'?'selected':'' ?>>Routine (R)</option>
            <option value="S" <?= ($_POST['priority']??'')==='S'?'selected':'' ?>>Stat / Urgent (S)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Visit Type</label>
          <select name="visit_type">
            <option value="O" <?= ($_POST['visit_type']??'O')==='O'?'selected':'' ?>>Outpatient (O)</option>
            <option value="I" <?= ($_POST['visit_type']??'')==='I'?'selected':'' ?>>Inpatient (I)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Referring Doctor</label>
          <input name="doctor" value="<?= $_POST['doctor']??'Dr. Test Doctor' ?>">
        </div>
      </div>
      <div class="btn-row">
        <button class="btn btn-primary" type="submit">🏗 Build HL7 Message</button>
      </div>
    </div>

    <!-- Step 3: Full Send -->
    <div class="card" id="tab-send" <?= $testMode!=='send'?'style="display:none"':'' ?>>
      <div class="card-title">
        <div class="card-icon" style="background:#14532d;color:#86efac">📡</div>
        Step 3 — Send Real HL7 to Somatiq RIS
      </div>
      <div style="background:#450a0a;border:1px solid #ef4444;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:.82rem;color:#fca5a5">
        ⚠️ This sends a <strong>REAL message</strong> to Somatiq's server. Use test patient data only!
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Patient ID</label>
          <input name="patient_id" value="<?= $_POST['patient_id']??'PID-20260930-020' ?>">
        </div>
        <div class="form-group">
          <label>Patient Name</label>
          <input name="patient_name" value="<?= $_POST['patient_name']??'JINI' ?>">
        </div>
        <div class="form-group">
          <label>Date of Birth</label>
          <input name="dob" type="date" value="<?= $_POST['dob']??'1900-01-01' ?>">
        </div>
        <div class="form-group">
          <label>Sex</label>
          <select name="sex">
            <option value="F" <?= ($_POST['sex']??'F')==='F'?'selected':'' ?>>Female (F)</option>
            <option value="M" <?= ($_POST['sex']??'')==='M'?'selected':'' ?>>Male (M)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input name="phone" value="<?= $_POST['phone']??'9980506871' ?>">
        </div>
        <div class="form-group">
          <label>Procedure Code</label>
          <input name="proc_code" value="<?= $_POST['proc_code']??'RDS171' ?>">
        </div>
        <div class="form-group">
          <label>Procedure Name</label>
          <input name="proc_name" value="<?= $_POST['proc_name']??'CT SCAN OF BRAIN' ?>">
        </div>
        <div class="form-group">
          <label>Modality</label>
          <select name="modality">
            <?php foreach(['CT','MRI','X-RAY','USG','DOPPLER','PET','NM'] as $m): ?>
            <option value="<?=$m?>" <?= ($_POST['modality']??'CT')===$m?'selected':'' ?>><?=$m?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Priority</label>
          <select name="priority">
            <option value="R">Routine (R)</option>
            <option value="S">Stat (S)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Visit Type</label>
          <select name="visit_type">
            <option value="O">Outpatient (O)</option>
            <option value="I">Inpatient (I)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Referring Doctor</label>
          <input name="doctor" value="<?= $_POST['doctor']??'Dr Sanjeet SB' ?>">
        </div>
      </div>
      <div class="btn-row">
        <button class="btn btn-danger" type="submit">📡 Send to Somatiq RIS</button>
      </div>
    </div>
  </form>

  <!-- ── RESULT OUTPUT ─────────────────────────────────────────────────── -->
  <?php if ($result): ?>
  <div class="card">
    <div class="card-title">📊 Test Result — <?= htmlspecialchars($result['step']) ?></div>

    <?php if ($testMode === 'config'): ?>
      <div class="check-grid">
        <?php
        $labels = [
          'config_file_exists'    => 'Config file exists',
          'host'                  => 'Somatiq Host',
          'port'                  => 'MLLP Port',
          'timeout'               => 'Timeout (s)',
          'hl7_version'           => 'HL7 Version',
          'sending_application'   => 'Vendor Code (MSH-3)',
          'sending_facility'      => 'Centre Code (MSH-4)',
          'receiving_application' => 'Receiver (MSH-5)',
          'openssl_loaded'        => 'PHP OpenSSL',
          'logs_writable'         => 'logs/ Writable',
          'hl7builder_exists'     => 'HL7Builder.php',
          'mllpclient_exists'     => 'MLLPClient.php',
          'somatiqservice_exists' => 'SomatiqHL7Service.php',
        ];
        foreach ($result['checks'] as $key => $val):
            $isBool = is_bool($val);
            $isOk   = $isBool ? $val : !in_array($val, ['MISSING', false, null], true);
            $display = $isBool ? ($val ? '✅ YES' : '❌ NO') : htmlspecialchars((string)$val);
            $cls     = $isBool ? ($val ? 'val-ok' : 'val-fail') : 'val-info';
            if ($key === 'sending_facility' && $val === 'DEMO') {
                $display = '⚠️ DEMO — Change to production CENTRE_CODE';
                $cls = 'val-fail';
            }
        ?>
        <div class="check-item">
          <span class="check-label"><?= $labels[$key] ?? $key ?></span>
          <span class="check-val <?= $cls ?>"><?= $display ?></span>
        </div>
        <?php endforeach; ?>
      </div>

    <?php elseif (isset($result['success'])): ?>
      <div class="result-panel <?= $result['success'] ? 'result-success' : 'result-error' ?>">
        <div class="result-header">
          <?= $result['success'] ? '✅' : '❌' ?>
          <?= htmlspecialchars($result['message'] ?? '') ?>
        </div>
        <?php if (!empty($result['ack'])): ?>
        <div class="result-body">
          <div style="font-size:.72rem;color:#64748b;margin-bottom:8px;font-weight:700;text-transform:uppercase">Raw ACK from Somatiq:</div>
          <div class="hl7-box"><?= htmlspecialchars(str_replace("\r", "\r\n", $result['ack'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($result['hl7'] ?? $hl7Msg)): ?>
    <div style="margin-top:18px">
      <div style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase;margin-bottom:8px">HL7 ORM^O01 Message Sent:</div>
      <div class="hl7-box"><?php
        $raw = $result['hl7'] ?? $hl7Msg;
        $lines = explode("\r", $raw);
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $seg  = substr($line, 0, 3);
            $cls  = 'seg-' . $seg;
            echo '<span class="' . $cls . '">' . htmlspecialchars($line) . '</span>' . "\n";
        }
      ?></div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ── LIVE LOG ────────────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-title" style="justify-content:space-between">
      <span>📋 Live Log — Last 20 Entries <small style="font-weight:400;text-transform:none">(logs/somatiq_hl7.log)</small></span>
      <a href="?clear_log=1" style="color:#ef4444;font-size:.72rem;font-weight:700;text-decoration:none">🗑 Clear Log</a>
    </div>
    <?php
    // Clear log action
    if (isset($_GET['clear_log']) && file_exists($logFile)) {
        file_put_contents($logFile, '');
        echo '<script>location.href=location.href.split("?")[0]</script>';
    }
    if (empty($logLines)):
    ?>
    <div class="log-empty">No log entries yet. Place a radiology order to see HL7 dispatch logs here.</div>
    <?php else:
      foreach ($logLines as $line):
        $decoded = json_decode($line, true);
        $isOk    = ($decoded['success'] ?? false);
        $cls     = $isOk ? 'log-ok' : 'log-fail';
    ?>
    <div class="log-entry <?= $cls ?>">
      <?php if ($decoded): ?>
        <strong><?= htmlspecialchars($decoded['ts'] ?? '') ?></strong>
        | Order: <strong><?= htmlspecialchars($decoded['order_id'] ?? '?') ?></strong>
        | Patient: <?= htmlspecialchars($decoded['patient'] ?? '?') ?>
        | <?= $isOk ? '✅ ACCEPTED' : '❌ FAILED' ?>
        <?php if (!empty($decoded['error'])): ?> | Error: <?= htmlspecialchars($decoded['error']) ?><?php endif; ?>
        <?php if (!empty($decoded['message'])): ?> | <?= htmlspecialchars($decoded['message']) ?><?php endif; ?>
      <?php else: ?>
        <?= htmlspecialchars($line) ?>
      <?php endif; ?>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <p style="text-align:center;color:#334155;font-size:.75rem;margin-top:10px">
    ⚠️ Delete <code>somatiq_hl7_test.php</code> from your server before going live in production.
  </p>

</div>

<script>
function setTab(tab) {
  document.getElementById('test_mode').value = tab;
  ['config','build','send'].forEach(t => {
    document.getElementById('tab-'+t).style.display = t === tab ? 'block' : 'none';
  });
  document.querySelectorAll('.tab-btn').forEach((btn, i) => {
    btn.classList.toggle('active', ['config','build','send'][i] === tab);
  });
}
</script>
</body>
</html>
