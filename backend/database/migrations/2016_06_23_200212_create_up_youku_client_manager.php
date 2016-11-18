<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpYoukuClientManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('youku_client_manager', function (Blueprint $table) {
            $table->integer('clientid')->primary('clientid');
            $table->string('brand');
            $table->integer('firstindustry');
            $table->integer('secondindustry');
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('type');
            $table->string('reason');
            $table->dateTime('created_time');
            $table->dateTime('updated_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('youku_client_manager');
    }
}
