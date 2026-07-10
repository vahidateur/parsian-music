<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores admin-configurable settings, grouped by section (general, email, telegram, …).
 *
 * Design:  one row per section; the payload column holds the section's full key-value
 *          map as JSON.  This avoids schema churn when new settings fields are added —
 *          only the PHP code and Blade partials need updating.
 *
 * Read:    AppSetting::getGroup('email')  → ['mail_host' => '…', …]
 * Write:   AppSetting::setGroup('email', […])
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->unique()->comment('Settings section key, e.g. general, email, telegram');
            $table->json('payload')->nullable()->comment('Full key-value map for the section');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
