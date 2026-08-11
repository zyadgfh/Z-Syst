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
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
            
            // Add composite index for business + branch
            $table->index(['business_id', 'branch_id']);
            
            // Add unique constraint for business + branch + invoice number
            $table->unique(['business_id', 'branch_id', 'invoiceNumber'], 'unique_purchase_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('unique_purchase_invoice');
            $table->dropIndex(['business_id', 'branch_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};