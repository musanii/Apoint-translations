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
            $table->unsignedInteger('reconciliation_attempts')->default(0);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->timestamp('next_reconciliation_at')->nullable();
            $table->index(
                ['status', 'provider', 'next_reconciliation_at'],
                'payments_reconciliation_due_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payment_reconciliation_due_index');
            $table->dropColumn([
                'reconciliation_attempts',
                'last_reconciled_at',
                'next_reconciliation_at',
            ]);

        });
    }
};
