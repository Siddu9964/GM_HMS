<?php
$pageTitle = 'Results Management';
$pageIcon  = 'fa-list-alt';
$navTitle  = 'Lab Results';
$navSub    = 'View, edit, and print laboratory results';
require_once 'includes/lab_head.php';
require_once __DIR__ . '/../models/Database.php';

$db = new \Database();
$db->connect();

$dateFilter = $_GET['date'] ?? 'all';
$isAll = ($dateFilter === 'all');
$sourceFilter = strtoupper($_GET['source'] ?? 'ALL');

$sql = "
    SELECT * FROM (
        SELECT lr.result_id, lr.order_id, lr.patient_id, lr.test_name, lr.result_data, lr.abnormal_flags, 
               lr.result_date, lr.result_time, lr.report_file, lr.reviewed_by, lr.reviewed_at, lr.status, 
               lr.created_at, lr.patient_type,
               CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
               'OPD' AS order_source
        FROM lab_results lr
        LEFT JOIN patient p ON lr.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci
        
        UNION ALL
        
        SELECT ilr.result_id, ilr.order_id, ilr.patient_id, ilr.test_name, ilr.result_data, ilr.abnormal_flags, 
               ilr.result_date, ilr.result_time, ilr.report_file, ilr.reviewed_by, ilr.reviewed_at, ilr.status, 
               ilr.created_at, ilr.patient_type,
               CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
               'IPD' AS order_source
        FROM ipd_lab_results ilr
        LEFT JOIN patient p ON ilr.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci
    ) AS combined_results
";
$params = [];
if (!$isAll) {
    $sql .= " WHERE result_date = ?";
    $params[] = $dateFilter;
}
$sql .= " ORDER BY result_date DESC, result_time DESC";

$results = $db->fetchAll($sql, $params);
?>
<?php require_once 'includes/lab_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/lab_navbar.php'; ?>

