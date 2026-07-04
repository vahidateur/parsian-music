# Design Document: Admin Settings Module

## Overview

The Admin Settings Module provides a centralized administration interface for managing application configuration, including branding, theme customization, user management, and translation overrides. The module is built on a two-table database foundation (`settings` and `translation_overrides`), with an in-memory caching layer to optimize performance. All operations are RTL-aware and support Persian language throughout.

**Key Design Decisions:**
- Single JSON-keyed `settings` table for scalability and flexibility
- Cache-first retrieval with Laravel's cache facade
- Separated concerns between controllers, services, and repositories
- File uploads with strict validation and timestamped storage
- Translation overrides stored separately for easy management

---

## Architecture

The Admin Settings Module follows a modular monolith pattern with clear separation of concerns:

```
Routes (HTTP Layer)
    ↓
Controllers (Request Handling)
    ↓
Services (Business Logic)
    ↓
Repositories (Data Abstraction)
    ↓
Models + Cache (Persistence)
```

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       └── SettingsController.php       # Main controller for all settings
│   └── Requests/
│       ├── StoreBrandingSettingsRequest.php
│       ├── StoreThemeSettingsRequest.php
│       ├── StoreUserRequest.php
│       ├── UpdateUserRequest.php
│       └── UpdateTranslationOverrideRequest.php
├── Services/
│   └── SettingsService.php                  # Orchestrates settings operations
├── Repositories/
│   ├── SettingsRepository.php               # Settings CRUD and caching
│   ├── TranslationOverrideRepository.php    # Translation overrides management
│   └── UserRepository.php                   # User management for settings context
└── Models/
    ├── Setting.php
    └── TranslationOverride.php
resources/
├── views/
│   └── admin/
│       └── settings/
│           ├── layout.blade.php             # RTL-aware layout wrapper
│           ├── branding/
│           │   ├── index.blade.php          # List/form combined
│           │   └── preview.blade.php        # Logo/favicon preview partial
│           ├── theme/
│           │   └── index.blade.php
│           ├── users/
│           │   ├── index.blade.php          # List with pagination
│           │   ├── create.blade.php         # Create form
│           │   ├── edit.blade.php           # Edit form
│           │   └── _user-row.blade.php      # Table row partial
│           └── translations/
│               ├── index.blade.php          # Search and list
│               ├── edit-modal.blade.php     # Override form modal
│               └── _translation-row.blade.php
database/
└── migrations/
    ├── YYYY_MM_DD_HHMMSS_create_settings_table.php
    └── YYYY_MM_DD_HHMMSS_create_translation_overrides_table.php
