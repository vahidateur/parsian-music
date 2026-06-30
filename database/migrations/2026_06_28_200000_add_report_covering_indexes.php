<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds composite covering indexes for report range-scan queries.
 *
 * Both the attendance report and teacher report filter class_sessions by
 * session_date BETWEEN ? AND ? and then join or aggregate. These composite
 * indexes allow MySQL to satisfy the WHERE + JOIN/SELECT entirely from the
 * index (covering), avoiding full row lookups.
 *
 * Index layout rationale:
 *
 *  class_sessions(session_date, enrollment_id, status)
 *   → covers TeacherReportController's JOIN on enrollment_id + CASE on status
 *   → covers general date-range session listing with status filter
 *
 *  class_sessions(session_date, id)
 *   → covers AttendanceReportController's JOIN on class_sessions.id
 *     where the only WHERE is session_date BETWEEN
 *
 *  class_attendances(class_session_id, student_id, status)
 *   → covers the attendance report's CASE aggregation on status after
 *     joining on class_session_id, grouped by student_id
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->index(['session_date', 'enrollment_id', 'status'], 'idx_sessions_date_enrollment_status');
            $table->index(['session_date', 'id'], 'idx_sessions_date_id');
        });

        Schema::table('class_attendances', function (Blueprint $table) {
            $table->index(['class_session_id', 'student_id', 'status'], 'idx_attendances_session_student_status');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_date_enrollment_status');
            $table->dropIndex('idx_sessions_date_id');
        });

        Schema::table('class_attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_session_student_status');
        });
    }
};
