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
            if (!Schema::hasColumn('products', 'is_splittable')) {
                $table->boolean('is_splittable')->default(false)->after('is_active')->comment('منتج قابل للتجزئة');
            }
            if (!Schema::hasColumn('products', 'split_unit_id')) {
                $table->uuid('split_unit_id')->nullable()->after('is_splittable')->comment('وحدة التجزئة (الوحدة الصغرى)');
            }
            if (!Schema::hasColumn('products', 'split_quantity')) {
                $table->decimal('split_quantity', 12, 3)->nullable()->after('split_unit_id')->comment('كمية التجزئة (مثال: 1 حبة = 0.05 من العلبة)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['is_splittable', 'split_unit_id', 'split_quantity'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

