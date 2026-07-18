<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id')->nullable()->comment('معرف الشركة');
            $table->string('name', 255)->comment('اسم الصلاحية');
            $table->string('slug', 100)->unique()->comment('slug الصلاحية');
            $table->string('group', 100)->nullable()->comment('المجموعة');
            $table->text('description')->nullable()->comment('الوصف');
            $table->boolean('is_system')->default(false)->comment('صلاحية نظام');
            $table->timestamps();

            // Unique Constraints
            $table->unique('slug', 'uniq_permissions_slug');

            // Indexes
            $table->index('company_id', 'idx_permissions_company_id');
            $table->index('group', 'idx_permissions_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};