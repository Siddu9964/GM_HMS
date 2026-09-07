<?php
$pageTitle = 'Radiology Performance Analytics';
$pageIcon  = 'fa-chart-pie';
$navTitle  = 'Imaging Analytics';
$navSub    = 'Operational performance, scanner utilization, and report turnaround statistics';
require_once 'includes/rad_head.php';
?>
<?php require_once 'includes/rad_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/rad_navbar.php'; ?>

<div class="lis-content">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up">
    <div>
      <div class="lis-page-title">
        <div class="lis-page-title-icon" style="background:linear-gradient(135deg,var(--lis-primary),#0284c7);color:white;border:none;">
          <i class="fas fa-chart-pie"></i>
        </div>
        <div>
          Radiology Performance & TAT Analytics
          <div class="lis-page-subtitle">Equipment utilization, scan-to-report turnaround times, and radiologist productivity</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <select id="analytics-period" class="lis-input lis-select" style="width:auto;" onchange="loadAnalyticsData()">
        <option value="today">Today</option>
        <option value="week">This Week</option>
        <option value="month" selected>This Month</option>
      </select>
      <button class="lis-btn lis-btn-outline" onclick="loadAnalyticsData()">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
    </div>
  </div>

  <!-- Performance Cards -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;" class="lis-fade-up-1">
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon teal" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-stopwatch"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" style="font-size:1.6rem;color:#0284c7;">38 min</div>
        <div class="lis-kpi-label">Median Scan-to-Report TAT</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-red" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-bolt"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" style="font-size:1.6rem;color:#dc2626;">12 min</div>
        <div class="lis-kpi-label">STAT Trauma Scan TAT</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-emerald" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-check-circle"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" style="font-size:1.6rem;color:#059669;">96.8%</div>
        <div class="lis-kpi-label">Reports within SLA</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon" style="background:#fae8ff;color:#a855f7;width:40px;height:40px;font-size:1rem;"><i class="fas fa-redo"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" style="font-size:1.6rem;color:#a855f7;">0.4%</div>
        <div class="lis-kpi-label">Repeat Exposure Rate</div>
      </div>
    </div>
  </div>

  <!-- Operational Grid -->
  <div class="lis-grid-2 lis-fade-up-2">
    <!-- TAT Distribution -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-clock"></i> Report Turnaround Time by Modality</div>
      </div>
      <div class="lis-card-body" style="padding:20px;">
        <div style="margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:0.85rem;">
            <span>Digital X-Ray (Target &lt; 45 min)</span>
            <strong>22 min (98% on-time)</strong>
          </div>
          <div class="lis-progress-bar"><div class="lis-progress-fill" style="width:98%;background:#0284c7;"></div></div>
        </div>
        <div style="margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:0.85rem;">
            <span>CT Scans (Target &lt; 90 min)</span>
            <strong>54 min (94% on-time)</strong>
          </div>
          <div class="lis-progress-bar"><div class="lis-progress-fill" style="width:94%;background:#d97706;"></div></div>
        </div>
        <div style="margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:0.85rem;">
            <span>Ultrasound & Doppler (Target &lt; 30 min)</span>
            <strong>18 min (99% on-time)</strong>
          </div>
          <div class="lis-progress-bar"><div class="lis-progress-fill" style="width:99%;background:#16a34a;"></div></div>
        </div>
      </div>
    </div>

    <!-- Scanner Capacity -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-tachometer-alt"></i> Scanner Capacity & Utilization</div>
      </div>
      <div class="lis-card-body" style="padding:20px;">
        <div style="margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:0.85rem;">
            <span>Digital X-Ray (Room 102)</span>
            <strong>68% utilization</strong>
          </div>
          <div class="lis-progress-bar"><div class="lis-progress-fill" style="width:68%;background:var(--lis-primary);"></div></div>
        </div>
        <div style="margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:0.85rem;">
            <span>CT Scanner (Room 101)</span>
            <strong>48% utilization</strong>
          </div>
          <div class="lis-progress-bar"><div class="lis-progress-fill" style="width:48%;background:#0284c7;"></div></div>
        </div>
        <div style="margin-bottom:16px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:0.85rem;">
            <span>Ultrasound & Doppler (Room 103)</span>
            <strong>55% utilization</strong>
          </div>
          <div class="lis-progress-bar"><div class="lis-progress-fill" style="width:55%;background:#16a34a;"></div></div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

<script>
function loadAnalyticsData() {
  radToast('Analytics refreshed for selected period', 'info');
}
</script>

<?php require_once 'includes/rad_foot.php'; ?>
