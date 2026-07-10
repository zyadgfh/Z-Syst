<?php

use Illuminate\Support\Facades\DB;

function runUpdateForV6()
{
    DB::transaction(function () {

        DB::statement("
            INSERT INTO transactions (
                platform,
                transaction_type,
                type,
                amount,
                date,
                business_id,
                branch_id,
                user_id,
                reference_id,
                invoice_no,
                created_at,
                updated_at
            )
            SELECT
                'sale',
                'others',
                'credit',
                s.paidAmount - IFNULL(dc.total_paid, 0),
                s.saleDate,
                s.business_id,
                s.branch_id,
                s.user_id,
                s.id,
                s.invoiceNumber,
                NOW(),
                NOW()
            FROM sales s
            LEFT JOIN (
                SELECT sale_id, SUM(payDueAmount) total_paid
                FROM due_collects
                WHERE sale_id IS NOT NULL
                GROUP BY sale_id
            ) dc ON dc.sale_id = s.id
            WHERE s.paidAmount > IFNULL(dc.total_paid, 0)
        ");

        DB::statement("
            INSERT INTO transactions (
                platform,
                transaction_type,
                type,
                amount,
                date,
                business_id,
                branch_id,
                user_id,
                reference_id,
                invoice_no,
                created_at,
                updated_at
            )
            SELECT
                'purchase',
                'others',
                'debit',
                p.paidAmount - IFNULL(dc.total_paid, 0),
                p.purchaseDate,
                p.business_id,
                p.branch_id,
                p.user_id,
                p.id,
                p.invoiceNumber,
                NOW(),
                NOW()
            FROM purchases p
            LEFT JOIN (
                SELECT purchase_id, SUM(payDueAmount) total_paid
                FROM due_collects
                WHERE purchase_id IS NOT NULL
                GROUP BY purchase_id
            ) dc ON dc.purchase_id = p.id
            WHERE p.paidAmount > IFNULL(dc.total_paid, 0)
        ");

        if (moduleCheck('MultiBranchAddon')) {
            DB::statement("
                UPDATE parties p
                JOIN (
                    SELECT business_id, MIN(id) branch_id
                    FROM branches
                    GROUP BY business_id
                ) b ON b.business_id = p.business_id
                SET p.branch_id = b.branch_id
            ");
        }

    });
}
