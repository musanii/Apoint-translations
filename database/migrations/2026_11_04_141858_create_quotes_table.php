<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_request_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('reference')->unique();

            $table->index(['quote_request_id', 'status']);

            $table->string('pricing_type', 30);

            $table->decimal('quantity', 12, 2)->nullable();

            $table->string('unit', 30)->nullable();

            $table->decimal('rate', 12, 4)->nullable();

            $table->decimal('subtotal', 12, 2);

            $table->decimal('express_fee', 12, 2)->default(0);

            $table->decimal('certification_fee', 12, 2)->default(0);

            $table->decimal('total', 12, 2);

            $table->text('admin_notes')->nullable();

            $table->text('customer_notes')->nullable();

            $table->string('status', 30)->default('draft');

            $table->timestamp('valid_until')->nullable();

            $table->timestamp('accepted_at')->nullable();

            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
