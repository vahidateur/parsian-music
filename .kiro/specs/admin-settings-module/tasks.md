# Implementation Plan: Admin Settings Module

## Overview

Tasks are ordered by regression risk: additive migrations/models first, then repositories/services, then form requests, then routes/controllers wiring, then UI views, then shared/cache-touching integration points (runtime translation resolution, layout head injection, sidebar component) last. Each task touches at most 1-2 files. Language: PHP (Laravel), tests via PHPUnit. Property-based tests use randomized data-driven PHPUnit tests (looping across many generated inputs) since no dedicated PBT library is installed in this project.

## Tasks

- [ ] 1. Create database migrations
  - [ ] 1.1 Create `settings` table migration
    - Columns: `id`, `key` (string, unique index), `value` (json/text), timestamps
    - _Requirements: 5.1, 9.4_
  - [ ] 1.2 Create `translation_overrides` table migration
    - Columns: `id`, `translation_key` (string, unique index), `overridden_value` (text), timestamps
    - _Requirements: 5.2_

- [ ] 2. Create Eloquent models
  - [ ] 2.1 Create `Setting` model
    - `$table = 'settings'`, `$fillable = ['key','value']`, `$casts = ['value' => 'json']`
    - _Requirements: 5.1_
  - [ ] 2.2 Create `TranslationOverride` model
    - `$table = 'translation_overrides'`, `$fillable = ['translation_key','overridden_value']`
    - _Requirements: 5.2_
  - [ ]* 2.3 Write unit tests for `Setting` and `TranslationOverride` models
    - Verify JSON casting on `Setting.value`, mass-assignment via `$fillable`
    - _Requirements: 5.1, 5.2_

