<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255)->comment('العنوان');
            $table->text('message')->comment('الرسالة');
            $table->string('type', 50)->default('info')->comment('النوع');
            $table->string('audience', 50)->default('all')->comment('الجمهور');
            $table->jsonb('companies_ids')->nullable()->comment('معرفات الشركات');
            $table->timestamp('starts_at')->nullable()->comment('تاريخ البدء');
            $table->timestamp('ends_at')->nullable()->comment('تاريخ الانتهاء');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('created_by', 'fk_announcements_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('type', 'idx_announcements_type');
            $table->index('is_active', 'idx_announcements_is_active');
            $table->index('starts_at', 'idx_announcements_starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};