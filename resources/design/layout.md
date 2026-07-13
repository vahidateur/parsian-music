# Layout System - Parsian Music Academy

## Grid System

### 12-Column Grid
Standard responsive grid based on 12 columns for flexible layouts.

```
┌─────────────────────────────────────────┐
│ 1  │ 2  │ 3  │ 4  │ 5  │ 6  │ 7  │ 8  │ 9  │ 10 │ 11 │ 12 │
└─────────────────────────────────────────┘
```

### Column Spans

| Columns | Percentage | Usage |
|---------|-----------|--------|
| 12 | 100% | Full width |
| 8 | ~67% | Hero section (login page) |
| 6 | 50% | Two-column layouts |
| 4 | ~33% | Login card section, three-column |
| 3 | 25% | Four-column (dashboard widgets) |
| 2 | ~17% | Six-column (rare) |

### Tailwind Grid Classes

```blade
{{-- 12-column grid container --}}
<div class="grid grid-cols-12 gap-0">
  
  {{-- Hero: 8 columns (67%) --}}
  <section class="col-span-8">
    Hero
  </section>
  
  {{-- Login: 4 columns (33%) --}}
  <section class="col-span-4">
    Login Card
  </section>
  
</div>
```

## Breakpoints

### Responsive Scale

| Breakpoint | Min Width | Device | Grid Behavior |
|------------|-----------|--------|---------------|
| `xs` | `0px` | Mobile portrait | 1 column (full width) |
| `sm` | `640px` | Mobile landscape | 2-4 columns |
| `md` | `768px` | Tablet | 6-8 columns |
| `lg` | `1024px` | Desktop | 12 columns |
| `xl` | `1280px` | Large desktop | 12 columns |
| `2xl` | `1920px` | Reference size | 12 columns (design spec) |

### Tailwind Responsive Syntax

```blade
{{-- Mobile: full width, Desktop: 8 cols --}}
<div class="col-span-12 lg:col-span-8">
```

### Login Page Responsive Behavior

**Desktop (>= 1024px):**
```
┌──────────────────────┬─────────────┐
│                      │             │
│   Hero (8 cols)      │ Login (4)   │
│                      │             │
└──────────────────────┴─────────────┘
```

**Tablet (768px - 1023px):**
```
┌──────────────────────────────┐
│                              │
│   Hero (hidden or minimal)   │
│                              │
├──────────────────────────────┤
│                              │
│   Login (full width)         │
│                              │
└──────────────────────────────┘
```

**Mobile (< 768px):**
```
┌──────────────┐
│              │
│   Login      │
│  (centered)  │
│              │
└──────────────┘
```

## Container Widths

### Max-Width Constraints

| Token | Value | Usage |
|-------|-------|-------|
| `--container-xs` | `640px` | Forms, modals |
| `--container-sm` | `768px` | Content blocks |
| `--container-md` | `1024px` | Page sections |
| `--container-lg` | `1280px` | Wide layouts |
| `--container-xl` | `1536px` | Full-width sections |

### Centered Container

```blade
<div class="mx-auto max-w-screen-xl px-4">
  <!-- Content -->
</div>
```

## Spacing System

### Padding Scale

| Token | Value | Usage |
|-------|-------|-------|
| `--space-page-mobile` | `16px` | Page padding (mobile) |
| `--space-page-tablet` | `24px` | Page padding (tablet) |
| `--space-page-desktop` | `32px` | Page padding (desktop) |

### Gutter System

**Grid gaps:**
- **None**: `gap-0` - Login split screen (no gap)
- **Tight**: `gap-4` (16px) - Dense layouts
- **Normal**: `gap-6` (24px) - Default spacing
- **Loose**: `gap-8` (32px) - Spacious layouts

## Layout Patterns

### 1. Split Screen (Login Page)

```blade
<div class="min-h-screen w-full grid grid-cols-12" dir="ltr">
  
  {{-- Hero: Left side, 8 cols --}}
  <section class="col-span-8 hidden lg:block">
    <!-- Hero content -->
  </section>
  
  {{-- Login: Right side, 4 cols --}}
  <section class="col-span-12 lg:col-span-4">
    <div class="flex items-center justify-center min-h-screen">
      <!-- Login card -->
    </div>
  </section>
  
</div>
```

### 2. Centered Card (Simple pages)

```blade
<div class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <!-- Card content -->
  </div>
</div>
```

### 3. Dashboard Grid (Multi-widget)

```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <div>Widget 1</div>
  <div>Widget 2</div>
  <div>Widget 3</div>
</div>
```

### 4. Sidebar Layout (Admin panel)

