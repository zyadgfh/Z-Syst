<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Product;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PurchaseProductImport implements ToCollection
{
    protected $businessId;
    protected $errors = [];

    public function __construct($businessId)
    {
        $this->businessId = $businessId;
    }

    public function collection(Collection $rows)
    {
        // Skip header row
        $rows = $rows->slice(1);

        // Filter out completely empty rows or missing product name/code
        $rows = $rows->filter(function ($row) {
            return !empty(trim($row[0] ?? '')) && !empty(trim($row[1] ?? ''));
        });

        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $productCode = trim($row[0] ?? '');
                $quantity = (float)($row[1] ?? 0);

                // Fetch product with flexible matching
                $product = Product::where('business_id', $this->businessId)
                    ->whereRaw("TRIM(LEADING '0' FROM productCode) = ?", [ltrim($productCode, '0')])
                    ->first();

                if (!$product) {
                    throw new \Exception("Row " . ($index + 2) . ": '{$productCode}' not found.");
                }

                // Use product's saved price if not given in file
                $purchasePrice = isset($row[2]) && is_numeric($row[2]) && $row[2] !== '' ? (float) $row[2] : (float) $product->productPurchasePrice;
                $salePrice     = isset($row[3]) && is_numeric($row[3]) && $row[3] !== '' ? (float) $row[3] : (float) $product->productSalePrice;
                $wholesalePrice = isset($row[4]) && is_numeric($row[4]) && $row[4] !== '' ? (float) $row[4] : (float) $product->productWholeSalePrice;
                $dealerPrice   = isset($row[5]) && is_numeric($row[5]) && $row[5] !== '' ? (float) $row[5] : (float) $product->productDealerPrice;

                $batchNo = $row[6] ?? null;
                $expireDate = $this->parseExcelDate($row[7] ?? null);

                $type = 'purchase';

                // Check if product already in cart with same batch & type
                $existingCartItem = Cart::search(function ($cartItem) use ($product, $batchNo, $type) {
                    return $cartItem->id == $product->id &&
                        ($cartItem->options->batch_no ?? null) == $batchNo &&
                        ($cartItem->options->type ?? null) == $type;
                })->first();

                if ($existingCartItem) {
                    $newQty = $existingCartItem->qty + $quantity;
                    Cart::update($existingCartItem->rowId, ['qty' => $newQty]);
                } else {
                    Cart::add([
                        'id' => $product->id,
                        'name' => $product->productName,
                        'qty' => $quantity,
                        'price' => $purchasePrice,
                        'options' => [
                            'type' => $type,
                            'product_code' => $product->productCode,
                            'product_unit_id' => $product->unit_id ?? null,
                            'product_unit_name' => $product->unit?->unitName ?? null,
                            'batch_no' => $batchNo,
                            'expire_date' => $expireDate,
                            'product_image' => $product->image ?? null,
                            'purchase_price' => $purchasePrice,
                            'sales_price' => $salePrice,
                            'whole_sale_price' => $wholesalePrice,
                            'dealer_price' => $dealerPrice,
                        ]
                    ]);
                }
            }
        });
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function parseExcelDate($value)
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim($value);

        // Try common date formats
        foreach (['m/d/Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try general parser
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
