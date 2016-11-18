<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUpAccountSubType extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_sub_type', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('account_type', 16)->comment('帐号类型，ADMIN,MANAGER,TRAFFICKER,ADVERTISER');
            $table->integer('account_department')->default(1)->comment('帐号部门，1:销售 2:媒介 3:财务 4:管理员 5:运营 6:审计员');
            $table->integer('default_role_id')->default(1)->comment('默认角色');
            $table->dateTime('created_at')->default('0000-00-00 00:00:00'); //创建时间
            $table->dateTime('updated_at')->default('0000-00-00 00:00:00'); //修改时间
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
        Schema::drop('account_sub_type');
    }

}
