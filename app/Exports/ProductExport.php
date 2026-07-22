<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldQueue
{
    use Exportable;

    public function __construct(
        protected ?string $companyId = null,
        protected ?string $categoryId = null,
        protected ?string $search = null,
        protected array $selectedIds = [],
    ) {}

    public function query(): Builder
    {
        $query = Product::query()
            ->with(['category:id,name', 'unit:id,unitName,short_code', 'manufacturer:id,name'])
            ->withSum('stocks', 'productStock');

        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            '#',
            'اسم المنتج (Product Name)',
            'الاسم العلمي (Generic Name)',
            'الاسم التجاري (Brand Name)',
            'الباركود (Barcode)',
            'كود المنتج (SKU)',
            'الفئة (Category)',
            'المصنع (Manufacturer)',
            'وحدة القياس (Unit)',
            'سعر الشراء (Purchase Price)',
            'سعر البيع (Sales Price)',
            'سعر الجملة (Wholesale)',
            'المخزون (Stock)',
            'الحد الأدنى (Min Stock)',
            'نقطة إعادة الطلب (Reorder)',
            'قابل للتجزئة (Splittable)',
            'مادة محكمة (Controlled)',
            'تاريخ الصلاحية (Expiry)',
            'نشط (Active)',
            'تاريخ الإنشاء (Created)',
        ];
    }

    public function map($product): array
    {
        $stocks = $product->stocks ?? collect();
        $totalStock = $stocks->sum('productStock');
        $nearestExpiry = $stocks->where('productStock', '>', 0)->min('expire_date');

        return [
            $product->id,
            $product->product_name ?? $product->name,
            $product->generic_name ?? '-',
            $product->brand_name ?? '-',
            $product->barcode ?? '-',
            $product->product_code ?? $product->sku ?? '-',
            $product->category?->name ?? $product->category?->categoryName ?? '-',
            $product->manufacturer?->name ?? '-',
            $product->unit?->unitName ?? $product->unit?->short_code ?? $product->unit_of_measure ?? '-',
            (float) ($product->purchase_price ?? 0),
            (float) ($product->sales_price ?? 0),
            (float) ($product->wholesale_price ?? 0),
            (int) ($totalStock ?? 0),
            (int) ($product->min_stock ?? 0),
            (int) ($product->reorder_point ?? $product->reorder_level ?? 0),
            $product->is_splittable ? 'نعم' : 'لا',
            $product->is_controlled ? 'نعم' : ($product->controlled_substance_schedule ? 'نعم (جدول ' . $product->controlled_substance_schedule . ')' : 'لا'),
            $nearestExpiry ? date('Y-m-d', strtotime($nearestExpiry)) : '-',
            $product->is_active ? 'نعم' : 'لا',
            $product->created_at?->format('Y-m-d H:i') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
                ],
            ],
        ];
    }
}

