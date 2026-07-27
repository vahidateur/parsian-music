# Implementation Plan

## Overview

Fix the admin shell layout by establishing scroll isolation, overflow containment, BEM namespace unification, logical properties for RTL, and enforcing the ownership contract (shell.css owns layout, tokens.css owns values, components own internal presentation only). Uses bug condition methodology: exploration test → preservation test → implementation → validation.

## Tasks

- [x] 1. Write bug condition exploration test
  - **Property 1: Bug Condition** - Scroll Isolation and Layout Containment Violation
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate layout bugs exist in the current shell
  - **Scoped PBT Approach**: Scope to concrete failing cases:
    - Body has scroll (no `overflow: hidden`)
    - `.admin-shell__content` lacks `overflow-y: auto`
    - Sidebar lacks `position: fixed` with `inset-block: 0`
    - Topbar lacks `position: sticky`
    - Horizontal overflow exists at 1440px, 1600px, 1920px, 2560px viewports
    - `admin.blade.php` contains hardcoded classes (`bg-slate-950`, `text-white`)
    - Old namespace classes exist (`admin-sidebar`, `sidebar-nav`, `topbar`, `content`)
    - Blade templates contain layout utilities (`ml-64`, `mr-64`, `pl-64`, `pr-64`, `fixed`, `sticky`)
  - Test assertions match Expected Behavior Properties from design:
    - `body.admin-page` MUST have `overflow: hidden` and `height: 100vh`
    - `.admin-shell__content` MUST have `overflow-y: auto`, `flex: 1`, `min-height: 0`
    - `.admin-shell__sidebar` MUST have `position: fixed`, `inset-block: 0`
    - `.admin-shell__topbar` MUST have `position: sticky`, `top: 0`
    - No horizontal scrollbar at any tested viewport width
    - No hardcoded colors in admin Blade files
    - No layout utilities in admin Blade files
    - Only `admin-shell__*` BEM namespace for shell elements
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bug exists)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 1.1, 1.2, 1.3, 1.5, 1.8, 1.9, 2.1, 2.2, 2.3, 2.5, 2.8, 2.9_

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Non-Layout Behavior Unchanged
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy inputs (interactions that do NOT involve layout positioning):
    - Observe: Sidebar collapse toggle works with smooth transition
    - Observe: Mobile drawer opens from inline-end with backdrop overlay
    - Observe: Topbar notification panel and user menu display with correct z-index
    - Observe: Focus indicators visible on interactive elements
    - Observe: `prefers-reduced-motion: reduce` disables transitions
    - Observe: Existing modules (Dashboard, Teachers, Students, Calendar, Settings) render correctly
    - Observe: Z-index hierarchy maintained (Sidebar → Topbar → Dropdown → Modal → Toast → Tooltip)
  - Write property-based tests capturing observed behavior patterns:
    - For all sidebar collapse interactions → content offset adjusts, transition occurs
    - For all mobile drawer opens → backdrop visible, focus trap active, RTL positioning correct
    - For all topbar dropdown toggles → panel displays at correct z-index layer
    - For all keyboard navigation → focus indicators visible on all interactive elements
    - For all module renders → content displays within `.admin-shell__content` without breakage
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

- [x] 3. Fix for admin shell layout — CSS Token Cleanup
  - [x] 3.1 Audit and consolidate all design tokens in `tokens.css`
    - Verify all `--admin-*` tokens exist: `--admin-sidebar-width-expanded`, `--admin-sidebar-width-collapsed`, `--admin-topbar-height`, `--admin-content-max-width`
    - Verify all `--admin-z-*` tokens exist: `--admin-z-navigation`, `--admin-z-dropdown`, `--admin-z-overlay`, `--admin-z-dialog`
    - Remove any duplicate token definitions found in other CSS files
    - Ensure token values use primitive token references (e.g., `var(--space-8)`, `var(--z-sticky)`)
    - **Definition of Done**: All tokens defined exactly once in `tokens.css`, zero duplicates across codebase
    - _Bug_Condition: input.bladeContainsHardcodedColors == true OR duplicated tokens exist_
    - _Expected_Behavior: All tokens defined exclusively in tokens.css, no duplication_
    - _Preservation: Existing token values unchanged, only location consolidated_
    - _Requirements: 2.12, 2.15_

