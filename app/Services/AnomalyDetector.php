<?php

namespace App\Services;

use App\Models\AnomalyAlert;
use App\Models\Shift;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;

/**
 * Financial anomaly scanning — passive rules on top of the ops data:
 *  - repeated returns by the same employee (refund abuse)
 *  - drawer variance on closed shifts (cash leakage)
 *  - discount spikes (unauthorized markdowns)
 */
class AnomalyDetector
{
    public function scan(int $businessId): array
    {
        return [
            'returns' => $this->scanReturns($businessId),
            'drawer' => $this->scanDrawer($businessId),
            'discounts' => $this->scanDiscounts($businessId),
        ];
    }

    protected function scanReturns(int $businessId): int
    {
        $count = 0;
        $byUser = SaleReturn::query()->where('sale_returns.business_id', $businessId)
            ->where('sale_returns.created_at', '>=', now()->subDays(7))
            ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->selectRaw('sales.user_id, COUNT(*) c')->groupBy('sales.user_id')->pluck('c', 'sales.user_id');

        foreach ($byUser as $userId => $c) {
            if ((int) $c >= 3) {
                $this->alert($businessId, 'excessive_returns', $c >= 6 ? 'high' : 'medium',
                    'مرتجعات متكررة لموظف خلال أسبوع', ['user_id' => $userId, 'returns' => (int) $c], $userId);
                $count++;
            }
        }

        return $count;
    }

    protected function scanDrawer(int $businessId): int
    {
        $count = 0;
        Shift::forBusiness($businessId)->where('status', 'closed')
            ->where('closed_at', '>=', now()->subDays(7))
            ->whereRaw('ABS(difference) > 50')
            ->get()->each(function (Shift $shift) use (&$count, $businessId) {
                $this->alert($businessId, 'drawer_variance', abs((float) $shift->difference) > 200 ? 'high' : 'medium',
                    'فرق درج في وردية '.$shift->shift_number,
                    ['shift_id' => $shift->id, 'difference' => (float) $shift->difference], $shift->user_id);
                $count++;
            });

        return $count;
    }

    protected function scanDiscounts(int $businessId): int
    {
        $avg = (float) Sale::forBusiness($businessId)
            ->where('saleDate', '>=', now()->subDays(30)->toDateString())
            ->avg('discountAmount');

        if ($avg > 0) {
            $spiky = Sale::forBusiness($businessId)
                ->where('saleDate', '>=', now()->subDays(7)->toDateString())
                ->where('discountAmount', '>', $avg * 3)
                ->where('discountAmount', '>', 20)
                ->get();

            foreach ($spiky as $sale) {
                $this->alert($businessId, 'discount_spike', 'medium',
                    'خصم مرتفع في فاتورة '.($sale->invoiceNumber ?? '#'.$sale->id),
                    ['sale_id' => $sale->id, 'discount' => (float) $sale->discountAmount, 'avg30' => $avg], $sale->user_id);
            }

            return $spiky->count();
        }

        return 0;
    }

    protected function alert(int $businessId, string $type, string $severity, string $title, array $details, ?$userId = null): void
    {
        // Dedupe: same type + user + day
        $exists = AnomalyAlert::forBusiness($businessId)->where('type', $type)
            ->whereDate('created_at', today())
            ->where('user_id', $userId)->exists();
        if ($exists) {
            return;
        }

        AnomalyAlert::create([
            'business_id' => $businessId, 'type' => $type, 'severity' => $severity,
            'title' => $title, 'details' => $details, 'user_id' => $userId,
        ]);
    }
}
