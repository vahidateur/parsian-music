<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                  ->constrained('invoices')
                  ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->timestamp('paid_at');
            $table->string('method', 30);   // PaymentMethodEnum value
            $table->string('status', 20)->default('completed'); // PaymentStatusEnum value
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            // Who registered this payment (nullable — system-generated payments have no user)
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index('invoice_id');
            $table->index('status');
            $table->index('paid_at');
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
