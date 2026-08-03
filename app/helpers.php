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

if (! function_exists('feedback_field_attributes')) {
    /**
     * Accessible wiring for an invalid form control of the shared Feedback_Channel.
     *
     * Emits `aria-invalid="true" aria-describedby="{field}-error"` when the current
     * request has a validation error for the field, and nothing otherwise.
     *
     * Usage: <input name="phone" {{ feedback_field_attributes('phone') }}>
     *        <x-admin.feedback field="phone" />
     */
    function feedback_field_attributes(string $field, mixed $errors = null): \Illuminate\Support\HtmlString
    {
        return \App\Support\Feedback\FeedbackChannel::fieldAttributes($field, $errors);
    }
}
