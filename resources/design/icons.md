# Icon System - Parsian Music Academy

## Icon Library

**Primary:** [Lucide Icons](https://lucide.dev/)

### Why Lucide?
- Open source, MIT license
- Consistent 24×24 grid
- Perfect for glassmorphism (clean, minimal strokes)
- RTL-friendly (most icons are symmetric)
- Framework-agnostic (SVG)

## Installation

### Via CDN (Current)
```html
<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();
</script>
```

### Via NPM (Recommended for production)
```bash
npm install lucide
```

```js
import { Phone, Lock, Eye, EyeOff, LogIn } from 'lucide';
```

## Icon Sizes

| Token | Value | Usage |
|-------|-------|-------|
| `--icon-xs` | `16px` | Inline text icons |
| `--icon-sm` | `20px` | Input prefixes, badges |
| `--icon-md` | `24px` | Default size (buttons, links) |
| `--icon-lg` | `32px` | Section headings |
| `--icon-xl` | `48px` | Feature icons |

## Icon Categories

### 1. Form Icons
Used in login, registration, forms.

| Icon | Name | Usage |
|------|------|-------|
| 📱 | `phone` | Phone number input |
| 🔒 | `lock` | Password input |
| 👁️ | `eye` | Show password |
| 👁️‍🗨️ | `eye-off` | Hide password |
| ✉️ | `mail` | Email input |
| 👤 | `user` | Username input |

### 2. Action Icons
Used for buttons, CTAs.

| Icon | Name | Usage |
|------|------|-------|
| ➡️ | `log-in` | Login button |
| ⬅️ | `log-out` | Logout button |
| 🔄 | `refresh-cw` | Retry action |
| ✓ | `check` | Success feedback |
| ✕ | `x` | Close modal, dismiss |

### 3. Social Icons
Used for third-party login.

| Icon | Name | Usage |
|------|------|-------|
|  | `google` | Google login (custom SVG) |
|  | `apple` | Apple login (custom SVG) |

**Note:** Lucide doesn't include brand icons. Use custom SVGs for social login.

### 4. Navigation Icons
Used for menus, dashboards.

| Icon | Name | Usage |
|------|------|-------|
| 🏠 | `home` | Dashboard home |
| 👥 | `users` | Students, teachers list |
| 📅 | `calendar` | Class schedule |
| 💰 | `dollar-sign` | Payments |
| ⚙️ | `settings` | Settings page |
| 📊 | `bar-chart` | Reports |
| 🎵 | `music` | Music-related sections |

### 5. Feedback Icons
Used for alerts, validation.

| Icon | Name | Usage |
|------|------|-------|
| ✓ | `check-circle` | Success message |
| ⚠️ | `alert-triangle` | Warning message |
| ❌ | `x-circle` | Error message |
| ℹ️ | `info` | Info message |

## Usage Patterns

### Input Prefix Icon
```blade
<div class="relative">
  <i data-lucide="phone" class="
    absolute
    right-4
    top-1/2
    -translate-y-1/2
    w-5 h-5
    text-[var(--text-secondary)]
  "></i>
  <input class="pr-12" />
</div>
```

### Button with Icon
```blade
<button class="flex items-center gap-2">
  <i data-lucide="log-in" class="w-5 h-5"></i>
  <span>ورود</span>
</button>
```

### Icon-Only Button (Social)
```blade
<button class="
  w-14 h-14
  flex items-center justify-center
  rounded-full
" aria-label="ورود با گوگل">
  <i data-lucide="google" class="w-6 h-6"></i>
</button>
```

### Toggle Icon (Show/Hide Password)
```blade
<button 
  x-data="{ show: false }"
  @click="show = !show"
  class="absolute left-4 top-1/2 -translate-y-1/2"
>
  <i 
    :data-lucide="show ? 'eye-off' : 'eye'"
    class="w-5 h-5 text-[var(--text-secondary)]"
  ></i>
</button>
```

## Icon Colors

Match icons to their context:

| Context | Color | Token |
|---------|-------|-------|
| Default | Muted gray | `var(--text-secondary)` |
| Active | Gold | `var(--gold-300)` |
| Success | Green | `var(--success-500)` |
| Error | Red | `var(--error-500)` |
| Disabled | Light gray | `rgba(255,255,255,0.3)` |

## Accessibility

### 1. Decorative Icons
Icons with adjacent text are decorative:

```blade
<button>
  <i data-lucide="log-in" aria-hidden="true"></i>
  <span>ورود</span> <!-- Text provides context -->
</button>
```

### 2. Functional Icons
Icons without text need `aria-label`:

```blade
<button aria-label="نمایش رمز عبور">
  <i data-lucide="eye"></i>
</button>
```

### 3. Interactive Icons
Toggle icons need state announcement:

```blade
<button 
  aria-label="رمز عبور"
  aria-pressed="false"
  x-data="{ show: false }"
  @click="show = !show; $el.setAttribute('aria-pressed', show)"
>
  <i :data-lucide="show ? 'eye-off' : 'eye'"></i>
</button>
```

## RTL Considerations

### Symmetric Icons
Most icons are symmetric and don't need RTL adjustment:
- ✅ `phone`, `lock`, `eye`, `user`, `settings`, `home`

### Directional Icons
Some icons need horizontal flip in RTL:
- ❌ `log-in` → flip to point left
- ❌ `arrow-right` → becomes `arrow-left`
- ❌ `chevron-right` → becomes `chevron-left`

**Solution:**
```blade
<i 
  data-lucide="log-in" 
  class="rtl:scale-x-[-1]"
></i>
```

Or use Lucide's directional variants:
```blade
<i data-lucide="arrow-left"></i> <!-- Use left for RTL -->
```

## Custom Social Icons

Lucide doesn't include brand icons. Use custom SVG:

### Google Icon
```blade
<svg class="w-6 h-6" viewBox="0 0 24 24">
  <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
  <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
  <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
  <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
</svg>
```

### Apple Icon
```blade
<svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
  <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
</svg>
```

## Design Tokens

```css
:root {
  /* Icon sizes */
  --icon-xs: 16px;
  --icon-sm: 20px;
  --icon-md: 24px;
  --icon-lg: 32px;
  --icon-xl: 48px;
  
  /* Icon colors */
  --icon-default: var(--text-secondary);
  --icon-active: var(--gold-300);
  --icon-disabled: rgba(255, 255, 255, 0.3);
}
```

## Performance

### Lazy Loading
Initialize Lucide icons after DOM ready:

```js
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
});
```

### Dynamic Icons
For SPAs, re-initialize after dynamic content:

```js
// After inserting new icons
lucide.createIcons({
  icons: { phone, lock, eye } // Only icons you need
});
```

## Checklist

Before using an icon:
- [ ] Icon has clear purpose (not decorative clutter)
- [ ] Decorative icons have `aria-hidden="true"`
- [ ] Functional icons have `aria-label`
- [ ] Size matches surrounding text or design spec
- [ ] Color provides sufficient contrast
- [ ] RTL behavior tested (flip if directional)
- [ ] Icon initialized with `lucide.createIcons()`

