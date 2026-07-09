<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('drugs')) {
            Schema::create('drugs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->uuid('uuid')->unique();
                $table->string('name')->index();
                $table->string('generic_name')->nullable()->index();
                $table->string('barcode')->nullable();
                $table->string('manufacturer')->nullable();
                $table->string('form')->nullable();
                $table->string('strength')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->index(['company_id', 'barcode']);
                $table->unique(['company_id', 'name']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('drugs');
    }
}
