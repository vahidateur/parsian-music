# Border Radius System - Parsian Music Academy

## Scale

| Token | Value | Usage |
|-------|-------|-------|
| `--radius-xs` | `8px` | Small elements (badges, tags) |
| `--radius-sm` | `12px` | Medium elements (chips, small cards) |
| `--radius-md` | `18px` | Inputs, buttons |
| `--radius-lg` | `28px` | Large cards, modals |
| `--radius-xl` | `40px` | Hero sections |
| `--radius-full` | `50%` | Circular elements (avatars, social buttons) |

## Component Mapping

### Cards
- **Login card**: `var(--radius-lg)` (28px)
- **Dashboard widget**: `var(--radius-md)` (18px)
- **Panel**: `var(--radius-sm)` (12px)

### Form Elements
- **Input fields**: `var(--radius-md)` (18px)
- **Buttons**: `var(--radius-md)` (18px)
- **Checkboxes**: `var(--radius-xs)` (8px)
- **Select dropdowns**: `var(--radius-md)` (18px)

### Interactive Elements
- **Social login buttons**: `var(--radius-full)` (50%)
- **Avatar**: `var(--radius-full)` (50%)
- **Badge**: `var(--radius-xs)` (8px)

### Containers
- **Modal**: `var(--radius-lg)` (28px)
- **Dropdown menu**: `var(--radius-sm)` (12px)
- **Toast notification**: `var(--radius-md)` (18px)

## Design Principles

1. **Larger elements = larger radius**
   - Cards and modals use `--radius-lg`
   - Inputs and buttons use `--radius-md`
   - Small chips use `--radius-sm`

2. **Functional elements = moderate radius**
   - Inputs/buttons: `18px` for friendly but not toy-like

3. **Decorative elements = flexible**
   - Can use `--radius-full` for pure circles
   - Can use `--radius-xl` for dramatic effect

## Accessibility

- Ensure corners don't cut off focus rings
- Minimum `4px` spacing between border and focus outline

## Usage Examples

```blade
{{-- Login card --}}
<div class="[border-radius:var(--radius-lg)]">

{{-- Input field --}}
<input class="[border-radius:var(--radius-md)]">

{{-- Social button --}}
<button class="[border-radius:var(--radius-full)]">
```
