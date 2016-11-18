<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpExpenseLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        CREATE TABLE `up_expense_log` (
            `expenseid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `campaignid` MEDIUMINT(9) NOT NULL,
            `zoneid` MEDIUMINT(9) NOT NULL,
            `cb` TEXT,
            `price` FLOAT NOT NULL,
            `price_gift` DECIMAL(11,2) DEFAULT '0.00',
            `actiontime` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            `target_type` VARCHAR(255) DEFAULT NULL,
            `target_cat` VARCHAR(255) DEFAULT NULL,
            `target_id` VARCHAR(255) DEFAULT NULL,
            `source` TINYINT(1) DEFAULT '0',
            `channel` VARCHAR(100) DEFAULT NULL,
            `af_income` DECIMAL(11,2) NOT NULL,
            `status` TINYINT(4) NOT NULL DEFAULT '0',
            `ad_id` INT(11) DEFAULT '0',
            `origin_zone_id` INT(11) DEFAULT '0',
            `refer` VARCHAR(50) DEFAULT NULL,
            `target_deliveryid` INT(11) DEFAULT NULL,
            `source_log_type` CHAR(16) NOT NULL DEFAULT '',
            `source_log_id` INT(11) NOT NULL DEFAULT '0',
            `updated_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`expenseid`, `actiontime`),
            KEY `idx_deliverylog_campaignid` (`campaignid`),
            KEY `idx_deliverylog_zoneid` (`zoneid`),
            KEY `up_delivery_log_target_type_target_cat_target_id_index` (`target_type`,`target_cat`,`target_id`),
            KEY `up_delivery_log_refer_target_deliveryid_index` (`refer`,`target_deliveryid`),
            KEY `actiontime` (`actiontime`),
            KEY `up_delivery_log_source_log_type_source_log_id` (`source_log_type`,`source_log_id`)
) PARTITION BY RANGE (TO_DAYS(actiontime))
(
PARTITION pm201507 VALUES LESS THAN (TO_DAYS('2015-07-01')),
PARTITION pm201508 VALUES LESS THAN (TO_DAYS('2015-08-01')),
PARTITION pm201509 VALUES LESS THAN (TO_DAYS('2015-09-01')),
PARTITION pm201510 VALUES LESS THAN (TO_DAYS('2015-10-01')),
PARTITION pm201511 VALUES LESS THAN (TO_DAYS('2015-11-01')),
PARTITION pm201512 VALUES LESS THAN (TO_DAYS('2015-12-01')),
PARTITION pm201601 VALUES LESS THAN (TO_DAYS('2016-01-01')),
PARTITION pm201602 VALUES LESS THAN (TO_DAYS('2016-02-01')),
PARTITION pm201603 VALUES LESS THAN (TO_DAYS('2016-03-01')),
PARTITION pm201604 VALUES LESS THAN (TO_DAYS('2016-04-01')),
PARTITION pm201605 VALUES LESS THAN (TO_DAYS('2016-05-01')),
PARTITION pm201606 VALUES LESS THAN (TO_DAYS('2016-06-01')),
PARTITION pm201607 VALUES LESS THAN (TO_DAYS('2016-07-01')),
PARTITION pm201608 VALUES LESS THAN (TO_DAYS('2016-08-01')),
PARTITION pm201609 VALUES LESS THAN (TO_DAYS('2016-09-01')),
PARTITION pm201610 VALUES LESS THAN (TO_DAYS('2016-10-01')),
PARTITION pm201611 VALUES LESS THAN (TO_DAYS('2016-11-01')),
PARTITION pm201612 VALUES LESS THAN (TO_DAYS('2016-12-01')),
PARTITION pm201701 VALUES LESS THAN (TO_DAYS('2017-01-01')),
PARTITION pm201702 VALUES LESS THAN (TO_DAYS('2017-02-01')),
PARTITION pm201703 VALUES LESS THAN (TO_DAYS('2017-03-01')),
PARTITION pm201704 VALUES LESS THAN (TO_DAYS('2017-04-01')),
PARTITION pm201705 VALUES LESS THAN (TO_DAYS('2017-05-01')),
PARTITION pm201706 VALUES LESS THAN (TO_DAYS('2017-06-01')),
PARTITION pm201707 VALUES LESS THAN (TO_DAYS('2017-07-01')),
PARTITION pm201708 VALUES LESS THAN (TO_DAYS('2017-08-01')),
PARTITION pm201709 VALUES LESS THAN (TO_DAYS('2017-09-01')),
PARTITION pm201710 VALUES LESS THAN (TO_DAYS('2017-10-01')),
PARTITION pm201711 VALUES LESS THAN (TO_DAYS('2017-11-01')),
PARTITION pm201712 VALUES LESS THAN (TO_DAYS('2017-12-01')),
PARTITION pm201801 VALUES LESS THAN (TO_DAYS('2018-01-01')),
PARTITION pm201802 VALUES LESS THAN (TO_DAYS('2018-02-01')),
PARTITION pm201803 VALUES LESS THAN (TO_DAYS('2018-03-01')),
PARTITION pm201804 VALUES LESS THAN (TO_DAYS('2018-04-01')),
PARTITION pm201805 VALUES LESS THAN (TO_DAYS('2018-05-01')),
PARTITION pm201806 VALUES LESS THAN (TO_DAYS('2018-06-01')),
PARTITION pm201807 VALUES LESS THAN (TO_DAYS('2018-07-01')),
PARTITION pm201808 VALUES LESS THAN (TO_DAYS('2018-08-01')),
PARTITION pm201809 VALUES LESS THAN (TO_DAYS('2018-09-01')),
PARTITION pm201810 VALUES LESS THAN (TO_DAYS('2018-10-01')),
PARTITION pm201811 VALUES LESS THAN (TO_DAYS('2018-11-01')),
PARTITION pm201812 VALUES LESS THAN (TO_DAYS('2018-12-01')),
PARTITION pm201901 VALUES LESS THAN (TO_DAYS('2019-01-01')),
PARTITION pm201902 VALUES LESS THAN (TO_DAYS('2019-02-01')),
PARTITION pm201903 VALUES LESS THAN (TO_DAYS('2019-03-01')),
PARTITION pm201904 VALUES LESS THAN (TO_DAYS('2019-04-01')),
PARTITION pm201905 VALUES LESS THAN (TO_DAYS('2019-05-01')),
PARTITION pm201906 VALUES LESS THAN (TO_DAYS('2019-06-01')),
PARTITION pm201907 VALUES LESS THAN (TO_DAYS('2019-07-01')),
PARTITION pm201908 VALUES LESS THAN (TO_DAYS('2019-08-01')),
PARTITION pm201909 VALUES LESS THAN (TO_DAYS('2019-09-01')),
PARTITION pm201910 VALUES LESS THAN (TO_DAYS('2019-10-01')),
PARTITION pm201911 VALUES LESS THAN (TO_DAYS('2019-11-01')),
PARTITION pm201912 VALUES LESS THAN (TO_DAYS('2019-12-01')),
PARTITION pm2020m VALUES LESS THAN MAXVALUE
);
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