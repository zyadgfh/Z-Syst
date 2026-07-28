<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        // Add missing columns that the Product model expects
        // The model defines these in $attributes and $fillable
        $columns = [
            // Already added: track_inventory, is_splittable, is_taxable, price_currency, sales_price, product_code, is_controlled, pack_size, unit_of_measure
            'track_inventory' => ['type' => 'boolean', 'default' => true],
            'is_splittable' => ['type' => 'boolean', 'default' => false],
            'prescription_required' => ['type' => 'boolean', 'default' => false],
            'is_taxable' => ['type' => 'boolean', 'default' => true],
            'price_currency' => ['type' => 'string', 'default' => 'EGP', 'length' => 10],
            'sales_price' => ['type' => 'decimal', 'default' => 0, 'precision' => 12, 'scale' => 3],
            'product_code' => ['type' => 'string', 'default' => null, 'length' => 100, 'nullable' => true],
            'is_controlled' => ['type' => 'boolean', 'default' => false],
            'pack_size' => ['type' => 'integer', 'default' => 1],
            'unit_of_measure' => ['type' => 'string', 'default' => 'piece', 'length' => 50],

            // Extra columns used by ProductFactory and Product model
            'generic_name' => ['type' => 'string', 'default' => null, 'length' => 255, 'nullable' => true],
            'brand_name' => ['type' => 'string', 'default' => null, 'length' => 255, 'nullable' => true],
            'barcode' => ['type' => 'string', 'default' => null, 'length' => 100, 'nullable' => true],
            'selling_price' => ['type' => 'decimal', 'default' => 0, 'precision' => 12, 'scale' => 3],
            'wholesale_price' => ['type' => 'decimal', 'default' => 0, 'precision' => 12, 'scale' => 3],
            'created_by' => ['type' => 'string', 'default' => null, 'length' => 100, 'nullable' => true],
            'updated_by' => ['type' => 'string', 'default' => null, 'length' => 100, 'nullable' => true],
        ];

        Schema::table('products', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column => $config) {
                if (Schema::hasColumn('products', $column)) {
                    continue;
                }

                $col = match ($config['type']) {
                    'boolean' => $table->boolean($column)->default($config['default']),
                    'integer' => $table->integer($column)->default($config['default']),
                    'string' => isset($config['length'])
                        ? $table->string($column, $config['length'])->nullable($config['nullable'] ?? true)->default($config['default'])
                        : $table->string($column)->nullable($config['nullable'] ?? true)->default($config['default']),
                    'decimal' => $table->decimal($column, $config['precision'] ?? 12, $config['scale'] ?? 3)->default($config['default']),
                    default => $table->string($column)->nullable(),
                };

                // Add comment
                $comments = [
                    'track_inventory' => 'تتبع المخزون',
                    'is_splittable' => 'قابل للتجزئة',
                    'prescription_required' => 'يحتاج وصفة طبية',
                    'is_taxable' => 'خاضع للضريبة',
                    'price_currency' => 'عملة السعر',
                    'sales_price' => 'سعر البيع',
                    'product_code' => 'كود المنتج',
                    'is_controlled' => 'مادة خاضعة للرقابة',
                    'pack_size' => 'حجم العلبة',
                    'unit_of_measure' => 'وحدة القياس',
                    'generic_name' => 'الاسم العلمي',
                    'brand_name' => 'الاسم التجاري',
                    'barcode' => 'الباركود',
                    'selling_price' => 'سعر البيع',
                    'wholesale_price' => 'سعر الجملة',
                    'created_by' => 'منشئ',
                    'updated_by' => 'محدث',
                ];

                if (method_exists($col, 'comment')) {
                    $col->comment($comments[$column] ?? $column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'track_inventory', 'is_splittable', 'prescription_required',
                'is_taxable', 'price_currency', 'sales_price', 'product_code', 'is_controlled',
                'pack_size', 'unit_of_measure',
                'generic_name', 'brand_name', 'barcode', 'selling_price', 'wholesale_price',
                'created_by', 'updated_by',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
