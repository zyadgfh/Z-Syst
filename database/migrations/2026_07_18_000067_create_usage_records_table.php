<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('metric', 100)->comment('المقياس');
            $table->integer('current_value')->default(0)->comment('القيمة الحالية');
            $table->integer('limit_value')->default(0)->comment('الحد الأقصى');
            $table->timestamp('recorded_at')->useCurrent()->comment('تاريخ التسجيل');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_usage_records_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            // Indexes
            $table->index('company_id', 'idx_usage_records_company_id');
            $table->index('metric', 'idx_usage_records_metric');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};