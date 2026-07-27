# Admin Shell Layout Fix — Bugfix Design

## Overview

The admin shell layout has architectural violations: scroll is not isolated, sidebar may move on scroll, hardcoded Tailwind utilities exist in `admin.blade.php`, and no explicit scroll/overflow containment is defined. This fix establishes the Shell as the single architectural owner of layout, scroll, positioning, and overflow — enforcing a strict contract where `shell.css` owns all layout rules, `tokens.css` owns all design values, and `components/*.css` own only internal presentation.

## Glossary

- **Bug_Condition (C)**: Any state where shell layout rules are violated — sidebar scrolls, body scrolls, horizontal overflow exists, hardcoded colors in Blade, or layout utilities in templates
- **Property (P)**: Correct shell behavior — sidebar fixed, topbar sticky, scroll isolated to content, no horizontal overflow, all values from tokens, single BEM namespace
- **Preservation**: Mobile drawer, sidebar collapse, topbar dropdowns, keyboard focus, reduced-motion, existing module rendering — all must remain unchanged
- **Shell**: The `admin-shell` component — the single layout owner for the entire admin panel
- **Ownership Matrix**: The contract defining which file owns which concern (see Architecture section)
- **BEM Namespace**: `admin-shell__*` — the only permitted class pattern for shell-level elements
- **Scroll Isolation**: `body { overflow: hidden }` + `.admin-shell__content { overflow-y: auto; height: 100vh - topbar }` pattern

## Architecture

### Ownership Matrix (Immutable Contract)

| Concern | Owner | Forbidden By |
|---------|-------|-------------|
| **Layout** (sidebar position, content offset, main grid) | `shell.css` | Components, Blade, dashboard.css |
| **Scroll** (overflow-y, overflow-x, scroll containment) | `shell.css` | Components, Blade |
| **Breakpoints** (viewport-level responsive changes) | `shell.css` | Components (internal breakpoints OK) |
| **Z-index** (stacking hierarchy) | `tokens.css` | Components, Blade, shell.css (uses token refs only) |
| **RTL** (logical properties for positioning) | `shell.css` (via logical props) | Components (for shell concerns) |
| **Theme** (colors, surfaces, accent) | `tokens.css` | Blade (no hardcoded values) |
| **Component Styling** (internal layout, borders, typography) | `components/*.css` | Shell concerns forbidden |
| **Visual Presentation** (colors, shadows, interactions) | `components/*.css` | Shell positioning forbidden |
| **Design Tokens** (all primitive/semantic values) | `tokens.css` | Duplication in other files forbidden |

### File Architecture

```
resources/css/
├── admin-foundation.css     ← imports tokens + shell (order matters)
├── admin/
│   ├── tokens.css           ← ALL design token definitions
│   ├── shell.css            ← ALL layout/scroll/position/overflow rules
│   └── dashboard.css        ← Dashboard component styles only (no layout overrides)
│   └── components/          ← Future: per-component CSS (internal only)
```

### Import Order (Critical)

```css
/* admin-foundation.css */
@import './admin/tokens.css';   /* 1. Tokens first */
@import './admin/shell.css';    /* 2. Shell layout second */
```

## Bug Details

### Bug Condition

The bug manifests when the admin shell renders and any of the following are true: (a) page content is longer than viewport causing body-level scroll, (b) sidebar moves when user scrolls, (c) horizontal scrollbar appears at any viewport width, (d) Blade template contains hardcoded Tailwind utilities for layout/color, (e) old namespace classes exist alongside `admin-shell__*`.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type ShellRenderState
  OUTPUT: boolean
  
  RETURN (input.bodyHasScroll == true)
         OR (input.sidebarMovesOnScroll == true)
         OR (input.horizontalOverflowExists == true)
         OR (input.bladeContainsHardcodedColors == true)
         OR (input.bladeContainsLayoutUtilities == true)
         OR (input.oldNamespaceClassesExist == true)
         OR (input.componentOverridesShellProperty == true)
