<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOperationManagerRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        UPDATE up_operations uo SET uo.id=11101 WHERE uo.name='manager-home';
        UPDATE up_operations uo SET uo.id=19101 WHERE uo.name='manager-campaign';
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('11102', 'manager-trafficker-overview', '媒介概览', 'MANAGER');
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('11103', 'manager-sale-overview', '销售概览', 'MANAGER');
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
