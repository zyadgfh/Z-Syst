<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('module'); // general, sales, purchases, inventory, products, customers, suppliers, invoices, payments, expenses, treasury, reports, notifications, printing, barcode, pos, security, audit
            $table->string('group')->default('general'); // sub-group within module
            $table->string('name'); // human-readable name
            $table->text('description')->nullable();
            $table->string('type')->default('boolean'); // boolean, string, integer, decimal, select, multi_select, json, color, date, time
            $table->json('type_options')->nullable(); // for select/multi_select: {options: [{value, label}]}
            $table->json('default_value')->nullable();
            $table->json('validation_rules')->nullable(); // Laravel validation rules as JSON
            $table->boolean('is_system_configurable')->default(false);
            $table->boolean('is_organization_configurable')->default(true);
            $table->boolean('is_branch_configurable')->default(true);
            $table->boolean('is_role_configurable')->default(true);
            $table->boolean('is_user_configurable')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['module', 'is_enabled']);
            $table->index(['module', 'group']);
            $table->index(['sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_definitions');
    }
};
