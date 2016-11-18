<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDailyMonth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $startDate = '2015-04-01';
        $endDate = date("Y-m-d");
        for (
            $i = $startDate;
            date("Y-m",strtotime($i)) <= date("Y-m",strtotime($endDate));
            $i = date('Y-m-01', strtotime("$i +1 month"))
        ) {
                $daily = 'up_data_hourly_daily_'. date("Ym", strtotime($i));
                $sql = "CREATE TABLE {$daily} (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `ad_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL,
  `zone_id` int(10) unsigned NOT NULL,
  `requests` int(10) unsigned NOT NULL DEFAULT '0',
  `impressions` int(10) unsigned NOT NULL DEFAULT '0',
  `win_count` int(10) DEFAULT '0',
  `total_revenue` decimal(10,4) DEFAULT '0.0000',
  `total_revenue_gift` decimal(10,4) DEFAULT '0.0000',
  `af_income` decimal(10,2) DEFAULT '0.00',
  `clicks` int(10) unsigned NOT NULL DEFAULT '0',
  `conversions` int(10) unsigned NOT NULL DEFAULT '0',
  `cpa` int(11) DEFAULT '0',
  `consum` decimal(10,2) DEFAULT '0.00',
  `file_click` int(11) DEFAULT '0',
  `file_down` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `up_data_hourly_daily_date_ad_id_zone_id_unique` (`date`,`campaign_id`,`ad_id`,`zone_id`),
  KEY `up_data_hourly_daily_ad_id_date` (`ad_id`,`date`),
  KEY `up_data_hourly_daily_campaign_id_date` (`campaign_id`,`date`),
  KEY `up_data_hourly_daily_date` (`date`),
  KEY `up_data_hourly_daily_zone_id_date` (`zone_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=98;
            ";
            DB::getPdo()->exec($sql);
                $client = 'up_data_hourly_daily_client_'. date("Ym", strtotime($i));
                $sql2 = "CREATE TABLE {$client} (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `ad_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL,
  `zone_id` int(10) unsigned NOT NULL,
  `requests` int(10) unsigned NOT NULL DEFAULT '0',
  `impressions` int(10) unsigned NOT NULL DEFAULT '0',
  `win_count` int(10) DEFAULT '0',
  `total_revenue` decimal(10,4) DEFAULT '0.0000',
  `total_revenue_gift` decimal(10,4) DEFAULT '0.0000',
  `af_income` decimal(10,2) DEFAULT '0.00',
  `clicks` int(10) unsigned NOT NULL DEFAULT '0',
  `conversions` int(10) unsigned NOT NULL DEFAULT '0',
  `cpa` int(11) DEFAULT '0',
  `consum` decimal(10,2) DEFAULT '0.00',
  `file_click` int(11) DEFAULT '0',
  `file_down` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `clientid` mediumint(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `up_data_hourly_daily_client_date_ad_id_zone_id_unique` (`date`,`campaign_id`,`ad_id`,`zone_id`),
  KEY `up_data_hourly_daily_client_ad_id_date` (`ad_id`,`date`),
  KEY `up_data_hourly_daily_client_campaign_id_date` (`campaign_id`,`date`),
  KEY `up_data_hourly_daily_client_date` (`date`),
  KEY `up_data_hourly_daily_client_zone_id_date` (`zone_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=109;
            ";
                DB::getPdo()->exec($sql2);
                $af = 'up_data_hourly_daily_af_'. date("Ym", strtotime($i));
                $sql3 = "CREATE TABLE {$af} (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `ad_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL,
  `zone_id` int(10) unsigned NOT NULL,
  `requests` int(10) unsigned NOT NULL DEFAULT '0',
  `impressions` int(10) unsigned NOT NULL DEFAULT '0',
  `win_count` int(10) DEFAULT '0',
  `total_revenue` decimal(10,4) DEFAULT '0.0000',
  `total_revenue_gift` decimal(10,4) DEFAULT '0.0000',
  `af_income` decimal(10,2) DEFAULT '0.00',
  `clicks` int(10) unsigned NOT NULL DEFAULT '0',
  `conversions` int(10) unsigned NOT NULL DEFAULT '0',
  `cpa` int(11) DEFAULT '0',
  `consum` decimal(10,2) DEFAULT '0.00',
  `file_click` int(11) DEFAULT '0',
  `file_down` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `affiliateid` mediumint(9) NOT NULL DEFAULT '0',
  `pay` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `up_data_hourly_daily_af_date_ad_id_zone_id_unique` (`date`,`campaign_id`,`ad_id`,`zone_id`),
  KEY `up_data_hourly_daily_af_ad_id_date` (`ad_id`,`date`),
  KEY `up_data_hourly_daily_af_campaign_id_date` (`campaign_id`,`date`),
  KEY `up_data_hourly_daily_af_date` (`date`),
  KEY `up_data_hourly_daily_af_zone_id_date` (`zone_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=123;
            ";
            DB::getPdo()->exec($sql3);
            $date = date("Ym", strtotime($i));
            DB::getPdo()->exec("INSERT INTO {$daily}
          SELECT * from up_data_hourly_daily where DATE_FORMAT(date,'%Y%m')={$date}");
            DB::getPdo()->exec("INSERT INTO {$client}
          SELECT * from up_data_hourly_daily_client where DATE_FORMAT(date,'%Y%m')= {$date}");
            DB::getPdo()->exec("INSERT INTO {$af}
          SELECT * from up_data_hourly_daily_af where DATE_FORMAT(date,'%Y%m')= {$date}");
        }
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
