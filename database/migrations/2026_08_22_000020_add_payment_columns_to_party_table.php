<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            if (!Schema::hasColumn('parties', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0)->after('due');
            }
            if (!Schema::hasColumn('parties', 'current_balance')) {
                $table->decimal('current_balance', 15, 2)->default(0)->after('opening_balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'current_balance']);
        });
    }
};