END FUNCTION
```

### Examples

- **Scroll not isolated**: User scrolls dashboard content → sidebar moves up with page because `body` is the scroll container, not `.admin-shell__content`
- **Horizontal overflow**: At 1440px viewport, content overflows horizontally because no `overflow-x: hidden` on shell or body
- **Hardcoded colors**: `admin.blade.php` has `class="bg-slate-950 text-white"` — violates token ownership
- **Layout in Blade**: Templates may contain `ml-64`, `fixed`, `sticky` — violates shell.css ownership
- **Old namespace**: Classes like `admin-sidebar`, `sidebar-nav` may coexist with `admin-shell__sidebar`

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Mobile/tablet (< 1024px): sidebar hidden, hamburger toggle shows drawer from inline-end
- Sidebar collapse/expand: smooth transition, content offset adjusts
- Topbar dropdowns: notification panel and user menu display with correct z-index
- Mobile drawer: backdrop overlay, focus trap, RTL positioning (from inline-end)
- `prefers-reduced-motion: reduce`: all transitions disabled
- Keyboard navigation: focus indicators on all interactive elements
- Existing modules: Dashboard, Teachers, Students, Calendar, Settings render correctly
- Z-index hierarchy: Sidebar → Topbar → Dropdown → Modal → Toast → Tooltip

**Scope:**
All inputs that do NOT involve layout ownership violations should be completely unaffected by this fix. This includes:
- All component internal styling
- All Alpine.js state management
- All navigation link behavior
- All dropdown/panel open/close logic
- All existing responsive behavior at < 1024px

## Hypothesized Root Cause

Based on the bug analysis, the root causes are:

1. **Missing Scroll Isolation**: `shell.css` does not set `overflow: hidden` on body or `overflow-y: auto` with a computed height on `.admin-shell__content`. The page uses default body scroll, which moves the sidebar.

2. **No Overflow-X Containment**: Neither `body` nor `.admin-shell` has `overflow-x: hidden`, allowing horizontal scrollbar when content is wider than viewport.

3. **Hardcoded Values in Blade**: `admin.blade.php` uses `class="bg-slate-950 text-white"` directly — bypassing the token system and violating the ownership contract.

4. **Missing Height Constraint on Content**: `.admin-shell__content` has no `height` or `max-height` constraint, so it grows with content and pushes the overall page height, creating body scroll instead of content-area scroll.

5. **Potential Old Namespace Remnants**: Other admin view files may still reference old class patterns that conflict with the BEM namespace.

## Correctness Properties

Property 1: Bug Condition - Scroll Isolation and Layout Containment

_For any_ admin shell render state where content exceeds viewport height, the fixed shell SHALL isolate scroll to `.admin-shell__content` only — the sidebar SHALL remain fixed, the topbar SHALL remain sticky, `body` SHALL have `overflow: hidden`, and no horizontal scrollbar SHALL appear at any viewport width (1440px, 1600px, 1920px, 2560px).

**Validates: Requirements 2.1, 2.2, 2.3, 2.6**

Property 2: Preservation - Non-Layout Behavior Unchanged

_For any_ interaction that does NOT involve layout positioning (mouse clicks on nav items, dropdown toggles, mobile drawer open/close, keyboard navigation, module content rendering), the fixed code SHALL produce exactly the same behavior as the original code, preserving all existing functionality for sidebar collapse, topbar dropdowns, mobile responsive behavior, and module rendering.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8**

Property 3: Ownership Contract - No Layout in Blade or Components

_For any_ Blade template or component CSS file in the admin panel, the fixed codebase SHALL contain zero hardcoded colors (`bg-slate-*`, `text-white`), zero layout utilities (`ml-64`, `fixed`, `sticky`, `left-0`, `right-0`, `top-0`), and zero old namespace classes — all layout SHALL be defined exclusively in `shell.css` and all tokens in `tokens.css`.

**Validates: Requirements 2.5, 2.8, 2.9, 2.10, 2.11, 2.12, 2.13, 2.14, 2.15, 2.16**

Property 4: RTL Correctness - Logical Properties Only

_For any_ RTL rendering of the admin shell, the sidebar SHALL appear on inline-end (right), content SHALL have correct `margin-inline-start`, and all positioning SHALL use logical properties exclusively — no physical `left`/`right` properties in shell layout rules.

**Validates: Requirements 2.4**

## Components and Interfaces

### Shell Structure (BEM Classes)

```
.admin-shell                     ← Root container (100vh, grid layout)
├── .admin-shell--collapsed      ← Modifier: sidebar collapsed state
├── .admin-shell__sidebar        ← Fixed sidebar (position: fixed, inset-block: 0)
│   ├── .admin-shell__sidebar-header
│   ├── .admin-shell__sidebar-navigation
│   └── .admin-shell__sidebar-footer
├── .admin-shell__main           ← Main area (margin-inline-start offset)
│   ├── .admin-shell__topbar     ← Sticky topbar (position: sticky, top: 0)
│   └── .admin-shell__content    ← Scrollable content (overflow-y: auto, height: calc)
│       └── .admin-shell__content-inner  ← Max-width constraint
├── .admin-shell__drawer-backdrop ← Mobile overlay
└── .admin-shell__drawer          ← Mobile navigation drawer
```

### Alpine.js State Model

```javascript
// Shell state (x-data on .admin-shell)
{
  sidebarCollapsed: false,    // Desktop sidebar collapsed
  drawerOpen: false,          // Mobile drawer open
  notifOpen: false,           // Notification panel open
  userMenuOpen: false,        // User dropdown open
}
```

### Blade Component Interface

```blade
{{-- layouts/admin.blade.php — Structure only, zero styling --}}
<html lang="{{ app()->getLocale() }}" dir="rtl">
<body class="admin-page">
  <div class="admin-shell" x-data="{ sidebarCollapsed: false, drawerOpen: false }">
    {{-- sidebar, topbar, content via shell component --}}
    @yield('content')
  </div>
