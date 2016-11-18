<?php
namespace App\Console\Commands;

use App\Components\Config;
use App\Models\Agency;
use App\Models\OperationClient;
use App\Models\OperationDetail;
use App\Models\Campaign;
use \DB;

class JobRecoverDailyData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_recover_daily_data {--start-date=} {--end-date=} {--role=} {--days=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover daily_data...';

    protected $revenue_type = [
        'CPD' => Campaign::REVENUE_TYPE_CPD,
        'CPC' => Campaign::REVENUE_TYPE_CPC,
        'CPA' => Campaign::REVENUE_TYPE_CPA,
        'CPT' => Campaign::REVENUE_TYPE_CPT,
        'CPM' => Campaign::REVENUE_TYPE_CPM,
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
        // 处理时间输入
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $days = $this->option('days') ? intval($this->option('days')) - 1 : 10;

        $role = $this->option('role') ? $this->option('role') : 'p,af,client';
        $roles = explode(',', $role);

        if ($startDate) {
            if (!$endDate) {
                $endDate = date("Y-m-d", strtotime("{$days} day", strtotime($startDate)));
            }
        } else {
            if (!$endDate) {
                $endDate = gmdate("Y-m-d", strtotime('8 hour'));
            }
            $startDate = date("Y-m-d", strtotime("-{$days} day", strtotime($endDate)));
        }
        for ($i=$startDate;
            date("Y-m", strtotime($i)) <= date("Y-m", strtotime($endDate));
            $i = date('Y-m-01', strtotime("$i +1 month"))) {
            if (date("Y-m", strtotime($i)) == date("Y-m", strtotime($endDate))) {
                $_endDate = $endDate;
            } else {
                $_endDate = date('Y-m-d', strtotime(date('Y-m-01', strtotime($i)) . ' +1 month -1 day'));
            }
            if (in_array('p', $roles)) {
                $this->notice(date('H:i:s') . " Begin recover platform");
                $count = $this->recoverData($i, $_endDate, $agency->agencyid);
            }
            if (in_array('af', $roles)) {
                $this->notice(date('H:i:s') . " Begin recover af");
                $count = $this->recoverDataAf($i, $_endDate, $agency->agencyid);
            }
            if (in_array('client', $roles)) {
                $this->notice(date('H:i:s') . " Begin recover client");
                $this->recoverDataClient($i, $_endDate, $agency->agencyid);
            }
        }
    }

    /**
     * 生成平台数据，直接从hourly到daily，已有的数据更新
     */
    private function recoverData($startDate, $endDate, $agencyId)
    {
        $summaryTable = 'up_data_summary_ad_hourly';
        $tableName = 'up_data_hourly_daily_'. date("Ym", strtotime($startDate));

        return $this->insertUpdate($startDate, $endDate, $summaryTable, $tableName, $agencyId);
    }

    /**
     * 生成媒体商数据，直接从ad_hourly到daily_af，已有的数据更新
     */
    private function recoverDataAf($startDate, $endDate, $agencyId)
    {
        $summaryTable = 'up_data_summary_ad_hourly';
        $tableName = 'up_data_hourly_daily_af_'. date("Ym", strtotime($startDate));
        $addSelects = ['affiliateid' => 'b.affiliateid'];

        //不能刷新今天及之后的数据
        $today = date('Y-m-d');
        if (strtotime($today) <= strtotime($endDate)) {
            $endDate = date('Y-m-d', strtotime('-1 day'));
        }
        while (strtotime($startDate) <= strtotime($endDate)) {
            //查询当天的数据是否已通过审核
            $row =  OperationDetail::where('day_time', $startDate)
                ->whereIn(
                    'status',
                    [
                        OperationDetail::STATUS_ACCEPT_PENDING_REPORT,
                        OperationDetail::STATUS_ACCEPT_REPORT_DONE,
                        OperationDetail::STATUS_ACCEPT_DONE
                    ]
                )
                ->where('agencyid', $agencyId)
                ->first();
            if (!empty($row)) {
                $this->insertUpdate($startDate, $startDate, $summaryTable, $tableName, $agencyId, $addSelects);
                $this->repairHourAfData($startDate, $startDate, $tableName, $agencyId);
            }
            $startDate = date('Y-m-d', strtotime('1 day', strtotime($startDate)));
        }
    }

