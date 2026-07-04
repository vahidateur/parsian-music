# Requirements Document

## Introduction

این ویژگی یک بخش تنظیمات ادمین به پنل مدیریتی آموزشگاه پارسیان موزیک اضافه می‌کند. ادمین‌ها می‌توانند برندسازی سایت (نام، لوگو، لینک‌های اجتماعی)، تنظیمات تم (فونت، رنگ‌های اصلی، استایل سایدبار)، مدیریت کاربران (ایجاد، ویرایش، غیرفعال‌سازی)، و مدیریت ترجمه‌های سیستم را انجام دهند. تمام تنظیمات در دیتابیس ذخیره می‌شوند و با کش‌ارزیابی بهینه می‌شوند.

## Glossary

- **AdminSettingsModule**: ماژول تنظیمات ادمین شامل چهار بخش اصلی (Branding, Theme, Users, Translations).
- **BrandingSettings**: نام سایت، زیرنویس، لوگو، فاویکون، لینک‌های شبکه‌های اجتماعی.
- **ThemeSettings**: انتخاب فونت، رنگ اصلی، استایل سایدبار.
- **TranslationOverride**: بازنویسی محلی برای کلیدهای ترجمه‌ی `admin.*` در دیتابیس.
- **SettingsCache**: کش در‌حافظه برای تنظیمات و ترجمه‌های موثر.
- **Admin**: کاربر ادمین با دسترسی کامل به تنظیمات.
- **User**: کاربر سیستم که می‌تواند نقش‌های مختلفی داشته باشد (admin, staff, teacher).
- **FileHandler**: کامپوننت امن برای آپلود و ذخیره‌ی فایل‌ها.
- **RoleEnum**: شمارش نقش‌های کاربری موجود در `App\Enums\RoleEnum`.

---

## Requirements

### Requirement 1: تنظیمات برندسازی

**User Story:** به عنوان یک Admin، می‌خواهم نام سایت، لوگو، فاویکون و لینک‌های اجتماعی را تغییر دهم، تا سایت با هویت آموزشگاه هماهنگ باشد.

#### Acceptance Criteria

1. THE AdminSettingsModule SHALL provide a branding settings page at route `admin.settings.branding`.
2. THE branding settings form SHALL display fields for: site name (text), site subtitle (text), logo file upload, favicon file upload, WhatsApp URL (text), Telegram URL (text), Instagram URL (text).
3. WHEN a logo file is uploaded, THE FileHandler SHALL validate the file type (PNG, JPG, JPEG only) and file size (max 2MB).
4. WHEN a favicon file is uploaded, THE FileHandler SHALL validate the file type (ICO, PNG only) and file size (max 1MB).
5. WHEN each file is valid, THE FileHandler SHALL store it independently in `storage/app/public/settings/` with a timestamped filename to prevent collisions.
6. THE branding settings page SHALL display a preview thumbnail of the currently stored logo and favicon.
7. WHEN the branding form is submitted, THE BrandingSettings model SHALL store or update the values in the `settings` table with a key of `branding`.
8. WHEN branding settings are updated, THE SettingsCache SHALL be invalidated so subsequent reads fetch fresh values.
9. THE branding settings values SHALL be retrievable via a `SettingsRepository::get('branding')` call that returns an associative array or object.
10. IF file upload fails, THEN THE form SHALL return a user-friendly error message without exposing filesystem details.

---

### Requirement 2: تنظیمات تم

**User Story:** به عنوان یک Admin، می‌خواهم فونت، رنگ اصلی و استایل سایدبار را انتخاب کنم، تا ظاهر سایت دلخواه باشد.

#### Acceptance Criteria

1. THE AdminSettingsModule SHALL provide a theme settings page at route `admin.settings.theme`.
2. THE theme settings form SHALL display a dropdown for font family with options: `Vazirmatn` (پیش‌فرض), `IRANSansX`, `Tahoma`.
3. THE theme settings form SHALL display a color picker for primary color with a default value of `#4F46E5`.
4. THE theme settings form SHALL display a dropdown for sidebar style with options: `dark` (سایدبار تیره), `light` (سایدبار روشن), `compact` (فشرده).
5. WHEN the theme form is submitted, THE ThemeSettings model SHALL store or update the values in the `settings` table with a key of `theme`.
6. WHEN theme settings are updated, THE SettingsCache SHALL be invalidated.
7. THE theme CSS variables SHALL be output in the layout's `<head>` tag using the stored theme settings.
8. WHEN a user loads the dashboard, THE layout's CSS SHALL apply both the stored font family and primary color together using Tailwind and CSS custom properties.
9. WHEN theme settings are updated, IF cache invalidation fails, THEN THE system SHALL roll back the theme changes in the database to preserve consistency.
10. THE sidebar component SHALL apply the stored sidebar style class dynamically (e.g., `sidebar-dark`, `sidebar-light`, `sidebar-compact`).

