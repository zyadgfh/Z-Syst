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
        Schema::table('sales', function (Blueprint $table) {
            $table->double('change_amount')->default(0)->after('paidAmount');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->double('change_amount')->default(0)->after('paidAmount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('change_amount');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('change_amount');
        });
    }
};
