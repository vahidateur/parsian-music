# Implementation Plan: Teacher Hero Phase 2 — Visual Foundation

## Overview

Build visual layer on frozen Phase 1 architecture. Add gradient backgrounds, placeholder frame, final typography, token-driven spacing. CSS-only (no images, no animations, no ornaments).

**Constraints:**
- NO architectural changes
- NO component reordering
- NO adding/removing components
- Token-driven styling only
- CSS-only effects (gradients, vignettes, noise)

## Architecture Rules (enforced in every task)

1. **No Architecture Changes:** Phase 1 structure is frozen and immutable
2. **No Component Merges:** All 10 components from Phase 1 remain separate
3. **Token-Driven:** Every value must come from `teacher-theme.css`
4. **No Hardcoded Values:** No hex colors, no pixel dimensions in components (use tokens)
5. **CSS-Only Effects:** Gradients, vignettes, noise — no image assets yet
6. **No Animations:** Static layout only (Phase 5+)
7. **No Particles or Glow:** Reserved for Phase 4+
8. **No Ornaments:** Decoration layer stays empty (Phase 3+)
9. **Grid Layout Only:** 12-col mobile-first, responsive column spans for tablet/desktop
10. **Placeholder Frames:** Show dashed outlines where real assets will go (Phase 3+)
11. **Responsive Column Splits:** Desktop 8/4, Tablet 7/5, Mobile 12-col stacked
12. **Portrait First on Mobile:** CSS order property for visual hierarchy
13. **Final Dimensions:** Portrait 520×720 exact, photo 460×660 inset, CTA 52px height, badge 32px height
14. **Rule 15 in Effect:** After freeze, only bug fixes allowed. Architectural changes require Phase 3.

## Tasks

- [ ] 1. Extend teacher-theme.css with Phase 2 tokens
  - [ ] 1.1 Add color gradient tokens
    - `--hero-gradient-start`, `--hero-gradient-end`
    - `--hero-vignette-color`, `--hero-noise-opacity`
    - `--badge-border-color`, `--chip-background`
    - All colors reference main design system (var(--neutral-*), var(--gold-*), etc.)
    - _Requirements: RQ-2.21, RQ-2.22_

  - [ ] 1.2 Add dimension tokens
    - `--portrait-width: 520px`, `--portrait-height: 720px`
    - `--portrait-photo-width: 460px`, `--portrait-photo-height: 660px`
    - `--portrait-inset: 30px`
    - `--badge-height: 32px`, `--chip-height: 30px`, `--cta-height: 52px`
    - `--hero-min-height-mobile: 480px`, `--hero-min-height-tablet: 500px`, `--hero-min-height-desktop: 600px`
    - _Requirements: RQ-2.21, RQ-2.22_

  - [ ] 1.3 Organize tokens by section
    - Comments: `/* COLORS */`, `/* HERO HEIGHTS */`, `/* PORTRAIT */`, `/* BADGES */`, `/* BUTTONS */`, `/* DECORATIONS */`
    - Clear grouping for future phases
    - _Requirements: RQ-2.21_

- [ ] 2. Style background layer (gradient + vignette + noise)
  - [ ] 2.1 Apply linear gradient
    - Start: `var(--hero-gradient-start)` (neutral-900)
    - End: `var(--hero-gradient-end)` (neutral-950)
    - Direction: 135deg
    - Uses `background: linear-gradient(...)`
    - _Requirements: RQ-2.1, RQ-2.22_

  - [ ] 2.2 Add radial overlay effect
    - Radial gradient centered at center-top
    - Creates subtle depth and focus
    - Uses CSS `radial-gradient(...)`
    - Subtle opacity (does not harsh)
    - _Requirements: RQ-2.2, RQ-2.22_

  - [ ] 2.3 Add vignette effect
    - `box-shadow: inset 0 0 60px var(--hero-vignette-color)`
    - Darkens outer edges
    - Color: `var(--hero-vignette-color)` = rgba(0,0,0,0.5)
    - _Requirements: RQ-2.3, RQ-2.22_

  - [ ] 2.4 Add subtle noise texture (CSS-only)
    - SVG noise pattern embedded in CSS (data: URL)
    - `background-image: url("data:image/svg+xml,...")`
    - `background-blend-mode: overlay`
    - Opacity: `var(--hero-noise-opacity)` = 0.05
    - Very subtle, does not distract
    - _Requirements: RQ-2.4, RQ-2.22_

  - [ ] 2.5 Apply responsive heights
    - Mobile: `min-height: var(--hero-min-height-mobile)` = 480px
    - Tablet: `min-height: var(--hero-min-height-tablet)` = 500px
    - Desktop: `min-height: var(--hero-min-height-desktop)` = 600px
    - Use `@media` queries for breakpoints
    - _Requirements: RQ-2.5, RQ-2.22_

  - [ ] 2.6 Verify background-layer component renders correctly
    - Gradient visible on page
    - Vignette edges visible
    - No image asset (CSS only)
    - Responsive heights work
    - _Requirements: RQ-2.1 through RQ-2.5_

