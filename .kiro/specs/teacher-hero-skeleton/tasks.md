# Implementation Plan: Teacher Hero Skeleton — Phase 1 (ARCHITECTURE FINAL)

## Overview

10 Blade components + 1 CSS theme stub, organized in subdirectories (`hero/`, `portrait/`, `badges/`, `chips/`, `buttons/`). CSS Grid 12-col, mobile-first, RTL, semantic HTML, ARIA/keyboard accessibility, 4 named image slots (empty divs), Design Tokens, z-index policy, mock data only. No backend, no Flexbox, no animations, no images.

## Architecture Rules (enforced in every task)

1. Every component has single responsibility
2. Hero ONLY orchestrates child components — zero inline content
3. No hardcoded images anywhere
4. No inline SVG larger than 20 lines
5. No page-specific CSS inside reusable components
6. Every component must be reusable across multiple pages
7. Every image must load through a named slot div
8. Public pages must never depend on backend data during UI development
9. `buttons/cta-button.blade.php` is site-wide — NOT teacher-specific
10. `badges/experience-badge.blade.php` is reusable for any badge context
11. `chips/instrument-chip.blade.php` is reusable for any chip/tag context
12. **No inline CSS** — no `style="..."` attributes; all styles in `resources/css/` or CSS modules; exception: dynamically injected CSS variables only
13. **Design Tokens only** — no hardcoded values (`color: #D5AF58` ❌, `border-radius: 28px` ❌); use `var(--color-primary)`, `var(--radius-xl)`, `var(--space-6)` ✓
14. **Layer z-index policy** — Hero stack is FROZEN, never to be broken:
    - `var(--z-hero-background): 0`  → Background layer
    - `var(--z-hero-decoration): 10` → Decoration layer
    - `var(--z-hero-portrait): 20`   → Portrait layer
    - `var(--z-hero-info): 30`       → Information layer
    - `var(--z-navigation): 40`      → Navigation (above all hero layers)
15. **Never redesign a frozen phase** — After a phase is approved: only bug fixes are allowed. Architectural changes require a new phase. A frozen Hero stays frozen even while building About, Schedule, etc.
16. **No placeholder styles** — Every placeholder must have the EXACT dimensions of the final component. Only content is fake. Layout is final. If the final portrait is 520×720, the placeholder is 520×720 from day one. Zero layout shift when real assets replace placeholders.

## Tasks

- [x] 1. Create entry-point view and asset directory structure
  - [x] 1.1 Create `resources/views/teachers/show.blade.php`
    - Extend base layout (inherits navbar and breadcrumb)
    - Wrap hero in `<main>`
    - Define `$teacher` mock array inline: `name`, `role`, `experience`, `instruments`
    - Note: key is `role` (not `title`)
    - Pass `$teacher` to `<x-ui.teacher.hero.hero :teacher="$teacher" />`
    - No controller, no Eloquent, no route model binding
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 11.1, 11.2, 11.3, 11.4_

  - [x] 1.2 Create asset directory structure placeholders
    - Create `.gitkeep` files to establish:
      - `storage/app/public/ui/teacher/backgrounds/`
      - `storage/app/public/ui/teacher/frames/`
      - `storage/app/public/ui/teacher/portraits/`
      - `storage/app/public/ui/teacher/decorations/`
      - `storage/app/public/ui/teacher/icons/`
    - No images added — structure only, reserved for Phase 2+
    - _Architecture Rules: 3, 7_

- [x] 2. Create `resources/css/teacher-theme.css` stub
  - [x] 2.1 Create `resources/css/teacher-theme.css`
    - Define CSS custom properties (Design Tokens) for teacher pages:
      ```css
      /* Teacher Theme — Phase 1 stub. Real values added in Phase 2. */
      :root {
        /* Colors — placeholder tokens */
        --teacher-color-primary: var(--color-primary, #000);
        --teacher-color-accent:  var(--color-accent, #000);

        /* Spacing */
        --teacher-space-hero-gap: var(--space-6, 1.5rem);

        /* Z-index layer stack — FROZEN */
        --z-hero-background:  0;
        --z-hero-decoration: 10;
        --z-hero-portrait:   20;
        --z-hero-info:       30;
        --z-navigation:      40;
      }
      ```
    - Import in `resources/css/app.css` or base layout
    - All hero components must use `var(--z-hero-*)` for z-index
    - No hardcoded color/spacing values anywhere in components
    - _Architecture Rules: 12, 13, 14_

