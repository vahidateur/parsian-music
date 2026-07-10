<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateInstituteRequest;
use App\Models\InstituteProfile;
use App\Services\InstituteProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly InstituteProfileService $instituteService) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'catalogue' => config('settings.catalogue'),
        ]);
    }

    public function show(string $section): View
    {
        $catalogue = config('settings.catalogue');

        abort_unless(array_key_exists($section, $catalogue), 404);

        $viewData = [
            'section'   => $section,
            'meta'      => $catalogue[$section],
            'catalogue' => $catalogue,
        ];

        if ($section === 'institute') {
            $viewData['profile'] = InstituteProfile::instance();
        }

        return view('admin.settings.show', $viewData);
    }

    public function updateInstitute(UpdateInstituteRequest $request): RedirectResponse
    {
        $this->instituteService->update($request);

        return redirect()
            ->route('admin.settings.show', 'institute')
            ->with('success', 'اطلاعات آموزشگاه با موفقیت ذخیره شد.');
    }
}
