<?php

namespace App\Services;

use App\Models\InstituteProfile;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for the institute's profile.
 *
 * Registered as a singleton in AppServiceProvider so the DB is
 * hit at most once per request.  If you add caching later (Redis,
 * file cache, etc.) or multi-branch support, change only this class.
 *
 * Usage anywhere:
 *   institute()->name
 *   institute()->logo_url
 *   app(InstituteSettings::class)->phone
 */
class InstituteSettings
{
    private InstituteProfile $profile;

    public function __construct()
    {
        // Swap Cache::remember() in here when you add Redis:
        // $this->profile = Cache::remember('institute_profile', 3600, fn () => InstituteProfile::instance());
        $this->profile = InstituteProfile::instance();
    }

    /**
     * Reload the profile from the database and reset the resolved singleton.
     * Call this after saving so the in-request cache stays fresh.
     */
    public function refresh(): static
    {
        $this->profile = InstituteProfile::instance()->fresh();

        return $this;
    }

    /**
     * Expose all model attributes directly on this service.
     */
    public function __get(string $key): mixed
    {
        return $this->profile->{$key};
    }

    /**
     * Allow isset() checks used by Blade {{ $x ?? '' }} patterns.
     */
    public function __isset(string $key): bool
    {
        return isset($this->profile->{$key});
    }

    /**
     * Return the raw model for relationship/eager-loading use-cases.
     */
    public function model(): InstituteProfile
    {
        return $this->profile;
    }
}
