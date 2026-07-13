# Parsian Music Academy - Design System

## Overview

Complete design system documentation for Parsian Music Academy. This system ensures visual consistency across all application pages: Login, Register, Dashboard, Student Panel, Teacher Panel, and Admin Panel.

## Documentation Structure

### 📁 Core Design Files

| File | Purpose | Status |
|------|---------|--------|
| `colors.md` | Color palette, semantic colors, accessibility | ✅ Complete |
| `spacing.md` | 8px grid system, component spacing | ✅ Complete |
| `radius.md` | Border radius scale, component mapping | ✅ Complete |
| `typography.md` | Type scale, fonts, responsive sizing | ✅ Complete |
| `shadows.md` | Elevation levels, glow effects | ✅ Complete |
| `glass.md` | Glassmorphism guidelines, blur effects | ✅ Complete |
| `motion.md` | Animation timing, easing, reduced motion | ✅ Complete |
| `icons.md` | Lucide icon system, usage patterns | ✅ Complete |
| `layout.md` | Grid system, breakpoints, z-index | ✅ Complete |

### 🎨 Design Tokens

**Location:** `resources/css/design-tokens.css`

Centralized CSS custom properties that implement the design documentation. All values are referenced from the markdown files above.

**Usage:**
```blade
<div class="[background:var(--glass-bg)] [border-radius:var(--radius-lg)]">
```

## Design Principles

### 1. Consistency
Every visual element follows the design tokens. No arbitrary values unless explicitly specified in documentation.

### 2. Accessibility
- WCAG AA contrast ratios (4.5:1 for text)
- Keyboard navigation on all interactive elements
- Respects `prefers-reduced-motion`
- Focus indicators on all focusable elements

### 3. Responsiveness
- Mobile-first approach
- Breakpoints: `xs` (0px), `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px), `2xl` (1920px)
- Design reference: 1920×1080 desktop

### 4. RTL Support
- Persian (Farsi) primary language
- Physical layouts use `dir="ltr"` (split screens)
- Content flows use `dir="rtl"` (text, icons, padding)

### 5. Performance
- GPU-accelerated animations (`transform`, `opacity` only)
- Limited glass blur elements (< 5 per viewport)
- Lazy icon initialization
- Optimized for low-end devices

## Component Architecture

### UI Primitives (`components/ui/`)
Generic, reusable components with NO domain-specific logic.

**Glass Components:**
- `glass-card.blade.php` - Full glass card shell
- `glass-panel.blade.php` - Lighter glass panel
- `glass-section.blade.php` - Glass section wrapper

**Form Components:**
- `input.blade.php` - Text input primitive
- `checkbox.blade.php` - Checkbox primitive
- `button.blade.php` - Button with variants (primary, secondary, ghost, danger, success)
- `form-field.blade.php` - Field wrapper with label, validation, hint

**Brand Components:**
- `brand-logo.blade.php` - Academy logo
- `brand-title.blade.php` - Academy title
- `brand-subtitle.blade.php` - Academy subtitle
- `brand-divider.blade.php` - Decorative divider

**Utility Components:**
- `divider.blade.php` - Section divider
- `icon.blade.php` - Lucide icon wrapper

### Domain Components (`components/auth/`, `components/dashboard/`, etc.)
Domain-specific components that COMPOSE UI primitives.

**Auth Components:**
- `login-card.blade.php` - Composes `<x-ui.glass.card>`
- `login-header.blade.php` - Composes brand components
- `login-form.blade.php` - Composes form fields
- `login-social.blade.php` - Composes social buttons
- `login-footer.blade.php` - Composes quote/footer

## Token Naming Convention

### Colors
- `--{color}-{shade}`: `--gold-300`, `--neutral-950`
- `--text-{level}`: `--text-primary`, `--text-secondary`
- `--{semantic}-{element}`: `--error-500`, `--success-bg`

### Spacing
- `--space-{number}`: `--space-1` (8px), `--space-6` (48px)

### Radius
- `--radius-{size}`: `--radius-xs`, `--radius-lg`, `--radius-full`

### Typography
- `--text-{size}`: `--text-xs`, `--text-2xl`, `--text-4xl`

### Shadows
- `--shadow-{level}`: `--shadow-sm`, `--shadow-xl`
- `--shadow-{element}`: `--shadow-button`, `--shadow-input-focus`

### Motion
- `--duration-{speed}`: `--duration-fast`, `--duration-slow`
- `--ease-{type}`: `--ease-standard`, `--ease-bounce`

### Icons
- `--icon-{size}`: `--icon-xs`, `--icon-lg`

### Layout
- `--container-{size}`: `--container-xs`, `--container-xl`
- `--z-{layer}`: `--z-modal`, `--z-toast`

## Usage Guidelines

### ✅ Do

```blade
{{-- Use design tokens --}}
<div class="[padding:var(--space-6)] [border-radius:var(--radius-lg)]">