- [x] 3. Create reusable leaf components (`badges/`, `chips/`, `buttons/`)
  - [x] 3.1 Create `resources/views/components/ui/teacher/badges/experience-badge.blade.php`
    - `@props(['experience'])` — one required string prop
    - Render `<div role="text" aria-label="{{ $experience }}">{{ $experience }}</div>`
    - Styling via CSS tokens only — no inline styles, no hardcoded values
    - Reusable for any badge context (not teacher-specific)
    - Tag: `<x-ui.teacher.badges.experience-badge>`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 12.6 | Architecture Rules: 1, 6, 12, 13_

  - [x] 3.2 Create `resources/views/components/ui/teacher/chips/instrument-chip.blade.php`
    - `@props(['instrument'])` — one required string prop
    - Render `<span>{{ $instrument }}</span>` chip
    - Styling via CSS tokens only
    - Reusable for any chip/tag context (not teacher-specific)
    - Tag: `<x-ui.teacher.chips.instrument-chip>`
    - _Requirements: 6.1, 6.3, 6.4, 6.5, 12.5 | Architecture Rules: 1, 6, 12, 13_

  - [x] 3.3 Create `resources/views/components/ui/teacher/buttons/cta-button.blade.php`
    - `@props(['label' => 'درخواست کلاس'])` — optional prop with Persian default
    - Render semantic `<button type="button" aria-label="{{ $label }}">{{ $label }}</button>`
    - Full width on mobile (`w-full`), contained on desktop — via Tailwind only, no inline styles
    - Visible focus ring (no `outline: none` without replacement)
    - No href, no click handler, no navigation
    - Site-wide reusable (NOT teacher-specific)
    - Tag: `<x-ui.teacher.buttons.cta-button>`
    - _Requirements: 7.1, 7.4, 7.5, 7.6 | Architecture Rules: 1, 6, 9, 12, 13_

- [x] 4. Create independent hero layer components (`hero/`)
  - [x] 4.1 Create `resources/views/components/ui/teacher/hero/background-layer.blade.php`
    - `@props([])` — no props
    - Outer grid cell: `col-span-12 md:col-span-8`, min-height via token `var(--teacher-min-h-hero, 400px)`
    - z-index: `style="z-index: var(--z-hero-background)"` (CSS variable injection — Rule 12 exception)
    - Inside: `<div id="teacher-background-slot" role="img" aria-label="تصویر پس‌زمینه مدرس"></div>` — empty, no image, no CSS background
    - Tag: `<x-ui.teacher.hero.background-layer>`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 12.1–12.4 | Architecture Rules: 1, 3, 7, 12, 13, 14_

  - [x] 4.2 Create `resources/views/components/ui/teacher/portrait/portrait-frame.blade.php`
    - `@props([])` — no props
    - Wrap in `<figure>`; add `<figcaption class="sr-only">تصویر مدرس</figcaption>`
    - `<div id="teacher-frame-slot" role="img" aria-label="قاب پرتره مدرس" class="aspect-square min-h-[200px]"></div>`
    - `<div id="teacher-photo-slot" role="img" aria-label="تصویر پروفایل مدرس"></div>` — Phase 2 stub
    - Both slots: empty divs only — no img, no src, no background-image CSS
    - Tag: `<x-ui.teacher.portrait.portrait-frame>`
    - _Requirements: 3.1, 3.4, 12.4 | Architecture Rules: 1, 3, 7, 12_

  - [x] 4.3 Create `resources/views/components/ui/teacher/portrait/portrait-image.blade.php`
    - `@props([])` — no props
    - Empty stub — renders nothing in Phase 1
    - `{{-- Phase 2: portrait image will load via #teacher-photo-slot --}}`
    - Tag: `<x-ui.teacher.portrait.portrait-image>`
    - _Requirements: 12.3, 12.4_

  - [x] 4.4 Create `resources/views/components/ui/teacher/hero/portrait-layer.blade.php`
    - `@props([])` — no props
    - Grid cell: `col-span-12 md:col-span-4`
    - z-index: `style="z-index: var(--z-hero-portrait)"` (CSS variable injection — Rule 12 exception)
    - Embed `<x-ui.teacher.portrait.portrait-frame />`
    - Tag: `<x-ui.teacher.hero.portrait-layer>`
    - _Requirements: 3.1, 3.2, 3.3 | Architecture Rules: 1, 14_

  - [x] 4.5 Create `resources/views/components/ui/teacher/hero/decoration-layer.blade.php`
    - `@props([])` — no props
    - `<div aria-hidden="true" style="z-index: var(--z-hero-decoration)">` (CSS variable injection — Rule 12 exception)
    - Inside: `<div id="teacher-decoration-slot"></div>` — empty, reserved for Phase 2
    - Zero visual output in Phase 1
    - Tag: `<x-ui.teacher.hero.decoration-layer>`
    - _Requirements: 12.1, 12.2, 12.3 | Architecture Rules: 1, 14_

