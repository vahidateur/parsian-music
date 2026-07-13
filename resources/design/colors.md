# Color System - Parsian Music Academy

## Brand Colors

### Gold Palette (Primary)
- `--gold-100`: `#F8E7B5` - Lightest gold, for highlights
- `--gold-200`: `#F4D28B` - Light gold, for gradients top
- `--gold-300`: `#D5AF58` - Primary gold, main brand color
- `--gold-400`: `#B98D36` - Dark gold, for depth

**Usage:**
- Buttons, links, focus states
- Brand elements (logo, headings)
- Interactive elements hover states

### Neutral Palette

#### Dark Backgrounds
- `--neutral-950`: `#0E1018` - Main page background
- `--neutral-900`: `#11131B` - Hero/section backgrounds
- `--neutral-850`: `#1C2230` - Panel backgrounds

#### Text Colors
- `--text-primary`: `#FFFFFF` - Primary text
- `--text-secondary`: `#CFC7B2` - Muted text
- `--text-tertiary`: `rgba(255,255,255,0.70)` - Disabled/placeholder

### Glass Effect Colors
- `--glass-bg`: `rgba(10,12,18,0.42)` - Glassmorphism background
- `--glass-border`: `rgba(213,175,88,0.18)` - Golden border with transparency
- `--glass-overlay`: `rgba(0,0,0,0.45)` - Dark overlay for modals

### Semantic Colors

#### Success
- `--success-500`: `#10B981` - Success state
- `--success-bg`: `rgba(16,185,129,0.10)` - Success background

#### Error
- `--error-500`: `#EF4444` - Error state
- `--error-bg`: `rgba(239,68,68,0.10)` - Error background

#### Warning
- `--warning-500`: `#F59E0B` - Warning state
- `--warning-bg`: `rgba(245,158,11,0.10)` - Warning background

#### Info
- `--info-500`: `#3B82F6` - Info state
- `--info-bg`: `rgba(59,130,246,0.10)` - Info background

## Accessibility

All color combinations must meet **WCAG AA** contrast ratios:
- Normal text: 4.5:1 minimum
- Large text: 3:1 minimum
- Interactive elements: Must have visible focus states

## Usage in Components

```blade
{{-- Button primary --}}
<button class="bg-gradient-to-b from-[var(--gold-200)] to-[var(--gold-300)]">

{{-- Text muted --}}
<p class="text-[var(--text-secondary)]">

{{-- Glass card --}}
<div class="[background:var(--glass-bg)] [border-color:var(--glass-border)]">
```
