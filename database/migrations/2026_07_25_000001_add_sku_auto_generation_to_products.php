<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'product_code')) {
                $table->string('product_code', 100)
                    ->nullable()
                    ->after('sku')
                    ->comment('product auto code');
            }
            $table->index('product_code', 'idx_products_product_code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_product_code');
            if (Schema::hasColumn('products', 'product_code')) {
                $table->dropColumn('product_code');
            }
        });
    }
};

