<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('reference')->unique();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('express_fee', 12, 2)->default(0);
            $table->decimal('certification_fee', 12, 2)->default(0);
            $table->decimal('courier_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            $table->string('delivery_method', 20)->default('digital');

            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->string('delivery_country', 100)->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('delivery_postal_code', 30)->nullable();

            $table->string('status', 30)->default('pending_payment');
            $table->string('payment_status', 30)->default('unpaid');

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['payment_status', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
