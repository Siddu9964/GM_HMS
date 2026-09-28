<?php
/**
 * ot_view/includes/ot_foot.php
 * Drop before </body> on every page to include global modals and scripts
 */
?>
<style>
.ot-profile-control { padding: 0.6rem 1rem; border: 1.5px solid var(--ot-border); border-radius: 8px; width: 100%; transition: 0.2s; font-size: 0.82rem; color: var(--ot-text); }
.ot-profile-control:focus { border-color: var(--ot-primary); box-shadow: 0 0 0 3px rgba(31,107,74,.1); outline: none; }
.profile-view-mode { font-size: 0.9rem; padding: 0.6rem 0; color: var(--ot-text); }
</style>

<!-- Global Profile & Security Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" id="profileModalDialog">
        
    <!-- Profile Section -->
    <div id="profileSectionWrapper" class="w-100">
        <form id="globalProfileForm" onsubmit="updateGlobalProfile(event)" class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;" enctype="multipart/form-data">
            <div class="d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, var(--ot-primary), #144d34); color: white; padding: 1.5rem;">
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
                    <?php $avatarUrl = !empty($_SESSION['photo']) ? htmlspecialchars($_SESSION['photo']) : null; ?>
                    <div class="position-relative">
                        <div id="profileAvatarPreview" style="width:100px;height:100px;border-radius:50%;background:var(--ot-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:700; overflow:hidden; border: 4px solid #fff; box-shadow: 0 6px 16px rgba(0,0,0,0.12); transition: 0.3s;">
                            <?php if($avatarUrl): ?>
                                <img src="<?= $avatarUrl ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <span><?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                        <label for="profilePhotoUpload" class="profile-edit-mode d-none position-absolute" style="bottom:2px; right:2px; background:var(--ot-primary); color:#fff; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: transform 0.2s; border: 2px solid #fff;">
                            <i class="fas fa-camera" style="font-size: 0.75rem;"></i>
                        </label>
                        <input type="file" id="profilePhotoUpload" name="photo" accept="image/*" class="d-none" onchange="previewGlobalProfilePhoto(this)">
                    </div>
                    <h5 class="mt-3 mb-0 fw-bold" style="color:var(--ot-text);"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></h5>
                    <div class="text-muted small text-uppercase fw-bold mt-1" style="letter-spacing: 1px; font-size: 0.7rem;"><?= htmlspecialchars($_SESSION['role'] ?? 'Scrub Nurse') ?></div>
                </div>
                
                <div class="row g-3 px-2 pb-2">
                    <div class="col-md-12">
                        <label class="ot-form-label mb-1">Full Name</label>
                        <div class="profile-view-mode fw-bold"><?= htmlspecialchars($_SESSION['full_name'] ?? '-') ?></div>
                        <input type="text" class="ot-profile-control profile-edit-mode d-none" name="full_name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ot-form-label mb-1">Email Address</label>
                        <div class="profile-view-mode fw-bold"><?= htmlspecialchars($_SESSION['email'] ?? '-') ?></div>
                        <input type="email" class="ot-profile-control profile-edit-mode d-none" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="ot-form-label mb-1">Phone Number</label>
                        <div class="profile-view-mode fw-bold"><?= htmlspecialchars($_SESSION['mobile_number'] ?? '-') ?></div>
                        <input type="text" class="ot-profile-control profile-edit-mode d-none" name="mobile_number" value="<?= htmlspecialchars($_SESSION['mobile_number'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer profile-edit-mode d-none bg-light border-top">
                <button type="button" class="ot-btn ot-btn-outline me-2" onclick="toggleGlobalProfileEdit()">Cancel</button>
                <button type="submit" class="ot-btn ot-btn-primary"><i class="fas fa-save me-2"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Password Section -->
    <div id="securitySectionWrapper" class="d-none w-100">
        <form id="globalSecurityForm" onsubmit="changeGlobalPassword(event)" class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="row g-0">
                <!-- Left Info Panel -->
                <div class="col-md-5 p-4 border-end d-flex flex-column justify-content-center position-relative" style="background: #fafaf8;">
                    <button type="button" class="btn-close position-absolute d-md-none" data-bs-dismiss="modal" aria-label="Close" style="top: 15px; right: 15px;"></button>
                    <div class="text-center mb-4">
                        <div style="width: 60px; height: 60px; background: rgba(31,107,74,.1); color: var(--ot-primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <h6 class="fw-bold text-center mb-3" style="color:var(--ot-text);">Strong Password</h6>
                    <p class="text-muted small text-center mb-4">Protect your account with a secure password. Recommendations:</p>
                    
                    <div class="d-flex flex-column gap-2 small text-muted">
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> Minimum 8 characters</div>
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> Uppercase & lowercase</div>
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> At least one number</div>
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> A special character</div>
                    </div>
                </div>
                
                <!-- Right Form Panel -->
                <div class="col-md-7 p-4 bg-white position-relative">
                    <button type="button" class="btn-close position-absolute d-none d-md-block" data-bs-dismiss="modal" aria-label="Close" style="top: 15px; right: 15px;"></button>
                    <h6 class="fw-bold mb-4 mt-2" style="color:var(--ot-text);"><i class="fas fa-key me-2"></i>Update Password</h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="ot-form-label mb-1">Current Password</label>
                            <div class="position-relative">
                                <input type="password" class="ot-profile-control" name="current_password" style="padding-right: 35px;" required placeholder="Enter current password">
                                <button type="button" class="position-absolute" style="right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:var(--ot-muted); outline:none;" onclick="toggleGlobalPassword(this)">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="ot-form-label mb-1">New Password</label>
                            <div class="position-relative">
                                <input type="password" class="ot-profile-control" name="new_password" id="global_new_password" style="padding-right: 35px;" required minlength="8" placeholder="Create new password">
                                <button type="button" class="position-absolute" style="right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:var(--ot-muted); outline:none;" onclick="toggleGlobalPassword(this)">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="ot-form-label mb-1">Confirm New Password</label>
                            <div class="position-relative">
                                <input type="password" class="ot-profile-control" id="global_confirm_password" style="padding-right: 35px;" required minlength="8" placeholder="Verify new password">
                                <button type="button" class="position-absolute" style="right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:var(--ot-muted); outline:none;" onclick="toggleGlobalPassword(this)">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="button" class="ot-btn ot-btn-outline me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="ot-btn ot-btn-primary"><i class="fas fa-lock me-2"></i> Update</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

  </div>
