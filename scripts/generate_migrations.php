<?php

/**
 * Script لإنشاء جميع الهجرات المتبقية لقاعدة بيانات pharmacy_db
 * ينشئ 74 ملف هجرة بالترتيب الصحيح
 */

$migrations = [
    // CRM Group
    '2026_07_18_000024_create_insurance_companies_table.php' => 'insurance_companies',
    '2026_07_18_000025_create_customers_table.php' => 'customers',
    '2026_07_18_000026_create_doctors_table.php' => 'doctors',

    // Supply Chain Group
    '2026_07_18_000027_create_suppliers_table.php' => 'suppliers',
    '2026_07_18_000028_create_purchase_orders_table.php' => 'purchase_orders',
    '2026_07_18_000029_create_purchase_order_items_table.php' => 'purchase_order_items',
    '2026_07_18_000030_create_goods_received_notes_table.php' => 'goods_received_notes',
    '2026_07_18_000031_create_grn_items_table.php' => 'grn_items',
    '2026_07_18_000032_create_purchase_returns_table.php' => 'purchase_returns',
    '2026_07_18_000033_create_purchase_return_items_table.php' => 'purchase_return_items',

    // Inventory Group
    '2026_07_18_000034_create_inventory_table.php' => 'inventory',
    '2026_07_18_000035_create_stock_movements_table.php' => 'stock_movements',
    '2026_07_18_000036_create_stock_adjustments_table.php' => 'stock_adjustments',
    '2026_07_18_000037_create_stock_adjustment_items_table.php' => 'stock_adjustment_items',
    '2026_07_18_000038_create_stock_transfers_table.php' => 'stock_transfers',
    '2026_07_18_000039_create_stock_transfer_items_table.php' => 'stock_transfer_items',
    '2026_07_18_000040_create_stock_takes_table.php' => 'stock_takes',
    '2026_07_18_000041_create_stock_take_items_table.php' => 'stock_take_items',

    // Prescriptions Group
    '2026_07_18_000042_create_prescriptions_table.php' => 'prescriptions',
    '2026_07_18_000043_create_prescription_items_table.php' => 'prescription_items',
    '2026_07_18_000044_create_prescription_refills_table.php' => 'prescription_refills',
    '2026_07_18_000045_create_controlled_substances_log_table.php' => 'controlled_substances_log',

    // Sales & POS Group
    '2026_07_18_000046_create_coupons_table.php' => 'coupons',
    '2026_07_18_000047_create_cash_registers_table.php' => 'cash_registers',
    '2026_07_18_000048_create_sales_table.php' => 'sales',
    '2026_07_18_000049_create_sale_items_table.php' => 'sale_items',
    '2026_07_18_000050_create_sale_payments_table.php' => 'sale_payments',
    '2026_07_18_000051_create_sale_returns_table.php' => 'sale_returns',
    '2026_07_18_000052_create_sale_return_items_table.php' => 'sale_return_items',
    '2026_07_18_000053_create_cash_register_transactions_table.php' => 'cash_register_transactions',

    // Financial Group
    '2026_07_18_000054_create_expense_categories_table.php' => 'expense_categories',
    '2026_07_18_000055_create_expenses_table.php' => 'expenses',
    '2026_07_18_000056_create_payment_records_table.php' => 'payment_records',
    '2026_07_18_000057_create_accounts_table.php' => 'accounts',
    '2026_07_18_000058_create_journal_entries_table.php' => 'journal_entries',
    '2026_07_18_000059_create_journal_entry_lines_table.php' => 'journal_entry_lines',

    // Insurance Group
    '2026_07_18_000060_create_insurance_plans_table.php' => 'insurance_plans',
    '2026_07_18_000061_create_insurance_claims_table.php' => 'insurance_claims',
    '2026_07_18_000062_create_insurance_claim_items_table.php' => 'insurance_claim_items',

    // SaaS & Billing Group
    '2026_07_18_000063_create_subscription_plans_table.php' => 'subscription_plans',
    '2026_07_18_000064_create_subscriptions_table.php' => 'subscriptions',
    '2026_07_18_000065_create_invoices_table.php' => 'invoices',
    '2026_07_18_000066_create_invoice_items_table.php' => 'invoice_items',
    '2026_07_18_000067_create_usage_records_table.php' => 'usage_records',

    // System Group
    '2026_07_18_000068_create_settings_table.php' => 'settings',
    '2026_07_18_000069_create_notifications_table.php' => 'notifications',
    '2026_07_18_000070_create_activity_logs_table.php' => 'activity_logs',
    '2026_07_18_000071_create_audit_logs_table.php' => 'audit_logs',
    '2026_07_18_000072_create_announcements_table.php' => 'announcements',
    '2026_07_18_000073_create_support_tickets_table.php' => 'support_tickets',
    '2026_07_18_000074_create_ticket_messages_table.php' => 'ticket_messages',
];

echo "هذا الملف مرجعي - الرجاء تنفيذ artisan migrate:generate أو إنشاء الهجرات يدوياً\n";
echo "عدد الهجرات المتبقية: " . count($migrations) . " ملف\n";