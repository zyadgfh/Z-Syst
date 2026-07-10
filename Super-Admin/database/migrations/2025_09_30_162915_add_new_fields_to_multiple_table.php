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
        Schema::table('payment_types', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
            $table->decimal('balance', 15, 3)->default(0)->after('name');
            $table->decimal('opening_balance', 15, 3)->default(0)->after('name');
            $table->date('opening_date')->nullable()->after('name');
            $table->boolean('show_in_invoice')->default(1)->after('name');
            $table->longText('meta')->nullable()->after('name');
            $table->softDeletes();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->string('variant_name')->nullable()->after('productDealerPrice');
            $table->longText('variation_data')->nullable()->after('productDealerPrice');
            $table->longText('serial_numbers')->nullable()->after('productDealerPrice');
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->text('variation_ids')->nullable()->after('productCode');
            $table->integer('has_serial')->default(0)->after('productCode');
            $table->longText('warranty_guarantee_info')->nullable()->after('productCode');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->longText('warranty_guarantee_info')->nullable()->after('quantities');
            $table->double('discount')->default(0)->after('price');
        });

        Schema::table('gateways', function (Blueprint $table) {
            $table->longText('platform')->nullable()->after('name');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->longText('meta')->nullable()->after('vat_name');
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'opening_balance', 'opening_date', 'show_in_invoice', 'meta', 'balance','deleted_at']);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['variant_name', 'variation_data', 'serial_numbers']);
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['variation_ids', 'warranty_guarantee_info','has_serial']);
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['warranty_guarantee_info', 'discount']);
        });

        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn('platform');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('meta');
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
};
