<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip on SQLite — FK constraints require table recreation
        if ($this->isSQLite()) {
            return;
        }

        // purchases → businesses, parties, users, branches
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
            $table->foreign('party_id')->references('id')->on('parties')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        // purchase_details → purchases, products
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });

        // purchase_returns → purchases, parties, users, businesses
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();
            $table->foreign('party_id')->references('id')->on('parties')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
        });

        // purchase_return_details → purchase_returns, purchase_details, products, businesses
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->cascadeOnDelete();
            $table->foreign('purchase_detail_id')->references('id')->on('purchase_details')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
        });

        // sale_details → stocks
        Schema::table('sale_details', function (Blueprint $table) {
            $table->foreign('stock_id')->references('id')->on('stocks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if ($this->isSQLite()) {
            return;
        }

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
        });

        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id', 'purchase_detail_id', 'product_id', 'business_id']);
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['purchase_id', 'party_id', 'user_id', 'business_id']);
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropForeign(['purchase_id', 'product_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['business_id', 'party_id', 'user_id', 'branch_id']);
        });
    }

    protected function isSQLite(): bool
    {
        return config('database.default') === 'sqlite'
            || config('database.connections.' . config('database.default') . '.driver') === 'sqlite';
    }
};
