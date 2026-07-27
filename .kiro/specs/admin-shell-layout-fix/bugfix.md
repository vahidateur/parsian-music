# Bugfix Requirements Document

## Introduction

The admin panel shell layout has multiple issues affecting scroll behavior, positioning, RTL correctness, design system consistency, and architectural ownership. This bugfix stabilizes the Shell as the single source of truth for layout — ensuring sidebar never scrolls, topbar stays sticky, scroll is isolated to the content area only, no horizontal overflow exists, all classes follow the `admin-shell__*` BEM namespace, and no hardcoded/Tailwind utility classes exist for layout or colors. This fix also establishes a regression-safe contract for all current and future admin modules.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN the user scrolls the page content THEN the sidebar may move with the scroll because scroll is not isolated to the content area

1.2 WHEN page content exceeds the viewport height THEN scroll happens on the body/page level rather than being contained inside the content area only

1.3 WHEN the viewport is narrow or content is wide THEN horizontal overflow/scrollbar may appear because there is no explicit `overflow-x` containment on the shell or body

1.4 WHEN RTL layout is active THEN main content offset and sidebar positioning may not be fully correct across all viewport widths due to inconsistent logical property usage

1.5 WHEN `layouts/admin.blade.php` is used THEN it applies hardcoded Tailwind utility classes (`bg-slate-950 text-white`) instead of Design System token-based classes

1.6 WHEN the admin shell renders at 1440px, 1600px, 1920px, or 2560px THEN layout may not properly constrain or fill content because explicit handling for these widths is missing

1.7 WHEN multiple admin views exist (dashboard, teachers list, students list, etc.) THEN some views may define their own sidebar, topbar, or margin/padding/width instead of using the single Shell component

1.8 WHEN CSS classes are inspected THEN old namespace patterns (e.g., `admin-sidebar`, `admin-topbar`, `sidebar`, `sidebar-nav`, `topbar`, `content`) may exist alongside the correct `admin-shell__*` BEM namespace

1.9 WHEN Blade templates for admin pages are inspected THEN inline layout utilities (`ml-64`, `mr-64`, `pl-64`, `pr-64`, `fixed`, `sticky`, `left-0`, `right-0`, `top-0`) may exist in templates instead of being defined solely in Shell CSS

1.10 WHEN components or pages override shell-owned properties (sidebar width, content offset, topbar height, z-index, safe area, padding) THEN layout becomes inconsistent and breaks at certain viewports

### Expected Behavior (Correct)

2.1 WHEN the user scrolls the page content THEN the sidebar SHALL remain fixed in position (never move) and only the content area inside `.admin-shell__content` SHALL scroll

2.2 WHEN page content exceeds the viewport height THEN scroll SHALL be isolated: `body` with `overflow: hidden`, and the content region with `overflow-y: auto` — creating a single scroll container

2.3 WHEN any viewport width is used THEN the shell SHALL prevent horizontal overflow by containing all elements within the viewport width (no horizontal scrollbar at any size)

2.4 WHEN RTL layout is active THEN the sidebar SHALL appear on the inline-end side (right), main content SHALL have correct `margin-inline-start` offset, and all positioning SHALL use logical properties exclusively

2.5 WHEN any admin layout template is used THEN it SHALL use Design System token-based classes only — no hardcoded colors (`bg-slate-950`, `text-white`, `border-slate-700`) and no raw Tailwind for layout

2.6 WHEN the admin shell renders at viewport widths 1440px, 1600px, 1920px, and 2560px THEN the layout SHALL properly display with sidebar fixed, topbar sticky, content constrained by `--admin-content-max-width`, and all grid/charts/tables correctly sized

2.7 WHEN any admin view renders THEN it SHALL use the single Shell component (`x-admin.shell`) as the only source of truth for layout — no view shall build its own sidebar, topbar, or calculate its own margins/widths

2.8 WHEN CSS classes are inspected THEN only the `admin-shell__*` BEM namespace SHALL exist for shell-related elements — all old namespace patterns (`admin-sidebar`, `admin-topbar`, `sidebar`, `sidebar-nav`, `topbar`, `content`) SHALL be removed

2.9 WHEN Blade templates for admin pages are inspected THEN no inline layout utilities (`ml-64`, `mr-64`, `pl-64`, `pr-64`, `fixed`, `sticky`, `left-0`, `right-0`, `top-0`) SHALL exist — all layout SHALL be defined solely in Shell CSS files (`tokens.css`, `shell.css`)

2.10 WHEN the shell layout is active THEN only the Shell CSS SHALL own: sidebar width, content offset, topbar height, z-index hierarchy, safe area, and page padding — no other component or page SHALL override these values

