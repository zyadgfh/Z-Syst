<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'business_id')) {
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('audit_logs', 'model_type')) {
                $table->string('model_type')->nullable();
            }

            if (!Schema::hasColumn('audit_logs', 'model_id')) {
                $table->unsignedBigInteger('model_id')->nullable();
            }

            if (!Schema::hasColumn('audit_logs', 'old_values')) {
                $table->json('old_values')->nullable();
            }

            if (!Schema::hasColumn('audit_logs', 'new_values')) {
                $table->json('new_values')->nullable();
            }
        });

        $existingIndexes = collect(Schema::getIndexes('audit_logs'))
            ->pluck('name')
            ->all();

        $indexes = [
            'audit_logs_model_type_model_id_index' => ['model_type', 'model_id'],
            'audit_logs_business_id_created_at_index' => ['business_id', 'created_at'],
            'audit_logs_user_id_created_at_index' => ['user_id', 'created_at'],
        ];

        foreach ($indexes as $name => $columns) {
            if (in_array($name, $existingIndexes, true)) {
                continue;
            }

            Schema::table('audit_logs', function (Blueprint $table) use ($name, $columns) {
                $table->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (['model_type', 'model_id', 'old_values', 'new_values'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
