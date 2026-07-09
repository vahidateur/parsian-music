# BRANCH DIFF AUDIT — FEATURE INVENTORY

**Date:** July 2, 2026  
**Current Branch:** master  
**Comparing:** master vs demo-seeder  
**Master Commit:** 3981985 (stable dashboard baseline)  
**Demo-seeder Commit:** 191a19a + uncommitted changes  

---

## SUMMARY

| Category | Count | Status |
|----------|-------|--------|
| **A — Safe to Port** | 7 | Ready for merge |
| **B — Risky/Partial** | 6 | Review needed |
| **C — Broken/Discard** | 2 | Do not merge |

**Total Files Changed:** 68  
**Total Insertions:** 9,215  
**Total Deletions:** 421  

---

## CATEGORY A — SAFE FEATURES (READY TO PORT)

### A1: Translations & Localization

| Feature | Branch | Files Changed | Status | Action |
|---------|--------|---------------|--------|--------|
| **Persian (Farsi) Localization** | demo-seeder | `lang/fa/admin.php`, `resources/lang/fa/admin.php` | ✅ Complete, no errors | **KEEP** |
| **English Translations** | demo-seeder | `lang/en/admin.php`, `resources/lang/en/admin.php` | ✅ Complete, comprehensive | **KEEP** |
| **Translation Keys** | demo-seeder | Multiple views use `__('admin.*')` | ✅ Consistent | **KEEP** |

**Notes:**
- All translation keys present in both EN/FA
- No conflicts with master
- Ready for direct merge

---

### A2: Jalalian Helper (Persian Date Converter)

| Feature | Branch | Files Changed | Status | Action |
|---------|--------|---------------|--------|--------|
| **Jalalian Helper Class** | demo-seeder | `app/Helpers/Jalalian.php` | ✅ Complete utility | **KEEP** |
| **Jalali Date Display in Views** | demo-seeder | Multiple views use `\App\Helpers\Jalalian` | ✅ Working | **KEEP** |
| **Dashboard Jalali Dates** | demo-seeder | `resources/views/admin/dashboard.blade.php` line 25+ | ✅ Displays correctly | **KEEP** |

**Notes:**
- Utility code, no dependencies on risky features
- Already working in master
- Safe to keep

---

### A3: Demo Seeder (Test Data)

| Feature | Branch | Files Changed | Status | Action |
|---------|--------|---------------|--------|--------|
| **DemoSeeder Class** | demo-seeder | `database/seeders/DemoSeeder.php` (362 lines) | ✅ Complete | **KEEP** |
| **Documentation** | demo-seeder | `DEMO_DATABASE_SETUP.md`, `DEMO_SEEDER_SUMMARY.txt` | ✅ Clear | **KEEP** |

**Notes:**
- Creates realistic test data for teachers, students, enrollments
- 362 insertions, no deletions = additive only
- Does not modify existing logic
- Can be run anytime with `php artisan db:seed --class=DemoSeeder`

---

### A4: Dashboard UI Improvements (DashboardService)

| Feature | Branch | Files Changed | Status | Action |
|---------|--------|---------------|--------|--------|
| **DashboardService Enhancements** | demo-seeder | `app/Services/Reports/DashboardService.php` (109 lines added) | ✅ Improved charts | **KEEP** |
| **Enrollment Trend** | demo-seeder | Adds `getEnrollmentTrend()` method | ✅ Working | **KEEP** |
| **Attendance Stats** | demo-seeder | Adds `getAttendanceStats()` method | ✅ Working | **KEEP** |

**Notes:**
- Only additions, no breaking changes
- Enhances dashboard with real data
- Master dashboard still works without these

---

### A5: Locale & Middleware (I18n Support)

| Feature | Branch | Files Changed | Status | Action |
|---------|--------|---------------|--------|--------|
| **LocaleController** | demo-seeder | `app/Http/Controllers/LocaleController.php` (21 lines) | ✅ Route switching | **KEEP** |
| **SetLocale Middleware** | demo-seeder | `app/Http/Middleware/SetLocale.php` (25 lines) | ✅ Language detection | **KEEP** |
| **bootstrap/app.php** | demo-seeder | +4 lines for locale support | ✅ Configuration | **KEEP** |

**Notes:**
- Enables language switching between EN/FA
- Non-invasive middleware
- Already working in current codebase

---

### A6: CSS Improvements

| Feature | Branch | Files Changed | Status | Action |
|---------|--------|---------------|--------|--------|
| **app.css Enhancements** | demo-seeder | `resources/css/app.css` (43 insertions) | ✅ Visual polish | **KEEP** |

**Notes:**
- Only additions to styling
- No conflicts with existing styles

---

### A7: Documentation & Audits

| Feature | Branch | Files Changed | Status | Action |
|---------|--------|---------------|--------|--------|
| **Documentation Files** | demo-seeder | 19 markdown files (AUDIT_FINDINGS, IMPLEMENTATION_COMPLETE, etc.) | ✅ Reference only | **KEEP** |

