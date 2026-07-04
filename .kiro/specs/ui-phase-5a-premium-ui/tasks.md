# Implementation Plan

## Overview

Phase 5A implementation is split into 3 isolated batches for safe, phased execution. Each batch is independently executable and rollbackable. Execute one batch, review, then proceed to the next.

---

## Tasks

---

## Task Dependency Graph

```json
{
  "waves": [
    {
      "id": "wave-1",
      "label": "Phase 5A.1: Font & CSS Foundation",
      "tasks": ["1.1", "1.2", "1.3"],
      "sequential": true,
      "checkpoint": "Review & Approve"
    },
    {
      "id": "wave-2",
      "label": "Phase 5A.2: Sidebar & Dashboard",
      "tasks": ["2.1", "2.2"],
      "sequential": true,
      "checkpoint": "Review & Approve",
      "dependsOn": "wave-1"
    },
    {
      "id": "wave-3",
      "label": "Phase 5A.3: Tables & Empty States",
      "tasks": ["3.0-audit", "3.1-conditional", "3.2", "3.3"],
      "sequential": true,
      "checkpoint": "Review & Approve",
      "dependsOn": "wave-2"
    }
  ],
  "globalGuardrails": [
    "Blur: backdrop-blur-sm (4px) only",
    "Shadows: shadow-xl shadow-black/10-15 (no stacking)",
    "Animations: 300ms max, ease-in-out only",
    "Target: 60 FPS on low-end hardware"
  ]
}
```

---

## Notes

**Batch 3.0 Critical:** The data-table audit determines the path for Batch 3. If data-table.blade.php is not used, skip Batch 3.1 and modify existing table views directly in Batch 3.3.

**Performance Guardrails (ENFORCED):**
- Blur: `backdrop-blur-sm` (4px) ONLY — no heavier blur values
- Shadows: `shadow-xl shadow-black/10-15` (no stacking)
- Animations: 300ms max, ease-in-out only
- Target: 60 FPS on low-end hardware

**Rollback Safety:** Each batch has isolated rollback commands. Full rollback restores all Phase 5A changes.

---

## Batch Details

**Objective:** Establish Persian typography foundation and Vazirmatn font system

**Affected Files:**
1. `resources/css/app.css`

**Exact Changes:**

In `resources/css/app.css`, ensure the following sections exist and are properly ordered:

```css
/* At the TOP of app.css, BEFORE @tailwind directives */
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap');

/* These should be AFTER the Tailwind directives */
@layer base {
  html {
    font-family: 'Vazirmatn', system-ui, -apple-system, sans-serif;
  }

  /* Persian text rendering */
  :lang(fa) {
    font-family: 'Vazirmatn', system-ui, -apple-system, sans-serif;
    line-height: 1.8;
    letter-spacing: 0.5px;
    word-spacing: 0.05em;
  }

  /* Headings: heavier weight in Persian */
  :lang(fa) h1,
  :lang(fa) h2,
  :lang(fa) h3 {
    font-weight: 700;
    line-height: 1.6;
  }

  /* Smooth font rendering */
  body {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
  }
}
```

**Verification:**
- [ ] Font imports from CDN (check Network tab in DevTools)
- [ ] Vazirmatn loads all weights: 300, 400, 600, 700 (DevTools → Application → Fonts)
- [ ] Persian text renders with line-height 1.8+ (inspect :lang(fa) elements)
- [ ] Letter-spacing 0.5px applied to Persian (DevTools → Computed styles)
- [ ] No layout shift on font swap (CSS `display=swap` works)
- [ ] English text still readable (fallback fonts working)

**Test Checklist:**
- [ ] Load dashboard in Persian locale (fa)
- [ ] Inspect stat card text; verify font is Vazirmatn
- [ ] Check sidebar Persian text; verify line-height
- [ ] Zoom to 200%; text remains readable
- [ ] Switch locale to English; font falls back gracefully

**Rollback Command:**
```bash
git restore resources/css/app.css
npm run build  # Rebuild CSS
```

---

### Batch 1.2: Extend Tailwind Config for Typography

**Objective:** Register Vazirmatn font in Tailwind and extend theme

**Affected Files:**
1. `tailwind.config.js`

**Exact Changes:**

Modify `tailwind.config.js` theme extend section:

