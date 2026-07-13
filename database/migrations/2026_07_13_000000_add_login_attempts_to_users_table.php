<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('login_attempts')->default(0)->comment('تعداد تلاش‌های ناموفق لاگین');
            $table->timestamp('locked_until')->nullable()->comment('زمانی که حساب تا آن‌ جا قفل است');
            $table->index('locked_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['locked_until']);
            $table->dropColumn(['login_attempts', 'locked_until']);
        });
    }
};