---

### Requirement 3: مدیریت کاربران

**User Story:** به عنوان یک Admin، می‌خواهم کاربران جدید ایجاد کنم، اطلاعات آن‌ها را ویرایش کنم، وضعیت فعال/غیرفعال را تغییر دهم، و رمز‌عبور را بازنشانی کنم.

#### Acceptance Criteria

1. THE AdminSettingsModule SHALL provide a user management page at route `admin.settings.users` with a paginated list of all users (25 per page).
2. THE user list table SHALL display columns: name, email, role, status (active/inactive), and action buttons (edit, toggle status, reset password).
3. WHEN an Admin clicks "Create User", THE form SHALL display fields: name (text), email (email), role (dropdown with values from RoleEnum), password (auto-generated strong password).
4. THE auto-generated password SHALL be a minimum of 12 characters with uppercase, lowercase, numbers, and special characters.
5. WHEN the create user form is submitted with valid data, THE User model SHALL be created with an `active` status of `true`.
6. WHEN a new user is created successfully and the notification email fails, THE system SHALL leave the user created in the database and handle the email failure separately (the user can reset their password later).
7. WHEN an Admin clicks "Edit User" for an existing user, THE form SHALL display fields: name, email, role (read-only to prevent role manipulation), and an optional password field.
8. WHEN an Admin edits a user and changes the password field, THE User model SHALL update the password only if the field is not empty.
9. WHEN an Admin clicks "Toggle Status" for a user, THE User model's `active` column SHALL flip between `true` and `false` without requiring additional confirmation.
10. WHEN an Admin clicks "Reset Password" for a user, THE system SHALL generate a new strong password, update the User model, and send an email with the new credentials.
11. THE user list form SHALL support searching by name or email using a search input field.
12. IF an error occurs during user creation or update, THEN THE form SHALL return a validation error message without exposing database details.

---

### Requirement 4: مدیر ترجمه

**User Story:** به عنوان یک Admin، می‌خواهم کلیدهای ترجمه‌ی `admin.*` را جستجو کرم، بازنویسی‌های محلی ایجاد یا بازنویسی کنم، و پیش‌فرض فایل‌های زبان را مشاهده کنم.

#### Acceptance Criteria

1. THE AdminSettingsModule SHALL provide a translation manager page at route `admin.settings.translations`.
2. THE translation manager page SHALL display a table with columns: translation key, default value (از `lang/fa/admin.php`), override value (اگر موجود است), و action buttons (edit, delete).
3. THE translation manager table SHALL display all keys from `lang/fa/admin.php` with prefix `admin.`.
4. WHEN an Admin enters a search term, THE translation manager SHALL filter the table to show only keys or default values containing the search term (case-insensitive). IF no matches are found, THE filtered table SHALL display empty with a message.
5. WHEN an Admin clicks "Edit" for a translation, THE form SHALL display the translation key (read-only), default value (read-only), and a textarea for the override value.
6. WHEN the override form is submitted with a non-empty value, THE TranslationOverride model SHALL create or update a record in the `translation_overrides` table with the key and overridden value.
7. WHEN the override form is submitted with an empty value and an override exists, THE TranslationOverride record SHALL be deleted, reverting to the default value.
8. WHEN a translation override is saved or deleted, THE SettingsCache SHALL be invalidated so subsequent translation lookups fetch fresh values.
9. WHEN a translation is requested at runtime via `__()` helper or `trans()`, THE system SHALL check the `translation_overrides` table first; IF an override exists, THE override value SHALL be returned; otherwise, THE default value from `lang/fa/admin.php` SHALL be returned.
10. THE translation manager page SHALL display a note explaining that overrides take precedence over file values.
11. IF no overrides exist for a translation, THE override column SHALL display `—` (dash).

---

### Requirement 5: ساختار دیتابیس و کش

**User Story:** به عنوان یک Developer، می‌خواهم تنظیمات بدون ایجاد جداول متعددی ذخیره شوند و با کش بهینه شوند.

#### Acceptance Criteria