</div>

<!-- SweetAlert2 for beautiful popups -->
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
    const fd = new FormData(e.target);
    const userId = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;

    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
    btn.disabled = true;

    try {
        const res = await fetch(API_BASE + 'staff/' + userId + '/update-profile', {
            method: 'POST',
            body: fd
        }).then(r => r.json());

        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Profile Updated', text: 'Your profile has been successfully updated.', confirmButtonColor: '#1f6b4a', timer: 1500 });
            setTimeout(() => window.location.reload(), 1500);
        } else {
            Swal.fire({ icon: 'error', title: 'Update Failed', text: res.error || 'Failed to update profile.', confirmButtonColor: '#1f6b4a' });
        }
    } catch (err) { 
        Swal.fire({ icon: 'error', title: 'Error', text: 'A network error occurred.', confirmButtonColor: '#1f6b4a' });
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function changeGlobalPassword(e) {
    e.preventDefault();
    const newPass = document.getElementById('global_new_password').value;
    const confirmPass = document.getElementById('global_confirm_password').value;
    
    if (newPass !== confirmPass) {
        Swal.fire({ icon: 'error', title: 'Mismatch', text: 'New passwords do not match.', confirmButtonColor: '#1f6b4a' });
        return;
    }
    if (newPass.length < 8) {
        Swal.fire({ icon: 'error', title: 'Too Short', text: 'Password must be at least 8 characters.', confirmButtonColor: '#1f6b4a' });
        return;
    }

    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd.entries());
    
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Updating...';
    btn.disabled = true;

    try {
        const res = await fetch(API_BASE + 'auth/change-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());

        if (res.success || res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Password Changed', text: res.message || 'Your password was successfully updated.', confirmButtonColor: '#1f6b4a' });
            e.target.reset();
            bootstrap.Modal.getInstance(document.getElementById('profileModal')).hide();
        } else {
            Swal.fire({ icon: 'error', title: 'Update Failed', text: res.error || res.message || 'Failed to change password.', confirmButtonColor: '#1f6b4a' });
        }
    } catch (err) { 
        Swal.fire({ icon: 'error', title: 'Error', text: 'A network error occurred.', confirmButtonColor: '#1f6b4a' });
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
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

    const modal = new bootstrap.Modal(document.getElementById('profileModal'));
    modal.show();
}
</script>
</body>
</html>
