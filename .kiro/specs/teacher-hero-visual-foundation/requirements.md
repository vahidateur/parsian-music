# Phase 2 Requirements: Teacher Hero Visual Foundation

## Overview

Build the visual layer on top of Phase 1's frozen architecture. NO architectural changes. Only styling and visual enhancements using design tokens and CSS. Add gradient backgrounds, placeholder dimensions, final typography, and spacing using `teacher-theme.css` tokens.

---

## Requirement Group: Background Layer Visual Foundation

### RQ-2.1: Background Gradient Overlay
**Given** the background layer component  
**When** rendered  
**Then** it fills with a temporary dark gradient  
- Start: Dark neutral-900 (top)
- End: Dark neutral-950 (bottom)
- No image asset
- CSS gradient only
- Uses `var(--color-...)` tokens

### RQ-2.2: Radial Overlay
**Given** the background gradient  
**When** rendered  
**Then** add a radial overlay effect  
- Radial gradient centered at (top-left → center)
- Creates depth and focus
- Uses CSS variables only
- No hardcoded hex or rgba

### RQ-2.3: Vignette Effect
**Given** the background layer  
**When** rendered  
**Then** add subtle vignette edges  
- Darkens outer edges
- Draws focus to center
- CSS `box-shadow` or gradient-based
- Subtle opacity (not harsh)

### RQ-2.4: Noise Texture
**Given** the background layer  
**When** rendered  
**Then** add subtle noise texture using CSS only  
- No image asset
- CSS-only noise (e.g., SVG filter or noise pattern)
- Very subtle (does not distract)
- Uses `var(--opacity-...)` tokens

### RQ-2.5: Background Slot Final Dimensions
**Given** the `#teacher-background-slot`  
**When** rendered  
**Then** use exact final dimensions  
- Desktop: full width, min-height 600px (from tokens)
- Tablet: full width, min-height 500px (from tokens)
- Mobile: full width, min-height 480px (from tokens)
- No responsive scaling of dimensions
- Reserved for Phase 3+ real asset

---

## Requirement Group: Portrait Layer Visual Foundation

### RQ-2.6: Portrait Frame Placeholder
**Given** the portrait layer component  
**When** rendered  
**Then** show a soft dashed frame placeholder  
- Frame dimensions: 520×720px (EXACT, no scaling)
- Photo slot inside: 460×660px (30px inset all sides)
- Dashed border style (not solid)
- Uses `var(--color-...)` for border color
- Border width: `var(--border-width-...)`

### RQ-2.7: No Image in Portrait Frame
**Given** the portrait frame  
**When** rendered  
**Then** show placeholder only  
- No `<img>` tag
- No SVG
- No AI asset
- Empty slots with background color from tokens
- Reserved for Phase 3+ real portrait image

---

## Requirement Group: Decoration Layer

### RQ-2.8: Decoration Layer Reserved
**Given** the decoration layer component  
**When** rendered  
**Then** keep layout reserved but render nothing  
- `aria-hidden="true"` (already set in Phase 1)
- Slot visible but empty
- Z-index: `var(--z-hero-decoration)` = 10
- No ornaments, particles, or effects yet
- Reserved for Phase 3+ decorative assets

---

## Requirement Group: Info Layer Visual Foundation

### RQ-2.9: Info Layer Spacing
**Given** the info layer component  
**When** rendered  
**Then** use final spacing from design tokens  
- Gap between elements: `var(--space-3)` (1rem)
- Top/bottom padding: `var(--space-6)`
- Left/right padding: `var(--space-3)`
- All spacing via CSS tokens, no hardcoded px

### RQ-2.10: Typography Sizes — Final
**Given** the info layer text elements  
**When** rendered  
**Then** use final typography sizes  
- Name (h1): `var(--text-3xl)` (36px), font-weight 700
- Role (p): `var(--text-lg)` (18px), font-weight 400
- All sizes via `var(--text-...)` tokens

### RQ-2.11: Badge Final Dimensions
**Given** the experience badge component  
**When** rendered  
**Then** use final dimensions and styling  
- Height: 32px (fixed)
- Padding: `var(--space-2)` x, `var(--space-1)` y
- Border: 1px `var(--gold-300)`
- Border-radius: `var(--radius-xs)`
- Font size: `var(--text-sm)`
- Uses tokens only, no hardcoded values

### RQ-2.12: Chip Spacing and Sizing
**Given** the instrument chip component  
**When** rendered  
**Then** use final dimensions and spacing  
- Height: ~30px (fixed)
- Padding: `var(--space-2)` x, `var(--space-1)` y
- Gap between chips: `var(--space-1)` (4px)
- Border-radius: `var(--radius-xs)`
- Font size: `var(--text-sm)`
- Background: `var(--glass-bg)` token
- Uses tokens only

