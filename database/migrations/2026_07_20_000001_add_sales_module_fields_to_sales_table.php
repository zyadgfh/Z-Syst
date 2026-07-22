<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add modern sales module fields to the existing sales table.
     *
     * This migration bridges the legacy camelCase schema with the new
     * snake_case module fields, adding support for:
     * - Customer name/phone (walk-in POS sales)
     * - Branch tracking
     * - Subtotal, change, payment_method, status
     * - Prescription linkage
     * - Soft deletes
     * - Created/Updated by user IDs (UUID)
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // ── Customer info (for walk-in POS / non-registered customers) ──
            if (! Schema::hasColumn('sales', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('party_id');
            }
            if (! Schema::hasColumn('sales', 'customer_phone')) {
                $table->string('customer_phone', 50)->nullable()->after('customer_name');
            }

            // ── Branch tracking ──
            if (! Schema::hasColumn('sales', 'branch_id')) {
                $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete()->after('company_id');
            }

            // ── Modern totals ──
            if (! Schema::hasColumn('sales', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('totalAmount');
            }
            if (! Schema::hasColumn('sales', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
            }
            if (! Schema::hasColumn('sales', 'amount_paid')) {
                $table->decimal('amount_paid', 15, 2)->default(0)->after('tax_amount');
            }
            if (! Schema::hasColumn('sales', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->default(0)->after('amount_paid');
            }

            // ── Payment & Status ──
            if (! Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('paymentType');
            }
            if (! Schema::hasColumn('sales', 'status')) {
                $table->string('status', 50)->default('completed')->after('isPaid');
            }

            // ── Prescription linkage ──
            if (! Schema::hasColumn('sales', 'prescription_id')) {
                $table->foreignUuid('prescription_id')->nullable()->constrained('prescriptions')->nullOnDelete()->after('status');
            }

            // ── Notes & Metadata ──
            if (! Schema::hasColumn('sales', 'notes')) {
                $table->text('notes')->nullable()->after('meta');
            }

            // ── Audit columns ──
            if (! Schema::hasColumn('sales', 'created_by')) {
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete()->after('notes');
            }
            if (! Schema::hasColumn('sales', 'updated_by')) {
                $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            }

            // ── Soft deletes ──
            if (! Schema::hasColumn('sales', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            // ── Indexes for performance ──
            $table->index('customer_name');
            $table->index('customer_phone');
            $table->index('payment_method');
            $table->index('status');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $columns = [
                'customer_name', 'customer_phone', 'branch_id',
                'subtotal', 'discount_amount', 'amount_paid', 'change_amount',
                'payment_method', 'status', 'prescription_id',
                'notes', 'created_by', 'updated_by', 'deleted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