- [x] 4. Fix for admin shell layout — Shell Layout Refactor
  - [x] 4.1 Implement scroll isolation and overflow containment in `shell.css`
    - Add `.admin-page { overflow: hidden; height: 100vh; }` for body-level lock
    - Add `.admin-shell { overflow-x: hidden; max-width: 100vw; }` to prevent horizontal overflow
    - Change `.admin-shell__main` to `height: 100vh; display: flex; flex-direction: column`
    - Add `.admin-shell__content { overflow-y: auto; overflow-x: hidden; flex: 1; min-height: 0; }`
    - Remove any `min-height: 100vh` on `.admin-shell__main` (replace with `height: 100vh`)
    - **Definition of Done**: Body never scrolls, only `.admin-shell__content` scrolls, no horizontal scrollbar at any viewport
    - **Dependencies**: Task 3 (tokens must exist first)
    - _Bug_Condition: input.bodyHasScroll == true OR input.horizontalOverflowExists == true_
    - _Expected_Behavior: body.overflow == 'hidden' AND content.overflowY == 'auto' AND horizontalScrollbar == false_
    - _Preservation: All existing flexbox/grid within content area unchanged_
    - _Requirements: 2.1, 2.2, 2.3, 2.6_

- [x] 5. Fix for admin shell layout — Namespace Unification
  - [x] 5.1 Remove all old namespace patterns, enforce `admin-shell__*` BEM only
    - Search and remove all old classes: `admin-sidebar`, `admin-topbar`, `sidebar`, `sidebar-nav`, `topbar`, `content` (as shell-level selectors)
    - Replace any old references in Blade templates with correct BEM equivalents
    - Ensure CSS has zero selectors using old namespace patterns
    - **Definition of Done**: `grep -r "admin-sidebar\|admin-topbar\|sidebar-nav"` returns zero results in admin CSS/Blade
    - **Dependencies**: Task 4 (shell layout must be in place)
    - _Bug_Condition: input.oldNamespaceClassesExist == true_
    - _Expected_Behavior: Only admin-shell__* BEM namespace exists for shell elements_
    - _Preservation: All functionality preserved, only class names changed_
    - _Requirements: 2.8, 2.10_

- [x] 6. Fix for admin shell layout — Sidebar Fixed Positioning
  - [x] 6.1 Ensure sidebar uses fixed positioning with logical properties and token-based values
    - Verify `.admin-shell__sidebar` has `position: fixed; inset-block: 0; inset-inline-start: 0`
    - Width from token: `width: var(--admin-sidebar-width-expanded)`
    - Collapsed state: `width: var(--admin-sidebar-width-collapsed)`
    - Z-index from token: `z-index: var(--admin-z-navigation)`
    - No physical `left`/`right` properties — only logical
    - No hardcoded pixel values — only `var()` references
    - **Definition of Done**: Sidebar never moves on scroll, uses only logical properties and token values
    - **Dependencies**: Task 5 (namespace must be unified)
    - _Bug_Condition: input.sidebarMovesOnScroll == true_
    - _Expected_Behavior: sidebar.position == 'fixed' AND sidebar uses logical properties only_
    - _Preservation: Sidebar collapse/expand transition unchanged_
    - _Requirements: 2.1, 2.4, 2.10_

- [x] 7. Fix for admin shell layout — Topbar Sticky Positioning
  - [x] 7.1 Ensure topbar uses sticky positioning with correct z-index from tokens
    - Verify `.admin-shell__topbar` has `position: sticky; top: 0`
    - Height from token: `height: var(--admin-topbar-height)`
    - Z-index from token: `z-index: var(--admin-z-navigation)`
    - Flex-shrink: 0 to prevent compression
    - No hardcoded values
    - **Definition of Done**: Topbar always visible at top of `.admin-shell__main`, never scrolls away
    - **Dependencies**: Task 6 (sidebar positioning must be set)
    - _Bug_Condition: topbar scrolls away with content_
    - _Expected_Behavior: topbar.position == 'sticky' AND topbar always visible_
    - _Preservation: Topbar dropdowns (notification, user menu) continue working_
    - _Requirements: 2.1, 2.10_