**Notes:**
- Non-code documentation
- Safe to merge or keep separate
- Valuable for future reference

---

## CATEGORY B — RISKY/PARTIALLY BROKEN (REVIEW NEEDED)

### B1: Room Management Module

| Feature | Branch | Files Changed | Status | Issue | Action |
|---------|--------|---------------|--------|-------|--------|
| **Room Model** | demo-seeder | `app/Models/Room.php` | ⚠️ New model | No data validation | **REVIEW** |
| **RoomController** | demo-seeder | `app/Http/Controllers/Admin/RoomController.php` (70 lines) | ⚠️ New CRUD | Needs testing | **REVIEW** |
| **Room Views** | demo-seeder | `resources/views/admin/rooms/*.blade.php` (3 files) | ⚠️ New UI | Not tested in production | **REVIEW** |
| **Migration (Duplicate)** | demo-seeder | TWO migrations: `2024_01_01_create_rooms_table.php` + `2026_07_02_142202_create_rooms_table.php` | 🔴 **CRITICAL** | **Duplicate migration!** | **FIX FIRST** |

**Issues:**
- Two room migrations with different timestamps
- Will cause migration conflicts
- RoomController not in routes (routes/web.php missing room routes on master)
- Rooms still hardcoded in calendar/sessions views

**Recommendation:**
- ❌ **DO NOT MERGE** rooms module yet
- Delete duplicate migration
- Add room routes to web.php
- Update hardcoded room lists to use Room model

---

### B2: RecurringScheduleController Enhancements

| Feature | Branch | Files Changed | Status | Issue | Action |
|---------|--------|---------------|--------|-------|--------|
| **Time Validation (15:00-21:30)** | demo-seeder | `app/Http/Controllers/Admin/RecurringScheduleController.php` (66 lines) | ✅ Fixed | Added `between:15:00,21:30` | **KEEP** |
| **Schedule Model Updates** | demo-seeder | `app/Models/RecurringSchedule.php` (18 lines) | ✅ Helper method | `getFormattedStartTimeAttribute()` | **KEEP** |

**Notes:**
- Time validation working correctly
- Safe to merge

---

### B3: ClassSessionController Refactor

| Feature | Branch | Files Changed | Status | Issue | Action |
|---------|--------|---------------|--------|-------|--------|
| **Manual Session Creation** | demo-seeder | `app/Http/Controllers/Admin/ClassSessionController.php` (+44 lines) | ⚠️ Partial | `create()` & `store()` methods added | **NEEDS TEST** |
| **Filter Data in Views** | demo-seeder | Passes `$students, $teachers, $instruments` | ✅ Working | Fixed empty dropdowns | **KEEP** |
| **Session Create View** | demo-seeder | `resources/views/admin/sessions/create.blade.php` (101 lines) | ⚠️ New UI | Queries Room model in view (anti-pattern) | **REVIEW** |

**Issues:**
- View queries Room directly: `\App\Models\Room::where('is_active', true)->orderBy('name')->get()`
- Better: Pass from controller
- Otherwise functional

**Recommendation:**
- ✅ Accept with minor refactor: Move Room query to controller

---

### B4: StudentEnrollmentController Changes

| Feature | Branch | Files Changed | Status | Issue | Action |
|---------|--------|---------------|--------|-------|--------|
| **Enrollment CRUD** | demo-seeder | `app/Http/Controllers/Admin/StudentEnrollmentController.php` (+24 lines) | ⚠️ Enhanced | New methods added | **REVIEW** |

**Recommendation:**
- Needs verification that enrollment routes exist
- Check if all methods have corresponding routes

---

### B5: Calendar View Refactor

| Feature | Branch | Files Changed | Status | Issue | Action |
|---------|--------|---------------|--------|-------|--------|
| **Enum Bug Fixes** | demo-seeder | `resources/views/admin/calendar.blade.php` (43 changes) | ✅ Fixed | Extracted `$statusValue` | **KEEP** |
| **Hardcoded Rooms** | demo-seeder | Still uses `['Room 1', 'Room 2', 'Room 3']` | ⚠️ Not dynamic | Should query Room model | **NEEDS FIX** |

**Recommendation:**
- Keep enum fixes
- Replace hardcoded rooms with Room model query

---

### B6: Dashboard Enhancement

| Feature | Branch | Files Changed | Status | Issue | Action |
|---------|--------|---------------|--------|-------|--------|
| **Dashboard Charts** | demo-seeder | `resources/views/admin/dashboard.blade.php` (379 lines) | ⚠️ Major rewrite | Complex logic, many changes | **TEST REQUIRED** |
| **Attendance Stats** | demo-seeder | New widget showing stats | ✅ Works if data exists | Depends on ClassAttendance table | **VERIFY** |
| **Enrollment Trend** | demo-seeder | 6-month trend chart | ⚠️ Complex | CSS bar chart | **TEST** |

