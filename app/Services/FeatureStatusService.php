<?php

namespace App\Services;

class FeatureStatusService
{
    /**
     * Return the live feature status snapshot.
     *
     * The list is the public-facing "what's done" surface used by the
     * Flutter app and any status dashboards. Keep it honest: do not list
     * features that are not actually shipped end-to-end (migrations +
     * models + service + controller + routes + at least one test).
     */
    public function getFeatures(): array
    {
        return [
            [
                'key' => 'multi_tenancy',
                'label' => 'Multi-Tenancy',
                'status' => 'completed',
                'details' => 'Tenant context is resolved from authenticated business context via TenantResolver.',
            ],
            [
                'key' => 'pos_system',
                'label' => 'POS System',
                'status' => 'completed',
                'details' => 'Sales and invoices are handled through the existing API layer.',
            ],
            [
                'key' => 'inventory_management',
                'label' => 'Inventory Management',
                'status' => 'completed',
                'details' => 'Stock, FEFO, alerts, and turnover reporting are implemented.',
            ],
            [
                'key' => 'sales_invoices',
                'label' => 'Sales and Invoices',
                'status' => 'completed',
                'details' => 'Sales workflow and invoice generation are available.',
            ],
            [
                'key' => 'reports_statistics',
                'label' => 'Reports and Statistics',
                'status' => 'completed',
                'details' => 'Reports and dashboards are exposed through the API.',
            ],
            [
                'key' => 'mobile_app',
                'label' => 'Mobile App',
                'status' => 'completed',
                'details' => 'Flutter app project is included in the repository.',
            ],
            [
                'key' => 'alerts',
                'label' => 'Alerts',
                'status' => 'completed',
                'details' => 'Expiry alerts and notification flow are active.',
            ],
            [
                'key' => 'backup_automation',
                'label' => 'Backup Automation',
                'status' => 'completed',
                'details' => 'Database backup command is scheduled automatically.',
            ],
            [
                'key' => 'backup_from_ui',
                'label' => 'Backup from Mobile',
                'status' => 'completed',
                'details' => 'Users can trigger a backup from the mobile settings screen.',
            ],
            [
                'key' => 'drug_interactions',
                'label' => 'Drug Interaction Checker',
                'status' => 'completed',
                'details' => 'Drug interaction lookup during prescription dispense.',
            ],
            [
                'key' => 'fefo',
                'label' => 'FEFO (First Expiry, First Out)',
                'status' => 'completed',
                'details' => 'Stock deduction and alerting honor expiry dates.',
            ],
            [
                'key' => 'sales_prediction',
                'label' => 'AI Sales Prediction',
                'status' => 'completed',
                'details' => 'PredictionService forecasts sales and triggers auto-order suggestions.',
            ],
            [
                'key' => 'auto_order',
                'label' => 'Auto-Order',
                'status' => 'completed',
                'details' => 'Auto-order rules and suggestions based on forecasts and reorder points.',
            ],
            [
                'key' => 'inventory_turnover',
                'label' => 'Inventory Turnover Analysis',
                'status' => 'completed',
                'details' => 'Turnover ratio, DIO, ABC analysis, slow-moving and dead-stock reports.',
            ],
            [
                'key' => 'stock_audit',
                'label' => 'Stock Audit & Reconciliation',
                'status' => 'completed',
                'details' => 'Periodic/spot stock counts with variance reconciliation and posting.',
            ],
            [
                'key' => 'financial_audit',
                'label' => 'Financial Audit Log',
                'status' => 'completed',
                'details' => 'Per-audit financial transactions, comparisons, and statistics.',
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance',
                'status' => 'in_progress',
                'details' => 'Module scaffold pending. Tables, models, service, controller, and tests are in progress.',
            ],
            [
                'key' => 'multi_warehouse',
                'label' => 'Multi-Warehouse',
                'status' => 'pending',
                'details' => 'Not started. Needs warehouses, warehouse_stocks, and stock_transfers tables.',
            ],
            [
                'key' => 'traceability',
                'label' => 'Drug Recall & Traceability',
                'status' => 'pending',
                'details' => 'Not started. Needs batch serial tracking and recall event workflow.',
            ],
        ];
    }
}
