# Component Architecture - Parsian Music Academy

## Overview

The Parsian Music Academy design system follows a **composition-based architecture** where domain-specific components are built by composing generic UI primitives. This ensures maximum reusability and maintainability across the entire application.

## Architecture Principles

### 1. Separation of Concerns

```
UI Primitives (Generic)
    ↓ composed by
Domain Components (Specific)
    ↓ composed by
Page Views (Complete)
```

### 2. Composition Over Inheritance

Domain components **ONLY compose** UI primitives. They do NOT contain custom styling or duplicate logic.

### 3. Single Responsibility

Each component has ONE job:
- **UI Primitives**: Visual presentation (buttons, inputs, cards)
- **Domain Components**: Business logic arrangement (login form, dashboard widget)
- **Page Views**: Complete page assembly

## Component Hierarchy

### Level 1: UI Primitives (`components/ui/`)

**Purpose:** Generic, reusable visual components with NO domain logic.

**Rules:**
- ✅ Must work in ANY context (login, dashboard, settings, etc.)
- ✅ Props are ONLY for visual customization (size, color, variant)
- ❌ NO domain-specific props (e.g., NO `loginUrl`, `userType`, `dashboardMode`)
- ❌ NO business logic (e.g., NO authentication checks, role validation)

**Categories:**

#### Glass Components (`ui/glass/`)
- `card.blade.php` - Glassmorphism card shell
- `panel.blade.php` - Lighter glass panel
- `section.blade.php` - Glass section wrapper

#### Form Components (`ui/`)
- `input.blade.php` - Text input primitive
- `form-field.blade.php` - Field wrapper (label + input + validation + hint)
- `checkbox.blade.php` - Checkbox with label
- `button.blade.php` - Button with variants (primary, secondary, ghost, danger, success)

#### Brand Components (`ui/`)
- `brand-logo.blade.php` - Academy logo
- `brand-title.blade.php` - Persian title "آموزشگاه موسیقی پارسیان"
- `brand-subtitle.blade.php` - Persian subtitle "تالار هنر، جادو و موسیقی"
- `brand-english.blade.php` - English name "PARSIAN MUSIC ACADEMY"

#### Utility Components (`ui/`)
- `divider.blade.php` - Section divider
- `icon.blade.php` - Lucide icon wrapper (TODO)

### Level 2: Domain Components (`components/auth/`, `components/dashboard/`, etc.)

**Purpose:** Domain-specific components that compose UI primitives.

**Rules:**
- ✅ Composes ONLY UI primitives from `components/ui/`
- ✅ Can contain domain logic (routes, authentication, data)
- ✅ Can accept domain-specific props
- ❌ NO custom styling (use UI primitives for all visuals)
- ❌ NO duplicate UI code (reuse primitives)

**Auth Domain (`components/auth/`):**
- `login-card.blade.php` - Complete login card (composes all login sections)
- `login-header.blade.php` - Logo + title + subtitle (composes brand components)
- `login-form.blade.php` - Phone + password fields (composes form-field + input)
- `login-social.blade.php` - Social login buttons (TODO)
- `login-footer.blade.php` - Footer quote (TODO)

**Dashboard Domain (`components/dashboard/`):**
- Existing: `kpi-card`, `stat-card`, `activity-timeline-item`, etc.
- TODO: Refactor to compose `ui/` primitives instead of custom styling

### Level 3: Page Views (`resources/views/`)

**Purpose:** Complete pages that compose domain components.

**Rules:**
- ✅ Composes domain components and UI primitives
- ✅ Contains page-level layout and structure
- ✅ Handles data passing to components
- ❌ NO duplicate component logic
- ❌ Minimal custom styling (use components)

## Component Communication

### Props Flow (Top-Down)

```blade
{{-- Page View --}}
<x-auth.login-card />

{{-- Domain Component --}}
<x-auth.login-form :action="route('login')" />

{{-- UI Primitive --}}
<x-ui.input 
    name="phone" 
    type="tel" 
    :hasError="$errors->has('phone')" 
/>
```

### Slots (Content Projection)

```blade
{{-- Wrapper with slot --}}
<x-ui.form-field name="phone" label="شماره موبایل">
    
    {{-- Default slot --}}
    <x-ui.input name="phone" type="tel" />
    
    {{-- Named slot --}}
    <x-slot:icon>
        <i data-lucide="phone"></i>
    </x-slot:icon>
    
</x-ui.form-field>
```

## Example: Login Flow

### ❌ WRONG (Old Approach)

```blade
{{-- components/auth/login-form.blade.php --}}
<form>
    {{-- Custom input styling (duplicates UI primitive) --}}
    <input class="w-full h-[70px] rounded-[18px] bg-white/[0.06] ...">
    
    {{-- Login-specific button (not reusable) --}}
    <button class="bg-gradient-to-b from-gold-200 to-gold-300">
        ورود
    </button>
</form>
```

### ✅ CORRECT (New Approach)

```blade
{{-- components/auth/login-form.blade.php --}}
<form>
    {{-- Compose generic form-field --}}
    <x-ui.form-field name="phone" label="شماره موبایل">
        {{-- Compose generic input --}}
        <x-ui.input name="phone" type="tel" />
        
        {{-- Compose generic icon --}}
        <x-slot:icon>
            <i data-lucide="phone"></i>
        </x-slot:icon>
    </x-ui.form-field>
    
    {{-- Compose generic button --}}
    <x-ui.button variant="primary" type="submit" :fullWidth="true">
        ورود
    </x-ui.button>
</form>
```

