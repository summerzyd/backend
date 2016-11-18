<?php
namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\LogHelper;

class JobUpdateDailyView extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_update_daily_view  {--build-date=} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily_month Update view';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $build_date = $this->option('build-date') ? $this->option('build-date') : date('Y-m');
        $date_time = strtotime($build_date. '-01 00:00:01');
        $date = date('Ym', $date_time);
        $this->createDailyTable('up_data_hourly_daily_'.$date);
        $this->createDailyClientTable('up_data_hourly_daily_client_'.$date);
        $this->createDailyAfTable('up_data_hourly_daily_af_'.$date);
        $daily = $this->getDailyTables('up_data_hourly_daily_', $date_time);
        DB::update("CREATE OR REPLACE VIEW up_data_hourly_daily AS  {$daily}");
        $client = $this->getDailyTables('up_data_hourly_daily_client_', $date_time);
        DB::update("CREATE OR REPLACE VIEW up_data_hourly_daily_client AS  {$client}");
        $af = $this->getDailyTables('up_data_hourly_daily_af_', $date_time);
        DB::update("CREATE OR REPLACE VIEW up_data_hourly_daily_af AS  {$af}");
    }
    private function getDailyTables($table, $date_time)
    {
        $current_month = date('Ym', $date_time);
        $last_month = date('Ym', strtotime('-1 month', $date_time));
        $before_month = date('Ym', strtotime('-2 month', $date_time));
        $daily = "SELECT * FROM  ". $table. $current_month.
            " UNION SELECT * FROM " .$table .$last_month.
            " UNION SELECT *  FROM  ". $table. $before_month
        ;
        return $daily;
    }
    private function createDailyTable($table)
    {
        $has = DB::select("SHOW TABLES LIKE '{$table}%'");
        if (count($has)) {
            LogHelper::info($table. '已创建');
        } else {
            $sql = "CREATE TABLE {$table} (
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
            DB::update($sql);
            LogHelper::info('创建'.$table);
        }
    }
    private function createDailyClientTable($table)
    {
        $has = DB::select("SHOW TABLES LIKE '{$table}%'");
        if (count($has)) {
            LogHelper::info($table. '已创建');
        } else {
            $sql = "CREATE TABLE {$table} (
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
            DB::update($sql);
            LogHelper::info('创建'.$table);
        }
    }

    /**
     * @param $table
     */
    private function createDailyAfTable($table)
    {
        $has = DB::select("SHOW TABLES LIKE '{$table}%'");
        if (count($has)) {
            LogHelper::info($table . '已创建');
        } else {
            $sql = "CREATE TABLE {$table} (
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
            DB::update($sql);
            LogHelper::info('创建' . $table);
        }
    }
}
