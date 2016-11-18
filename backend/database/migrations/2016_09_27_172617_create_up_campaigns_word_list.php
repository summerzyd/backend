<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpCampaignsWordList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        CREATE TABLE `up_campaigns_word_list` (
            `cid` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `campaignid` int(11) NOT NULL,
            `content` varchar(1024) DEFAULT NULL,
            `vec` mediumtext DEFAULT NULL,
            `limit` int(11) NOT NULL DEFAULT '60',
            `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`cid`),
            KEY `up_campaigns_word_list_campaignid_index` (`campaignid`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
