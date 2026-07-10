<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Vat;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductModel;
use App\Models\Variation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProductImport implements ToCollection
{
    protected $businessId;
    protected $categories = [];
    protected $brands = [];
    protected $units = [];
    protected $vats = [];
    protected $models = [];
    protected $existingProductCodes = [];
    protected $excelProductCodes = [];

    public function __construct($businessId)
    {
        $this->businessId = $businessId;
        $this->existingProductCodes = Product::where('business_id', $businessId)
            ->pluck('productCode')
            ->toArray();
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header row

                // Read Excel columns
                $productName       = trim($row[0]);
                $categoryName      = trim($row[1]);
                $unitName          = trim($row[2]);
                $brandName         = trim($row[3]);
                $stockQty          = $row[4] ?? 0;
                $productCode       = trim($row[5]);
                $purchasePrice     = (float)($row[6] ?? 0);
                $salePrice         = (float)($row[7] ?? 0);
                $dealerPrice       = (float)($row[8] ?? $salePrice);
                $wholesalePrice    = (float)($row[9] ?? $salePrice);
                $vatName           = trim($row[10]);
                $vatPercent        = (float)($row[11] ?? 0);
                $vatType           = $row[12] ?? 'exclusive';
                $alertQty          = (int)($row[13] ?? 0);
                $manufacturer      = $row[14] ?? null;
                $expireDate        = $this->parseExcelDate($row[15]);
                $batchNo           = $row[16] ?? null;
                $model             = trim($row[17]);
                $manufacturingDate = $row[18] ?? null;
                $productType       = strtolower(trim($row[19] ?? 'single'));
                $variationsText    = trim($row[20] ?? ''); // Example: "Color:Black|Size:M"

                if (!$productName || !$productCode || !$categoryName) continue;
                if (in_array($productCode, $this->existingProductCodes)) continue;
                if (in_array($productCode, $this->excelProductCodes)) continue;

                // --- VAT and profit ---
                $vatAmount = ($purchasePrice * $vatPercent) / 100;
                $grandPurchasePrice = $vatType === 'inclusive'
                    ? $purchasePrice + $vatAmount
                    : $purchasePrice;
                $profitPercent = $purchasePrice > 0
                    ? round((($salePrice - $purchasePrice) / $purchasePrice) * 100, 3)
                    : 0.0;
                $this->excelProductCodes[] = $productCode;

                // --- Related models ---
                $categoryId = $this->categories[$categoryName] ??= Category::firstOrCreate(
                    ['categoryName' => $categoryName, 'business_id' => $this->businessId],
                    ['categoryName' => $categoryName]
                )->id;

                $brandId = $this->brands[$brandName] ??= Brand::firstOrCreate(
                    ['brandName' => $brandName, 'business_id' => $this->businessId],
                    ['brandName' => $brandName]
                )->id;

                $unitId = $this->units[$unitName] ??= Unit::firstOrCreate(
                    ['unitName' => $unitName, 'business_id' => $this->businessId],
                    ['unitName' => $unitName]
                )->id;

                $vatId = $this->vats[$vatName] ??= Vat::firstOrCreate(
                    ['name' => $vatName, 'business_id' => $this->businessId],
                    ['name' => $vatName, 'rate' => $vatPercent]
                )->id;

                $modelId = $this->models[$model] ??= ProductModel::firstOrCreate(
                    ['name' => $model, 'business_id' => $this->businessId],
                    ['name' => $model]
                )->id;

                // --- Parse variations ---
                $variationData = [];
                $variantNameParts = [];
                $variationIds = [];

                if ($productType === 'variant' && $variationsText) {
                    $variationParts = explode('|', $variationsText);
                    foreach ($variationParts as $part) {
                        [$name, $value] = array_map('trim', explode(':', $part));

                        if ($name && $value) {
                            $variationData[] = [$name => $value];
                            $variantNameParts[] = $value;

                            // Find existing variation ID by name and value
                            $variation = Variation::where('business_id', $this->businessId)
                                ->where('name', $name)
                                ->first();

                            if ($variation && in_array($value, $variation->values)) {
                                $variationIds[] = (string)$variation->id;
                            }
                        }
                    }
                }

                $variantName = implode(' - ', $variantNameParts);

                // --- Create Product ---
                $product = Product::create([
                    'productName'   => $productName,
                    'business_id'   => $this->businessId,
                    'unit_id'       => $unitId,
                    'brand_id'      => $brandId,
                    'category_id'   => $categoryId,
                    'model_id'      => $modelId,
                    'productCode'   => $productCode,
                    'vat_id'        => $vatId,
                    'vat_type'      => $vatType,
                    'vat_amount'    => $vatAmount,
                    'alert_qty'     => $alertQty,
                    'expire_date'   => $expireDate,
                    'manufacturer'  => $manufacturer,
                    'product_type'  => $productType,
                    'variation_ids'    => $variationIds
                ]);

                // --- Create Stock ---
                Stock::updateOrCreate(
                    [
                        'batch_no'    => $batchNo,
                        'business_id' => $this->businessId,
                        'product_id'  => $product->id,
                    ],
                    [
                        'expire_date'            => $expireDate,
                        'productStock'           => $stockQty,
                        'productPurchasePrice'   => $grandPurchasePrice,
                        'profit_percent'         => $profitPercent,
                        'productSalePrice'       => $salePrice,
                        'productWholeSalePrice'  => $wholesalePrice,
                        'productDealerPrice'     => $dealerPrice,
                        'mfg_date'               => $manufacturingDate,
                        'variation_data'         => $variationData,
                        'variant_name'           => $variantName,
                    ]
                );
            }
        });
    }

    protected function parseExcelDate($value)
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim($value);
        foreach (['m/d/Y', 'd/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }
}
