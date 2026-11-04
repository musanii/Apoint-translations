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
        Schema::create('quote_request_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_request_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension', 10);
            $table->unsignedBigInteger('size');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_request_documents');
    }
};
