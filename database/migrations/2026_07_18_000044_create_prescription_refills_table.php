<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_refills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prescription_id')->comment('معرف الوصفة');
            $table->integer('refill_number')->comment('رقم إعادة التعبئة');
            $table->date('refill_date')->comment('تاريخ إعادة التعبئة');
            $table->uuid('dispensed_by')->comment('صرف بواسطة');
            $table->decimal('total_amount', 15, 3)->default(0)->comment('الإجمالي');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('prescription_id', 'fk_prescription_refills_prescription_id')
                  ->references('id')->on('prescriptions')
                  ->onDelete('cascade');
            $table->foreign('dispensed_by', 'fk_prescription_refills_dispensed_by')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['prescription_id', 'refill_number'], 'uniq_prescription_refills_number');

            // Indexes
            $table->index('prescription_id', 'idx_prescription_refills_prescription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_refills');
    }
};