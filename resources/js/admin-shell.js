/**
 * Admin Shell state.
 * Responsibility: sidebar collapse, mobile drawer, topbar panels (notification, user menu).
 * Phase: 1 — Dashboard Polish.
 *
 * Rules:
 * - Only one panel can be open at a time (mutual exclusion).
 * - Escape closes the currently open panel.
 * - Focus returns to the triggering button on close.
 */

export default function adminShell() {
    return {
        /* Sidebar */
        collapsed: false,

        /* Admin theme: 'dark' (night) | 'glass' (day) */
        theme: 'dark',

        /* Mobile drawer */
        mobileOpen: false,
        accessibilityReady: false,

        /* Topbar panels */
        notifOpen: false,
        userMenuOpen: false,

        init() {
            this.accessibilityReady = window.__alpineFocusReady === true;

            try {
                this.collapsed = window.localStorage.getItem('adminSidebarCollapsed') === 'true';
            } catch {
                this.collapsed = false;
            }

            // Server already rendered the marker from the cookie; mirror it.
            this.theme = document.documentElement.dataset.adminTheme === 'glass' ? 'glass' : 'dark';
        },

        /* ── Theme ── */
        toggleTheme() {
            this.theme = this.theme === 'glass' ? 'dark' : 'glass';
            document.documentElement.dataset.adminTheme = this.theme;

            const oneYearInSeconds = 60 * 60 * 24 * 365;
            document.cookie = `pm_admin_theme=${this.theme};path=/;max-age=${oneYearInSeconds};samesite=lax`;

            try {
                window.localStorage.setItem('pmAdminTheme', this.theme);
            } catch { /* optional persistence */ }
        },

        /* ── Sidebar ── */
        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            try {
                window.localStorage.setItem('adminSidebarCollapsed', String(this.collapsed));
            } catch { /* optional persistence */ }
        },

        /* ── Mobile drawer ── */
        openMobile() {
            if (!this.accessibilityReady) return;
            this.closeAllPanels();
            this.mobileOpen = true;
        },

        closeMobile() {
            this.mobileOpen = false;
        },

        /* ── Notification panel ── */
        toggleNotif() {
            if (this.notifOpen) {
                this.closeNotif();
            } else {
                this.closeAllPanels();
                this.notifOpen = true;
            }
        },

        closeNotif() {
            if (!this.notifOpen) return;
            this.notifOpen = false;
            this.$nextTick(() => {
                this.$refs.notifTrigger?.focus();
            });
        },

        /* ── User menu ── */
        toggleUserMenu() {
            if (this.userMenuOpen) {
                this.closeUserMenu();
            } else {
                this.closeAllPanels();
                this.userMenuOpen = true;
            }
        },

        closeUserMenu() {
            if (!this.userMenuOpen) return;
            this.userMenuOpen = false;
            this.$nextTick(() => {
                this.$refs.userMenuTrigger?.focus();
            });
        },

        /* ── Shared ── */
        closeAllPanels() {
            this.notifOpen = false;
            this.userMenuOpen = false;
        },
    };
}