</body>
</html>
```

## Data Models

### Layout State Model

| State | Type | Default | Owner |
|-------|------|---------|-------|
| `sidebarCollapsed` | boolean | `false` | Alpine.js (shell x-data) |
| `drawerOpen` | boolean | `false` | Alpine.js (shell x-data) |
| `--admin-sidebar-current-width` | CSS custom property | `var(--admin-sidebar-width-expanded)` | shell.css |
| Content scroll position | native | 0 | Browser (on `.admin-shell__content`) |

### CSS Custom Properties (Layout Tokens)

| Token | Value | Defined In |
|-------|-------|-----------|
| `--admin-sidebar-width-expanded` | `calc(var(--space-8) * 3 + var(--space-6))` | tokens.css |
| `--admin-sidebar-width-collapsed` | `var(--space-10)` | tokens.css |
| `--admin-topbar-height` | `var(--space-8)` | tokens.css |
| `--admin-content-max-width` | `var(--container-xl)` | tokens.css |
| `--admin-z-navigation` | `var(--z-sticky)` | tokens.css |
| `--admin-z-dropdown` | `var(--z-dropdown)` | tokens.css |
| `--admin-z-overlay` | `var(--z-modal-backdrop)` | tokens.css |
| `--admin-z-dialog` | `var(--z-modal)` | tokens.css |

## Fix Implementation

### Changes Required

Assuming our root cause analysis is correct:

**File**: `resources/css/admin/shell.css`

**Specific Changes**:

1. **Add scroll isolation to `.admin-shell__main`**:
   - Add `height: 100vh` to create a fixed-height container
   - Add `display: flex; flex-direction: column` for topbar + content stacking
   - Remove `min-height: 100vh` (replace with `height: 100vh`)

2. **Add overflow containment to `.admin-shell__content`**:
   - Add `overflow-y: auto` for vertical scroll isolation
   - Add `overflow-x: hidden` to prevent horizontal scroll within content
   - Add `flex: 1; min-height: 0` to fill remaining space below topbar

3. **Add body-level overflow lock on `.admin-page`**:
   - Add `overflow: hidden` to prevent body scroll
   - Add `height: 100vh` to constrain body to viewport

4. **Add explicit overflow-x prevention on `.admin-shell`**:
   - Add `overflow-x: hidden` to shell root
   - Add `max-width: 100vw` as safety rail

5. **Viewport-specific handling** (1440px, 1920px, 2560px):
   - Verify `--admin-content-max-width` constrains content at large widths
   - Add `@media (min-width: 1440px)` rules if needed for wide-screen optimization

---

**File**: `resources/views/layouts/admin.blade.php`

**Specific Changes**:

1. **Remove hardcoded Tailwind classes**: Replace `class="bg-slate-950 text-white"` with `class="admin-page"`
2. **Remove `min-h-screen` wrapper div**: The shell handles height via CSS
3. **Ensure zero layout utilities in Blade**: Body has only `admin-page` class

---

**File**: `resources/css/admin/tokens.css` (if needed)

**Specific Changes**:
- Verify all z-index values are defined as tokens
- No new tokens needed unless wide-screen handling requires them

## Error Handling

| Edge Case | Handling |
|-----------|----------|
| Content overflow beyond max-width | `.admin-shell__content-inner` has `width: min(100%, var(--admin-content-max-width))` — constrains naturally |
| Missing design tokens (broken import) | Shell uses `var()` with no fallback — page renders with browser defaults (acceptable degradation) |
| Very long content (1000+ rows table) | Scroll isolation ensures only content area scrolls; sidebar/topbar remain fixed |
| Very narrow viewport (< 320px) | Shell hides sidebar, drawer fills viewport width with `min()` constraint |
| RTL with collapsed sidebar | Logical properties handle both directions automatically |
| JavaScript disabled (no Alpine) | Shell renders expanded by default; collapse/drawer non-functional but layout intact |
| Content taller than 100vh with nested scroll | Only `.admin-shell__content` scrolls; nested scrollable areas work independently |

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bug on unfixed code, then verify the fix works correctly and preserves existing behavior.

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm or refute the root cause analysis.

**Test Plan**: Inspect the rendered admin shell at various viewports and verify scroll behavior, overflow, and class patterns. Run on UNFIXED code to observe failures.

**Test Cases**:
1. **Body Scroll Test**: Add content exceeding viewport height → verify body scrolls (will fail = bug confirmed)
2. **Sidebar Fixed Test**: Scroll content → verify sidebar moves (will fail = bug confirmed)
3. **Horizontal Overflow Test**: Render at 1440px with wide table → verify horizontal scrollbar appears (may fail on unfixed code)
4. **Hardcoded Class Audit**: Inspect `admin.blade.php` → verify `bg-slate-950 text-white` exists (bug confirmed)
5. **Namespace Audit**: Grep for old namespace patterns (`admin-sidebar`, `sidebar-nav`, `topbar`, `content`) outside BEM

**Expected Counterexamples**:
- Body scroll exists because no `overflow: hidden` on body
- Sidebar moves because it relies on page-level scroll context
- Possible causes: missing scroll isolation, missing overflow containment, hardcoded values

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds, the fixed function produces the expected behavior.

**Pseudocode:**
```
FOR ALL viewport IN [1440, 1600, 1920, 2560] DO
  result := renderShell(viewport, longContent)
  ASSERT sidebar.position == 'fixed'
  ASSERT sidebar.scrollsWithPage == false
  ASSERT topbar.position == 'sticky'
  ASSERT body.overflowY == 'hidden'
  ASSERT content.overflowY == 'auto'
  ASSERT document.scrollingElement.scrollHeight <= viewport.height
  ASSERT horizontalScrollbar == false
