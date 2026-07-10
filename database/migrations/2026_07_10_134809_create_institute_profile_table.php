<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institute_profile', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name')->default('');
            $table->string('name_en')->nullable();
            $table->string('description')->nullable();

            // Media
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();

            // Contact
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Social
            $table->string('instagram')->nullable();
            $table->string('telegram')->nullable();
            $table->string('whatsapp')->nullable();

            // Location
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();

            // Schedule (JSON arrays)
            $table->json('working_days')->nullable();   // ['saturday','sunday',...]
            $table->string('working_hours_from')->nullable();  // '08:00'
            $table->string('working_hours_to')->nullable();    // '21:00'

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_profile');
    }
};
