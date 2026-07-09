# ROUTE AUDIT — locale.switch Missing Route

**Date:** July 2, 2026  
**Issue:** Route [locale.switch] not defined  
**Status:** ❌ MISSING  

---

## FINDINGS

### 1. Route Definition
**Status:** ❌ **MISSING FROM routes/web.php**

**Where needed:**
- File: `routes/web.php`
- Current state: No route named `locale.switch` defined
- No import of LocaleController

### 2. LocaleController Status
**Status:** ✅ **CONTROLLER EXISTS WITH CORRECT METHOD**

**File:** `app/Http/Controllers/LocaleController.php`

```php
class LocaleController extends Controller
{
    private const ALLOWED_LOCALES = ['fa', 'en'];

    public function switch(string $locale): RedirectResponse
    {
        if (! in_array($locale, self::ALLOWED_LOCALES, true)) {
            abort(404);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
```

- ✅ Class exists
- ✅ Method `switch($locale)` implemented
- ✅ Returns RedirectResponse
- ✅ Validates allowed locales (fa, en)
- ✅ Sets session and redirects back

### 3. Dashboard Blade Usage
**Status:** ✅ **CALLS ROUTE CORRECTLY**

**File:** `resources/views/layouts/dashboard.blade.php` (lines 133-138)

```blade
<a href="{{ route('locale.switch', 'fa') }}" ...>FA</a>
<a href="{{ route('locale.switch', 'en') }}" ...>EN</a>
```

- ✅ Calls `route('locale.switch', locale_parameter)`
- ✅ Passes 'fa' and 'en' as parameters
- ✅ Blade syntax correct

Also uses `$locale` variable (line 135, 138):
```blade
{{ $locale === 'fa' ? 'text-amber-300' : '...' }}
```

This variable needs to be set in the layout (see note below).

### 4. Web.php Analysis
**File:** `routes/web.php`

**Current imports:**
```php
use App\Enums\RoleEnum;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\ClassAttendanceController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentEnrollmentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TeacherPanelController;
use App\Http\Controllers\Admin\TeacherReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
```

**Status:** ❌ **Missing:** `use App\Http\Controllers\LocaleController;`

---

## REQUIRED FIX

### Step 1: Add Import to web.php
**Add this line after ProfileController import (line 12):**

```php
use App\Http\Controllers\LocaleController;
```

### Step 2: Add Route to web.php
**Add this route group (can go after the auth.php require, around line 34):**

```php
Route::middleware('auth')->get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
```

**Full context where to add (after line 33):**
```php
require __DIR__.'/auth.php';
require __DIR__.'/dashboard.php';

// ADD THIS:
Route::middleware('auth')->get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// EXISTING CODE:
Route::middleware(['auth', 'role:admin'])->prefix('admin/students')...
```

---

## SUMMARY TABLE

| Item | Status | Details |
|------|--------|---------|
| LocaleController exists | ✅ | Path: `app/Http/Controllers/LocaleController.php` |
| switch() method exists | ✅ | Signature: `switch(string $locale): RedirectResponse` |
| Route imported in web.php | ❌ | **MISSING** - need to add import |
| Route defined in web.php | ❌ | **MISSING** - need to add route definition |
| Dashboard calls route | ✅ | `route('locale.switch', 'fa/en')` correct |
| Dashboard passes $locale | ⚠️ | **Variable needs to be passed from layout/controller** |

---

## EXACT LINES TO ADD

### File: routes/web.php

**Line to add (import section):**
```php
use App\Http\Controllers\LocaleController;
```

**Insert after:** Line 12 (after `use App\Http\Controllers\ProfileController;`)

**Line to add (routes section):**
```php
Route::middleware('auth')->get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
```

**Insert after:** Line 33 (after `require __DIR__.'/dashboard.php';`)

---

## ADDITIONAL NOTE: $locale Variable

**File:** `resources/views/layouts/dashboard.blade.php`

**Lines 135, 138 use:** `$locale` variable

```blade
{{ $locale === 'fa' ? 'text-amber-300' : '...' }}
{{ $locale === 'en' ? 'text-amber-300' : '...' }}
```

**Status:** ⚠️ This variable is used but may not be passed to the layout.

**Solution:** Add to any layout-providing view or in a service provider:

```php
View::share('locale', session('locale', config('app.locale')));
```

Or in layout's controller route, pass it:
```php
return view('layouts.dashboard', ['locale' => session('locale', 'en')]);
```

Currently, the locale is stored in session by SetLocale middleware. The variable should be available via session or config, but may not be explicitly passed.

**Check:** Look for where AppLayout or GuestLayout components pass this variable, or add it to AppServiceProvider.

---

## COMPLETE WORKING SOLUTION

Add to `routes/web.php`:

```php
<?php

use App\Enums\RoleEnum;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\ClassAttendanceController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentEnrollmentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TeacherPanelController;
use App\Http\Controllers\Admin\TeacherReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocaleController;  // ← ADD THIS
use Illuminate\Support\Facades\Route;

// ... existing routes ...

require __DIR__.'/auth.php';
require __DIR__.'/dashboard.php';

// ADD THIS ROUTE:
Route::middleware('auth')->get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// ... rest of routes ...
```

---

## STATUS

**Missing:** 
- ❌ LocaleController import in web.php
- ❌ locale.switch route definition

**Present:**
- ✅ LocaleController class
- ✅ switch() method implementation
- ✅ Dashboard blade calling route correctly
- ⚠️ $locale variable usage (needs verification of source)

**Root Cause:** 
Route definition exists in demo-seeder but was not added to current master when file was restored. Only the controller was restored, not the route configuration.

---

**Audit Complete - Ready for Implementation**
