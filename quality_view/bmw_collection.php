<?php
$pageTitle = 'BMW Waste Collection Log';
$pageIcon  = 'fa-biohazard';
$pageDesc  = 'Record daily ward-level biomedical waste collection and weight segregation';
require_once __DIR__ . '/includes/quality_head.php';

// Fetch distinct room types from hospital_beds table
$bedRoomTypes = [];
try {
    require_once __DIR__ . '/../core/Autoloader.php';
    require_once __DIR__ . '/../modules/Quality/Repositories/BMWRepository.php';
    $bmwRepo = new \GM_HMS\Modules\Quality\Repositories\BMWRepository();
    $bedRoomTypes = $bmwRepo->getDistinctRoomTypes();
} catch (\Throwable $e) {
    error_log('Failed to fetch room types: ' . $e->getMessage());
    $bedRoomTypes = ['ICU', 'Emergency Room', 'General Ward', 'CCU', 'Private Room', 'Semi Private Room', 'Deluxe Room'];
}
?>
<?php require_once __DIR__ . '/includes/quality_sidebar.php'; ?>

<!-- Advance Search Select2 Styles -->
<style>
.select2-container--default .select2-selection--single {
  height: 42px !important;
  border: 1.5px solid var(--qsc-border-light) !important;
  border-radius: 8px !important;
  padding: 6px 12px !important;
  background-color: #ffffff !important;
  display: flex !important;
  align-items: center !important;
  transition: border-color 0.2s, box-shadow 0.2s !important;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
  border-color: var(--qsc-green) !important;
  box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.15) !important;
  outline: none !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  color: #1e293b !important;
  font-weight: 600 !important;
  font-size: 0.88rem !important;
  line-height: normal !important;
  padding-left: 0 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 40px !important;
  right: 8px !important;
}
.select2-dropdown {
  border: 1.5px solid var(--qsc-green) !important;
  border-radius: 8px !important;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
  z-index: 100055 !important;
  overflow: hidden !important;
}
.select2-search--dropdown {
  padding: 8px !important;
  background: #f8fafc !important;
  border-bottom: 1px solid var(--qsc-border-light) !important;
}
.select2-search--dropdown .select2-search__field {
  border: 1.5px solid var(--qsc-border-light) !important;
  border-radius: 6px !important;
  padding: 7px 10px !important;
  font-size: 0.85rem !important;
  font-weight: 500 !important;
}
.select2-search--dropdown .select2-search__field:focus {
  border-color: var(--qsc-green) !important;
  outline: none !important;
}
.select2-results__group {
  font-size: 0.72rem !important;
  font-weight: 800 !important;
  color: var(--qsc-green-deep) !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  background: #f1f5f9 !important;
  padding: 6px 12px !important;
}
.select2-results__option {
  padding: 8px 14px !important;
  font-size: 0.86rem !important;
  font-weight: 500 !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
  background-color: var(--qsc-green) !important;
  color: #ffffff !important;
}
.select2-container--default .select2-results__option[aria-selected=true] {
  background-color: var(--qsc-green-faint) !important;
  color: var(--qsc-green-deep) !important;
  font-weight: 700 !important;
}

/* ── MOCKUP EXACT STYLING (Biomedical Waste Collection & Disposal) ── */
.modal-mockup-content {
  background-color: #faf8f4 !important;
  border-radius: 22px !important;
  border: 1px solid #eae5db !important;
  box-shadow: 0 25px 60px rgba(15, 45, 30, 0.18) !important;
  font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, sans-serif !important;
  overflow: hidden;
}

.modal-mockup-header {
  background: #faf8f4 !important;
  border-bottom: none !important;
  padding: 24px 32px 10px 32px !important;
}

.modal-mockup-title {
  color: #0f3422 !important;
  font-weight: 800 !important;
  font-size: 1.55rem !important;
  letter-spacing: -0.4px !important;
}

/* Nav Tabs matching image */
.mockup-tab-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  padding: 0;
  margin: 0;
}

.mockup-tab-nav .nav-link {
  background: #ffffff;
  color: #334155;
  border: 1px solid #e2e8f0 !important;
  border-radius: 12px !important;
  padding: 8px 18px !important;
  font-weight: 600 !important;
  font-size: 0.88rem !important;
  transition: all 0.2s ease;
}

.mockup-tab-nav .nav-link:hover {
  background: #f1f5f9;
  color: #0f3422;
}

.mockup-tab-nav .nav-link.active {
  background: #34d399 !important;
  background: linear-gradient(135deg, #4ade80, #22c55e) !important;
  color: #052e16 !important;
  border-color: #22c55e !important;
  font-weight: 800 !important;
  box-shadow: 0 0 18px rgba(34, 197, 94, 0.55), 0 4px 10px rgba(34, 197, 94, 0.3) !important;
}

/* Sub-header */
.mockup-subheader-title {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0f3422;
  margin-bottom: 2px;
}
.mockup-subheader-meta {
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
}

/* 5 Bins Row */
.mockup-bins-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
}

.mockup-bin-card {
  border-radius: 18px;
  padding: 14px 12px 14px 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  min-height: 295px;
  text-align: center;
  position: relative;
  transition: transform 0.2s, box-shadow 0.2s;
}
.mockup-bin-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.06);
}

/* Card 1: Yellow */
.mockup-bin-card.card-yellow {
  background: #fefce8;
  border: 1.5px solid #fef08a;
}
.mockup-bin-card.card-yellow .mockup-bin-pill {
  background: #facc15;
  color: #713f12;
  box-shadow: 0 0 12px rgba(250, 204, 21, 0.4);
}

/* Card 2: Red */
.mockup-bin-card.card-red {
  background: #fff1f2;
  border: 1.5px solid #fecdd3;
}
.mockup-bin-card.card-red .mockup-bin-pill {
  background: #ef4444;
  color: #ffffff;
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
}

/* Card 3: Blue */
.mockup-bin-card.card-blue {
  background: #eff6ff;
  border: 1.5px solid #bfdbfe;
}
.mockup-bin-card.card-blue .mockup-bin-pill {
  background: #3b82f6;
  color: #ffffff;
  box-shadow: 0 0 12px rgba(59, 130, 246, 0.4);
}

/* Card 4: White */
.mockup-bin-card.card-white {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
}
.mockup-bin-card.card-white .mockup-bin-pill {
  background: #ffffff;
  color: #334155;
  border: 1px solid #cbd5e1;
}

/* Card 5: Green */
.mockup-bin-card.card-green {
  background: #f0fdf4;
  border: 1.5px solid #bbf7d0;
}
.mockup-bin-card.card-green .mockup-bin-pill {
  background: #22c55e;
  color: #ffffff;
  box-shadow: 0 0 14px rgba(34, 197, 94, 0.5);
}

.mockup-bin-pill {
  width: 85%;
  padding: 4px 10px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 0.82rem;
  letter-spacing: 0.2px;
  margin-bottom: 10px;
}

