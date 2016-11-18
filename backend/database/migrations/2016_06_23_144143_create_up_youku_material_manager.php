<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpYoukuMaterialManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //优酷素材管理
        Schema::create('youku_material_manager', function (Blueprint $table) {
            $table->integer('id')->primary('id');
            $table->string('url');
            $table->date('startdate'); //角色组名
            $table->date('enddate'); //角色组描述
            $table->tinyInteger('status')->default(1); //创建人的Id
            $table->string('reason');//权限列表
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
        Schema::drop('youku_material_manager');
    }
}