```

---

## Components and Interfaces

### 1. Database Models

#### Setting Model
```php
class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = ['key', 'value'];
    protected $casts = ['value' => 'json'];
}
```

**Table Schema (Requirement 5):**
- `id` (primary key)
- `key` (string, unique)
- `value` (json/text)
- `created_at`, `updated_at`

#### TranslationOverride Model
```php
class TranslationOverride extends Model
{
    protected $table = 'translation_overrides';
    protected $fillable = ['translation_key', 'overridden_value'];
}
```

**Table Schema (Requirement 5):**
- `id` (primary key)
- `translation_key` (string, unique)
- `overridden_value` (text)
- `created_at`, `updated_at`

### 2. Repositories

#### SettingsRepository
Singleton pattern ensuring single cache/database manager.

**Public Methods:**
- `get(string $key): array|null` — Fetch setting from cache or DB
- `all(): Collection` — Fetch all settings
- `put(string $key, array $value): bool` — Store setting and invalidate cache
- `forget(string $key): bool` — Delete setting and invalidate cache
- `invalidateCache(string $key): void` — Explicit cache invalidation

**Caching Logic:**
- Cache key format: `settings:{key}` (e.g., `settings:branding`)
- TTL: 24 hours (configurable)
- Retrieval: Check cache first → DB if miss → populate cache
- Invalidation: On create/update/delete via `cache()->forget()`

**Implementation Pattern:**
```php
public function get(string $key): ?array
{
    return cache()->remember(
        "settings:{$key}",
        now()->addHours(24),
        fn() => $this->queryDatabase($key)
    );
}
```

#### TranslationOverrideRepository
Manages translation overrides with cache invalidation.

**Public Methods:**
- `get(string $translationKey): ?string` — Fetch override or null
- `put(string $translationKey, string $value): bool` — Store override
- `forget(string $translationKey): bool` — Delete override
- `invalidateCache(): void` — Clear translations cache

#### UserRepository
Wraps User model for password generation and role management.

**Public Methods:**
- `paginated(int $perPage = 25): Paginator`
- `search(string $query, int $perPage = 25): Paginator`
- `create(array $data): User`
- `update(User $user, array $data): bool`
- `toggleStatus(User $user): bool`
- `resetPassword(User $user): string` — Returns new password

### 3. Services

#### SettingsService
Orchestrates business logic for all settings operations.

**Public Methods:**
- `getBrandingSettings(): array`
- `saveBrandingSettings(array $data): bool`
- `getThemeSettings(): array`
- `saveThemeSettings(array $data): bool`
- `uploadFile(UploadedFile $file, string $type): string` — Returns filename
- `deleteFile(string $filename): bool`

**File Upload Logic:**
- Validate file type and size
- Generate timestamped filename: `{setting}_{timestamp}_{randomHash}.{ext}`
- Store in `storage/app/public/settings/`
- Return path for storage in database
- On error: throw exception with user-friendly message

**Branding Settings Data Structure:**
```php
[
    'site_name' => 'آموزشگاه پارسیان موزیک',
    'site_subtitle' => '',
    'logo_path' => 'settings/logo_1702345678_abc123.png',
    'favicon_path' => 'settings/favicon_1702345678_def456.ico',
    'whatsapp_url' => '',
    'telegram_url' => '',
    'instagram_url' => '',
]
```

**Theme Settings Data Structure:**
```php
[
    'font_family' => 'Vazirmatn',
    'primary_color' => '#4F46E5',
    'sidebar_style' => 'dark',
]
```

#### TranslationService
Manages translation overrides and default fallbacks.

**Public Methods:**
- `get(string $translationKey): string` — Override if exists, else default
- `saveOverride(string $key, string $value): bool`
- `deleteOverride(string $key): bool`
- `getDefaultValue(string $key): string`

**Runtime Integration:**
- Extends or wraps Laravel's translation loader
- Queried via `trans()` and `__()` helpers
- Override precedence: database → default language files

#### UserService
Manages user creation, updates, and password operations.

**Public Methods:**
- `createUser(array $data): User|false`
- `updateUser(User $user, array $data): bool`
- `generatePassword(): string` — 12+ chars with uppercase, lowercase, numbers, special chars
- `toggleStatus(User $user): bool`
- `sendCredentialsEmail(User $user, string $password): void`

**Password Generation:**
Uses `Str::random()` with character set: `A-Z`, `a-z`, `0-9`, `!@#$%^&*`

### 4. Controllers

#### SettingsController
Single controller handling all settings routes.

**Methods:**

| Method | Route | View/Response |
|--------|-------|---------------|
| `brandingIndex()` | GET `/admin/settings/branding` | `admin.settings.branding.index` |
| `brandingStore()` | POST `/admin/settings/branding` | Redirect + flash |
| `themeIndex()` | GET `/admin/settings/theme` | `admin.settings.theme.index` |
| `themeStore()` | POST `/admin/settings/theme` | Redirect + flash |
| `usersIndex()` | GET `/admin/settings/users` | `admin.settings.users.index` |
| `usersCreate()` | GET `/admin/settings/users/create` | `admin.settings.users.create` |
| `usersStore()` | POST `/admin/settings/users` | Redirect + flash |
| `usersEdit()` | GET `/admin/settings/users/{user}` | `admin.settings.users.edit` |
| `usersUpdate()` | POST `/admin/settings/users/{user}` | Redirect + flash |
| `usersToggleStatus()` | POST `/admin/settings/users/{user}/toggle` | Redirect + flash |
| `usersResetPassword()` | POST `/admin/settings/users/{user}/reset-password` | Redirect + flash |
| `translationsIndex()` | GET `/admin/settings/translations` | `admin.settings.translations.index` |
| `translationsEdit()` | GET `/admin/settings/translations/{key}/edit` | JSON response with form |
| `translationsStore()` | POST `/admin/settings/translations/{key}` | JSON response |
| `translationsDelete()` | DELETE `/admin/settings/translations/{key}` | JSON response |

