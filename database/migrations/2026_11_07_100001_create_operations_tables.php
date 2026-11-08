<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales operations unit: shifts & cash drawer, promotions/coupons,
 * storefront orders, deliveries, ETA e-invoice submissions, online payment
 * gateway transactions, light accounting (accounts + journal), anomaly
 * alerts, patient chronic meds, and POS offline-sync idempotency columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Shifts & cash drawer ────────────────────────────────────────
        Schema::create('shifts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignUuid('user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $t->string('shift_number')->unique();
            $t->decimal('opening_float', 12, 2)->default(0);
            $t->enum('status', ['open', 'closed'])->default('open');
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->decimal('expected_cash', 12, 2)->default(0);
            $t->decimal('counted_cash', 12, 2)->default(0);
            $t->decimal('difference', 12, 2)->default(0);
            $t->text('notes')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'status']);
        });

        Schema::create('shift_cash_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignUuid('user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $t->enum('type', ['cash_in', 'cash_out', 'sale_cash', 'refund']);
            $t->decimal('amount', 12, 2);
            $t->string('reason')->nullable();
            $t->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index(['shift_id', 'type']);
        });

        // ── Promotions & coupons ────────────────────────────────────────
        Schema::create('promotions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->enum('type', ['percent', 'fixed', 'qty_bonus', 'near_expiry']);
            $t->decimal('value', 12, 2)->default(0);
            $t->unsignedInteger('min_qty')->default(1);
            $t->unsignedInteger('bonus_qty')->default(0);
            $t->enum('applies_to', ['all', 'product', 'category'])->default('all');
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('coupon_code')->nullable()->unique();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->unsignedInteger('max_uses')->nullable();
            $t->unsignedInteger('used_count')->default(0);
            $t->boolean('is_active')->default(true);
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'is_active']);
        });

        // ── Storefront orders & deliveries ──────────────────────────────
        Schema::create('store_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('order_number')->unique();
            $t->string('customer_name');
            $t->string('customer_phone')->nullable();
            $t->string('address')->nullable();
            $t->enum('fulfillment', ['pickup', 'delivery'])->default('pickup');
            $t->enum('status', ['new', 'confirmed', 'ready', 'delivered', 'cancelled'])->default('new');
            $t->decimal('total', 12, 2)->default(0);
            $t->json('items');
            $t->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('delivery_id')->nullable()->constrained()->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'status']);
        });

        Schema::create('deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('store_order_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignUuid('courier_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $t->string('courier_name')->nullable();
            $t->string('customer_name')->nullable();
            $t->string('customer_phone')->nullable();
            $t->string('address')->nullable();
            $t->decimal('fee', 10, 2)->default(0);
            $t->enum('status', ['pending', 'assigned', 'picked', 'out_for_delivery', 'delivered', 'failed', 'cancelled'])->default('pending');
            $t->timestamp('assigned_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'status']);
        });

        // ── ETA e-invoice ───────────────────────────────────────────────
        Schema::create('eta_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->string('submission_uuid')->nullable()->index();
            $t->string('long_id')->nullable();
            $t->enum('status', ['pending', 'submitted', 'accepted', 'rejected', 'cancelled', 'error'])->default('pending');
            $t->json('request_payload')->nullable();
            $t->json('response_payload')->nullable();
            $t->text('error_message')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'status']);
        });

        // ── Online payment gateways ─────────────────────────────────────
        Schema::create('gateway_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('gateway', 32); // paymob | fawry | instapay
            $t->string('purpose', 32)->default('subscription'); // subscription | store_order | sale
            $t->unsignedBigInteger('payable_id')->nullable();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 8)->default('EGP');
            $t->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $t->string('reference')->nullable()->index();
            $t->json('payload')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'status']);
        });

        // ── Light accounting ────────────────────────────────────────────
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('code', 16);
            $t->string('name');
            $t->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $t->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $t->boolean('is_system')->default(false);
            $t->timestamps();
            $t->unique(['business_id', 'code']);
        });

        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->date('entry_date');
            $t->text('description')->nullable();
            $t->string('source', 32)->default('manual'); // sale | purchase | expense | manual | adjustment
            $t->unsignedBigInteger('source_id')->nullable();
            $t->decimal('amount', 14, 2);
            $t->json('lines'); // [{account_code, debit, credit, memo}]
            $t->foreignUuid('user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $t->timestamps();
            $t->index(['business_id', 'entry_date']);
        });

        // ── Anomaly alerts ──────────────────────────────────────────────
        Schema::create('anomaly_alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('type', 48);
            $t->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $t->string('title');
            $t->json('details')->nullable();
            $t->foreignUuid('user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $t->boolean('is_resolved')->default(false);
            $t->timestamps();
            $t->index(['business_id', 'is_resolved']);
        });

        // ── Patient chronic meds (refill reminders) ─────────────────────
        Schema::create('patient_meds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignId('party_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->boolean('chronic')->default(true);
            $t->unsignedInteger('days_supply')->default(30);
            $t->timestamp('last_filled_at')->nullable();
            $t->timestamp('next_refill_at')->nullable();
            $t->boolean('active')->default(true);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['business_id', 'next_refill_at']);
        });

        // ── Tenant storefront slug ──────────────────────────────────────
        Schema::table('businesses', function (Blueprint $t) {
            $t->string('store_slug')->nullable()->unique()->after('companyName');
        });

        // ── TOTP 2FA secret on users ────────────────────────────────────
        Schema::table('app_users', function (Blueprint $t) {
            $t->string('two_factor_secret')->nullable();
        });

        // ── POS offline sync + shift attribution on sales ───────────────
        Schema::table('sales', function (Blueprint $t) {
            $t->foreignId('shift_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $t->uuid('client_uuid')->nullable()->unique()->after('shift_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->dropConstrainedForeignId('shift_id');
            $t->dropColumn('client_uuid');
        });
        Schema::table('app_users', function (Blueprint $t) {
            $t->dropColumn('two_factor_secret');
        });
        Schema::table('businesses', function (Blueprint $t) {
            $t->dropColumn('store_slug');
        });
        foreach (['patient_meds', 'anomaly_alerts', 'journal_entries', 'accounts', 'gateway_transactions',
            'eta_invoices', 'deliveries', 'store_orders', 'promotions', 'shift_cash_movements', 'shifts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
