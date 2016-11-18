<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUpRoles extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',60); //角色组名
            $table->string('description',60); //角色组描述
            $table->integer('type')->default(2); //创建人的Id
            $table->text('operation_list');//权限列表
            $table->integer('created_by'); //创建人的Id
            $table->integer('updated_by'); //修改人Id
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
        Schema::drop('roles');
    }

}
