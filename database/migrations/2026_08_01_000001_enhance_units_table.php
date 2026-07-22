<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('units')) {
            Schema::table('units', function (Blueprint $table) {
                if (!Schema::hasColumn('units', 'short_code')) {
                    $table->string('short_code', 20)->nullable()->after('unitName')->comment('رمز الوحدة المختصر (مثال: كجم, لتر, مل)');
                }
                if (!Schema::hasColumn('units', 'category')) {
                    $table->string('category', 50)->nullable()->after('short_code')->comment('تصنيف الوحدة (weight, volume, quantity, length)');
                }
                if (!Schema::hasColumn('units', 'is_fractional')) {
                    $table->boolean('is_fractional')->default(false)->after('category')->comment('قابل للكسر (نعم/لا)');
                }
                if (!Schema::hasColumn('units', 'precision')) {
                    $table->tinyInteger('precision')->default(0)->after('is_fractional')->comment('عدد المنازل العشرية');
                }
                if (!Schema::hasColumn('units', 'base_unit_id')) {
                    $table->foreignId('base_unit_id')->nullable()->after('precision')->constrained('units')->onDelete('set null')->comment('الوحدة الأساسية المرتبطة');
                }
                if (!Schema::hasColumn('units', 'conversion_factor')) {
                    $table->decimal('conversion_factor', 12, 4)->default(1)->after('base_unit_id')->comment('معامل التحويل للوحدة الأساسية');
                }
                if (!Schema::hasColumn('units', 'description')) {
                    $table->string('description', 255)->nullable()->after('conversion_factor')->comment('وصف الوحدة');
                }
                if (!Schema::hasColumn('units', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('units')) {
            Schema::table('units', function (Blueprint $table) {
                $columns = ['short_code', 'category', 'is_fractional', 'precision', 'base_unit_id', 'conversion_factor', 'description', 'is_active'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('units', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