```javascript
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Vazirmatn', ...defaultTheme.fontFamily.sans],
                fa: ['Vazirmatn', 'system-ui', '-apple-system'],
            },
            backdropBlur: {
                'sm': '4px',    // Subtle blur for glass effects
            },
            opacity: {
                '04': '0.04',   // Very light transparency
                '08': '0.08',
            },
        },
    },

    plugins: [forms],
};
```

**Verification:**
- [ ] Tailwind build succeeds without errors (`npm run build`)
- [ ] Generated CSS includes Vazirmatn font declarations
- [ ] Custom utilities available in templates (e.g., `backdrop-blur-sm`)
- [ ] Tailwind class intellisense recognizes `opacity-04`, `opacity-08`

**Test Checklist:**
- [ ] `npm run build` completes without warnings
- [ ] Dashboard loads; typography unchanged from Batch 1.1
- [ ] Try applying `backdrop-blur-sm` class to an element; blur appears
- [ ] Custom opacity classes work (`bg-amber-500/[0.04]`)

**Rollback Command:**
```bash
git restore tailwind.config.js
npm run build
```

---

### Batch 1.3: Add Base CSS Components (Glass, Transitions, Utilities)

**Objective:** Define reusable CSS component utilities for entire Phase 5A

**Affected Files:**
1. `resources/css/app.css`

**Exact Changes:**

In `resources/css/app.css`, add this AFTER the @layer base section:

```css
@layer components {
  /* Transitions & Animations */
  .sidebar-transition {
    @apply transition-all duration-300 ease-in-out;
  }

  .transition-smooth {
    @apply transition-all duration-200 ease-in-out;
  }

  /* Glass Effect Base (subtle) */
  .glass {
    @apply backdrop-blur-sm bg-gray-900/70;
  }

  .glass-sm {
    @apply backdrop-blur-sm bg-gray-900/80;
  }

  /* Interactive Hover States */
  .nav-item-active {
    @apply bg-amber-500/10 ring-1 ring-amber-500/20 text-amber-300;
  }

  .nav-item-hover {
    @apply hover:bg-gray-800/50 hover:text-amber-300 transition-colors duration-200;
  }

  /* Table Utilities */
  .table-sticky-header {
    @apply sticky top-0 z-10 bg-gray-800/50 backdrop-blur-sm;
  }

  .table-row-hover {
    @apply hover:bg-gray-800/30 transition-colors duration-200;
  }

  .table-zebra tbody tr:nth-child(even) {
    @apply bg-gray-800/20;
  }

  /* Spacing Grid Enforcement (8px multiples) */
  .p-safe {
    @apply p-6; /* 24px = 8 * 3 */
  }

  .gap-safe {
    @apply gap-6; /* 24px = 8 * 3 */
  }

  /* RTL Logical Properties Support */
  [dir="rtl"] .flex-row-logical {
    @apply flex-row-reverse;
  }

  [dir="rtl"] .text-start-logical {
    @apply text-end;
  }
}
```

**Verification:**
- [ ] CSS compiles without errors
- [ ] Classes are available in Blade templates (use `@apply` classes in HTML)
- [ ] No duplicate class definitions
- [ ] Glass effect applies backdrop-blur-sm (verify with DevTools)

**Test Checklist:**
- [ ] Apply `.glass` class to a test element; verify blur and opacity
- [ ] Apply `.table-zebra` to a table; alternate rows highlighted
- [ ] Apply `.nav-item-hover` to a nav link; hover state works
- [ ] Check `.sidebar-transition` on sidebar; 300ms timing correct
- [ ] RTL elements with `.flex-row-logical` display reversed on dir="rtl"

**Rollback Command:**
```bash
git restore resources/css/app.css
npm run build
```

---

## Batch 1 Summary

**Files Modified:** 2 (app.css, tailwind.config.js)

**What was done:**
- ✅ Vazirmatn font imported and applied globally
- ✅ Persian typography rules (@layer base) configured
- ✅ Tailwind extended with custom utilities (glass effects, opacity variants)
- ✅ Base CSS components defined (@layer components)
- ✅ RTL logical properties set up

**What to review:**
- Typography rendering accuracy
- Font loading performance (no FOUT)
- Custom Tailwind utilities available
- No visual breaking changes to existing UI

**Next Step:** After review approval, proceed to **Batch 2: Phase 5A.2**.

**Rollback Everything in Batch 1:**
```bash
git restore resources/css/app.css tailwind.config.js
npm run build
```

