<?php

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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('model_id')->nullable()->constrained('product_models')->nullOnDelete()->after('category_id');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete()->after('category_id');
            $table->string('product_type')->default('single')->nullable()->after('productPicture');
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['category_id']);
            $table->foreignId('category_id')->nullable()->change();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->foreignId('stock_id')->nullable()->constrained()->nullOnDelete()->after('product_id');
            $table->string('expire_date')->nullable()->after('quantities');
            $table->string('mfg_date')->nullable()->after('quantities');
            $table->double('productPurchasePrice')->default(0)->after('quantities');
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->foreignId('stock_id')->nullable()->constrained()->nullOnDelete()->after('product_id');
            $table->string('profit_percent')->nullable()->after('quantities');
            $table->string('mfg_date')->nullable()->after('quantities');
            $table->string('expire_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['model_id', 'warehouse_id', 'product_type']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['brand_id']);
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->cascadeOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['stock_id', 'expire_date', 'productPurchasePrice', 'mfg_date']);
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropColumn(['stock_id', 'profit_percent', 'mfg_date']);
            $table->date('expire_date')->nullable()->change();
        });
    }
};
