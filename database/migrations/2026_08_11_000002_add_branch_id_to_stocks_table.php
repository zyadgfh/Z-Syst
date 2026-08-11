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
        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
            
            // Add composite index for business + branch
            $table->index(['business_id', 'branch_id']);
            
            // Add unique constraint for business + branch + product + batch
            $table->unique(['business_id', 'branch_id', 'product_id', 'batch_no'], 'unique_stock_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique('unique_stock_batch');
            $table->dropIndex(['business_id', 'branch_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};