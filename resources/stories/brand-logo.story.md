# Brand Logo - Component Story

## Overview

The Parsian Music Academy logo component represents the brand identity across the entire application.

**Component:** `<x-ui.brand.logo />`  
**Category:** Brand  
**Status:** ✅ Complete

## Design Specification

### Visual Style
- **Shape:** Circular outline
- **Border:** Thin golden stroke (1.5px)
- **Fill:** Transparent center
- **Icon:** Minimal treble clef (musical symbol)
- **Style:** Flat vector (no gradients, no 3D, no shadows)

### Colors
- **Border:** `var(--gold-300)` (#D5AF58)
- **Icon:** `var(--gold-300)` (#D5AF58)
- **Background:** Transparent

### Dimensions
| Size | Dimension | Usage |
|------|-----------|-------|
| `sm` | 40px | Navigation, mobile header, badges |
| `md` | 64px | Login page, cards, default size |
| `lg` | 96px | Hero sections, landing page |

## Usage Examples

### Basic Usage
```blade
<x-ui.brand.logo />
```

### Size Variants
```blade
{{-- Small logo for navigation --}}
<x-ui.brand.logo size="sm" />

{{-- Medium logo for login (default) --}}
<x-ui.brand.logo size="md" />

{{-- Large logo for hero sections --}}
<x-ui.brand.logo size="lg" />
```

### With Animations
```blade
{{-- Spinning logo --}}
<x-ui.brand.logo class="logo-spin" />

{{-- Glowing logo --}}
<x-ui.brand.logo class="logo-glow" />

{{-- Hover scale --}}
<x-ui.brand.logo class="hover:scale-110 transition-transform" />
```

### In Context
```blade
{{-- Login header --}}
<header class="flex flex-col items-center">
    <x-ui.brand.logo size="md" class="mb-4" />
    <h1>آموزشگاه موسیقی پارسیان</h1>
</header>

{{-- Navigation bar --}}
<nav class="flex items-center gap-3">
    <x-ui.brand.logo size="sm" />
    <span>پارسیان</span>
</nav>

{{-- Hero section --}}
<section class="hero">
    <x-ui.brand.logo size="lg" class="logo-glow" />
</section>
```

## Props

| Prop | Type | Default | Options | Description |
|------|------|---------|---------|-------------|
| `size` | string | `'md'` | `'sm'`, `'md'`, `'lg'` | Logo dimensions |
| `class` | string | - | Any CSS classes | Additional styling |

## States

### Normal
Default appearance with golden border and treble clef icon.

### Hover
Can be styled with hover effects:
```blade
<x-ui.brand.logo class="hover:opacity-80" />
```

### Focus
When wrapped in focusable element:
```blade
<a href="/" class="focus:outline-none focus:ring-2 focus:ring-gold-300">
    <x-ui.brand.logo />
</a>
```

### Disabled
Reduced opacity for disabled state:
```blade
<x-ui.brand.logo class="opacity-50" />
```

### Animated
Use animation classes from `animations.css`:
```blade
<x-ui.brand.logo class="logo-spin" />
<x-ui.brand.logo class="logo-glow" />
```

## Accessibility

### Screen Readers
- `role="img"` - Semantic image role
- `aria-label="لوگوی آموزشگاه موسیقی پارسیان"` - Persian label

### Keyboard Navigation
- Non-interactive by default (no tab stop)
- When wrapped in link/button, inherits focus behavior

### Color Contrast
- Golden color (#D5AF58) on dark background meets WCAG AA
- Maintains visibility in all themes

### Reduced Motion
- Logo respects `prefers-reduced-motion`
- Animations disabled automatically

## Technical Details

### Implementation
- **Format:** Inline SVG
- **ViewBox:** `0 0 64 64`
- **Stroke:** Golden color token
- **Fill:** Transparent

### Performance
- Inline SVG (no HTTP request)
- Minimal DOM nodes
- GPU-accelerated animations

### Browser Support
- All modern browsers
- IE11+ (SVG support required)

## Design Tokens Used

```css
--gold-300: #D5AF58;
--duration-fast: 200ms;
--ease-standard: cubic-bezier(0.22, 1, 0.36, 1);
```

## Related Components

- `<x-ui.brand.title />` - Academy Persian title
- `<x-ui.brand.subtitle />` - Persian subtitle
- `<x-ui.brand.english-title />` - English brand name
- `<x-ui.brand.divider />` - Brand section divider

## Themes

### Dark Theme (Default)
- Border: Golden (#D5AF58)
- Works perfectly on dark backgrounds

### Light Theme
- Same golden color (visible on light bg)
- May need opacity adjustment

### Academy Gold Theme
- Enhanced golden border
- More prominent glow

## Snapshots

### Desktop (1920×1080)
- **Size md:** 64×64px
- **Position:** Centered in login card header
- **Spacing:** 16px margin bottom

### Tablet (768px)
- Same as desktop
- No size changes

### Mobile (< 768px)
- Can use `sm` size (40px) for space efficiency
- Still uses `md` in login page

## Testing Checklist

- [x] Renders correctly in all sizes (sm, md, lg)
- [x] Uses design tokens (no hard-coded colors)
- [x] Accessible (role, aria-label)
- [x] Works in dark theme
- [x] SVG scales crisply
- [x] Animation-ready (spin, glow work)
- [x] Respects reduced motion
- [x] No layout shift
- [x] Proper spacing in context

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-07-13 | Initial implementation with treble clef icon |

---

**Component Status:** 🟢 Production Ready  
**Last Updated:** July 13, 2026
