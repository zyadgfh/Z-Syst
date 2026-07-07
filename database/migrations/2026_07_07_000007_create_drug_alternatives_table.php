<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugAlternativesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('drug_alternatives')) {
            Schema::create('drug_alternatives', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('drug_id')->index();
                $table->unsignedBigInteger('alternative_drug_id')->index();
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('drug_id')->references('id')->on('drugs')->onDelete('cascade');
                $table->foreign('alternative_drug_id')->references('id')->on('drugs')->onDelete('cascade');

                $table->unique(['company_id','drug_id','alternative_drug_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('drug_alternatives');
    }
}