.mockup-bin-icon {
  font-size: 2.1rem;
  margin-bottom: 6px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.mockup-bin-subtitle {
  font-size: 0.72rem;
  font-weight: 700;
  line-height: 1.25;
  color: #475569;
  min-height: 28px;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Clean User-Friendly Bin Input Box */
.user-friendly-input-card {
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 14px;
  padding: 8px 10px;
  display: flex;
  align-items: baseline;
  justify-content: center;
  width: 100%;
  margin: 12px 0 8px 0;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);
  transition: all 0.2s ease;
}
.card-yellow .user-friendly-input-card:focus-within {
  border-color: #eab308;
  box-shadow: 0 0 0 3.5px rgba(234, 179, 8, 0.25);
  background: #ffffff;
}
.card-red .user-friendly-input-card:focus-within {
  border-color: #ef4444;
  box-shadow: 0 0 0 3.5px rgba(239, 68, 68, 0.25);
  background: #ffffff;
}
.card-blue .user-friendly-input-card:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3.5px rgba(59, 130, 246, 0.25);
  background: #ffffff;
}
.card-white .user-friendly-input-card:focus-within {
  border-color: #94a3b8;
  box-shadow: 0 0 0 3.5px rgba(148, 163, 184, 0.25);
  background: #ffffff;
}
.card-green .user-friendly-input-card:focus-within {
  border-color: #22c55e;
  box-shadow: 0 0 0 3.5px rgba(34, 197, 94, 0.25);
  background: #ffffff;
}
.user-friendly-input-field {
  font-size: 2.1rem;
  font-weight: 800;
  color: #0f172a;
  border: none;
  background: transparent;
  text-align: center;
  width: 100%;
  padding: 0;
  outline: none;
  line-height: 1;
  font-family: inherit;
}
.user-friendly-input-unit {
  font-size: 0.95rem;
  font-weight: 800;
  color: #64748b;
  margin-left: 3px;
  user-select: none;
}
.card-has-value {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

/* Bin Statutory Purpose Box */
.bin-purpose-box {
  width: 100%;
  margin-top: 10px;
  padding: 8px 8px;
  background: rgba(255, 255, 255, 0.75);
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.bin-purpose-title {
  font-size: 0.73rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}
.bin-purpose-desc {
  font-size: 0.7rem;
  color: #475569;
  font-weight: 600;
  line-height: 1.3;
}

/* Bottom collection activity card */
.mockup-activity-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 16px 22px;
}
.mockup-progress-track {
  width: 100%;
  height: 12px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
  margin: 8px 0;
}
.mockup-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7);
  border-radius: 999px;
  box-shadow: 0 0 12px rgba(52, 211, 153, 0.6);
  transition: width 0.3s ease;
}

.btn-mockup-discard {
  background: #ffffff !important;
  color: #334155 !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 12px !important;
  font-weight: 700 !important;
  font-size: 0.9rem !important;
  padding: 10px 24px !important;
  transition: all 0.2s;
}
.btn-mockup-discard:hover {
  background: #f8fafc !important;
}

.btn-mockup-submit {
  background: #0f3422 !important;
  color: #ffffff !important;
  border: none !important;
  border-radius: 12px !important;
  font-weight: 800 !important;
  font-size: 0.9rem !important;
  padding: 10px 28px !important;
  box-shadow: 0 4px 14px rgba(15, 52, 34, 0.25) !important;
  transition: all 0.2s;
}
.btn-mockup-submit:hover {
  background: #174e33 !important;
  transform: translateY(-1px);
}

/* Right Sidebar */
.mockup-sidebar-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 18px 16px;
  height: 100%;
}
.mockup-side-title {
  font-size: 0.92rem;
  font-weight: 800;
  color: #0f3422;
  margin-bottom: 12px;
}
.recent-entry-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.78rem;
  padding: 6px 0;
  border-bottom: 1px dashed #e2e8f0;
}
.recent-entry-row:last-child {
  border-bottom: none;
}

.bin-overview-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.82rem;
  font-weight: 600;
  padding: 6px 0;
  color: #334155;
}
.bin-overview-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 8px;
}

