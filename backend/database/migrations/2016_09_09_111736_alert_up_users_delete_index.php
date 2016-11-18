<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class alertUpUsersDeleteIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //删除掉平台大于1，类型为0的数据
       $sql = <<<SQL
        DROP INDEX up_users_username ON up_users;
        CREATE UNIQUE INDEX  up_users_username ON up_users(agencyid,username);
SQL;
        DB::getPdo()->exec($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
