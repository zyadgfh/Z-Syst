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
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('stocks')->onDelete('set null');
            $table->string('barcode_number', 50)->unique();
            $table->string('barcode_type', 20)->default('CODE128'); // CODE128, EAN13, UPC, QR
            $table->string('barcode_image')->nullable();
            $table->string('print_status', 20)->default('not_printed'); // not_printed, printed, reprinted
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('print_count')->default(0);
            $table->string('size', 20)->default('standard'); // small, standard, large
            $table->json('print_settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'branch_id']);
            $table->index('barcode_number');
            $table->index('product_id');
            $table->index('batch_id');
            $table->index('print_status');
            $table->index('created_at');
        });

        // Add barcode column to products table if not exists
        if (! Schema::hasColumn('products', 'barcode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('barcode', 50)->nullable()->after('sku');
                $table->index('barcode');
            });
        }

        // Add barcode column to stocks table if not exists
        if (! Schema::hasColumn('stocks', 'barcode')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->string('barcode', 50)->nullable()->after('batch_no');
                $table->index('barcode');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcodes');

        // Remove barcode column from products table
        if (Schema::hasColumn('products', 'barcode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['barcode']);
                $table->dropColumn('barcode');
            });
        }

        // Remove barcode column from stocks table
        if (Schema::hasColumn('stocks', 'barcode')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropIndex(['barcode']);
                $table->dropColumn('barcode');
            });
        }
    }
};
