<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpDataSummaryAdxHourly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //新增ADX类存储字段
        $sql = <<<SQL
CREATE TABLE `up_data_summary_adx_daily` (
	`id` BIGINT (20) NOT NULL AUTO_INCREMENT,
	`affiliateid` INT (10) UNSIGNED NOT NULL,
	`external_zone_id` INT (10) UNSIGNED NOT NULL,
	`date` date NOT NULL,
	`bid_number` INT (10) UNSIGNED NOT NULL DEFAULT '0',
	`win_number` INT (10) UNSIGNED NOT NULL DEFAULT '0',
	`impressions` INT (10) UNSIGNED NOT NULL DEFAULT '0',
	`clicks` INT (10) UNSIGNED NOT NULL DEFAULT '0',
	`af_income` DECIMAL (10, 2) DEFAULT '0.00',
	`created_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`updated_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `up_data_summary_ad_hourlyx_date_time_zone_id_unique` (`date`, `external_zone_id`, `affiliateid`),
	KEY `up_data_summary_adx_hourly_date_time` (`date`)
) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8 AVG_ROW_LENGTH = 164;
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
