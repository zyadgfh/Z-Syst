<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurance_claim_items')) {
            return;
        }
        Schema::create('insurance_claim_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_claim_id')->comment('معرف المطالبة');
            $table->uuid('sale_item_id')->nullable()->comment('معرف بند البيع');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->decimal('quantity', 15, 3)->default(1)->comment('الكمية');
            $table->decimal('claimed_amount', 15, 3)->default(0)->comment('المبلغ المطالب به');
            $table->decimal('approved_amount', 15, 3)->default(0)->comment('المبلغ المعتمد');
            $table->decimal('paid_amount', 15, 3)->default(0)->comment('المبلغ المدفوع');
            $table->text('rejection_reason')->nullable()->comment('سبب الرفض');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('insurance_claim_id', 'fk_insurance_claim_items_claim_id')
                  ->references('id')->on('insurance_claims')
                  ->onDelete('cascade');
            $table->foreign('sale_item_id', 'fk_insurance_claim_items_sale_item_id')
                  ->references('id')->on('sale_items')
                  ->onDelete('set null');
            $table->foreign('product_id', 'fk_insurance_claim_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('insurance_claim_id', 'idx_insurance_claim_items_claim_id');
            $table->index('product_id', 'idx_insurance_claim_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claim_items');
    }
};