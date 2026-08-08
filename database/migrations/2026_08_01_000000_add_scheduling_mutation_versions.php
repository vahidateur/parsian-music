<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table): void {
            $table->string('session_version', 80)->nullable()->index();
        });

        Schema::create('scheduling_resource_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 32);
            $table->unsignedBigInteger('resource_id');
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
            $table->unique(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduling_resource_versions');
        Schema::table('class_sessions', function (Blueprint $table): void {
            $table->dropIndex(['session_version']);
            $table->dropColumn('session_version');
        });
    }
};