/* Tab Card Styling */
.tab-step-card {
  background: #ffffff;
  border: 1px solid var(--qsc-border-light);
  border-radius: 16px;
  padding: 22px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

.review-summary-table thead th {
  background: #f8fafc;
  color: #475569;
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 8px 12px;
}
.review-summary-table tbody td {
  padding: 10px 14px;
  font-size: 0.88rem;
  vertical-align: middle;
}
</style>

<div class="qsc-main">
<?php require_once __DIR__ . '/includes/quality_navbar.php'; ?>

<div class="qsc-content">

  <!-- Filter & Action Bar -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-2">
      <input type="date" id="filter-date-from" class="form-control form-control-sm" style="width:145px;" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
      <span class="text-muted" style="font-size:0.8rem;">to</span>
      <input type="date" id="filter-date-to"   class="form-control form-control-sm" style="width:145px;" value="<?= date('Y-m-d') ?>">
      <select id="filter-status" class="form-select form-select-sm" style="width:135px;">
        <option value="">All Statuses</option>
        <option value="Collected">Collected</option>
        <option value="Dispatched">Dispatched</option>
        <option value="Completed">Completed</option>
      </select>
      <button class="btn-qsc-outline" onclick="loadRecords()"><i class="fas fa-filter"></i> Filter</button>
    </div>
    <button class="btn-qsc-primary" data-bs-toggle="modal" data-bs-target="#collectionModal">
      <i class="fas fa-plus"></i> Log Waste Collection
    </button>
  </div>

  <!-- Table Card -->
  <div class="qsc-card">
    <div class="qsc-card-header">
      <span class="qsc-card-title"><i class="fas fa-list" style="color:var(--qsc-green);margin-right:6px;"></i>Waste Collection Records</span>
      <span id="total-badge" class="badge" style="background:var(--qsc-green);font-size:0.8rem;"></span>
    </div>
    <div class="qsc-card-body p-0">
      <div class="table-responsive">
        <table id="collectionTable" class="table table-hover mb-0" style="font-size:0.85rem;">
          <thead>
            <tr>
              <th class="ps-3">#</th>
              <th>Date & Time</th>
              <th>Location</th>
              <th><span class="bin-badge bin-green"><i class="fas fa-trash-can me-1"></i>Green</span></th>
              <th><span class="bin-badge bin-yellow"><i class="fas fa-trash-can me-1"></i>Yellow</span></th>
              <th><span class="bin-badge bin-red"><i class="fas fa-trash-can me-1"></i>Red</span></th>
              <th><span class="bin-badge bin-blue"><i class="fas fa-trash-can me-1"></i>Blue</span></th>
              <th><span class="bin-badge bin-white"><i class="fas fa-trash-can me-1"></i>White</span></th>
              <th>H. Total</th>
              <th>Status</th>
              <th>Logged By</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="collection-tbody">
            <tr><td colspan="12" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading records…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /.qsc-content -->
</div><!-- /.qsc-main -->

<!-- ══ Modal 1: Create / Edit Collection Modal (Matching Attached Mockup) ═════════════════════ -->
<div class="modal fade" id="collectionModal" tabindex="-1" aria-labelledby="collectionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1260px; width: 96vw;">
    <form id="collectionForm" class="modal-content modal-mockup-content">
      
      <!-- Header matching mockup -->
      <div class="modal-header modal-mockup-header d-flex align-items-center justify-content-between pb-2">
        <div>
          <h4 class="modal-mockup-title mb-0" id="collectionModalLabel">
            <i class="fas fa-biohazard me-2 text-success"></i>Biomedical Waste Collection & Disposal
          </h4>
          <div class="text-muted" style="font-size:0.8rem;margin-top:2px;">
            Hospital Segregation Registry &bull; Real-time Calibrated Weights
          </div>
        </div>
        <div class="d-flex align-items-center gap-3">
          <!-- Shift / Team Dropdown -->
          <select id="f-team-filter" class="form-select form-select-sm fw-semibold" style="border-radius:10px;background:#ffffff;border:1px solid #cbd5e1;padding:6px 14px;font-size:0.82rem;color:#1e293b;">
            <option value="All Teams">All Teams</option>
            <option value="Morning Shift">Morning Shift</option>
            <option value="Evening Shift">Evening Shift</option>
            <option value="Night Shift">Night Shift</option>
            <option value="ICU Dedicated">ICU Dedicated</option>
          </select>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="opacity:0.6;"></button>
        </div>
      </div>

      <div class="modal-body px-4 pb-4 pt-2" style="background:#faf8f4;">
        <input type="hidden" id="form-record-id" value="">

        <!-- ── TOP SECTION: Location, Time & Notes Strip (No tab clicks required!) ── -->
        <div class="p-3 bg-white rounded-3 border mb-3 shadow-sm" style="border-color:#e2e8f0!important;">
          <div class="row g-3 align-items-center">
            
            <!-- Location Dropdown (from hospital_beds) -->
            <div class="col-md-5">
              <label class="form-label mb-1 fw-bold text-uppercase d-flex align-items-center justify-content-between" style="font-size:0.75rem;letter-spacing:0.4px;color:#0f3422;">
                <span><i class="fas fa-location-dot text-danger me-1"></i> Ward / Location *</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;">Bed Master</span>
              </label>
              <select id="f-location" class="form-select fw-semibold" style="width:100%;" required>
                <option value="">-- Search or Select Room Type / Ward --</option>
                <optgroup label="Room Types (from Hospital Beds)">
                  <?php foreach ($bedRoomTypes as $rt): ?>
                    <option value="<?= htmlspecialchars($rt) ?>" <?= $rt === 'General Ward' ? 'selected' : '' ?>><?= htmlspecialchars($rt) ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="Other Hospital Areas">
                  <option value="OT Complex">OT Complex</option>
                  <option value="Dialysis Unit">Dialysis Unit</option>
                  <option value="Labour Ward">Labour Ward</option>
                  <option value="Central Laboratory">Central Laboratory</option>
                  <option value="ICU">ICU</option>
                  <option value="OPD Clinic">OPD Clinic</option>
                </optgroup>
              </select>
            </div>

            <!-- Collection Timestamp (IST) -->
            <div class="col-md-4">
              <label class="form-label mb-1 fw-bold text-uppercase" style="font-size:0.75rem;letter-spacing:0.4px;color:#0f3422;">
                <i class="far fa-clock text-success me-1"></i> Collection Time (IST) *
              </label>
              <div class="d-flex gap-2">
                <input type="datetime-local" id="f-collection-at" class="form-control fw-semibold" style="font-size:0.88rem;" required>
                <button type="button" class="btn btn-outline-success btn-sm px-2 fw-bold text-nowrap" onclick="setCurrentDateTime()" title="Set to Now (IST)">
                  <i class="fas fa-bolt"></i> Now
                </button>
              </div>
            </div>

            <!-- Notes / Remarks -->
            <div class="col-md-3">
              <label class="form-label mb-1 fw-bold text-uppercase" style="font-size:0.75rem;letter-spacing:0.4px;color:#0f3422;">
                <i class="far fa-comment-dots text-primary me-1"></i> Remarks / Notes
              </label>
              <input type="text" id="f-remarks" class="form-control" style="font-size:0.88rem;" placeholder="Shift / Bag tags…">
            </div>

          </div>

          <!-- Quick Room Type Chips Row -->
          <div class="d-flex flex-wrap align-items-center gap-1 mt-2 pt-2 border-top">
            <span class="text-muted fw-bold me-1" style="font-size:0.7rem;text-transform:uppercase;">Quick Ward:</span>
            <div class="qsc-chip-group d-inline-flex flex-wrap gap-1" id="dept-chips" style="margin:0;">
              <?php foreach ($bedRoomTypes as $chipRt): ?>
                <span class="qsc-chip" style="font-size:0.75rem;padding:3px 10px;" onclick="selectLocationChip(this, '<?= addslashes($chipRt) ?>')"><?= htmlspecialchars($chipRt) ?></span>
              <?php endforeach; ?>
              <span class="qsc-chip" style="font-size:0.75rem;padding:3px 10px;" onclick="selectLocationChip(this, 'OT Complex')">OT Complex</span>
              <span class="qsc-chip" style="font-size:0.75rem;padding:3px 10px;" onclick="selectLocationChip(this, 'Central Laboratory')">Lab</span>
            </div>
          </div>
        </div>

        <!-- ── MAIN SECTION: Two Columns (5 Bins on Left, Live Summary on Right) ── -->
        <div class="row g-3">
          
          <!-- LEFT COLUMN: 5 Bin Cards + Collection Activity (75% width) -->
          <div class="col-lg-9">
            
            <!-- 5 Bins Row (Direct, User-Friendly Numeric Inputs) -->
            <div class="mockup-bins-grid mb-3">
              
              <!-- 🟡 Yellow Card -->
              <div class="mockup-bin-card card-yellow" id="card-yellow">
                <div class="mockup-bin-pill">Yellow Bin</div>
                <div class="mockup-bin-icon text-warning">
                  <i class="fas fa-biohazard"></i>
                </div>
                <div class="mockup-bin-subtitle">Anatomical & Soiled</div>
                <div class="user-friendly-input-card">
                  <input type="number" id="f-yellow" class="user-friendly-input-field" min="0" step="0.01" placeholder="0.00" autofocus>
                  <span class="user-friendly-input-unit">kg</span>
                </div>
                <div class="bin-purpose-box">
                  <div class="bin-purpose-title text-warning-emphasis">
                    <i class="fas fa-circle-info"></i> Purpose
                  </div>
                  <div class="bin-purpose-desc">
                    Soiled cotton, dressings, body fluids, placenta, expired drugs & blood bags
                  </div>
                </div>
              </div>

              <!-- 🔴 Red Card -->
              <div class="mockup-bin-card card-red" id="card-red">
                <div class="mockup-bin-pill">Red Bin</div>
                <div class="mockup-bin-icon text-danger">
                  <i class="fas fa-recycle"></i>
                </div>
                <div class="mockup-bin-subtitle">Contaminated Plastics</div>
                <div class="user-friendly-input-card">
                  <input type="number" id="f-red" class="user-friendly-input-field" min="0" step="0.01" placeholder="0.00">
                  <span class="user-friendly-input-unit">kg</span>
                </div>
                <div class="bin-purpose-box">
                  <div class="bin-purpose-title text-danger">
                    <i class="fas fa-circle-info"></i> Purpose
                  </div>
                  <div class="bin-purpose-desc">
                    IV tubes, bottles, catheters, syringes (w/o needle), urine bags & gloves
                  </div>
                </div>
              </div>

              <!-- 🔵 Blue Card -->
              <div class="mockup-bin-card card-blue" id="card-blue">
                <div class="mockup-bin-pill">Blue Bin</div>
                <div class="mockup-bin-icon text-primary">
                  <i class="fas fa-flask-vial"></i>
                </div>
                <div class="mockup-bin-subtitle">Glassware & Ampoules</div>
                <div class="user-friendly-input-card">
                  <input type="number" id="f-blue" class="user-friendly-input-field" min="0" step="0.01" placeholder="0.00">
                  <span class="user-friendly-input-unit">kg</span>
                </div>
                <div class="bin-purpose-box">
                  <div class="bin-purpose-title text-primary">
                    <i class="fas fa-circle-info"></i> Purpose
                  </div>
                  <div class="bin-purpose-desc">
                    Medicine vials, ampoules, broken/intact glass & orthopedic implants
                  </div>
                </div>
              </div>

              <!-- ⚪ White Card -->
              <div class="mockup-bin-card card-white" id="card-white">
                <div class="mockup-bin-pill">White Bin</div>
                <div class="mockup-bin-icon text-secondary">
                  <i class="fas fa-syringe"></i>
                </div>
                <div class="mockup-bin-subtitle">Puncture Sharps/Blades</div>
                <div class="user-friendly-input-card">
                  <input type="number" id="f-white" class="user-friendly-input-field" min="0" step="0.01" placeholder="0.00">
                  <span class="user-friendly-input-unit">kg</span>
                </div>
                <div class="bin-purpose-box">
                  <div class="bin-purpose-title text-secondary-emphasis">
                    <i class="fas fa-circle-info"></i> Purpose
                  </div>
                  <div class="bin-purpose-desc">
                    Needles with syringes, scalpels, surgical blades & suture needles
                  </div>
                </div>
              </div>

              <!-- 🟢 Green Card -->
              <div class="mockup-bin-card card-green" id="card-green">
                <div class="mockup-bin-pill">Green Bin</div>
                <div class="mockup-bin-icon text-success">
                  <i class="fas fa-trash-can"></i>
                </div>
                <div class="mockup-bin-subtitle">General Ward Waste</div>
                <div class="user-friendly-input-card">
                  <input type="number" id="f-green" class="user-friendly-input-field" min="0" step="0.01" placeholder="0.00">
                  <span class="user-friendly-input-unit">kg</span>
                </div>
                <div class="bin-purpose-box">
                  <div class="bin-purpose-title text-success">
                    <i class="fas fa-circle-info"></i> Purpose
                  </div>
                  <div class="bin-purpose-desc">
                    Paper towels, food waste, packaging, fruit peels & office stationery
                  </div>
                </div>
              </div>

            </div>

            <!-- Collection Activity Card + Bottom Action Buttons -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-2">
              
              <!-- Collection Activity Card -->
              <div class="mockup-activity-card flex-grow-1" style="max-width: 560px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div class="fw-bold d-flex align-items-center gap-2" style="color:#0f3422;font-size:0.95rem;">
                    <i class="fas fa-chart-line text-success"></i> Collection Activity
                  </div>
                  <div class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold" style="font-size:0.85rem;" id="act-pct-badge">0%</div>
                </div>
                <div class="text-muted mb-1 d-flex justify-content-between align-items-center" style="font-size:0.75rem;">
                  <span>Progress Ratio Bar</span>
                  <span id="act-status-text" class="text-success fw-semibold">Waiting for entry</span>
                </div>
                <div class="mockup-progress-track">
                  <div class="mockup-progress-bar" id="act-progress-bar" style="width: 0%;"></div>
                </div>
                <div class="text-muted" style="font-size:0.82rem;font-weight:600;">
                  <span class="text-dark fw-bold" id="act-current-display">0.00 kg</span> (Current) / <span id="act-est-display">60.00 kg</span> (Estimated Ward Waste)
                </div>
              </div>

              <!-- Bottom Action Buttons -->
              <div class="d-flex align-items-center gap-3 ms-auto">
                <button type="button" class="btn-mockup-discard" onclick="resetCollectionForm()">
                  <i class="fas fa-rotate-left me-1"></i> Discard
                </button>
                <button type="submit" class="btn-mockup-submit" id="collection-save-btn">
                  <i class="fas fa-check-circle me-1"></i> Save Waste Collection
                </button>
              </div>

            </div>

          </div>

          <!-- RIGHT COLUMN: Real-Time Summary & Audit Details (25% width) -->
          <div class="col-lg-3">
            <div class="mockup-sidebar-card d-flex flex-column justify-content-between h-100">
              
              <div>
                <!-- Total Weight Live Counter -->
                <div class="p-3 bg-light rounded-3 border text-center mb-3">
                  <div class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;letter-spacing:0.5px;">Total Hospital Weight</div>
                  <div class="fw-bold fs-3 text-success my-1" id="side-total-counter">0.00 kg</div>
                  <small class="text-muted" id="side-ward-label">General Ward</small>
                </div>

                <!-- Bin Status Overview Section -->
                <div class="mockup-side-title mb-2">
                  <i class="fas fa-chart-pie me-1 text-success"></i> Segregation Overview
                </div>
                <div class="bin-overview-item">
                  <span><span class="bin-overview-dot" style="background:#facc15;"></span> Infectious</span>
                  <span class="fw-bold text-dark" id="side-yellow-weight">0.00 kg</span>
                </div>
                <div class="bin-overview-item">
                  <span><span class="bin-overview-dot" style="background:#ef4444;"></span> Contaminated Plastics</span>
                  <span class="fw-bold text-dark" id="side-red-weight">0.00 kg</span>
                </div>
                <div class="bin-overview-item">
                  <span><span class="bin-overview-dot" style="background:#3b82f6;"></span> Glassware</span>
                  <span class="fw-bold text-dark" id="side-blue-weight">0.00 kg</span>
                </div>
                <div class="bin-overview-item">
                  <span><span class="bin-overview-dot" style="background:#94a3b8;"></span> Cytotoxic/Sharps</span>
                  <span class="fw-bold text-dark" id="side-white-weight">0.00 kg</span>
                </div>
                <div class="bin-overview-item">
                  <span><span class="bin-overview-dot" style="background:#22c55e;"></span> General Waste</span>
                  <span class="fw-bold text-dark" id="side-green-weight">0.00 kg</span>
                </div>

                <!-- Recent Hospital Collections -->
                <div class="mt-3 pt-2 border-top">
                  <div class="mockup-side-title mb-2" style="font-size:0.85rem;">
                    <i class="fas fa-history me-1 text-secondary"></i> Recent Entries
                  </div>
                  <div id="mockup-recent-list">
                    <!-- Injected dynamically -->
                  </div>
                </div>
              </div>

              <!-- Audit Verified Badge -->
              <div class="mt-3 p-2 bg-success-subtle border border-success-subtle rounded-3 text-center" style="font-size:0.72rem;">
                <i class="fas fa-shield-halved text-success me-1"></i>
                <span class="fw-bold text-success">Hospital Segregation Verified</span>
              </div>

            </div>
          </div>

        </div>

      </div>

    </form>
  </div>
</div>

<!-- ══ Modal 2: Direct Dispatch Modal (No URL params needed!) ══════ -->
<div class="modal fade" id="directDispatchModal" tabindex="-1" aria-labelledby="directDispatchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content qsc-modal-pro">
      
      <div class="modal-header qsc-modal-header-pro">
        <div>
          <h5 class="modal-title mb-0 fw-bold d-flex align-items-center gap-2" id="directDispatchModalLabel">
            <i class="fas fa-truck-medical"></i> Dispatch to Vendor
          </h5>
          <div class="qsc-modal-sub" id="dd-record-subtitle">Handover Waste to Authorized Waste Management Agency</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" style="background:#fcfbf9;">
        <input type="hidden" id="dd-record-id">

        <!-- Hospital Weight Summary -->
        <div class="p-3 bg-white rounded-3 border mb-3" style="border-left:4px solid var(--qsc-green)!important;border-color:var(--qsc-border-light);">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold text-uppercase" style="font-size:0.75rem;letter-spacing:0.5px;color:var(--qsc-green-deep);">
              <i class="fas fa-hospital me-1 text-success"></i> Hospital Recorded Weights
            </span>
            <span class="badge bg-success-subtle text-success" id="dd-h-total-pill">0.00 Kg</span>
          </div>
          <div id="dd-h-summary" class="d-flex flex-wrap gap-2">
            <!-- Filled dynamically -->
          </div>
        </div>

        <!-- Vendor & Vehicle Inputs -->
        <div class="p-3 bg-white rounded-3 border mb-3" style="border-color:var(--qsc-border-light)!important;">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label mb-1 fw-bold text-uppercase" style="font-size:0.72rem;letter-spacing:0.4px;">
                Vendor / Agency Name *
              </label>
              <input type="text" id="dd-vendor" class="form-control fw-semibold" placeholder="e.g. Maridi Eco Industries / Sembcorp" required>
            </div>
            <div class="col-md-6">
              <label class="form-label mb-1 fw-bold text-uppercase" style="font-size:0.72rem;letter-spacing:0.4px;">
                Vehicle Number *
              </label>
              <input type="text" id="dd-vehicle" class="form-control fw-semibold" placeholder="e.g. KA 01 AB 1234" required>
            </div>
            <div class="col-md-4">
              <label class="form-label mb-1 fw-bold text-uppercase" style="font-size:0.72rem;letter-spacing:0.4px;">
                Receipt No. / Manifest No.
              </label>
              <input type="text" id="dd-ref" class="form-control" placeholder="Receipt No. (Auto if empty)">
            </div>
            <div class="col-md-4">
              <label class="form-label mb-1 fw-bold text-uppercase" style="font-size:0.72rem;letter-spacing:0.4px;">
                Driver Name
              </label>
              <input type="text" id="dd-driver" class="form-control" placeholder="Optional">
            </div>
            <div class="col-md-4">
              <label class="form-label mb-1 fw-bold text-uppercase" style="font-size:0.72rem;letter-spacing:0.4px;">
                Driver Phone
              </label>
              <input type="tel" id="dd-contact" class="form-control" placeholder="Optional">
            </div>
          </div>
        </div>

        <!-- Vendor Bin Weights -->
        <div class="p-3 bg-white rounded-3 border mb-3" style="border-color:var(--qsc-border-light)!important;">
          <label class="form-label mb-2 fw-bold text-uppercase d-block" style="font-size:0.75rem;letter-spacing:0.5px;color:var(--qsc-green-deep);">
            <i class="fas fa-trash-can me-1 text-success"></i> Vendor Weighed Bin Weights (Kg)
          </label>
          <div class="bin-input-grid">
            <div class="bin-input-card bin-yellow">
              <label><i class="fas fa-trash-can me-1 text-warning"></i>Yellow</label>
              <input type="number" id="dd-yellow" min="0" step="0.01" placeholder="0.00" oninput="updateDirectRecon()">
            </div>
            <div class="bin-input-card bin-red">
              <label><i class="fas fa-trash-can me-1 text-danger"></i>Red</label>
              <input type="number" id="dd-red" min="0" step="0.01" placeholder="0.00" oninput="updateDirectRecon()">
            </div>
            <div class="bin-input-card bin-blue">
              <label><i class="fas fa-trash-can me-1 text-primary"></i>Blue</label>
              <input type="number" id="dd-blue" min="0" step="0.01" placeholder="0.00" oninput="updateDirectRecon()">
            </div>
            <div class="bin-input-card bin-white">
              <label><i class="fas fa-trash-can me-1 text-secondary"></i>White</label>
              <input type="number" id="dd-white" min="0" step="0.01" placeholder="0.00" oninput="updateDirectRecon()">
            </div>
            <div class="bin-input-card bin-green">
              <label><i class="fas fa-trash-can me-1 text-success"></i>Green</label>
              <input type="number" id="dd-green" min="0" step="0.01" placeholder="0.00" oninput="updateDirectRecon()">
            </div>
          </div>
        </div>

        <!-- Reconciliation Preview Badges -->
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <div class="qsc-total-badge p-2 px-3 rounded-3" style="background:#475569;">
              <span style="font-size:0.75rem;"><i class="fas fa-hospital me-1"></i> Hospital:</span>
              <span class="ms-auto fw-bold" id="dd-preview-h-total" style="font-size:1.1rem;">0.00 Kg</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="qsc-total-badge p-2 px-3 rounded-3" style="background:var(--qsc-green-mid);">
              <span style="font-size:0.75rem;"><i class="fas fa-truck me-1"></i> Vendor:</span>
              <span class="ms-auto fw-bold" id="dd-preview-v-total" style="font-size:1.1rem;">0.00 Kg</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="qsc-total-badge p-2 px-3 rounded-3" id="dd-preview-variance-badge" style="background:#16a34a;">
              <span style="font-size:0.75rem;"><i class="fas fa-scale-balanced me-1"></i> Variance:</span>
              <span class="ms-auto fw-bold" id="dd-preview-variance" style="font-size:1.1rem;">0.00 Kg</span>
            </div>
          </div>
        </div>

        <div class="mb-0">
          <label class="form-label mb-1 fw-bold text-uppercase" style="font-size:0.72rem;letter-spacing:0.4px;">Remarks</label>
          <input type="text" id="dd-remarks" class="form-control" placeholder="Optional handover notes…">
        </div>

      </div>

      <div class="modal-footer px-4 py-3 d-flex justify-content-between align-items-center" style="background:var(--qsc-cream);border-top:1px solid var(--qsc-border-light);">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-qsc-primary shadow-sm" id="dd-confirm-btn" onclick="saveDirectDispatch()">
          <i class="fas fa-check-circle me-1"></i> Confirm Dispatch Handover
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ══ All JavaScript Code ═════════════════════════════════════════ -->
<script>
// ─── Real-time Auto-compute total, Collection Activity & Sidebar ──────────────
function bindBinInputEvents() {
  ['f-green','f-yellow','f-red','f-blue','f-white'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', updateTotal);
      el.addEventListener('change', updateTotal);
      el.addEventListener('keyup', updateTotal);
    }
  });
}
bindBinInputEvents();

