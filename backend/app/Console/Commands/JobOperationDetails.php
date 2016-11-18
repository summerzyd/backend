<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Models\Agency;
use App\Models\Client;

class JobOperationDetails extends Command
{
    protected $signature = 'job_operation_details   {--build-date=}  {--agencyid=}';
    
    protected $description = '计算每日运营明细.';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function handle()
    {
        $agencyId = $this->option('agencyid') ? $this->option('agencyid') : 0;
        if ($agencyId > 0) {
            $model = Agency::find($agencyId);
            if ($model) {
                $this->finish($model);
            }
        } else {
            $models = Agency::get();
            foreach ($models as $model) {
                $this->finish($model);
            }
        }
    }
    
    public function finish(Agency $agency)
    {
        $current = $this->option('build-date') ? $this->option('build-date') : date('Y-m-d');
        $yesterday = date('Y-m-d', (strtotime($current)-24*60*60));
        $dateArr = [$yesterday, $current];
        foreach ($dateArr as $k => $date) {
            $clients    =   0;
            $traffickers=   0;
            $partners   =   0;
            $manual_clients = 0;
            $manual_traffickers = 0;
            $manual_partners = 0;

            $startTime  =   date("Y-m-d 16:00:00", strtotime($date)-8*60*60);
            $endTime    =   $date." 15:59:59";
            $defaultAffiliateId = Client::DEFAULT_AFFILIATE_ID;

            $sql = "SELECT
                        '{$date}' AS `day_time`,
                        SUM(`total_revenue`) AS `total_revenue`,
                        SUM(af_income) AS `af_income`
                    FROM
                        `up_data_summary_ad_hourly`
                        INNER JOIN `up_banners` ON `up_banners`.bannerid = `up_data_summary_ad_hourly`.ad_id
                        INNER JOIN `up_campaigns` ON `up_campaigns`.campaignid = `up_banners`.campaignid
                        INNER JOIN `up_clients` ON `up_clients`.clientid = `up_campaigns`.clientid
                    WHERE 1
                        AND `up_clients`.agencyid = '{$agency->agencyid}'
                        AND `up_clients`.affiliateid = {$defaultAffiliateId}
                        AND '{$startTime}' <= date_time
                        AND date_time <= '{$endTime}'";
            $val    =   DB::selectOne($sql);
            $date   =   $val->day_time;
            $clients =  (null != $val->total_revenue) ? $val->total_revenue : 0;
            $traffickers= (null != $val->af_income ) ? $val->af_income : 0;
            $partners = $clients - $traffickers;
            $this->update(
                [
                    $date,
                    $agency->agencyid,
                    $clients,
                    $traffickers,
                    $partners,
                    $clients,
                    $traffickers,
                    $partners
                ]
            );
            
            //人工投放的数据，获取广告主的消耗 up_delivery_manual_log
            $tmp_manual_clients = 0;
            $tmp_manual_traffickers = 0;
/*
            $startTime  =   date("Y-m-d 16:00:00", strtotime($date)-8*60*60);
            $endTime    =   $date." 15:59:59";
            $string =   "SELECT 
                        sum(price) AS manual_clients,
                        sum(af_income) AS manual_traffickers,
                        (sum(price) - sum(af_income)) AS manual_partners
                        FROM up_delivery_log AS dl
                        INNER JOIN up_campaigns AS uc ON dl.campaignid = uc.campaignid
                        INNER JOIN up_clients AS cl ON uc.clientid = cl.clientid
                        WHERE 1
                        AND cl.affiliateid = {$defaultAffiliateId}
                        AND cl.agencyid = '{$agency->agencyid}'
                        AND '{$startTime}' <= `actiontime` AND `actiontime` <= '{$endTime}' 
                        AND 0 < dl.`zoneid`
                        AND 0 < dl.`campaignid`
                        AND dl.`source` = 1;";
            $dataArr = DB::selectOne($string);
            $tmp_manual_clients = !empty($dataArr->manual_clients) ? $dataArr->manual_clients : 0;
            $tmp_manual_traffickers = !empty($dataArr->manual_traffickers) ?
                $dataArr->manual_traffickers : 0;
*/
            $dateTime = gmdate("Y-m-d H:i:s", strtotime($date ." 03:00:00"));
            
            $string =   "SELECT SUM(price) AS manual_clients
                        FROM up_delivery_manual_log AS dm
                            INNER JOIN up_campaigns AS uc ON dm.campaignid = uc.campaignid
                            INNER JOIN up_clients AS cl ON uc.clientid = cl.clientid
                        WHERE 1
                            AND cl.agencyid = '{$agency->agencyid}'
                            AND cl.affiliateid = {$defaultAffiliateId}
                            AND actiontime = '{$dateTime}'";
            $dataClientsArr=   DB::selectOne($string);

            $strSql = "SELECT SUM(af_income) AS manual_traffickers
                        FROM up_expense_manual_log AS em
                        INNER JOIN up_campaigns AS uc ON em.campaignid = uc.campaignid
                        INNER JOIN up_clients AS cl ON uc.clientid = cl.clientid
                        WHERE 1
                        AND cl.agencyid = '{$agency->agencyid}'
                        AND cl.affiliateid = {$defaultAffiliateId}
                        AND actiontime = '{$dateTime}'";
            $dataAffiliateArr = DB::selectOne($strSql);
            
            $manual_clients = !empty($dataClientsArr->manual_clients) ? $dataClientsArr->manual_clients : 0;
            $manual_clients += $tmp_manual_clients;
            
            $manual_traffickers = !empty($dataAffiliateArr->manual_traffickers) ?
                $dataAffiliateArr->manual_traffickers : 0;
            $manual_traffickers += $tmp_manual_traffickers;
                    
            $manual_partners = $manual_clients - $manual_traffickers;
            
            $this->updateManual(
                [
                    $date,
                    $agency->agencyid,
                    $manual_clients,
                    $manual_traffickers,
                    $manual_partners,
                    $manual_clients,
                    $manual_traffickers,
                    $manual_partners
                ]
            );
        }
    }
    
    /**
     * 插入媒体商结算数据
     */
    private function update($newData)
    {
        $fields =   array('`day_time`','`agencyid`','`clients`','`traffickers`','`partners`');
        $arr_keys=  array_keys($fields);
        $strArr =   array();
        foreach ($arr_keys as $k => $v) {
            $strArr[]   =   '?';
        }
        $sql    =   "INSERT INTO up_operation_details(".implode(",", $fields).") VALUES (".implode(",", $strArr).")";
        $sql    .=  "ON DUPLICATE KEY UPDATE `clients` = ?,`traffickers` = ?,`partners` = ?;";
        DB::update($sql, $newData);
    }
    
    /**
     * 更新人工投放数据
     */
    private function updateManual($newData)
    {
        $fields =   array('`day_time`','`agencyid`','`manual_clients`','`manual_traffickers`','`manual_partners`');
        $arr_keys=  array_keys($fields);
        $strArr =   array();
        foreach ($arr_keys as $k => $v) {
            $strArr[]   =   '?';
        }
        $sql    =   "INSERT INTO up_operation_details(".implode(",", $fields).") VALUES (".implode(",", $strArr).")";
        $sql    .= "ON DUPLICATE KEY UPDATE `manual_clients` = ?,`manual_traffickers` = ?,`manual_partners` = ?;";
        DB::update($sql, $newData);
    }
}
