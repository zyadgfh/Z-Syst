<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('sale_id');
            $table->string('reason')->nullable()->after('user_id');
            $table->decimal('total_amount', 12, 2)->default(0)->after('reason');
            $table->decimal('refund_amount', 12, 2)->default(0)->after('total_amount');
            $table->string('status')->default('pending')->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'reason', 'total_amount', 'refund_amount', 'status']);
        });
    }
};