function updateTotal() {
  const g = parseFloat(document.getElementById('f-green')?.value  || 0);
  const y = parseFloat(document.getElementById('f-yellow')?.value || 0);
  const r = parseFloat(document.getElementById('f-red')?.value    || 0);
  const b = parseFloat(document.getElementById('f-blue')?.value   || 0);
  const w = parseFloat(document.getElementById('f-white')?.value  || 0);
  const total = g + y + r + b + w;

  // 1. Update Collection Activity Progress Bar (Real-time)
  const estWardWaste = 60.0; // benchmark estimated ward waste
  const pct = estWardWaste > 0 ? Math.min(100, Math.round((total / estWardWaste) * 100)) : 0;

  const actBar = document.getElementById('act-progress-bar');
  const actPctBadge = document.getElementById('act-pct-badge');
  const actCurrent = document.getElementById('act-current-display');
  const actStatus = document.getElementById('act-status-text');

  if (actBar) actBar.style.width = pct + '%';
  if (actPctBadge) actPctBadge.textContent = pct + '%';
  if (actCurrent) actCurrent.textContent = total.toFixed(2) + ' kg';
  if (actStatus) {
    if (total <= 0) actStatus.textContent = 'Waiting for entry';
    else if (pct < 50) actStatus.textContent = total.toFixed(2) + ' kg logged';
    else if (pct < 100) actStatus.textContent = 'Near ward capacity';
    else actStatus.textContent = 'Capacity reached (' + total.toFixed(2) + ' kg)';
  }

  // 2. Highlight Cards with Active Value
  ['green','yellow','red','blue','white'].forEach(color => {
    const card = document.getElementById('card-' + color);
    const val = parseFloat(document.getElementById('f-' + color)?.value || 0);
    if (card) {
      if (val > 0) card.classList.add('card-has-value');
      else card.classList.remove('card-has-value');
    }
  });

  // 3. Update Right Sidebar Live Summary
  const sideY = document.getElementById('side-yellow-weight');
  const sideR = document.getElementById('side-red-weight');
  const sideB = document.getElementById('side-blue-weight');
  const sideW = document.getElementById('side-white-weight');
  const sideG = document.getElementById('side-green-weight');

  if (sideY) sideY.textContent = y.toFixed(2) + ' kg';
  if (sideR) sideR.textContent = r.toFixed(2) + ' kg';
  if (sideB) sideB.textContent = b.toFixed(2) + ' kg';
  if (sideW) sideW.textContent = w.toFixed(2) + ' kg';
  if (sideG) sideG.textContent = g.toFixed(2) + ' kg';

  // 4. Update Sidebar Total Counter
  const sideTotal = document.getElementById('side-total-counter');
  if (sideTotal) sideTotal.textContent = total.toFixed(2) + ' kg';
}

