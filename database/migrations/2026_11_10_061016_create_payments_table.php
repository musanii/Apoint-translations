<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('reference')->unique();
            $table->string('provider');
            $table->string('provider_reference')->nullable();

            $table->decimal('amount', 12, 2);

            $table->string('status')->default('pending');
            $table->text('failure_reason')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['provider', 'provider_reference'],
                'payments_provider_reference_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
