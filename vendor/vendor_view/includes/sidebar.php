<!-- ── UNIFIED SIDEBAR COMPONENT ── -->
<aside class="nexus-sidebar">
    <div class="nexus-brand">
        <div class="nexus-brand-icon"><i class="fas fa-shield-virus"></i></div>
        <span class="nexus-brand-name">MediVend</span>
    </div>

    <nav style="flex:1;">
        <a href="index.php" class="nav-link <?= $current_page == 'quotation' ? 'active' : '' ?>">
            <i class="fas fa-file-invoice"></i> Quotation
        </a>
        <a href="product_view.php" class="nav-link <?= $current_page == 'product' ? 'active' : '' ?>">
            <i class="fas fa-box-open"></i> Product
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="footer-name"><?= $vendorName ?></div>
        <div class="footer-logout" onclick="location.href='logout.php'">SIGN OUT</div>
    </div>
</aside>