- [x] 8. Fix for admin shell layout — Content Scroll Isolation
  - [x] 8.1 Verify content area has `overflow-y: auto`, `flex: 1`, `min-height: 0`
    - Confirm `.admin-shell__content` rules from task 4.1 are effective
    - Add `.admin-shell__content-inner { width: min(100%, var(--admin-content-max-width)); margin-inline: auto; }` for max-width constraint
    - Verify scroll only happens within this container (not body, not main)
    - Test with content exceeding viewport height (long tables, many cards)
    - **Definition of Done**: Scrolling content never moves sidebar or topbar, content constrained by max-width token
    - **Dependencies**: Task 7 (topbar must be sticky to validate scroll isolation)
    - _Bug_Condition: input.bodyHasScroll == true OR sidebar moves on scroll_
    - _Expected_Behavior: Only .admin-shell__content scrolls, sidebar/topbar fixed/sticky_
    - _Preservation: All content within modules renders correctly_
    - _Requirements: 2.1, 2.2, 2.6_

- [x] 9. Fix for admin shell layout — RTL Validation
  - [x] 9.1 Verify all logical properties work correctly, no physical left/right in shell
    - Audit `shell.css` for any `left:`, `right:`, `margin-left:`, `margin-right:`, `padding-left:`, `padding-right:` — replace with logical equivalents
    - Verify sidebar appears on inline-end (right) in RTL
    - Verify content has correct `margin-inline-start` offset
    - Verify drawer opens from inline-end in RTL
    - Test at all breakpoints in RTL mode
    - **Definition of Done**: Zero physical directional properties in shell.css, RTL renders correctly at all viewports
    - **Dependencies**: Task 8 (all layout must be in place before RTL validation)
    - _Bug_Condition: physical left/right properties exist in shell layout_
    - _Expected_Behavior: All positioning uses logical properties, RTL works automatically_
    - _Preservation: RTL layout unchanged for components (only shell properties converted)_
    - _Requirements: 2.4_

- [x] 10. Fix for admin shell layout — Responsive Validation
  - [x] 10.1 Test and fix at 1440px, 1600px, 1920px, 2560px
    - Verify at each viewport: sidebar fixed, topbar sticky, content scrolls, no overflow
    - Verify `--admin-content-max-width` constrains content at large widths (2560px)
    - Verify no stretching or excessive whitespace at ultra-wide viewports
    - Add `@media` rules if needed for wide-screen optimization
    - Verify grid/charts/tables correctly sized within content area
    - **Definition of Done**: Visual inspection passes at all 4 viewport widths, no layout break
    - **Dependencies**: Task 9 (RTL must work before responsive validation)
    - _Bug_Condition: layout breaks at specific viewport widths_
    - _Expected_Behavior: Layout correct at 1440px, 1600px, 1920px, 2560px_
    - _Preservation: Mobile/tablet behavior (< 1024px) unchanged_
    - _Requirements: 2.6_

