<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUpBrokers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
CREATE TABLE `up_brokers` (
    `brokerid` mediumint(9) NOT NULL AUTO_INCREMENT,
    `agencyid` mediumint(9) NOT NULL DEFAULT '0',
    `name` varchar(255) NOT NULL DEFAULT '',
    `brief_name` varchar(255) NOT NULL DEFAULT '',
    `contact` varchar(255) DEFAULT NULL,
    `email` varchar(64) NOT NULL DEFAULT '',
    `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `account_id` mediumint(9) DEFAULT NULL,
    `creator_uid` int(11) NOT NULL DEFAULT '0',
    `status` tinyint(4) NOT NULL DEFAULT '1',
    PRIMARY KEY (`brokerid`),
    UNIQUE KEY `up_brokes_agencyid_clientname_unique` (`agencyid`,`name`),
    UNIQUE KEY `up_brokes_agencyid_email_unique` (`agencyid`,`email`),
    UNIQUE KEY `up_brokes_account_id` (`account_id`)
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
        //
    }
}
