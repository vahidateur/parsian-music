<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('token', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('used')->default(false);

            // Composite index for fast lookup + cleanup
            $table->index(['phone', 'used', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_password_resets');
    }
};
