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
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('service_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('source_language_id')
                ->nullable()
                ->constrained('languages')
                ->restrictOnDelete();

            $table->foreignId('target_language_id')
                ->nullable()
                ->constrained('languages')
                ->restrictOnDelete();

            $table->foreignId('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity')->nullable();

            $table->string('unit')->nullable();

            $table->date('delivery_date')->nullable();

            $table->text('instructions')->nullable();

            $table->string('status', 30)->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
