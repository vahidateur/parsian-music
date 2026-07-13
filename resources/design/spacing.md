# Spacing System - Parsian Music Academy

## 8px Base Unit

All spacing follows an **8px grid system** for visual consistency.

## Scale

| Token | Value | Usage |
|-------|-------|-------|
| `--space-1` | `8px` | Tight spacing (icon gaps) |
| `--space-2` | `16px` | Small spacing (form label → input) |
| `--space-3` | `24px` | Medium spacing (input gaps) |
| `--space-4` | `32px` | Large spacing (section gaps) |
| `--space-5` | `40px` | XL spacing (header → form) |
| `--space-6` | `48px` | XXL spacing (card padding) |
| `--space-8` | `64px` | Hero spacing |
| `--space-10` | `80px` | Layout spacing |
| `--space-12` | `96px` | Page section spacing |
| `--space-15` | `120px` | Major section dividers |

## Component Spacing

### Cards
- **Padding**: `var(--space-6)` (48px)
- **Section gaps**: `var(--space-4)` (32px)

### Forms
- **Input height**: `70px`
- **Input gaps**: `var(--space-3)` (24px)
- **Label → Input**: `var(--space-2)` (16px)
- **Form → Actions**: `var(--space-5)` (40px)

### Typography
- **Title → Subtitle**: `var(--space-2)` (16px)
- **Paragraph spacing**: `var(--space-3)` (24px)
- **Section headings**: `var(--space-5)` (40px)

### Buttons
- **Padding horizontal**: `var(--space-3)` (24px)
- **Icon → Text gap**: `var(--space-2)` (16px)

## Layout Grid

```
Login Card Internal Structure:
├── Padding: 48px (--space-6)
├── Header
├── Gap: 40px (--space-5)
├── Form
├── Gap: 32px (--space-4)
├── Actions
├── Gap: 32px (--space-4)
├── Social
├── Gap: 32px (--space-4)
└── Footer
```

## Responsive Adjustments

### Mobile (< 768px)
- Reduce `--space-6` → `--space-4` (card padding 48px → 32px)
- Reduce `--space-5` → `--space-4` (section gaps 40px → 32px)

### Tablet (768px - 1024px)
- Keep base spacing
- Adjust card width, not spacing

### Desktop (> 1024px)
- Use full spacing scale
- Maximum spacing: `--space-15` (120px)

## Usage Examples

```blade
{{-- Card padding --}}
<div class="[padding:var(--space-6)]">

{{-- Section gap --}}
<div class="[margin-bottom:var(--space-4)]">

{{-- Input gap --}}
<div class="[gap:var(--space-3)]">
```
