<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('name', 255)->comment('اسم نسبة الضريبة');
            $table->decimal('rate', 5, 2)->default(0)->comment('النسبة');
            $table->boolean('is_default')->default(false)->comment('الافتراضي');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_tax_rates_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            // Indexes
            $table->index('company_id', 'idx_tax_rates_company_id');
            $table->index('is_default', 'idx_tax_rates_is_default');
            $table->index('is_active', 'idx_tax_rates_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};