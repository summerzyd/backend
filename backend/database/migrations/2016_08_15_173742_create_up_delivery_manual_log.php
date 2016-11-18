<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpDeliveryManualLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        CREATE TABLE `up_delivery_manual_log` (
            `deliveryid` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `campaignid` int(11) NOT NULL,
            `zoneid` int(11) NOT NULL,
            `bannerid` int(11) DEFAULT NULL,
            `price` float NOT NULL,
            `price_gift` decimal(11,2) DEFAULT '0.00',
            `actiontime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `af_income` decimal(11,2) NOT NULL,
            `channel` varchar(100) DEFAULT NULL,
            `source_log_type` char(16) NOT NULL DEFAULT '',
            `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`deliveryid`),
            KEY `idx_deliverylog_campaignid` (`campaignid`),
            KEY `idx_deliverylog_zoneid` (`zoneid`),
            KEY `actiontime` (`actiontime`),
            KEY `up_delivery_log_source_log_type_source_log_id` (`source_log_type`),
            KEY `bannerid` (`bannerid`) USING BTREE
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