**Error Handling:**
- File upload errors: Return `admin.settings.file_upload_error` message
- DB errors: Log via `Log::error()`, return user-friendly message, use cached data if available
- Validation errors: Use form request validation, return back with errors

---

## Data Models

### Branding Settings (Stored as JSON)
```json
{
  "site_name": "آموزشگاه پارسیان موزیک",
  "site_subtitle": "",
  "logo_path": null,
  "favicon_path": null,
  "whatsapp_url": "",
  "telegram_url": "",
  "instagram_url": ""
}
```

**Default Values (Requirement 9):**
- Seeded on migration/database init
- Site name: `آموزشگاه پارسیان موزیک`
- All URLs: empty string
- File paths: null

### Theme Settings (Stored as JSON)
```json
{
  "font_family": "Vazirmatn",
  "primary_color": "#4F46E5",
  "sidebar_style": "dark"
}
```

**Options:**
- Font family: `Vazirmatn` (پیش‌فرض), `IRANSansX`, `Tahoma`
- Sidebar style: `dark`, `light`, `compact`

### Translation Override Structure
```
translation_key: "admin.settings.branding.site_name"
overridden_value: "نام سایت دوم"
```

---

## Caching Strategy

### Cache Layer Design

**Cache Key Format:**
- Settings: `settings:{key}` (e.g., `settings:branding`)
- Translations: `settings:translations` (entire collection)
- User search: `admin:users:search:{hash}` (temporary, 5 min TTL)

**TTL Values:**
- Settings: 24 hours (can be configured in `.env` or config)
- Translations: 24 hours
- User searches: 5 minutes

**Invalidation Triggers:**
1. Setting created/updated/deleted → `cache()->forget("settings:{key}")`
2. Translation override created/updated/deleted → `cache()->forget("settings:translations")`
3. User status toggle → No cache needed (rare operation)

### Cache-First Retrieval Pattern

```php
// Settings Repository
public function get(string $key): ?array
{
    return cache()->remember(
        "settings:{$key}",
        now()->addHours(24),
        fn() => Setting::where('key', $key)->first()?->value ?? null
    );
}

// Translation Service
public function get(string $translationKey): string
{
    $override = cache()->remember(
        "settings:translations",
        now()->addHours(24),
        fn() => TranslationOverride::pluck('overridden_value', 'translation_key')->toArray()
    );
    
    return $override[$translationKey] ?? trans($translationKey);
}
```

### Fallback Behavior (Requirement 8)

If database query fails:
1. Check if cache exists → use cached data
2. If no cache → disable feature gracefully
3. Log error: `Log::error('Settings DB query failed', ['key' => $key])`
4. Return user-friendly error message (not stack trace)

---

## File Upload Handling

### Upload Locations
- Base directory: `storage/app/public/settings/`
- Each setting type in subdirectory: `branding/`, `theme/`

### Validation Rules

**Logo File (Requirement 1):**
- Accepted MIME types: `image/png`, `image/jpeg`
- Max file size: 2MB
- Rejected types: SVG (for performance), BMP, GIF

**Favicon File (Requirement 1):**
- Accepted MIME types: `image/x-icon`, `image/png`
- Max file size: 1MB

### Upload Process

