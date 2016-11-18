<?php
namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\ManualDeliveryData;
use Illuminate\Support\Facades\DB;
use App\Services\MessageService;
use App\Components\Helper\EmailHelper;
use App\Services\BalanceService;
use App\Models\Campaign;
use \PDO;
use App\Models\OperationClient;

class JobMixingManageStats extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_mixing_manage_stats {--build-date=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'mixing manual_deliverydata.';
    
    protected $summaryTable = 'data_summary_ad_hourly';
    
    protected $manualTable = 'manual_deliverydata';
    
    protected $manualDeliveryTable = 'delivery_manual_log';
    
    protected $manualExpenseTable = 'expense_manual_log';
    
    
    /**
     * 要写入 hourly 表及
     * delivery_manual_log， expense_manual_log 两个表
     */
    private $hourlyDeliveryExpense = [
        'D2D',
        'C2C',
        'A2A',
        'T2T',
        'S2S',
    ];
    
    /**
     * 要写入 hourly 表及 expense_manual_log 两个表
     */
    private $hourlyExpense = [
      'A2D-AF',
      'A2C-AF'
    ];
    
    private $delivery = [
       'A-AD'
    ];
    /*
     * 待删除的表
    protected $deliveryTable = 'delivery_log';
    
    protected $downTable = 'down_log';
    
    protected $clickTable = 'click_log';
    */

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
        $updateData = DB::transaction(function () use ($agency) {
            $updateData = [];
            $priceIsZore = [
                'A2D-AF',
                'A2C-AF'
            ];
            $pdo = DB::getPdo();
            $prefix = DB::getTablePrefix();
            // 设置DB查询返回格式为数组
            DB::setFetchMode(PDO::FETCH_ASSOC);
            // 获取人工添加媒体数据的历史记录
            $rows = DB::table("{$this->manualTable} as m")
                ->join('campaigns as c', 'm.campaign_id', '=', 'c.campaignid')
                ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
                ->select('m.*')
                ->where('m.campaign_id', '>', 0)
                ->where('cli.agencyid', $agency->agencyid)
                ->where('flag', ManualDeliveryData::FLAG_UNTREATED)
                ->where(function ($query) {
                    $query->where('m.revenues', '>', 0)
                        ->orWhere('m.expense', '>', 0)
                        ->orWhere('m.conversions', '>', 0);
                })
                ->take(30)
                ->get();

            //设置DB查询返回格式为对象
            DB::setFetchMode(PDO::FETCH_OBJ);
            $this->notice("{$this->manualTable} rows return: " . count($rows));
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    //检查导入的人工投放数据，是否已经有审计数据，没有就生成
                    $this->processOperationClients($row);
                    
                    $historyPrice = 0;
                    $date = $row['date'];
                    // 补量数据都补到当天的03时
                    $dateTime = gmdate("Y-m-d H:i:s", strtotime($date ." 03:00:00"));
                    //如果revenues的值为0，
                    //并且不是A2D或者A2C媒体商数据，
                    //则广告主支出必须大于0
                    //由于Eqq有可能采集不到 revenues的值，所以会为 0
                    if ($row['expense'] > 0 && ($row['revenues'] == 0 && !in_array($row['data_type'], $priceIsZore))) {
                        $historyPrice = $this->getCampaignHistoryPrice($row['campaign_id'], $date);
                        if (empty($historyPrice)) {
                            continue;
                        }
                        
                        $row['revenues'] = $historyPrice * $row['conversions'];
                        //更新 deliveryData的 revenues的值为最新算出的值
                        DB::table($this->manualTable)
                            ->where('id', $row['id'])
                            ->update(['revenues' => $row['revenues']]);
                    }
                    
                    // 计算每个小时需要写入的数据，up_data_summary_ad_hourly，up_delivery_log
                    $result = false;
                    /*
                     * 如果类型为D2D，C2C，A2A，T2T的，都要写入 hourly 表及
                     * delivery_manual_log， expense_manual_log 两个表
                     */
                    if (in_array($row['data_type'], $this->hourlyDeliveryExpense)) {
                        switch ($row['data_type']) {
                            case 'D2D':
                                if (intval($row['conversions']) == 0) {
                                    $this->error('campaign_id ' . $row['campaign_id'] . ' conversion is 0');
                                }
                                $source_log_type = 'down';
                                $amount = $row['conversions'];
                                break;
                            case 'C2C':
                                if (intval($row['clicks']) == 0) {
                                    $this->error('campaign_id ' . $row['campaign_id'] . ' clicks is 0');
                                }
                                $source_log_type = 'click';
                                $amount = $row['clicks'];
                                break;
                            case 'A2A':
                                if (intval($row['cpa']) == 0) {
                                    $this->error('campaign_id ' . $row['campaign_id'] . ' cpa is 0');
                                }
                                $source_log_type = 'action';
                                $amount = $row['cpa'];
                                break;
                            case 'T2T':
                                $source_log_type = 'time';
                                $amount = 1;
                                break;
                            case 'S2S':
                                $source_log_type = 'sale';
                                $amount = 1;
                                break;
                            default:
                                $source_log_type = 'down';
                                $amount = 1;
                                break;
                        }
                        
                        //写数据到 hourly
                        $hourlyParam = [
                            'date_time' => $dateTime,
                            'zone_id' => $row['zone_id'],
                            'banner_id' => $row['banner_id'],
                            'impressions' => $row['views']
                        ];
                        $result = $this->createHourlyData($hourlyParam);
                        
                        //计算需要扣多少推广金，多少赠送金
                        $account_id = Campaign::find($row['campaign_id'])->client->account_id;
                        $price = $row['revenues']; //广告主消耗
                        $price_gift = 0;
                        if ($account_id > 0) {
                            $balanceRow = BalanceService::getGiftPrice($account_id, $row['revenues']);
                            $price_gift = $balanceRow['price_gift'];
                        }
                        //写数据到 delivery_manual_log
                        $param = [
                            'campaignid' => $row['campaign_id'],
                            'zoneid' => $row['zone_id'],
                            'bannerid' => $row['banner_id'],
                            'price' => $price,
                            'price_gift' => $price_gift,
                            'actiontime' => $dateTime,
                            'af_income' => $row['expense'],
                            'source_log_type' => $source_log_type,
                            'channel' => $row['id'],
                            'amount' => $amount,
                        ];
                        $result = $this->createDeliveryManualLog($param);
                        
                        //写数据到 expense_manual_log
                        $param = [
                            'campaignid' => $row['campaign_id'],
                            'zoneid' => $row['zone_id'],
                            'bannerid' => $row['banner_id'],
                            'price' => $price,
                            'price_gift' => $price_gift,
                            'actiontime' => $dateTime,
                            'af_income' => $row['expense'],
                            'source_log_type' => $source_log_type,
                            'channel' => $row['id'],
                            'amount' => $amount,
                        ];
                        $result = $this->createExpenseManualLog($param);
                        
                        //扣除广告主的金额
                        $proResult = $pdo->query('call account_cost_price("' .
                            $account_id . '", "' . $row['revenues'] . '");');
                        
                    } elseif (in_array($row['data_type'], $this->hourlyExpense)) {
                        //导入A2D-AF或A2C-AF
                        $hourlyParam = [
                            'date_time' => $dateTime,
                            'zone_id' => $row['zone_id'],
                            'banner_id' => $row['banner_id'],
                            'impressions' => $row['views']
                        ];
                        $result = $this->createHourlyData($hourlyParam);
                        
                        //写数据到 expense_manual_log
                        $param = [
                            'campaignid' => $row['campaign_id'],
                            'zoneid' => $row['zone_id'],
                            'bannerid' => $row['banner_id'],
                            'price' => 0, //录媒体商的数据时，无法取得广告主的消耗
                            'price_gift' => 0, //录媒体商的数据时，无法取得广告主的消耗
                            'actiontime' => $dateTime,
                            'af_income' => $row['expense'],
                            'source_log_type' => ($row['data_type'] == 'A2D-AF') ? 'down' : 'click',
                            'channel' => $row['id'],
                            'amount' => ($row['data_type'] == 'A2D-AF') ? $row['conversions'] : $row['clicks'],
                        ];
                        $result = $this->createExpenseManualLog($param);
                    } else {
                        //导入A-AD的数据
                        $row['delivery_count'] = $row['cpa'];
                        $account_id = Campaign::find($row['campaign_id'])->client->account_id;
                        //修改状态
                        $result = $this->setCPAManualDeliveryData($dateTime, $row);
                        if ($result) {
                            $proResult = $pdo->query('call account_cost_price("' .
                                $account_id . '", "' . $row['revenues'] . '");');
                        }
                    }
                    
                    //输出和记录log
                    $this->notice('job_mixing_manage_stats function set'
                            . $row['data_type'] . ' result=' . $result);
                    
                    //返回成功才设置flag=1
                    if ($result) {
                        // 更新的日志的状态
                        DB::table($this->manualTable)->where('id', $row['id'])->update([
                            'flag' => ManualDeliveryData::FLAG_ASSIGNED,
                            'update_time' => date("Y-m-d H:i:s", time())
                        ]);
                        // 执行别的job需要传入时间
                        $updateData[$row['date']] = $row['date'];
                    }
                }// end foreach
            }
            return $updateData;
        });
        
        //更新这次执行的日期的hourly数据到别的表,要执行common计划
        if (count($updateData) > 0) {
            foreach ($updateData as $k => $v) {
                if ($k == 'insert_list') {
                    continue;
                } else {
                    $this->notice('JobMixingManageStats call commands day: ' . $v .'agencyid', $agency->agencyid);
                    $this->call('job_repair_manage_stats', ['--build-date' => $v, '--agencyid' => $agency->agencyid]);
                }
            }
        }
    }


    
    /**
     * 获取CPA的投放数据
     */
    private function setCPAManualDeliveryData($dateTime, $row)
    {
        $sourceLogType = 'action';
        $startTime = date('Y-m-d H:i:s', strtotime($row['date'] . ' -8 hour'));
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +1 days') - 1);
        $result = true;

        //计算需要扣多少推广金，多少赠送金
        $account_id = Campaign::find($row['campaign_id'])->client->account_id;
        $price = $row['revenues'];
        $price_gift = 0;
        if ($account_id > 0) {
            $balanceRow = BalanceService::getGiftPrice($account_id, $row['revenues']);
            $price_gift = $balanceRow['price_gift'];
        }

        // 查询广告的媒体比例
        $sql = "
        SELECT
            b.affiliateid, SUM(h.af_income) s_af_income
        FROM
            up_data_summary_ad_hourly h
        JOIN up_banners b ON (h.ad_id = b.bannerid)
        WHERE
            h.date_time >= '$startTime'
          AND h.date_time <= '$endTime'
            AND b.campaignid = {$row['campaign_id']}
            AND h.af_income > 0
        GROUP BY b.affiliateid";
        $aff_rows = DB::select($sql);
        $aff_rows = json_decode(json_encode($aff_rows), true);
        if (count($aff_rows) < 1) {
            return false;
        }

        //计算cpa要分配的数量
        $total_af_income = 0;
        foreach ($aff_rows as $aff_row) {
            $total_af_income += $aff_row['s_af_income'];
        }

        $cpaData = [];
        $first_key = '';
        $cpaRateData = [];
        $cpaAfIncome = [];
        foreach ($aff_rows as $aff_row) {
            //每个媒体要分配的cpa
            if (empty($first_key)) {
                //先给第一条赋予总数，然后在递减
                $first_key = $aff_row['affiliateid'];
                $cpaData[$first_key] = $row['delivery_count'];
                $cpaRateData[$first_key] = 1;
            } else {
                //第二条以后在用第一条的总数递减
                $cpaData[$aff_row['affiliateid']] = ceil($row['delivery_count']
                    * ($aff_row['s_af_income'] / $total_af_income));

                $cpaRateData[$aff_row['affiliateid']] = sprintf(
                    "%.2f",
                    floor(($aff_row['s_af_income'] / $total_af_income) * 100) / 100
                );

                $cpaAfIncome[$aff_row['affiliateid']] = sprintf("%.2f", $aff_row['s_af_income']);
                //判断如果总数<=当前要分配的数，那后面的都赋予0
                if ($cpaData[$first_key] <= $cpaData[$aff_row['affiliateid']] || $cpaData[$first_key] == 0) {
                    $cpaData[$aff_row['affiliateid']] = 0;
                    $cpaRateData[$aff_row['affiliateid']] = 0;
                }
                $cpaRateData[$first_key] -= $cpaRateData[$aff_row['affiliateid']];
                $cpaData[$first_key] -= $cpaData[$aff_row['affiliateid']];
            }
        }

        if (!empty($cpaData)) {
            /*
            //把值为0的清空掉
            foreach ($cpaData as $key => $val) {
                if (0 >= $val) {
                    unset($cpaData[$key]);
                }
            }*/

            if (!empty($cpaData)) {
                //循环有效的媒体商
                foreach ($cpaData as $k => $v) {
                    $query = DB::selectOne("SELECT
                                z.zoneid,
                                b.bannerid
                            FROM
                                up_zones z
                            JOIN up_banners b ON (
                                z.affiliateid = b.affiliateid
                            )
                            WHERE
                                z.affiliateid = '$k'
                            AND b.campaignid = '{$row['campaign_id']}'
                            AND z.type = 3
                            AND z.platform = 8
                            LIMIT 1");
                    if ($v < 1) {
                        continue;
                    }

                    //按比例生成数据到 delivery_manual_log
                    $param = [
                        'campaignid' => $row['campaign_id'],
                        'zoneid' => $query->zoneid,
                        'bannerid' => $query->bannerid,
                        'price' => sprintf("%.2f", $price * $cpaRateData[$k]),
                        'price_gift' => sprintf("%.2f", $price_gift * $cpaRateData[$k]),
                        'actiontime' => $dateTime,
                        'af_income' => 0,
                        'source_log_type' => $sourceLogType,
                        'channel' => $row['id'],
                        'amount' => $v,
                    ];
                    $result = $this->createDeliveryManualLog($param);
                }
            }
        }//end if
        
        return $result;
    }
    
    
    /**
     * 更新或者
     * @param type $param
     * @return int
     */
    private function createHourlyData($param)
    {
        $dataSummary = [];
        
        //插入报表展示量数据
        $isExist = DB::table($this->summaryTable)
                ->select('data_summary_ad_hourly_id')
                ->where('date_time', $param['date_time'])
                ->where('zone_id', $param['zone_id'])
                ->where('ad_id', $param['banner_id'])
                ->first();

        if (count($isExist) > 0 && $isExist->data_summary_ad_hourly_id > 0) {
            $this->info("Update data_summary_ad_hourly_id:
            {$isExist->data_summary_ad_hourly_id} impressions +{$param['impressions']}");
            DB::table($this->summaryTable)
                ->where('data_summary_ad_hourly_id', $isExist->data_summary_ad_hourly_id)
                ->update([
                    'requests' => $param['impressions'],
                    'impressions' => $param['impressions'],
                    'impressions_mixing' => $param['impressions'],
                    'updated_time' => date('Y-m-d H:i:s')
                ]);
        } else {
            $dataSummary[] = [
                'date_time' => $param['date_time'],
                'ad_id' => $param['banner_id'],
                'zone_id' => $param['zone_id'],
                'requests' => $param['impressions'],
                'impressions' => $param['impressions'],
                'impressions_mixing' => $param['impressions'],
                'updated' => date("Y-m-d H:i:s", time()),
                'total_basket_value' => 0,
                'total_num_items' => 0,
                'total_revenue' => 0,
                'total_cost' => 0,
                'total_techcost' => 0
            ];
            $this->insert($this->summaryTable, $dataSummary);
        }
        return true;
    }
    
    
    
    /**
     * 生成 delivery_manual_log 的数据
     */
    protected function createDeliveryManualLog($param)
    {
        //检查数据是否存在，如果存在则更新，不存在则新增
        $isExist =  DB::table($this->manualDeliveryTable)
                    ->select('deliveryid')
                    ->where('campaignid', $param['campaignid'])
                    ->where('zoneid', $param['zoneid'])
                    ->where('actiontime', $param['actiontime'])
                    ->first();
        if (count($isExist) > 0 && $isExist->deliveryid > 0) {
            $update_time = date('Y-m-d H:i:s');
            $tablePrefix = DB::getTablePrefix();
            $sql = "
                    UPDATE {$tablePrefix}{$this->manualDeliveryTable} SET 
                    price = price + {$param['price']},
                    price_gift = price_gift + {$param['price_gift']},
                    af_income = af_income + {$param['af_income']},
                    amount = amount + {$param['amount']},
                    updated_time = '{$update_time}'
                    WHERE 1 AND deliveryid = {$isExist->deliveryid}
            ";
            $rows = DB::getPdo()->exec($sql);
            $result = (0 < $rows) ? true : false;
        } else {
            $result = DB::table($this->manualDeliveryTable)->insert($param);
        }
        return $result;
    }
    
    /**
     * 生成 expense_manual_log 的数据
     */
    protected function createExpenseManualLog($param)
    {
        //检查数据是否存在，如果存在则更新，不存在则新增
        $isExist =  DB::table($this->manualExpenseTable)
                ->select('expenseid')
                ->where('campaignid', $param['campaignid'])
                ->where('zoneid', $param['zoneid'])
                ->where('actiontime', $param['actiontime'])
                ->first();
        if (count($isExist) > 0 && $isExist->expenseid > 0) {
            $update_time = date('Y-m-d H:i:s');
            $tablePrefix = DB::getTablePrefix();
            $sql = "
                    UPDATE {$tablePrefix}{$this->manualExpenseTable} SET 
                    price = price + {$param['price']},
                    price_gift = price_gift + {$param['price_gift']},
                    af_income = af_income + {$param['af_income']},
                    amount = amount + {$param['amount']},
                    updated_time = '{$update_time}'
                    WHERE 1 AND expenseid = {$isExist->expenseid}
                ";
            $rows = DB::getPdo()->exec($sql);
            $result = (0 < $rows) ? true : false;
        } else {
            $result = DB::table($this->manualExpenseTable)->insert($param);
        }
        
        return $result;
    }

    /**
     * 插入数据到相应的表
     * @param string $table 表名
     * @param array $data 数据
     * @param integer $count 每次执行条数
     */
    private function insert($table, $data, $count = 100)
    {
        $mysqlArray = [];
        if (count($data) > 0) {
            foreach ($data as $val) {
                $mysqlArray[] = $val;
                if (count($mysqlArray) == $count) {
                    DB::table($table)->insert($mysqlArray);
                    $mysqlArray = [];
                }
            }
            DB::table($table)->insert($mysqlArray);
        }
    }

    /**
     * 获取广告历史出价
     */
    private function getCampaignHistoryPrice($campaignId, $date)
    {
        $current_revenue = 0;
        $daytime = strtotime("$date 00:00:00");
        $price = 0;
        $time = 0;
        for ($i = 0; $i < 24; $i++) {
            $queryTime = gmdate('Y-m-d H:00:00', ($daytime + $time));
            $sql = "SELECT
                    c.campaignid,
                    IF (
                    tmp.current_revenue > 0,
                    tmp.current_revenue,
                    IF (ntmp.current_revenue > 0,ntmp.current_revenue,c.revenue)
                    ) AS current_revenue
                    FROM
                    up_campaigns c
                    LEFT JOIN (
                        SELECT
                        campaignid,
                        current_revenue
                        FROM
                        `up_campaign_revenue_history`
                        WHERE
                        campaignid = {$campaignId}
                        AND `time` <= DATE_FORMAT(
                        '{$queryTime}',
                        '%Y-%m-%d %H:59:59'
                        )
                        ORDER BY
                        id DESC
                    ) AS tmp ON (
                        tmp.campaignid = c.campaignid
                    )
                    LEFT JOIN (
                        SELECT
                        campaignid,
                        current_revenue
                        FROM
                        `up_campaign_revenue_history`
                        WHERE
                        campaignid = {$campaignId}
                        ORDER BY
                        id ASC LIMIT 1
                    ) AS ntmp ON (
                        ntmp.campaignid = c.campaignid
                    )
                    WHERE
                    c.campaignid = {$campaignId}
                    GROUP BY
                    tmp.campaignid;";
            $row = DB::selectOne($sql);
            $row = json_decode(json_encode($row), true);
            $price += $row['current_revenue'];
            $time += 3600;
        }

        $current_revenue = round($price / 24, 2);
        return $current_revenue;
    }
    
    
    /**
     * 检测审计表中是否存在当天的数据，没有就新增
     * @param array $rows
     */
    private function processOperationClients($rows)
    {
        $campaign_id = $rows['campaign_id'];
        $date = $rows['date'];
        //如果审计中不存在，补的数据类型为人工投放的
        $type = OperationClient::TYPE_ARTIFICIAL_DELIVERY;
        $status = OperationClient::STATUS_PENDING_AUDIT;
        $issue = OperationClient::ISSUE_NOT_APPROVAL;
        
        $count = DB::table('operation_clients')
                ->where('campaign_id', $campaign_id)
                ->where('date', $date)
                ->count();
        if (0 == $count) {
            $result = DB::table('operation_clients')->insert([
                    'campaign_id' => $campaign_id,
                    'type' => $type,
                    'status' => $status,
                    'issue' => $issue,
                    'date' => $date
            ]);
        }
    }
}
