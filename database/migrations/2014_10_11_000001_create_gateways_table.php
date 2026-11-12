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
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->string('mode'); // 1 = Sandbox || 0 = Live
            // boolean, not string: production stores boolean and GatewaySeeder writes
            // 1/'1'. A varchar column would also reject the integer the seeder binds.
            $table->boolean('status')->default(true);
            // charge is a money amount added to the payable total (Helper::convert_money,
            // PaymentController pay_amount), not a flag. Production stores numeric.
            $table->double('charge', 10, 2)->default(0);
            $table->string('image')->nullable();
            $table->text('data')->nullable();
            $table->json('manual_data')->nullable();
            $table->boolean('is_manual')->default(0);
            $table->boolean('accept_img')->default(0);
            $table->string('namespace')->nullable();
            $table->integer('phone_required')->default(0);
            $table->text('instructions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