- [x] 5. Create info-layer composite component (`hero/`)
  - [x] 5.1 Create `resources/views/components/ui/teacher/hero/info-layer.blade.php`
    - `@props(['teacher'])` — receives full teacher array
    - Root: `<article dir="rtl" style="z-index: var(--z-hero-info)">` (CSS variable injection — Rule 12 exception)
    - Grid cell: `col-span-12 md:col-span-4`
    - Mobile: `text-center items-center`; desktop: normal via responsive Tailwind
    - `<h1>{{ $teacher['name'] }}</h1>`, `<p>{{ $teacher['role'] }}</p>` (key: `role`)
    - `<x-ui.teacher.badges.experience-badge :experience="$teacher['experience']" />`
    - Loop: `<x-ui.teacher.chips.instrument-chip :instrument="$instrument" />`
    - `<x-ui.teacher.buttons.cta-button />` — CTA lives here, NOT as standalone layer
    - CSS Grid for internal layout (no Flexbox), no page-specific CSS, no hardcoded values
    - Tag: `<x-ui.teacher.hero.info-layer>`
    - _Requirements: 4.1–4.7, 6.2, 7.2, 7.3, 8.4 | Architecture Rules: 1, 5, 12, 13, 14_

- [x] 6. Create hero orchestrator component (`hero/`)
  - [x] 6.1 Create `resources/views/components/ui/teacher/hero/hero.blade.php`
    - `@props(['teacher'])` — receives full teacher array
    - `<header><section dir="rtl" class="grid grid-cols-12 gap-0 overflow-x-hidden">`
    - Layer order:
      1. `<x-ui.teacher.hero.background-layer />`  — 12-col mobile / 8-col desktop
      2. `<x-ui.teacher.hero.portrait-layer />`    — 12-col mobile / 4-col desktop
      3. `<x-ui.teacher.hero.info-layer :teacher="$teacher" />` — 12-col mobile / 4-col desktop
      4. `<x-ui.teacher.hero.decoration-layer />`  — 12-col (empty in Phase 1)
    - Hero: orchestration ONLY — zero inline content, zero layer logic
    - No animations, no absolute positioning, no images, no hardcoded values
    - Tag: `<x-ui.teacher.hero.hero>`
    - _Requirements: 1.1–1.6, 2.1–2.4, 3.2–3.3, 4.6–4.7, 7.2–7.3, 10.1–10.5, 12.1–12.4 | Architecture Rules: all 14_

- [x] 7. Checkpoint — visual review
  - Open at all breakpoints: 390, 430, 768, 1024, 1366, 1600, 1920px
  - ✓ Stacked + centered on mobile; 8+4 split on desktop
  - ✓ CTA inside info section (not a separate layer)
  - ✓ Persian RTL renders correctly
  - ✓ 4 named slots are empty divs (no img, no src, no CSS background):
    - `#teacher-background-slot`, `#teacher-frame-slot`, `#teacher-photo-slot`, `#teacher-decoration-slot`
  - ✓ No horizontal scroll (`overflow-x-hidden`)
  - ✓ No Flexbox, no animations, no images
  - ✓ Semantic HTML: `<header>`, `<main>`, `<section>`, `<figure>`, `<figcaption>`, `<button type="button">`
  - ✓ ARIA labels on all 4 slots; `aria-hidden="true"` on decoration; visible focus ring on CTA
  - ✓ No inline `style="..."` except CSS variable injections
  - ✓ No hardcoded color/spacing values — all `var(--)` tokens
  - ✓ Z-index on each layer uses `var(--z-hero-*)`
  - Ask user for approval before proceeding to tests.

