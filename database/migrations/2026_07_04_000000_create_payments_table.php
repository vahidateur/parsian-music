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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->cascadeOnDelete();
            $table->decimal('amount_total', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('remaining_balance', 10, 2);
            $table->date('payment_date');
            $table->string('payment_method', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_enrollment_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