<div class="lis-content" style="background: #f8fafc; min-height: calc(100vh - var(--lis-navbar-h));">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up" style="padding-bottom:16px;">
    <div style="flex: 1;">
      <div class="lis-page-title">
        <div class="lis-page-title-icon" style="background: linear-gradient(135deg, var(--lis-primary), #0d9488); color: white; border: none; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);"><i class="fas fa-list-alt"></i></div>
        <div>
          Results Management
          <div class="lis-page-subtitle">View, edit, and print laboratory test results</div>
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="kanban-search-wrapper" style="position: relative;">
          <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--lis-text-muted); font-size: 0.85rem;"></i>
          <input type="text" id="resultSearchInput" placeholder="Search patient or test..." class="lis-input" style="padding-left: 32px; border-radius: 20px; width: 250px;" autocomplete="off" onkeyup="filterResults()">
      </div>
      <select id="result-source" class="lis-input lis-select" style="width:auto; border-radius: 20px;" onchange="filterResults()">
        <option value="all" <?= $sourceFilter === 'ALL' ? 'selected' : '' ?>>All Types</option>
        <option value="OPD" <?= $sourceFilter === 'OPD' ? 'selected' : '' ?>>OPD</option>
        <option value="IPD" <?= $sourceFilter === 'IPD' ? 'selected' : '' ?>>IPD</option>
      </select>
      <select id="result-date" class="lis-input lis-select" style="width:auto; border-radius: 20px;" onchange="changeDate()">
        <option value="<?= date('Y-m-d') ?>" <?= $dateFilter === date('Y-m-d') ? 'selected' : '' ?>>Today</option>
        <option value="all" <?= $isAll ? 'selected' : '' ?>>All Dates</option>
      </select>
      <a href="kanban.php" class="lis-btn lis-btn-outline" style="border-radius: 20px;">
        <i class="fas fa-sync-alt"></i> Refresh
      </a>
      <a href="test_orders.php" class="lis-btn lis-btn-primary" style="border-radius: 20px; background: linear-gradient(135deg, var(--lis-primary), #0d9488); border: none; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);">
        <i class="fas fa-plus"></i> New Order
      </a>
    </div>
  </div>

  <!-- Results Table -->
  <div class="lis-card lis-fade-up-1" style="border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
    <div class="lis-card-body" style="padding: 0;">
        <table class="lis-table" id="resultsTable" style="margin: 0; width: 100%;">
            <thead>
                <tr style="background: #f1f5f9;">
                    <th style="padding: 14px 16px;">Date & Time</th>
                    <th>Order ID</th>
                    <th>Patient</th>
                    <th>Test Name</th>
                    <th>Status</th>
                    <th style="text-align: right; padding-right: 16px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px 20px; color: #64748b;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i><br>
                        No results found for the selected date.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($results as $row): 
                        // Try to parse JSON test names cleanly
                        $testName = $row['test_name'];
                        try {
                            $decoded = json_decode($testName, true);
                            if (is_array($decoded)) {
                                $testName = implode(', ', $decoded);
                            }
                        } catch (Exception $e) {}
                        
                        $status = $row['status'] ?? 'Pending Review';
                        $statusBadge = 'lis-badge-routine';
                        if (str_contains(strtolower($status), 'review')) $statusBadge = 'lis-badge-urgent';
                        elseif (str_contains(strtolower($status), 'complete') || str_contains(strtolower($status), 'report')) $statusBadge = 'lis-badge-stat';
                    ?>
                    <tr class="result-row" data-search="<?= htmlspecialchars(strtolower($row['order_id'] . ' ' . $row['patient_name'] . ' ' . $testName)) ?>" data-source="<?= htmlspecialchars($row['order_source'] ?? 'OPD') ?>">
                        <td style="padding: 14px 16px; font-weight: 500; color: #334155;">
                            <?= htmlspecialchars(date('d M Y', strtotime($row['result_date']))) ?>
                            <div style="font-size: 0.75rem; color: #94a3b8;"><i class="far fa-clock"></i> <?= htmlspecialchars($row['result_time']) ?></div>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: #0ea5e9;">#<?= htmlspecialchars($row['order_id']) ?></span>
                            <?php if (($row['order_source'] ?? '') === 'IPD'): ?>
                                <span class="badge bg-danger" style="margin-left: 5px; font-size: 0.65rem;">IPD</span>
                            <?php else: ?>
                                <span class="badge bg-primary" style="margin-left: 5px; font-size: 0.65rem;">OPD</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem;">
                                    <?= htmlspecialchars(substr($row['patient_name'] ?? 'P', 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($row['patient_name'] ?? 'Unknown Patient') ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b;">ID: <?= htmlspecialchars($row['patient_id']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight: 600; color: #334155;">
                            <?= htmlspecialchars($testName) ?>
                        </td>
                        <td>
                            <span class="lis-badge <?= $statusBadge ?>"><?= htmlspecialchars($status) ?></span>
                        </td>
                        <td style="text-align: right; padding-right: 16px;">
                            <button type="button" onclick="editResult('<?= htmlspecialchars($row['order_id']) ?>', '<?= htmlspecialchars($row['order_source'] ?? 'OPD') ?>')" class="lis-btn lis-btn-outline" style="padding: 4px 12px; font-size: 0.75rem; border-color: #cbd5e1; color: #475569; margin-right: 4px; background:transparent; cursor:pointer;">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="print_result.php?order_id=<?= urlencode($row['order_id']) ?>&source=<?= urlencode($row['order_source'] ?? 'OPD') ?>" target="_blank" class="lis-btn lis-btn-outline" style="padding: 4px 12px; font-size: 0.75rem; border-color: #cbd5e1; color: #475569; background:transparent; text-decoration:none;">
                                <i class="fas fa-print"></i> Print
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
  </div>

<style>
/* Smooth hover effects for the table */
#resultsTable tbody tr {
    transition: background-color 0.2s, transform 0.1s;
    border-bottom: 1px solid #f1f5f9;
}
#resultsTable tbody tr:last-child {
    border-bottom: none;
}
#resultsTable tbody tr:hover {
    background-color: #f8fafc;
    box-shadow: inset 2px 0 0 var(--lis-primary);
}
</style>

<script>
function changeDate() {
    const val = document.getElementById('result-date').value;
    window.location.href = 'kanban.php?date=' + val;
}

