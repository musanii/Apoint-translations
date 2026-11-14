
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('provider');
            $table->string('provider_channel_id')->nullable()->after('payment_method');
        });

        DB::table('payments')
            ->where('provider', 'mpesa')
            ->update([
                'provider' => 'daraja',
                'payment_method' => 'mpesa',
            ]);
    }

    public function down(): void
    {
        // Restore the original provider value before removing the columns.
        DB::table('payments')
            ->where('provider', 'daraja')
            ->where('payment_method', 'mpesa')
            ->update([
                'provider' => 'mpesa',
            ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'provider_channel_id',
            ]);
        });
    }
};