function updateMockupLocationSubtitle(locName) {
  const label = locName || document.getElementById('f-location')?.value || 'General Ward';
  const sideWard = document.getElementById('side-ward-label');
  if (sideWard) sideWard.textContent = label;
}

function updateMockupRecentEntries(rows) {
  const container = document.getElementById('mockup-recent-list');
  if (!container) return;
  if (!rows || rows.length === 0) return;

  container.innerHTML = rows.slice(0, 5).map(r => {
    let timeStr = '10:48 AM';
    if (r.collection_at) {
      const parts = r.collection_at.split(' ');
      if (parts[1]) {
        const timeParts = parts[1].split(':');
        let h = parseInt(timeParts[0]);
        const m = timeParts[1];
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        timeStr = `${h}:${m} ${ampm}`;
      }
    }
    const tot = parseFloat(r.h_total_weight || 0).toFixed(2);
    return `
      <div class="recent-entry-row">
        <span class="fw-semibold text-truncate" style="max-width:95px;" title="${r.location || 'Ward'}">${r.location || 'Ward'}</span>
        <span class="text-muted" style="font-size:0.75rem;">${timeStr}</span>
        <span class="fw-bold text-end text-dark">${tot} kg</span>
      </div>
    `;
  }).join('');
}

function toggleCardHighlight(cardId, isActive) {
  const card = document.getElementById(cardId);
  if (card) {
    if (isActive) card.classList.add('has-value');
    else card.classList.remove('has-value');
  }
}

