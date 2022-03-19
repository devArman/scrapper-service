<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class CreateEbayTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebay_tokens', function (Blueprint $table) {
            $table->id();
            $table->integer('account_id');
            $table->string('token_type');
            $table->text('access_token');
            $table->string('refresh_token');
            $table->bigInteger('expires_in');
            $table->bigInteger('refresh_token_expires_in');
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
        Schema::dropIfExists('ebay_tokens');
    }
}