---

## Batch 2: Phase 5A.2 — Sidebar Polish & Dashboard Redesign

### Batch 2.1: Polish Sidebar with Smooth Animations

**Objective:** Add smooth collapse/expand animations and refine hover states

**Affected Files:**
1. `resources/views/layouts/dashboard.blade.php`
2. `resources/css/app.css`

**Exact Changes:**

**In `resources/views/layouts/dashboard.blade.php`:**

Verify the sidebar already has Alpine.js `x-data` and state management:

```blade
<div class="app-shell relative z-10 flex min-h-screen"
    x-data="{ 
        collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        toggleCollapse() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebarCollapsed', this.collapsed);
        }
    }"
    :style="'--sidebar-width: ' + (collapsed ? '80px' : '280px')">
```

Verify sidebar has `sidebar-transition` class:
```blade
<aside class="sidebar-transition sidebar-fixed-width fixed inset-y-0 z-30 hidden flex-col bg-gray-900/70 backdrop-blur-xl lg:flex">
```

Verify collapse button tooltip and arrow logic:
```blade
<button 
    @click="toggleCollapse()"
    class="flex-shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-800/50 hover:text-amber-300"
    :title="collapsed ? 'بسط' : 'جمع'">
    <!-- Expanded: show RIGHT-pointing arrow (collapse direction) -->
    <svg x-show="!collapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
    </svg>
    <!-- Collapsed: show LEFT-pointing arrow (expand direction) -->
    <svg x-show="collapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
</button>
```

**Verification:**
- [ ] Sidebar toggles smoothly (300ms animation)
- [ ] State persists after reload (check localStorage)
- [ ] Arrow icons show correct direction
- [ ] No layout jank during collapse/expand
- [ ] CSS variable `--sidebar-width` updates (DevTools computed styles)

**Test Checklist:**
- [ ] Click collapse button; sidebar smoothly shrinks to 80px
- [ ] Refresh page; sidebar stays collapsed
- [ ] Click expand; sidebar smoothly expands to 280px
- [ ] Content offsets correctly (main-content-offset)
- [ ] No horizontal scroll or overflow
- [ ] Hover states on nav items work (amber-300 on hover)

**Rollback Command:**
```bash
git restore resources/views/layouts/dashboard.blade.php
npm run build
```

---

### Batch 2.2: Redesign Dashboard Stat Cards with Glass Effects & Trends

**Objective:** Apply glass effect, trend indicators, and improved hover states to stat cards

**Affected Files:**
1. `resources/views/admin/dashboard.blade.php`
2. `resources/css/app.css`

**Exact Changes:**

**Audit Current State:**
First, review existing stat card structure in `dashboard.blade.php`. Cards should already have glass-like styling. We're refining them to:
1. Ensure `backdrop-blur-sm` (4px only, not heavier)
2. Add trend indicators (up/down arrows)
3. Optimize opacity values (0.04–0.08 range)
4. Verify responsive grid (1/2/4 columns)

**In `resources/views/admin/dashboard.blade.php`:**

Update stat card grid to ensure responsive classes:
```blade
{{-- KPI Cards --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
```

For each stat card, ensure trend indicator in top-right:
```blade
<div class="group relative overflow-hidden rounded-2xl border border-amber-500/10 bg-gradient-to-br from-amber-500/[0.04] via-gray-900/90 to-gray-900/80 p-6 shadow-xl shadow-black/15 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/30">
  <!-- Top row: icon + label + TREND -->
  <div class="relative flex items-start justify-between">
    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 ring-1 ring-amber-500/20">
      <!-- Icon SVG -->
    </div>
    <!-- TREND INDICATOR (new) -->
    <span class="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-400/80">
      ↑ +12%  <!-- Or ↓ -3% for negative -->
    </span>
  </div>
  <!-- Rest of card content unchanged -->
</div>
```

**In `resources/css/app.css`:**

Add stat card utility if not already present:
```css
@layer components {
  .stat-card {
    @apply group relative overflow-hidden rounded-2xl border border-amber-500/10 bg-gradient-to-br from-amber-500/[0.04] via-gray-900/90 to-gray-900/80 p-6 shadow-xl shadow-black/15 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1;
  }

  .stat-card:hover {
    @apply border-amber-500/30;
  }

  .stat-card-glow {
    @apply pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-amber-500/10 blur-3xl transition-all duration-300 group-hover:bg-amber-500/20;
  }

  .stat-card-progress-bar {
    @apply relative mt-5 h-1 overflow-hidden rounded-full bg-gray-800/80;
  }

  .stat-card-progress-fill {
    @apply h-full rounded-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all duration-500 group-hover:w-full;
  }
}
```

