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
            'someFutureKey' => 'x',
            'another_one' => 'y',
        ]);

        $this->assertSame('x', $request->input('someFutureKey'));
        $this->assertSame('y', $request->input('another_one'));
        $this->assertCount(2, $request->all());
    }

    /**
     * The mobile bulk-import template generates columns named "Purchase Price*",
     * "MRP*" and "Wholesale Price", which the app sends as productPurchasePrice,
     * productSalePrice and productWholeSalePrice. None of the mechanical
     * camel-to-snake conversions exist as columns, and because the real ones are
     * NOT NULL DEFAULT 0 an unmapped key did not fail — it created the product
     * with a zero selling price and a zero cost, i.e. stock that sells for nothing
     * and reports a 100% margin.
     */
    public function test_bulk_product_prices_map_onto_the_columns_that_exist(): void
    {
        $request = $this->normalize([
            'productName' => 'Panadol',
            'productPurchasePrice' => 4.5,
            'productSalePrice' => 9.5,
            'productWholeSalePrice' => 7.25,
        ]);

        $this->assertSame('Panadol', $request->input('product_name'));
        $this->assertSame(4.5, $request->input('purchase_without_tax'));
        $this->assertSame(9.5, $request->input('sales_price'));
        $this->assertSame(7.25, $request->input('wholesale_price'));

        foreach (['product_purchase_price', 'product_sale_price', 'product_whole_sale_price'] as $invented) {
            $this->assertNull(
                $request->input($invented),
                "{$invented} does not exist on products; mapping to it would silently store nothing."
            );
        }
    }

    /**
     * "Purchase Price*" in the template is a tax-exclusive cost — the bulk screen
     * populates the field from productPurchasePriceWithoutTax — so it must land in
     * purchase_without_tax. Filing it under purchase_with_tax would put a
     * tax-exclusive figure in a tax-inclusive column.
     */
    public function test_the_bulk_purchase_price_is_treated_as_tax_exclusive(): void
    {
        $request = $this->normalize(['productPurchasePrice' => 4.5]);

        $this->assertSame(4.5, $request->input('purchase_without_tax'));
        $this->assertNull($request->input('purchase_with_tax'));
    }

    /**
     * No products column backs the dealer price, but the generated template still
     * asks the user for one, so the value is kept in the product's meta JSON — the
     * extension point the same bulk flow already uses for strength, generic_name,
     * shelf, batch_no and medicine_details — instead of being discarded. The
     * sibling meta keys must survive the merge.
     */
    public function test_dealer_price_is_preserved_in_product_meta_alongside_its_siblings(): void
    {
        $request = $this->normalize([
            'productDealerPrice' => 6.5,
            'meta' => ['strength' => '500mg', 'shelf' => 'A3'],
        ]);

        $this->assertSame(6.5, $request->input('meta.dealer_price'));
        $this->assertSame('500mg', $request->input('meta.strength'));
        $this->assertSame('A3', $request->input('meta.shelf'));

        // ZSystProductController persists this via $request->only(['meta']), so the
        // value has to be genuinely nested, not a literal "meta.dealer_price" key.
        $this->assertSame(
            ['strength' => '500mg', 'shelf' => 'A3', 'dealer_price' => 6.5],
            $request->only(['meta'])['meta']
        );
    }

    /**
     * The canonical-wins invariant has to hold for a nested target too. This is why
     * the middleware checks Arr::has() rather than array_key_exists(): a literal
     * array_key_exists('meta.dealer_price', $input) is always false, so the legacy
     * key would have overwritten a value the client sent explicitly.
     */
    public function test_an_explicit_nested_value_is_never_overwritten_by_a_legacy_key(): void
    {
        $request = $this->normalize([
            'meta' => ['dealer_price' => 'canonical'],
            'productDealerPrice' => 'legacy',
        ]);

        $this->assertSame('canonical', $request->input('meta.dealer_price'));
    }
}
