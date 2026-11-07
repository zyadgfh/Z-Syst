<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class TelegramDigestCommand extends Command
{
    protected $signature = 'telegram:daily {--business= : business id}';
    protected $description = 'Send the daily sales digest (+low stock) to owners on Telegram';

    public function handle(TelegramNotifier $telegram): int
    {
        $businesses = $this->option('business')
            ? Business::whereKey((int) $this->option('business'))->get()
            : Business::all();

        foreach ($businesses as $business) {
            $sent = $telegram->send((int) $business->id, $telegram->dailyDigest((int) $business->id));
            $this->info("business {$business->id}: ".($sent ? 'sent' : 'simulated (not configured)'));
        }

        return self::SUCCESS;
    }
}
