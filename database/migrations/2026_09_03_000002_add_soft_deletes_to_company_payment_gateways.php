<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_payment_gateways', function (Blueprint $table) {
            if (!Schema::hasColumn('company_payment_gateways', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_payment_gateways', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
