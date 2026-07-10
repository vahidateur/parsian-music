<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateInstituteRequest;
use App\Models\AppSetting;
use App\Models\InstituteProfile;
use App\Services\InstituteProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly InstituteProfileService $instituteService) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        return view('admin.settings.index', [
            'catalogue' => config('settings.catalogue'),
        ]);
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function show(string $section): View
    {
        $catalogue = config('settings.catalogue');

        abort_unless(array_key_exists($section, $catalogue), 404);

        $viewData = [
            'section'   => $section,
            'meta'      => $catalogue[$section],
            'catalogue' => $catalogue,
            'settings'  => AppSetting::getGroup($section),
        ];

        if ($section === 'institute') {
            $viewData['profile'] = InstituteProfile::instance();
        }

        return view('admin.settings.show', $viewData);
    }

    // ── Generic Update ────────────────────────────────────────────────────────

    public function update(Request $request, string $section): RedirectResponse
    {
        $catalogue = config('settings.catalogue');

        abort_unless(array_key_exists($section, $catalogue), 404);
        abort_if($catalogue[$section]['coming_soon'] ?? false, 403, 'این بخش هنوز پیاده‌سازی نشده است.');

        $rules     = $this->validationRules($section);
        $validated = $rules
            ? $request->validate($rules)
            : $request->except(['_token', '_method']);

        // Normalise boolean-like fields that arrive as checkbox (absent = false)
        if ($section === 'telegram') {
            $validated['telegram_enabled'] = $request->boolean('telegram_enabled');
        }

        if ($section === 'notifications') {
            $validated['events']   = $request->input('events', []);
            $validated['channels'] = $request->input('channels', []);
        }

        AppSetting::setGroup($section, $validated);

        return redirect()
            ->route('admin.settings.show', $section)
            ->with('success', 'تنظیمات با موفقیت ذخیره شد.');
    }

    // ── Institute Update (dedicated FormRequest) ──────────────────────────────

    public function updateInstitute(UpdateInstituteRequest $request): RedirectResponse
    {
        $this->instituteService->update($request);

        return redirect()
            ->route('admin.settings.show', 'institute')
            ->with('success', 'اطلاعات آموزشگاه با موفقیت ذخیره شد.');
    }

    // ── Section-level validation rules ────────────────────────────────────────

    private function validationRules(string $section): array
    {
        return match ($section) {
            'general' => [
                'app_name'                 => ['required', 'string', 'max:100'],
                'locale'                   => ['required', 'in:fa,en'],
                'timezone'                 => ['required', 'string', 'max:50'],
                'date_format'              => ['required', 'in:jalali,gregorian'],
                'week_start'               => ['required', 'in:saturday,monday'],
                'per_page'                 => ['required', 'integer', 'min:5', 'max:100'],
                'session_default_duration' => ['required', 'integer', 'min:15', 'max:180'],
            ],
            'email' => [
                'mail_host'         => ['nullable', 'string', 'max:255'],
                'mail_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
                'mail_username'     => ['nullable', 'string', 'max:255'],
                'mail_password'     => ['nullable', 'string', 'max:255'],
                'mail_encryption'   => ['nullable', 'in:tls,ssl,'],
                'mail_from_name'    => ['nullable', 'string', 'max:100'],
                'mail_from_address' => ['nullable', 'email', 'max:255'],
            ],
            'telegram' => [
                'telegram_bot_token' => ['nullable', 'string', 'max:500'],
                'telegram_chat_id'   => ['nullable', 'string', 'max:100'],
                'telegram_enabled'   => ['nullable'],
            ],
            'notifications' => [
                'events'   => ['nullable', 'array'],
                'channels' => ['nullable', 'array'],
            ],
            default => [],
        };
    }
}
