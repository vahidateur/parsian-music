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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->foreignId('recurring_schedule_id')->nullable()->constrained('recurring_schedules')->nullOnDelete();
            $table->date('session_date')->index();
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('status', 20)->default('scheduled')->index();
            $table->string('room', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Optimize frequent dashboard and scheduling queries
            $table->index(['session_date', 'status']);
            $table->index(['enrollment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
