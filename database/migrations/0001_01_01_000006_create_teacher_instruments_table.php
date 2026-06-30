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
        Schema::create('teacher_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
            $table->string('skill_level', 20)->index();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Prevent duplicate teacher + instrument pairs
            $table->unique(['teacher_id', 'instrument_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_instruments');
    }
};
