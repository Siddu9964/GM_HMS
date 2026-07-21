/**
 * Common Responsiveness JS
 * Handlers for Mobile Sidebar, Navbar Dropdowns, and Overlays
 */

function toggleSidebar() {
    // Detect which sidebar is present based on the section
    let sidebar = document.getElementById('adminSidebar') ||
        document.getElementById('doctorSidebar') ||
        document.getElementById('receptionSidebar') ||
        document.getElementById('pharmacySidebar');

    if (!sidebar) return;

    const isOpen = sidebar.style.transform === 'translateX(0px)' ||
        sidebar.classList.contains('translate-x-0') ||
        sidebar.classList.contains('open');

    if (isOpen && !sidebar.classList.contains('-translate-x-full')) {
        // Close sidebar
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.remove('open');
        removeOverlay();
        document.body.style.overflow = '';
    } else {
        // Open sidebar
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        sidebar.classList.add('open');
        createOverlay();
        document.body.style.overflow = 'hidden';
    }
}

function createOverlay() {
    if (document.getElementById('sidebarOverlay')) return;

    const overlay = document.createElement('div');
    overlay.id = 'sidebarOverlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300';
    overlay.onclick = toggleSidebar;
    document.body.appendChild(overlay);
}

function removeOverlay() {
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.remove(), 300);
    }
}

function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    if (!dropdown) return;

    const isHidden = dropdown.classList.contains('hidden');

    // Close all other dropdowns first
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== id) menu.classList.add('hidden');
    });

    if (isHidden) {
        dropdown.classList.remove('hidden');
    } else {
        dropdown.classList.add('hidden');
    }
}

// Close dropdowns and sidebar on window resize if moving to large screen
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        let sidebar = document.getElementById('adminSidebar') ||
            document.getElementById('doctorSidebar') ||
            document.getElementById('receptionSidebar') ||
            document.getElementById('pharmacySidebar');

        if (sidebar) {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.remove('open');
            sidebar.classList.remove('-translate-x-full');
            document.body.style.overflow = '';
            removeOverlay();
        }
    }
});
