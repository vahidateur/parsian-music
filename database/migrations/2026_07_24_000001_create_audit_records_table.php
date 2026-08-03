<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 32);
            $table->string('entity_type', 32)->nullable();
            $table->string('action', 32)->nullable();
            $table->string('selection_mode', 32)->nullable();
            $table->string('context_fingerprint', 128)->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('succeeded')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->json('reason_categories')->nullable();
            $table->json('reason_identifiers')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
            $table->index(['entity_type', 'action']);
            $table->index('context_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_records');
    }
};