- [ ] 3. Style portrait layer (frame placeholder)
  - [ ] 3.1 Create dashed frame border
    - Border: `2px dashed var(--glass-border)`
    - Dimensions: `width: var(--portrait-width)`, `height: var(--portrait-height)` = 520×720px
    - Border-radius: `var(--radius-md)`
    - Background: subtle `var(--neutral-850)`
    - _Requirements: RQ-2.6, RQ-2.22_

  - [ ] 3.2 Create photo slot placeholder
    - Inside frame: `460×660px` (30px inset all sides)
    - Border: `1px solid var(--glass-border)`
    - Border-radius: `var(--radius-sm)`
    - Background: `var(--neutral-900)` with subtle opacity
    - Placeholder text: "تصویر مدرس" (centered, screen-reader only)
    - _Requirements: RQ-2.6, RQ-2.7, RQ-2.22_

  - [ ] 3.3 Verify portrait frame renders correctly
    - Frame shows dashed border, exactly 520×720px
    - Photo slot inside, exactly 460×660px
    - No image, no SVG, no AI asset
    - Placeholder state (soft colors)
    - _Requirements: RQ-2.6, RQ-2.7_

- [ ] 4. Keep decoration layer empty (Phase 2 hold)
  - [ ] 4.1 Verify decoration layer is hidden
    - Layer not rendered visually
    - Slot reserved but empty
    - `aria-hidden="true"` respected
    - Z-index: `var(--z-hero-decoration)` = 10
    - _Requirements: RQ-2.8_

- [ ] 5. Style info layer (typography + spacing)
  - [ ] 5.1 Apply final typography sizes
    - Name (h1): `font-size: var(--text-3xl)` = 36px, weight 700
    - Role (p): `font-size: var(--text-lg)` = 18px, weight 400
    - All text uses `var(--text-*` tokens
    - _Requirements: RQ-2.10, RQ-2.22_

  - [ ] 5.2 Apply final spacing
    - Gap between elements: `gap: var(--space-3)` = 1rem
    - Padding x/y: `var(--space-6)` top/bottom, `var(--space-3)` left/right
    - All spacing uses `var(--space-*)` tokens
    - _Requirements: RQ-2.9, RQ-2.22_

  - [ ] 5.3 Style experience badge
    - Height: `var(--badge-height)` = 32px
    - Border: `1px solid var(--gold-300)`
    - Border-radius: `var(--radius-xs)` = 6px
    - Padding: `var(--space-1)` y, `var(--space-2)` x
    - Background: transparent, color: `var(--gold-300)`
    - Font: `var(--text-sm)` 500
    - _Requirements: RQ-2.11, RQ-2.22_

  - [ ] 5.4 Style instrument chips
    - Height: `var(--chip-height)` = 30px
    - Border: `1px solid var(--glass-border)`
    - Border-radius: `var(--radius-xs)` = 6px
    - Padding: `var(--space-1)` y, `var(--space-2)` x
    - Background: `var(--glass-bg)`, color: `var(--text-secondary)`
    - Gap between chips: `var(--space-1)` = 4px
    - Font: `var(--text-sm)` 400
    - _Requirements: RQ-2.12, RQ-2.22_

  - [ ] 5.5 Style CTA button
    - Height: `var(--cta-height)` = 52px
    - Padding: `var(--space-2)` y, `var(--space-4)` x
    - Width: 100% mobile, auto desktop (max 300px)
    - Background: `var(--gold-300)`, color: `var(--neutral-950)`
    - Border-radius: `var(--radius-sm)` = 12px
    - Font: `var(--text-md)` 700
    - Focus ring: outline 2px, offset 2px
    - _Requirements: RQ-2.13, RQ-2.22_

  - [ ] 5.6 Verify info layer renders correctly
    - All typography matches final sizes
    - All spacing uses tokens
    - Badge, chips, CTA dimensions correct
    - No hardcoded px values
    - _Requirements: RQ-2.9 through RQ-2.13_

