<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('imported_by')->comment('من قام بالاستيراد');
            $table->string('file_name', 255)->comment('اسم الملف');
            $table->string('file_path', 500)->comment('مسار الملف');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->comment('حالة الاستيراد');
            $table->integer('total_rows')->default(0)->comment('إجمالي الصفوف');
            $table->integer('imported_rows')->default(0)->comment('الصفوف المستوردة');
            $table->integer('failed_rows')->default(0)->comment('الصفوف الفاشلة');
            $table->json('errors')->nullable()->comment('الأخطاء');
            $table->timestamps();

            $table->foreign('company_id', 'fk_pi_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('imported_by', 'fk_pi_imported_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('company_id', 'idx_pi_company_id');
            $table->index('status', 'idx_pi_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_imports');
    }
};

