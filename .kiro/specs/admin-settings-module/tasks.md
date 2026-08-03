# Implementation Plan: Admin Settings Module

> **STATUS: SUPERSEDED (reconciled 2026-07-28).**
>
> This spec designed a settings module from scratch: a `settings` key/value table,
> a `translation_overrides` table, `SettingsRepository`, `TranslationOverrideRepository`,
> `UserRepository`, `SettingsService`, `UserService`, `TranslationService`, and a
> `SettingsController` split into branding / theme / users / translations actions.
>
> The repository already ships a **different, working settings module**:
>
> - `app_settings` table + `App\Models\AppSetting` (`getGroup()` / `setGroup()`, JSON payload per section)
> - `config/settings.php` — the section catalogue (title, group, colour, icon, `coming_soon`, features)
> - `App\Http\Controllers\Admin\SettingsController` — `index`, `show($section)`, `update($section)`, `updateInstitute`
> - `App\Services\SettingsManager`, `App\Services\InstituteProfileService`, `App\Models\InstituteProfile`
> - Routes: `admin/settings` group in `routes/web.php` (`role:admin,super_admin`)
> - Views: `resources/views/admin/settings/{index,show}.blade.php`
>
> Building the spec's architecture on top of this would create two competing
> settings systems. Nothing from tasks 1–22 should be implemented.
>
> **Task 23 (dynamic `sidebar_style` class) is explicitly rejected.** The admin panel
> now has a two-theme architecture — `data-admin-theme="dark"` (default) and
> `"glass"` — rendered server-side from the `pm_admin_theme` cookie in
> `layouts/dashboard.blade.php` and scoped in `resources/css/admin/glass.css`.
> See `17_DECISION_LOG.md` (2026-07-24). Applying a `sidebar-dark` / `sidebar-light`
> / `sidebar-compact` class from a settings row would fight that system and
> regress both themes.

## Reconciliation

| Spec tasks | Actual state | Resolution |
|---|---|---|
| 1–2 `settings` + `translation_overrides` tables and models | Different implementation exists | `app_settings` + `AppSetting` (grouped JSON) already in production. No new tables. |
| 3.1 `SettingsSeeder` | Not applicable | Defaults come from the `config/settings.php` catalogue and per-section view defaults, not seeded rows. |
| 3.2 `config/settings.php` | Exists | Already present, but as the **section catalogue** rather than a cache-TTL file. Repurposing it would break the settings index/show pages. |
| 4–5 `SettingsRepository`, `TranslationOverrideRepository` | Superseded | `AppSetting::getGroup()/setGroup()` + `SettingsManager` fill this role. |
| 6, 10, 15, 21 user management (repository/service/controller/views) | Already implemented elsewhere | `App\Http\Controllers\Admin\UserController` + `admin/users` routes (`role:super_admin,admin`) cover index/create/store/edit/update/destroy/toggle/resetPassword. The catalogue's `users` section stays `coming_soon` because it is about custom **roles & permissions**, which is genuinely future work. |
| 7 form requests | Partially different | `UpdateInstituteRequest` exists; other sections validate through `SettingsController::validationRules($section)`. Adding parallel request classes would split validation across two places. |
| 9 `SettingsService` branding/theme/uploads | Superseded | Branding lives in the `institute` section (`InstituteProfileService`); login-page assets upload through `SettingsController::update('login')`. |
| 11 `TranslationService` + runtime `__()` interception | Not applicable / rejected as designed | Translations are file-based (`lang/fa`, `lang/en`). Hooking DB overrides into every `trans()` call is a global-blast-radius change with a cache cost, and no requirement in the current product depends on it. Should be re-specced if ever needed. |
| 13 routes | Exists | `admin/settings` group already registered with role middleware. |
| 14 branding/theme controller actions | Superseded | Handled by the generic `show`/`update` section dispatch. |
| 16, 22 translation manager | Not applicable | See task 11. |
| 18 `settings` localization sub-array | Not applicable | Section labels/descriptions come from `config/settings.php` (Persian), which is the single source of truth for the settings UI. |
| 19–20 settings layout / branding / theme views | Superseded | `admin/settings/{index,show}.blade.php` render every catalogue section. `appearance` is intentionally flagged `coming_soon`. |
| 23 dynamic `sidebar_style` class | **REJECTED — conflicts with Dark/Glass theme architecture** | Preserve the newer architecture. If per-user appearance settings are wanted later, they must integrate with `data-admin-theme`, not replace it. |

## Follow-up worth specifying separately

If the product still wants the *intent* behind this spec, the useful remainders are:

1. Wiring the catalogue's `appearance` section to the existing `data-admin-theme`
   system (default theme per user, instead of cookie-only).
2. A roles & permissions layer behind the catalogue's `users` section.

Both need their own spec; neither should reuse this file.
