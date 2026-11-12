<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
 * The map deliberately excludes the camelCase keys that are *correct* camelCase,
 * each verified against the live schema or the controller that reads it — do not
 * "complete" the map with them:
 *  - `categoryName` / `categoryDescription`: still the real column names on
 *    `expense_categories` and `income_categories`;
 *  - `subscriptionName`, `offerPrice`, `subscriptionPrice`: real camelCase
 *    columns on `plans`;
 *  - `imageUrl`: a real quoted camelCase column on `banners`;
 *  - the `*Permission` keys (`salePermission`, `stockPermission`, ...):
 *    ZSystUserController reads `$request->addExpensePermission` directly, so the
 *    camelCase name is the contract, not a legacy alias;
 *  - `unitName`: UnitController merges its own `unit_name` alias locally;
 *  - `expanseFor` / `expenseDate`: ZSystExpenseController already reads both
 *    spellings (`$request->expanse_for ?? $request->expanseFor`);
 * Three product keys map to a column whose name is NOT their mechanical
 * camel-to-snake conversion, so each is justified here rather than derived:
 *  - `productWholeSalePrice` => `wholesale_price` (products has no
 *    `product_whole_sale_price`);
 *  - `productSalePrice` => `sales_price`. The mobile bulk-import template calls
 *    this column "MRP*" and it is the product's selling price.
 *  - `productPurchasePrice` => `purchase_without_tax`. The bulk screen populates
 *    it from `productPurchasePriceWithoutTax`, i.e. the template's "Purchase
 *    Price*" column is a tax-exclusive cost, so mapping it to
 *    `purchase_with_tax` would store a tax-exclusive figure in a tax-inclusive
 *    column. Note the *response* side disagrees on purpose: a purchase line's
 *    price comes back as `purchase_with_tax`, which is what
 *    PurchaseController selects.
 *
 * `productDealerPrice` has no column in any table. It is still mapped — to
 * `meta.dealer_price` — because the mobile app generates a bulk-import template
 * with a "Dealer Price" header that the user fills in, and an unmapped key means
 * that value is silently discarded. `products.meta` is the application's own
 * extension point (the same bulk flow already stores strength, generic_name,
 * shelf, batch_no and medicine_details there) and is json-cast and fillable.
 * Nothing surfaces it in a UI yet; making dealer price a first-class column is a
 * product decision, not a shim.
 *
 * @see \App\Http\Controllers\Api\ZSystCategoryController
 * @see \App\Http\Controllers\Api\UnitController
 * @see \App\Http\Controllers\Api\ZSystUserController
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

        // Incomes. Unlike ZSystExpenseController — which reads
        // `$request->expanse_for ?? $request->expanseFor` — ZSystIncomeController
        // reads snake_case only, so the mobile app's camelCase keys were being
        // stored as null rather than rejected: an income silently lost its
        // purpose, date and reference.
        'incomeFor' => 'income_for',
        'incomeDate' => 'income_date',
        'referenceNo' => 'reference_no',

        // Products. The canonical names here are NOT the mechanical conversion
        // of the legacy keys — products has no product_whole_sale_price, no
        // product_sale_price and no product_purchase_price — which is exactly why
        // they belong in this map. Verified against the live `products` columns:
        // purchase_without_tax, purchase_with_tax, profit_percent, sales_price
        // and wholesale_price, all NOT NULL DEFAULT 0.
        //
        // Because they default to 0 rather than rejecting the row, an unmapped key
        // does not fail loudly: ZSystProductController::store() only validates
        // product_name and category_id, so a bulk import from the mobile app used
        // to create the product "successfully" with sales_price 0 and
        // purchase_without_tax 0 — a product that sells for nothing and reports
        // 100% margin. (updateStock() is the endpoint that does validate these as
        // required|numeric; the mobile stock screens already send them snake_case.)
        'productWholeSalePrice' => 'wholesale_price',
        'productSalePrice' => 'sales_price',

        // The bulk-import template's "Purchase Price*" column is a tax-exclusive
        // cost: bulk_product_upload_screen.dart populates this field from
        // productPurchasePriceWithoutTax. Mapping it to purchase_with_tax would
        // file a tax-exclusive figure under a tax-inclusive column.
        'productPurchasePrice' => 'purchase_without_tax',

        // No such column exists. The template still asks the user for a "Dealer
        // Price", so keep the value in the product's meta JSON — the extension
        // point this same bulk flow already uses for strength, generic_name,
        // shelf, batch_no and medicine_details — instead of discarding it. See
        // the class docblock; surfacing it in a UI is a product decision.
        'productDealerPrice' => 'meta.dealer_price',
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

            // Arr::has(), not array_key_exists(): a mapping may target a nested
            // path (`productDealerPrice` => `meta.dealer_price`), and the whole
            // point of this guard is that a canonical value the client already
            // sent is never overwritten by its legacy alias. A literal
            // array_key_exists('meta.dealer_price', $input) is always false, so
            // the legacy key would have won.
            if ($snake === null || Arr::has($input, $snake)) {
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
