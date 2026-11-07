<?php

namespace App\Console\Commands;

use App\Models\PatientMed;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class PatientRefillCommand extends Command
{
    protected $signature = 'patients:refill';
    protected $description = 'Flag chronic-med patients whose refill is due (creates alerts + telegram note)';

    public function handle(TelegramNotifier $telegram): int
    {
        $due = PatientMed::with(['party', 'product'])->active()
            ->where('chronic', true)
            ->whereDate('next_refill_at', '<=', today())
            ->get();

        foreach ($due as $med) {
            $lowStock = (int) $med->product->stocks()->sum('productStock') <= 3;
            $telegram->send((int) $med->business_id,
                '💊 تجديد دواء مزمن: '.($med->party->name ?? 'مريض').' — '.($med->product->name ?? '').
                ($lowStock ? ' ⚠️ المخزون قارب على النفاد — اتصل بالمريض.' : ' — جاهز للاستلام.'));
            $med->update(['next_refill_at' => now()->addDays((int) $med->days_supply)]);
        }

        $this->info("refill reminders: {$due->count()}");

        return self::SUCCESS;
    }
}
