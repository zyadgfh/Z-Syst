<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram notification channel for owners: daily digest, low-stock and
 * anomaly alerts. Pure HTTP via Guzzle — no SDK. When no bot token/chat id
 * is configured the notifier runs in simulation mode (logs + returns false)
 * so nothing breaks offline.
 *
 * Settings: telegram_bot_token, telegram_chat_id (business_settings).
 */
class TelegramNotifier
{
    public function configured(int $businessId): bool
    {
        return (bool) BusinessSetting::get($businessId, 'telegram_bot_token')
            && (bool) BusinessSetting::get($businessId, 'telegram_chat_id');
    }

    public function send(int $businessId, string $text): bool
    {
        if (!$this->configured($businessId)) {
            Log::info('telegram(simulation)', ['business' => $businessId, 'text' => mb_substr($text, 0, 120)]);

            return false;
        }

        try {
            Http::post('https://api.telegram.org/bot'.BusinessSetting::get($businessId, 'telegram_bot_token').'/sendMessage', [
                'chat_id' => BusinessSetting::get($businessId, 'telegram_chat_id'),
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ])->throw();

            return true;
        } catch (\Throwable $e) {
            Log::warning('telegram send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function dailyDigest(int $businessId): string
    {
        $today = now()->toDateString();
        $sales = \App\Models\Sale::forBusiness($businessId)->whereDate('saleDate', $today);
        $total = (float) (clone $sales)->sum('totalAmount');
        $count = (int) (clone $sales)->count();
        $profit = (float) (clone $sales)->sum('lossProfit');
        $lowStock = \App\Models\Product::query()->where('business_id', $businessId)
            ->whereHas('stocks')->get()
            ->filter(fn ($p) => (int) $p->stocks()->sum('productStock') <= 5)
            ->take(5)->pluck('name');

        $lines = [
            '📊 <b>ملخص اليوم</b>',
            'عدد الفواتير: '.$count,
            'إجمالي المبيعات: '.number_format($total, 2).' EGP',
            'الربح: '.number_format($profit, 2).' EGP',
        ];
        if ($lowStock->isNotEmpty()) {
            $lines[] = "\n⚠️ مخزون منخفض:\n• ".implode("\n• ", $lowStock->all());
        }

        return implode("\n", $lines);
    }
}
