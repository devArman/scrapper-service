<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name',500)->default('N/A');
            $table->string('asin');
            $table->string('url')->nullable();
            $table->string('image')->nullable();
            $table->string('cat_id')->nullable();
            $table->integer('on_amazon_page')->nullable();
            $table->float('price',10,2)->nullable();
            $table->string('currency')->default('GBP');
            $table->float('rating')->nullable();
            $table->integer('total_reviews')->default(0);
            $table->text('description')->nullable();
            $table->string('availability')->nullable();
            $table->integer('amazon_choice')->default(0);
            $table->integer('prime')->default(0);
            $table->string('dispatches_from')->nullable();
            $table->string('sold_by')->nullable();
            $table->json('feature_bullets')->nullable();
            $table->json('variants')->nullable();
            $table->float('possible_profit_min',10,2)->default(0);
            $table->float('possible_profit_max',10,2)->default(0);
            $table->json('ebay_products')->nullable();
            $table->boolean('amazon_product_list_scrapped')->default(false);
            $table->boolean('amazon_single_product_scrapped')->default(false);
            $table->boolean('keepa_api_scrapped')->default(false);
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
        Schema::dropIfExists('products');
    }
}
