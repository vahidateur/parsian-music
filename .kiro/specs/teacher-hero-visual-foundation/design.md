# Phase 2 Design: Teacher Hero Visual Foundation

## Overview

Visual styling for Phase 1 frozen architecture. Gradient backgrounds, placeholder frames, final typography, token-driven spacing. CSS-only effects (no images, no animations, no ornaments).

---

## Design System Reference

### Colors (from teacher-theme.css)
- Primary gradient: `var(--neutral-900)` → `var(--neutral-950)`
- Accent: `var(--gold-300)`
- Text primary: `var(--text-primary)`
- Text secondary: `var(--text-secondary)`
- Glass background: `var(--glass-bg)` with `var(--glass-border)`

### Spacing (Design Tokens)
- Base unit: 0.25rem (4px)
- Gap standard: `var(--space-3)` = 1rem (16px)
- Padding hero: `var(--space-6)` = 1.5rem (24px)
- Between info items: `var(--space-3)` = 1rem
- Chip gap: `var(--space-1)` = 0.25rem (4px)

### Typography (Design Tokens)
- Heading 1 (Name): `var(--text-3xl)` = 36px, weight 700
- Body (Role): `var(--text-lg)` = 18px, weight 400
- Badge/Chip text: `var(--text-sm)` = 12px, weight 500/400
- CTA text: `var(--text-md)` = 16px, weight 700

### Border Radius (Design Tokens)
- Extra small: `var(--radius-xs)` = 6px
- Small: `var(--radius-sm)` = 12px
- Medium: `var(--radius-md)` = 16px

---

## Component Styling Guide

### 1. Background Layer

**CSS Structure:**
```css
.teacher-hero-background {
  /* Grid positioning */
  grid-column: span 12; /* mobile */
  @media (min-width: 768px) { grid-column: span 8; } /* tablet/desktop */
  
  /* Z-index */
  z-index: var(--z-hero-background); /* = 0 */
  
  /* Gradient background */
  background: linear-gradient(
    135deg,
    var(--neutral-900) 0%,
    var(--neutral-950) 100%
  );
  
  /* Radial overlay (center light, edges dark) */
  background: 
    radial-gradient(
      ellipse at center top,
      rgba(255, 255, 255, 0.03) 0%,
      transparent 50%
    ),
    linear-gradient(
      135deg,
      var(--neutral-900) 0%,
      var(--neutral-950) 100%
    );
  
  /* Vignette edges */
  box-shadow: inset 0 0 60px rgba(0, 0, 0, 0.5);
  
  /* Noise texture (CSS-only) */
  background-image: 
    url("data:image/svg+xml,%3Csvg...%3E"), /* noise SVG pattern */
    /* gradients above */
  background-blend-mode: overlay;
  opacity: 0.98; /* Allow layer below to peek */
  
  /* Dimensions */
  min-height: var(--hero-min-height-mobile, 480px);
  @media (min-width: 768px) {
    min-height: var(--hero-min-height-tablet, 500px);
  }
  @media (min-width: 1024px) {
    min-height: var(--hero-min-height-desktop, 600px);
  }
  
  position: relative;
}

#teacher-background-slot {
  /* Placeholder slot */
  width: 100%;
  height: 100%;
  background: transparent;
  /* Reserved for Phase 3+ real asset */
}
```

**Visual Result:**
- Dark gradient background (top light-dark, bottom dark)
- Radial highlight at center-top (subtle depth)
- Vignette darkening edges
- Subtle noise texture overlay
- Slot empty, ready for Phase 3 image

---

### 2. Portrait Layer