**Verification:**
- [ ] Stat cards have `backdrop-blur-sm` (4px blur, not heavier)
- [ ] Opacity values: from-/via-/to use `0.04` or `0.08` (not 0.1 or higher)
- [ ] Shadows are `shadow-xl shadow-black/15` only (no stacking)
- [ ] Trend indicators display in top-right corner
- [ ] Hover state: card lifts slightly (-translate-y-1) and border brightens
- [ ] Grid layout: 1 col mobile, 2 cols tablet (sm), 4 cols desktop (xl)
- [ ] No jank or lag on hover (60 FPS target)

**Test Checklist:**
- [ ] Dashboard loads; stat cards display with glass effect
- [ ] Trend indicators visible (↑ or ↓ with percentage)
- [ ] Hover over stat card; smooth elevation (300ms)
- [ ] Resize to mobile (<640px); 1 column layout
- [ ] Resize to tablet (640–1023px); 2 columns
- [ ] Resize to desktop (≥1024px); 4 columns
- [ ] DevTools → Performance; no frame drops on hover
- [ ] Inspect card; verify opacity, blur, shadow values

**Rollback Command:**
```bash
git restore resources/views/admin/dashboard.blade.php
npm run build
```

---

## Batch 2 Summary

**Files Modified:** 2 (layouts/dashboard.blade.php, admin/dashboard.blade.php, app.css)

**What was done:**
- ✅ Sidebar collapse/expand animated smoothly (300ms)
- ✅ State persists via localStorage
- ✅ Stat cards styled with subtle glass effects
- ✅ Trend indicators added to stat cards
- ✅ Responsive grid refined (1/2/4 columns)
- ✅ Hover states optimized

**What to review:**
- Sidebar animation smoothness
- Stat card visual appeal vs readability
- Responsive grid behavior across breakpoints
- Performance (60 FPS maintained)
- No breaking changes to existing dashboard

**Next Step:** After review approval, proceed to **Batch 3: Phase 5A.3**.

**Rollback Everything in Batch 2:**
```bash
git restore resources/views/layouts/dashboard.blade.php resources/views/admin/dashboard.blade.php
npm run build
```

---

## Batch 3: Phase 5A.3 — Table Enhancements & Empty States

### Batch 3.0: CRITICAL — Audit data-table.blade.php Usage

**Objective:** Determine if data-table.blade.php component is used before modifying

**Task:**
1. Search entire codebase for references to `data-table` component:
   ```bash
   grep -r "data-table" resources/views/ --include="*.blade.php"
   grep -r "@include.*table" resources/views/ --include="*.blade.php"
   grep -r "table.*component" resources/views/ --include="*.blade.php"
   ```

2. Document findings:
   - [ ] If `data-table.blade.php` IS used: Proceed with Batch 3.1 modifications
   - [ ] If `data-table.blade.php` NOT used: Skip to Batch 3.2 (modify existing table views directly)
   - [ ] If `data-table.blade.php` exists but UNUSED: Leave unchanged; document decision

3. List all table views found:
   - [ ] Specify exact view paths
   - [ ] Note which tables need enhancements

**Decision Record:**

Create a decision note in the spec:
```
Audit Result: 
- data-table.blade.php: [USED / NOT USED]
- Tables to modify: [list of view paths]
- Tables skipped: [any unused components]
```

---

### Batch 3.1: (If data-table IS Used) Enhance Table Component

**Objective:** Add sticky headers, zebra styling, sort indicators to table component

**Affected Files:**
1. `resources/views/components/table/data-table.blade.php` (IF USED)

**Exact Changes:**

If audit finds data-table IS used, apply these changes:

