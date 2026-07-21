<!-- ── UNIFIED TOPBAR COMPONENT ── -->
<header class="nexus-navbar">
    <div class="nexus-navbar-left">
        <div class="nexus-breadcrumb">
            <i class="fas fa-home"></i>
            <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
            <span class="current"><?= $page_title ?? 'Dashboard' ?></span>
        </div>
        <div class="nexus-search">
            <i class="fas fa-search" style="color:#9ca3af;font-size:0.85rem;"></i>
            <input type="text" id="nexusSearchGlobal" placeholder="Search indents, products…">
            <div class="nexus-search-k">⌘K</div>
        </div>
    </div>
    <div class="nexus-actions">
        <div class="nexus-action-icon" title="Refresh" onclick="location.reload()" style="cursor:pointer;">
            <i class="fas fa-sync-alt"></i>
        </div>
        <div class="nexus-action-icon" title="Notifications">
            <i class="far fa-bell"></i>
            <div class="nexus-badge"></div>
        </div>
        <div style="height:28px;width:1px;background:rgba(0,0,0,0.07);"></div>
        <div class="avatar-circle" style="width:38px;height:38px;border-radius:10px;cursor:default;" title="<?= $vendorName ?>">
            <?= strtoupper(substr($vendorName,0,2)) ?>
        </div>
    </div>
</header>
