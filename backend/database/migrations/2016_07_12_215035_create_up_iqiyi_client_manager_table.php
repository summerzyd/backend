<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpIqiyiClientManagerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //爱奇艺广告主管理
        Schema::create('iqiyi_client_manager', function (Blueprint $table) {
            $table->integer('clientid')->primary('clientid');
            $table->string('clientname');
            $table->string('industry');
            $table->tinyInteger('status')->default(1);
            $table->string('upload_op');
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
        Schema::drop('iqiyi_client_manager');
    }
}
