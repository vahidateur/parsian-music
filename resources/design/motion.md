# Motion & Animation System - Parsian Music Academy

## Design Philosophy

Motion should be **purposeful, elegant, and respectful** of user preferences. Every animation must have a reason and an accessible alternative.

## Timing Functions

### Easing Curves

| Name | Value | Usage |
|------|-------|-------|
| `--ease-standard` | `cubic-bezier(0.22, 1, 0.36, 1)` | Default smooth motion |
| `--ease-enter` | `cubic-bezier(0, 0, 0.2, 1)` | Elements entering viewport |
| `--ease-exit` | `cubic-bezier(0.4, 0, 1, 1)` | Elements leaving viewport |
| `--ease-bounce` | `cubic-bezier(0.68, -0.55, 0.265, 1.55)` | Playful interactions |

### Duration Scale

| Token | Value | Usage |
|-------|-------|-------|
| `--duration-instant` | `100ms` | Micro-interactions (hover) |
| `--duration-fast` | `200ms` | UI feedback (clicks) |
| `--duration-normal` | `300ms` | Default transitions |
| `--duration-slow` | `500ms` | Complex animations |
| `--duration-slower` | `800ms` | Page transitions |

## Animation Types

### 1. Fade Animations

**Fade In**
```css
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.fade-in {
  animation: fadeIn var(--duration-normal) var(--ease-enter);
}
```

**Fade In Up** (Login card entrance)
```css
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in-up {
  animation: fadeInUp var(--duration-slow) var(--ease-standard);
}
```

### 2. Hover Animations

**Button Lift**
```css
.button-lift {
  transition: 
    transform var(--duration-fast) var(--ease-standard),
    box-shadow var(--duration-fast) var(--ease-standard);
}

.button-lift:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-button-hover);
}

.button-lift:active {
  transform: translateY(1px);
}
```

**Glow Expand**
```css
.glow-expand {
  transition: box-shadow var(--duration-normal) var(--ease-standard);
}

.glow-expand:hover {
  box-shadow: 0 0 20px rgba(213, 175, 88, 0.5);
}
```

### 3. Focus Animations

**Focus Ring** (Inputs)
```css
.focus-ring {
  transition: 
    border-color var(--duration-fast) var(--ease-standard),
    box-shadow var(--duration-fast) var(--ease-standard);
}

.focus-ring:focus {
  border-color: var(--gold-300);
  box-shadow: var(--shadow-input-focus);
  outline: none;
}
```

### 4. Loading Animations

**Spinner**
```css
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.spinner {
  animation: spin 1s linear infinite;
}
```

**Pulse** (Skeleton loaders)
```css
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.pulse {
  animation: pulse 2s var(--ease-standard) infinite;
}
```

### 5. Stagger Animations

For lists of items (e.g., dashboard cards):

```css
.stagger-item {
  opacity: 0;
  animation: fadeInUp var(--duration-normal) var(--ease-enter) forwards;
}

.stagger-item:nth-child(1) { animation-delay: 0ms; }
.stagger-item:nth-child(2) { animation-delay: 100ms; }
.stagger-item:nth-child(3) { animation-delay: 200ms; }
.stagger-item:nth-child(4) { animation-delay: 300ms; }
```

## Accessibility: Reduced Motion

### Critical Rule
**ALWAYS respect `prefers-reduced-motion`**. This is not optional.

### Implementation

```css
/* Default: animations enabled */
.animated-element {
  animation: fadeInUp 500ms ease-out;
  transition: all 300ms ease;
}

/* Reduced motion: disable animations */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

### What to Disable

In `prefers-reduced-motion`:
- ✅ Disable: entrance animations, continuous animations, parallax
- ✅ Keep: instant feedback (hover state changes), focus indicators
- ✅ Reduce: long transitions to `< 100ms`

### Testing

Test with browser DevTools:
- **Chrome**: DevTools → Rendering → Emulate CSS media `prefers-reduced-motion`
- **Firefox**: Settings → Privacy → Motion → Reduce motion
- **Safari**: System Preferences → Accessibility → Display → Reduce motion

## Component Animations

### Login Card
- **Entrance**: Fade in + slide up (`500ms`)
- **Delay**: `200ms` after page load
- **Easing**: `ease-standard`

### Buttons
- **Hover**: Lift + shadow (`200ms`)
- **Active**: Press down (`100ms`)
- **Focus**: Ring expand (`200ms`)

### Inputs
- **Focus**: Border color + shadow (`200ms`)
- **Error shake**: Horizontal shake (`300ms`)
- **Success check**: Scale + fade (`400ms`)

### Modals
- **Enter**: Backdrop fade + content scale (`300ms`)
- **Exit**: Reverse animation (`200ms`)

### Toasts
- **Enter**: Slide from right + fade (`300ms`)
- **Exit**: Slide to right + fade (`200ms`)
- **Auto-dismiss**: After `5000ms`

## Performance

### GPU Acceleration
Animate only `transform` and `opacity` for 60fps:

```css
/* ✅ Good: GPU-accelerated */
.good {
  transform: translateY(10px);
  opacity: 0.5;
}

/* ❌ Bad: CPU-bound, repaints */
.bad {
  top: 10px;
  background: red;
}
```

### Will-Change
Use sparingly for complex animations:

```css
.complex-animation {
  will-change: transform, opacity;
}

/* Remove after animation completes */
.complex-animation.done {
  will-change: auto;
}
```

## Design Tokens

```css
:root {
  /* Durations */
  --duration-instant: 100ms;
  --duration-fast: 200ms;
  --duration-normal: 300ms;
  --duration-slow: 500ms;
  --duration-slower: 800ms;
  
  /* Easing */
  --ease-standard: cubic-bezier(0.22, 1, 0.36, 1);
  --ease-enter: cubic-bezier(0, 0, 0.2, 1);
  --ease-exit: cubic-bezier(0.4, 0, 1, 1);
  --ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
}
```

## Usage Examples

### Button Component
```blade
<button class="
  transition-all
  [transition-duration:var(--duration-fast)]
  [transition-timing-function:var(--ease-standard)]
  hover:-translate-y-0.5
  hover:[box-shadow:var(--shadow-button-hover)]
  active:translate-y-0.5
">
  Button
</button>
```

### Login Card Entrance
```blade
<div 
  x-data="{ show: false }"
  x-init="setTimeout(() => show = true, 200)"
  x-show="show"
  x-transition:enter="transition ease-out duration-500"
  x-transition:enter-start="opacity-0 translate-y-10"
  x-transition:enter-end="opacity-100 translate-y-0"
>
  <!-- Card content -->
</div>
```

### Input Focus
```blade
<input class="
  transition-all
  [transition-duration:var(--duration-fast)]
  border-[var(--glass-border-light)]
  focus:border-[var(--gold-300)]
  focus:[box-shadow:var(--shadow-input-focus)]
  focus:outline-none
">
```

## Checklist

Before shipping any animation:
- [ ] Has a clear purpose (not decorative)
- [ ] Duration < 500ms (unless page transition)
- [ ] Respects `prefers-reduced-motion`
- [ ] Uses `transform` or `opacity` (not layout properties)
- [ ] Tested on low-end devices
- [ ] Focus states remain visible during animation
- [ ] Doesn't block user interaction

