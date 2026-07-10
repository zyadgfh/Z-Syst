<?php

use Illuminate\Support\Facades\DB;

function runUpdate()
{
    $users = DB::table('users')
        ->where('role', 'staff')
        ->whereNotNull('visibility')
        ->get();

    foreach ($users as $user) {
        if ($user->visibility) {
            $old = json_decode($user->visibility, true);
            $new = [];

            // Sales
            $new['sales'] = !empty($old['salePermission'])
                ? ["read" => "1", "create" => "1", "update" => "1", "delete" => "1"]
                : ["read" => "0", "create" => "0", "update" => "0", "delete" => "0"];

            // Products
            $new['products'] = !empty($old['productPermission'])
                ? ["read" => "1", "create" => "1", "update" => "1", "delete" => "1"]
                : ["read" => "0", "create" => "0", "update" => "0", "delete" => "0"];

            // Purchases
            $new['purchases'] = !empty($old['purchasePermission'])
                ? ["read" => "1", "create" => "1", "update" => "1", "delete" => "1"]
                : ["read" => "0", "create" => "0", "update" => "0", "delete" => "0"];

            // Expenses
            $new['expenses'] = !empty($old['addExpensePermission'])
                ? ["read" => "1", "create" => "1", "update" => "1", "delete" => "1"]
                : ["read" => "0", "create" => "0", "update" => "0", "delete" => "0"];

            // Incomes
            $new['incomes'] = !empty($old['addIncomePermission'])
                ? ["read" => "1", "create" => "1", "update" => "1", "delete" => "1"]
                : ["read" => "0", "create" => "0", "update" => "0", "delete" => "0"];

            // Stock
            $new['stocks'] = !empty($old['stockPermission'])
                ? ["read" => "1"]
                : ["read" => "0"];

            // Parties
            $new['parties'] = !empty($old['partiesPermission'])
                ? ["read" => "1", "create" => "1", "update" => "1", "delete" => "1"]
                : ["read" => "0", "create" => "0", "update" => "0", "delete" => "0"];

            // Dues
            $new['dues'] = !empty($old['dueListPermission'])
                ? ["read" => "1"]
                : ["read" => "0"];

            // Loss / Profit
            $new['loss-profits'] = !empty($old['lossProfitPermission'])
                ? ["read" => "1"]
                : ["read" => "0"];

            // Reports
            if (!empty($old['reportsPermission'])) {
                $new['sale-reports'] = ["read" => "1"];
                $new['purchase-reports'] = ["read" => "1"];
                $new['expense-reports'] = ["read" => "1"];
                $new['income-reports'] = ["read" => "1"];
                $new['stock-reports'] = ["read" => "1"];
                $new['due-reports'] = ["read" => "1"];
                $new['loss-profit-reports'] = ["read" => "1"];
            } else {
                $new['sale-reports'] = ["read" => "0"];
                $new['purchase-reports'] = ["read" => "0"];
                $new['expense-reports'] = ["read" => "0"];
                $new['income-reports'] = ["read" => "0"];
                $new['stock-reports'] = ["read" => "0"];
                $new['due-reports'] = ["read" => "0"];
                $new['loss-profit-reports'] = ["read" => "0"];
            }

            $new['manage-settings'] = ["read" => "0", "update" => "0"];

            DB::table('users')
                ->where('id', $user->id)
                ->update(['visibility' => json_encode($new)]);
        }
    }
}
