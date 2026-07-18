<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('drug_a_id')->comment('الدواء أ');
            $table->uuid('drug_b_id')->comment('الدواء ب');
            $table->string('severity', 50)->comment('الخطورة');
            $table->text('description')->nullable()->comment('الوصف');
            $table->text('management')->nullable()->comment('الإدارة');
            $table->text('references')->nullable()->comment('المراجع');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('drug_a_id', 'fk_drug_interactions_drug_a')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
            $table->foreign('drug_b_id', 'fk_drug_interactions_drug_b')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['drug_a_id', 'drug_b_id'], 'uniq_drug_interactions_pair');

            // Check Constraint
            $table->check('drug_a_id < drug_b_id');

            // Indexes
            $table->index('severity', 'idx_drug_interactions_severity');
            $table->index('is_active', 'idx_drug_interactions_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_interactions');
    }
};