// ─── Quick Increment Controls ────────────────────────────────────────────────
function addWeight(fieldId, amount) {
  const input = document.getElementById(fieldId);
  const current = parseFloat(input.value || 0);
  const next = (current + amount).toFixed(2);
  input.value = next > 0 ? next : '';
  updateTotal();
}

function clearBin(fieldId) {
  document.getElementById(fieldId).value = '';
  updateTotal();
}

function clearAllBins() {
  ['f-green','f-yellow','f-red','f-blue','f-white'].forEach(id => {
    document.getElementById(id).value = '';
  });
  updateTotal();
}

// ─── Quick Department Selection ──────────────────────────────────────────────
function selectLocationChip(chipEl, locName) {
  document.querySelectorAll('#dept-chips .qsc-chip').forEach(c => c.classList.remove('active'));
  chipEl.classList.add('active');
  
  if (typeof $ !== 'undefined' && $('#f-location').hasClass('select2-hidden-accessible')) {
    if ($('#f-location').find("option[value='" + locName + "']").length === 0) {
      const newOpt = new Option(locName, locName, true, true);
      $('#f-location').append(newOpt);
    }
    $('#f-location').val(locName).trigger('change');
  } else {
    document.getElementById('f-location').value = locName;
  }
  updateMockupLocationSubtitle(locName);
}

function setCurrentDateTime() {
  document.getElementById('f-collection-at').value = getLocalDateTimeString();
}

function appendRemark(text) {
  const field = document.getElementById('f-remarks');
  if (field.value.trim() === '') {
    field.value = text;
  } else if (!field.value.includes(text)) {
    field.value += ' | ' + text;
  }
}

function resetCollectionForm() {
  document.getElementById('collectionForm').reset();
  document.getElementById('form-record-id').value = '';
  setCurrentDateTime();
  document.querySelectorAll('#dept-chips .qsc-chip').forEach(c => c.classList.remove('active'));
  if (typeof $ !== 'undefined' && $('#f-location').hasClass('select2-hidden-accessible')) {
    $('#f-location').val('').trigger('change');
  } else {
    const loc = document.getElementById('f-location');
    if (loc) loc.value = '';
  }
  clearAllBins();
  updateMockupLocationSubtitle('General Ward');
}

function advanceToTab(target) {
  // Tabs removed: All controls are integrated into a single seamless screen.
}

