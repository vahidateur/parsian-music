# Typography System - Parsian Music Academy

## Fonts

### Persian Text
- **Font Family**: Vazirmatn
- **Weights**: 300 (Light), 400 (Regular), 500 (Medium), 600 (Semi-Bold), 700 (Bold), 800 (Extra-Bold)
- **Source**: Google Fonts

### English Text
- **Font Family**: Playfair Display (decorative, for brand)
- **Weights**: 400 (Regular), 500 (Medium), 600 (Semi-Bold), 700 (Bold)
- **Source**: Google Fonts
- **Usage**: Brand name, decorative English elements only

## Type Scale

| Token | Size | Line Height | Weight | Usage |
|-------|------|-------------|--------|-------|
| `--text-xs` | `12px` | `1.5` | 400 | Captions, metadata |
| `--text-sm` | `13px` | `1.5` | 400 | Helper text, labels |
| `--text-base` | `15px` | `1.6` | 400 | Body text, inputs |
| `--text-md` | `16px` | `1.6` | 600 | Buttons, emphasized text |
| `--text-lg` | `18px` | `1.5` | 600 | Section headings |
| `--text-xl` | `22px` | `1.4` | 700 | Page headings |
| `--text-2xl` | `26px` | `1.3` | 700 | Card titles (login title) |
| `--text-3xl` | `32px` | `1.2` | 700 | Page titles |
| `--text-4xl` | `40px` | `1.1` | 700 | Hero titles |

## Component Typography

### Login Card
- **Title**: `26px` / `700` / `var(--gold-200)` - "آموزشگاه موسیقی پارسیان"
- **Subtitle**: `15px` / `400` / `var(--text-secondary)` - "تالار هنر، جادو و موسیقی"
- **English**: `13px` / `600` / `var(--gold-300)` / `3px letter-spacing` / `uppercase`

### Form Elements
- **Input**: `15px` / `400` / `var(--text-primary)`
- **Placeholder**: `15px` / `400` / `rgba(255,255,255,0.55)`
- **Label**: `14px` / `500` / `var(--text-secondary)`
- **Error**: `13px` / `400` / `var(--error-500)`
- **Helper**: `13px` / `400` / `var(--text-tertiary)`

### Buttons
- **Primary button**: `16px` / `700` / `#14100a` (dark text on gold)
- **Secondary button**: `16px` / `600` / `var(--text-primary)`

### Quotes / Italics
- **Quote text**: `14px` / `400` / `italic` / `var(--gold-300)/80`

## Responsive Typography

### Mobile (< 768px)
```
--text-2xl: 22px  (reduced from 26px)
--text-3xl: 28px  (reduced from 32px)
--text-4xl: 32px  (reduced from 40px)
```

### Tablet (768px - 1024px)
- Keep base scale
- Adjust line-height for longer lines

### Desktop (> 1024px)
- Full type scale
- Can increase line-height for readability

## RTL Considerations

- **Letter spacing**: Use sparingly in Persian (only for English/Latin)
- **Line height**: Persian needs `1.6-1.8` (more than Latin)
- **Word spacing**: Slight increase (`0.05em`) for Persian readability

## Accessibility

- **Minimum size**: `13px` for body text
- **Contrast**: All text meets WCAG AA (4.5:1 for normal, 3:1 for large)
- **Focus states**: Visible on all interactive typography

## Usage Examples

```blade
{{-- Login title --}}
<h1 class="[font-size:var(--text-2xl)] font-bold text-[var(--gold-200)]">

{{-- Body text --}}
<p class="[font-size:var(--text-base)] text-[var(--text-primary)]">

{{-- English brand --}}
<p class="font-playfair [font-size:var(--text-sm)] uppercase tracking-[3px] text-[var(--gold-300)]">
```

## Font Loading

```html
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
```
