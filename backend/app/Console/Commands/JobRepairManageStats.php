<?php
namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;

class JobRepairManageStats extends Command
{

    /**
     *  1、条件
        agencyid = 1,2,3,4，
        $startTime 为昨天(-8小时),昨天转成 UTC时间（2016-10-15 16:00:00 到 2016-10-16 15:59:59
        $startTime 〈= h.date_time 〈 $endTime 需要更新为0的字段
        total_revenue = 0，total_revenue_gift = 0，af_income = 0，
        c.revenue_type = cpc or b.revenue_type = cpc set clicks = 0
        c.revenue_type = cpd or b.revenue_type = cpd set conversion = 0
        c.revenue_type = cpa or b.revenue_type = cpa set cpa = 0
        c.revenue_type = cpm or b.revenue_type = cpm set impression = 0

        2、分别传入
        CPD (程序，人工分别更新  h.conversions）
        CPC（程序，人工分别更新 h.clicks）
        CPA（程序，人工分别更新h.cpa）
        CPM（程序 更新h.impressions）
        CPT（人工没有需要额外更新的字段）
        CPS（按销售计费，没有额外更新的字段）

        delivery_log（程序）,
        up_delivery_manual_log（人工）中汇总相应数据更新到匹配的字段，
     *  更新到 hourly表相应的额外字段，所以计费类型都更新
        total_revenue，total_revenue_gift，updated 这三个字段

        3、分别重新计算媒体商人工（up_expense_manual_log），
     * 程序化（up_expense_log）的 af_imcome 收入更新 hourly表

        4、更新存在转换关系的 (banners为 CPD, CPC，CPM）的数据，更新
        CPD h.conversions,  CPC h.clicks, CMP h.impressions
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_repair_manage_stats {--build-date=} {--days=} {--part=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新报表数据';
    
    protected $revenue_type = [
        'CPT' => Campaign::REVENUE_TYPE_CPT,
        'CPM' => Campaign::REVENUE_TYPE_CPM,
        'CPD' => Campaign::REVENUE_TYPE_CPD,
        'CPC' => Campaign::REVENUE_TYPE_CPC,
        'CPA' => Campaign::REVENUE_TYPE_CPA,
        'CPS' => Campaign::REVENUE_TYPE_CPS,
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
        $today = $this->option('build-date') ? $this->option('build-date') : date('Y-m-d', strtotime('-1 day'));
        $days = $this->option('days') ? $this->option('days') : 1; // 修复多少天的数据
        $part = $this->option('part') ? $this->option('part') : 'all'; // 运行哪一部分代码
        $diff = $days - 1;
        $startDate = date("Y-m-d", strtotime($today . " -{$diff} days"));
        $startTime = date('Y-m-d H:i:s', strtotime($startDate . ' -8 hour'));
        $endTime = date('Y-m-d H:i:s', strtotime($today . ' +1 days -8 hour') - 1);
        /* ---start---up_delivery_log录入up_data_summary_ad_hourly---start--- */
        if ($part === 'all' || $part === 'hourly') {
            $this->notice('entering partition hourly');
            $transaction = DB::transaction(function () use ($startTime, $endTime, $agency) {
                // 生成报表数据
                $param = array(
                    $startTime,
                    $endTime
                );
                $revenueTypeCpd = Campaign::REVENUE_TYPE_CPD;
                $revenueTypeCpc = Campaign::REVENUE_TYPE_CPC;
                $revenueTypeCpa = Campaign::REVENUE_TYPE_CPA;
                $revenueTypeCpm = Campaign::REVENUE_TYPE_CPM;
                
                $sql = "UPDATE up_data_summary_ad_hourly h
                        LEFT JOIN up_banners b ON b.bannerid = h.ad_id
                        LEFT JOIN up_campaigns c ON c.campaignid = b.campaignid
                        LEFT JOIN up_affiliates aff ON aff.affiliateid = b.affiliateid
                        SET
                        h.impressions = IF(c.revenue_type = {$revenueTypeCpm} OR
                         b.revenue_type = {$revenueTypeCpm}, 0, h.impressions),
                        h.clicks = IF(c.revenue_type = {$revenueTypeCpc} OR 
                        b.revenue_type = {$revenueTypeCpc}, 0, h.clicks),
                        h.conversions = IF(c.revenue_type = {$revenueTypeCpd} OR 
                        b.revenue_type = {$revenueTypeCpd}, 0, h.conversions),
                        h.cpa = IF(c.revenue_type = {$revenueTypeCpa} OR 
                        b.revenue_type = {$revenueTypeCpa}, 0, h.cpa),
                        h.total_revenue = 0,
                        h.total_revenue_gift = 0,
                        h.af_income = 0
                        WHERE aff.agencyid = {$agency->agencyid} 
                        AND '{$startTime}' <= h.date_time AND h.date_time < '{$endTime}'
                ";
                $query = DB::getPdo()->exec($sql);
                $this->notice('clean up_delivery_log->hourly: ' . ($query !== false ? 'success' : 'fail'));
                if ($query === false) {
                    $this->error('clean up_delivery_log->hourly error');
                    return false;
                }

                //广告主扣费数据修复
                //修复程序化CPD数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPD'], 0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client auto CPD: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client auto CPD error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                //修复人工CPD数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPD'], 1, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client manual CPD: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client manual CPD error, sql: $sql param:" . json_encode($param));
                    return false;
                }

                //修复程序化CPC数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPC'], 0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client auto CPC: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client auto CPC error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                //修复人工CPC数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPC'], 1, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client manual CPC: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client manual CPC error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                
                //修复程序化CPA数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPA'], 0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client auto CPA: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client auto CPA error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                //修复人工CPA数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPA'], 1, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client manual CPA: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client manual CPA error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                
                //修复程序化CPM数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPM'], 0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client auto CPM: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client auto CPM error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                
                //修复人工CPT数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPT'], 1, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client Manual CPT: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client auto CPT error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                
                //修复程序化CPS的数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPS'], 0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client Manual CPS: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client auto CPS error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                
                //修复人工CPS的数据
                $sql = $this->setHourlyDelivery($this->revenue_type['CPS'], 1, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly client Manual CPS: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly client auto CPS error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                
                //修复媒体商支出数据
                //程序化媒体
                $sql = $this->setHourlyExpense(0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly affiliate auto data: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly affiliate auto data error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                //人工媒体
                $sql = $this->setHourlyExpense(1, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly affiliate manual data: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly affiliate manual data error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                
                //修复结算方式存在转换的数据
                //程序化D
                $sql = $this->setHourlyTransform($this->revenue_type['CPD'], 0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly auto transform D: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly auto transform D error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                //人工D
                $sql = $this->setHourlyTransform($this->revenue_type['CPD'], 1, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly manual transform D: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly manual transform D error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                //程序化C
                $sql = $this->setHourlyTransform($this->revenue_type['CPC'], 0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly auto transform C: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly auto transform C error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                //人工C
                $sql = $this->setHourlyTransform($this->revenue_type['CPC'], 1, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly manual transform C: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly manual transform C error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                //程序化M
                $sql = $this->setHourlyTransform($this->revenue_type['CPM'], 0, $agency->agencyid);
                $query = DB::statement($sql, $param);
                echo 'stat hourly auto transform M: ' . ($query ? 'success' : 'fail') . "\n";
                if (! $query) {
                    $this->error("stat hourly auto transform M error, sql: $sql param:" . json_encode($param));
                    return false;
                }
                
                return true;
            });

            if (! $transaction) {
                echo 'stat up_delivery_log->hourly error,script going to exit...' . "\n";
                $this->error("stat up_delivery_log->hourly error,script going to exit...");
                exit();
            }

                echo 'leaving partition hourly' . "\n\n";
        }
        /* ----end----up_delivery_log录入up_data_summary_ad_hourly----end---- */

        /* ----end----模拟存储过程(proc_audit_hourly)----end---- */
        for ($i = 0; $i < $days; $i ++) {
            $date = date("Y-m-d", strtotime($today . " -{$i} days"));
            if ($part === 'all' || $part === 'job') {
                /*
                $this->call('job_ranking', array(
                    '--build-date' => $date,
                    '--agencyid' => $agency->agencyid
                ));
                */
                $this->call('job_operation_details', array(
                    '--build-date' => $date,
                    '--agencyid' => $agency->agencyid
                ));
            
                $this->call('job_recover_daily_data', array(
                    '--start-date' => $date,
                    '--end-date' => $date,
                    '--agencyid' => $agency->agencyid
                ));
            
                $this->call('job_balance_log', array(
                    '--build-date' => date("Y-m-d", strtotime("$date +1 day")),
                    '--agencyid' => $agency->agencyid
                ));
                $this->notice('leaving partition job');
            }
        }
    }

    /**
     * 修复hourly的广告主结算数据
     * @param int $revenue_type
     * @param int $manual 0:程序化, 1:人工
     * @param int $agencyId
     * @return string
     */
    protected function setHourlyDelivery($revenue_type, $manual, $agencyId)
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
        $update_set = "{$revenue_data} = VALUES({$revenue_data}),";
        
        if ($manual == 0) {
            $table = 'up_delivery_log';
            if ($revenue_type == $this->revenue_type['CPM']) {
                $count = 'COUNT(d.price)*100';
            } else {
                $count = 'COUNT(d.price)';
            }
        } else {
            $table = 'up_delivery_manual_log';
            $count = 'SUM(d.amount)';
        }
        
        if (in_array($revenue_type, [$this->revenue_type['CPT'], $this->revenue_type['CPS']])) {
            $sql = <<<SQL
INSERT INTO up_data_summary_ad_hourly(
`date_time`,
`ad_id`,
`zone_id`,
`total_revenue`,
`total_revenue_gift`,
`updated`)
SELECT 
    DATE_FORMAT(d.actiontime, "%Y-%m-%d %H:00:00") AS _hour,
    b.bannerid,
    d.zoneid,
    CONVERT(IFNULL(SUM(d.price),0), decimal(10,2)) AS _price,
    CONVERT(IFNULL(SUM(d.price_gift),0), decimal(10,2)) AS _price_gift,
    UTC_TIMESTAMP()
FROM {$table} AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND m.revenue_type = {$revenue_type}
    AND cli.agencyid = {$agencyId}
    AND d.actiontime BETWEEN ? AND ?
GROUP BY _hour, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE total_revenue = VALUES(total_revenue),
    total_revenue_gift = VALUES(total_revenue_gift),
    updated = VALUES(updated)
SQL;
        } else {
            $sql = <<<SQL
INSERT INTO up_data_summary_ad_hourly(
`date_time`,
`ad_id`,
`zone_id`,
{$revenue_data},
`total_revenue`,
`total_revenue_gift`,
`updated`)
SELECT
    DATE_FORMAT(d.actiontime, "%Y-%m-%d %H:00:00") AS _hour,
    b.bannerid,
    d.zoneid,
    {$count} AS _conversions,
    CONVERT(IFNULL(SUM(d.price),0), decimal(10,2)) AS _price,
    CONVERT(IFNULL(SUM(d.price_gift),0), decimal(10,2)) AS _price_gift,
    UTC_TIMESTAMP()
FROM {$table} AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND m.revenue_type = {$revenue_type}
    AND cli.agencyid = {$agencyId}
    AND d.actiontime BETWEEN ? AND ?
GROUP BY _hour, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE {$update_set}
    total_revenue = VALUES(total_revenue),
    total_revenue_gift = VALUES(total_revenue_gift),
    updated = VALUES(updated)
SQL;
        }
        
        return $sql;
    }
    
    /**
     * 修复hourly媒体商支出数据
     * @param int $manual
     * @param int $agencyId
     * @return string
     */
    protected function setHourlyExpense($manual, $agencyId)
    {
        if ($manual == 0) {
            $table = 'up_expense_log';
        } else {
            $table = 'up_expense_manual_log';
        }
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
FROM {$table} AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND d.actiontime >= ?
    AND d.actiontime <= ?
    AND cli.agencyid = {$agencyId}
GROUP BY _hour, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE af_income = VALUES(af_income),
    updated = VALUES(updated)
SQL;
        return $sql;
    }
    
    /**
     * 修复hourly存在结算方式转换的数据
     * @param int $revenue_type
     * @param int $manual
     * @param int $agencyId
     * @return string
     */
    protected function setHourlyTransform($revenue_type, $manual, $agencyId)
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
        if ($manual == 0) {
            $table = 'up_expense_log';
            if ($revenue_type == $this->revenue_type['CPM']) {
                $count = 'COUNT(d.price)*100';
            } else {
                $count = 'COUNT(d.price)';
            }
        } else {
            $table = 'up_expense_manual_log';
            $count = 'SUM(d.amount)';
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
FROM {$table} AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND b.revenue_type = {$revenue_type}
    AND m.revenue_type != {$revenue_type}
    AND d.actiontime >= ?
    AND d.actiontime <= ?
    AND cli.agencyid = {$agencyId}
GROUP BY _hour, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE {$revenue_data} = VALUES({$revenue_data}),
    updated = VALUES(updated)
SQL;
        return $sql;
    }
}
