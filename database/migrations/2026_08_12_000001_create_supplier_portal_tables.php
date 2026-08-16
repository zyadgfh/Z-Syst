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
        // Check if supplier_portal_users table already exists
        if (!Schema::hasTable('supplier_portal_users')) {
            // Supplier portal users table
            Schema::create('supplier_portal_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('party_id')->constrained()->cascadeOnDelete(); // Link to supplier party
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('role')->default('viewer'); // admin, manager, viewer
                $table->boolean('is_active')->default(true);
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['business_id', 'party_id']);
                $table->index('email');
            });
        }

        // Supplier order responses table
        if (!Schema::hasTable('supplier_order_responses')) {
            Schema::create('supplier_order_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_portal_user_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('response_type', ['accepted', 'rejected', 'modified', 'pending'])->default('pending');
                $table->text('response_notes')->nullable();
                $table->decimal('proposed_total', 10, 2)->nullable();
                $table->date('proposed_delivery_date')->nullable();
                $table->text('modifications')->nullable(); // JSON
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();
                
                $table->index(['business_id', 'purchase_order_id']);
                $table->index('response_type');
            });
        }

        // Supplier shipment tracking table
        if (!Schema::hasTable('supplier_shipments')) {
            Schema::create('supplier_shipments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_portal_user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('tracking_number')->nullable();
                $table->string('carrier')->nullable();
                $table->enum('status', ['pending', 'shipped', 'in_transit', 'delivered', 'cancelled'])->default('pending');
                $table->date('estimated_delivery_date')->nullable();
                $table->date('actual_delivery_date')->nullable();
                $table->text('shipping_notes')->nullable();
                $table->text('delivery_address')->nullable();
                $table->string('delivery_contact')->nullable();
                $table->string('delivery_phone')->nullable();
                $table->timestamps();
                
                $table->index(['business_id', 'purchase_order_id']);
                $table->index('status');
                $table->index('tracking_number');
            });
        }

        // Supplier invoice upload table
        if (!Schema::hasTable('supplier_portal_invoices')) {
            Schema::create('supplier_portal_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_portal_user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('invoice_number');
                $table->date('invoice_date');
                $table->decimal('amount', 10, 2);
                $table->string('currency')->default('USD');
                $table->string('file_path');
                $table->string('file_name');
                $table->string('file_mime_type');
                $table->enum('status', ['pending', 'verified', 'rejected', 'paid'])->default('pending');
                $table->text('verification_notes')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                
                $table->unique(['business_id', 'invoice_number']);
                $table->index(['business_id', 'purchase_order_id']);
                $table->index('status');
            });
        }

        // Note: supplier_ratings table already exists in 2026_08_07_000009_create_supplier_management_tables.php
        // We'll use that existing table instead of creating a duplicate

        // Supplier portal activity log
        if (!Schema::hasTable('supplier_portal_activities')) {
            Schema::create('supplier_portal_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_portal_user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action'); // login, view_order, respond, upload_invoice, etc.
                $table->string('entity_type')->nullable(); // purchase_order, shipment, invoice
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->text('details')->nullable(); // JSON
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                
                $table->index(['business_id', 'supplier_portal_user_id']);
                $table->index(['entity_type', 'entity_id']);
                $table->index('created_at');
            });
        }

        // Supplier notifications
        if (!Schema::hasTable('supplier_notifications')) {
            Schema::create('supplier_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_portal_user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type'); // new_order, order_update, payment_received, etc.
                $table->string('title');
                $table->text('message');
                $table->text('data')->nullable(); // JSON
                $table->boolean('read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                $table->index(['business_id', 'supplier_portal_user_id']);
                $table->index('read');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_notifications');
        Schema::dropIfExists('supplier_portal_activities');
        // Note: supplier_ratings is managed by 2026_08_07_000009_create_supplier_management_tables.php
        Schema::dropIfExists('supplier_portal_invoices');
        Schema::dropIfExists('supplier_shipments');
        Schema::dropIfExists('supplier_order_responses');
        Schema::dropIfExists('supplier_portal_users');
    }
};