# Glass Effect System - Parsian Music Academy

## Overview

The glassmorphism design creates depth and elegance through layered transparency, blur, and subtle borders.

## Glass Properties

### Background
```css
--glass-bg: rgba(10, 12, 18, 0.42)
```
- **Base color**: Dark neutral `#0A0C12`
- **Opacity**: `42%` for subtle transparency
- **Purpose**: Creates frosted glass appearance while maintaining readability

### Backdrop Blur
```css
--glass-blur: 32px
backdrop-filter: blur(var(--glass-blur))
-webkit-backdrop-filter: blur(var(--glass-blur))
```
- **Value**: `32px` for strong blur effect
- **Browser support**: Requires `-webkit-` prefix for Safari
- **Fallback**: Background remains visible without blur

### Border
```css
--glass-border: rgba(213, 175, 88, 0.18)
border: 1px solid var(--glass-border)
```
- **Color**: Golden `#D5AF58`
- **Opacity**: `18%` for subtle glow
- **Width**: `1px` consistent across all glass elements

### Shadows

**Outer shadow** (depth):
```css
--glass-shadow-outer: 0 40px 120px rgba(0, 0, 0, 0.45)
```

**Inner glow** (highlight):
```css
--glass-shadow-inner: inset 0 1px 0 rgba(255, 255, 255, 0.06)
```

**Combined**:
```css
--glass-shadow: 
  0 40px 120px rgba(0, 0, 0, 0.45),
  inset 0 1px 0 rgba(255, 255, 255, 0.06);
```

## Glass Component Hierarchy

### Level 1: Cards & Modals
- **Full glass effect**: Background, blur, border, shadows
- **Examples**: Login card, dashboard panels, modal dialogs
- **Z-index**: `10`

### Level 2: Panels & Sections
- **Reduced glass**: Lighter background, less blur
- **Examples**: Form sections, content panels
- **Z-index**: `5`

### Level 3: Inputs
- **Minimal glass**: Border only, no blur
- **Examples**: Text inputs, textareas, select dropdowns
- **Z-index**: `1`

## Usage Patterns

### Full Glass Card
```blade
<div class="
  [background:var(--glass-bg)]
  [backdrop-filter:blur(var(--glass-blur))]
  [-webkit-backdrop-filter:blur(var(--glass-blur))]
  border
  [border-color:var(--glass-border)]
  [box-shadow:var(--glass-shadow)]
  [border-radius:var(--radius-lg)]
  overflow-hidden
">
  <!-- content -->
</div>
```

### Glass Panel (lighter)
```blade
<div class="
  [background:rgba(10,12,18,0.25)]
  [backdrop-filter:blur(16px)]
  [-webkit-backdrop-filter:blur(16px)]
  border
  [border-color:var(--glass-border)]
  [border-radius:var(--radius-md)]
">
  <!-- content -->
</div>
```

### Glass Input
```blade
<input class="
  bg-transparent
  border
  [border-color:rgba(213,175,88,0.12)]
  [border-radius:var(--radius-md)]
  focus:[border-color:var(--glass-border)]
  focus:[box-shadow:var(--shadow-input-focus)]
">
```

## Best Practices

### 1. Layer Management
- Glass elements need elements **behind** them to blur
- Place glass cards over images, gradients, or patterns
- Don't stack multiple glass layers (causes performance issues)

### 2. Contrast
- Ensure text remains readable on glass backgrounds
- Minimum contrast: `4.5:1` for body text
- Use darker glass (`opacity: 0.6`) if content underneath is bright

### 3. Performance
- Blur is GPU-intensive
- Limit to `< 5` glass elements per viewport
- Disable blur in `prefers-reduced-motion`

### 4. Browser Support
- Always include `-webkit-backdrop-filter` for Safari
- Provide fallback background without blur:
  ```css
  background: rgba(10, 12, 18, 0.42); /* Fallback */
  backdrop-filter: blur(32px);
  -webkit-backdrop-filter: blur(32px);
  ```

## Accessibility

### Reduced Motion
Users with `prefers-reduced-motion` should see static glass without blur:

```css
@media (prefers-reduced-motion: reduce) {
  * {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    background: rgba(10, 12, 18, 0.85) !important; /* Increase opacity */
  }
}
```

### Contrast
- Test glass elements with [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- Gold border must remain visible against all backgrounds
- White text must maintain `4.5:1` contrast on glass

## Responsive Behavior

### Mobile (< 768px)
- Reduce blur: `blur(24px)` instead of `32px`
- Increase background opacity: `0.6` instead of `0.42`
- Simplify shadows to improve performance

### Tablet (768px - 1024px)
- Keep base glass effect
- May reduce blur to `28px` on lower-end devices

### Desktop (> 1024px)
- Full glass effect
- Maximum blur: `32px`

## Design Tokens

```css
:root {
  /* Glass backgrounds */
  --glass-bg: rgba(10, 12, 18, 0.42);
  --glass-bg-panel: rgba(10, 12, 18, 0.25);
  
  /* Glass blur */
  --glass-blur: 32px;
  --glass-blur-light: 16px;
  
  /* Glass borders */
  --glass-border: rgba(213, 175, 88, 0.18);
  --glass-border-light: rgba(213, 175, 88, 0.12);
  
  /* Glass shadows */
  --glass-shadow: 0 40px 120px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.06);
  --glass-shadow-panel: 0 20px 60px rgba(0, 0, 0, 0.35);
}
```

## Examples

### Login Card
Primary glass card with full effect.

### Dashboard Panel
Secondary glass panel with lighter background.

### Modal Overlay
Dark glass overlay with heavy blur for focus.

