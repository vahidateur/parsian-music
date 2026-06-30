<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds missing single-column indexes used by the admin panel queries.
 *
 * Composite indexes already exist on these tables (see the create migrations),
 * but several controllers filter on a single foreign key column, e.g.
 * `where('teacher_id', ...)` or `where('enrollment_id', ...)`, where MySQL
 * cannot efficiently use a left-prefixed composite. This migration adds the
 * dedicated single-column indexes those queries need.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->index('teacher_id');
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->index('enrollment_id');
        });

        Schema::table('class_attendances', function (Blueprint $table) {
            $table->index('class_session_id');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropIndex(['teacher_id']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropIndex(['enrollment_id']);
        });

        Schema::table('class_attendances', function (Blueprint $table) {
            $table->dropIndex(['class_session_id']);
            $table->dropIndex(['student_id']);
        });
    }
};
