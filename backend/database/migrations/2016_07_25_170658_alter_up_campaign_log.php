<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpCampaignLog extends Migration
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
        Rename TABLE `up_campaign_log` TO `up_operation_log`;
        ALTER TABLE `up_operation_log` CHANGE COLUMN `campaignid` `target_id` int(10) NOT NULL DEFAULT 0;
        ALTER TABLE `up_operation_log` ADD COLUMN `category` int(10) NOT NULL DEFAULT 110 AFTER `target_id`;
        ALTER TABLE `up_operation_log` ADD COLUMN `user_id` int(10) NOT NULL DEFAULT 1 AFTER `type`;
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
    }
}
