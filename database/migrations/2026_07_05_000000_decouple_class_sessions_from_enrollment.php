<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1 of decoupling manual session creation from enrollment.
     *
     * Adds nullable student_id / teacher_id / instrument_id directly on
     * class_sessions, and makes the existing enrollment_id nullable so it
     * remains in place (unchanged) for RecurringSchedule / SessionGeneratorService,
     * which are explicitly out of scope for this change.
     *
     * session_fee / discount columns are intentionally left untouched —
     * kept for backward compatibility, simply no longer written to by the
     * manual session creation form.
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('enrollment_id')
                ->constrained('students')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->after('student_id')
                ->constrained('teachers')->nullOnDelete();
            $table->foreignId('instrument_id')->nullable()->after('teacher_id')
                ->constrained('instruments')->nullOnDelete();

            $table->index(['student_id', 'status']);
            $table->index(['teacher_id', 'status']);
        });

        // Make enrollment_id nullable. Drop + recreate the FK constraint so
        // the nullability change is applied reliably on MySQL.
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('enrollment_id')->nullable()->change();
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreign('enrollment_id')
                ->references('id')->on('student_enrollments')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: reverting enrollment_id to NOT NULL will fail if any rows have
     * a null enrollment_id at rollback time (i.e. any manually-created
     * sessions from Phase 1 still exist).
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['instrument_id']);
            $table->dropIndex(['student_id', 'status']);
            $table->dropIndex(['teacher_id', 'status']);
            $table->dropColumn(['student_id', 'teacher_id', 'instrument_id']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('enrollment_id')->nullable(false)->change();
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreign('enrollment_id')
                ->references('id')->on('student_enrollments')
                ->cascadeOnDelete();
        });
    }
};
