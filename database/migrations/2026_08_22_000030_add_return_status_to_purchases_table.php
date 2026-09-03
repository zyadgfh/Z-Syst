<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite: recreate all purchase-related tables without using RENAME
            // which breaks internal FK references
            DB::statement('PRAGMA foreign_keys = OFF');

            // Copy data before dropping
            $purchases = DB::table('purchases')->get();
            $purchaseDetails = DB::table('purchase_details')->get();
            $purchaseReturns = DB::table('purchase_returns')->get();
            $purchaseReturnDetails = DB::table('purchase_return_details')->get();

            // Drop tables in reverse dependency order
            DB::statement('DROP TABLE purchase_return_details');
            DB::statement('DROP TABLE purchase_returns');
            DB::statement('DROP TABLE purchase_details');
            DB::statement('DROP TABLE purchases');

            // Recreate purchases with updated status CHECK constraint
            DB::statement('
                CREATE TABLE "purchases" (
                    "id" integer primary key autoincrement not null,
                    "party_id" integer,
                    "business_id" integer not null,
                    "user_id" integer,
                    "tax_id" integer,
                    "discountAmount" double not null default (0),
                    "tax_amount" double not null default (0),
                    "dueAmount" double not null default (0),
                    "paidAmount" double not null default (0),
                    "totalAmount" double not null default (0),
                    "invoiceNumber" varchar,
                    "isPaid" tinyint(1) not null default (0),
                    "paymentType" varchar not null default (\'Cash\'),
                    "purchaseDate" datetime,
                    "purchase_data" text,
                    "note" varchar,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "branch_id" integer,
                    "status" varchar check ("status" in (\'pending\', \'received\', \'partial\', \'canceled\', \'returned_partially\', \'returned_fully\')) not null default \'pending\',
                    "received_at" datetime,
                    "canceled_at" datetime,
                    "deleted_at" datetime
                )
            ');

            // Recreate purchase_details
            DB::statement('
                CREATE TABLE "purchase_details" (
                    "id" integer primary key autoincrement not null,
                    "purchase_id" integer not null,
                    "product_id" integer not null,
                    "purchase_without_tax" double not null default (0),
                    "purchase_with_tax" double not null default (0),
                    "profit_percent" double not null default (0),
                    "sales_price" double not null default (0),
                    "wholesale_price" double not null default (0),
                    "quantities" integer not null default (0),
                    "batch_no" varchar,
                    "expire_date" date,
                    "created_at" datetime,
                    "updated_at" datetime
                )
            ');

            // Recreate purchase_returns
            DB::statement('
                CREATE TABLE "purchase_returns" (
                    "id" integer primary key autoincrement not null,
                    "business_id" integer not null,
                    "purchase_id" integer not null,
                    "invoice_no" varchar,
                    "return_date" datetime,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "party_id" integer,
                    "user_id" integer,
                    "total_amount" double not null default (0),
                    "credit_amount" double not null default (0),
                    "status" varchar not null default \'pending\',
                    "reason" varchar,
                    "notes" varchar
                )
            ');

            // Recreate purchase_return_details
            DB::statement('
                CREATE TABLE "purchase_return_details" (
                    "id" integer primary key autoincrement not null,
                    "purchase_return_id" integer not null,
                    "purchase_detail_id" integer not null,
                    "product_id" integer not null,
                    "return_qty" integer not null default (0),
                    "return_amount" double not null default (0),
                    "unit_price" double not null default (0),
                    "discount" double not null default (0),
                    "tax" double not null default (0),
                    "credit_amount" double not null default (0),
                    "reason" varchar,
                    "batch_no" varchar,
                    "business_id" integer,
                    "created_at" datetime,
                    "updated_at" datetime
                )
            ');

            // Restore data
            foreach ($purchases as $row) {
                DB::table('purchases')->insert((array) $row);
            }
            foreach ($purchaseDetails as $row) {
                DB::table('purchase_details')->insert((array) $row);
            }
            foreach ($purchaseReturns as $row) {
                DB::table('purchase_returns')->insert((array) $row);
            }
            foreach ($purchaseReturnDetails as $row) {
                DB::table('purchase_return_details')->insert((array) $row);
            }

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL approach: use raw SQL for the rename strategy
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            DB::statement('ALTER TABLE purchases RENAME TO purchases_old');
            DB::statement('ALTER TABLE purchase_details RENAME TO purchase_details_old');
            DB::statement('ALTER TABLE purchase_returns RENAME TO purchase_returns_old');
            DB::statement('ALTER TABLE purchase_return_details RENAME TO purchase_return_details_old');

            DB::statement('
                CREATE TABLE `purchases` (
                    `id` bigint unsigned auto_increment primary key,
                    `party_id` bigint unsigned null,
                    `business_id` bigint unsigned not null,
                    `user_id` bigint unsigned null,
                    `tax_id` bigint unsigned null,
                    `discountAmount` double not null default 0,
                    `tax_amount` double not null default 0,
                    `dueAmount` double not null default 0,
                    `paidAmount` double not null default 0,
                    `totalAmount` double not null default 0,
                    `invoiceNumber` varchar null,
                    `isPaid` tinyint(1) not null default 0,
                    `paymentType` varchar not null default \'Cash\',
                    `purchaseDate` datetime null,
                    `purchase_data` text null,
                    `note` varchar null,
                    `created_at` timestamp null,
                    `updated_at` timestamp null,
                    `branch_id` bigint unsigned null,
                    `status` enum(\'pending\', \'received\', \'partial\', \'canceled\', \'returned_partially\', \'returned_fully\') not null default \'pending\',
                    `received_at` datetime null,
                    `canceled_at` datetime null,
                    `deleted_at` datetime null
                ) ENGINE=InnoDB
            ');
            DB::statement('INSERT INTO purchases SELECT * FROM purchases_old');
            DB::statement('DROP TABLE purchases_old');

            DB::statement('
                CREATE TABLE `purchase_details` (
                    `id` bigint unsigned auto_increment primary key,
                    `purchase_id` bigint unsigned not null,
                    `product_id` bigint unsigned not null,
                    `purchase_without_tax` double not null default 0,
                    `purchase_with_tax` double not null default 0,
                    `profit_percent` double not null default 0,
                    `sales_price` double not null default 0,
                    `wholesale_price` double not null default 0,
                    `quantities` int not null default 0,
                    `batch_no` varchar null,
                    `expire_date` date null,
                    `created_at` timestamp null,
                    `updated_at` timestamp null
                ) ENGINE=InnoDB
            ');
            DB::statement('INSERT INTO purchase_details SELECT * FROM purchase_details_old');
            DB::statement('DROP TABLE purchase_details_old');

            DB::statement('
                CREATE TABLE `purchase_returns` (
                    `id` bigint unsigned auto_increment primary key,
                    `business_id` bigint unsigned not null,
                    `purchase_id` bigint unsigned not null,
                    `invoice_no` varchar null,
                    `return_date` datetime null,
                    `created_at` timestamp null,
                    `updated_at` timestamp null,
                    `party_id` bigint unsigned null,
                    `user_id` bigint unsigned null,
                    `total_amount` double not null default 0,
                    `credit_amount` double not null default 0,
                    `status` varchar not null default \'pending\',
                    `reason` varchar null,
                    `notes` varchar null
                ) ENGINE=InnoDB
            ');
            DB::statement('INSERT INTO purchase_returns SELECT * FROM purchase_returns_old');
            DB::statement('DROP TABLE purchase_returns_old');

            DB::statement('
                CREATE TABLE `purchase_return_details` (
                    `id` bigint unsigned auto_increment primary key,
                    `purchase_return_id` bigint unsigned not null,
                    `purchase_detail_id` bigint unsigned not null,
                    `product_id` bigint unsigned not null,
                    `return_qty` int not null default 0,
                    `return_amount` double not null default 0,
                    `unit_price` double not null default 0,
                    `discount` double not null default 0,
                    `tax` double not null default 0,
                    `credit_amount` double not null default 0,
                    `reason` varchar null,
                    `batch_no` varchar null,
                    `business_id` bigint unsigned null,
                    `created_at` timestamp null,
                    `updated_at` timestamp null
                ) ENGINE=InnoDB
            ');
            DB::statement('INSERT INTO purchase_return_details SELECT * FROM purchase_return_details_old');
            DB::statement('DROP TABLE purchase_return_details_old');

            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            $purchases = DB::table('purchases')->get();
            $purchaseDetails = DB::table('purchase_details')->get();
            $purchaseReturns = DB::table('purchase_returns')->get();
            $purchaseReturnDetails = DB::table('purchase_return_details')->get();

            DB::statement('DROP TABLE purchase_return_details');
            DB::statement('DROP TABLE purchase_returns');
            DB::statement('DROP TABLE purchase_details');
            DB::statement('DROP TABLE purchases');

            DB::statement('
                CREATE TABLE "purchases" (
                    "id" integer primary key autoincrement not null,
                    "party_id" integer,
                    "business_id" integer not null,
                    "user_id" integer,
                    "tax_id" integer,
                    "discountAmount" double not null default (0),
                    "tax_amount" double not null default (0),
                    "dueAmount" double not null default (0),
                    "paidAmount" double not null default (0),
                    "totalAmount" double not null default (0),
                    "invoiceNumber" varchar,
                    "isPaid" tinyint(1) not null default (0),
                    "paymentType" varchar not null default (\'Cash\'),
                    "purchaseDate" datetime,
                    "purchase_data" text,
                    "note" varchar,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "branch_id" integer,
                    "status" varchar check ("status" in (\'pending\', \'received\', \'partial\', \'canceled\')) not null default \'pending\',
                    "received_at" datetime,
                    "canceled_at" datetime,
                    "deleted_at" datetime
                )
            ');

            DB::statement('
                CREATE TABLE "purchase_details" (
                    "id" integer primary key autoincrement not null,
                    "purchase_id" integer not null,
                    "product_id" integer not null,
                    "purchase_without_tax" double not null default (0),
                    "purchase_with_tax" double not null default (0),
                    "profit_percent" double not null default (0),
                    "sales_price" double not null default (0),
                    "wholesale_price" double not null default (0),
                    "quantities" integer not null default (0),
                    "batch_no" varchar,
                    "expire_date" date,
                    "created_at" datetime,
                    "updated_at" datetime
                )
            ');

            DB::statement('
                CREATE TABLE "purchase_returns" (
                    "id" integer primary key autoincrement not null,
                    "business_id" integer not null,
                    "purchase_id" integer not null,
                    "invoice_no" varchar,
                    "return_date" datetime,
                    "created_at" datetime,
                    "updated_at" datetime,
                    "party_id" integer,
                    "user_id" integer,
                    "total_amount" double not null default (0),
                    "credit_amount" double not null default (0),
                    "status" varchar not null default \'pending\',
                    "reason" varchar,
                    "notes" varchar
                )
            ');

            DB::statement('
                CREATE TABLE "purchase_return_details" (
                    "id" integer primary key autoincrement not null,
                    "purchase_return_id" integer not null,
                    "purchase_detail_id" integer not null,
                    "product_id" integer not null,
                    "return_qty" integer not null default (0),
                    "return_amount" double not null default (0),
                    "unit_price" double not null default (0),
                    "discount" double not null default (0),
                    "tax" double not null default (0),
                    "credit_amount" double not null default (0),
                    "reason" varchar,
                    "batch_no" varchar,
                    "business_id" integer,
                    "created_at" datetime,
                    "updated_at" datetime
                )
            ');

            foreach ($purchases as $row) {
                DB::table('purchases')->insert((array) $row);
            }
            foreach ($purchaseDetails as $row) {
                DB::table('purchase_details')->insert((array) $row);
            }
            foreach ($purchaseReturns as $row) {
                DB::table('purchase_returns')->insert((array) $row);
            }
            foreach ($purchaseReturnDetails as $row) {
                DB::table('purchase_return_details')->insert((array) $row);
            }

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            // MySQL reverse...
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
};
