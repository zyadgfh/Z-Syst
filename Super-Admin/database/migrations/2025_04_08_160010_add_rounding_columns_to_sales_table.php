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
            $table->string('rounding_option')->nullable()->after('totalAmount');
            $table->double('rounding_amount', 10, 2)->default(0)->after('totalAmount');
            $table->double('actual_total_amount', 10, 2)->default(0)->after('totalAmount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['rounding_option', 'rounding_amount', 'actual_total_amount']);
        });
    }
};