// ─── Load Records Table ──────────────────────────────────────────────────────
async function loadRecords() {
  const dateFrom = document.getElementById('filter-date-from').value;
  const dateTo   = document.getElementById('filter-date-to').value;
  const status   = document.getElementById('filter-status').value;

  const qs = new URLSearchParams({ date_from: dateFrom, date_to: dateTo, status, limit: 100 });
  const tbody = document.getElementById('collection-tbody');
  tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading records…</td></tr>';

  try {
    const res  = await qscApi(`/api/quality/bmw/records?${qs}`);
    const rows = res.data?.data ?? [];
    document.getElementById('total-badge').textContent = `${res.data?.total ?? 0} records`;
    updateMockupRecentEntries(rows);

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 text-muted"><i class="fas fa-inbox me-2"></i>No records found for the selected filter period.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map((r, i) => `
      <tr>
        <td class="ps-3 fw-bold text-muted">${i+1}</td>
        <td><i class="far fa-clock text-muted me-1"></i>${r.collection_at ?? '—'}</td>
        <td><strong><i class="fas fa-location-dot text-danger me-1"></i>${r.location}</strong></td>
        <td>${r.h_green_weight  > 0 ? '<span class="bin-badge bin-green"><i class="fas fa-trash-can me-1"></i>'  +r.h_green_weight.toFixed(2)+'</span>' : '<span class="text-muted">—</span>'}</td>
        <td>${r.h_yellow_weight > 0 ? '<span class="bin-badge bin-yellow"><i class="fas fa-trash-can me-1"></i>'+r.h_yellow_weight.toFixed(2)+'</span>' : '<span class="text-muted">—</span>'}</td>
        <td>${r.h_red_weight    > 0 ? '<span class="bin-badge bin-red"><i class="fas fa-trash-can me-1"></i>'   +r.h_red_weight.toFixed(2)+'</span>'    : '<span class="text-muted">—</span>'}</td>
        <td>${r.h_blue_weight   > 0 ? '<span class="bin-badge bin-blue"><i class="fas fa-trash-can me-1"></i>'  +r.h_blue_weight.toFixed(2)+'</span>'   : '<span class="text-muted">—</span>'}</td>
        <td>${r.h_white_weight  > 0 ? '<span class="bin-badge bin-white"><i class="fas fa-trash-can me-1"></i>' +r.h_white_weight.toFixed(2)+'</span>'  : '<span class="text-muted">—</span>'}</td>
        <td><strong style="color:var(--qsc-green);font-size:0.95rem;">${r.h_total_weight.toFixed(2)} Kg</strong></td>
        <td>${statusBadge(r.status)}</td>
        <td><small class="text-muted"><i class="far fa-user me-1"></i>${r.logged_by_user ?? '—'}</small></td>
        <td>
          <div class="d-flex gap-1">
            ${r.status === 'Collected' ? `<button class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit Record" onclick="openEdit(${JSON.stringify(r).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i></button>` : ''}
            ${r.status === 'Collected' ? `<button class="btn btn-sm btn-outline-success py-1 px-2" title="Dispatch to Vendor" onclick="openDirectDispatch(${JSON.stringify(r).replace(/"/g,'&quot;')})"><i class="fas fa-truck-medical"></i></button>` : ''}
            ${r.status === 'Completed' ? `<a href="manifest_print.php?id=${r.id}" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Print Manifest"><i class="fas fa-print"></i></a>` : ''}
            <button class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete" onclick="deleteRecord(${r.id})"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>`).join('');
  } catch(e) {
    tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-danger"><i class="fas fa-circle-exclamation me-2"></i>${e.message}</td></tr>`;
  }
}

// ─── Open Edit Modal ─────────────────────────────────────────────────────────
function openEdit(r) {
  document.getElementById('form-record-id').value = r.id;
  document.getElementById('f-collection-at').value = r.collection_at ? r.collection_at.replace(' ','T') : '';
  
  // Set location in Advance Search Select2
  if (typeof $ !== 'undefined' && $('#f-location').hasClass('select2-hidden-accessible')) {
    if ($('#f-location').find("option[value='" + r.location + "']").length === 0) {
      const newOpt = new Option(r.location, r.location, true, true);
      $('#f-location').append(newOpt);
    }
    $('#f-location').val(r.location).trigger('change');
  } else {
    const loc = document.getElementById('f-location');
    if (loc) loc.value = r.location;
  }

  document.getElementById('f-green').value  = r.h_green_weight  || '';
  document.getElementById('f-yellow').value = r.h_yellow_weight || '';
  document.getElementById('f-red').value    = r.h_red_weight    || '';
  document.getElementById('f-blue').value   = r.h_blue_weight   || '';
  document.getElementById('f-white').value  = r.h_white_weight  || '';
  document.getElementById('f-remarks').value = r.remarks || '';
  
  // Highlight chip if matching
  document.querySelectorAll('#dept-chips .qsc-chip').forEach(c => {
    if (c.textContent.trim().toLowerCase() === (r.location || '').toLowerCase()) {
      c.classList.add('active');
    } else {
      c.classList.remove('active');
    }
  });

  document.getElementById('collectionModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Waste Collection #' + r.id;
  document.getElementById('collection-save-btn').innerHTML  = '<i class="fas fa-save me-1"></i> Update Collection';
  updateTotal();
  updateMockupLocationSubtitle(r.location);

  new bootstrap.Modal(document.getElementById('collectionModal')).show();
}

// ─── Modal Show / Reset Listener & Select2 Initialization ─────────────────────
document.getElementById('collectionModal').addEventListener('shown.bs.modal', function () {
  if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
    if (!$('#f-location').hasClass('select2-hidden-accessible')) {
      $('#f-location').select2({
        dropdownParent: $('#collectionModal'),
        placeholder: '-- Search or Select Room Type / Ward --',
        allowClear: true,
        width: '100%',
        tags: true
      });
    }
  }
});

document.getElementById('collectionModal').addEventListener('show.bs.modal', function(e) {
  if (!e.relatedTarget) return; // opened by JS for edit
  resetCollectionForm();
  document.getElementById('collectionModalLabel').innerHTML = '<i class="fas fa-biohazard me-2"></i>Log Waste Collection';
  document.getElementById('collection-save-btn').innerHTML  = '<i class="fas fa-check-circle me-1"></i> Save Waste Collection';
});

