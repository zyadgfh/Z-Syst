<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Legacy payload compatibility shim.
 *
 * The database columns were unified to snake_case to match the live Supabase
 * schema, and the request-validation contracts followed them. Clients that were
 * built against the older camelCase contract — the bundled Flutter app, PWA
 * builds already cached in the field, and any third-party integration — keep
 * sending the old keys. Without this shim those requests fail validation (422)
 * or silently lose fields.
 *
 * The shim is strictly additive and non-destructive:
 *  - it only ever ADDS the snake_case twin of a known legacy key;
 *  - it never overwrites an explicit snake_case value sent by the client
 *    (the canonical name always wins);
 *  - it never removes the legacy key, so controllers that still read
 *    `$request->paidAmount` keep working;
 *  - unknown keys are passed through untouched.
 *
 * The map deliberately excludes `categoryName` / `categoryDescription`, which
 * are still the real column names on `expense_categories` and
 * `income_categories`, and the `plans` camelCase columns. Those endpoints handle
 * their own aliases locally so the two meanings never collide.
 *
 * @see \App\Http\Controllers\Api\ZSystCategoryController
 * @see \App\Http\Controllers\Api\UnitController
 */
class NormalizeLegacyPayloadKeys
{
    /**
     * legacy camelCase payload key => canonical snake_case key
     *
     * @var array<string, string>
     */
    private const LEGACY_MAP = [
        'companyName' => 'company_name',
        'phoneNumber' => 'phone_number',
        'pictureUrl' => 'picture_url',
        'subscriptionDate' => 'subscription_date',
        'remainingShopBalance' => 'remaining_shop_balance',
        'shopOpeningBalance' => 'shop_opening_balance',
        'productName' => 'product_name',
        'productCode' => 'product_code',
        'productStock' => 'product_stock',
        'invoiceNumber' => 'invoice_number',
        'saleDate' => 'sale_date',
        'purchaseDate' => 'purchase_date',
        'totalAmount' => 'total_amount',
        'paidAmount' => 'paid_amount',
        'dueAmount' => 'due_amount',
        'discountAmount' => 'discount_amount',
        'lossProfit' => 'loss_profit',
        'isPaid' => 'is_paid',
        'paymentType' => 'payment_type',

        // Due collections. The live columns are amount / paid_amount /
        // due_amount; the mobile app still posts the old camelCase names.
        'totalDue' => 'amount',
        'payDueAmount' => 'paid_amount',
        'dueAmountAfterPay' => 'due_amount',
        'paymentDate' => 'payment_date',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if ($input !== []) {
            $patch = [];
            $this->collect($input, '', $patch);

            if ($patch !== []) {
                // Dot paths, not nested arrays: Request::merge() replaces a whole
                // key, so a partial nested patch would wipe sibling fields
                // (e.g. the rest of a POS line item).
                $request->merge($patch);
            }
        }

        return $next($request);
    }

    /**
     * Walk the payload and record snake_case twins for legacy keys.
     *
     * @param  array<int|string, mixed>  $input
     * @param  array<string, mixed>  $patch
     */
    private function collect(array $input, string $prefix, array &$patch): void
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $this->collect($value, $this->path($prefix, (string) $key), $patch);

                continue;
            }

            if (! is_string($key)) {
                continue;
            }

            $snake = self::LEGACY_MAP[$key] ?? null;

            if ($snake === null || array_key_exists($snake, $input)) {
                continue;
            }

            $patch[$this->path($prefix, $snake)] = $value;
        }
    }

    private function path(string $prefix, string $key): string
    {
        return $prefix === '' ? $key : $prefix.'.'.$key;
    }
}
