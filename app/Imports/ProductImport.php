<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductImport as ProductImportLog;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToCollection, WithHeadingRow, WithChunkReading, WithValidation
{
    use Importable;

    public function __construct(
        protected string $companyId,
        protected string $importLogId,
        protected ?string $userId = null,
    ) {}

    public function collection(Collection $rows): void
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$imported, &$failed, &$errors) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 for header and 0-index

                try {
                    $productData = $this->mapRowToProduct($row);

                    // Check if product exists by barcode or sku
                    $existingProduct = Product::where('company_id', $this->companyId)
                        ->where(function ($q) use ($productData) {
                            if (!empty($productData['barcode'])) {
                                $q->where('barcode', $productData['barcode']);
                            }
                            if (!empty($productData['product_code'])) {
                                $q->orWhere('product_code', $productData['product_code']);
                            }
                            if (!empty($productData['sku'])) {
                                $q->orWhere('sku', $productData['sku']);
                            }
                        })
                        ->first();

                    if ($existingProduct) {
                        $existingProduct->update(array_merge($productData, [
                            'updated_by' => $this->userId,
                        ]));
                    } else {
                        Product::create(array_merge($productData, [
                            'company_id' => $this->companyId,
                            'created_by' => $this->userId,
                            'updated_by' => $this->userId,
                        ]));
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        // Update import log
        ProductImportLog::where('id', $this->importLogId)->update([
            'status' => $failed > 0 ? 'completed' : 'completed',
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'errors' => $errors,
        ]);
    }

    protected function mapRowToProduct(Collection $row): array
    {
        $data = [];

        // Required fields
        $data['product_name'] = $row['name'] ?? $row['product_name'] ?? $row['اسم_المنتج'] ?? null;
        if (empty($data['product_name'])) {
            throw new \Exception('Product name is required');
        }

        // Optional fields
        $data['generic_name'] = $row['generic_name'] ?? $row['الاسم_العلمي'] ?? null;
        $data['brand_name'] = $row['brand_name'] ?? $row['الاسم_التجاري'] ?? null;
        $data['barcode'] = $row['barcode'] ?? $row['باركود'] ?? null;
        $data['product_code'] = $row['sku'] ?? $row['product_code'] ?? $row['كود'] ?? null;
        $data['description'] = $row['description'] ?? $row['وصف'] ?? null;

        // Category lookup
        $categoryName = $row['category'] ?? $row['category_name'] ?? $row['فئة'] ?? null;
        if ($categoryName) {
            $category = Category::where('company_id', $this->companyId)
                ->where(function ($q) use ($categoryName) {
                    $q->where('name', $categoryName)
                        ->orWhere('categoryName', $categoryName);
                })->first();
            $data['category_id'] = $category?->id;
        }

        // Manufacturer lookup
        $manufacturerName = $row['manufacturer'] ?? $row['manufacturer_name'] ?? $row['مصنع'] ?? null;
        if ($manufacturerName) {
            $manufacturer = Manufacturer::where('company_id', $this->companyId)
                ->where('name', $manufacturerName)->first();
            if (!$manufacturer) {
                $manufacturer = Manufacturer::create([
                    'company_id' => $this->companyId,
                    'name' => $manufacturerName,
                    'is_active' => true,
                ]);
            }
            $data['manufacturer_id'] = $manufacturer->id;
        }

        // Unit lookup
        $unitName = $row['unit'] ?? $row['unit_name'] ?? $row['وحدة'] ?? null;
        if ($unitName) {
            $unit = Unit::where('company_id', $this->companyId)
                ->where(function ($q) use ($unitName) {
                    $q->where('unitName', $unitName)
                        ->orWhere('short_code', $unitName);
                })->first();
            $data['unit_id'] = $unit?->id;
        }

        // Numeric fields
        $data['purchase_price'] = (float) ($row['purchase_price'] ?? $row['cost_price'] ?? $row['سعر_الشراء'] ?? 0);
        $data['sales_price'] = (float) ($row['sales_price'] ?? $row['selling_price'] ?? $row['سعر_البيع'] ?? 0);
        $data['wholesale_price'] = (float) ($row['wholesale_price'] ?? $row['سعر_الجملة'] ?? 0);
        $data['min_stock'] = (int) ($row['min_stock'] ?? $row['min_stock_level'] ?? $row['حد_أدني'] ?? 0);
        $data['reorder_point'] = (int) ($row['reorder_point'] ?? $row['reorder_level'] ?? $row['نقطة_اعادة'] ?? 0);
        $data['max_stock'] = (int) ($row['max_stock'] ?? $row['max_stock_level'] ?? $row['حد_أقصي'] ?? 0);

        // Boolean fields
        $data['is_active'] = filter_var($row['is_active'] ?? $row['active'] ?? $row['نشط'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['is_splittable'] = filter_var($row['is_splittable'] ?? $row['splittable'] ?? $row['قابل_للتجزئة'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $data;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function rules(): array
    {
        return [];
    }
}

