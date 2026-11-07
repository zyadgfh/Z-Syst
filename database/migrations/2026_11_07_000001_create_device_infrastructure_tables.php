<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('barcode');   // barcode|receipt|label
            $table->string('connection', 20)->default('system'); // network|usb|bluetooth|system
            $table->string('host', 120)->nullable();          // ip:port (network) — informative
            $table->string('vendor', 60)->nullable();
            $table->string('model', 60)->nullable();
            $table->boolean('is_default_barcode')->default(false);
            $table->boolean('is_default_receipt')->default(false);
            $table->json('settings')->nullable();             // width_mm, dpi, codepage…
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_probe_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'type']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('uuid', 40)->unique();             // public identifier
            $table->string('token', 80)->unique()->nullable(); // POS/Flutter enrollment token
            $table->string('fingerprint', 64)->index();       // sha256(business|ua|platform|lang)
            $table->string('name');
            $table->string('type', 20)->default('pc');        // pos|tablet|pc|mobile|other
            $table->string('platform', 60)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('has_scanner')->default(true);    // keyboard-wedge assumed present
            $table->foreignId('barcode_printer_id')->nullable()->constrained('printers')->nullOnDelete();
            $table->foreignId('receipt_printer_id')->nullable()->constrained('printers')->nullOnDelete();
            $table->json('peripherals')->nullable();          // free-form hints (model, connection…)
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_current')->default(false);    // cosmetic marker set in UI flows
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'fingerprint']);
        });

        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_settings');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('printers');
    }
};
