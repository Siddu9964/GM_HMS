/**
 * GM HMS - Admin Common JS
 * Handles Sidebar toggles for mobile responsiveness
 */

function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlayId = 'sidebarOverlay';
    let overlay = document.getElementById(overlayId);

    if (sidebar.classList.contains('-translate-x-full')) {
        // Open sidebar
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        
        // Create overlay
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = overlayId;
            overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden';
            overlay.onclick = toggleSidebar;
            document.body.appendChild(overlay);
        }
        document.body.style.overflow = 'hidden';
    } else {
        // Close sidebar
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        
        // Remove overlay
        if (overlay) {
            overlay.remove();
        }
        document.body.style.overflow = 'auto';
    }
}

// Close sidebar on window resize if it gets back to desktop view
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) { // lg breakpoint
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar) {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full'); // Reset state for mobile
        }
        
        if (overlay) {
            overlay.remove();
        }
        document.body.style.overflow = 'auto';
    }
});

function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// Close dropdown when clicking outside
window.onclick = function(event) {
    if (!event.target.matches('.flex.items-center.space-x-3') && 
        !event.target.matches('.flex.items-center.space-x-3 *')) {
        const dropdowns = document.getElementsByClassName("dropdown-menu");
        for (let i = 0; i < dropdowns.length; i++) {
            dropdowns[i].classList.remove('show');
        }
    }
}
