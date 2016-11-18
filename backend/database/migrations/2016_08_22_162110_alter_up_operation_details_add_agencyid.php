<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpOperationDetailsAddAgencyid extends Migration
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
        ALTER TABLE `up_operation_details` ADD `agencyid` mediumint(9) NOT NULL DEFAULT '0' AFTER `day_time`;
        UPDATE `up_operation_details` SET `agencyid` = 2 WHERE 1 AND `agencyid` = 0;
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
