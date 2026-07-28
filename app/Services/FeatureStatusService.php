<?php

namespace App\Services;

class FeatureStatusService
{
    public function getFeatures(): array
    {
        return [
            [
                'key' => 'multi_tenancy',
                'label' => 'Multi-Tenancy',
                'status' => 'completed',
                'details' => 'Tenant context is resolved from authenticated business context.',
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
        ];
    }
}
