<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_returns', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('sale_id');
            }
            if (!Schema::hasColumn('sale_returns', 'reason')) {
                $table->string('reason')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('sale_returns', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('reason');
            }
            if (!Schema::hasColumn('sale_returns', 'refund_amount')) {
                $table->decimal('refund_amount', 12, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('sale_returns', 'status')) {
                $table->string('status')->default('pending')->after('refund_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $cols = ['user_id', 'reason', 'total_amount', 'refund_amount', 'status'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('sale_returns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
