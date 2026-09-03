<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_points', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('reference');
            $table->boolean('expiration_notification_sent')->default(false)->after('expires_at');
        });

        // Backfill: set expires_at for all existing earned points (12 months from created_at)
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::table('loyalty_points')
                ->where('type', 'earned')
                ->whereNull('expires_at')
                ->update(['expires_at' => DB::raw("datetime(created_at, '+12 months')")]);
        } else {
            DB::table('loyalty_points')
                ->where('type', 'earned')
                ->whereNull('expires_at')
                ->update(['expires_at' => DB::raw("DATE_ADD(created_at, INTERVAL 12 MONTH)")]);
        }
    }

    public function down(): void
    {
        Schema::table('loyalty_points', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'expiration_notification_sent']);
        });
    }
};
