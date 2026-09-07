<?php
/**
 * RIS Foot Include — rad_foot.php
 * Outputs closing tags, profile modals, and standard scripts.
 */
?>
</div><!-- /.lis-layout -->

<style>
.ph-form-control { padding: 0.6rem 1rem; border: 1px solid var(--lis-border, #e2e8f0); border-radius: 8px; width: 100%; transition: 0.2s; }
.ph-form-control:focus { border-color: var(--lis-primary, #1f6b4a); box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.1); outline: none; }
.profile-view-mode { font-size: 1rem; padding: 0.6rem 0; }
</style>

<!-- Global Profile & Security Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" id="profileModalDialog">
        
    <!-- Profile Section -->
    <div id="profileSectionWrapper" class="w-100">
        <form id="globalProfileForm" onsubmit="updateGlobalProfile(event)" class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;" enctype="multipart/form-data">
            <div class="d-flex justify-content-between align-items-center" style="background: #1f6b4a; color: #f3efe6; padding: 1.5rem;">
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
                        <div id="profileAvatarPreview" style="width:110px;height:110px;border-radius:50%;background:var(--lis-primary, #1f6b4a);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700; overflow:hidden; border: 5px solid #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.15); transition: 0.3s;">
                            <?php if($avatarUrl): ?>
                                <img src="<?= $avatarUrl ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <span><?= strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'R', 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h4 class="mt-3 mb-0 fw-bold"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></h4>
                    <div class="text-muted small text-uppercase fw-bold mt-1" style="letter-spacing: 1px;"><?= htmlspecialchars($_SESSION['role'] ?? 'Radiologist') ?></div>
                </div>
                
                <div class="row g-4 px-2 pb-2">
                    <div class="col-md-12">
                        <label class="ph-label text-muted small text-uppercase fw-bold mb-1">Full Name</label>
                        <div class="profile-view-mode fw-bold fs-6"><?= htmlspecialchars($_SESSION['full_name'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="ph-label text-muted small text-uppercase fw-bold mb-1">Email Address</label>
                        <div class="profile-view-mode fw-bold fs-6"><?= htmlspecialchars($_SESSION['email'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="ph-label text-muted small text-uppercase fw-bold mb-1">Phone Number</label>
                        <div class="profile-view-mode fw-bold fs-6"><?= htmlspecialchars($_SESSION['mobile_number'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- Core Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Radiology Core Script -->
<script src="/GM_HMS/radiology_view/assets/js/radiology.js?v=<?= time() ?>"></script>
<script src="/GM_HMS/radiology_view/assets/js/notifications.js?v=<?= time() ?>"></script>

<script>
(function () {
  const BREAKPOINT = 1024;
  function getSidebar() { return document.getElementById('lis-sidebar'); }
  function getOverlay() { return document.getElementById('lis-sidebar-overlay'); }

  window.lisOpenSidebar = function () {
    const s = getSidebar();
    const o = getOverlay();
    if (s) s.classList.add('sidebar-open');
    if (o) o.classList.add('visible');
    document.body.style.overflow = 'hidden';
  };

  window.lisCloseSidebar = function () {
    const s = getSidebar();
    const o = getOverlay();
    if (s) s.classList.remove('sidebar-open');
    if (o) o.classList.remove('visible');
    document.body.style.overflow = '';
  };

  window.lisToggleSidebar = function () {
    const s = getSidebar();
    if (!s) return;
    if (s.classList.contains('sidebar-open')) lisCloseSidebar();
    else lisOpenSidebar();
  };

  window.addEventListener('resize', function () {
    if (window.innerWidth >= BREAKPOINT) lisCloseSidebar();
  });
})();
</script>
</body>
</html>
