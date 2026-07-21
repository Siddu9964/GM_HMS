/**
 * GM_HMS — Global Sidebar Toggle
 * Collapsible sidebar with localStorage persistence
 * Works across all 5 role views (Admin, Doctor, Reception, Nurse, Pharmacy)
 */

(function () {
    'use strict';

    if (window.gmSidebarInitialized) {
        return;
    }
    window.gmSidebarInitialized = true;

    const STORAGE_KEY = 'gm_sidebar_collapsed';
    const COLLAPSED_CLASS = 'sidebar-collapsed';
    const OPEN_CLASS = 'sidebar-open'; // mobile

    // ── Restore saved state on load ──
    document.addEventListener('DOMContentLoaded', function () {
        const saved = localStorage.getItem(STORAGE_KEY);
        // Default to collapsed (true) if no preference saved yet
        if (saved === null || saved === 'true') {
            document.body.classList.add(COLLAPSED_CLASS);
        } else {
            document.body.classList.remove(COLLAPSED_CLASS);
        }

        // Bind toggle buttons
        const toggleBtns = document.querySelectorAll(
            '#gm-sidebar-toggle, .gm-sidebar-toggle, #sidebarToggle, #sidebarToggleBtn'
        );

        toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', toggleSidebar);
        });

        // Mobile overlay click closes sidebar
        const overlay = document.querySelector('.gm-sidebar-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeMobileSidebar);
        }

        // Update icon direction
        updateToggleIcon();
    });

    // ── Toggle on desktop (collapsed ↔ expanded) ──
    function toggleSidebar() {
        const isMobile = window.innerWidth < 1024;

        if (isMobile) {
            document.body.classList.toggle(OPEN_CLASS);
        } else {
            document.body.classList.toggle(COLLAPSED_CLASS);
            const isNowCollapsed = document.body.classList.contains(COLLAPSED_CLASS);
            localStorage.setItem(STORAGE_KEY, isNowCollapsed);
            updateToggleIcon();
        }
    }

    function closeMobileSidebar() {
        document.body.classList.remove(OPEN_CLASS);
    }

    // Update hamburger icon transform direction
    function updateToggleIcon() {
        const isCollapsed = document.body.classList.contains(COLLAPSED_CLASS);
        const icons = document.querySelectorAll(
            '#gm-sidebar-toggle i, .gm-sidebar-toggle i, #sidebarToggle i, #sidebarToggleBtn i'
        );
        icons.forEach(function (icon) {
            // Rotate icon to indicate direction
            icon.style.transform = isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
            icon.style.transition = 'transform 0.3s ease';
        });
    }

    // ── Handle window resize ──
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) {
            document.body.classList.remove(OPEN_CLASS);
        }
    });

    // ── Expose toggle function globally ──
    window.gmToggleSidebar = toggleSidebar;
    window.toggleSidebar = toggleSidebar; // backward compat alias

    // ── Floating tooltips for collapsed icons ──
    (function () {
        let activeTooltip = null;

        document.addEventListener('mouseenter', function (e) {
            // Only show tooltips if sidebar is collapsed
            if (!document.body.classList.contains(COLLAPSED_CLASS)) {
                return;
            }

            const target = e.target.closest('.gm-nav-link[data-tooltip], .gm-sidebar-user[data-tooltip]');
            if (!target) return;

            const text = target.getAttribute('data-tooltip');
            if (!text) return;

            // Remove any existing active tooltip
            if (activeTooltip) {
                activeTooltip.remove();
            }

            // Create tooltip element
            activeTooltip = document.createElement('div');
            activeTooltip.className = 'gm-floating-tooltip';
            activeTooltip.textContent = text;
            document.body.appendChild(activeTooltip);

            // Position calculation
            const targetRect = target.getBoundingClientRect();
            const tooltipHeight = activeTooltip.offsetHeight;
            
            const topPos = targetRect.top + (targetRect.height - tooltipHeight) / 2 + window.scrollY;
            const leftPos = targetRect.right + 10 + window.scrollX;

            activeTooltip.style.top = topPos + 'px';
            activeTooltip.style.left = leftPos + 'px';
            
            // Trigger animation
            requestAnimationFrame(function () {
                if (activeTooltip) {
                    activeTooltip.classList.add('visible');
                }
            });
        }, true);

        document.addEventListener('mouseleave', function (e) {
            const target = e.target.closest('.gm-nav-link[data-tooltip], .gm-sidebar-user[data-tooltip]');
            if (target && activeTooltip) {
                const tooltipToRemove = activeTooltip;
                activeTooltip = null;
                
                tooltipToRemove.classList.remove('visible');
                setTimeout(function () {
                    tooltipToRemove.remove();
                }, 150);
            }
        }, true);
    })();

})();