- [x] 8. Write unit tests
  - [x]* 8.1 Pest: `hero/background-layer` — assert `#teacher-background-slot` present, no `<img>`, no inline style
  - [x]* 8.2 Pest: `portrait/portrait-frame` — assert `#teacher-frame-slot`, `#teacher-photo-slot`, `<figure>`, no `<img>`
  - [x]* 8.3 Pest: `hero/portrait-layer` — assert portrait-frame renders, no `<img>`
  - [x]* 8.4 Pest: `hero/decoration-layer` — assert `#teacher-decoration-slot`, `aria-hidden="true"`, empty output
  - [x]* 8.5 Pest: `badges/experience-badge` — assert experience value in output, no `<img>`/`<svg>`
  - [x]* 8.6 Pest: `chips/instrument-chip` — assert instrument value in output, no `<img>`/`<svg>`
  - [x]* 8.7 Pest: `buttons/cta-button` — assert `<button type="button">`, `aria-label`, default/custom label, no `href`, no inline style
  - [x]* 8.8 Pest: `hero/info-layer` — assert name, role, experience, 3 instruments, `<button type="button">` in output
  - [x]* 8.9 Pest: `hero/hero` — assert `grid-cols-12`, `overflow-x-hidden`, `<header>`, `<section>`, all 4 slots, no `<img>`, all data flows through, `<button type="button">`

- [x] 9. Screenshot verification
  - [x] 9.1 390×844 — mobile stacked, centered, RTL, slots visible as empty blocks
  - [x] 9.2 768×1024 — 8+4 split, CTA inside info column
  - [x] 9.3 1366×768 — desktop layout, no horizontal scroll
  - [x] 9.4 1920×1080 — full HD, no stretching or overflow

- [x] 10. Final checkpoint — freeze layout
  - ✓ All 10 components at correct paths (hero/, portrait/, badges/, chips/, buttons/)
  - ✓ `resources/css/teacher-theme.css` with z-index tokens
  - ✓ `resources/views/teachers/show.blade.php` (plural)
  - ✓ `role` key (not `title`) everywhere
  - ✓ 4 named slots: `#teacher-background-slot`, `#teacher-frame-slot`, `#teacher-photo-slot`, `#teacher-decoration-slot`
  - ✓ Asset dirs under `storage/app/public/ui/teacher/`
  - ✓ Rules 12, 13, 14 enforced throughout
  - ✓ No backend, no Eloquent, no Flexbox, no animations, no images
  - ✓ Semantic HTML + ARIA + keyboard + visible focus
  - Ask user to confirm Phase 1 complete and layout frozen.

## Notes

- `*` tasks are optional — skip for faster MVP
- Phase 1 frozen after Task 10 — no structural changes allowed after completion
- `teacher-theme.css` is a stub — real values (colors, effects, frames, ornaments) added in Phase 2
- CTA lives INSIDE `info-layer` — not a standalone layer
- Hero = 4 layers: background (z:0) → decoration (z:10) → portrait (z:20) → info+CTA (z:30)
- Z-index hierarchy via `var(--z-hero-*)` — NEVER hardcoded numbers directly
- Design Tokens: all values via `var(--)` — no `#hex`, no `px` literals in component styles
- No inline `style="..."` except for CSS variable injection (`style="z-index: var(--z-hero-*)"`)
- Storybook-style: every component independently renderable, page-agnostic, no page-specific logic
- Phase 2 plan: `teacher-theme.css` fills with real colors, frames, ornaments, particles, light effects

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "2.1", "3.1", "3.2", "3.3", "4.1", "4.2", "4.3", "4.5"] },
    { "id": 1, "tasks": ["4.4", "5.1"] },
    { "id": 2, "tasks": ["6.1"] },
    { "id": 3, "tasks": ["8.1", "8.2", "8.3", "8.4", "8.5", "8.6", "8.7", "8.8"] },
    { "id": 4, "tasks": ["8.9"] },
    { "id": 5, "tasks": ["9.1", "9.2", "9.3", "9.4"] }
  ]
}
```
