<?php
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'IPD Returns Verification';
include 'includes/ph_head.php';
?>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">IPD Returns Verification</h1>
    <p class="ph-page-subtitle">Verify and process pharmacy return requests from Nurses</p>
  </div>
</div>

<div class="ph-card mb-4">
    <div class="ph-card-body p-3">
        <ul class="nav nav-pills" id="returnsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" onclick="loadRequests('PENDING')">Pending Requests</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-success" id="accepted-tab" data-bs-toggle="tab" data-bs-target="#accepted" type="button" role="tab" onclick="loadRequests('ACCEPTED')">Accepted</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-danger" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab" onclick="loadRequests('REJECTED')">Rejected</button>
            </li>
        </ul>
    </div>
</div>

<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table">
      <thead>
        <tr>
          <th>Requested At</th>
          <th>Patient Details</th>
          <th>Medicine</th>
          <th>Batch</th>
          <th class="text-center">Orig Qty</th>
          <th class="text-center">Return Qty</th>
          <th class="text-end">Return Amt</th>
          <th class="text-center" id="actionsHeader">Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

</div></div></div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let currentStatus = 'PENDING';

document.addEventListener('DOMContentLoaded', () => {
    loadRequests('PENDING');
    setInterval(() => {
        if(currentStatus === 'PENDING') loadRequests('PENDING', true);
    }, 15000);
});

async function loadRequests(status, silent = false) {
    currentStatus = status;
    const actionsHeader = document.getElementById('actionsHeader');
    
    if (status === 'PENDING') {
        actionsHeader.innerText = 'Actions';
    } else {
        actionsHeader.innerText = 'Processed By';
    }

    // Direct fetch

    // Fallback direct fetch
    fetch(`api/get_ipd_return_requests.php?status=${status}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderTable(data.data);
                // Update badge if pending
                if (status === 'PENDING' && document.getElementById('ipd-returns-badge')) {
                    const badge = document.getElementById('ipd-returns-badge');
                    if (data.data.length > 0) {
                        badge.innerText = data.data.length;
                        badge.style.display = 'inline-block';
                        badge.style.background = '#ef4444';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            } else {
                if(!silent) PH.error(data.message);
            }
        });
}

function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-check-circle fa-3x mb-3 text-light"></i><br>No ${currentStatus.toLowerCase()} return requests.</td></tr>`;
        return;
    }
    
    let html = '';
    data.forEach(req => {
        let actionHtml = '';
        if (currentStatus === 'PENDING') {
            actionHtml = `
                <button class="ph-btn ph-btn-sm ph-btn-success me-1" onclick="processReq(${req.request_id}, 'ACCEPT')" title="Accept"><i class="fas fa-check"></i> Accept</button>
                <button class="ph-btn ph-btn-sm ph-btn-danger" onclick="processReq(${req.request_id}, 'REJECT')" title="Reject"><i class="fas fa-times"></i> Reject</button>
            `;
        } else {
            actionHtml = `<span class="badge bg-secondary">${req.processed_by || 'System'}</span><br><small class="text-muted">${req.processed_at}</small>`;
        }
        
        html += `
            <tr>
                <td><div class="fw-bold">${req.requested_at}</div><small class="text-muted">By: ${req.requested_by}</small></td>
                <td><div class="fw-bold text-dark">${req.first_name} ${req.last_name || ''}</div><div class="text-muted" style="font-size:0.7rem;">Adm: ${req.admission_id} | Ward: ${req.ward} Bed: ${req.bed_id}</div></td>
                <td><strong>${req.medicine_name}</strong></td>
                <td>${req.batch_no || '-'}</td>
                <td class="text-center">${parseFloat(req.original_qty)}</td>
                <td class="text-center text-danger fw-bold">${parseFloat(req.return_qty)}</td>
                <td class="text-end fw-bold">₹${parseFloat(req.return_amount).toFixed(2)}</td>
                <td class="text-center">${actionHtml}</td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function processReq(id, action) {
    const actionText = action === 'ACCEPT' ? 'Accept this return and reduce billing amount?' : 'Reject this return request?';
    PH.confirm(actionText, '', () => {
        PH.loading('Processing...');
        fetch('api/process_ipd_return.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ request_id: id, action: action })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                PH.success(res.message);
                loadRequests(currentStatus);
            } else {
                PH.error(res.message);
            }
        })
        .catch(e => PH.error('Network Error'));
    });
}
</script>
</body>
</html>