// ─── Form Submit (Collection) ────────────────────────────────────────────────
document.getElementById('collectionForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('collection-save-btn');
  const id  = document.getElementById('form-record-id').value;
  
  const g = parseFloat(document.getElementById('f-green').value  || 0);
  const y = parseFloat(document.getElementById('f-yellow').value || 0);
  const r = parseFloat(document.getElementById('f-red').value    || 0);
  const b = parseFloat(document.getElementById('f-blue').value   || 0);
  const w = parseFloat(document.getElementById('f-white').value  || 0);

  if ((g + y + r + b + w) <= 0) {
    qscToast('Please enter weight for at least one bin category (> 0 Kg).', 'error');
    return;
  }

  const locEl = document.getElementById('f-location');
  let locationVal = (typeof $ !== 'undefined' && $(locEl).val() ? $(locEl).val() : (locEl ? locEl.value : '')).trim();
  if (!locationVal) {
    locationVal = (document.getElementById('sub-ward-name')?.textContent || 'General Ward').trim();
    if (locEl) locEl.value = locationVal;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving…';

  const body = {
    collection_at:   document.getElementById('f-collection-at').value.replace('T',' '),
    location:        locationVal,
    h_green_weight:  g,
    h_yellow_weight: y,
    h_red_weight:    r,
    h_blue_weight:   b,
    h_white_weight:  w,
    remarks:         document.getElementById('f-remarks').value
  };

  try {
    const url    = id ? `/api/quality/bmw/records/${id}` : '/api/quality/bmw/records';
    const method = id ? 'PUT' : 'POST';
    const res    = await qscApi(url, { method, body: JSON.stringify(body) });
    qscToast(res.message ?? 'Collection recorded successfully', 'success');
    bootstrap.Modal.getInstance(document.getElementById('collectionModal')).hide();
    loadRecords();
  } catch(err) {
    qscToast(err.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + (id ? 'Update Collection' : 'Save Waste Collection');
  }
});

// ─── Delete Record ────────────────────────────────────────────────────────────
async function deleteRecord(id) {
  const result = await Swal.fire({
    title: 'Delete Waste Record?',
    text: 'Are you sure you want to permanently delete this BMW record?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Delete'
  });
  if (!result.isConfirmed) return;
  try {
    const res = await qscApi(`/api/quality/bmw/records/${id}`, { method: 'DELETE' });
    qscToast(res.message ?? 'Record deleted', 'success');
    loadRecords();
  } catch(e) {
    qscToast(e.message, 'error');
  }
}

// ─── Direct Dispatch Handlers ────────────────────────────────────────────────
let _directDispatchHTotal = 0;

function openDirectDispatch(r) {
  _directDispatchHTotal = parseFloat(r.h_total_weight || 0);
  document.getElementById('dd-record-id').value = r.id;
  document.getElementById('dd-record-subtitle').textContent = `Record #${r.id} • ${r.location} • Collected: ${r.collection_at}`;
  document.getElementById('dd-h-total-pill').textContent = _directDispatchHTotal.toFixed(2) + ' Kg Total';
  document.getElementById('dd-preview-h-total').textContent = _directDispatchHTotal.toFixed(2) + ' Kg';

  // Render Hospital Summary Badges
  const summaryEl = document.getElementById('dd-h-summary');
  summaryEl.innerHTML = ['yellow','red','blue','white','green'].map(c => {
    const v = r[`h_${c}_weight`];
    return v > 0 ? `<span class="bin-badge bin-${c}"><i class="fas fa-trash-can me-1"></i>${c.charAt(0).toUpperCase()+c.slice(1)}: ${parseFloat(v).toFixed(2)} Kg</span>` : '';
  }).join('') || '<span class="text-muted">No weights recorded</span>';

  // Pre-fill vendor bins with hospital weights as default for speed
  document.getElementById('dd-yellow').value = r.h_yellow_weight || '';
  document.getElementById('dd-red').value    = r.h_red_weight    || '';
  document.getElementById('dd-blue').value   = r.h_blue_weight   || '';
  document.getElementById('dd-white').value  = r.h_white_weight  || '';
  document.getElementById('dd-green').value  = r.h_green_weight  || '';
  
  document.getElementById('dd-vendor').value  = '';
  document.getElementById('dd-vehicle').value = '';
  document.getElementById('dd-ref').value     = '';
  document.getElementById('dd-driver').value  = '';
  document.getElementById('dd-contact').value = '';
  document.getElementById('dd-remarks').value = '';

  updateDirectRecon();
  new bootstrap.Modal(document.getElementById('directDispatchModal')).show();
}

function updateDirectRecon() {
  const vy = parseFloat(document.getElementById('dd-yellow').value || 0);
  const vr = parseFloat(document.getElementById('dd-red').value    || 0);
  const vb = parseFloat(document.getElementById('dd-blue').value   || 0);
  const vw = parseFloat(document.getElementById('dd-white').value  || 0);
  const vg = parseFloat(document.getElementById('dd-green').value  || 0);
  const vTotal = vy + vr + vb + vw + vg;
  const variance = vTotal - _directDispatchHTotal;

  document.getElementById('dd-preview-v-total').textContent = vTotal.toFixed(2) + ' Kg';
  document.getElementById('dd-preview-variance').textContent = (variance >= 0 ? '+' : '') + variance.toFixed(2) + ' Kg';

  const varBadge = document.getElementById('dd-preview-variance-badge');
  if (Math.abs(variance) < 0.01) {
    varBadge.style.background = '#16a34a';
  } else if (variance > 0) {
    varBadge.style.background = '#dc2626';
  } else {
    varBadge.style.background = '#d97706';
  }
}

async function saveDirectDispatch() {
  const id  = document.getElementById('dd-record-id').value;
  const btn = document.getElementById('dd-confirm-btn');
  
  const vendor = document.getElementById('dd-vendor').value.trim();
  const vehicle = document.getElementById('dd-vehicle').value.trim();

  if (!vendor || !vehicle) {
    qscToast('Please enter both Vendor Name and Vehicle Number.', 'error');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Confirming Handover…';

  const now = new Date();
  const body = {
    dispatch_at:     getLocalDateString(now) + ' ' + getLocalTimeString(now),
    dispatch_time:   getLocalTimeString(now),
    vendor_name:     vendor,
    vehicle_number:  vehicle,
    driver_name:     document.getElementById('dd-driver').value.trim(),
    driver_contact:  document.getElementById('dd-contact').value.trim(),
    reference_no:    document.getElementById('dd-ref').value.trim(),
    v_green_weight:  parseFloat(document.getElementById('dd-green').value  || 0),
    v_yellow_weight: parseFloat(document.getElementById('dd-yellow').value || 0),
    v_red_weight:    parseFloat(document.getElementById('dd-red').value    || 0),
    v_blue_weight:   parseFloat(document.getElementById('dd-blue').value   || 0),
    v_white_weight:  parseFloat(document.getElementById('dd-white').value  || 0),
    remarks:         document.getElementById('dd-remarks').value.trim()
  };

  try {
    const res = await qscApi(`/api/quality/bmw/records/${id}/dispatch`, { method: 'POST', body: JSON.stringify(body) });
    const d   = res.data;
    bootstrap.Modal.getInstance(document.getElementById('directDispatchModal')).hide();
    
    const confirm = await Swal.fire({
      icon: 'success',
      title: 'Dispatch Confirmed!',
      html: `Receipt / Ref No: <strong>${d.reference_no}</strong><br>
             Hospital: ${parseFloat(d.h_total_weight).toFixed(2)} Kg &nbsp;|&nbsp;
             Vendor: ${parseFloat(d.v_total_weight).toFixed(2)} Kg &nbsp;|&nbsp;
             Variance: ${parseFloat(d.weight_difference) >= 0 ? '+' : ''}${parseFloat(d.weight_difference).toFixed(2)} Kg`,
      confirmButtonColor: '#1f6b4a',
      confirmButtonText: 'Print Manifest',
      showCancelButton: true,
      cancelButtonText: 'Done'
    });
    if (confirm.isConfirmed) {
      window.open(`/GM_HMS/quality_view/manifest_print.php?id=${id}`, '_blank');
    }
    loadRecords();
  } catch(e) {
    qscToast(e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Confirm Dispatch Handover';
  }
}

// ─── Initial Page Load ───────────────────────────────────────────────────────
setCurrentDateTime();
loadRecords();
</script>

<?php require_once __DIR__ . '/includes/quality_foot.php'; ?>
