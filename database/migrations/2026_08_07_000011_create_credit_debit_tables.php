<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->string('credit_number')->unique();
            $table->date('credit_date');
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->string('reference')->nullable();
            $table->string('status')->default('pending');
            $table->foreignUuid('approved_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_debits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->string('debit_number')->unique();
            $table->date('debit_date');
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->string('reference')->nullable();
            $table->string('status')->default('pending');
            $table->foreignUuid('approved_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('credit_debit_items', function (Blueprint $table) {
            $table->id();

            // Production has always had this column; this migration never created
            // it, so `migrate:fresh` built a credit_debit_items without a tenant
            // key while production had one. A ledger line belongs to the same
            // business as its parent credit/debit, and both TenantScoped and the
            // database's RLS policies key off it — without the column a line could
            // not be attributed to a tenant at all. Declared NOT NULL with a
            // foreign key to match supplier_credits and supplier_debits above and
            // the state production was aligned to by
            // `enforce_tenant_ownership_on_business_id`.
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // Production also carried `type` and `description` here, leftovers from
            // an earlier design of this table — its column order showed it began as
            // (id, business_id, type, amount, description, timestamps) and had the
            // parent_id/parent_type/product_id design below appended later. Nothing
            // in the application read or wrote them, no index or view depended on
            // them, and the table was empty, so they were dropped from production by
            // `drop_legacy_type_and_description_from_credit_debit_items` rather than
            // enshrined here as dead columns. Do not re-add them without a consumer.

            $table->foreignId('parent_id');
            $table->string('parent_type');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_debit_items');
        Schema::dropIfExists('supplier_debits');
        Schema::dropIfExists('supplier_credits');
    }
};