**Recommendation:**
- Review carefully
- Test with real data
- Check responsive design

---

## CATEGORY C — BROKEN/DISCARD

### C1: Duplicate Room Migration

| Feature | Branch | Issue | Action |
|---------|--------|-------|--------|
| **Migration Files** | demo-seeder | TWO files create `rooms` table: `2024_01_01_create_rooms_table.php` + `2026_07_02_142202_create_rooms_table.php` | 🔴 **DELETE ONE** |

**Why broken:**
- Laravel will try to run both migrations
- Second one will fail (table already exists)
- Causes migration errors

**Fix:**
```bash
# Delete: database/migrations/2024_01_01_create_rooms_table.php
# Keep: database/migrations/2026_07_02_142202_create_rooms_table.php
```

---

### C2: Missing Routes for Room Module

| Feature | Branch | Issue | Action |
|---------|--------|-------|--------|
| **Room Routes** | demo-seeder | RoomController exists but `routes/web.php` missing room routes | 🔴 **BROKEN** |

**Evidence:**
- `RoomController::index()` exists
- But no route to access it
- Would 404 if someone tries `/admin/rooms`

**Fix:**
- Add to `routes/web.php`:
```php
Route::middleware(['auth', 'role:admin'])->prefix('admin/rooms')->name('admin.rooms.')->group(function () {
    Route::get('/', [RoomController::class, 'index'])->name('index');
    Route::get('/create', [RoomController::class, 'create'])->name('create');
    Route::post('/', [RoomController::class, 'store'])->name('store');
    Route::get('/{room}/edit', [RoomController::class, 'edit'])->name('edit');
    Route::put('/{room}', [RoomController::class, 'update'])->name('update');
    Route::delete('/{room}', [RoomController::class, 'destroy'])->name('destroy');
    Route::patch('/{room}/toggle', [RoomController::class, 'toggle'])->name('toggle');
});
```

---

## DETAILED FILE-BY-FILE ANALYSIS

### Views Status

| View File | Branch | Changes | Status | Notes |
|-----------|--------|---------|--------|-------|
| `admin/calendar.blade.php` | demo-seeder | 43 changes | ✅ Safe | Enum fixes + filters |
| `admin/dashboard.blade.php` | demo-seeder | 379 changes | ⚠️ Major | Complex, needs testing |
| `admin/sessions/index.blade.php` | demo-seeder | 88 changes | ✅ Safe | Enum fixes + pagination |
| `admin/sessions/create.blade.php` | demo-seeder | NEW | ⚠️ Minor issue | Queries Room in view |
| `admin/students/edit.blade.php` | demo-seeder | 81 changes | ✅ Safe | Jalali date helper |
| `admin/enrollments/index.blade.php` | demo-seeder | 81 changes | ✅ Safe | Enum fixes |
| `admin/enrollments/show.blade.php` | demo-seeder | NEW (295 lines) | ✅ Safe | New view, no conflicts |
| `admin/teachers/*.blade.php` | demo-seeder | NEW (4 files) | ✅ Safe | Teacher CRUD views |
| `admin/rooms/*.blade.php` | demo-seeder | NEW (3 files) | ⚠️ Risky | No routes, hardcoded data |

---

## MERGE RECOMMENDATION

### ✅ SAFE TO MERGE NOW (No conflicts, no issues)
1. Translations (EN/FA)
2. Jalalian helper
3. Demo seeder
4. UI improvements (dashboard service)
5. Locale/middleware support
6. CSS improvements
7. Enum bug fixes in views
8. Teacher CRUD views
9. Enrollment show view
10. Time validation (15:00-21:30)

### ⚠️ REQUIRES FIXES FIRST (Before merge)
1. **Delete duplicate migration**: `2024_01_01_create_rooms_table.php`
2. **Add room routes** to `routes/web.php`
3. **Move Room query** from sessions create view to controller
4. **Replace hardcoded rooms** in calendar/sessions with Room model query

### ❌ DO NOT MERGE (Until fixed)
- Nothing critical, just requires the fixes above

---

## MERGE STRATEGY

**Phase 1 — Safe Features (Low Risk)**
- Merge: Translations, Jalalian, demo seeder, locale support, CSS, docs

**Phase 2 — With Fixes (Medium Risk)**
- Apply fixes above
- Test room management thoroughly
- Merge: Room module, sessions CRUD

**Phase 3 — Major Refactors (High Risk)**
- Extensive testing required
- Merge: Dashboard enhancements

---

## FILES STATUS SUMMARY

```
✅ SAFE TO KEEP:           55 files
⚠️  NEEDS REVIEW:          8 files  
🔴 NEEDS FIXING:           2 issues (duplicate migration, missing routes)
❌ SHOULD DISCARD:         0 files
```

---

**Audit Complete**  
**Last Updated:** July 2, 2026
