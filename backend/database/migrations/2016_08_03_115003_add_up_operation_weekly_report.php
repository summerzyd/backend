<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpOperationWeeklyReport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = <<<SQL
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('17103', 'manager-weekly-report-view', 'BiddingOS周报', 'MANAGER');
        ALTER TABLE up_daily MODIFY date varchar(50);
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
