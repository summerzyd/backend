<?php

use Illuminate\Database\Migrations\Migration;

class CreateTableUpDataHourlyDaily extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
CREATE TABLE `up_data_hourly_daily` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `ad_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL,
  `zone_id` int(10) unsigned NOT NULL,
  `requests` int(10) unsigned NOT NULL DEFAULT '0',
  `impressions` int(10) unsigned NOT NULL DEFAULT '0',
  `total_revenue` decimal(10,4) DEFAULT NULL,
  `total_revenue_gift` decimal(10,4) DEFAULT '0.0000',
  `af_income` decimal(10,2) NOT NULL,
  `clicks` int(10) unsigned NOT NULL DEFAULT '0',
  `conversions` int(10) unsigned NOT NULL DEFAULT '0',
  `cpa` int(11) DEFAULT '0',
  `consum` decimal(10,2) DEFAULT '0.00',
  `file_click` int(11) DEFAULT '0',
  `file_down` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `up_data_hourly_daily_date_ad_id_zone_id_unique` (`date`,`campaign_id`,`ad_id`,`zone_id`),
  KEY `up_data_hourly_daily_date` (`date`),
  KEY `up_data_hourly_daily_ad_id_date` (`ad_id`,`date`),
  KEY `up_data_hourly_daily_zone_id_date` (`zone_id`,`date`)
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
