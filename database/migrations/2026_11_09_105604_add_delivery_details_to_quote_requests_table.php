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
        Schema::table('quote_requests', function (Blueprint $table) {
    $table->string('delivery_method', 20)
        ->default('digital')
        ->after('instructions');

    $table->string('recipient_name')->nullable();
    $table->string('recipient_phone', 30)->nullable();
    $table->string('delivery_country', 100)->nullable();
    $table->string('delivery_address')->nullable();
    $table->string('delivery_postal_code', 30)->nullable();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_method',
                'recipient_name',
                'recipient_phone',
                'delivery_country',
                'delivery_address',
                'delivery_postal_code',
            ]);
        });
    }
};
