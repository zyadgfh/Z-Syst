<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('to_branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('shipped_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('restrict');

            $table->string('transfer_number')->unique();
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'in_transit',
                'received',
                'cancelled',
            ])->default('pending');

            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->decimal('total_items', 10, 2)->default(0);
            $table->decimal('total_quantity', 10, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['from_branch_id', 'status']);
            $table->index(['to_branch_id', 'status']);
            $table->index(['requested_at', 'status']);
            $table->index('transfer_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
