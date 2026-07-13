<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (! Schema::hasColumn('sales', 'invoiceNumber')) {
                    $table->string('invoiceNumber')->nullable()->after('invoice_number');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'invoiceNumber')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('invoiceNumber');
            });
        }
    }
};
