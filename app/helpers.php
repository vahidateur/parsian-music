<?php

use App\Services\InstituteSettings;
use App\Services\SettingsManager;

if (! function_exists('settings')) {
    /**
     * Return the SettingsManager hub.
     *
     * Examples:
     *   settings()->institute()->name
     *   settings()->institute()->logo_url
     *   settings()->institute()->working_days
     *   settings()->timezone()
     *   settings()->locale()
     *   settings()->email()['from_address']
     *   settings()->telegram()['enabled']
     */
    function settings(): SettingsManager
    {
        return app(SettingsManager::class);
    }
}

if (! function_exists('institute')) {
    /**
     * Shortcut to settings()->institute().
     * Kept for convenience — both spellings work.
     */
    function institute(): InstituteSettings
    {
        return settings()->institute();
    }
}
