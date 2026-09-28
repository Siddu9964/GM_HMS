<?php
/**
 * ot_view/surgery_schedule.php
 * OT Module - Surgery Schedule (List + Add/Edit + Status Update)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
// ── OT Module Access Guard ────────────────────────────────────────────────────
$_allowedOtRoles = ['Scrub_Nurse', 'admin', 'Admin', 'Administrator'];
$_currentRole = trim($_SESSION['role'] ?? '');
if (!in_array($_currentRole, $_allowedOtRoles)) {
    header('Location: ../login.php?error=' . urlencode('Access Denied: OT Module is restricted to Scrub Nurses only.'));
    exit;
}
$pageTitle = 'Surgery Schedule';
require_once 'includes/ot_head.php';
// Load modern dashboard CSS for styling
echo '<link rel="stylesheet" href="assets/css/ot_dashboard.css">';
?>
<div class="ot-wrap">

  <!-- Navbar -->
  <div id="ot-content">
  <?php require_once 'includes/ot_navbar.php'; ?>

  <div class="d-flex">
    <?php require_once 'includes/ot_sidebar.php'; ?>

    <!-- Page Body -->
    <div class="ot-page-body w-100">

      <!-- Filters -->
      <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
              <label class="ot-form-label mb-0 text-nowrap">Date:</label>
              <input type="date" id="filter-date" class="ot-form-control" value="" style="width: auto;">
              <button class="ot-btn ot-btn-outline ot-btn-sm text-nowrap" onclick="document.getElementById('filter-date').value=new Date().toISOString().slice(0,10);OT.loadSurgeries();" title="Filter today">
                <i class="fas fa-calendar-day me-1"></i>Today
              </button>
              <button class="ot-btn ot-btn-outline ot-btn-sm" onclick="document.getElementById('filter-date').value='';document.getElementById('filter-status').value='';document.getElementById('filter-search').value='';OT.loadSurgeries();" title="Show all">
                <i class="fas fa-times me-1"></i>Clear
              </button>
            </div>
            <div class="d-flex align-items-center gap-2">
              <label class="ot-form-label mb-0 text-nowrap">Status:</label>
              <select id="filter-status" class="ot-form-control" style="width: auto; min-width: 140px;">
                <option value="">All Statuses</option>
                <option value="Scheduled">Scheduled</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Postponed">Postponed</option>
                <option value="Preponed">Preponed</option>
              </select>
            </div>
            <div class="d-flex align-items-center gap-2 flex-grow-1">
              <input type="text" id="filter-search" class="ot-form-control w-100" placeholder="Search Patient, Surgery, OT Room...">
            </div>
            <div>
              <button class="ot-btn ot-btn-primary" onclick="OT.loadSurgeries()" style="white-space: nowrap;">
                <i class="fas fa-search"></i> Filter
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- Surgeries Table -->
      <div class="ot-table-card">
        <div class="ot-table-header">
          <div class="d-flex align-items-center gap-2">
            <h5><i class="fas fa-list me-2" style="color:var(--ot-primary)"></i>Surgery List</h5>
            <span class="text-muted small" id="record-count"></span>
          </div>
          <button class="ot-btn ot-btn-primary ot-btn-sm" onclick="OT.openAddModal()">
            <i class="fas fa-plus"></i> Schedule Surgery
          </button>
        </div>
        <div class="ot-table-wrap">
          <table class="ot-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Surgery</th>
                <th>Surgery Team</th>
                <th>OT Room</th>
                <th>Date</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="surgeries-tbody">
              <tr><td colspan="11" class="text-center py-4 text-muted">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div><!-- /ot-table-card -->

    </div><!-- /ot-page-body -->
  </div><!-- /d-flex -->
  </div><!-- /ot-content -->
</div><!-- /ot-wrap -->

<!-- ══════════════════════════════════════════════════════════════════════════
     ADD / EDIT SURGERY MODAL
══════════════════════════════════════════════════════════════════════════ -->
<style>
/* Modern Modal Styling */
.ot-modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(31, 107, 74, 0.15);
    overflow: hidden;
}
.ot-modal-header {
    background: var(--d-green-06);
    border-bottom: 1px solid var(--d-green-12);
    padding: 1.25rem 1.5rem;
}
.ot-modal-title {
    color: var(--d-green);
    font-weight: 800;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.ot-modal-close {
    background: var(--d-green-12);
    color: var(--d-green);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}
.ot-modal-close:hover {
    background: var(--d-green-20);
    transform: rotate(90deg);
}
.ot-form-section {
    background: var(--d-white);
    border: 1px solid var(--d-green-12);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
}
.ot-form-section-title {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--d-green-60);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.ot-form-section-title i {
    color: var(--d-green-40);
}
.ot-input-v2 {
    background: var(--d-surface);
    border: 1.5px solid var(--d-green-12);
    border-radius: 8px;
    padding: 0.6rem 0.8rem;
    font-size: 0.85rem;
    color: var(--d-text);
    transition: all 0.2s ease;
    width: 100%;
}
.ot-input-v2:focus {
    outline: none;
    border-color: var(--d-green-40);
    background: var(--d-white);
    box-shadow: 0 0 0 3px var(--d-green-06);
}
.ot-label-v2 {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--d-text);
    margin-bottom: 0.4rem;
    display: block;
}
.ot-add-team-btn {
    background: var(--d-green-06);
    color: var(--d-green);
    border: 1.5px dashed var(--d-green-20);
    border-radius: 8px;
    padding: 0.5rem;
    width: 100%;
    font-size: 0.8rem;
    font-weight: 700;
    transition: all 0.2s ease;
    margin-top: 0.5rem;
}
.ot-add-team-btn:hover {
    background: var(--d-green-12);
    border-color: var(--d-green-40);
}
</style>

