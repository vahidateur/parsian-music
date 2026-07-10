<?php

/**
 * @deprecated This migration creates the legacy `lead_requests` table which was
 * superseded by the `leads` table introduced in Sprint 20.1
 * (migration: 2026_07_10_143600_create_leads_table.php).
 *
 * The `lead_requests` table has no associated Model, Service, Controller, or Route.
 * This migration is retained only to preserve DB migration history.
 * The table should be dropped in a future cleanup migration once it is confirmed
 * that no production data exists in `lead_requests`.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lead_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 20)->index();
            $table->string('instrument');
            $table->string('skill_level');
            $table->text('preferred_days');
            $table->text('preferred_times');
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_requests');
    }
};
