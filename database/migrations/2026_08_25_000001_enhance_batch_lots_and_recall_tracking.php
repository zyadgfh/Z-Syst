<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add quantity tracking and quarantine fields to batch_lots
        Schema::table('batch_lots', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->after('lot_number');
            $table->string('status', 20)->default('active')->after('recall_date');
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'expiry_date']);
        });

        // Create recall_affected_batches pivot table
        Schema::create('recall_affected_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recall_event_id')->constrained('recall_events')->cascadeOnDelete();
            $table->foreignId('batch_lot_id')->constrained('batch_lots')->cascadeOnDelete();
            $table->string('quarantine_status', 20)->default('pending'); // pending, quarantined, released, disposed
            $table->timestamp('quarantined_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('quantity_affected')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['recall_event_id', 'batch_lot_id']);
            $table->index(['quarantine_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recall_affected_batches');

        Schema::table('batch_lots', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['business_id', 'expiry_date']);
            $table->dropColumn(['quantity', 'status']);
        });
    }
};