    /**
     * 生成广告主数据
     * 1.按天获取审核通过的campaign
     * （修复按时间区间获取campaign的BUG，区间内某一天campaign被驳回不可知）
     * 2.从hourly表获取数据插入，已有的数据不更新
     */
    private function recoverDataClient($startDate, $endDate, $agencyId)
    {
        //广告主绝对不能刷新今天的数据
        $today = date('Y-m-d');
        if (strtotime($today) <= strtotime($endDate)) {
            $endDate = date('Y-m-d', strtotime('-1 day'));
        }
        while (strtotime($startDate) <= strtotime($endDate)) {
            $this->processClientData($startDate, $agencyId);
            $startDate = date('Y-m-d', strtotime('1 day', strtotime($startDate)));
        }
    }


    private function processClientData($date, $agencyId)
    {
        $summaryTable = 'up_data_summary_ad_hourly';
        $tableName = 'up_data_hourly_daily_client_'.date("Ym", strtotime($date));
        $addSelects = ['clientid' => 'c.clientid'];

        $this->notice("Check operation_clients {$date}");
        $from = date('Y-m-d H:i:s', strtotime('-8 hours', strtotime($date)));
        $to = date('Y-m-d H:i:s', strtotime('1 day', strtotime($from)));
        $sql = "SELECT DISTINCT b.campaignid, oc.issue FROM up_data_summary_ad_hourly ds
JOIN up_banners b ON ds.ad_id = b.bannerid
LEFT JOIN up_operation_clients oc ON b.campaignid = oc.campaign_id AND oc.date='{$date}'
WHERE date_time>='{$from}' AND date_time<'{$to}' AND oc.issue = 0";
        $campaigns = \DB::select($sql);
        $cids = [];
        foreach ($campaigns as $c) {
            $cids[] = $c->campaignid;
        }
        if (count($cids) > 0) {
            $this->insertUpdate($date, $date, $summaryTable, $tableName, $agencyId, $addSelects, false, $cids);
        }

        $zoneIds = Config::get('biddingos.add_impression_zone_id');
        $sql = "UPDATE
            {$tableName}
            SET impressions = impressions + conversions * FLOOR(1000 +(RAND() * 1000))
            WHERE
            zone_id in ({$zoneIds})
            AND conversions > 0
            AND impressions = 0
        ";
        \DB::update($sql);
    }

    private function insertUpdate(
        $startDate,
        $endDate,
        $summaryTable,
        $tableName,
        $agencyId,
        $addSelects = [],
        $update = true,
        $cids = []
    ) {
        // 数据库为UTC时间，需减去8小时
        $startDateTime = date("Y-m-d H:i:s", strtotime('-8 hour', strtotime($startDate)));
        $endDateTime = date("Y-m-d H:i:s", strtotime('+16 hour', strtotime($endDate)));

        $selects = [
            'date' => "DATE_FORMAT(DATE_ADD(ds.date_time, INTERVAL 8 HOUR),'%Y-%m-%d') AS date",
            'campaign_id' => "b.campaignid AS campaign_id",
            'ad_id' => "ds.ad_id",
            'zone_id' => "ds.zone_id",
            'requests' => "SUM(ds.requests) AS requests",
            'impressions' => "SUM(ds.impressions) AS impressions",
            'total_revenue' => "SUM(ds.total_revenue) AS total_revenue",
            'total_revenue_gift' => "SUM(ds.total_revenue_gift) AS total_revenue_gift",
            'af_income' => "SUM(ds.af_income) AS af_income",
            'clicks' => "SUM(ds.clicks) AS clicks",
            'conversions' => "SUM(ds.conversions) AS conversions",
            'cpa' => "SUM(ds.cpa) AS cpa",
            'consum' => "SUM(ds.consum) AS consum",
            'file_click' => "SUM(ds.file_click) AS file_click",
            'file_down' => "SUM(ds.file_down) AS file_down",
            'win_count' => "SUM(ds.win_count) AS win_count",
        ];
        if (count($addSelects) > 0) {
            $selects = array_merge($selects, $addSelects);
        }
        $updates = [
            'requests',
            'impressions',
            'total_revenue',
            'total_revenue_gift',
            'af_income',
            'clicks',
            'conversions',
            'cpa',
            'consum',
            'file_click',
            'file_down',
            'win_count'
        ];
        $insertColumn = implode(',', array_keys($selects));
        $selectColumn = implode(',', array_values($selects));

        if ($update) {
            foreach ($updates as &$v) {
                $v = $v . "=VALUES({$v})";
            }
        } else {
            $updates = ['date=date'];
        }
        $where = '';
        if (count($cids) > 0) {
            $where = 'AND b.campaignid IN (' . implode(',', $cids) . ')';
        }
        $updateStr = implode(',', $updates);
        $sql = "INSERT INTO {$tableName} ({$insertColumn})
SELECT {$selectColumn} FROM {$summaryTable} AS ds
INNER JOIN up_banners AS b ON b.bannerid = ds.ad_id
LEFT JOIN up_campaigns AS c ON c.campaignid= b.campaignid
LEFT JOIN up_clients AS cli ON cli.clientid = c.clientid
WHERE ds.date_time >= '{$startDateTime}' AND ds.date_time < '{$endDateTime}' {$where}
AND  cli.agencyid = '{$agencyId}'
GROUP BY date, campaign_id, ad_id, zone_id
ON DUPLICATE KEY UPDATE {$updateStr}";
        return \DB::insert($sql);
    }


