<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unique invoice numbers per business (for sales)
        Schema::table('sales', function (Blueprint $table) {
            $table->unique(['business_id', 'invoiceNumber'], 'unique_sale_invoice_per_business');
        });

        // Unique invoice numbers per business (for purchases)
        Schema::table('purchases', function (Blueprint $table) {
            $table->unique(['business_id', 'invoiceNumber'], 'unique_purchase_invoice_per_business');
        });

        // Unique stock batches per business/product
        Schema::table('stocks', function (Blueprint $table) {
            $table->unique(['business_id', 'product_id', 'batch_no'], 'unique_stock_batch_per_business');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('unique_sale_invoice_per_business');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('unique_purchase_invoice_per_business');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique('unique_stock_batch_per_business');
        });
    }
};