### RQ-2.13: CTA Button Final Dimensions
**Given** the CTA button component  
**When** rendered  
**Then** use final dimensions  
- Height: 52px (fixed)
- Padding: `var(--space-4)` x, `var(--space-2)` y
- Width: 100% on mobile, contained on desktop
- Border-radius: `var(--radius-sm)`
- Font size: `var(--text-md)` (16px)
- Font weight: 700
- Background: `var(--gold-300)`
- Color (text): `var(--neutral-950)`
- Uses tokens only

---

## Requirement Group: Hero Layout Responsiveness

### RQ-2.14: Desktop Layout — 8/4 Split
**Given** the hero at desktop breakpoint (≥1024px)  
**When** rendered  
**Then** display 8+4 column grid split  
- Background: 8 columns
- Portrait + Decoration: 4 columns
- Info: inside portrait column
- No change to Phase 1 responsive grid structure

### RQ-2.15: Tablet Layout — 7/5 Split
**Given** the hero at tablet breakpoint (768px–1023px)  
**When** rendered  
**Then** display 7+5 column grid split  
- Background: 7 columns
- Portrait + Decoration: 5 columns
- Info: inside portrait column
- Responsive via Tailwind grid utilities

### RQ-2.16: Mobile Layout — Stacked, Portrait First
**Given** the hero at mobile breakpoint (<768px)  
**When** rendered  
**Then** stack all sections vertically, portrait first  
- Portrait layer: 12 columns (full width)
- Background layer: 12 columns (full width)
- Info layer: 12 columns (full width)
- Order: portrait first (visual hierarchy on small screens)
- All centered alignment

---

## Requirement Group: Visual Restrictions

### RQ-2.17: No Animations
**Given** any component in Phase 2  
**When** rendered  
**Then** static layout only  
- No `@keyframes`
- No `transition` properties
- No `animation` CSS
- Animations reserved for Phase 5+

### RQ-2.18: No Particles or Glow Effects
**Given** the background or decoration layers  
**When** rendered  
**Then** no particle systems or glow effects  
- CSS gradient and vignette only
- No blur, glow, or shadow effects beyond vignette
- Reserved for Phase 4+

### RQ-2.19: No Ornaments
**Given** the decoration layer  
**When** rendered  
**Then** no ornamental elements rendered  
- Slot remains empty
- Layout reserved for Phase 3+
- No SVG ornaments, frames, or icons

### RQ-2.20: No Hardcoded Pixel Values
**Given** any styling in Phase 2  
**When** written  
**Then** use `teacher-theme.css` tokens only  
- No `width: 520px` inline — use `var(--portrait-width)`
- No `height: 720px` inline — use `var(--portrait-height)`
- No `color: #D5AF58` — use `var(--gold-300)`
- Exception: exact final dimensions (520×720) are allowed as token values in CSS, not inline styles

---

## Requirement Group: Design Tokens

### RQ-2.21: Extend teacher-theme.css
**Given** the design token file  
**When** updated for Phase 2  
**Then** add new variables  
- Background gradient colors: `--bg-gradient-start`, `--bg-gradient-end`
- Vignette: `--vignette-color`, `--vignette-opacity`
- Noise: `--noise-opacity`
- Portrait dimensions: `--portrait-width: 520px`, `--portrait-height: 720px`, `--portrait-photo-width: 460px`, `--portrait-photo-height: 660px`
- Badge: `--badge-height: 32px`
- Chip: `--chip-height: 30px`
- CTA: `--cta-height: 52px`
- Layout gaps: `--layout-gap-desktop: 0px`, `--layout-gap-tablet: 0px`
- All new tokens grouped by section (COLORS, HERO, PORTRAIT, BADGES, BUTTONS, DECORATIONS)

### RQ-2.22: No Hardcoded Values in Components
**Given** any Blade component  
**When** rendered  
**Then** all styling values use `var(--...)` tokens  
- Every color: `var(--*)`
- Every dimension: `var(--*)`
- Every spacing: `var(--*)`
- Every radius: `var(--*)`
- Every opacity: `var(--*)`

---

## Requirement Group: Freeze After Completion

### RQ-2.23: Freeze Phase 2 Layout
**Given** all visual enhancements complete  
**When** approved  
**Then** freeze Phase 2 layout permanently  
- Rule 15: No redesign of frozen phase
- Only bug fixes allowed after freeze
- Architectural changes require Phase 3
- Document freeze date and scope

---

## Notes

- **No Architecture Changes:** Phase 1 component structure is immutable.
- **No Component Merges:** All 10 components from Phase 1 remain separate.
- **Token-Driven:** Every value must come from `teacher-theme.css`.
- **CSS-Only Backgrounds:** No image assets in Phase 2 (gradients, vignettes, noise only).
- **Placeholder Frames:** Show dashed outlines where real assets will go in Phase 3+.
- **Desktop 8/4, Tablet 7/5, Mobile Stacked:** Responsive grid changes only, no architecture changes.
- **Portrait First on Mobile:** Visual reordering via CSS order property or grid ordering.
- **Freeze on Completion:** Phase 2 becomes immutable once approved; Phase 3+ requires new phase.