```blade
<div class="overflow-x-auto">
  <table class="w-full text-start text-sm">
    {{-- Sticky Header --}}
    <thead class="sticky top-0 z-10 bg-gray-800/50 backdrop-blur-sm">
      <tr class="border-b border-gray-800/60">
        @foreach ($columns as $col)
          <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500
            {{ $col['sortable'] ? 'cursor-pointer hover:text-amber-400' : '' }}">
            {{ $col['label'] }}
            {{-- Sort Arrow --}}
            @if ($col['sortable'])
              @if ($sortBy === $col['key'])
                <span class="text-amber-400">{{ $sortOrder === 'asc' ? '↑' : '↓' }}</span>
              @endif
            @endif
          </th>
        @endforeach
      </tr>
    </thead>

    {{-- Zebra Rows --}}
    <tbody class="divide-y divide-gray-800/60">
      @forelse ($rows as $index => $row)
        <tr class="{{ $index % 2 === 0 ? 'bg-gray-800/20' : '' }} hover:bg-gray-800/30 transition-colors duration-200">
          @foreach ($columns as $col)
            <td class="px-6 py-3.5 text-gray-100">
              {{ $row[$col['key']] ?? '—' }}
            </td>
          @endforeach
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($columns) }}" class="px-6 py-12 text-center text-sm text-gray-500">
            No data available
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
```

**Verification:**
- [ ] Sticky header stays visible on scroll
- [ ] Zebra rows alternate colors correctly
- [ ] Hover highlights rows smoothly
- [ ] Sort arrows display when sortable
- [ ] No horizontal scroll issues
- [ ] Empty state shows when no data

---

### Batch 3.2: (Regardless) Create Empty State Component

**Objective:** Create reusable empty-state component for all no-data scenarios

**Affected Files:**
1. `resources/views/components/empty-state.blade.php` (NEW)

**Exact Changes:**

Create new file `resources/views/components/empty-state.blade.php`:

```blade
@props([
    'icon' => null,
    'title' => 'No Data',
    'message' => 'No items found.',
    'ctaUrl' => null,
    'ctaLabel' => 'Create New',
])

<div class="flex flex-col items-center justify-center rounded-2xl border border-gray-800/60 bg-gray-900/50 px-6 py-12 text-center backdrop-blur-sm">
  {{-- Icon Placeholder --}}
  @if ($icon)
    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10">
      {!! $icon !!}
    </div>
  @endif

  {{-- Title & Message --}}
  <h3 class="text-base font-semibold text-gray-100">{{ $title }}</h3>
  <p class="mt-2 text-sm text-gray-500">{{ $message }}</p>

  {{-- CTA Button (Optional) --}}
  @if ($ctaUrl)
    <a href="{{ $ctaUrl }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-amber-500/20 px-4 py-2 text-sm font-medium text-amber-300 transition hover:bg-amber-500/30">
      {{ $ctaLabel }}
    </a>
  @endif
</div>
```

**Usage Example:**

In any Blade view:
```blade
@forelse ($items as $item)
  {{-- render item --}}
@empty
  <x-empty-state
    title="No Students Found"
    message="Start by adding a new student."
    ctaUrl="{{ route('admin.students.create') }}"
    ctaLabel="New Student"
  />
@endforelse
```

**Verification:**
- [ ] Component renders without errors
- [ ] Icon displays (if provided)
- [ ] Title and message appear
- [ ] CTA button links correctly
- [ ] Styling matches dashboard aesthetic (glass effect, rounded, spacing)

**Test Checklist:**
- [ ] Render component with all props
- [ ] Render component without icon
- [ ] Render component without CTA
- [ ] Check responsive layout on mobile
- [ ] Verify spacing (padding 24–32px)
- [ ] Hover CTA button; color changes

**Rollback Command:**
```bash
rm resources/views/components/empty-state.blade.php
```

---

### Batch 3.3: Apply Table & Empty State Styling

**Objective:** Integrate table enhancements and empty states into actual table views

**Affected Files:**
1. Specific table views (identified via audit)
2. `resources/css/app.css` (update if needed)

**Exact Changes:**

For each table view identified in audit:

1. Update table `<thead>` to use sticky header:
   ```blade
   <thead class="sticky top-0 z-10 bg-gray-800/50 backdrop-blur-sm">
   ```

2. Update table rows for zebra styling:
   ```blade
   @foreach ($items as $index => $item)
     <tr class="{{ $index % 2 === 0 ? 'bg-gray-800/20' : '' }} hover:bg-gray-800/30 transition-colors">
   ```

3. Replace empty state with component:
   ```blade
   @empty
     <x-empty-state
       title="..."
       message="..."
       ctaUrl="{{ ... }}"
     />
   @endforelse
   ```

4. Add sort indicators to sortable headers (if applicable)

