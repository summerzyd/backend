<?php

use Illuminate\Database\Migrations\Migration;

class AlterTableUpOperationEmail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('41901', 'manager-mail-report-view', 'BiddingOS日报', 'MANAGER');
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('99101', 'manager-mail-report-receiver', 'BiddingOS日报接收者', 'MANAGER');
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
