<?php

namespace Tests\Feature;

use App\Http\Middleware\NormalizeLegacyPayloadKeys;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * The mobile app posts the legacy camelCase contract; the database columns and
 * the request-validation rules are snake_case. NormalizeLegacyPayloadKeys is the
 * shim between them, and it is the only thing standing between a camelCase
 * request and a field that is silently stored as null.
 *
 * These tests pin the mappings that were verified against the live schema, and
 * — just as importantly — pin the keys that must stay camelCase, so the map is
 * not "completed" with an alias that would overwrite a real column name.
 *
 * No database is involved: the middleware only reshapes the request.
 */
class LegacyPayloadKeyNormalizationTest extends TestCase
{
    /**
     * Run a payload through the shim and return the resulting request.
     */
    private function normalize(array $payload): Request
    {
        $request = Request::create('/api/v1/anything', 'POST', $payload);

        (new NormalizeLegacyPayloadKeys)->handle(
            $request,
            static fn (Request $r): Response => new Response('')
        );

        return $request;
    }

    /**
     * ZSystIncomeController reads snake_case only — unlike the expense
     * controller, which reads both spellings — so without these mappings an
     * income created from the mobile app silently lost its purpose, date and
     * reference.
     */
    public function test_income_camel_case_keys_are_mapped_onto_the_real_columns(): void
    {
        $request = $this->normalize([
            'incomeFor' => 'Retail sales',
            'incomeDate' => '2026-11-10',
            'referenceNo' => 'INV-9',
            'amount' => 250,
        ]);

        $this->assertSame('Retail sales', $request->input('income_for'));
        $this->assertSame('2026-11-10', $request->input('income_date'));
        $this->assertSame('INV-9', $request->input('reference_no'));
        $this->assertSame(250, $request->input('amount'));

        // The shim is additive: the legacy key survives for controllers that
        // still read it directly.
        $this->assertSame('Retail sales', $request->input('incomeFor'));
    }

    /**
     * ZSystProductController validates `wholesale_price` as required|numeric, so
     * a mobile request sending the legacy key failed with 422 and the product
     * could not be saved at all. The canonical name is NOT the mechanical
     * conversion of the legacy key — products.product_whole_sale_price does not
     * exist — which is why this mapping is asserted explicitly.
     */
    public function test_product_wholesale_price_maps_to_the_column_that_actually_exists(): void
    {
        $request = $this->normalize(['productWholeSalePrice' => 12.5]);

        $this->assertSame(12.5, $request->input('wholesale_price'));
        $this->assertNull(
            $request->input('product_whole_sale_price'),
            'A mechanical camel-to-snake conversion would invent a column that does not exist.'
        );
    }

    /**
     * The canonical name always wins: a client that has already migrated must
     * not have its explicit value overwritten by a stale legacy key.
     */
    public function test_explicit_snake_case_value_is_never_overwritten(): void
    {
        $request = $this->normalize([
            'income_for' => 'canonical',
            'incomeFor' => 'legacy',
        ]);

        $this->assertSame('canonical', $request->input('income_for'));
    }

    /**
     * These camelCase keys are the contract, not a legacy alias. Mapping them
     * would add a snake_case twin that no column backs — or, worse, collide with
     * a different meaning on another endpoint.
     */
    public function test_keys_that_are_correctly_camel_case_are_left_alone(): void
    {
        $request = $this->normalize([
            'categoryName' => 'Antibiotics',       // real column on expense_categories
            'subscriptionName' => 'Starter',       // real camelCase column on plans
            'imageUrl' => 'https://x/y.png',       // real quoted camelCase column on banners
            'salePermission' => 'true',            // read as camelCase by ZSystUserController
            'unitName' => 'Strip',                 // UnitController merges its own alias
        ]);

        $this->assertNull($request->input('category_name'));
        $this->assertNull($request->input('subscription_name'));
        $this->assertNull($request->input('image_url'));
        $this->assertNull($request->input('sale_permission'));
        $this->assertNull($request->input('unit_name'));

        $this->assertSame('Antibiotics', $request->input('categoryName'));
        $this->assertSame('true', $request->input('salePermission'));
    }

    /**
     * Nested payloads are patched by dot path on purpose: Request::merge()
     * replaces a whole key, so merging a partial nested array would wipe sibling
     * fields — for example the rest of a POS line item.
     */
    public function test_nested_legacy_keys_are_mapped_without_wiping_siblings(): void
    {
        $request = $this->normalize([
            'products' => [
                [
                    'productName' => 'Panadol',
                    'qty' => 3,
                    'sales_price' => 9.5,
                ],
            ],
        ]);

        $this->assertSame('Panadol', $request->input('products.0.product_name'));
        $this->assertSame(3, $request->input('products.0.qty'));
        $this->assertSame(9.5, $request->input('products.0.sales_price'));
    }

    /**
     * Unknown keys must pass through untouched — the shim has no business
     * guessing at a contract it does not know.
     */
    public function test_unknown_keys_pass_through_unchanged(): void
    {
        $request = $this->normalize([
            'productDealerPrice' => 7,   // no such column in any table
            'someFutureKey' => 'x',
        ]);

        $this->assertSame(7, $request->input('productDealerPrice'));
        $this->assertNull($request->input('product_dealer_price'));
        $this->assertSame('x', $request->input('someFutureKey'));
        $this->assertCount(2, $request->all());
    }
}