1. **Validation:** Check MIME type and file size
2. **Naming:** Generate `{setting}_{timestamp}_{randomHash}.{ext}`
   ```php
   $filename = "{$type}_{time()}_{Str::random(8)}.{$ext}";
   ```
3. **Storage:** `storage/app/public/settings/{$filename}`
4. **Database:** Store relative path in settings JSON
5. **Error Handling:** Return user-friendly message, don't expose filesystem details

### File Deletion
When a new file is uploaded, delete the old file if it exists:
```php
if ($oldPath && Storage::disk('public')->exists($oldPath)) {
    Storage::disk('public')->delete($oldPath);
}
```

### Public Access
Files stored in `public/settings/` are publicly accessible via URL:
- Logo: `/storage/settings/logo_1702345678_abc123.png`
- Favicon: `/storage/settings/favicon_1702345678_def456.ico`

---

## RTL Support

### Markup Structure
All views include `dir="rtl"` at root container:
```blade
<div dir="rtl" class="space-y-6">
    <!-- Content -->
</div>
```

### Tailwind RTL Utilities
Use Tailwind's RTL-aware utilities:
- `me-` (margin-end) instead of `ml-`
- `ps-` (padding-start) instead of `pl-`
- `text-end` instead of `text-right`
- `flex-row-reverse` for reversed layouts
- `rtl:` modifier for RTL-specific styles

### Form Elements
- File upload inputs: Proper alignment in RTL mode
- Color picker: Center alignment with `mx-auto`
- Form labels: Use `label-required` class with RTL support

### Translation Keys
All UI text stored in `lang/fa/admin.php`:
```php
'settings' => [
    'branding' => [
        'title' => 'تنظیمات برندسازی',
        'site_name' => 'نام سایت',
        'logo' => 'لوگو',
    ],
    'theme' => [
        'title' => 'تنظیمات تم',
        'primary_color' => 'رنگ اصلی',
    ],
    'users' => [
        'title' => 'مدیریت کاربران',
        'create_user' => 'ایجاد کاربر جدید',
    ],
]
```

---

## Access Control & Security

### Route Middleware (Requirement 6)
All admin settings routes require:
1. Authentication: `auth:web`
2. Authorization: `middleware('role:admin')` or `CheckRole::class . ':admin'`

**Route Group:**
```php
Route::middleware(['auth:web', CheckRole::class . ':admin'])->group(function () {
    Route::prefix('admin/settings')->group(function () {
        Route::resource('branding', SettingsController::class);
        Route::resource('theme', SettingsController::class);
        Route::resource('users', SettingsController::class);
        Route::resource('translations', SettingsController::class);
    });
});
```

### Error Responses
- Unauthenticated: Redirect to login page
- Unauthorized (non-admin): 403 Forbidden response

---

## Error Handling

### Database Errors (Requirement 8)

**Scenario:** DB query fails during settings retrieval
1. Attempt cache fallback
2. Log error with context
3. Return graceful message to user
4. Disable affected feature or show cached version

```php
try {
    return $this->settingsRepository->get('branding');
} catch (Exception $e) {
    Log::error('Failed to fetch branding settings', ['exception' => $e]);
    // Return cached data or default values
    return cache()->get('settings:branding') ?? $this->getDefaults('branding');
}
```

### File Upload Errors (Requirement 8)

**Error Messages:**
- Invalid type: `admin.settings.file_invalid_type`
- File too large: `admin.settings.file_too_large`
- Storage full: `admin.settings.file_upload_error`
- Disk error: `admin.settings.file_upload_error`

**User-Facing:**
- No filesystem paths in error messages
- No stack traces
- Friendly, actionable text

### Validation Errors

**Form Validation:**
- Use form request validation classes
- Return back with errors and old input
- Highlight invalid fields in UI

**Example:**
```php
class StoreBrandingSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'site_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:1024',
            'whatsapp_url' => 'nullable|url',
        ];
    }
}
```

---

## Testing Strategy

### Unit Tests

