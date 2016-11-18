<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryRepairLog20160512 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
CREATE TABLE `up_delivery_repair_log` (
    `delivery_repair_log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `campaignid` int(11) unsigned NOT NULL,
    `bannerid` int(11) NOT NULL,
    `zoneid` int(11) unsigned NOT NULL,
    `source` tinyint(4) unsigned NOT NULL,
    `amount` double NOT NULL,
    `amount_type` varchar(16) NOT NULL,
    `expense` decimal(10,2) NOT NULL,
    `comment` text,
    `source_comment` text NOT NULL,
    `status` tinyint(4) unsigned NOT NULL,
    `date_time` date DEFAULT NULL,
    `created_time` timestamp NULL DEFAULT NULL,
    `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`delivery_repair_log_id`),
    KEY `campaignid` (`campaignid`),
    KEY `bannerid` (`bannerid`),
    KEY `zoneid` (`zoneid`)
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
        Schema::drop('up_delivery_repair_log');
    }
}
