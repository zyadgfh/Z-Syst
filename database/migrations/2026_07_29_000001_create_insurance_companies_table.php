<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('integration_type', ['manual', 'api', 'hybrid'])->default('manual');
            $table->string('api_endpoint')->nullable();
            $table->text('api_credentials')->nullable(); // encrypted at rest by app cast
            $table->decimal('default_coverage_percent', 5, 2)->default(80.00);
            $table->decimal('default_copay_percent', 5, 2)->default(20.00);
            $table->integer('settlement_days')->default(30);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_companies');
    }
};
