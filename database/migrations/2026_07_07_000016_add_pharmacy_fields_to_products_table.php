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
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('generic_name')->nullable()->after('name');
            $table->string('brand_name')->nullable()->after('generic_name');
            $table->string('barcode')->nullable()->after('slug');
            $table->string('dosage_form')->nullable()->after('description');
            $table->string('strength')->nullable()->after('dosage_form');
            $table->string('unit_of_measure')->nullable()->after('strength');
            $table->boolean('prescription_required')->default(false)->after('unit_of_measure');
            $table->string('controlled_substance_schedule')->nullable()->after('prescription_required');
            $table->string('storage_conditions')->nullable()->after('controlled_substance_schedule');
            $table->decimal('min_stock_level', 10, 2)->default(0)->after('storage_conditions');
            $table->decimal('reorder_point', 10, 2)->default(0)->after('min_stock_level');
            $table->decimal('max_stock_level', 10, 2)->default(0)->after('reorder_point');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('wholesale_price');
            $table->string('image')->nullable()->after('tax_rate');

            $table->index('barcode');
            $table->index('generic_name');
            $table->index('prescription_required');
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
