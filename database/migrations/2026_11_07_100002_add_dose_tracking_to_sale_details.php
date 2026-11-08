<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dose tracking on sold items: the pharmacist captures the dosing schedule
 * at the counter ("1-0-1 بعد الأكل" / BID / TID…), the sale line stores it,
 * and the POS can print dose labels whose CODE128 barcode re-opens the
 * instructions by scanning (DOSE:{sale}:{detail}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_details', function (Blueprint $t) {
            $t->string('dose', 160)->nullable()->after('quantities');
            $t->string('dose_code', 24)->nullable()->after('dose');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $t) {
            $t->dropColumn(['dose', 'dose_code']);
        });
    }
};