**SettingsRepository Tests:**
- Test cache population on first retrieval
- Test cache hit on subsequent retrieval
- Test cache invalidation after update
- Test database fallback if cache miss
- Test error handling with DB unavailable

**SettingsService Tests:**
- Test branding settings save/retrieve
- Test theme settings save/retrieve
- Test file upload validation (type, size)
- Test file upload success and storage
- Test file cleanup on new upload

**TranslationService Tests:**
- Test override retrieval
- Test default fallback
- Test override creation/deletion
- Test cache invalidation

**UserRepository Tests:**
- Test pagination
- Test search filtering
- Test password generation (12+ chars, mixed case, numbers, special)
- Test user creation with valid data
- Test user update with partial data
- Test status toggle

### Integration Tests

**Settings Workflow (Branding):**
- Load branding form
- Submit form with valid data
- Verify data persisted to database
- Verify cache invalidated
- Load form again, verify fresh values

**File Upload Workflow:**
- Upload logo with valid file
- Verify file stored in correct location
- Verify filename is timestamped
- Verify path stored in settings
- Replace with new file, verify old file deleted

**User Management Workflow:**
- Create user with generated password
- Verify user created with `active=true`
- Edit user name/email
- Reset password, verify email sent
- Toggle status, verify `active` flipped

**Translation Override Workflow:**
- Load translation manager
- Search for translation key
- Edit translation and save override
- Verify override returned by `trans()` helper
- Delete override, verify default returned

### Performance Tests

**Load Time Benchmarks (Requirement 8):**
- Branding form load: < 200ms
- Theme form load: < 200ms
- Users list (25 per page): < 500ms
- Translations table (100 keys): < 300ms

**Cache Effectiveness:**
- First request (cache miss): baseline
- Second request (cache hit): < 10% of first request time

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Settings Persistence Round Trip
*For any* valid settings data (branding or theme), when saved to the database via SettingsRepository and then retrieved via the same method, the retrieved value SHALL be identical to the saved value in structure and content.
**Validates: Requirements 1.7, 1.9, 2.5**

**Reasoning:** This is a fundamental correctness property that ensures data is not lost or corrupted during storage/retrieval. It's universal across all settings records and variations in data values are meaningful for testing.

### Property 2: Cache Invalidation Ensures Fresh Retrieval
*For any* cached setting, when that setting is updated in the database via SettingsRepository, the cache SHALL be invalidated such that the next retrieval via SettingsRepository reflects the new value and not the previously cached value.
**Validates: Requirements 1.8, 2.6, 5.4, 5.6**

**Reasoning:** Cache effectiveness depends on proper invalidation. This property verifies the cache doesn't serve stale data after updates. Variations in setting keys and values are meaningful.

### Property 3: Translation Override Precedence Over Default
*For any* translation key that has an override in the translation_overrides table, when the translation is requested at runtime via `trans()` or `__()` helper, the override value SHALL be returned instead of the default value from `lang/fa/admin.php`.
**Validates: Requirement 4.9**

**Reasoning:** Translation resolution is critical for correctness. Variations in override values matter for testing the selection logic.

### Property 4: File Upload Validation and Storage
*For any* file upload attempt, if the file does not match the allowed MIME types for its category (PNG/JPG for logo, ICO/PNG for favicon) or exceeds the maximum size limit for its category (2MB for logo, 1MB for favicon), the upload SHALL be rejected, the database SHALL not be updated, and any previously stored file SHALL remain unchanged.
**Validates: Requirements 1.3, 1.4, 1.5**

**Reasoning:** File validation is critical for security and data integrity. Testing with varying file types, sizes at and around boundaries reveals edge cases.

