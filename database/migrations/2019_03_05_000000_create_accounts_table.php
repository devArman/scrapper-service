<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('organization_id');
            $table->string('name', 255);
            $table->string('type', 50);
            $table->json('credentials');
            $table->timestamps();
        });
    }
}