- [x] 11. Fix for admin shell layout — Accessibility
  - [x] 11.1 Verify keyboard focus, reduced-motion, focus indicators
    - Verify all interactive elements in shell have visible focus indicators
    - Verify `prefers-reduced-motion: reduce` disables all shell transitions
    - Verify focus trap on mobile drawer (using `@alpinejs/focus` x-trap)
    - Verify tab order is logical (sidebar → topbar → content)
    - Verify no focus trap escape when drawer is closed
    - **Definition of Done**: Keyboard-only navigation works through entire shell, reduced-motion respected, all focus indicators visible
    - **Dependencies**: Task 10 (responsive must work before a11y validation)
    - _Bug_Condition: missing focus indicators or motion not respecting preference_
    - _Expected_Behavior: All WCAG AA focus/motion requirements met_
    - _Preservation: Existing focus behavior and reduced-motion unchanged (verify, don't break)_
    - _Requirements: 3.5, 3.6_

- [x] 12. Fix for admin shell layout — Blade Template Cleanup
  - [x] 12.1 Remove hardcoded Tailwind classes from `admin.blade.php`
    - Replace `class="bg-slate-950 text-white"` with `class="admin-page"`
    - Remove any `min-h-screen` wrapper div (shell handles height)
    - Ensure body has only semantic classes (`admin-page`)
    - _Requirements: 2.5, 2.9, 2.14_
  - [x] 12.2 Remove all layout utilities from admin Blade templates
    - Search all admin Blade files for: `ml-64`, `mr-64`, `pl-64`, `pr-64`, `fixed`, `sticky`, `left-0`, `right-0`, `top-0`
    - Remove found utilities — shell.css owns all positioning
    - Verify no inline styles exist for layout
    - **Definition of Done**: `grep -r "ml-64\|mr-64\|pl-64\|pr-64\|fixed\|sticky\|left-0\|right-0\|top-0"` returns zero results in admin Blade files
    - **Dependencies**: Task 11 (all CSS must be finalized before Blade cleanup)
    - _Bug_Condition: input.bladeContainsLayoutUtilities == true_
    - _Expected_Behavior: Zero layout utilities in Blade, all layout in shell.css_
    - _Preservation: Page rendering unchanged (CSS now owns what Blade previously defined)_
    - _Requirements: 2.5, 2.9, 2.10, 2.11, 2.14_

- [ ] 13. Verify bug condition exploration test now passes
  - [x] 13.1 Re-run bug condition exploration test
    - **Property 1: Expected Behavior** - Scroll Isolation and Layout Containment
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 1
    - **EXPECTED OUTCOME**: Test PASSES (confirms all layout bugs are fixed)
    - **Dependencies**: Task 12 (all implementation must be complete)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.8, 2.9_

  - [ ] 13.2 Verify preservation tests still pass
    - **Property 2: Preservation** - Non-Layout Behavior Unchanged
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests from step 2
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
    - Confirm all tests still pass after fix (no regressions)
    - **Dependencies**: Task 13.1
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8_

- [ ] 14. Checkpoint - Ensure all tests pass
  - Verify Property 1 (Bug Condition → Expected Behavior) passes
  - Verify Property 2 (Preservation) passes
  - Verify Property 3 (Ownership Contract) — no layout in Blade or components
  - Verify Property 4 (RTL Correctness) — logical properties only in shell
  - Run full test suite
  - Visual inspection at 1440px, 1600px, 1920px, 2560px
  - RTL inspection at all breakpoints
  - Mobile/tablet inspection (< 1024px) for regression
  - Ensure all tests pass, ask the user if questions arise.

## Task Dependency Graph

```json
{
  "waves": [
    {
      "wave": 1,
      "tasks": ["1", "2"],
      "description": "Exploration and preservation tests on UNFIXED code (run in parallel)"
    },
    {
      "wave": 2,
      "tasks": ["3.1"],
      "description": "CSS Token Cleanup — consolidate all tokens in tokens.css"
    },
    {
      "wave": 3,
      "tasks": ["4.1"],
      "description": "Shell Layout Refactor — scroll isolation, overflow containment"
    },
    {
      "wave": 4,
      "tasks": ["5.1"],
      "description": "Namespace Unification — remove old patterns, enforce admin-shell__* BEM"
    },
    {
      "wave": 5,
      "tasks": ["6.1"],
      "description": "Sidebar — fixed positioning with logical properties"
    },
    {
      "wave": 6,
      "tasks": ["7.1"],
      "description": "Topbar — sticky positioning with token z-index"
    },
    {
      "wave": 7,
      "tasks": ["8.1"],
      "description": "Content Scroll Isolation — overflow-y:auto, flex:1, min-height:0"
    },
    {
      "wave": 8,
      "tasks": ["9.1"],
      "description": "RTL Validation — logical properties only, no physical left/right"
    },
    {
      "wave": 9,
      "tasks": ["10.1"],
      "description": "Responsive Validation — test at 1440px, 1600px, 1920px, 2560px"
    },
    {
      "wave": 10,
      "tasks": ["11.1"],
      "description": "Accessibility — keyboard focus, reduced-motion, focus indicators"
    },
    {
      "wave": 11,
      "tasks": ["12.1", "12.2"],
      "description": "Blade Template Cleanup — remove hardcoded classes and layout utilities"
    },
    {
      "wave": 12,
      "tasks": ["13.1", "13.2"],
      "description": "Verify exploration test passes and preservation tests still pass"
    },
    {
      "wave": 13,
      "tasks": ["14"],
      "description": "Final checkpoint — all properties hold, visual inspection passes"
    }
  ]
}
```

## Notes

- Tasks 1 and 2 (exploration + preservation tests) MUST run on UNFIXED code before any implementation
- Task 1 is expected to FAIL — this confirms the bug exists
- Task 2 is expected to PASS — this captures baseline behavior to preserve
- Tasks 3–12 are sequential implementation following strict dependency order
- Task 13 re-runs the same tests from tasks 1 and 2 to validate the fix
- Task 14 is the final checkpoint ensuring all properties hold
- All layout ownership belongs exclusively to `shell.css` (Ownership Matrix from design)
- All token definitions belong exclusively to `tokens.css`
- No hardcoded values, no Tailwind layout utilities in Blade templates
- RTL uses logical properties only — zero physical `left`/`right` in shell CSS