1. THE database migration SHALL create a `settings` table with columns: `id` (primary key), `key` (string, unique), `value` (JSON/text), `created_at`, `updated_at`.
2. THE database migration SHALL create a `translation_overrides` table with columns: `id` (primary key), `translation_key` (string, unique), `overridden_value` (text), `created_at`, `updated_at`.
3. WHEN a setting is created or updated, THE SettingsCache key SHALL be derived from the setting's `key` column (e.g., cache key `settings:branding` for key `branding`).
4. WHEN a setting is created, updated, or deleted via the UI or API, THE corresponding SettingsCache entry SHALL be immediately invalidated using Laravel's `cache()->forget()` method.
5. WHEN a setting is retrieved and no cache entry exists, THE SettingsRepository SHALL query the database and populate the cache with a configurable TTL (default 24 hours). IF cache already exists, THE system MAY re-query the database for fresh values if needed.
6. WHEN a translation override is created, updated, or deleted, THE cache key `settings:translations` SHALL be invalidated.
7. THE SettingsRepository SHALL provide a singleton pattern ensuring only one instance manages cache and database interactions.

---

### Requirement 6: نقابل‌دسترسی و کنترل دسترسی

**User Story:** به عنوان یک Admin، می‌خواهم اطمینان داشته باشم فقط ادمین‌ها به تنظیمات دسترسی دارند.

#### Acceptance Criteria

1. ALL routes in the AdminSettingsModule SHALL require authentication via `auth:web` middleware.
2. ALL routes in the AdminSettingsModule SHALL require the user to have the `admin` role via the `CheckRole` middleware with role `admin`.
3. WHEN an unauthenticated user tries to access any settings route, THE system SHALL redirect to the login page.
4. WHEN an authenticated non-admin user tries to access any settings route, THE system SHALL return a 403 Forbidden response.

---

### Requirement 7: پشتیبانی RTL و فارسی

**User Story:** به عنوان یک Admin، می‌خواهم تمام رابط کاربری تنظیمات به‌صورت RTL نمایش داده شود و تمام متون فارسی باشند.

#### Acceptance Criteria

1. ALL views for the AdminSettingsModule SHALL include the `dir="rtl"` attribute in their root container.
2. THE branding and theme settings forms SHALL use Tailwind CSS utilities that respect RTL layout (e.g., `ml-` becomes `mr-`, `text-left` becomes `text-right`).
3. THE translation keys for all labels, buttons, and messages SHALL be stored in `lang/fa/admin.php` under a `settings` sub-array.
4. THE color picker and file upload inputs SHALL maintain proper alignment in RTL mode.
5. WHEN theme primary color changes, THE CSS custom properties SHALL be applied without affecting text direction.

---

### Requirement 8: عملکرد و خطاهای برنامه

**User Story:** به عنوان یک Admin، می‌خواهم تنظیمات بدون تاخیر قابل ملاحظه بارگذاری و ذخیره شوند.

#### Acceptance Criteria

1. THE user list page SHALL load and display in under 500ms (with pagination at 25 users per page).
2. THE translation manager table SHALL load and display up to 100 translation keys in under 300ms.
3. THE branding and theme settings forms SHALL load in under 200ms.
4. IF a database query fails, THE AdminSettingsModule SHALL implement fallback behavior: use cached data if available, disable affected features gracefully, log the error via `Log::error()`, and return a user-friendly error message without exposing stack traces.
5. IF file upload fails due to disk space or permissions, THE system SHALL return an error message: `admin.settings.file_upload_error`.
6. IF a translation key is malformed or missing, THE system SHALL skip that key and log a warning without crashing the page.

---

### Requirement 9: ایجاد و بازنشانی تنظیمات پیش‌فرض

**User Story:** به عنوان یک Developer، می‌خواهم تنظیمات پیش‌فرض هنگام نصب یا مهاجرت در دیتابیس ذخیره شوند.

#### Acceptance Criteria

1. THE database seeder SHALL populate the `settings` table with default branding values: site_name = `آموزشگاه پارسیان موزیک`, site_subtitle = ``, logo = `null`, favicon = `null`, whatsapp_url = ``, telegram_url = ``, instagram_url = ``.
2. THE database seeder SHALL populate the `settings` table with default theme values: font_family = `Vazirmatn`, primary_color = `#4F46E5`, sidebar_style = `dark`.
3. THE database seeder SHALL NOT create any translation override entries (the table SHALL remain empty initially).
4. WHEN the migration runs, the `settings` table SHALL be created with proper indexes on the `key` column.

---

### Requirement 10: بدون مهاجرت‌های اضافی

**User Story:** به عنوان یک Developer، می‌خواهم نیاز به حداقل 2 جدول جدید برآورده شود بدون مهاجرت‌های بیش‌تری.

#### Acceptance Criteria

1. THE AdminSettingsModule SHALL use only 2 database tables: `settings` and `translation_overrides`.
2. THE existing Laravel User model, authentication tables, and role/permission systems SHALL NOT be modified or extended.
3. NO additional migration files SHALL be required beyond the two table creation migrations.
