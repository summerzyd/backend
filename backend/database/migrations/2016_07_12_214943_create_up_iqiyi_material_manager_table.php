<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpIqiyiMaterialManagerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //爱奇艺素材管理
        Schema::create('iqiyi_material_manager', function (Blueprint $table) {
            $table->integer('id')->primary('id');
            $table->string('url');
            $table->string('m_id');
            $table->string('tv_id');
            $table->date('startdate'); 
            $table->date('enddate');
            $table->tinyInteger('status')->default(1);
            $table->string('reason');
            $table->dateTime('created_time');
            $table->dateTime('updated_time');
            $table->index(array('url'));
        });
          
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('iqiyi_material_manager');
    }
}