**CSS Structure:**
```css
.teacher-hero-portrait-layer {
  /* Grid positioning */
  grid-column: span 12; /* mobile */
  @media (min-width: 768px) { grid-column: span 4; } /* tablet/desktop */
  
  /* Mobile reordering: portrait first */
  @media (max-width: 767px) { order: 1; }
  
  /* Z-index */
  z-index: var(--z-hero-portrait); /* = 20 */
  
  /* Alignment */
  display: grid;
  place-items: center;
  padding: var(--space-6) var(--space-3);
}

figure.teacher-portrait-frame {
  /* Exact dimensions - FROZEN */
  width: var(--portrait-width, 520px);
  height: var(--portrait-height, 720px);
  
  /* Dashed border placeholder */
  border: 2px dashed var(--glass-border);
  border-radius: var(--radius-md);
  background: var(--neutral-850); /* subtle background */
  
  position: relative;
  margin: 0;
}

#teacher-frame-slot {
  width: 100%;
  height: 100%;
  border-radius: var(--radius-md);
  background: transparent;
  position: relative;
  display: grid;
  place-items: center;
}

#teacher-photo-slot {
  /* Photo inset: 30px from frame edge */
  width: var(--portrait-photo-width, 460px);
  height: var(--portrait-photo-height, 660px);
  
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-sm);
  background: var(--neutral-900);
  
  /* Placeholder content */
  display: grid;
  place-items: center;
  color: var(--text-secondary);
  font-size: var(--text-sm);
  
  /* Soft focus effect (placeholder state) */
  opacity: 0.8;
}

figcaption {
  /* Screen reader only */
  display: none;
}
```

**Visual Result:**
- Exact 520×720px frame with dashed border
- Photo slot inside: 460×660px (30px inset)
- Subtle background color
- Placeholder state (soft focus, muted colors)
- No image, no SVG, no AI asset

---

### 3. Decoration Layer

**CSS Structure:**
```css
.teacher-hero-decoration-layer {
  /* Grid positioning */
  grid-column: span 12;
  
  /* Accessibility */
  aria-hidden: true;
  
  /* Z-index */
  z-index: var(--z-hero-decoration); /* = 10 */
  
  /* Layout reserved, no visual output */
  display: none; /* Hidden in Phase 2 */
  /* or: visibility: hidden; opacity: 0; */
}

#teacher-decoration-slot {
  /* Empty, reserved for Phase 3+ ornaments */
  width: 100%;
  height: 100%;
}
```

**Visual Result:**
- Layer hidden (not rendered)
- Slot reserved but empty
- Ready for Phase 3 decorative assets

---

### 4. Info Layer

**CSS Structure:**
```css
.teacher-hero-info-layer {
  /* Grid positioning */
  grid-column: span 12; /* mobile */
  @media (min-width: 768px) { grid-column: span 4; } /* tablet/desktop */
  
  /* Mobile reordering: info second (portrait first) */
  @media (max-width: 767px) { order: 2; }
  
  /* Z-index */
  z-index: var(--z-hero-info); /* = 30 */
  
  /* Layout */
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-3);
  
  /* Alignment */
  place-items: center; /* mobile */
  @media (min-width: 768px) { place-items: start; }
  
  /* Padding */
  padding: var(--space-6) var(--space-3);
  
  /* RTL support */
  direction: rtl;
}

/* Name (h1) */
.teacher-hero-info-layer h1 {
  font-size: var(--text-3xl);
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
  text-align: center;
  @media (min-width: 768px) { text-align: start; }
  margin: 0;
  padding: 0;
}

/* Role (p) */
.teacher-hero-info-layer > p {
  font-size: var(--text-lg);
  font-weight: 400;
  color: var(--text-secondary);
  text-align: center;
  @media (min-width: 768px) { text-align: start; }
  margin: 0;
  padding: 0;
}

/* Experience badge */
.teacher-experience-badge {
  height: var(--badge-height, 32px);
  padding: var(--space-1) var(--space-2);
  border: 1px solid var(--gold-300);
  border-radius: var(--radius-xs);
  background: transparent;
  color: var(--gold-300);
  font-size: var(--text-sm);
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Instrument chips */
.teacher-instruments-container {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-1);
  justify-content: center;
  @media (min-width: 768px) { justify-content: flex-start; }
}

.teacher-instrument-chip {
  height: var(--chip-height, 30px);
  padding: var(--space-1) var(--space-2);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-xs);
  background: var(--glass-bg);
  color: var(--text-secondary);
  font-size: var(--text-sm);
  font-weight: 400;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* CTA Button */
.teacher-cta-button {
  height: var(--cta-height, 52px);
  padding: var(--space-2) var(--space-4);
  width: 100%;
  @media (min-width: 768px) { width: auto; max-width: 300px; }
  
  background: var(--gold-300);
  color: var(--neutral-950);
  border: none;
  border-radius: var(--radius-sm);
  
  font-size: var(--text-md);
  font-weight: 700;
  cursor: pointer;
  
  display: flex;
  align-items: center;
  justify-content: center;
  
  /* Focus ring */
  outline: 2px solid var(--gold-300);
  outline-offset: 2px;
  
  /* Transition for Phase 5 */
  /* transition: background-color 0.2s ease; */
  
  /* Hover state (Phase 5+) */
  /* &:hover { background: var(--gold-200); } */
}

.teacher-cta-button:focus-visible {
  outline: 2px solid var(--gold-300);
  outline-offset: 2px;
}

.teacher-cta-button:active {
  /* Phase 5+: pressed state */
}
```

