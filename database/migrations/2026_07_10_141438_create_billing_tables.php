<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Human-readable number: INV-2026-00001
            $table->string('invoice_number', 30)->unique();

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            $table->foreignId('enrollment_id')
                  ->nullable()
                  ->constrained('student_enrollments')
                  ->nullOnDelete();

            $table->date('issue_date');
            $table->date('due_date');

            // Financials — 12 digits, 2 decimal places (handles up to ~9.9 billion)
            $table->decimal('subtotal',  12, 2)->default(0);
            $table->decimal('discount',  12, 2)->default(0);
            $table->decimal('tax',       12, 2)->default(0);
            $table->decimal('total',     12, 2)->default(0);

            $table->char('currency', 3)->default('IRR');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('student_id');
            $table->index('enrollment_id');
            $table->index('status');
            $table->index('due_date');
            $table->index(['student_id', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                  ->constrained('invoices')
                  ->cascadeOnDelete();

            $table->string('title');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount',   12, 2)->default(0);
            $table->decimal('total',      12, 2)->default(0);

            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
