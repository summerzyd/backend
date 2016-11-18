<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDeliveryManualLogAddAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        ALTER TABLE `up_delivery_manual_log`
        ADD COLUMN `amount` int(10) NULL DEFAULT 0 AFTER `source_log_type`;
SQL;
        DB::getPdo()->exec($sql);
        
        $sql = <<<SQL
        ALTER TABLE `up_delivery_manual_log`
ADD UNIQUE INDEX `campaignid_zoneid_actiontime` (`campaignid`, `zoneid`, `actiontime`)
SQL;
        DB::getPdo()->exec($sql);
        
        $sql = <<<SQL
        ALTER TABLE `up_expense_manual_log`
        ADD COLUMN `amount` int(10) NULL DEFAULT 0 AFTER `source_log_type`;
SQL;
        DB::getPdo()->exec($sql);
        
        $sql = <<<SQL
        ALTER TABLE `up_expense_manual_log`
ADD UNIQUE INDEX `campaignid_zoneid_actiontime` (`campaignid`, `zoneid`, `actiontime`)
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
