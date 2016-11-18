<?php
namespace App\Console\Commands;

use App\Components\Helper\EmailHelper;
use App\Components\Helper\LogHelper;
use App\Models\Agency;
use App\Models\BalanceLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\DeliveryManualLog;
use App\Models\DeliveryRepairLog;
use App\Components\Config;
use App\Models\ExpenseManualLog;
use App\Services\BalanceService;
use Illuminate\Support\Facades\DB;

class JobDeliveryRepairLog extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_delivery_repair_log {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修复delivery_log || expense_log || manual_delivery_log || manual_expense_log的数据';

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
        //执行php artisan的路径
        $system_dir = Config::get('biddingos.job.jobSystemArtisanDir');
        
       //获取状态为未处理的修复数据
        $data = DB::table('delivery_repair_log as d')
            ->join('campaigns as c', 'd.campaignid', '=', 'c.campaignid')
            ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
            ->where('cli.agencyid', $agency->agencyid)
            ->where('d.status', 0)
            ->select('d.*')
            ->orderBy("delivery_repair_log_id", 'desc')
            ->limit(50)
            ->get();
        //记录消息和执行job的日期
        $result = array();
        $result["message"] = ''; //记录日志消息
        $result["update_day"] = array();//记录job要执行的时间
        foreach ($data as $v) {
            $result = DB::transaction(function () use ($v, $result) {
                $flag = true;
                $message = '';
                $message .= "\n job_delivery_repair_log id:{$v->delivery_repair_log_id} \n";
                $comment = ""; //记录操作的时间和操作的数据量
                $mysqlArray = array();
                $deliveryRepairLog = DeliveryRepairLog::find($v->delivery_repair_log_id);
                //状态改成处理中
                $deliveryRepairLog->status = 1;
                $deliveryRepairLog->save();
                //查询10天内审计的最大日期，那么可操作的就是最大日期+1天
                $sql = <<<SQL
                    SELECT
                        DATE_ADD(MAX(date),INTERVAL 1 day) AS max_date
                    FROM
                        `up_operation_clients`
                    WHERE
                        campaign_id = '{$v->campaignid}'
                    AND issue = 0
                    AND date > DATE_SUB(CURDATE(),INTERVAL 10 DAY)
SQL;
                $max_date = DB::selectOne($sql);
                if (empty($max_date->max_date)) {
                    //查不到10天内最大的审计日期,补到前天
                    $message .= "\n Select max_date is empty SQL:\n{$sql} \n\t\n";
                    $message .= "\n repair on the day before yesterday \n";
                    $target_datetime = date("Y-m-d", strtotime("-2 day"));
                } else {
                    $target_datetime = $max_date->max_date;
                }
                //确保前面没有错误
                if ($flag) {
                    //修复delivery_log actiontime>=$date_time 的人工录数据或者程序化数据
                    $date_time = $target_datetime;

                    //是否需要补到指定的日期
                    if (!empty($v->date_time) && $v->date_time >= $target_datetime) {
                        $date_time = $v->date_time;
                    }
                    $result["update_day"][$date_time] = $date_time;
                    //要增加数据到down_log 或者 click_log
                    if ($v->amount > 0) {
                        $message .= "\n Insert Data to be added to actiontime = $date_time \n";
                        //要增加条数
                        $num = $v->amount;
                        $sql = <<<SQL
                            SELECT
                                c.campaignid
                            FROM
                                up_campaigns c
                            JOIN up_banners b ON (c.campaignid = b.campaignid)
                            JOIN up_clients cli ON (c.clientid = cli.clientid)
                            JOIN up_affiliates aff ON (b.affiliateid = aff.affiliateid)
                            WHERE
                                b.bannerid = {$v->bannerid}
                            AND cli.clients_status = 1
                            AND aff.affiliates_status = 1
                            AND c.`status` = 0
                            AND b.`status` = 0
SQL;
                        //查询广告是否在投放中
                        $act_campaignid = DB::selectOne($sql);
                        if (empty($act_campaignid->campaignid)) {
                            //广告已经暂停，不能补数据
                            $message .= "\n Ad has suspended sql:\n{$sql} \n";
                            $flag = false;
                        } else {
                            if ($v->source == 0) {
                                //24小时的展示量，用于分配down和click生成24小时的数据
                                $res = $this->programMultiRecord($date_time, $v, $num, $message, $flag);
                            } else {
                                $date_time = gmdate("Y-m-d H:i:s", strtotime($date_time ." 03:00:00"));
                                $res = $this->manualMultiRecord($date_time, $v, $num, $flag, $message);
                            }
                            $flag = $res['flag'];
                            $message = $res['message'];
                            $comment = $res['comment'];
                        }
                    } else {
                        $num = abs($v->amount);
                        if ($v->source == 0) {
                            $res = $this->programReduceRecord($date_time, $v, $num, $message, $flag);
                        } else {
                            $date_time = gmdate("Y-m-d H:i:s", strtotime($date_time ." 03:00:00"));
                            $res = $this->manualReduceRecord($date_time, $v, $num, $message, $flag);
                        }
                        $flag = $res['flag'];
                        $message = $res['message'];
                        $comment = $res['comment'];
                    }
                }

                $this->notice($message);
                $result['message'] .= $message;
                if (!$flag) {
                    LogHelper::error($message);
                    DB::rollBack();
                } else {
                    //状态改成已完成
                    $deliveryRepairLog->status = 2;
                    $deliveryRepairLog->comment = $comment;
                    $deliveryRepairLog->save();
                }
                return $result;
            });
        }
        