```blade
<div class="grid grid-cols-12 min-h-screen">
  
  {{-- Sidebar: 2 cols --}}
  <aside class="col-span-2">
    <!-- Navigation -->
  </aside>
  
  {{-- Main content: 10 cols --}}
  <main class="col-span-10">
    <!-- Page content -->
  </main>
  
</div>
```

## Z-Index Scale

Layering system for overlapping elements.

| Layer | Z-Index | Usage |
|-------|---------|-------|
| Base | `0` | Default layer (page content) |
| Dropdown | `10` | Dropdowns, tooltips |
| Sticky | `20` | Sticky headers |
| Fixed | `30` | Fixed navigation |
| Modal Backdrop | `40` | Overlay backgrounds |
| Modal Content | `50` | Modals, dialogs |
| Popover | `60` | Popovers, alerts |
| Toast | `70` | Toast notifications |

```css
:root {
  --z-base: 0;
  --z-dropdown: 10;
  --z-sticky: 20;
  --z-fixed: 30;
  --z-modal-backdrop: 40;
  --z-modal: 50;
  --z-popover: 60;
  --z-toast: 70;
}
```

## Safe Areas

### Mobile Safe Zones

Account for notches, home indicators:

```blade
<div class="
  pt-[env(safe-area-inset-top)]
  pb-[env(safe-area-inset-bottom)]
  pl-[env(safe-area-inset-left)]
  pr-[env(safe-area-inset-right)]
">
  <!-- Content -->
</div>
```

### Keyboard Offset

On mobile, shift content when keyboard opens:

```blade
<div 
  x-data="{ keyboardOpen: false }"
  @focusin.window="keyboardOpen = true"
  @focusout.window="keyboardOpen = false"
  :class="{ 'pb-64': keyboardOpen }"
>
  <!-- Form content -->
</div>
```

## RTL Layout

### Physical vs Logical

**Physical directions** (left/right):
- Use for asymmetric layouts (split screen)
- Set `dir="ltr"` on container to preserve physical positioning

**Logical directions** (start/end):
- Use for content flow (text, icons, padding)
- Automatically flips with `dir="rtl"`

### Example: Login Page

```blade
{{-- Physical layout: left=hero, right=login --}}
<div dir="ltr" class="grid grid-cols-12">
  
  {{-- Hero always on LEFT (physical) --}}
  <section class="col-span-8">
    Hero
  </section>
  
  {{-- Login always on RIGHT (physical) --}}
  <section class="col-span-4" dir="rtl">
    {{-- Content flows RTL (logical) --}}
    <div class="pr-4"> <!-- Padding-right in RTL = padding-start --}}
      Login content
    </div>
  </section>
  
</div>
```

## Accessibility

### Focus Management

```blade
{{-- Skip to main content --}}
<a 
  href="#main-content" 
  class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100]"
>
  Skip to main content
</a>

<main id="main-content">
  <!-- Page content -->
</main>
```

### Landmark Roles

```blade
<header role="banner">Header</header>
<nav role="navigation">Nav</nav>
<main role="main">Main content</main>
<aside role="complementary">Sidebar</aside>
<footer role="contentinfo">Footer</footer>
```

## Responsive Images

### Hero Background

```blade
<div class="
  bg-cover bg-center
  lg:bg-[url('/images/hero-1920.jpg')]
  md:bg-[url('/images/hero-1024.jpg')]
  bg-[url('/images/hero-768.jpg')]
">
```

### Responsive Sizing

```blade
<img 
  src="logo.svg"
  alt="آموزشگاه موسیقی پارسیان"
  class="w-16 h-16 md:w-20 md:h-20 lg:w-24 lg:h-24"
>
```

## Design Tokens

```css
:root {
  /* Container widths */
  --container-xs: 640px;
  --container-sm: 768px;
  --container-md: 1024px;
  --container-lg: 1280px;
  --container-xl: 1536px;
  
  /* Page padding */
  --space-page-mobile: 16px;
  --space-page-tablet: 24px;
  --space-page-desktop: 32px;
  
  /* Z-index */
  --z-base: 0;
  --z-dropdown: 10;
  --z-sticky: 20;
  --z-fixed: 30;
  --z-modal-backdrop: 40;
  --z-modal: 50;
  --z-popover: 60;
  --z-toast: 70;
}
```

## Checklist

Before finalizing a layout:
- [ ] Tested on mobile (< 768px)
- [ ] Tested on tablet (768px - 1023px)
- [ ] Tested on desktop (>= 1024px)
- [ ] RTL flow correct (text, icons, padding)
- [ ] Physical layout preserved where needed (split screen)
- [ ] Focus order logical (tab navigation)
- [ ] Keyboard accessible (all interactive elements)
- [ ] Safe areas respected (mobile notches)
- [ ] Z-index conflicts resolved

