<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOperationTraffickerRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        UPDATE up_operations uo SET uo.id=13105 WHERE uo.name='manager-trafficker';
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('13106', 'manager-trafficker-account-all', '媒体商管理-All', 'MANAGER');
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('13107', 'manager-trafficker-account-self', '媒体商管理-Self', 'MANAGER');
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