END FOR
```

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed function produces the same result as the original.

**Pseudocode:**
```
FOR ALL interaction WHERE NOT isBugCondition(interaction) DO
  ASSERT originalBehavior(interaction) == fixedBehavior(interaction)
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many viewport/state combinations automatically
- It catches edge cases (collapsed + mobile + RTL + long content)
- It provides strong guarantees that non-layout behavior is unchanged

**Test Plan**: Observe behavior on UNFIXED code first for all non-layout interactions, then write tests capturing that behavior.

**Test Cases**:
1. **Sidebar Collapse Preservation**: Verify sidebar collapse toggle continues to work with smooth transition
2. **Mobile Drawer Preservation**: Verify drawer opens from inline-end with backdrop and focus trap
3. **Topbar Dropdown Preservation**: Verify notification panel and user menu position correctly
4. **Module Rendering Preservation**: Verify Dashboard, Teachers, Students pages render content correctly
5. **Keyboard Focus Preservation**: Verify focus indicators visible on all interactive elements
6. **Reduced Motion Preservation**: Verify transitions disabled when `prefers-reduced-motion: reduce`

### Unit Tests

- Verify `body.admin-page` has `overflow: hidden` and `height: 100vh`
- Verify `.admin-shell__main` has `height: 100vh` and flexbox column
- Verify `.admin-shell__content` has `overflow-y: auto` and `flex: 1`
- Verify no horizontal scrollbar at 1440px, 1920px, 2560px
- Verify sidebar `position: fixed` and `inset-block: 0`
- Verify no `bg-slate-*` or `text-white` in any admin Blade file
- Verify no `ml-64`, `mr-64`, `pl-64`, `pr-64`, `fixed`, `sticky` in admin Blade files

