<?php
$pageTitle = 'Machine Status';
$pageIcon  = 'fa-server';
$navTitle  = 'Machine Dashboard';
$navSub    = 'Analyzer status, health monitoring and maintenance';
require_once 'includes/lab_head.php';
?>
<?php require_once 'includes/lab_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/lab_navbar.php'; ?>

<div class="lis-content">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up">
    <div>
      <div class="lis-page-title">
        <div class="lis-page-title-icon"><i class="fas fa-server"></i></div>
        <div>
          Machine Dashboard
          <div class="lis-page-subtitle">Analyzer status, workload and maintenance schedule</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <span class="lis-breadcrumb-pill"><i class="fas fa-sync-alt" style="font-size:0.5rem;"></i> Updated <?= date('H:i') ?></span>
      <button class="lis-btn lis-btn-outline" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
  </div>

  <!-- Status Summary -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;" class="lis-fade-up-1">
    <?php
    $summary = [
      ['Running',     3, 'c-emerald', 'fa-circle-check', '#059669'],
      ['Idle',        1, 'c-amber',   'fa-pause-circle',  '#d97706'],
      ['Maintenance', 1, 'c-red',     'fa-wrench',        '#dc2626'],
      ['Offline',     0, 'c-slate',   'fa-power-off',     '#64748b'],
    ];
    foreach ($summary as [$status, $count, $cls, $icon, $color]):
    ?>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon <?= $cls ?>" style="width:40px;height:40px;font-size:1rem;"><i class="fas <?= $icon ?>"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" style="font-size:1.6rem;"><?= $count ?></div>
        <div class="lis-kpi-label"><?= $status ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Machine Cards Grid -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px;" class="lis-fade-up-2">
    <?php
    $machines = [
      [
        'name'       => 'Hematology Analyzer',
        'model'      => 'Sysmex XN-1000',
        'status'     => 'running',
        'load'       => 78,
        'tests_today'=> 124,
        'uptime'     => '99.2%',
        'last_service'=> '15 Jul 2026',
        'next_service'=> '15 Oct 2026',
        'icon'       => 'fa-tint',
      ],
      [
        'name'       => 'Biochemistry Analyzer',
        'model'      => 'Roche Cobas c311',
        'status'     => 'running',
        'load'       => 62,
        'tests_today'=> 98,
        'uptime'     => '98.7%',
        'last_service'=> '1 Jul 2026',
        'next_service'=> '1 Oct 2026',
        'icon'       => 'fa-flask',
      ],
      [
        'name'       => 'Urine Analyzer',
        'model'      => 'Sysmex UC-3500',
        'status'     => 'idle',
        'load'       => 15,
        'tests_today'=> 21,
        'uptime'     => '97.5%',
        'last_service'=> '10 Jun 2026',
        'next_service'=> '10 Sep 2026',
        'icon'       => 'fa-vial',
      ],
      [
        'name'       => 'Coagulation Analyzer',
        'model'      => 'Stago STA-R Max',
        'status'     => 'running',
        'load'       => 45,
        'tests_today'=> 56,
        'uptime'     => '99.8%',
        'last_service'=> '20 Jul 2026',
        'next_service'=> '20 Oct 2026',
        'icon'       => 'fa-heartbeat',
      ],
      [
        'name'       => 'Immunoassay Analyzer',
        'model'      => 'Abbott Architect i1000sr',
        'status'     => 'maintenance',
        'load'       => 0,
        'tests_today'=> 0,
        'uptime'     => '94.1%',
        'last_service'=> '01 Aug 2026',
        'next_service'=> '01 Aug 2026',
        'icon'       => 'fa-shield-alt',
      ],
      [
        'name'       => 'Digital X-Ray',
        'model'      => 'Philips DigitalDiagnost C90',
        'status'     => 'running',
        'load'       => 55,
        'tests_today'=> 44,
        'uptime'     => '98.0%',
        'last_service'=> '5 Jul 2026',
        'next_service'=> '5 Jan 2027',
        'icon'       => 'fa-x-ray',
      ],
    ];
    $statusColors = [
      'running'     => '#059669',
      'idle'        => '#d97706',
      'maintenance' => '#dc2626',
      'offline'     => '#64748b',
    ];
    $loadColors = [
      'running'     => '#1f6b4a',
      'idle'        => '#d97706',
      'maintenance' => '#dc2626',
      'offline'     => '#94a3b8',
    ];
    foreach ($machines as $m):
      $statusColor = $statusColors[$m['status']] ?? '#64748b';
      $loadColor   = $loadColors[$m['status']]   ?? '#94a3b8';
    ?>
    <div class="lis-machine-card lis-card-hover">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div class="lis-kpi-icon c-green" style="width:44px;height:44px;">
          <i class="fas <?= $m['icon'] ?>"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:0.85rem;font-weight:800;color:var(--lis-text);"><?= htmlspecialchars($m['name']) ?></div>
          <div style="font-size:0.68rem;color:var(--lis-text-muted);"><?= htmlspecialchars($m['model']) ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:5px;">
          <div class="lis-machine-status-dot <?= $m['status'] ?>"></div>
          <span style="font-size:0.68rem;font-weight:700;color:<?= $statusColor ?>;text-transform:capitalize;"><?= $m['status'] ?></span>
        </div>
      </div>

      <!-- Load Bar -->
      <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
          <span style="font-size:0.68rem;font-weight:700;color:var(--lis-text-muted);">Current Load</span>
          <span style="font-size:0.72rem;font-weight:800;color:<?= $loadColor ?>;"><?= $m['load'] ?>%</span>
        </div>
        <div class="lis-progress-bar-wrap">
          <div class="lis-progress-bar-fill" style="width:<?= $m['load'] ?>%;background:<?= $loadColor ?>;"></div>
        </div>
      </div>

      <!-- Stats -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;">
        <div class="lis-price-cell">
          <span class="label">Tests Today</span>
          <span class="value"><?= $m['tests_today'] ?></span>
        </div>
        <div class="lis-price-cell">
          <span class="label">Uptime</span>
          <span class="value" style="color:var(--lis-success);"><?= $m['uptime'] ?></span>
        </div>
        <div class="lis-price-cell">
          <span class="label">Last Service</span>
          <span class="value" style="font-size:0.68rem;"><?= $m['last_service'] ?></span>
        </div>
        <div class="lis-price-cell">
          <span class="label">Next Service</span>
          <span class="value" style="font-size:0.68rem;<?= ($m['status']==='maintenance'?'color:var(--lis-danger);':'')?>"><?= $m['next_service'] ?></span>
        </div>
      </div>

      <!-- Action -->
      <div style="display:flex;gap:6px;">
        <?php if ($m['status'] === 'maintenance'): ?>
        <button class="lis-btn lis-btn-warning lis-btn-sm" style="flex:1;">
          <i class="fas fa-wrench"></i> Maintenance Mode
        </button>
        <?php elseif ($m['status'] === 'idle'): ?>
        <button class="lis-btn lis-btn-success lis-btn-sm" style="flex:1;">
          <i class="fas fa-play"></i> Activate
        </button>
        <?php else: ?>
        <button class="lis-btn lis-btn-outline lis-btn-sm" style="flex:1;">
          <i class="fas fa-info-circle"></i> Details
        </button>
        <?php endif; ?>
        <button class="lis-btn lis-btn-ghost lis-btn-sm lis-btn-icon">
          <i class="fas fa-ellipsis-v"></i>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Maintenance Schedule -->
  <div class="lis-card lis-fade-up-3">
    <div class="lis-card-header">
      <div class="lis-card-title"><i class="fas fa-calendar-alt"></i> Maintenance Schedule</div>
    </div>
    <div class="lis-table-wrap">
      <table class="lis-table">
        <thead>
          <tr>
            <th>Machine</th>
            <th>Model</th>
            <th>Last Service</th>
            <th>Next Service</th>
            <th>Status</th>
            <th>Technician</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($machines as $m): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="lis-machine-status-dot <?= $m['status'] ?>"></div>
                <strong style="font-size:0.82rem;"><?= htmlspecialchars($m['name']) ?></strong>
              </div>
            </td>
            <td style="font-size:0.75rem;color:var(--lis-text-muted);"><?= htmlspecialchars($m['model']) ?></td>
            <td><span class="lis-code"><?= $m['last_service'] ?></span></td>
            <td><span class="lis-code"><?= $m['next_service'] ?></span></td>
            <td>
              <?php if ($m['status'] === 'running'): ?>
                <span class="lis-badge lis-badge-completed">Operational</span>
              <?php elseif ($m['status'] === 'idle'): ?>
                <span class="lis-badge lis-badge-ordered">Standby</span>
              <?php elseif ($m['status'] === 'maintenance'): ?>
                <span class="lis-badge lis-badge-urgent">Under Maintenance</span>
              <?php else: ?>
                <span class="lis-badge" style="background:#f1f5f9;color:#64748b;">Offline</span>
              <?php endif; ?>
            </td>
            <td style="font-size:0.78rem;">Lab Engineering Team</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?php require_once 'includes/lab_foot.php'; ?>
