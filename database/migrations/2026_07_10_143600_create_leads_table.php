<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('full_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('age')->nullable();

            // Preferences (nullable FKs — lead may not know yet)
            $table->foreignId('preferred_instrument_id')
                  ->nullable()
                  ->constrained('instruments')
                  ->nullOnDelete();

            $table->foreignId('preferred_teacher_id')
                  ->nullable()
                  ->constrained('teachers')
                  ->nullOnDelete();

            // CRM fields
            $table->string('source', 30);    // LeadSourceEnum
            $table->string('status', 30)->default('new');    // LeadStatusEnum
            $table->string('priority', 10)->default('medium'); // LeadPriorityEnum

            $table->foreignId('assigned_to')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();

            // When converted, link to the created student (extension point)
            $table->foreignId('converted_student_id')
                  ->nullable()
                  ->constrained('students')
                  ->nullOnDelete();

            $table->timestamp('converted_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('next_follow_up_at');
            $table->index('source');
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
