<?php

namespace App\Console\Commands;

use App\Services\MpesaPaymentReconciliationRunner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:reconcile-mpesa')]
#[Description('Reconcile pending M-Pesa payments with Daraja')]
class ReconcilePendingMpesaPayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(
        MpesaPaymentReconciliationRunner $runner
    ):int
    {
        $attempted = $runner->run();
        $this->info(
            "Processed {$attempted} M-Pesa reconciliation attempts."
        );
      return self::SUCCESS;
    }
}
