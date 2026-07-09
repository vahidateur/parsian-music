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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
            $table->integer('sessions_allocated')->default(4);
            $table->integer('sessions_used')->default(0);
            $table->integer('monthly_fee')->default(3000000);
            $table->enum('payment_status', ['paid', 'unpaid', 'overdue'])->default('unpaid');
            $table->date('renewal_date')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();

            // Unique constraint on student-teacher-instrument combination
            $table->unique(['student_id', 'teacher_id', 'instrument_id']);

            // Indexes for common queries
            $table->index(['student_id']);
            $table->index(['teacher_id']);
            $table->index(['instrument_id']);
            $table->index(['renewal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
