<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->string('outcome');
            $table->text('message')->nullable();
            $table->unsignedInteger('attempt_number')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'created_at']);
            $table->index(['event_type', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_events');
    }
};
