<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_return_details', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_return_details', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('purchase_detail_id');
            }
            if (!Schema::hasColumn('purchase_return_details', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0)->after('return_qty');
            }
            if (!Schema::hasColumn('purchase_return_details', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('purchase_return_details', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('purchase_return_details', 'credit_amount')) {
                $table->decimal('credit_amount', 12, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('purchase_return_details', 'reason')) {
                $table->string('reason')->nullable()->after('credit_amount');
            }
            if (!Schema::hasColumn('purchase_return_details', 'batch_no')) {
                $table->string('batch_no')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('purchase_return_details', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->dropColumn(['product_id', 'unit_price', 'discount', 'tax', 'credit_amount', 'reason', 'batch_no', 'created_at', 'updated_at']);
        });
    }
};
