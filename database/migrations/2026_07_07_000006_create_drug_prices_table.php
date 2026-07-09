<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugPricesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('drug_prices')) {
            Schema::create('drug_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('drug_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->index();
                $table->decimal('price', 15, 4);
                $table->decimal('cost', 15, 4)->nullable();
                $table->string('currency', 10)->default('EGP');
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_to')->nullable();
                $table->timestamps();

                $table->foreign('drug_id')->references('id')->on('drugs')->onDelete('cascade');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

                $table->index(['company_id', 'drug_id', 'branch_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('drug_prices');
    }
}
