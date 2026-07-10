<?php

namespace App\Services;

use App\Http\Requests\Admin\UpdateInstituteRequest;
use App\Models\InstituteProfile;
use Illuminate\Support\Facades\Storage;
use App\Services\InstituteSettings;

class InstituteProfileService
{
    /**
     * Persist the institute profile from a validated request.
     * Handles image uploads and old image cleanup.
     */
    public function update(UpdateInstituteRequest $request): InstituteProfile
    {
        $profile = InstituteProfile::instance();

        $data = $request->safe()->except(['logo', 'cover']);

        if ($request->hasFile('logo')) {
            $this->deleteOld($profile->logo_path);
            $data['logo_path'] = $request->file('logo')->store('institute', 'public');
        }

        if ($request->hasFile('cover')) {
            $this->deleteOld($profile->cover_path);
            $data['cover_path'] = $request->file('cover')->store('institute', 'public');
        }

        // Normalise working_days — null when nothing is checked
        $data['working_days'] = $request->input('working_days') ?? [];

        $profile->update($data);

        // Keep the in-request singleton fresh after a save.
        app(InstituteSettings::class)->refresh();

        return $profile;
    }

    private function deleteOld(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