### Unchanged Behavior (Regression Prevention)

3.1 WHEN the viewport is below 1024px (mobile/tablet) THEN the system SHALL CONTINUE TO hide the sidebar and show the mobile hamburger toggle with drawer navigation

3.2 WHEN the sidebar collapse button is clicked THEN the system SHALL CONTINUE TO collapse/expand the sidebar with smooth transition and adjust the main content offset accordingly

3.3 WHEN the topbar notification panel or user menu is opened THEN the system SHALL CONTINUE TO display dropdown panels with correct z-index and positioning relative to the topbar

3.4 WHEN the mobile drawer is opened THEN the system SHALL CONTINUE TO display the drawer with backdrop overlay, focus trap behavior, and correct RTL positioning (from inline-end)

3.5 WHEN `prefers-reduced-motion: reduce` is active THEN the system SHALL CONTINUE TO disable transitions on shell elements

3.6 WHEN keyboard navigation is used THEN the system SHALL CONTINUE TO show focus indicators on all interactive elements within the shell

3.7 WHEN existing admin modules render (Dashboard, Teacher List, Student List, Calendar, Settings) THEN the system SHALL CONTINUE TO display all module content correctly without layout breakage

3.8 WHEN the z-index hierarchy is applied THEN the system SHALL CONTINUE TO maintain the correct stacking order: Sidebar → Topbar → Dropdown → Modal → Toast → Tooltip (all from tokens, no arbitrary values)

### Expected Behavior (Correct) — Continued

2.11 WHEN layout rules are defined THEN they SHALL exist only in `shell.css` — no layout rules in component files or Blade templates

2.12 WHEN design tokens are defined THEN they SHALL exist only in `tokens.css` — no token definitions duplicated in other files

2.13 WHEN component styles are defined THEN they SHALL exist only in `components/*.css` — components SHALL NOT contain layout positioning rules

2.14 WHEN Blade templates render admin pages THEN they SHALL NOT contain layout-specific styling (no inline styles, no layout utility classes)

2.15 WHEN CSS rules are inspected THEN no duplicated layout rules SHALL exist across files — each rule has exactly one owner

2.16 WHEN a component renders inside the shell THEN it SHALL NOT modify shell positioning (no overriding sidebar width, topbar height, content offset, or z-index hierarchy)

2.17 WHEN a new admin module is created THEN it SHALL only define content within `.admin-shell__content` — no module SHALL create its own sidebar, topbar, or layout wrapper

2.18 WHEN responsive behavior is needed within a component THEN the component SHALL adapt its internal layout only — viewport-level breakpoints and shell structure remain Shell-owned

**Ownership Matrix:**

| Concern | Owner | Not Allowed By |
|---------|-------|---------------|
| Sidebar positioning | Shell CSS (`shell.css`) | Components, Blade |
| Topbar positioning | Shell CSS (`shell.css`) | Components, Blade |
| Content offset | Shell CSS (`shell.css`) | Components, Blade |
| Viewport sizing | Shell CSS (`shell.css`) | Components, Blade |
| Overflow behavior | Shell CSS (`shell.css`) | Components, Blade |
| Breakpoint adaptation | Shell CSS (`shell.css`) | Components |
| Z-index hierarchy | Tokens (`tokens.css`) | Components, Blade |
| RTL adaptation | Shell CSS (logical props) | Components |
| Theme colors | Tokens (`tokens.css`) | Blade (no hardcoded) |
| Internal component layout | Component CSS | — |
| Local interactions | Component CSS | — |
| Visual presentation | Component CSS | — |

## Acceptance Criteria

- ✓ Sidebar never scrolls
- ✓ Topbar always visible (sticky)
- ✓ Only content area scrolls
- ✓ No horizontal scrollbar at any viewport width
- ✓ RTL layout fully preserved
- ✓ No hardcoded colors in admin templates
- ✓ No duplicated layout classes
- ✓ Single Shell Layout source of truth
- ✓ Zero old namespace conflicts (only `admin-shell__*`)
- ✓ All layout values from Design Tokens
- ✓ No inline layout utilities in Blade
- ✓ Passes visual inspection at: 1440px, 1600px, 1920px, 2560px
- ✓ No regression on existing modules (Dashboard, Teachers, Students, Calendar, Settings)
- ✓ CSS Architecture Contract enforced (shell.css / tokens.css / components/*.css separation)
- ✓ Component Ownership Contract enforced (Shell owns layout, Components own internal presentation)
- ✓ No component overrides shell-owned properties
