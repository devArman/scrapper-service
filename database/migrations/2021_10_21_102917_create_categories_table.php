<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('cat_id')->nullable();
            $table->string('parent_id')->nullable();
            $table->integer('domain_id')->nullable();
            $table->string('name')->nullable();
            $table->string('context_free_name')->nullable();
            $table->string('highest_rank')->nullable();
            $table->string('product_count')->nullable();
            $table->string('lowest_rank')->nullable();
            $table->dateTime('last_direct_update')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
}
