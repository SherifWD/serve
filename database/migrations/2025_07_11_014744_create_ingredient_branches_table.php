<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIngredientBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('ingredient_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedBigInteger('branch_id');
            $table->decimal('stock', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['ingredient_id', 'branch_id']);
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ingredient_branches');
    }
}
