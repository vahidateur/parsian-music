<?php

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
