<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add soft deletes to Party (customers/suppliers)
        if (Schema::hasTable('parties') && !Schema::hasColumn('parties', 'deleted_at')) {
            Schema::table('parties', fn (Blueprint $t) => $t->softDeletes());
        }

        // Add soft deletes to Category
        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'deleted_at')) {
            Schema::table('categories', fn (Blueprint $t) => $t->softDeletes());
        }

        // Add soft deletes to Warehouse
        if (Schema::hasTable('warehouses') && !Schema::hasColumn('warehouses', 'deleted_at')) {
            Schema::table('warehouses', fn (Blueprint $t) => $t->softDeletes());
        }

        // Add soft deletes to Prescription
        if (Schema::hasTable('prescriptions') && !Schema::hasColumn('prescriptions', 'deleted_at')) {
            Schema::table('prescriptions', fn (Blueprint $t) => $t->softDeletes());
        }
    }

    public function down(): void
    {
        Schema::table('parties', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('categories', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('warehouses', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('prescriptions', fn (Blueprint $t) => $t->dropSoftDeletes());
    }
};
