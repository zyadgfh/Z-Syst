<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'manufacturer_id')) {
                $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'generic_name')) {
                $table->string('generic_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('products', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('generic_name');
            }
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('products', 'dosage_form')) {
                $table->string('dosage_form')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'strength')) {
                $table->string('strength')->nullable()->after('dosage_form');
            }
            if (! Schema::hasColumn('products', 'unit_of_measure')) {
                $table->string('unit_of_measure')->nullable()->after('strength');
            }
            if (! Schema::hasColumn('products', 'prescription_required')) {
                $table->boolean('prescription_required')->default(false)->after('unit_of_measure');
            }
            if (! Schema::hasColumn('products', 'controlled_substance_schedule')) {
                $table->string('controlled_substance_schedule')->nullable()->after('prescription_required');
            }
            if (! Schema::hasColumn('products', 'storage_conditions')) {
                $table->string('storage_conditions')->nullable()->after('controlled_substance_schedule');
            }
            if (! Schema::hasColumn('products', 'min_stock_level')) {
                $table->decimal('min_stock_level', 10, 2)->default(0)->after('storage_conditions');
            }
            if (! Schema::hasColumn('products', 'reorder_point')) {
                $table->decimal('reorder_point', 10, 2)->default(0)->after('min_stock_level');
            }
            if (! Schema::hasColumn('products', 'max_stock_level')) {
                $table->decimal('max_stock_level', 10, 2)->default(0)->after('reorder_point');
            }
            if (! Schema::hasColumn('products', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('wholesale_price');
            }
            if (! Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->after('tax_rate');
            }

            if (! Schema::hasColumn('products', 'barcode')) {
                $table->index('barcode');
            }
            if (! Schema::hasColumn('products', 'generic_name')) {
                $table->index('generic_name');
            }
            if (! Schema::hasColumn('products', 'prescription_required')) {
                $table->index('prescription_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'manufacturer_id',
                'generic_name',
                'brand_name',
                'barcode',
                'dosage_form',
                'strength',
                'unit_of_measure',
                'prescription_required',
                'controlled_substance_schedule',
                'storage_conditions',
                'min_stock_level',
                'reorder_point',
                'max_stock_level',
                'tax_rate',
                'image',
            ]);
        });
    }
};