- [ ] 3. Create default settings seeder and cache config
  - [ ] 3.1 Create `SettingsSeeder`
    - Seed `branding` key with defaults (site_name, empty subtitle/urls, null paths) and `theme` key with defaults (Vazirmatn, #4F46E5, dark); do not create translation override rows
    - _Requirements: 9.1, 9.2, 9.3_
  - [ ] 3.2 Add settings cache TTL config file `config/settings.php`
    - Configurable TTL, default 24 hours
    - _Requirements: 5.5_
  - [ ]* 3.3 Write unit test for `SettingsSeeder`
    - Verify exact default values for `branding` and `theme`, and that `translation_overrides` remains empty
    - _Requirements: 9.1, 9.2, 9.3_

- [ ] 4. Implement SettingsRepository
  - [ ] 4.1 Implement `SettingsRepository` (singleton) with `get`, `all`, `put`, `forget`, `invalidateCache`
    - Cache key format `settings:{key}`, TTL from `config/settings.php`, cache-first retrieval with DB fallback
    - _Requirements: 1.7, 1.9, 5.3, 5.4, 5.5, 5.7_
  - [ ]* 4.2 Write property test for SettingsRepository round trip
    - **Property 1: Settings Persistence Round Trip**
    - **Validates: Requirements 1.7, 1.9, 2.5**
  - [ ]* 4.3 Write property test for SettingsRepository cache invalidation
    - **Property 2: Cache Invalidation Ensures Fresh Retrieval**
    - **Validates: Requirements 1.8, 2.6, 5.4, 5.6**
  - [ ]* 4.4 Write unit test for SettingsRepository DB failure fallback
    - Verify cached data is returned and error is logged when the database query fails
    - _Requirements: 8.4_

- [ ] 5. Implement TranslationOverrideRepository
  - [ ] 5.1 Implement `TranslationOverrideRepository` with `get`, `put`, `forget`, `invalidateCache`
    - Cache key `settings:translations`, invalidate on create/update/delete
    - _Requirements: 4.6, 4.7, 5.6_
  - [ ]* 5.2 Write unit tests for TranslationOverrideRepository
    - Test create, update, delete, and cache invalidation on each mutation
    - _Requirements: 4.6, 4.7, 5.6_

- [ ] 6. Implement UserRepository
  - [ ] 6.1 Implement `UserRepository` with `paginated`, `search`, `create`, `update`, `toggleStatus`, `resetPassword`
    - Search matches name or email, case-insensitive; pagination at 25 per page
    - _Requirements: 3.1, 3.11_
  - [ ]* 6.2 Write property test for UserRepository search filtering
    - **Property 7: User Search Filtering Is Accurate**
    - **Validates: Requirement 3.11**

- [ ] 7. Create form request validation classes
  - [ ] 7.1 Create `StoreBrandingSettingsRequest` and `StoreThemeSettingsRequest`
    - Validate site name/urls, logo (png,jpg,jpeg, max 2MB), favicon (ico,png, max 1MB), font/color/sidebar options
    - _Requirements: 1.2, 1.3, 1.4, 2.2, 2.3, 2.4_
  - [ ] 7.2 Create `StoreUserRequest` and `UpdateUserRequest`
    - Validate name, email, role (from RoleEnum), optional password on update
    - _Requirements: 3.3, 3.7_
  - [ ] 7.3 Create `UpdateTranslationOverrideRequest`
    - Validate override value field (nullable string)
    - _Requirements: 4.5, 4.6_

- [ ] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 9. Implement SettingsService
  - [ ] 9.1 Implement `SettingsService` branding methods (`getBrandingSettings`, `saveBrandingSettings`)
    - Persist via SettingsRepository, return user-friendly errors without filesystem/DB details
    - _Requirements: 1.7, 1.9, 1.10_
  - [ ] 9.2 Add theme methods to `SettingsService` (`getThemeSettings`, `saveThemeSettings`)
    - On cache invalidation failure, roll back the theme DB change
    - _Requirements: 2.5, 2.9_
  - [ ] 9.3 Add file upload methods to `SettingsService` (`uploadFile`, `deleteFile`)
    - Validate MIME type and size per category, generate timestamped filename, store independently, delete old file on replacement
    - _Requirements: 1.3, 1.4, 1.5, 1.10_
  - [ ]* 9.4 Write property test for file upload validation
    - **Property 4: File Upload Validation and Storage**
    - **Validates: Requirements 1.3, 1.4, 1.5**
  - [ ]* 9.5 Write unit tests for branding/theme save-retrieve and theme rollback on cache failure
    - _Requirements: 2.9_

- [ ] 10. Implement UserService
  - [ ] 10.1 Implement `UserService` (`createUser`, `updateUser`, `generatePassword`, `toggleStatus`, `sendCredentialsEmail`)
    - New users created with `active=true`; edit updates password only if field is non-empty; email failures do not roll back user creation
    - _Requirements: 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 3.10_
  - [ ]* 10.2 Write property test for generated password strength
    - **Property 5: Generated Password Meets Strength Requirements**
    - **Validates: Requirement 3.4**
  - [ ]* 10.3 Write property test for user status toggle self-inverse
    - **Property 6: User Status Toggle Is Self-Inverse**
    - **Validates: Requirement 3.9**
  - [ ]* 10.4 Write unit tests for user creation email failure handling and password reset
    - _Requirements: 3.6, 3.10_

- [ ] 11. Implement TranslationService and runtime integration
  - [ ] 11.1 Implement `TranslationService` (`get`, `saveOverride`, `deleteOverride`, `getDefaultValue`, `listAdminKeys`)
    - Override precedence over default file value; listAdminKeys reads all `admin.*` keys from `lang/fa/admin.php` without duplicates
    - _Requirements: 4.6, 4.7, 4.9_
  - [ ] 11.2 Wire `TranslationService` into runtime translation resolution used by `trans()`/`__()`
    - Check `translation_overrides` first, fall back to default language file value
    - _Requirements: 4.9_
  - [ ]* 11.3 Write property test for translation override precedence
    - **Property 3: Translation Override Precedence Over Default**
    - **Validates: Requirement 4.9**
  - [ ]* 11.4 Write property test for translation override deletion reverting to default
    - **Property 9: Translation Override Deletion Reverts to Default**
    - **Validates: Requirement 4.7**

- [ ] 12. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Register admin settings routes with access control
  - [ ] 13.1 Add `admin/settings/*` route group with `auth:web` and `CheckRole:admin` middleware
    - Cover branding, theme, users, translations endpoints
    - _Requirements: 6.1, 6.2_
  - [ ]* 13.2 Write feature tests for route middleware enforcement
    - Unauthenticated redirect to login; authenticated non-admin gets 403
    - _Requirements: 6.3, 6.4_

- [ ] 14. Implement SettingsController - branding and theme
  - [ ] 14.1 Implement `SettingsController::brandingIndex` and `brandingStore`
    - _Requirements: 1.1, 1.6, 1.7, 1.8, 1.10_
  - [ ] 14.2 Add `SettingsController::themeIndex` and `themeStore`
    - _Requirements: 2.1, 2.5, 2.6, 2.9_
  - [ ]* 14.3 Write feature tests for branding and theme endpoints
    - _Requirements: 1.7, 2.5_

- [ ] 15. Implement SettingsController - user management
  - [ ] 15.1 Add `usersIndex`, `usersCreate`, `usersStore`, `usersEdit`, `usersUpdate`, `usersToggleStatus`, `usersResetPassword` to `SettingsController`
    - Role field read-only on edit; toggle without confirmation; reset sends new credentials email
    - _Requirements: 3.1, 3.2, 3.5, 3.7, 3.8, 3.9, 3.10, 3.12_
  - [ ]* 15.2 Write feature tests for user management endpoints
    - _Requirements: 3.5, 3.9, 3.10_

- [ ] 16. Implement SettingsController - translation management
  - [ ] 16.1 Add `translationsIndex`, `translationsEdit`, `translationsStore`, `translationsDelete` to `SettingsController`
    - Search filters by key or default value, case-insensitive; empty override value deletes the override record
    - _Requirements: 4.1, 4.2, 4.4, 4.5, 4.6, 4.7, 4.11_
  - [ ]* 16.2 Write feature tests for translation management endpoints
    - _Requirements: 4.4, 4.6, 4.7_
  - [ ]* 16.3 Write property test for translation manager key completeness
    - **Property 8: Translation Manager Displays All Admin Keys**
    - **Validates: Requirement 4.3**

- [ ] 17. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 18. Add localization strings
  - [ ] 18.1 Add `settings` sub-array (branding, theme, users, translations labels/messages) to `lang/fa/admin.php`
    - Include `file_upload_error`, `file_invalid_type`, `file_too_large` message keys
    - _Requirements: 7.3, 8.5_

- [ ] 19. Build settings layout and branding views
  - [ ] 19.1 Create RTL settings layout wrapper `resources/views/admin/settings/layout.blade.php`
    - Root container with `dir="rtl"`
    - _Requirements: 7.1_
  - [ ] 19.2 Create branding index view and logo/favicon preview partial
    - Form fields per Requirement 1.2, preview thumbnails, RTL-aware Tailwind utilities
    - _Requirements: 1.2, 1.6, 7.2, 7.4_

- [ ] 20. Build theme view and layout CSS integration
  - [ ] 20.1 Create theme index view
    - Font dropdown, color picker (default `#4F46E5`), sidebar style dropdown
    - _Requirements: 2.2, 2.3, 2.4, 7.2, 7.4_
  - [ ] 20.2 Inject theme CSS custom properties into the shared admin layout `<head>`
    - Output font family and primary color as CSS custom properties using stored theme settings
    - _Requirements: 2.7, 2.8, 7.5_
  - [ ]* 20.3 Write property test for theme CSS variable output
    - **Property 10: Theme CSS Variables Are Present in Output**
    - **Validates: Requirement 2.7**

- [ ] 21. Build user management views
  - [ ] 21.1 Create users index view and table row partial
    - Columns: name, email, role, status, actions; search input
    - _Requirements: 3.1, 3.2, 3.11_
  - [ ] 21.2 Create user create view
    - _Requirements: 3.3_
  - [ ] 21.3 Create user edit view
    - Role field read-only, optional password field
    - _Requirements: 3.7_

- [ ] 22. Build translation manager views
  - [ ] 22.1 Create translations index view and table row partial
    - Columns: key, default value, override value (`—` when absent), actions; note that overrides take precedence
    - _Requirements: 4.2, 4.10, 4.11_
  - [ ] 22.2 Create translation edit modal view
    - Key and default value read-only, textarea for override value
    - _Requirements: 4.5_

- [ ] 23. Apply dynamic sidebar style class
  - [ ] 23.1 Apply stored `sidebar_style` as a dynamic class on the shared sidebar component
    - e.g. `sidebar-dark`, `sidebar-light`, `sidebar-compact`
    - _Requirements: 2.10_

- [ ] 24. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP; they are not implemented by the coding agent by default.
- Ordering follows lowest-regression-risk-first: migrations/models → repositories → form requests → services → routes/controllers → localization/views → shared layout/sidebar integration.
- `SettingsController.php` is extended incrementally across tasks 14.1, 14.2, 15.1, 16.1 — each touches the same file and must run sequentially, never in parallel.
- Property-based tests use randomized/generated inputs run across many iterations in PHPUnit, since no dedicated PBT library is installed.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "3.2", "6.1", "7.1", "7.2", "7.3"] },
    { "id": 1, "tasks": ["2.1", "2.2", "6.2"] },
    { "id": 2, "tasks": ["2.3", "3.1", "4.1", "5.1"] },
    { "id": 3, "tasks": ["3.3", "4.2", "4.3", "4.4", "5.2", "9.1"] },
    { "id": 4, "tasks": ["9.2", "10.1", "11.1"] },
    { "id": 5, "tasks": ["9.3", "10.2", "10.3", "10.4", "11.2"] },
    { "id": 6, "tasks": ["9.4", "9.5", "11.3", "11.4"] },
    { "id": 7, "tasks": ["13.1"] },
    { "id": 8, "tasks": ["14.1"] },
    { "id": 9, "tasks": ["14.2", "13.2"] },
    { "id": 10, "tasks": ["14.3", "15.1"] },
    { "id": 11, "tasks": ["15.2", "16.1"] },
    { "id": 12, "tasks": ["16.2", "16.3"] },
    { "id": 13, "tasks": ["18.1"] },
    { "id": 14, "tasks": ["19.1"] },
    { "id": 15, "tasks": ["19.2", "20.1", "21.1", "21.2", "21.3", "22.1", "22.2"] },
    { "id": 16, "tasks": ["20.2"] },
    { "id": 17, "tasks": ["20.3"] },
    { "id": 18, "tasks": ["23.1"] }
  ]
}
```
