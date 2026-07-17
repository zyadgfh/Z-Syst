<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_sales')) {
            Schema::create('pos_sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('business_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('status')->default('completed');
                $table->decimal('subtotal', 12, 2)->nullable();
                $table->decimal('tax_amount', 12, 2)->nullable();
                $table->decimal('total_amount', 12, 2)->nullable();
                $table->string('payment_method')->nullable();
                $table->text('notes')->nullable();
                $table->json('items')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'business_id']);
            });
        }

        // Create pos_sale_items table for better inventory tracking
        if (! Schema::hasTable('pos_sale_items')) {
            Schema::create('pos_sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_sale_id')->constrained('pos_sales')->onDelete('cascade');
                $table->foreignId('drug_id')->nullable()->constrained()->nullOnDelete();
                $table->string('barcode')->nullable();
                $table->string('name');
                $table->decimal('unit_price', 12, 2);
                $table->integer('quantity');
                $table->decimal('line_total', 12, 2);
                $table->string('prescription_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
    }
};
