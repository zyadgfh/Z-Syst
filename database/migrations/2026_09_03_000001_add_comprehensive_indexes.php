<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === Businesses & Plans ===
        $this->addIndexIfMissing('businesses', ['plan_subscribe_id', 'business_category_id', 'companyName']);
        $this->addIndexIfMissing('plans', ['status']);
        $this->addIndexIfMissing('business_categories', ['status']);

        // === Settings & Options (key-value lookups) ===
        $this->addIndexIfMissing('settings', ['key']);
        $this->addIndexIfMissing('options', ['key']);

        // === Gateways ===
        $this->addIndexIfMissing('gateways', ['status', 'namespace']);

        // === Loyalty ===
        $this->addIndexIfMissing('loyalty_programs', ['business_id', 'is_active']);

        // === Campaigns ===
        $this->addIndexIfMissing('campaigns', ['business_id', 'status', 'scheduled_at']);
        $this->addIndexIfMissing('campaign_metrics', ['campaign_id', 'metric_name']);

        // === Customer Orders ===
        $this->addIndexIfMissing('customer_order_items', ['customer_order_id', 'product_id']);
        $this->addIndexIfMissing('order_status_history', ['customer_order_id', 'status']);

        // === Subscription Plans ===
        $this->addIndexIfMissing('subscription_plans', ['is_active', 'slug']);
        $this->addIndexIfMissing('usage_records', ['business_id', 'metric_name']);

        // === Drug Interactions ===
        $this->addIndexIfMissing('drug_interactions', ['business_id', 'severity', 'drug_a_name']);

        // === Quality Checks ===
        $this->addIndexIfMissing('quality_checks', ['quality_status', 'check_date']);

        // === Approval Templates ===
        $this->addIndexIfMissing('approval_templates', ['business_id', 'type', 'is_active']);

        // === Exception Audit Log ===
        $this->addIndexIfMissing('exception_audit_log', ['action', 'actor_id']);

        // === Receipt & FEFO Settings ===
        $this->addIndexIfMissing('receipt_settings', ['business_id', 'is_active']);
        $this->addIndexIfMissing('fefo_settings', ['business_id', 'fefo_enabled']);
        $this->addIndexIfMissing('prediction_settings', ['business_id', 'prediction_enabled']);

        // === Inventory Reports ===
        $this->addIndexIfMissing('inventory_turnover_reports', ['business_id', 'report_type', 'period_start']);

        // === Push Notifications ===
        $this->addIndexIfMissing('push_notification_preferences', ['user_id', 'notification_type']);

        // === Tenant Payment Settings ===
        $this->addIndexIfMissing('company_payment_gateways', ['company_id', 'gateway_type', 'is_active']);

        // === Maintenance ===
        $this->addIndexIfMissing('maintenance_settings', ['is_enabled']);

        // === Workflow ===
        $this->addIndexIfMissing('workflow_steps', ['workflow_definition_id', 'sequence']);

        // === Onboarding ===
        $this->addIndexIfMissing('welcome_email_templates', ['onboarding_template_id', 'is_active']);
        $this->addIndexIfMissing('onboarding_automation_rules', ['onboarding_template_id', 'trigger_event']);

        // === Product Meta (units, manufacturers, medicine_types, taxes, box_sizes) ===
        $this->addIndexIfMissing('units', ['business_id', 'status']);
        $this->addIndexIfMissing('manufacturers', ['business_id', 'status']);
        $this->addIndexIfMissing('medicine_types', ['business_id', 'status']);
        $this->addIndexIfMissing('taxes', ['business_id', 'status']);
        $this->addIndexIfMissing('box_sizes', ['business_id', 'status']);

        // === Expense/Income Categories ===
        $this->addIndexIfMissing('expense_categories', ['business_id']);
        $this->addIndexIfMissing('income_categories', ['business_id']);

        // === Banners ===
        $this->addIndexIfMissing('banners', ['status']);

        // === Doctor Attention Settings ===
        $this->addIndexIfMissing('doctor_attention_settings', ['business_id', 'branch_id']);

        // === Messages & Testimonials & Features (landing module) ===
        $this->addIndexIfMissing('messages', ['business_id']);
        $this->addIndexIfMissing('testimonials', ['business_id']);
        $this->addIndexIfMissing('features', ['business_id']);
        $this->addIndexIfMissing('comments', ['business_id']);
    }

    public function down(): void
    {
        // Indexes are dropped automatically when the table is dropped
    }

    protected function addIndexIfMissing(string $table, array $columns): void
    {
        foreach ($columns as $col) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $col)) {
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
