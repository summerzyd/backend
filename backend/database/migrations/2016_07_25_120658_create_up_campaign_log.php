<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpCampaignLog extends Migration
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
        CREATE TABLE `up_campaign_log` (
            `id` int(10) NOT NULL AUTO_INCREMENT,
            `campaignid` mediumint(9) NOT NULL,
            `type` int(10) NOT NULL DEFAULT 1000,
            `operator` VARCHAR(255) NOT NULL DEFAULT '',
            `message` VARCHAR(1023) NOT NULL DEFAULT '',
            `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
            `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `up_campaign_log` (`campaignid`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
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
