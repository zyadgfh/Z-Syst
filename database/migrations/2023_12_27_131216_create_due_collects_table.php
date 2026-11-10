<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mirrors public.due_collects in the live Supabase schema exactly (see the
     * `fix_due_collects_schema_parity` migration there). This table used to be
     * the one place where the application and the database disagreed: the code
     * wrote invoice_number/user_id/sale_id/purchase_id/payment_date plus three
     * camelCase money columns, none of which existed in production, so creating
     * a due collection and the dues report both failed.
     *
     * Money columns keep the canonical snake_case names production already had:
     *   amount      = total due on the invoice/party at collection time
     *   paid_amount = what was actually collected by this record
     *   due_amount  = what remains after this payment
     */
    public function up(): void
    {
        Schema::create('due_collects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2)->default(0);
            $table->string('payment_type')->default('Cash');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->string('invoice_number')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->cascadeOnDelete();

            // The dues report filters by tenant + party and ranges over payment_date.
            $table->index(['business_id', 'party_id']);
            $table->index(['business_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('due_collects');
    }
};