{{-- Compose UI primitives in domain components --}}
<x-auth.login-card>
  <x-ui.brand-logo />
  <x-ui.form-field label="شماره موبایل">
    <x-ui.input type="tel" />
  </x-ui.form-field>
</x-auth.login-card>

{{-- Respect reduced motion --}}
<button class="transition-all duration-300">
  Button
</button>
```

### ❌ Don't

```blade
{{-- Don't use arbitrary values --}}
<div class="p-[37px] rounded-[23px]">

{{-- Don't put domain logic in UI primitives --}}
<x-ui.button route="login">Login</x-ui.button>

{{-- Don't forget reduced motion fallbacks --}}
<div class="animate-bounce">
  No reduced motion support
</div>
```

## Testing Checklist

Before shipping any component:

- [ ] Uses design tokens (no arbitrary values)
- [ ] Tested on mobile, tablet, desktop
- [ ] RTL layout correct
- [ ] Keyboard accessible
- [ ] Focus indicators visible
- [ ] WCAG AA contrast met
- [ ] Respects `prefers-reduced-motion`
- [ ] PHPDoc block with usage example
- [ ] Matches design specification exactly

## Tools & Resources

### Design
- **Fonts:** [Google Fonts](https://fonts.google.com/) (Vazirmatn, Playfair Display)
- **Icons:** [Lucide Icons](https://lucide.dev/)
- **Colors:** Design tokens in `design-tokens.css`

### Development
- **CSS Framework:** TailwindCSS
- **JS Framework:** Alpine.js (lightweight reactivity)
- **Backend:** Laravel Blade

### Accessibility
- **Contrast Checker:** [WebAIM](https://webaim.org/resources/contrastchecker/)
- **Screen Reader:** NVDA (Windows), VoiceOver (Mac)
- **Keyboard Nav:** Test all interactive elements with Tab/Shift+Tab

## Updates & Maintenance

### Adding New Tokens

1. Document in relevant markdown file (`colors.md`, `spacing.md`, etc.)
2. Add to `design-tokens.css`
3. Update this README if new category
4. Test across all pages

### Modifying Existing Tokens

1. Update markdown documentation
2. Update `design-tokens.css`
3. Test all affected components
4. Document breaking changes

### Component Refactoring

When refactoring components:
1. Check if it's a UI primitive or domain component
2. UI primitives: must be generic, no domain logic
3. Domain components: should compose UI primitives
4. Add PHPDoc, usage examples, accessibility notes
5. Test keyboard navigation and screen reader support

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-07-13 | Initial design system documentation |

## Questions?

Refer to individual markdown files for detailed guidelines on each topic. If something is not documented, **ask before implementing** rather than making assumptions.

---

**Design System Status:** 🟢 Complete  
**Last Updated:** July 13, 2026  
**Maintained By:** Parsian Music Academy Development Team