## File Organization

```
resources/
├── views/
│   ├── components/
│   │   ├── ui/                     # UI Primitives (Generic)
│   │   │   ├── glass/
│   │   │   │   ├── card.blade.php
│   │   │   │   ├── panel.blade.php
│   │   │   │   └── section.blade.php
│   │   │   ├── brand-logo.blade.php
│   │   │   ├── brand-title.blade.php
│   │   │   ├── brand-subtitle.blade.php
│   │   │   ├── brand-english.blade.php
│   │   │   ├── input.blade.php
│   │   │   ├── form-field.blade.php
│   │   │   ├── checkbox.blade.php
│   │   │   ├── button.blade.php
│   │   │   └── divider.blade.php
│   │   │
│   │   ├── auth/                   # Auth Domain
│   │   │   ├── login-card.blade.php
│   │   │   ├── login-header.blade.php
│   │   │   ├── login-form.blade.php
│   │   │   ├── login-social.blade.php
│   │   │   └── login-footer.blade.php
│   │   │
│   │   ├── dashboard/              # Dashboard Domain
│   │   │   ├── kpi-card.blade.php
│   │   │   ├── stat-card.blade.php
│   │   │   └── ...
│   │   │
│   │   └── settings/               # Settings Domain
│   │       └── ...
│   │
│   ├── auth/                       # Auth Pages
│   │   └── login.blade.php
│   │
│   └── dashboard/                  # Dashboard Pages
│       └── index.blade.php
│
├── css/
│   └── design-tokens.css           # Design System Tokens
│
└── design/                         # Design Documentation
    ├── README.md
    ├── ARCHITECTURE.md             # This file
    ├── colors.md
    ├── spacing.md
    ├── radius.md
    ├── typography.md
    ├── shadows.md
    ├── glass.md
    ├── motion.md
    ├── icons.md
    └── layout.md
```

## Component Documentation Standard

Every component must include:

### 1. PHPDoc Block

```blade
{{--
/**
 * Component Name
 * 
 * Brief description of what the component does.
 * State whether it's generic (UI primitive) or domain-specific.
 * 
 * @props
 * - prop1: Description (default: value)
 * - prop2: Description (required)
 * 
 * @slots
 * - default: Description
 * - namedSlot: Description
 * 
 * @example
 * <x-component :prop1="value">
 *     Content
 * </x-component>
 * 
 * @accessibility
 * - Accessibility consideration 1
 * - Accessibility consideration 2
 */
--}}
```

### 2. Props Definition

```blade
@props([
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
])
```

### 3. Usage Examples

Include at least 2-3 real-world usage examples in the PHPDoc.

## Refactoring Guidelines

When refactoring existing components:

### Step 1: Identify Component Type

- **UI Primitive?** → Move to `components/ui/`, remove domain logic
- **Domain Component?** → Keep in domain folder, refactor to compose primitives

### Step 2: Extract Repeated Patterns

If you see repeated styling (inputs, buttons, cards), create a UI primitive.

### Step 3: Remove Duplication

Replace custom styling with composed primitives:

```blade
{{-- Before --}}
<input class="w-full h-[70px] rounded-[18px] ...">

{{-- After --}}
<x-ui.input name="field" />
```

### Step 4: Test Reusability

Can this component be used in Login, Dashboard, Settings?
- **Yes** → It's a UI primitive
- **No** → It's a domain component (but should still compose primitives)

## Testing Checklist

Before shipping a component:

- [ ] PHPDoc block complete (description, props, example, accessibility)
- [ ] Props documented and have sensible defaults
- [ ] Component is properly categorized (UI primitive vs domain)
- [ ] UI primitives have NO domain logic
- [ ] Domain components compose ONLY primitives
- [ ] Works across all target pages (if UI primitive)
- [ ] Follows design tokens (no arbitrary values)
- [ ] Accessible (keyboard nav, screen reader, focus indicators)
- [ ] Respects `prefers-reduced-motion`

## Next Steps

### Completed ✅
- [x] Design system documentation (colors, spacing, typography, etc.)
- [x] Design tokens CSS refactored
- [x] Generic UI primitives created:
  - [x] Brand components (logo, title, subtitle, english)
  - [x] Form components (input, form-field, checkbox, button)
  - [x] Glass card refactored to be generic
  - [x] Divider component
- [x] Auth domain components refactored:
  - [x] login-header (composes brand components)
  - [x] login-form (composes form components)
  - [x] login-card (composes all auth sections)

### TODO ⏳
- [ ] Icon component wrapper (`ui/icon.blade.php`)
- [ ] Social login section (`auth/login-social.blade.php`)
- [ ] Footer quote section (`auth/login-footer.blade.php`)
- [ ] Refactor existing dashboard components to compose primitives
- [ ] Create register page using existing primitives
- [ ] Create forgot password page using existing primitives
- [ ] Update login.blade.php to use new architecture

---

**Last Updated:** July 13, 2026  
**Status:** 🟡 In Progress (Architecture refactoring complete, implementation ongoing)
