<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('email');
            $table->string('locale', 5)->default('fa')->after('avatar_path');
            $table->string('timezone', 50)->default('Asia/Tehran')->after('locale');
            $table->boolean('force_password_change')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'locale', 'timezone', 'force_password_change']);
        });
    }
};