**Visual Result:**
- Centered mobile, left-aligned desktop
- Name: large bold (36px)
- Role: medium secondary (18px)
- Badge: 32px height, gold border
- Chips: 30px height, glass background, flexible wrap
- CTA: full width mobile, 52px height, gold background
- All spacing via tokens

---

## Layout Grid Specifications

### Desktop (≥1024px)
```
┌─────────────────────────────────────────────────┐
│ Background (8 col)    │ Portrait + Info (4 col) │
│ [gradient+slot]       │ [frame] [name/role]     │
│ [vignette+noise]      │         [badge]         │
│ [min-h: 600px]        │         [chips]         │
│                       │         [CTA]           │
│                       │ z:20     z:30           │
└─────────────────────────────────────────────────┘
```

### Tablet (768px–1023px)
```
┌──────────────────────────────────┐
│ Background (7 col) │ Portrait (5) │
│ [gradient+slot]    │ [frame]      │
│ [vignette+noise]   │ [z:20]       │
│ [min-h: 500px]     │              │
│                    │ Info (5 col) │
│                    │ [z:30]       │
└──────────────────────────────────┘
```

### Mobile (<768px)
```
┌─────────────────────┐
│ Portrait (12 col)   │ (order: 1)
│ [frame] z:20        │
│ [min-h: 480px]      │
├─────────────────────┤
│ Info (12 col)       │ (order: 2)
│ [name/role]         │
│ [badge/chips/CTA]   │
│ [z:30]              │
├─────────────────────┤
│ Background (12 col) │ (order: 0, behind)
│ [gradient+slot]     │
│ [z:0]               │
└─────────────────────┘
```

---

## CSS Tokens to Extend (teacher-theme.css)

```css
/* Phase 2 Additions */

/* Background Layer */
--hero-gradient-start: var(--neutral-900);
--hero-gradient-end: var(--neutral-950);
--hero-vignette-color: rgba(0, 0, 0, 0.5);
--hero-noise-opacity: 0.05;

/* Hero Heights */
--hero-min-height-mobile: 480px;
--hero-min-height-tablet: 500px;
--hero-min-height-desktop: 600px;

/* Portrait Dimensions */
--portrait-width: 520px;
--portrait-height: 720px;
--portrait-photo-width: 460px;
--portrait-photo-height: 660px;
--portrait-inset: 30px;

/* Badge */
--badge-height: 32px;

/* Chip */
--chip-height: 30px;

/* CTA */
--cta-height: 52px;
--cta-width-mobile: 100%;
--cta-width-desktop: auto;
--cta-max-width-desktop: 300px;
```

---

## Browser Support

- Modern browsers: Chrome, Firefox, Safari, Edge (2021+)
- CSS Grid: 100% support
- CSS Variables: 100% support
- Gradients: 100% support
- Box-shadow vignette: 100% support
- SVG filters (for noise): IE11 not supported (graceful degrade to solid)

---

## Performance Considerations

- No images: fast load
- CSS gradients + vignette: GPU-accelerated
- No animations: zero jank
- Minimal JavaScript (phase-agnostic)
- Total added CSS: ~2KB

---

## Accessibility

- **Color Contrast:** Gold (gold-300) on dark (neutral-950) ✓ 7:1+ ratio
- **Focus Ring:** Visible on CTA button, uses outline not box-shadow
- **ARIA Labels:** All 4 slots labeled (existing from Phase 1)
- **Decoration Hidden:** `aria-hidden="true"` respected
- **RTL Support:** `direction: rtl` properly handled
- **Semantic HTML:** No changes from Phase 1

---

## Freezing Phase 2

Once design complete and verified:
1. No architectural changes to frozen layout
2. Only bug fixes permitted
3. Phase 3+ requires new phase for changes
4. Document freeze date and scope

