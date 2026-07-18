<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            $table->string('check_in_method')->default('qr'); // qr, manual, biometric
            $table->string('check_in_qr_token')->nullable(); // Token for QR-based check-in
            $table->string('ip_address')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'check_in_at']);
            $table->index(['branch_id', 'check_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};