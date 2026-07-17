<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('drugs')) {
            Schema::create('drugs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('business_id')->nullable();
                $table->string('name');
                $table->string('scientific_name')->nullable();
                $table->string('barcode')->nullable()->unique();
                $table->string('generic_name')->nullable();
                $table->string('strength')->nullable();
                $table->string('form')->nullable();
                $table->string('manufacturer')->nullable();
                $table->decimal('purchase_price', 12, 2)->default(0);
                $table->decimal('sale_price', 12, 2)->default(0);
                $table->decimal('wholesale_price', 12, 2)->default(0);
                $table->integer('stock_alert')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['company_id', 'business_id']);
                $table->index('barcode');
                $table->index('name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
