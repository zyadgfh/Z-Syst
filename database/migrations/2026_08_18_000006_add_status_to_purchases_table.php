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
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status', ['pending', 'received', 'partial', 'canceled'])
                ->default('pending')
                ->after('note');

            $table->timestamp('received_at')->nullable()->after('status');
            $table->timestamp('canceled_at')->nullable()->after('received_at');

            $table->index('status');
            $table->index('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['status', 'received_at']);
            $table->dropColumn(['status', 'received_at', 'canceled_at']);
        });
    }
};
