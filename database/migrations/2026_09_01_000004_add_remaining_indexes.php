<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === Remaining zero-index tables — add FK indexes ===

        // Purchase returns (financial records)
        $this->addIndexIfMissing('purchase_returns', ['business_id', 'purchase_id', 'party_id']);

        // GRN items (join-heavy detail table)
        $this->addIndexIfMissing('grn_items', ['grn_id', 'product_id']);

        // Purchase return details
        $this->addIndexIfMissing('purchase_return_details', ['purchase_return_id', 'product_id', 'business_id']);

        // Sale return details
        $this->addIndexIfMissing('sale_return_details', ['sale_return_id', 'sale_detail_id', 'business_id']);

        // Loyalty points
        $this->addIndexIfMissing('loyalty_points', ['user_id', 'business_id']);

        // Payment schedules
        $this->addIndexIfMissing('payment_schedules', ['supplier_id', 'business_id']);

        // Expense/income categories
        $this->addIndexIfMissing('expense_categories', ['business_id']);
        $this->addIndexIfMissing('income_categories', ['business_id']);

        // Approval workflows
        $this->addIndexIfMissing('approval_workflows', ['business_id', 'entity_id']);
        $this->addIndexIfMissing('approval_steps', ['workflow_id', 'approver_id']);

        // Budget
        $this->addIndexIfMissing('budget_transactions', ['budget_id', 'purchase_id']);
        $this->addIndexIfMissing('budget_alerts', ['budget_id', 'business_id']);

        // Campaign
        $this->addIndexIfMissing('campaign_metrics', ['campaign_id']);
        $this->addIndexIfMissing('campaign_recipients', ['campaign_id', 'party_id']);

        // Credit/debit
        $this->addIndexIfMissing('credit_debit_items', ['parent_id', 'product_id']);

        // Quality
        $this->addIndexIfMissing('quality_checks', ['grn_item_id']);
        $this->addIndexIfMissing('quality_reports', ['business_id', 'supplier_id']);

        // Exception audit log
        $this->addIndexIfMissing('exception_audit_log', ['business_id', 'user_id']);

        // Subscription logs
        $this->addIndexIfMissing('subscription_logs', ['subscription_id', 'business_id']);

        // Supplier performance
        $this->addIndexIfMissing('supplier_performance', ['supplier_id', 'business_id']);

        // Expense/income
        $this->addIndexIfMissing('expenses', ['business_id', 'user_id', 'expense_category_id']);
        $this->addIndexIfMissing('incomes', ['business_id', 'user_id', 'income_category_id']);

        // Subscription
        $this->addIndexIfMissing('subscriptions', ['business_id', 'plan_id', 'status']);
    }

    public function down(): void
    {
        // Indexes are dropped automatically when the table is dropped
        // For a clean rollback, we'd need to know each index name
        // This is intentionally left as a no-op for safety
    }

    protected function addIndexIfMissing(string $table, array $columns): void
    {
        foreach ($columns as $col) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $col)) {
                // Check for any index on this column (not just our naming convention)
                $indexes = DB::select("PRAGMA index_list('{$table}')");
                $hasIndex = false;
                foreach ($indexes as $idx) {
                    $info = DB::select("PRAGMA index_info('{$idx->name}')");
                    foreach ($info as $c) {
                        if ($c->name === $col) {
                            $hasIndex = true;
                            break 2;
                        }
                    }
                }

                if (! $hasIndex) {
                    Schema::table($table, function (Blueprint $t) use ($col) {
                        $t->index($col);
                    });
                }
            }
        }
    }
};
