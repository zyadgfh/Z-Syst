<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id')->nullable()->comment('معرف الشركة');
            $table->string('name', 255)->comment('اسم الدور');
            $table->string('slug', 100)->comment('slug الدور');
            $table->text('description')->nullable()->comment('الوصف');
            $table->boolean('is_system')->default(false)->comment('دور نظام');
            $table->integer('level')->default(0)->comment('المستوى');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_roles_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['company_id', 'slug'], 'uniq_roles_slug_company');

            // Indexes
            $table->index('company_id', 'idx_roles_company_id');
            $table->index('is_system', 'idx_roles_is_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
