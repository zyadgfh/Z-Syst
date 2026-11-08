<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\ForecastService;
use Illuminate\Console\Command;

class ForecastGenerateCommand extends Command
{
    protected $signature = 'forecast:generate {--business= : business id}';
    protected $description = 'Generate demand forecasts + auto-order reorder points (moving average + weekday seasonality)';

    public function handle(ForecastService $forecasts): int
    {
        $businesses = $this->option('business')
            ? Business::whereKey((int) $this->option('business'))->get()
            : Business::all();

        foreach ($businesses as $business) {
            $written = $forecasts->generate((int) $business->id);
            $this->info("business {$business->id}: $written forecast rows");
        }

        return self::SUCCESS;
    }
}
