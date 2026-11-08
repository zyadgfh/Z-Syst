<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Str;

/**
 * Arabic NL assistant for owners — a conservative intent interpreter that
 * only answers aggregate, permission-safe questions (no row dumps, no
 * arbitrary SQL). Supported intents:
 *   مبيعات اليوم/الشهر/أمس … + "كام"/"مجموع"
 *   عدد الفواتير …
 *   ربح …
 *   مخزون صنف X / ناقص مخزون
 *   أعلى الأصناف مبيعًا
 */
class AssistantEngine
{
    public function answer(int $businessId, string $question): array
    {
        $q = trim(mb_strtolower($question));
        $q = str_replace(['؟', '?'], '', $q);

        if (preg_match('/(مخزون|كمية)\s*(صنف|منتج|دوا|منتج)?\s*(.+)/u', $q, $m) && !str_contains($q, 'ناقص') && !str_contains($q, 'قليل')) {
            return $this->stockOf($businessId, trim($m[3]));
        }
        if (str_contains($q, 'ناقص') || str_contains($q, 'قليل') || str_contains($q, 'low stock')) {
            return $this->lowStock($businessId);
        }
        if (str_contains($q, 'أعلى') || str_contains($q, 'اكتر') || str_contains($q, 'الأكثر')) {
            return $this->topProducts($businessId);
        }
        if (preg_match('/(عدد|كم)\s*(فاتور|بيعة|عملية)/u', $q)) {
            return $this->invoiceCount($businessId, $this->period($q));
        }
        if (str_contains($q, 'ربح')) {
            return $this->profit($businessId, $this->period($q));
        }
        if (str_contains($q, 'بيع') || str_contains($q, 'مبيع') || str_contains($q, 'كام')) {
            return $this->salesSum($businessId, $this->period($q));
        }

        return [
            'intent' => 'unknown',
            'answer' => 'أسألني مثلًا: «كام بيعت الشهر ده؟» أو «مخزون باراسيتامول؟» أو «أعلى الأصناف مبيعًا» أو «ناقص مخزون».',
        ];
    }

    protected function period(string $q): array
    {
        return match (true) {
            str_contains($q, 'امبارح') || str_contains($q, 'أمس') || str_contains($q, 'البارحة') => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), 'أمس'],
            str_contains($q, 'شهر') => [now()->startOfMonth(), now(), 'هذا الشهر'],
            str_contains($q, 'اسبوع') || str_contains($q, 'أسبوع') => [now()->subWeek(), now(), 'آخر أسبوع'],
            default => [now()->startOfDay(), now(), 'اليوم'],
        };
    }

    protected function salesSum(int $businessId, array $period): array
    {
        [$from, $to, $label] = $period;
        $sum = (float) Sale::forBusiness($businessId)->whereDate('saleDate', '>=', $from->toDateString())->whereDate('saleDate', '<=', $to->toDateString())->sum('totalAmount');

        return ['intent' => 'sales_sum', 'answer' => "إجمالي المبيعات $label: ".number_format($sum, 2).' جنيه'];
    }

    protected function invoiceCount(int $businessId, array $period): array
    {
        [$from, $to, $label] = $period;
        $count = (int) Sale::forBusiness($businessId)->whereDate('saleDate', '>=', $from->toDateString())->whereDate('saleDate', '<=', $to->toDateString())->count();

        return ['intent' => 'invoice_count', 'answer' => "عدد الفواتير $label: $count فاتورة"];
    }

    protected function profit(int $businessId, array $period): array
    {
        [$from, $to, $label] = $period;
        $profit = (float) Sale::forBusiness($businessId)->whereDate('saleDate', '>=', $from->toDateString())->whereDate('saleDate', '<=', $to->toDateString())->sum('lossProfit');

        return ['intent' => 'profit', 'answer' => "الربح التقديري $label: ".number_format($profit, 2).' جنيه'];
    }

    protected function stockOf(int $businessId, string $name): array
    {
        $product = Product::where('business_id', $businessId)
            ->where(fn ($w) => $w->where('productName', 'like', '%'.$name.'%')->orWhere('name', 'like', '%'.$name.'%')->orWhere('productCode', 'like', '%'.$name.'%'))
            ->first();

        if (!$product) {
            return ['intent' => 'stock_of', 'answer' => "لم أجد صنفًا باسم «{$name}»."];
        }

        $qty = (int) $product->stocks()->sum('productStock');

        return ['intent' => 'stock_of', 'answer' => "مخزون «{$product->name}»: $qty وحدة".($qty <= 5 ? ' ⚠️ منخفض' : '')];
    }

    protected function lowStock(int $businessId): array
    {
        $items = Product::where('business_id', $businessId)->with('stocks')->get()
            ->map(fn ($p) => ['name' => $p->name, 'qty' => (int) $p->stocks->sum('productStock')])
            ->filter(fn ($i) => $i['qty'] <= 5)->take(10)->values();

        if ($items->isEmpty()) {
            return ['intent' => 'low_stock', 'answer' => 'لا توجد أصناف منخفضة المخزون 👍'];
        }

        return ['intent' => 'low_stock', 'answer' => 'أصناف تحتاج طلب:\n'.implode("\n", $items->map(fn ($i) => "• {$i['name']}: {$i['qty']}")->all())];
    }

    protected function topProducts(int $businessId): array
    {
        $rows = \App\Models\SaleDetails::query()
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.saleDate', '>=', now()->subDays(30)->toDateString())
            ->groupBy('products.name')
            ->orderByRaw('SUM(sale_details.quantities) DESC')
            ->limit(5)
            ->selectRaw('products.name, SUM(sale_details.quantities) qty')
            ->get();

        if ($rows->isEmpty()) {
            return ['intent' => 'top_products', 'answer' => 'لا بيانات مبيعات كافية بعد.'];
        }

        return ['intent' => 'top_products', 'answer' => "الأكثر مبيعًا (30 يوم):\n".$rows->map(fn ($r) => "• {$r->name}: {$r->qty}")->implode("\n")];
    }
}
