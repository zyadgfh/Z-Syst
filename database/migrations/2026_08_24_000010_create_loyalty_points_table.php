<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points')->default(0);
            $table->enum('type', ['earned', 'redeemed', 'expired', 'adjusted'])->default('earned');
            $table->string('description')->nullable();
            $table->foreignId('customer_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable(); // e.g. 'order:123', 'manual'
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('loyalty_points_balance')->default(0)->after('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('loyalty_points_balance');
        });
    }
};