**Verification:**
- [ ] Tables render correctly
- [ ] No broken styles
- [ ] Empty states appear
- [ ] Sticky headers work
- [ ] Zebra rows display
- [ ] No regressions in existing functionality

**Test Checklist:**
- [ ] Load each table view
- [ ] Scroll table (sticky header stays put)
- [ ] Load table with no data (empty state appears)
- [ ] Load table with data (zebra rows visible)
- [ ] Hover rows (smooth highlight)
- [ ] Responsive: mobile, tablet, desktop layouts

**Rollback Command:**
```bash
git restore resources/views/components/table/ resources/views/admin/
npm run build
```

---

## Batch 3 Summary

**Files Modified/Created:** 2 (data-table.blade.php [if used], empty-state.blade.php [new])

**What was done:**
- ✅ Audited data-table.blade.php usage (critical decision point)
- ✅ Enhanced table component (if used) with sticky headers, zebra rows, sort indicators
- ✅ Created empty-state component for all no-data scenarios
- ✅ Applied enhancements to actual table views

**What to review:**
- Audit findings and decision (was data-table used?)
- Table rendering and sticky header behavior
- Empty state component appearance and reusability
- Performance on tables with 50+ rows
- No regressions in existing table functionality

**Rollback Everything in Batch 3:**
```bash
git restore resources/views/components/table/ resources/views/admin/
rm resources/views/components/empty-state.blade.php
npm run build
```

---

## Overall Rollback Plan

**If Batch 1 breaks:** Restore app.css and tailwind.config.js
```bash
git restore resources/css/app.css tailwind.config.js
npm run build
```

**If Batch 2 breaks:** Restore sidebar and dashboard
```bash
git restore resources/views/layouts/dashboard.blade.php resources/views/admin/dashboard.blade.php
npm run build
```

**If Batch 3 breaks:** Restore tables and remove empty-state
```bash
git restore resources/views/components/table/ resources/views/admin/
rm resources/views/components/empty-state.blade.php
npm run build
```

**Full Rollback (revert all Phase 5A changes):**
```bash
git restore resources/css/app.css tailwind.config.js
git restore resources/views/layouts/dashboard.blade.php resources/views/admin/dashboard.blade.php
git restore resources/views/components/table/
rm resources/views/components/empty-state.blade.php
npm run build
```

---

## Execution Instructions

1. **Execute Batch 1 (Font + CSS)** → Review & Approve
2. **Execute Batch 2 (Sidebar + Dashboard)** → Review & Approve
3. **Execute Batch 3 (Tables + Empty States)** → Review & Approve

**After each batch:**
- Review code changes
- Test manually on dashboard
- Check performance (60 FPS)
- Verify no regressions
- Approve before proceeding to next batch

**Performance Guardrails (Non-Negotiable):**
- ✅ Blur: `backdrop-blur-sm` (4px) ONLY
- ✅ Shadows: `shadow-xl shadow-black/10-15` (no stacking)
- ✅ Animations: 300ms max, ease-in-out only
- ✅ Target: 60 FPS on low-end hardware

---

## Success Criteria

**Batch 1 Success:**
- [ ] Vazirmatn font loads and renders correctly
- [ ] Persian text has proper line-height (1.8) and letter-spacing (0.5px)
- [ ] English text falls back gracefully
- [ ] Tailwind config extends without errors
- [ ] Custom utilities available in templates

**Batch 2 Success:**
- [ ] Sidebar smoothly collapses/expands (300ms)
- [ ] State persists after reload
- [ ] Stat cards display glass effect subtly
- [ ] Trend indicators visible
- [ ] Responsive grid works (1/2/4 columns)
- [ ] No performance degradation (60 FPS maintained)

**Batch 3 Success:**
- [ ] Audit completed; data-table.blade.php usage determined
- [ ] Tables have sticky headers and zebra rows
- [ ] Empty state component works across contexts
- [ ] All table views render without errors
- [ ] No regressions in existing functionality

---

## Next Steps After All Batches

Once all 3 batches complete:
1. Full dashboard review
2. Cross-browser testing (Chrome, Firefox, Safari, Edge)
3. RTL (Persian) mode verification
4. Mobile/tablet/desktop responsive check
5. Accessibility audit (WCAG 2.1 AA)
6. Performance profiling (Lighthouse)
7. Final approval for merge to main branch
