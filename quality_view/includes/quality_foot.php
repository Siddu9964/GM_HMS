<?php
/**
 * quality_foot.php — Footer / Script Includes for Quality Portal
 * Mirrors lab_foot.php / ph_foot.php patterns
 */
?>
</div><!-- /.qsc-layout -->

<?php
$qscUserId    = (int)($_SESSION['user_id'] ?? 0);
$qscUserName  = $_SESSION['full_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'User';
$qscUserRole  = $_SESSION['role'] ?? 'Quality Manager';
$qscUserEmail = $_SESSION['email'] ?? '';
$qscUserPhone = $_SESSION['mobile_number'] ?? $_SESSION['mobile'] ?? $_SESSION['phone'] ?? '';
$qscUserPhoto = !empty($_SESSION['photo']) ? htmlspecialchars($_SESSION['photo']) : null;
$qscUserInit  = strtoupper(substr($qscUserName, 0, 1));
?>

<style>
.qsc-profile-control {
  padding: 0.6rem 1rem;
  border: 1px solid var(--qsc-border, #d9d2c2);
  border-radius: 8px;
  width: 100%;
  transition: 0.2s;
  background: #ffffff;
  color: #1e293b;
}
.qsc-profile-control:focus {
  border-color: var(--qsc-green, #1f6b4a);
  box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.15);
  outline: none;
}
.profile-view-mode {
  font-size: 1rem;
  padding: 0.6rem 0;
  color: #1e293b;
}
</style>

<!-- Global Profile & Security Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" id="profileModalDialog">
        
    <!-- Profile Section -->
    <div id="profileSectionWrapper" class="w-100">
        <form id="globalProfileForm" onsubmit="updateGlobalProfile(event)" class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;" enctype="multipart/form-data">
            <div class="d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, var(--qsc-green, #1f6b4a), var(--qsc-green-dark, #174f37)); color: white; padding: 1.5rem;">
                <span class="fs-5 fw-bold"><i class="fas fa-id-card me-2"></i>Profile Details</span>
                <div class="d-flex gap-2 position-relative" style="z-index: 1060;">
                    <button type="button" class="btn btn-sm" id="btnEditGlobalProfile" onclick="toggleGlobalProfileEdit(event)" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-edit me-1"></i> Edit
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body pt-0 position-relative bg-white">
                <div class="d-flex flex-column align-items-center mb-4" style="margin-top: -40px;">
                    <div class="position-relative">
                        <div id="profileAvatarPreview" style="width:110px;height:110px;border-radius:50%;background:var(--qsc-green, #1f6b4a);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700; overflow:hidden; border: 5px solid #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.15); transition: 0.3s;">
                            <?php if($qscUserPhoto): ?>
                                <img src="<?= $qscUserPhoto ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <span><?= $qscUserInit ?></span>
                            <?php endif; ?>
                        </div>
                        <label for="profilePhotoUpload" class="profile-edit-mode d-none position-absolute" style="bottom:5px; right:5px; background:var(--qsc-green, #1f6b4a); color:#fff; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: transform 0.2s; border: 2px solid #fff;">
                            <i class="fas fa-camera small"></i>
                        </label>
                        <input type="file" id="profilePhotoUpload" name="photo" accept="image/*" class="d-none" onchange="previewGlobalProfilePhoto(this)">
                    </div>
                    <h4 class="mt-3 mb-0 fw-bold"><?= htmlspecialchars($qscUserName) ?></h4>
                    <div class="text-muted small text-uppercase fw-bold mt-1" style="letter-spacing: 1px;"><?= htmlspecialchars($qscUserRole) ?></div>
                </div>
                
                <div class="row g-4 px-2 pb-2">
                    <div class="col-md-12">
                        <label class="text-muted small text-uppercase fw-bold mb-1">Full Name</label>
                        <div class="profile-view-mode fw-bold fs-6"><?= htmlspecialchars($qscUserName) ?></div>
                        <input type="text" class="qsc-profile-control profile-edit-mode d-none" name="full_name" value="<?= htmlspecialchars($qscUserName) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-bold mb-1">Email Address</label>
                        <div class="profile-view-mode fw-bold fs-6"><?= htmlspecialchars($qscUserEmail ?: '-') ?></div>
                        <input type="email" class="qsc-profile-control profile-edit-mode d-none" name="email" value="<?= htmlspecialchars($qscUserEmail) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-bold mb-1">Phone Number</label>
                        <div class="profile-view-mode fw-bold fs-6"><?= htmlspecialchars($qscUserPhone ?: '-') ?></div>
                        <input type="text" class="qsc-profile-control profile-edit-mode d-none" name="mobile_number" value="<?= htmlspecialchars($qscUserPhone) ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer profile-edit-mode d-none bg-light border-top">
                <button type="button" class="btn btn-outline-secondary me-2" onclick="toggleGlobalProfileEdit()">Cancel</button>
                <button type="submit" class="btn btn-success" style="background:var(--qsc-green, #1f6b4a); border-color:var(--qsc-green, #1f6b4a);"><i class="fas fa-save me-2"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Password Section -->
    <div id="securitySectionWrapper" class="d-none w-100">
        <form id="globalSecurityForm" onsubmit="changeGlobalPassword(event)" class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="row g-0">
                <!-- Left Info Panel -->
                <div class="col-md-5 p-4 border-end d-flex flex-column justify-content-center position-relative" style="background: #fdfbf7;">
                    <button type="button" class="btn-close position-absolute d-md-none" data-bs-dismiss="modal" aria-label="Close" style="top: 15px; right: 15px;"></button>
                    <div class="text-center mb-4">
                        <div style="width: 70px; height: 70px; background: rgba(31, 107, 74, 0.1); color: var(--qsc-green, #1f6b4a); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-center mb-3">Strong Password</h5>
                    <p class="text-muted small text-center mb-4">Protect your account with a secure password. We recommend following these best practices:</p>
                    
                    <div class="d-flex flex-column gap-2 small text-muted">
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> Minimum 8 characters long</div>
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> Use uppercase & lowercase letters</div>
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> Include at least one number</div>
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> Include a special character</div>
                    </div>
                </div>
                
                <!-- Right Form Panel -->
                <div class="col-md-7 p-4 bg-white position-relative">
                    <button type="button" class="btn-close position-absolute d-none d-md-block" data-bs-dismiss="modal" aria-label="Close" style="top: 15px; right: 15px;"></button>
                    <h6 class="fw-bold mb-4 mt-2"><i class="fas fa-key me-2"></i>Update Password</h6>
                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold text-uppercase">Current Password</label>
                            <div class="position-relative mt-1">
                                <input type="password" class="qsc-profile-control" name="current_password" style="padding-right: 40px;" required placeholder="Enter current password">
                                <button type="button" class="position-absolute" style="right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:var(--qsc-muted, #5a6e62); outline:none;" onclick="toggleGlobalPassword(this)">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold text-uppercase">New Password</label>
                            <div class="position-relative mt-1">
                                <input type="password" class="qsc-profile-control" name="new_password" id="global_new_password" style="padding-right: 40px;" required minlength="8" placeholder="Create new password">
                                <button type="button" class="position-absolute" style="right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:var(--qsc-muted, #5a6e62); outline:none;" onclick="toggleGlobalPassword(this)">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold text-uppercase">Confirm New Password</label>
                            <div class="position-relative mt-1">
                                <input type="password" class="qsc-profile-control" id="global_confirm_password" style="padding-right: 40px;" required minlength="8" placeholder="Verify new password">
                                <button type="button" class="position-absolute" style="right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:var(--qsc-muted, #5a6e62); outline:none;" onclick="toggleGlobalPassword(this)">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" style="background:var(--qsc-green, #1f6b4a); border-color:var(--qsc-green, #1f6b4a);"><i class="fas fa-lock me-2"></i> Update Password</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function toggleGlobalProfileEdit(e) {
    if (e) e.preventDefault();
    const editBtn = document.getElementById('btnEditGlobalProfile');
    if (!editBtn) return;
    
    const isCurrentlyViewMode = !editBtn.classList.contains('d-none');
    const viewEls = document.querySelectorAll('#profileModal .profile-view-mode');
    const editEls = document.querySelectorAll('#profileModal .profile-edit-mode');
    
    if (isCurrentlyViewMode) {
        viewEls.forEach(el => el.classList.add('d-none'));
        editEls.forEach(el => el.classList.remove('d-none'));
        editBtn.classList.add('d-none');
    } else {
        viewEls.forEach(el => el.classList.remove('d-none'));
        editEls.forEach(el => el.classList.add('d-none'));
        editBtn.classList.remove('d-none');
    }
}

function previewGlobalProfilePhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatarDiv = document.getElementById('profileAvatarPreview');
            avatarDiv.innerHTML = '<img src="'+e.target.result+'" alt="Profile" style="width:100%;height:100%;object-fit:cover;">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleGlobalPassword(btn) {
    const input = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

async function updateGlobalProfile(e) {
    e.preventDefault();
    const Toast = Swal.mixin({ toast: true, position: 'center', showConfirmButton: false, timer: 3000 });
    Toast.fire({ icon: 'info', title: 'Updating profile...' });
    
    const fd = new FormData(e.target);
    const userId = <?= json_encode($qscUserId) ?>;

    try {
        const res = await fetch('/GM_HMS/api/staff/' + userId + '/update-profile', {
            method: 'POST',
            body: fd
        }).then(r => r.json());

        if (res.success || res.status === 'success') {
            Toast.fire({ icon: 'success', title: 'Profile updated successfully' });
            setTimeout(() => window.location.reload(), 1000);
        } else {
            Swal.fire('Error', res.error || res.message || 'Failed to update profile', 'error');
        }
    } catch (err) { 
        Swal.fire('Error', 'An error occurred while updating profile', 'error'); 
    }
}

async function changeGlobalPassword(e) {
    e.preventDefault();
    const newPass = document.getElementById('global_new_password').value;
    const confirmPass = document.getElementById('global_confirm_password').value;
    
    if (newPass !== confirmPass) {
        Swal.fire('Error', 'New passwords do not match', 'error');
        return;
    }
    
    if (newPass.length < 8) {
        Swal.fire('Error', 'Password must be at least 8 characters', 'error');
        return;
    }

    const Toast = Swal.mixin({ toast: true, position: 'center', showConfirmButton: false, timer: 3000 });
    Toast.fire({ icon: 'info', title: 'Updating password...' });
    
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd.entries());

    try {
        const res = await fetch('/GM_HMS/api/auth/change-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());

        if (res.success || res.status === 'success') {
            Swal.fire('Success', res.message || 'Password changed successfully', 'success');
            e.target.reset();
            const modalEl = document.getElementById('profileModal');
            const modalInst = bootstrap.Modal.getInstance(modalEl);
            if (modalInst) modalInst.hide();
        } else {
            Swal.fire('Error', res.error || res.message || 'Failed to change password', 'error');
        }
    } catch (err) { 
        Swal.fire('Error', 'An error occurred while changing password', 'error'); 
    }
}

function openProfileModal(mode = 'profile') {
    const pSection = document.getElementById('profileSectionWrapper');
    const sSection = document.getElementById('securitySectionWrapper');
    const mDialog = document.getElementById('profileModalDialog');

    if (mode === 'security') {
        pSection.classList.add('d-none');
        sSection.classList.remove('d-none');
        mDialog.classList.remove('modal-md');
        mDialog.classList.add('modal-lg');
    } else {
        pSection.classList.remove('d-none');
        sSection.classList.add('d-none');
        mDialog.classList.remove('modal-lg');
        mDialog.classList.add('modal-md');
    }

    const modalEl = document.getElementById('profileModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }
    modal.show();
}

// Global polling for quality navbar alerts
async function updateQualityNotifications() {
    const badge = document.getElementById('qsc-notif-badge');
    const headerBadge = document.getElementById('qsc-notif-header-badge');
    const list = document.getElementById('qsc-notif-list');
    if (!badge || !list) return;

    try {
        if (typeof qscApi !== 'function') return;
        const res = await qscApi('/api/quality/dashboard');
        if (res && res.success && res.data) {
            const d = res.data;
            const pending = parseInt(d.pending_dispatch || 0, 10);
            const collectedToday = d.today && d.today.collected ? parseInt(d.today.collected.entries || 0, 10) : 0;
            const collectedWeight = d.today && d.today.collected ? parseFloat(d.today.collected.total_weight || 0).toFixed(1) : '0.0';

            let itemsHtml = '';
            let alertCount = 0;

            if (pending > 0) {
                alertCount += pending;
                itemsHtml += `
                    <a href="/GM_HMS/quality_view/bmw_dispatch.php" class="dropdown-item p-2 d-flex align-items-start gap-2 rounded-2 text-wrap mb-1" style="background:#fff7ed; border-left:3px solid #f97316;">
                        <i class="fas fa-truck-ramp-box text-warning mt-1" style="font-size:1.1rem;"></i>
                        <div>
                            <div class="fw-bold text-dark" style="font-size:0.83rem;">${pending} Waste Collection${pending > 1 ? 's' : ''} Awaiting Dispatch</div>
                            <div class="text-muted" style="font-size:0.74rem;">Ready for authorized vendor handover</div>
                        </div>
                    </a>
                `;
            }

            if (collectedToday > 0) {
                itemsHtml += `
                    <a href="/GM_HMS/quality_view/bmw_collection.php" class="dropdown-item p-2 d-flex align-items-start gap-2 rounded-2 text-wrap mb-1" style="background:#f0fdf4; border-left:3px solid #22c55e;">
                        <i class="fas fa-biohazard text-success mt-1" style="font-size:1.1rem;"></i>
                        <div>
                            <div class="fw-bold text-dark" style="font-size:0.83rem;">${collectedToday} Waste Log${collectedToday > 1 ? 's' : ''} Today (${collectedWeight} Kg)</div>
                            <div class="text-muted" style="font-size:0.74rem;">Hospital collection active</div>
                        </div>
                    </a>
                `;
            }

            if (!itemsHtml) {
                itemsHtml = '<div class="text-center text-muted py-3" style="font-size:0.82rem;"><i class="fas fa-check-circle text-success me-1"></i> No pending alerts. All waste up to date!</div>';
            }

            list.innerHTML = itemsHtml;

            if (alertCount > 0) {
                badge.innerText = alertCount;
                badge.style.display = 'inline-block';
                if (headerBadge) headerBadge.innerText = `${alertCount} Pending`;
            } else {
                badge.style.display = 'none';
                if (headerBadge) headerBadge.innerText = '0 New';
            }
        }
    } catch (e) {
        console.warn('Quality notification check error:', e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateQualityNotifications();
    setInterval(updateQualityNotifications, 20000);
});
</script>

</body>
</html>
