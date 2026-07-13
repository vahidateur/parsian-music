# Shadow System - Parsian Music Academy

## Elevation Levels

Shadows create depth hierarchy in the glassmorphism design system.

| Level | Token | Value | Usage |
|-------|-------|-------|-------|
| 0 | `--shadow-none` | `none` | Flat elements |
| 1 | `--shadow-sm` | `0 2px 8px rgba(0,0,0,0.15)` | Subtle hover |
| 2 | `--shadow-md` | `0 10px 30px rgba(0,0,0,0.25)` | Dropdowns, popovers |
| 3 | `--shadow-lg` | `0 20px 60px rgba(0,0,0,0.35)` | Panels, widgets |
| 4 | `--shadow-xl` | `0 40px 120px rgba(0,0,0,0.45)` | Cards, modals (main) |

## Glow Shadows (Golden)

Used for interactive elements to create magical glow effect.

| Type | Token | Value | Usage |
|------|-------|-------|-------|
| Button default | `--shadow-button` | `0 10px 30px rgba(213,175,88,0.35)` | Primary button |
| Button hover | `--shadow-button-hover` | `0 14px 40px rgba(213,175,88,0.5)` | Button hover state |
| Input focus | `--shadow-input-focus` | `0 0 0 4px rgba(213,175,88,0.12)` | Focus ring |
| Social hover | `--shadow-social-hover` | `0 0 20px rgba(213,175,88,0.3)` | Social button glow |

## Glass Card Shadows

Combined shadow for glassmorphism depth.

```css
--glass-shadow: 
  0 40px 120px rgba(0,0,0,0.45),     /* Outer depth shadow */
  inset 0 1px 0 rgba(255,255,255,0.06); /* Inner highlight */
```

## Component Shadows

### Cards
- **Login card**: `var(--shadow-xl)` + inner glow
- **Dashboard widget**: `var(--shadow-lg)`
- **Panel**: `var(--shadow-md)`

### Interactive Elements
- **Button default**: `var(--shadow-button)`
- **Button hover**: `var(--shadow-button-hover)` + `translateY(-2px)`
- **Button active**: `var(--shadow-button)` (reduced) + `translateY(1px)`

### Form Elements
- **Input default**: `none`
- **Input focus**: `var(--shadow-input-focus)`

### Overlays
- **Modal backdrop**: `0 0 0 9999px rgba(0,0,0,0.6)`
- **Dropdown**: `var(--shadow-lg)`
- **Toast**: `var(--shadow-md)`

## Animation

Shadows should animate smoothly:

```css
transition: box-shadow 300ms cubic-bezier(0.22, 1, 0.36, 1);
```

## Accessibility

- Shadows are decorative only
- Never rely on shadows for functionality
- Ensure sufficient color contrast independent of shadows

## Reduced Motion

Respect `prefers-reduced-motion`:

```css
@media (prefers-reduced-motion: reduce) {
  * {
    box-shadow: none !important; /* Or static shadow */
  }
}
```

## Usage Examples

```blade
{{-- Login card --}}
<div class="[box-shadow:var(--glass-shadow)]">

{{-- Button --}}
<button class="[box-shadow:var(--shadow-button)] 
               hover:[box-shadow:var(--shadow-button-hover)]">

{{-- Input focus --}}
<input class="focus:[box-shadow:var(--shadow-input-focus)]">
```

## Dark Mode Considerations

In a future dark mode implementation:
- Increase shadow opacity by 10-15%
- Glow shadows can remain the same (they're golden, not tied to mode)
