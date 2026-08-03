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

export const normalizeAdminTheme = (value) => value === 'glass' ? 'glass' : 'dark';

const readAdminTheme = () => {
    const marker = globalThis.document?.documentElement?.dataset?.adminTheme;

    if (marker === 'dark' || marker === 'glass') {
        return marker;
    }

    try {
        return normalizeAdminTheme(globalThis.localStorage?.getItem('pmAdminTheme'));
    } catch {
        return 'dark';
    }
};

const applyAdminTheme = (theme) => {
    const normalized = normalizeAdminTheme(theme);
    const root = globalThis.document?.documentElement;

    if (root?.dataset) {
        root.dataset.adminTheme = normalized;
    }

    try {
        if (globalThis.document) {
            const oneYearInSeconds = 60 * 60 * 24 * 365;
            globalThis.document.cookie = `pm_admin_theme=${normalized};path=/;max-age=${oneYearInSeconds};samesite=lax`;
        }
        globalThis.localStorage?.setItem('pmAdminTheme', normalized);
    } catch {
        /* optional persistence */
    }

    return normalized;
};

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
            this.accessibilityReady = globalThis.window?.__alpineFocusReady === true
                || globalThis.__alpineFocusReady === true;

            try {
                this.collapsed = globalThis.localStorage?.getItem('adminSidebarCollapsed') === 'true';
            } catch {
                this.collapsed = false;
            }

            // The server marker wins when present; missing or malformed state is dark.
            this.theme = applyAdminTheme(readAdminTheme());
        },

        /* ── Theme ── */
        toggleTheme() {
            this.theme = applyAdminTheme(
                normalizeAdminTheme(this.theme) === 'glass' ? 'dark' : 'glass'
            );
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
