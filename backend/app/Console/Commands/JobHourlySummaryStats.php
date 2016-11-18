<?php
namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;
use App\Components\Helper\LogHelper;

class JobHourlySummaryStats extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_hourly_summary_stats {--build-time=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每小时更新hourly表的展示量，下载量，下载完成等';
    
    protected $revenue_type = [
        'CPM' => Campaign::REVENUE_TYPE_CPM,
        'CPD' => Campaign::REVENUE_TYPE_CPD,
        'CPA' => Campaign::REVENUE_TYPE_CPA,
        'CPT' => Campaign::REVENUE_TYPE_CPT,
        'CPC' => Campaign::REVENUE_TYPE_CPC,
    ];

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
    /**
     * Execute the console command with agency
     * @param $agency
     * @return mixed
     */
    public function finish(Agency $agency)
    {
        $dateTime = $this->option('build-time') ? $this->option('build-time') : date('Y-m-d H:00:00');
        $startTime = date("Y-m-d H:i:s", strtotime($dateTime. "- 10 hour")); //UTC时间
        $endTime = date("Y-m-d H:i:s", strtotime($dateTime. "- 8 hour -1 second")); //UTC时间
        $this->notice('startTime: ' . $startTime);
        $this->notice("endTime: " . $endTime);
        $this->notice('Beginning hourly summary stat');
        $transaction = DB::transaction(function () use ($startTime, $endTime, $agency) {
            $param = array($startTime, $endTime);
            //统计上2个小时的展示量
            $sql = $this->summaryImpressions($agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat impressions up_data_intermediate_ad->hourly: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat impressions error, sql: $sql param:" . json_encode($param));
                return false;
            }
            
            //从delivery_log,click_log,down_log统计上2个小时D2D，C2C，D2C等下载点击量，写入到hourly
            //统计广告主的数据
            //统计CPD广告的数据
            $sql = $this->updateSummaryClient($this->revenue_type['CPD'], $agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat hourly client CPD : ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly client CPD error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //统计CPC广告的数据
            $sql = $this->updateSummaryClient($this->revenue_type['CPC'], $agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat hourly client CPC : ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly client CPC error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //统计CPA广告的数据
            $sql = $this->updateSummaryClient($this->revenue_type['CPA'], $agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat hourly client CPA : ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly client CPA error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //统计CPM广告的数据
            $sql = $this->updateSummaryClient($this->revenue_type['CPM'], $agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat hourly client CPM : ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly client CPM error, sql: $sql param:" . json_encode($param));
                return false;
            }
            
            //统计媒体商的数据
            //统计媒体商的支出数据
            $sql = $this->updateSummaryAffiliate($agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat hourly Affiliate af_income : ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly Affiliate af_income error, sql: $sql param:" . json_encode($param));
                return false;
            }
            
            //统计广告主,媒体商计费方式存在转换关系的数据
            // 统计D数据
            $sql = $this->updateSummaryTransform($this->revenue_type['CPD'], $agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat hourly transform D: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly transform D error, sql: $sql param:" . json_encode($param));
                return false;
            }
            // 统计C数据
            $sql = $this->updateSummaryTransform($this->revenue_type['CPC'], $agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat hourly transform C: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly transform C error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //统计M数据
            $sql = $this->updateSummaryTransform($this->revenue_type['CPM'], $agency->agencyid);
            $query = DB::statement($sql, $param);
            echo 'stat hourly transform M: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly transform M error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //下载完成量更新到hourly表
            // 查询记录下载完成的数据表up_download_accomplished
            echo 'start to updating hourly file_down'. "\n";
            $downloads = DB::table('download_accomplished')->where('download_accomplished.status', 0)
            ->join('banners', 'download_accomplished.bannerid', '=', 'banners.bannerid')
            ->join('affiliates', 'banners.affiliateid', '=', 'affiliates.affiliateid')
            ->where('affiliates.agencyid', $agency->agencyid)
            ->groupBy('banners.bannerid', 'zoneid', 'date_time')
            ->get([
                'banners.bannerid',
                'zoneid',
                DB::raw("DATE_FORMAT(start_time,'%Y-%m-%d %H:00:00') as date_time"),
                DB::raw('COUNT(1) as file_down')
            ]);
            
            foreach ($downloads as $d) {
                $bannerId = $d->bannerid;
                $zoneId = $d->zoneid;
                $dateTime = $d->date_time;
                $fileDown = $d->file_down;
                $sql = DB::table('data_summary_ad_hourly')->where('ad_id', $bannerId)
                ->where('zone_id', $zoneId)
                ->where('date_time', $dateTime);
                $count = $sql->count();
                try {
                    if ($count > 0) {
                        $sql->update(['file_down' => DB::raw("file_down+{$fileDown}")]);
                    } else {
                        DB::table('data_summary_ad_hourly')->insert([
                        'ad_id' => $bannerId,
                        'zone_id' => $zoneId,
                        'date_time' => $dateTime,
                        'file_down' => $fileDown,
                        ]);
                    }
            
                    DB::table('download_accomplished')->where('bannerid', $bannerId)
                    ->where('zoneid', $zoneId)
                    ->whereRaw("DATE_FORMAT(start_time,'%Y-%m-%d %H:00:00') = '{$dateTime}'")
                    ->update([
                        'status' => 1
                    ]);
                } catch (\Exception $e) {
                    $this->error("Unkown Error: " . $e->getMessage());
                    LogHelper::error("Unkown Error: " . $e->getMessage());
                }
            }
            echo 'The end to updating hourly file_down'. "\n";
            return true;
        });
        
        if (! $transaction) {
            echo 'stat hourly summary error,script going to exit...' . "\n";
            $this->error('stat hourly summary error,script going to exit...');
            exit();
        }
        $this->notice('Leaving hourly summary stat');
    }


    /**
     * 前2个小时展示量统计
     * @param int $agencyId
     * @return string
     */
    protected function summaryImpressions($agencyId)
    {
        $sql = <<<SQL
INSERT INTO up_data_summary_ad_hourly(
    date_time,
    ad_id,
    creative_id,
    zone_id,
    requests,
    impressions,
    conversions,
    clicks,
    total_revenue,
    af_income,
    total_basket_value,
    total_num_items,
    updated
)
SELECT 
    DATE_FORMAT(date_time, '%Y-%m-%d %H:00:00') AS hour_date_time,
    ad_id,
    creative_id,
    zone_id,
    SUM(requests),
    SUM(impressions),
    0,
    0,
    0,
    0,
    SUM(total_basket_value),
    SUM(total_num_items),
    UTC_TIMESTAMP()
FROM up_data_intermediate_ad
JOIN up_banners ON up_data_intermediate_ad.ad_id = up_banners.bannerid
JOIN up_affiliates ON up_affiliates.affiliateid = up_banners.affiliateid
WHERE date_time BETWEEN ? AND ?
AND up_affiliates.agencyid = {$agencyId}
GROUP BY
    hour_date_time, up_data_intermediate_ad.ad_id, up_data_intermediate_ad.creative_id, up_data_intermediate_ad.zone_id
ON DUPLICATE KEY UPDATE requests = VALUES(requests),
    impressions = VALUES(impressions),
    conversions = VALUES(conversions),
    total_basket_value = VALUES(total_basket_value),
    total_num_items = VALUES(total_num_items),
    updated = VALUES(updated)
SQL;
        return $sql;
    }
    
    /**
     * 更新广告主结算相关数据,clicks,conversions,total_revenue等
     * @param int $revenue_type
     * @param int $agencyId
     * @return string
     */
    protected function updateSummaryClient($revenue_type, $agencyId)
    {
        switch ($revenue_type) {
            case $this->revenue_type['CPD']:
                $revenue_data = 'conversions';
                break;
            case $this->revenue_type['CPC']:
                $revenue_data = 'clicks';
                break;
            case $this->revenue_type['CPA']:
                $revenue_data = 'cpa';
                break;
            case $this->revenue_type['CPM']:
                $revenue_data = 'impressions';
                break;
            default:
                $revenue_data = 'conversions';
                break;
        }
        if ($revenue_type == $this->revenue_type['CPM']) {
            $count = 'COUNT(d.price)*100';
        } else {
            $count = 'COUNT(d.price)';
        }
        $sql = <<<SQL
INSERT INTO up_data_summary_ad_hourly(
`date_time`,
`ad_id`,
`zone_id`,
{$revenue_data},
`total_revenue`,
`total_revenue_gift`,
`updated`)
SELECT DATE_FORMAT(d.actiontime, "%Y-%m-%d %H:00:00") AS _hour,
    b.bannerid,
    d.zoneid,
    {$count} AS _conversions,
    CONVERT(IFNULL(SUM(d.price),0), decimal(10,2)) AS _price,
    CONVERT(IFNULL(SUM(d.price_gift),0), decimal(10,2)) AS _price_gift,
    UTC_TIMESTAMP()
FROM up_delivery_log AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND m.revenue_type = {$revenue_type}
    AND cli.agencyid = {$agencyId}
    AND d.actiontime >= ?
    AND d.actiontime <= ?
GROUP BY _hour, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE {$revenue_data} = VALUES({$revenue_data}),
    total_revenue = VALUES(total_revenue),
    total_revenue_gift = VALUES(total_revenue_gift),
    updated = VALUES(updated)
SQL;
        return $sql;
    }
    
    /**
     * 更新媒体商结算相关数据,af_income等
     * @return string
     */
    protected function updateSummaryAffiliate($agencyId)
    {
        $sql = <<<SQL
INSERT INTO up_data_summary_ad_hourly(
`date_time`,
`ad_id`,
`zone_id`,
`af_income`,
`updated`)
SELECT DATE_FORMAT(d.actiontime, "%Y-%m-%d %H:00:00") AS _hour,
    b.bannerid,
    d.zoneid,
    CONVERT(SUM(d.af_income), decimal(10,2)) AS _income,
    UTC_TIMESTAMP()
FROM up_expense_log AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND cli.agencyid = {$agencyId}
    AND d.actiontime >= ?
    AND d.actiontime <= ?
GROUP BY _hour, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE af_income = VALUES(af_income),
    updated = VALUES(updated)
SQL;
        return $sql;
    }
    
    /**
     * 更新广告主、媒体商计费方式存在转换关系的数据, 如D->C, A->C的clicks
     * 目前只存在A->C,D->C,A->D,增加A->M,D->M,C->M
     * @param int $revenue_type
     * @param int $agencyId
     * @return stirng
     */
    protected function updateSummaryTransform($revenue_type, $agencyId)
    {
        switch ($revenue_type) {
            case $this->revenue_type['CPD']:
                $revenue_data = 'conversions';
                break;
            case $this->revenue_type['CPC']:
                $revenue_data = 'clicks';
                break;
            case $this->revenue_type['CPM']:
                $revenue_data = 'impressions';
                break;
            default:
                $revenue_data = 'conversions';
                break;
        }
        if ($revenue_type == $this->revenue_type['CPM']) {
            $count = 'COUNT(d.price)*100';
        } else {
            $count = 'COUNT(d.price)';
        }
        $sql = <<<SQL
INSERT INTO up_data_summary_ad_hourly(
`date_time`,
`ad_id`,
`zone_id`,
{$revenue_data},
`updated`)
SELECT DATE_FORMAT(d.actiontime, "%Y-%m-%d %H:00:00") AS _hour,
    b.bannerid,
    d.zoneid,
    {$count} AS _conversions,
    UTC_TIMESTAMP()
FROM up_expense_log AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND b.revenue_type = {$revenue_type}
    AND m.revenue_type != {$revenue_type}
    AND cli.agencyid = {$agencyId}
    AND d.actiontime >= ?
    AND d.actiontime <= ?
GROUP BY _hour, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE {$revenue_data} = VALUES({$revenue_data}),
    updated = VALUES(updated)
SQL;
        return $sql;
    }
}