    private function repairHourAfData($startTime, $endTime, $tableName, $agencyId)
    {
        $transaction = DB::transaction(function () use ($startTime, $endTime, $tableName, $agencyId) {
            $dStartTime = gmdate('Y-m-d H:i:s', strtotime($startTime));
            $dEndTime =   gmdate('Y-m-d H:i:s', strtotime($startTime . ' +1 days') - 1);
            $param = array(
                $startTime,
                $dStartTime,
                $dEndTime
            );
            //数据清零
            $query = \DB::table(DB::raw("$tableName as up_h"))
                ->join('affiliates as aff', 'aff.affiliateid', '=', 'h.affiliateid')
                ->where('aff.agencyid', $agencyId)
                ->where('date', '=', $startTime)
                ->update([
                    'clicks' => 0,
                    'conversions' => 0,
                    'total_revenue' => 0,
                    'total_revenue_gift' => 0,
                    'af_income' => 0,
                    'cpa' => 0,
                ]);
            echo 'clean up_delivery_log->hourly_daily_af: ' . ($query !== false ? 'success' : 'fail') . "\n";
            if ($query === false) {
                $this->error('clean up_delivery_log->hourly error, sql: ' . $query->toSql());
                return false;
            }

            //审计后广告主扣费数据修复
            //修复程序化CPD数据
            $sql = $this->setAfHourlyClient(0, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af client : ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af client  error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //修复人工CPD数据
            $sql = $this->setAfHourlyClient(1, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af client manual: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af client manual error, sql: $sql param:" . json_encode($param));
                return false;
            }

            //修复程序化CPC数据
            $sql = $this->setAfHourlyAffiliate($this->revenue_type['CPC'], 0, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af client auto CPC: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af client auto CPC error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //修复人工CPC数据
            $sql = $this->setAfHourlyAffiliate($this->revenue_type['CPC'], 1, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af client manual CPC: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af client manual CPC error, sql: $sql param:" . json_encode($param));
                return false;
            }

            //修复程序化CPA数据
            $sql = $this->setAfHourlyAffiliate($this->revenue_type['CPA'], 0, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af client auto CPA: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af client auto CPA error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //修复人工CPA数据
            $sql = $this->setAfHourlyAffiliate($this->revenue_type['CPA'], 1, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af client manual CPA: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af client manual CPA error, sql: $sql param:" . json_encode($param));
                return false;
            }

            //修复程序化CPM数据
            $sql = $this->setAfHourlyAffiliate($this->revenue_type['CPM'], 0, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af client auto CPM: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af client auto CPM error, sql: $sql param:" . json_encode($param));
                return false;
            }

            //修复人工CPT数据
            $sql = $this->setAfHourlyAffiliate($this->revenue_type['CPT'], 1, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af client manual CPT: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af client manual CPT error, sql: $sql param:" . json_encode($param));
                return false;
            }

            //修复审计后媒体商支出数据及下载
            //程序化媒体
            $sql = $this->setAfHourlyAffiliate($this->revenue_type['CPD'], 0, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af affiliate auto  auto CPD data: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af affiliate auto data error, sql: $sql param:" . json_encode($param));
                return false;
            }
            //人工媒体
            $sql = $this->setAfHourlyAffiliate($this->revenue_type['CPD'], 1, $tableName, $agencyId);
            $query = DB::statement($sql, $param);
            echo 'stat hourly_daily_af affiliate manual  auto CPD data: ' . ($query ? 'success' : 'fail') . "\n";
            if (! $query) {
                $this->error("stat hourly_daily_af affiliate manual data err, sql: $sql param:" . json_encode($param));
                return false;
            }

            //全部执行完，没有错误
            return true;
        });

        if (! $transaction) {
            echo 'stat hourly->hourly_daily_af error,script going to exit...' . "\n";
            $this->error('stat hourly->hourly_daily_af error,script going to exit...');
            exit();
        }
        echo 'leaving partition hourly' . "\n\n";
    }


    /**
     * 将审计后的广告主结算数据统计到hourly_daily表
     * @param int $revenue_type
     * @param int $manual
     */
    protected function setAfHourlyClient($manual, $tableName, $agencyId)
    {
        if ($manual == 0) {
            $table = 'up_delivery_log';
            $audit_condition = 'AND d.`status` = 0';
        } else {
            $table = 'up_delivery_manual_log';
            $audit_condition = '';
        }
        $sql = <<<SQL
INSERT INTO {$tableName}(
`date`,
`ad_id`,
`campaign_id`,
`zone_id`,
`total_revenue`,
`total_revenue_gift`,
`affiliateid`)
SELECT ? AS
    _date,
    b.bannerid,
    b.campaignid,
    d.zoneid,
    CONVERT(IFNULL(SUM(d.price),0), decimal(10,2)) AS _price,
    CONVERT(IFNULL(SUM(d.price_gift),0), decimal(10,2)) AS _price_gift,
    b.affiliateid
FROM {$table} AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND cli.agencyid = {$agencyId}
    {$audit_condition}
    AND d.actiontime BETWEEN ? AND ?
GROUP BY _date, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE total_revenue = VALUES(total_revenue),
    total_revenue_gift = VALUES(total_revenue_gift),
    affiliateid = VALUES(affiliateid)
SQL;
        return $sql;
    }

    /**
     * 将审计后的媒体商结算数据统计到hourly_daily表
     * @param int $revenue_type
     * @param int $manual
     * @param int $agencyId
     */
    protected function setAfHourlyAffiliate($revenue_type, $manual, $tableName, $agencyId)
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
        if ($manual == 0) {
            $table = 'up_expense_log';
            if ($revenue_type == $this->revenue_type['CPM']) {
                $count = 'COUNT(d.price)*100';
                $audit_condition = 'AND d.`status` = 0';
            } else {
                $count = 'COUNT(d.price)';
                $audit_condition = 'AND d.`status` = 0';
            }
        } else {
            $table = 'up_expense_manual_log';
            $count = 'SUM(d.amount)';
            $audit_condition = '';
        }
        $data = Campaign::getRevenueTypeToLogType();
        if (isset($data[$revenue_type])) {
            $sourceLogType = $data[$revenue_type];
            $audit_condition .= " AND d.`source_log_type` =  '{$sourceLogType}'";
        }
        if ($revenue_type == $this->revenue_type['CPT']) {
            $sql = <<<SQL
INSERT INTO {$tableName}(
`date`,
`ad_id`,
`campaign_id`,
`zone_id`,
`af_income`,
`affiliateid`)
SELECT ? AS
    _date,
    b.bannerid,
    b.campaignid,
    d.zoneid,
    CONVERT(SUM(d.af_income), decimal(10,2)) AS _income,
    b.affiliateid
FROM {$table} AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND cli.agencyid = {$agencyId}
    {$audit_condition}
    AND d.actiontime BETWEEN ? AND ?
GROUP BY _date, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE af_income = VALUES(af_income),
affiliateid = VALUES(affiliateid)
SQL;

        } else {
            $sql = <<<SQL
INSERT INTO {$tableName}(
`date`,
`ad_id`,
`campaign_id`,
`zone_id`,
`af_income`,
{$revenue_data},
`affiliateid`)
SELECT ? AS
    _date,
    b.bannerid,
    b.campaignid,
    d.zoneid,
    CONVERT(SUM(d.af_income), decimal(10,2)) AS _income,
    {$count} AS _conversions,
    b.affiliateid
FROM {$table} AS d
LEFT JOIN up_campaigns AS m ON m.campaignid = d.campaignid
LEFT JOIN up_zones AS z ON z.zoneid = d.zoneid
LEFT JOIN up_banners AS b ON m.campaignid = b.campaignid AND b.affiliateid = z.affiliateid
LEFT JOIN up_clients AS cli ON cli.clientid = m.clientid
WHERE d.campaignid > 0
    AND d.zoneid > 0
    AND cli.agencyid = {$agencyId}
    {$audit_condition}
    AND d.actiontime BETWEEN ? AND ?
GROUP BY _date, b.bannerid, d.zoneid
ON DUPLICATE KEY UPDATE {$revenue_data} = VALUES({$revenue_data}),
    af_income = VALUES(af_income),
    affiliateid = VALUES(affiliateid)
SQL;
        }
        return $sql;
    }
}