function filterResults() {
    const query = document.getElementById('resultSearchInput').value.toLowerCase().trim();
    const sourceFilter = document.getElementById('result-source').value;
    const rows = document.querySelectorAll('.result-row');
    
    rows.forEach(row => {
        const matchesQuery = !query || row.dataset.search.includes(query);
        const matchesSource = sourceFilter === 'all' || row.dataset.source === sourceFilter;
        
        if (matchesQuery && matchesSource) {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    });
}

// Initial filter apply on load to handle ?source= url parameter
document.addEventListener('DOMContentLoaded', () => {
    filterResults();
});


let currentEditId = null;
let editBsModal = null;
let currentSource = 'OPD';

async function editResult(orderId, source = 'OPD') {
    if (!editBsModal) {
        editBsModal = new bootstrap.Modal(document.getElementById('editModal'));
    }
    currentEditId = orderId;
    currentSource = source;
    
    document.getElementById('em-order-id').textContent = orderId;
    const tbody = document.getElementById('em-tbody');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">Loading...</td></tr>';
    
    editBsModal.show();

    try {
        const apiBase = (source === 'IPD') ? '/GM_HMS/api/laboratory/ipd-orders/' : '/GM_HMS/api/laboratory/orders/';
        const res = await fetch(apiBase + encodeURIComponent(orderId) + '/result');
        const data = await res.json();
        
        tbody.innerHTML = '';
        if (data.success && data.data && data.data.result_data) {
            let params = [];
            try { params = JSON.parse(data.data.result_data); } catch(e){}
            if(params.length > 0) {
                params.forEach(p => emAddRow(p.name, p.value, p.unit, p.range));
            } else {
                emAddRow();
            }
        } else {
            emAddRow();
        }
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:red;">Error loading results</td></tr>';
    }
}

function emAddRow(name='', val='', unit='', range='') {
    name = (name || '').toString();
    val = (val || '').toString();
    unit = (unit || '').toString();
    range = (range || '').toString();
    
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid #f1f5f9';
    tr.innerHTML = `
        <td style="padding:6px 4px;"><input type="text" class="em-name ph-form-control" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px;" value="${name.replace(/"/g, '&quot;')}"></td>
        <td style="padding:6px 4px;"><input type="text" class="em-val ph-form-control" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px;" value="${val.replace(/"/g, '&quot;')}"></td>
        <td style="padding:6px 4px;"><input type="text" class="em-unit ph-form-control" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px;" value="${unit.replace(/"/g, '&quot;')}"></td>
        <td style="padding:6px 4px;"><input type="text" class="em-range ph-form-control" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px;" value="${range.replace(/"/g, '&quot;')}"></td>
        <td style="padding:6px 4px; text-align:center;"><button type="button" onclick="this.closest('tr').remove()" style="background:none;border:none;color:#ef4444;cursor:pointer;"><i class="fas fa-trash"></i></button></td>
    `;
    document.getElementById('em-tbody').appendChild(tr);
}

async function emSave() {
    const btn = document.getElementById('em-btn-save');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    const rows = document.querySelectorAll('#em-tbody tr');
    let params = [];
    rows.forEach(tr => {
        const name = tr.querySelector('.em-name')?.value.trim();
        if(name) {
            params.push({
                name: name,
                value: tr.querySelector('.em-val')?.value.trim() || '',
                unit: tr.querySelector('.em-unit')?.value.trim() || '',
                range: tr.querySelector('.em-range')?.value.trim() || ''
            });
        }
    });
    
    const payload = { result_data: JSON.stringify(params) };
    
    try {
        const apiBase = (currentSource === 'IPD') ? '/GM_HMS/api/laboratory/ipd-orders/' : '/GM_HMS/api/laboratory/orders/';
        const res = await fetch(apiBase + encodeURIComponent(currentEditId) + '/result', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success) {
            alert('Results saved successfully!');
            editBsModal.hide();
        } else {
            alert(data.message || 'Error saving results');
        }
    } catch(e) {
        alert('Connection error');
    }
    
    btn.innerHTML = '<i class="fas fa-save"></i> Save Results';
    btn.disabled = false;
}
</script>

<!-- Bootstrap Result Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
      <div class="modal-header" style="border-bottom:1px solid #e2e8f0; padding:15px 20px;">
        <h5 class="modal-title" style="margin:0; font-weight:600; color:#1e293b;">
          Edit Results: <span id="em-order-id" style="color:var(--lis-primary)"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <div class="table-responsive">
          <table style="width:100%; border-collapse:collapse; font-size:0.85rem; min-width: 500px;" id="em-table">
              <thead>
                  <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                      <th style="padding:10px 8px; text-align:left; font-weight:600;">Parameter</th>
                      <th style="padding:10px 8px; text-align:left; font-weight:600;">Result</th>
                      <th style="padding:10px 8px; text-align:left; font-weight:600;">Unit</th>
                      <th style="padding:10px 8px; text-align:left; font-weight:600;">Range</th>
                      <th></th>
                  </tr>
              </thead>
              <tbody id="em-tbody">
              </tbody>
          </table>
        </div>
        
        <div style="margin-top:15px;">
            <button type="button" onclick="emAddRow()" class="lis-btn lis-btn-outline" style="font-size:0.75rem; padding:6px 12px; border-color:#cbd5e1; color:#475569; background:transparent;"><i class="fas fa-plus"></i> Add Row</button>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid #e2e8f0; padding:15px 20px;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" onclick="emSave()" class="btn btn-primary" id="em-btn-save" style="background:#10b981; border:none;"><i class="fas fa-save"></i> Save Results</button>
      </div>
    </div>
  </div>
</div>

</div><!-- /.lis-content -->

<?php require_once 'includes/lab_foot.php'; ?>
