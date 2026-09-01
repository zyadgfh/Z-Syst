<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ── Identity ──
            if (! Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->nullable()->after('productCode');
            }
            if (! Schema::hasColumn('products', 'internal_code')) {
                $table->string('internal_code')->nullable()->after('sku');
            }
            if (! Schema::hasColumn('products', 'scientific_name')) {
                $table->string('scientific_name')->nullable()->after('productName');
            }
            if (! Schema::hasColumn('products', 'commercial_name')) {
                $table->string('commercial_name')->nullable()->after('scientific_name');
            }
            if (! Schema::hasColumn('products', 'short_name')) {
                $table->string('short_name')->nullable()->after('commercial_name');
            }
            if (! Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('short_name');
            }
            if (! Schema::hasColumn('products', 'notes')) {
                $table->text('notes')->nullable()->after('description');
            }

            // ── Classification ──
            if (! Schema::hasColumn('products', 'subcategory_id')) {
                $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained('categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('subcategory_id')->constrained('manufacturers')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type')->default('product')->after('brand_id');
            }
            if (! Schema::hasColumn('products', 'dosage_form')) {
                $table->string('dosage_form')->nullable()->after('product_type');
            }
            if (! Schema::hasColumn('products', 'route_of_administration')) {
                $table->string('route_of_administration')->nullable()->after('dosage_form');
            }
            if (! Schema::hasColumn('products', 'strength')) {
                $table->string('strength')->nullable()->after('route_of_administration');
            }
            if (! Schema::hasColumn('products', 'unit_type')) {
                $table->string('unit_type')->nullable()->after('strength');
            }

            // ── Barcode ──
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('internal_code');
            }
            if (! Schema::hasColumn('products', 'barcode_type')) {
                $table->string('barcode_type')->nullable()->after('barcode');
            }
            if (! Schema::hasColumn('products', 'gtin')) {
                $table->string('gtin')->nullable()->after('barcode_type');
            }

            // ── Units ──
            if (! Schema::hasColumn('products', 'purchase_unit_id')) {
                $table->foreignId('purchase_unit_id')->nullable()->after('unit_id')->constrained('units')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'sales_unit_id')) {
                $table->foreignId('sales_unit_id')->nullable()->after('purchase_unit_id')->constrained('units')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'conversion_factor')) {
                $table->decimal('conversion_factor', 10, 4)->default(1)->after('sales_unit_id');
            }
            if (! Schema::hasColumn('products', 'allow_fractional_quantity')) {
                $table->boolean('allow_fractional_quantity')->default(true)->after('conversion_factor');
            }

            // ── Pricing ──
            if (! Schema::hasColumn('products', 'minimum_selling_price')) {
                $table->double('minimum_selling_price')->nullable()->after('sales_price');
            }
            if (! Schema::hasColumn('products', 'special_price')) {
                $table->double('special_price')->nullable()->after('minimum_selling_price');
            }
            if (! Schema::hasColumn('products', 'tax_included')) {
                $table->boolean('tax_included')->default(false)->after('tax_type');
            }

            // ── Inventory Behavior ──
            if (! Schema::hasColumn('products', 'track_inventory')) {
                $table->boolean('track_inventory')->default(true)->after('alert_qty');
            }
            if (! Schema::hasColumn('products', 'minimum_stock')) {
                $table->integer('minimum_stock')->default(0)->after('track_inventory');
            }
            if (! Schema::hasColumn('products', 'maximum_stock')) {
                $table->integer('maximum_stock')->nullable()->after('minimum_stock');
            }
            if (! Schema::hasColumn('products', 'reorder_point')) {
                $table->integer('reorder_point')->default(0)->after('maximum_stock');
            }
            if (! Schema::hasColumn('products', 'reorder_quantity')) {
                $table->integer('reorder_quantity')->nullable()->after('reorder_point');
            }
            if (! Schema::hasColumn('products', 'safety_stock')) {
                $table->integer('safety_stock')->nullable()->after('reorder_quantity');
            }
            if (! Schema::hasColumn('products', 'allow_negative_stock')) {
                $table->boolean('allow_negative_stock')->default(false)->after('safety_stock');
            }
            if (! Schema::hasColumn('products', 'stock_status')) {
                $table->string('stock_status')->default('in_stock')->after('allow_negative_stock');
            }

            // ── Pharmacy Data ──
            if (! Schema::hasColumn('products', 'active_ingredient')) {
                $table->string('active_ingredient')->nullable()->after('meta');
            }
            if (! Schema::hasColumn('products', 'concentration')) {
                $table->string('concentration')->nullable()->after('active_ingredient');
            }
            if (! Schema::hasColumn('products', 'dosage')) {
                $table->string('dosage')->nullable()->after('concentration');
            }
            if (! Schema::hasColumn('products', 'package_size')) {
                $table->integer('package_size')->nullable()->after('dosage');
            }
            if (! Schema::hasColumn('products', 'package_unit')) {
                $table->string('package_unit')->nullable()->after('package_size');
            }
            if (! Schema::hasColumn('products', 'prescription_required')) {
                $table->boolean('prescription_required')->default(false)->after('package_unit');
            }
            if (! Schema::hasColumn('products', 'controlled_item')) {
                $table->boolean('controlled_item')->default(false)->after('prescription_required');
            }
            if (! Schema::hasColumn('products', 'refrigerated')) {
                $table->boolean('refrigerated')->default(false)->after('controlled_item');
            }
            if (! Schema::hasColumn('products', 'temperature_requirements')) {
                $table->string('temperature_requirements')->nullable()->after('refrigerated');
            }
            if (! Schema::hasColumn('products', 'storage_instructions')) {
                $table->text('storage_instructions')->nullable()->after('temperature_requirements');
            }

            // ── Expiration ──
            if (! Schema::hasColumn('products', 'track_expiration')) {
                $table->boolean('track_expiration')->default(false)->after('storage_instructions');
            }
            if (! Schema::hasColumn('products', 'minimum_remaining_shelf_life')) {
                $table->integer('minimum_remaining_shelf_life')->nullable()->after('track_expiration');
            }
            if (! Schema::hasColumn('products', 'expiration_warning_days')) {
                $table->integer('expiration_warning_days')->default(30)->after('minimum_remaining_shelf_life');
            }

            // ── Supplier ──
            if (! Schema::hasColumn('products', 'preferred_supplier_id')) {
                $table->foreignId('preferred_supplier_id')->nullable()->after('manufacturer_id')->constrained('parties')->nullOnDelete();
            }

            // ── Status ──
            if (! Schema::hasColumn('products', 'active')) {
                $table->boolean('active')->default(true)->after('stock_status');
            }
            if (! Schema::hasColumn('products', 'discontinued')) {
                $table->boolean('discontinued')->default(false)->after('active');
            }
            if (! Schema::hasColumn('products', 'archived')) {
                $table->boolean('archived')->default(false)->after('discontinued');
            }

            // ── Audit ──
            if (! Schema::hasColumn('products', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('archived')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            // ── Indexes (guarded against pre-existing indexes) ──
            if (! Schema::hasIndex('products', 'products_barcode_index')) {
                $table->index('barcode');
            }
            if (! Schema::hasIndex('products', 'products_sku_index')) {
                $table->index('sku');
            }
            if (! Schema::hasIndex('products', 'products_internal_code_index')) {
                $table->index('internal_code');
            }
            if (! Schema::hasIndex('products', 'products_scientific_name_index')) {
                $table->index('scientific_name');
            }
            if (! Schema::hasIndex('products', 'products_active_index')) {
                $table->index('active');
            }
            if (! Schema::hasIndex('products', 'products_stock_status_index')) {
                $table->index('stock_status');
            }
            if (! Schema::hasIndex('products', 'products_track_inventory_index')) {
                $table->index('track_inventory');
            }
            if (! Schema::hasIndex('products', 'products_prescription_required_index')) {
                $table->index('prescription_required');
            }
            if (! Schema::hasIndex('products', 'products_business_id_active_stock_status_index')) {
                $table->index(['business_id', 'active', 'stock_status']);
            }
            if (! Schema::hasIndex('products', 'products_business_id_category_id_index')) {
                $table->index(['business_id', 'category_id']);
            }
            if (! Schema::hasIndex('products', 'products_business_id_brand_id_index')) {
                $table->index(['business_id', 'brand_id']);
            }
            if (! Schema::hasIndex('products', 'products_business_id_manufacturer_id_index')) {
                $table->index(['business_id', 'manufacturer_id']);
            }
            if (! Schema::hasIndex('products', 'unique_product_barcode')) {
                $table->unique(['business_id', 'barcode'], 'unique_product_barcode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('unique_product_barcode');
            $table->dropIndex(['business_id', 'manufacturer_id']);
            $table->dropIndex(['business_id', 'brand_id']);
            $table->dropIndex(['business_id', 'category_id']);
            $table->dropIndex(['business_id', 'active', 'stock_status']);
            $table->dropIndex('prescription_required');
            $table->dropIndex('track_inventory');
            $table->dropIndex('stock_status');
            $table->dropIndex('active');
            $table->dropIndex('scientific_name');
            $table->dropIndex('internal_code');
            $table->dropIndex('sku');
            $table->dropIndex('barcode');

            $table->dropForeign(['preferred_supplier_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['purchase_unit_id']);
            $table->dropForeign(['sales_unit_id']);
            $table->dropForeign(['subcategory_id']);
            $table->dropForeign(['brand_id']);

            $table->dropColumn([
                'sku', 'internal_code', 'scientific_name', 'commercial_name', 'short_name',
                'description', 'notes', 'barcode', 'barcode_type', 'gtin',
                'subcategory_id', 'brand_id', 'product_type', 'dosage_form',
                'route_of_administration', 'strength', 'unit_type',
                'purchase_unit_id', 'sales_unit_id', 'conversion_factor', 'allow_fractional_quantity',
                'minimum_selling_price', 'special_price', 'tax_included',
                'track_inventory', 'minimum_stock', 'maximum_stock', 'reorder_point',
                'reorder_quantity', 'safety_stock', 'allow_negative_stock', 'stock_status',
                'active_ingredient', 'concentration', 'dosage', 'package_size', 'package_unit',
                'prescription_required', 'controlled_item', 'refrigerated',
                'temperature_requirements', 'storage_instructions',
                'track_expiration', 'minimum_remaining_shelf_life', 'expiration_warning_days',
                'preferred_supplier_id',
                'active', 'discontinued', 'archived',
                'created_by', 'updated_by',
            ]);
        });
    }
};
