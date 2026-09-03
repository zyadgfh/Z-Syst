<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add foreign keys for tables that DON'T already have them
        // defined in their original create migrations.
        //
        // SKIP: products, sales, purchases, parties, sale_details,
        //       purchase_details, users — already have FKs in create migrations.
        //
        // NOTE: The original create migrations define:
        //   products.category_id     → cascadeOnDelete (correct for pharmacy)
        //   sales.party_id           → nullOnDelete
        //   purchases.party_id       → nullOnDelete
        //   users.business_id        → cascadeOnDelete

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });

        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('party_id')->references('id')->on('parties')->onDelete('cascade');
            $table->foreign('loyalty_program_id')->references('id')->on('loyalty_programs')->onDelete('cascade');
        });

        Schema::table('customer_interactions', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('party_id')->references('id')->on('parties')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies')->onDelete('cascade');
        });

        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('insurance_policy_id')->references('id')->on('insurance_policies')->onDelete('cascade');
        });

        Schema::table('batch_lots', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::table('recall_events', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        Schema::table('traceability_logs', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            // Note: traceability_logs.batch_lot_number is a string, not a FK to batch_lots
        });

        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['from_warehouse_id']);
            $table->dropForeign(['to_warehouse_id']);
        });

        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['party_id']);
            $table->dropForeign(['loyalty_program_id']);
        });

        Schema::table('customer_interactions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['party_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['sale_id']);
            $table->dropForeign(['purchase_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['insurance_company_id']);
        });

        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['insurance_policy_id']);
        });

        Schema::table('batch_lots', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('recall_events', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('traceability_logs', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['plan_id']);
        });
    }
};