### Property-Based Tests

- Generate random content heights (100px to 10000px) → verify scroll isolation holds
- Generate random viewport widths (320px to 3840px) → verify no horizontal overflow
- Generate random sidebar states (collapsed/expanded) × viewport widths → verify content offset correct
- Generate random RTL/LTR × sidebar states → verify logical properties work

### Integration Tests

- Full page render at 1440px: sidebar fixed + topbar sticky + content scrolls + no overflow
- Full page render at 1920px: same constraints + content-max-width constrains inner content
- Full page render at 2560px: same constraints + no stretching
- RTL render: sidebar on right, content offset from right
- Mobile (< 1024px): sidebar hidden, drawer works, no layout break
- Sidebar collapse → expand cycle: layout adjusts correctly at each viewport

## Performance Considerations

| Concern | Approach |
|---------|----------|
| GPU-friendly transforms | Sidebar transition uses `width` (not ideal but necessary for content reflow). Drawer uses `transform: translateX` for GPU compositing |
| No layout shift | Fixed sidebar + sticky topbar = no CLS. Content area is the only scroll container |
| Minimal reflow | Sidebar width change triggers reflow on `.admin-shell__main` only (margin change). Acceptable because it's user-initiated (collapse click) |
| Backdrop-filter performance | Already using `backdrop-filter: blur()` — no change needed. GPU-composited |
| Scroll performance | Native overflow scroll on single container — browser-optimized |
| `will-change` | Not added (premature optimization). Browser handles fixed/sticky compositing |
| `contain: layout` | Consider adding to `.admin-shell__sidebar` for paint containment — isolates repaints |

## Security Considerations

| Concern | Mitigation |
|---------|-----------|
| User-injectable styles | No `style=""` attributes in Blade. All styling via CSS classes only |
| Token injection | CSS custom properties are not user-controllable. All `var()` references resolve to static definitions in `tokens.css` |
| XSS via class names | Blade uses `{{ }}` for escaped output. No dynamic class generation from user input |
| Z-index manipulation | All z-index values from tokens, not arbitrary. No user-controllable z-index |
| Content overflow attacks | `overflow: hidden` on body prevents malicious content from expanding page. `max-width` constraints prevent horizontal exploit |

## Dependencies

| Dependency | Role | Version |
|-----------|------|---------|
| `tokens.css` | Provides all design token values (`--admin-*`, `--z-*`, `--space-*`) | Project-defined |
| `shell.css` | All layout rules (this is the file being fixed) | Project-defined |
| Alpine.js | State management for sidebar collapse, drawer toggle | ^3.x |
| `@alpinejs/focus` | Focus trap for mobile drawer | ^3.x |
| Vite | CSS build, import resolution, minification | ^5.x (per `vite.config.js`) |
| Laravel Blade | Template rendering (`@vite`, `@yield`, `{{ }}`) | ^11.x |
| `design-tokens.css` | Primitive token layer (`--gold-300`, `--neutral-950`, etc.) | Project-defined |
| `semantic-tokens.css` | Semantic aliases (`--glass-bg`, `--text-primary`, etc.) | Project-defined |
