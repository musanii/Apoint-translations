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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider_checkout_request_id')
                ->nullable()
                ->unique();

            $table->string('provider_merchant_request_id')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider_checkout_request_id']);

            $table->dropColumn([
                'provider_checkout_request_id',
                'provider_merchant_request_id',
            ]);
        });
    }
};
