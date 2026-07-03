<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds optional per-session pricing fields (fee & discount).
     *
     * These are attributes of an individual class session, captured at the
     * moment of manual scheduling. They are intentionally nullable so existing
     * generated sessions remain valid, and are independent of any future
     * payment/invoicing module.
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->unsignedInteger('session_fee')->nullable()->after('room');
            $table->unsignedInteger('discount')->nullable()->after('session_fee');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropColumn(['session_fee', 'discount']);
        });
    }
};