        if (count($data) > 0) {
            if (count($result["update_day"]) > 0) {
                foreach ($result["update_day"] as $day_k => $day_v) {
                    $this->call('job_mixing_amount', [
                        'start_time' => gmdate("Y-m-d H:i:s", strtotime($day_v . '00:00:00')),
                        'end_time' => gmdate("Y-m-d H:i:s", strtotime($day_v . ' 23:59:59'))
                    ]);
                }
            }
            $this->notice("\n Job Return Success!");
        } else {
            $this->notice("\n Job No Log To Cost!");
        }
        
        //每天中午12点将历史正在处理的数据改成未处理 status从1改成0
        $time = date("H");
        if ($time == '12') {
            $sql = <<<SQL
            UPDATE up_delivery_repair_log SET status = 0 WHERE status = 1;
SQL;
            $query = DB::statement($sql);
            $notice = "\n JobDeliveryRepairLog 12 O'clock update status from 1 to 0 "
                . ($query ? "Success" : "Fail")
                . ", sql:\n{$sql} \n";
            $this->notice($notice);
        }
        
        //发邮件给成每天12点才发
        $time = date("H");
        if ($time == '12') {
            if (!empty($result['message'])) {
                $email_users = Config::get('job_delivery_repair_log', $agency->agencyid);
                $this->notice("send email users :" . json_encode($email_users));
                $subject = "job_delivery_repair_log 修复delivery_log || expense_log
                || manual_delivery_log || manual_expense_log的数据";
                $view = 'emails.command.deliveryRepair';
                EmailHelper::sendEmail(
                    $view,
                    [
                        'subject' => $subject,
                        'msg' => [
                            'jobInfo' => $result['message'],
                        ],
                    ],
                    strpos($email_users, ';') ? explode(';', $email_users) : $email_users
                );
            }
        }
    }

    /**
     * 24小时的展示量，用于分配down和click生成24小时的数据
     * @param day $day Y-m-d
     * @return array
     */
    private function getImpression($day)
    {
        $day_start = gmdate("Y-m-d H:i:s", strtotime("$day 00:00:00"));
        $day_end = gmdate("Y-m-d H:i:s", strtotime("$day 23:59:59"));
        $impressions_row = $this->getDefaulImpression($day);
        $impressions_day['total'] = 0;
        //把数据打散到24小时
        foreach ($impressions_row as $val) {
            $impressions_day[$val->date_time2] = $val->sum_impressions;
            $impressions_day['total'] += $val->sum_impressions;
        }
        //先赋予0，给后面分配down click总条数时使用
        $impressions_day['delivery_count'] = 0;

        return $impressions_day;
    }

    /**
     * 获取默认的每小时展示量
     * @param type $day
     */
    private function getDefaulImpression($day)
    {
        // 补量数据都补到当天的03时
        $result = array();
        $daytime = gmdate("Y-m-d H:i:s", strtotime($day ." 03:00:00"));
        $result[] = (object) ["sum_impressions" => rand(10000, 100000), "date_time2" => $daytime];
        
        return $result;
    }

    /**
     * 程序化支持D2D C2C A2A生成要插入到媒体及广告消耗表
     * @param array $impressions_day 当天每小时要插入数据的比例
     * @param array $log 要生成的数据详情
     * @param int $num 要生成的数据量
     * @return array key=要插入的表 values[]=要插入的数据
     */
    private function getInsertLog($impressions_day, $log, $amount)
    {
        $data = array();
        //按照repair_log的amount录入到相应的表
        $first_key = '';
        foreach ($impressions_day as $key => $val) {
            if ($key == 'total' || $key == 'views' || $key == 'delivery_count') {
                continue;
            }
            //每小时要插入的数据
            if (empty($first_key)) {
                //先给第一条赋予总数，然后在递减
                $first_key = $key;
                $data[$first_key][$log->zoneid][$log->campaignid]['delivery_count'] = $amount;
            } else {
                //第二条以后在用第一条的总数递减
                $data[$key][$log->zoneid][$log->campaignid]['delivery_count'] = ceil($amount
                        * ($val / $impressions_day['total']));
                //判断如果总数<=当前要分配的数，那后面的都赋予0
                if ($data[$first_key][$log->zoneid][$log->campaignid]['delivery_count'] <=
                        $data[$key][$log->zoneid][$log->campaignid]['delivery_count'] ||
                        $data[$first_key][$log->zoneid][$log->campaignid]['delivery_count'] == 0
                ) {
                    $data[$key][$log->zoneid][$log->campaignid]['delivery_count'] = 0;
                }
                $data[$first_key][$log->zoneid][$log->campaignid]['delivery_count'] -=
                        $data[$key][$log->zoneid][$log->campaignid]['delivery_count'];
            }
            
            //按历史记录计算应用的
            $sql = <<<SQL
                SELECT
                    c.campaignid,
                    IF(tmp.current_revenue > 0, tmp.current_revenue, c.revenue) AS current_revenue
                FROM up_campaigns c
                LEFT JOIN (
                    SELECT
                        campaignid,current_revenue
                    FROM `up_campaign_revenue_history`
                    WHERE
                        campaignid={$log->campaignid}
                        AND `time`<=DATE_FORMAT('{$key}','%Y-%m-%d %H:59:59')
                    ORDER BY id DESC
                ) AS tmp
                ON(tmp.campaignid=c.campaignid)
                WHERE c.campaignid={$log->campaignid}
                GROUP BY tmp.campaignid
SQL;
            $campaign_revenue = DB::selectOne($sql);
            //扣费类型
            $data[$key][$log->zoneid][$log->campaignid]['source_log_type'] = $log->amount_type;
            //广告主价格
            $data[$key][$log->zoneid][$log->campaignid]['price_revenue'] = $campaign_revenue->current_revenue;
            //媒体价格
            $data[$key][$log->zoneid][$log->campaignid]['price_income'] = sprintf("%.2f", $log->expense / $amount);
        }

        //将相关数据组成insert_log数据返回
        $mysqlArray = array();
        foreach ($data as $k_hour => $v_hour) {
            foreach ($v_hour as $k_zoneid => $v_zoneid) {
                foreach ($v_zoneid as $k_campaignid => $v_campaignid) {
                    var_dump($v_campaignid['delivery_count']);
                    for ($i = 0; $i < $v_campaignid['delivery_count']; $i++) {
                        $accountId = 0;
                        $gift = BalanceService::getGiftPrice($accountId, $v_campaignid['price_revenue'])['price_gift'];
                        $mysqlArray['expense_log'][] = [
                            'campaignid' => $k_campaignid,
                            'zoneid' => $k_zoneid,
                            'price' => $v_campaignid['price_revenue'],
                            'price_gift' => $gift,
                            'actiontime' => $k_hour,
                            'channel' => 'repair' . $log->delivery_repair_log_id,
                            'af_income' => $v_campaignid['price_income'],
                            'status' => 2,
                            'target_type' => 'job',
                            'target_cat' => 'repair_log',
                            'target_id' => md5(uniqid(md5(microtime(true)), true))
                        ];
                        $mysqlArray['delivery_log'][] = [
                            'campaignid' => $k_campaignid,
                            'zoneid' => $k_zoneid,
                            'price' => $v_campaignid['price_revenue'],
                            'price_gift' => $gift,
                            'actiontime' => $k_hour,
                            'channel' => 'repair' . $log->delivery_repair_log_id,
                            'af_income' => $v_campaignid['price_income'],
                            'status' => 2,
                            'target_type' => 'job',
                            'target_cat' => 'repair_log',
                            'target_id' => md5(uniqid(md5(microtime(true)), true))
                        ];
                    }


                }
            }
        }
        
        return $mysqlArray;
    }

    /**
     * @param
     * @return string
     * 插入人工数据-广告主扣费表
     */
    private function updateDeliveryManualLog($param)
    {
        $fields = array_keys($param);
        $values = [];
        foreach ($param as $k => $v) {
            $values[] = "'{$v}'";
        }
        $status = DB::update('INSERT INTO up_delivery_manual_log('
            . implode(',', $fields)
            . ') VALUES('
            . implode(',', $values)
            . ') ON DUPLICATE KEY UPDATE amount= amount +'.$param['amount']
            . ',af_income= af_income + '.$param['af_income']
            . ',price_gift = price_gift +'.$param['price_gift']
            . ",price = price +'{$param['price']}'");
        if (!$status) {
            $this->error("stat daliveryManualLog manual error, sql: {$status->toSql()} param:" . json_encode($param));
            return false;
        }
        return true;
    }

    /**
     * @param $param
     * @return mixed
     * 插入人工数据-媒体扣费表
     */
    private function updateExpenseManualLog($param)
    {
        $fields = array_keys($param);
        $values = [];
        foreach ($param as $k => $v) {
            $values[] = "'{$v}'";
        }
        $status = DB::update('INSERT INTO up_expense_manual_log('
            . implode(',', $fields)
            . ') VALUES('
            . implode(',', $values)
            . ') ON DUPLICATE KEY UPDATE amount= amount +'.$param['amount']
            . ',af_income= af_income + '.$param['af_income']
            . ',price_gift = price_gift +'.$param['price_gift']
            . ",price = price +'{$param['price']}'");
        if (!$status) {
            $this->error("stat expenseManualLog manual error, sql: {$status->toSql()} param:" . json_encode($param));
            return false;
        }
        return true;
    }
    /**
     * 程序化投放-多录
     * @param $date_time
     * @param $v
     * @param $num
     * @param $message
     * @param $flag
     * @return bool
     */
    private function programMultiRecord($date_time, $v, $num, $message, $flag)
    {
        $revenue = 0;
        $gift = 0;
        $impressions_day = $this->getImpression($date_time);
        //生成要插入的数据
        $mysqlArray = $this->getInsertLog($impressions_day, $v, $num);
        //插入相应数据库，$tb是表名
        foreach ($mysqlArray as $tb => $tb_v) {
            $mysqlArray['delivery_datas'] = array();
            if (count($tb_v) > 0) {
                $message .= "\n Insert Info: table = $tb, "
                    . "channel = repair{$v->delivery_repair_log_id}, "
                    . "source = {$v->source}, amount = $num, "
                    . "insert amount = " . count($tb_v) . " \n";
                foreach ($tb_v as $k_insert => $v_insert) {
                    $revenue += $v_insert['price'];
                    $gift += $v_insert['price_gift'];
                    $mysqlArray['delivery_datas'][] = $v_insert;
                    if (count($mysqlArray['delivery_datas']) == 100) {
                        \DB::table($tb)->insert($mysqlArray['delivery_datas']);
                        $mysqlArray['delivery_datas'] = array();
                    }
                }
                DB::table($tb)->insert($mysqlArray['delivery_datas']);
                $comment = json_encode(
                    array(
                        'date_time' => $date_time,
                        'amount' => count($tb_v)
                    )
                );
                $message .= "\n JobDeliveryRepairLog "
                    . "Insert $tb: Success, comment: $comment \n";
            } else {
                //getInsertLog 方法返回的值为null
                $message .= "\n The return value of function getInsertLog is empty \n";
                $flag = false;
            }
        }
        $balance = $revenue - $gift;
        $sql = <<<SQL
                                    UPDATE up_balances
                                    SET
                                        balance = balance-'{$balance}',
                                        gift = gift-'{$gift}'
                                    WHERE
                                        account_id=(
                                            SELECT account_id
                                            FROM up_campaigns c
                                            JOIN `up_clients` cli ON (c.clientid = cli.clientid)
                                            WHERE campaignid = {$v->campaignid}
                                        )
SQL;
        $query = DB::statement($sql);
        $comment = json_encode(
            array(
                'day_start' => gmdate("Y-m-d H:i:s", strtotime("$date_time 00:00:00")),
                'amount' => $num,
                'sum_balance' => $revenue,
                'sum_gift' => $gift,
                'info' => 'JobDeliveryRepairLog'
            )
        );
        $message .= "\n JobDeliveryRepairLog Update up_balances: "
            . ($query ? "Success" : "Fail")
            . ", comment: $comment sql:\n{$sql} \n";
        return ['flag' => $flag, 'message' => $message, 'comment' => $comment];
    }

    /**
     * @param $date_time
     * @param $v
     * @param $num
     * @param $message
     * @param $flag
     */
    private function programReduceRecord($date_time, $v, $num, $message, $flag)
    {
        //要删除的起始时间 UTC
        $day_start = gmdate("Y-m-d H:i:s", strtotime("$date_time 00:00:00"));

        $delete_infos = array(
            'sum_balance' => 0,
            'sum_gift' => 0,
            'log_amount' => 0,
            'info' => ''
        );
        //是否满足删除数据的条件
        $sql = <<<SQL
                            SELECT
                                days,
                                COUNT(1) AS log_amount,
                                TRUNCATE(SUM(price)-SUM(price_gift), 2) AS sum_balance,
                                SUM(price_gift) AS sum_gift
                            FROM
                            (
                                    SELECT *, DATE_FORMAT(DATE_ADD(actiontime,INTERVAL 8 HOUR),"%Y-%m-%d") as days
                            FROM
                                up_delivery_log
                            WHERE
                                zoneid = {$v->zoneid} AND campaignid = {$v->campaignid}
                                AND actiontime >= '$day_start'
                                AND source = {$v->source}
                                AND source_log_type = '{$v->amount_type}'
                                ORDER BY actiontime ASC LIMIT $num
                            ) as tmp
                            GROUP BY days
SQL;
        $query = DB::select($sql);
        foreach ($query as $val) {
            $delete_infos['sum_balance'] += $val->sum_balance;
            $delete_infos['sum_gift'] += $val->sum_gift;
            $delete_infos['log_amount'] += $val->log_amount;
            $delete_infos['info'] .= "{$val->days} | {$val->log_amount} | "
                . "{$val->sum_balance} | {$val->sum_gift} |";
        }

        if (($delete_infos['sum_balance'] > 0 || $delete_infos['sum_gift'] > 0) &&
            $delete_infos['log_amount'] == $num) {
            //删除数据
            $sql = <<<SQL
                                DELETE FROM
                                    up_delivery_log
                                WHERE
                                    zoneid = {$v->zoneid}
                                    AND campaignid = {$v->campaignid}
                                    AND actiontime >= '$day_start'
                                    AND source = {$v->source}
                                    AND source_log_type = '{$v->amount_type}'
                                    ORDER BY actiontime ASC LIMIT $num
SQL;
            $query = DB::statement($sql);
            $message .= "\n JobDeliveryRepairLog Delete up_delivery_log: "
                . ($query ? "Success" : "Fail")
                . " sql:\n{$sql} \n";

            if ($query) {

                //更新余额
                $sql = <<<SQL
                                    UPDATE up_balances
                                    SET
                                        balance=balance+'{$delete_infos['sum_balance']}',
                                        gift=gift+'{$delete_infos['sum_gift']}'
                                    WHERE
                                        account_id=(
                                            SELECT account_id
                                            FROM up_campaigns c
                                            JOIN `up_clients` cli ON (c.clientid = cli.clientid)
                                            WHERE campaignid = {$v->campaignid}
                                        )
SQL;
                $query = DB::statement($sql);
                $comment = json_encode(
                    array(
                        'day_start' => $day_start,
                        'amount' => $num,
                        'sum_balance' => $delete_infos['sum_balance'],
                        'sum_gift' => $delete_infos['sum_gift'],
                        'info' => $delete_infos['info']
                    )
                );
                $message .= "\n JobDeliveryRepairLog Update up_balances: "
                    . ($query ? "Success" : "Fail")
                    . ", comment: $comment sql:\n{$sql} \n";
            }
        } else {
            //delivery的数据不够删
            $message .= "\n DeliveryLog log_amount != delete num "
                . "log_amount:{$delete_infos['log_amount']} \n"
                . "delete_amount:{$num} \n"
                . "sum_balance:{$delete_infos['sum_balance']} \n"
                . "info:{$delete_infos['info']} \n"
                . "sql:\n{$sql} \n";
            $flag = false;
        }
        return ['flag' => $flag, 'message' => $message, 'comment' => $comment];
    }

    /**
     * @param $date_time
     * @param $v
     * @param $num
     * @param $message
     * @param $flag
     * @return bool
     */
    private function manualMultiRecord($date_time, $v, $num, $message, $flag)
    {
        $account_id = Campaign::find($v->campaignid)->client->account_id;
        $price_gift = BalanceService::getGiftPrice($account_id, $v->revenue)['price_gift'];
        $param = [
            'campaignid' => $v->campaignid,
            'bannerid' => $v->bannerid,
            'zoneid' => $v->zoneid,
            'amount' => $v->amount,
            'af_income' => $v->expense,
            'price' => $v->revenue,
            'price_gift' => $price_gift,
            'actiontime' => $date_time,
            'source_log_type' => $v->amount_type,
            'channel' => $v->delivery_repair_log_id,
        ];
        $balance = $v->revenue - $price_gift;
        if ($v->amount_type == 'A2D-AF' || $v->amount_type == 'A2C-AF') {
            $flag = $this->updateExpenseManualLog($param);
        } else {
            $flag = $this->updateDeliveryManualLog($param);
            if (!$flag) {
                $this->error("stat deliveryManualLog manual error" . json_encode($param));
                $message .= "stat deliveryManualLog manual error" . json_encode($param);
                $flag = false;
            }
            $flag = $this->updateExpenseManualLog($param);
            if (!$flag) {
                $this->error("stat expenseManualLog manual error" . json_encode($param));
                $message .= "stat expenseManualLog manual error" . json_encode($param);
                $flag = false;
            }
            if ($flag) {
                $res = <<<SQL
                                    UPDATE up_balances
                                    SET
                                        balance = balance-'{$balance}',
                                        gift = gift - '{$price_gift}'
                                    WHERE
                                        account_id=(
                                            SELECT account_id
                                            FROM up_campaigns c
                                            JOIN `up_clients` cli ON (c.clientid = cli.clientid)
                                            WHERE campaignid = {$v->campaignid}
                                        )
SQL;
                $query = DB::statement($res);

                $comment = json_encode(
                    array(
                        'day_start' => $date_time,
                        'amount' => $num,
                        'sum_balance' => $v->revenue,
                        'sum_gift' => $price_gift,
                        'info' => 'JobDeliveryRepairLog'
                    )
                );
                $message .= "\n JobDeliveryRepairLog Update up_balances: "
                    . ($query ? "Success" : "Fail")
                    . ", comment: $comment sql:\n{$res} \n";
            }

        }
        return ['flag' => $flag, 'message' => $message, 'comment' => $comment];
    }

    /**
     * @param $date_time
     * @param $v
     * @param $num
     * @param $message
     * @param $flag
     */
    private function manualReduceRecord($date_time, $v, $num, $message, $flag)
    {
        $comment = '';
        if ($v->amount_type == 'A2D-AF' || $v->amount_type == 'A2C-AF') {
            $res = ExpenseManualLog::where('campaignid', $v->campaignid)
                ->where('bannerid', $v->bannerid)
                ->where('zoneid', $v->zoneid)
                ->where('actiontime', $date_time)
                ->first();
            if ($res && $res->amount <= $v->amount && abs($res->af_income) >= $v->expense) {
                $res->amount += $v->amount;
                $res->price += $v->revenue;
                $res->af_income += $v->expense;
                $res->save();
            } else {
                //manual_delivery_log的数据不够删
                $message .= "\n DeliveryLog log_amount != delete num "
                    . "log_amount:{$v->amount} \n"
                    . "delete_amount:{$num} \n"
                    . "sum_balance:{$v->amount} \n"
                    ."campaignid:\n{$v->campaignid} \n";
                $flag = false;
            }
        } else {
            $delivery = DeliveryManualLog::where('campaignid', $v->campaignid)
                ->where('bannerid', $v->bannerid)
                ->where('zoneid', $v->zoneid)
                ->where('actiontime', $date_time)
                ->first();
            if ($delivery && $delivery->amount <= abs($v->amount) && $delivery->price <= abs($v->revenue)) {
                $delivery->amount  += $v->amount;
                $delivery->price += $v->revenue;
                $delivery->af_income += $v->expense;
                $delivery->save();
            } else {
                $message .= "\n DeliveryLog log_amount != delete num "
                    . "log_amount:{$v->amount} \n"
                    . "delete_amount:{$num} \n"
                    . "sum_balance:{$v->amount} \n"
                    ."campaignid:\n{$v->campaignid} \n";
                $flag = false;
            }
            $expense = ExpenseManualLog::where('campaignid', $v->campaignid)
                ->where('bannerid', $v->bannerid)
                ->where('zoneid', $v->zoneid)
                ->where('actiontime', $date_time)
                ->first();
            if ($expense && $expense->amount <= abs($v->amount) && $expense->af_income >= abs($v->expense)) {
                $expense->amount  += $v->amount;
                $expense->price += $v->revenue;
                $expense->af_income += $v->expense;
                $expense->save();
            } else {
                $message .= "\n DeliveryLog log_amount != delete num "
                    . "log_amount:{$v->amount} \n"
                    . "delete_amount:{$num} \n"
                    . "sum_balance:{$v->amount} \n"
                    ."campaignid:\n{$v->campaignid} \n";
                $flag = false;
            }
            if ($flag) {
                $account_id = Campaign::find($v->campaignid)->client->account_id;
                if ($delivery->price_gift > abs($v->revenue)) {
                    $gift = $v->revenue;
                    $balance = 0;
                } else {
                    $gift = $delivery->price_gift;
                    $balance = abs($v->revenue) - $gift;
                }
                $sql = <<<SQL
                        UPDATE up_balances
                        SET
                            balance = balance+{$balance},
                            gift = gift + {$gift}
                        WHERE
                            account_id=(
                                SELECT account_id
                                FROM up_campaigns c
                                JOIN `up_clients` cli ON (c.clientid = cli.clientid)
                                WHERE campaignid = {$v->campaignid}
                            )
SQL;
                $query = DB::statement($sql);
                $comment = json_encode(
                    array(
                       'day_start' => $v->actiontime,
                       'amount' => $num,
                       'sum_balance' => $balance,
                       'sum_gift' => $gift,
                    )
                );
                $message .= "\n JobDeliveryRepairLog Update up_balances: "
                   . ($query ? "Success" : "Fail")
                   . ", comment: $comment sql:\n{$sql} \n";
            }
            return ['flag' => $flag, 'message' => $message, 'comment' => $comment];
        }
    }
}