<div class="modal fade" id="surgeryModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content ot-modal-content">
      <div class="modal-header ot-modal-header">
        <h5 class="modal-title ot-modal-title" id="modal-title">
          <i class="fas fa-procedures"></i> Schedule Surgery
        </h5>
        <button type="button" class="ot-modal-close" data-bs-dismiss="modal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body p-4" style="background: var(--d-surface);">
        <form id="surgery-form">
          <input type="hidden" id="surgery-id">

          <!-- Section 1: Patient Info -->
          <div class="ot-form-section">
            <div class="ot-form-section-title"><i class="fas fa-user"></i> Patient Information</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="ot-label-v2">Search Patient (ID/Name/Phone)</label>
                <div class="input-group position-relative">
                  <input type="text" id="field-patient-id" class="form-control ot-input-v2" style="border-top-right-radius:0; border-bottom-right-radius:0;" placeholder="Search ID, Name or Phone..." onkeyup="OT.advancedPatientSearch(this.value)">
                  <button class="btn" type="button" onclick="OT.searchPatient()" style="background: var(--d-green-12); border: 1.5px solid var(--d-green-12); border-left: none; border-top-right-radius:8px; border-bottom-right-radius:8px;">
                    <i class="fas fa-search" style="color: var(--d-green)"></i>
                  </button>
                  <div id="patient-search-results" class="dropdown-menu w-100 shadow-sm" style="display:none; position:absolute; top:100%; left:0; max-height: 250px; overflow-y: auto; z-index: 1050; border-radius: 8px; border: 1px solid var(--d-green-12);"></div>
                </div>
              </div>
              <div class="col-md-6">
                <label class="ot-label-v2">Patient Full Name <span class="text-danger">*</span></label>
                <input type="text" id="field-patient-name" class="ot-input-v2" placeholder="Auto-filled or type manually" required>
              </div>
            </div>
          </div>

          <!-- Section 2: Schedule -->
          <div class="ot-form-section">
            <div class="ot-form-section-title"><i class="far fa-clock"></i> Schedule Date & Time</div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="ot-label-v2">Date <span class="text-danger">*</span></label>
                <input type="date" id="field-date" class="ot-input-v2" required onchange="OT.checkRoomAvailability()">
              </div>
              <div class="col-md-4">
                <label class="ot-label-v2">Start Time <span class="text-danger">*</span></label>
                <input type="time" id="field-start-time" class="ot-input-v2" required onchange="OT.checkRoomAvailability()">
              </div>
              <div class="col-md-4">
                <label class="ot-label-v2">End Time</label>
                <input type="time" id="field-end-time" class="ot-input-v2" onchange="OT.checkRoomAvailability()">
              </div>
            </div>
          </div>

          <!-- Section 3: Procedure Details -->
          <div class="ot-form-section">
            <div class="ot-form-section-title"><i class="fas fa-scalpel"></i> Procedure Details</div>
            <div class="row g-3">
              <div class="col-md-5">
                <label class="ot-label-v2">Surgery Name <span class="text-danger">*</span></label>
                <input type="text" id="field-surgery-name" class="ot-input-v2" placeholder="e.g. Appendectomy">
              </div>
              <div class="col-md-4">
                <label class="ot-label-v2">Department</label>
                <div class="position-relative">
                  <input type="text" id="field-department" class="ot-input-v2 pe-4" placeholder="Search or Select..." onkeyup="OT.filterDepartments(this)" onfocus="OT.filterDepartments(this)" autocomplete="off">
                  <i class="fas fa-chevron-down" style="position:absolute; right:12px; top:12px; color:var(--d-green-40); pointer-events:none;"></i>
                  <div id="department-search-results" class="dropdown-menu w-100 shadow-sm" style="display:none; position:absolute; top:100%; left:0; max-height: 250px; overflow-y: auto; z-index: 1050; border-radius: 8px; border: 1px solid var(--d-green-12);"></div>
                </div>
              </div>
              <div class="col-md-3">
                <label class="ot-label-v2">OT Room</label>
                <select id="field-ot-room" class="ot-input-v2" onchange="OT.checkRoomAvailability()">
                  <option value="">Loading rooms...</option>
                </select>
              </div>
              <div class="col-md-5">
                <label class="ot-label-v2">Anesthesia Type</label>
                <select id="field-anesthesia-type" class="ot-input-v2">
                  <option value="">--Select--</option>
                  <option value="General Anesthesia">General Anesthesia</option>
                  <option value="Regional Anesthesia">Regional Anesthesia</option>
                  <option value="Local Anesthesia">Local Anesthesia</option>
                  <option value="MAC / Sedation">MAC / Sedation</option>
                  <option value="Spinal Anesthesia">Spinal Anesthesia</option>
                  <option value="Epidural">Epidural</option>
                </select>
              </div>
              <div class="col-12">
                <label class="ot-label-v2">Description / Notes</label>
                <textarea id="field-description" class="ot-input-v2" rows="2" placeholder="Additional surgical notes..."></textarea>
              </div>
            </div>
          </div>

          <!-- Section 4: Team -->
          <div class="ot-form-section mb-0">
            <div class="ot-form-section-title"><i class="fas fa-users"></i> Surgery Team <span class="text-danger ms-1">*</span></div>
            <div id="staff-container" class="d-flex flex-column gap-2">
                <!-- Rows generated dynamically by JS -->
            </div>
            <button type="button" class="ot-add-team-btn" onclick="OT.addStaffRow()">
                <i class="fas fa-plus me-1"></i> Add Team Member
            </button>
          </div>

          <div id="form-error" class="alert alert-danger mt-3 d-none" style="border-radius: 8px; font-size: 0.85rem;"></div>
        </form>
      </div>
      <div class="modal-footer" style="background: var(--d-white); border-top: 1px solid var(--d-green-12); padding: 1rem 1.5rem;">
        <button class="ot-btn ot-btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button class="ot-btn ot-btn-primary" onclick="OT.saveSurgery()" style="padding: 0.5rem 1.5rem;">
          <i class="fas fa-save me-1"></i> <span id="save-btn-text">Save Surgery</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     STATUS UPDATE MODAL (Postpone / Prepone / Cancel / Complete)
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Update Surgery Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="status-surgery-id">
        <div class="mb-3">
          <label class="ot-form-label">New Status <span class="text-danger">*</span></label>
          <select id="status-select" class="ot-form-control" onchange="OT.onStatusChange()">
            <option value="Scheduled">Scheduled</option>
            <option value="Ongoing">Ongoing</option>
            <option value="Completed">Completed</option>
            <option value="Postponed">Postponed</option>
            <option value="Preponed">Preponed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div id="reason-group" class="mb-3 d-none">
          <label class="ot-form-label">Reason Note <span class="text-danger">*</span></label>
          <textarea id="status-reason" class="ot-form-control" rows="3" placeholder="Enter reason for this status change..."></textarea>
        </div>
        <div id="status-error" class="alert alert-danger d-none"></div>
      </div>
      <div class="modal-footer border-0">
        <button class="ot-btn ot-btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button class="ot-btn ot-btn-primary" onclick="OT.saveStatus()">
          <i class="fas fa-check"></i> Update Status
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     DELETE CONFIRM MODAL
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-body text-center py-4">
        <div style="font-size:48px;color:var(--ot-danger);margin-bottom:12px;"><i class="fas fa-trash-alt"></i></div>
        <h6 class="fw-bold">Delete Surgery?</h6>
        <p class="text-muted small mb-0">This action cannot be undone.</p>
        <input type="hidden" id="delete-surgery-id">
      </div>
      <div class="modal-footer border-0 justify-content-center gap-2">
        <button class="ot-btn ot-btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button class="ot-btn ot-btn-danger" onclick="OT.confirmDelete()">
          <i class="fas fa-trash"></i> Delete
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/ot_main.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    OT.loadRooms();
    OT.loadSurgeries();

    // Re-filter on Enter
    document.getElementById('filter-search').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') OT.loadSurgeries();
    });

    // Open "Schedule Surgery" modal automatically if redirected from dashboard
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'new') {
        setTimeout(() => {
            if (typeof OT.openAddModal === 'function') {
                OT.openAddModal();
            }
        }, 100);
    }
});
</script>
<?php require_once 'includes/ot_foot.php'; ?>
