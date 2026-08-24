<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_returns', 'party_id')) {
                $table->unsignedBigInteger('party_id')->nullable()->after('purchase_id');
            }
            if (!Schema::hasColumn('purchase_returns', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('party_id');
            }
            if (!Schema::hasColumn('purchase_returns', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('return_date');
            }
            if (!Schema::hasColumn('purchase_returns', 'credit_amount')) {
                $table->decimal('credit_amount', 12, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('purchase_returns', 'status')) {
                $table->string('status')->default('pending')->after('credit_amount');
            }
            if (!Schema::hasColumn('purchase_returns', 'reason')) {
                $table->string('reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_returns', 'notes')) {
                $table->text('notes')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['purchase_id', 'status']);
            $table->dropColumn(['party_id', 'user_id', 'total_amount', 'credit_amount', 'status', 'reason', 'notes']);
        });
    }
};
