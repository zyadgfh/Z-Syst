<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Runtime permission grants that extend the static role map in
     * config/supabase.php. user_id references public.app_users (uuid).
     */
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();

            // Not used by the application's own queries — a grant is looked up by
            // user_id, and the model deliberately does NOT use TenantScoped, since
            // scoping the permission table by tenant would hide grants during auth
            // resolution and could lock a user out. This column exists because the
            // database enforces tenancy independently of Eloquent: user_permissions
            // has Row Level Security enabled with four policies of the form
            // `business_id = private.current_business_id()`, which protect direct
            // Supabase-client access. Production declares it nullable with no
            // foreign key, and this migration now reproduces that exactly, so
            // `migrate:fresh` yields a schema the RLS policies can be created on.
            $table->unsignedBigInteger('business_id')->nullable();

            // uuid, matching production and the comment above: grants are looked up by
            // app_users.id, which is a uuid.
            $table->uuid('user_id');
            $table->string('name', 120);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