- [ ] 6. Implement responsive grid layout
  - [ ] 6.1 Desktop layout (≥1024px)
    - Background: `grid-column: span 8`
    - Portrait + Decoration: `grid-column: span 4`
    - Info inside portrait column
    - No change to Phase 1 component positions
    - _Requirements: RQ-2.14, RQ-2.22_

  - [ ] 6.2 Tablet layout (768px–1023px)
    - Background: `grid-column: span 7`
    - Portrait + Decoration: `grid-column: span 5`
    - Info inside portrait column
    - _Requirements: RQ-2.15, RQ-2.22_

  - [ ] 6.3 Mobile layout (<768px)
    - All sections: `grid-column: span 12`
    - Portrait first: `order: 1`
    - Info second: `order: 2`
    - Background behind: `order: 0`
    - All centered alignment
    - _Requirements: RQ-2.16, RQ-2.22_

  - [ ] 6.4 Verify responsive layout at all breakpoints
    - 390px: portrait first, stacked
    - 768px: 7/5 split
    - 1024px: 8/4 split
    - 1920px: full layout, no overflow
    - _Requirements: RQ-2.14 through RQ-2.16_

- [ ] 7. Verify no animations, no particles, no ornaments
  - [ ] 7.1 Check for animations
    - No `@keyframes`
    - No `transition` properties
    - No `animation` CSS
    - Static layout only
    - _Requirements: RQ-2.17_

  - [ ] 7.2 Check for particles or glow effects
    - No blur, glow, shadow (except vignette)
    - No particle systems
    - Vignette only effect
    - _Requirements: RQ-2.18_

  - [ ] 7.3 Check decoration layer is empty
    - No ornaments rendered
    - Slot empty, layout reserved
    - Aria-hidden respected
    - _Requirements: RQ-2.19_

- [ ] 8. Checkpoint — visual review
  - [ ] 8.1 Open at all breakpoints: 390, 430, 768, 1024, 1366, 1600, 1920px
  - [ ] 8.2 Verify gradient and vignette on background
  - [ ] 8.3 Verify portrait frame (dashed, 520×720)
  - [ ] 8.4 Verify info layer typography (final sizes)
  - [ ] 8.5 Verify responsive grid splits (desktop 8/4, tablet 7/5, mobile stacked)
  - [ ] 8.6 Verify portrait first on mobile (via order or visual inspection)
  - [ ] 8.7 Verify no animations, particles, ornaments
  - [ ] 8.8 Verify all spacing uses tokens
  - [ ] 8.9 Verify CTA 52px, badge 32px, chip 30px heights
  - [ ] 8.10 Ask user for approval before proceeding to freeze

- [ ] 9. Screenshot verification
  - [ ]* 9.1 390×844 — mobile stacked, portrait first, centered, no scroll
  - [ ]* 9.2 768×1024 — tablet 7/5 split, info column displays correctly
  - [ ]* 9.3 1366×768 — desktop 8/4 split, background gradient visible
  - [ ]* 9.4 1920×1080 — full HD, no stretching, vignette visible

- [ ] 10. Final checkpoint — freeze Phase 2 layout
  - ✓ All styling uses design tokens
  - ✓ No hardcoded colors, dimensions, spacing
  - ✓ Gradient, vignette, noise CSS-only
  - ✓ Portrait frame dashed placeholder
  - ✓ Info layer final typography and spacing
  - ✓ Responsive grid: desktop 8/4, tablet 7/5, mobile 12-col
  - ✓ Portrait first on mobile
  - ✓ No animations, particles, ornaments
  - ✓ Decoration layer empty
  - ✓ Freeze Phase 2 layout permanently (Rule 15)
  - Ask user to confirm Phase 2 complete and layout frozen.

## Notes

- `*` tasks are optional — skip for faster MVP
- Phase 2 frozen after Task 10 — no structural changes allowed after completion
- All styling via `teacher-theme.css` tokens
- CSS-only effects: no image assets yet
- Responsive grid changes: desktop 8/4, tablet 7/5, mobile stacked
- Portrait reordering: mobile stacked with portrait first (via CSS order)
- Freeze after completion: no redesign allowed in Phase 3+

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "2.1", "2.2", "2.3", "2.4", "2.5", "2.6", "3.1", "3.2", "3.3", "4.1", "5.1", "5.2", "5.3", "5.4", "5.5", "5.6", "6.1", "6.2", "6.3", "6.4", "7.1", "7.2", "7.3"] },
    { "id": 1, "tasks": ["8"] },
    { "id": 2, "tasks": ["9.1", "9.2", "9.3", "9.4"] },
    { "id": 3, "tasks": ["10"] }
  ]
}
```