### Property 5: Generated Password Meets Strength Requirements
*For any* password generated by the UserService, the password SHALL be at least 12 characters in length, contain at least one uppercase letter (A-Z), at least one lowercase letter (a-z), at least one digit (0-9), and at least one special character from the set (!@#$%^&*).
**Validates: Requirement 3.4**

**Reasoning:** Password strength is a critical security property. Generating passwords across many iterations ensures the generator consistently meets all four requirements.

### Property 6: User Status Toggle Is Self-Inverse
*For any* user with a valid `active` status (true or false), applying the status toggle operation twice SHALL result in the same `active` value as before the first toggle.
**Validates: Requirement 3.9**

**Reasoning:** This is an idempotence property. For any initial state, toggling twice returns to that state. This is a fundamental correctness property of toggle operations.

### Property 7: User Search Filtering Is Accurate
*For any* search query and user database, the user search operation shall return only users whose name or email contains the search query (case-insensitive), and shall not return any users that do not match the query.
**Validates: Requirement 3.11**

**Reasoning:** Search correctness is universal. Varying the search terms and user data reveals edge cases in the filtering logic.

### Property 8: Translation Manager Displays All Admin Keys
*For any* complete set of translation keys in `lang/fa/admin.php` with prefix `admin.`, the translation manager page SHALL display all of these keys in the table, and SHALL not display duplicate keys.
**Validates: Requirement 4.3**

**Reasoning:** Data display completeness is important. Varying the number and content of translation keys tests the display logic comprehensively.

### Property 9: Translation Override Deletion Reverts to Default
*For any* translation key that has an override in the database, when the override is deleted and the translation is subsequently requested at runtime, the default value from the language file SHALL be returned.
**Validates: Requirement 4.7**

**Reasoning:** This property verifies that deletion properly removes the override so the default is used again. Combined with Property 3, it ensures the override system works bidirectionally.

### Property 10: Theme CSS Variables Are Present in Output
*For any* valid theme settings (font_family, primary_color, sidebar_style), when the admin layout is rendered, the HTML `<head>` section SHALL contain CSS custom property definitions for the theme values, and subsequent CSS rules SHALL be able to reference these properties.
**Validates: Requirement 2.7**

**Reasoning:** This tests that CSS variable generation works for all valid theme combinations. Variations in theme values matter for ensuring output generation logic.

### Property Reflection: Redundancy Analysis

After reviewing all 10 properties, the following redundancies were identified and consolidated:

- **Initial Properties 1.8, 2.6, 5.4, 5.6 (Cache Invalidation):** These were testing the same underlying pattern—that cache is invalidated when data changes. Consolidated into **Property 2** which covers all settings types and invalidation scenarios.

- **Initial Properties 4.6 and 4.8 (Translation Override Save + Cache):** These tested saving an override and then cache invalidation. The save operation is tested in **Property 1** (round trip) and cache invalidation in **Property 2**, so these are subsumed by the consolidated properties.

- **Initial Property 4.7 (Delete Override):** Consolidated with **Property 9** for clarity since delete is the inverse of save.

The final 10 properties represent the unique, non-redundant aspects of the system's correctness.

---

## Implementation Notes

### Default Seeding (Requirement 9)
Database seeder populates `settings` table with:
```php
[
    ['key' => 'branding', 'value' => json_encode([...defaults...])],
    ['key' => 'theme', 'value' => json_encode([...defaults...])],
]
```

### No Additional Migrations (Requirement 10)
Only 2 migrations created:
1. Create `settings` table
2. Create `translation_overrides` table

No modifications to `users`, `roles`, or other existing tables.

### Indexes
Both tables should have indexes on frequently queried columns:
- `settings.key` (unique index for fast lookups)
- `translation_overrides.translation_key` (unique index)

### Backwards Compatibility
- No modifications to existing User model or authentication
- Existing role/permission systems remain unchanged
- New tables are additive only

---

## Scalability Considerations

**Future Enhancements (Out of Scope):**
- Audit logging for setting changes
- Bulk user import/export
- Advanced translation filtering (by module, by status)
- Multi-tenant support
- API endpoints for settings management
- Settings versioning/rollback

**Performance Optimization (Future):**
- Redis for distributed caching
- Database query optimization for large user counts
- Batch file cleanup jobs
- CDN for static assets (logo, favicon)
