<?php

namespace App\Console\Commands;

use App\Models\EtaInvoice;
use App\Services\EtaInvoiceService;
use Illuminate\Console\Command;

class EtaRetryCommand extends Command
{
    protected $signature = 'eta:retry {--business= : business id}';
    protected $description = 'Retry pending/errored ETA e-invoice submissions';

    public function handle(EtaInvoiceService $eta): int
    {
        $query = EtaInvoice::whereIn('status', ['pending', 'error'])
            ->where('updated_at', '<', now()->subMinutes(5));
        if ($this->option('business')) {
            $query->forBusiness((int) $this->option('business'));
        }

        foreach ($query->limit(50)->get() as $invoice) {
            $fresh = $eta->submit($invoice->sale()->first());
            $this->info("eta invoice {$invoice->id}: {$fresh->status}");
        }

        return self::SUCCESS;
    }
}
