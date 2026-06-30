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
        Schema::create('recurring_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->tinyInteger('weekday')->index();
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('room', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Optimize scheduling lookups
            $table->index(['enrollment_id', 'weekday']);
            $table->index(['enrollment_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_schedules');
    }
};
