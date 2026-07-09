# CALENDAR ENUM ARRAY OFFSET BUG — COMPREHENSIVE AUDIT

**File:** `resources/views/admin/calendar.blade.php`  
**Error:** Cannot access offset of type App\Enums\SessionStatusEnum on array  
**Status:** 1 CRITICAL BUG FOUND

---

## CRITICAL ISSUE

### Line 108 - 🔴 ARRAY OFFSET ERROR

**Location:** `resources/views/admin/calendar.blade.php` line 108

**Code:**
```blade
$statusColors = [
    'scheduled' => 'border-sky-500/40 bg-sky-500/10 text-sky-300',
    'completed' => 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300',
    'cancelled' => 'border-red-500/40 bg-red-500/10 text-red-300',
    'makeup' => 'border-amber-500/40 bg-amber-500/10 text-amber-300',
];
$cardColor = $statusColors[$session->status] ?? 'border-gray-600 bg-gray-700/40 text-gray-300';
```

**Problem:**
- `$session->status` is a `SessionStatusEnum` object (enum backed by string)
- Line 108 uses it directly as array key: `$statusColors[$session->status]`
- PHP tries to access array with enum object, not string
- **Error:** Cannot access offset of type App\Enums\SessionStatusEnum on array

**Root Cause:**
- ClassSession model casts: `'status' => SessionStatusEnum::class`
- When retrieved from DB, `$session->status` is SessionStatusEnum object
- Must use `.value` property to get the string value

**Fix:**
```blade
@php
    $statusColors = [
        'scheduled' => 'border-sky-500/40 bg-sky-500/10 text-sky-300',
        'completed' => 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300',
        'cancelled' => 'border-red-500/40 bg-red-500/10 text-red-300',
        'makeup' => 'border-amber-500/40 bg-amber-500/10 text-amber-300',
    ];
    $statusValue = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
    $cardColor = $statusColors[$statusValue] ?? 'border-gray-600 bg-gray-700/40 text-gray-300';
@endphp
```

**Severity:** 🔴 **CRITICAL** - Crashes when rendering calendar with sessions

---

## FULL FILE ENUM AUDIT

### All Enum Usage in calendar.blade.php

#### ✅ SAFE - Line 35 (Filter: teacher_id)
```blade
{{ (string) request('teacher_id') === (string) $teacher->id ? 'selected' : '' }}
```
**Status:** SAFE - Comparing IDs (not enums)

#### ✅ SAFE - Line 44 (Filter: student_id)
```blade
{{ (string) request('student_id') === (string) $student->id ? 'selected' : '' }}
```
**Status:** SAFE - Comparing IDs (not enums)

#### ✅ SAFE - Line 54 (Filter: room)
```blade
{{ request('room') === $r ? 'selected' : '' }}
```
**Status:** SAFE - Comparing string room names

#### 🔴 **CRITICAL - Line 108 (Array offset using enum)**
```blade
$cardColor = $statusColors[$session->status] ?? 'border-gray-600 bg-gray-700/40 text-gray-300';
```
**Status:** ❌ UNSAFE - Using enum object as array key
**Error:** Cannot access offset of type App\Enums\SessionStatusEnum on array

---

## SUMMARY

| Line | Issue | Type | Severity | Status |
|------|-------|------|----------|--------|
| 108 | Enum used as array offset | Array access | 🔴 CRITICAL | ❌ UNSAFE |

---

## RECOMMENDED FIX

**Method 1: Extract enum value to variable (Recommended)**
```blade
@php
    $statusValue = $session->status instanceof \BackedEnum ? $session->status->value : (string) $session->status;
    $cardColor = $statusColors[$statusValue] ?? 'border-gray-600 bg-gray-700/40 text-gray-300';
@endphp
```

**Method 2: Direct access to .value (If enum is guaranteed)**
```blade
@php
    $cardColor = $statusColors[$session->status->value] ?? 'border-gray-600 bg-gray-700/40 text-gray-300';
@endphp
```

---

## ENUM PATTERN GUIDELINES

When using backed enums in Blade:

### ❌ UNSAFE Patterns:
```blade
{{ $enum }}                           # Direct echo
{{ $array[$enum] }}                   # Array access with enum
{{ __('key.'.$enum) }}                # Concatenation
@if ($enum === 'value')              # Direct comparison
```

### ✅ SAFE Patterns:
```blade
{{ $enum->value }}                    # Direct .value access
{{ $enum instanceof \BackedEnum ? $enum->value : (string) $enum }}  # instanceof check
{{ __('key.'.$enum->value) }}         # Concatenation with .value
@if ($enum->value === 'value')       # Comparison with .value
```

---

**Status:** Ready for fix
