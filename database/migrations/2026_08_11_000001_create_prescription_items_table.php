<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            
            $table->string('dosage')->nullable(); // الجرعة (مثال: 500mg)
            $table->string('frequency')->nullable(); // التكرار (مثال: 3 times daily)
            $table->string('duration')->nullable(); // المدة (مثال: 7 days)
            $table->text('instructions')->nullable(); // التعليمات (مثال: Take after meals)
            $table->integer('quantity')->default(0); // الكمية المطلوبة
            $table->integer('dispensed_quantity')->default(0); // الكمية المصرافة
            $table->boolean('dispensed')->default(false); // هل تم الصرف
            $table->timestamp('dispensed_at')->nullable(); // تاريخ الصرف
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['prescription_id', 'product_id']);
            $table->index(['business_id', 'dispensed']);
            $table->index('dispensed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};