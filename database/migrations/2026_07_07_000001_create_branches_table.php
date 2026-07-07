<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('code')->nullable()->index();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('is_main')->default(false);
                $table->string('timezone')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('branches');
    }
}
