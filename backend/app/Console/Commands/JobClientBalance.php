<?php
namespace App\Console\Commands;

use App\Components\Formatter;
use App\Components\Helper\EmailHelper;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;
use App\Components\Config;

class JobClientBalance extends Command
{
    /**
    * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_client_balance {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for monitor client balance.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /*
     * 极限值为100
     */
    private $limit = 100;

    /**
     * Execute the console command.
     * 每天03：00运行，监控余额变化，插入数据表up_client_balance_change中
     * 当真实的余额与账户余额差额大于100，发送警告邮件
     * @return mixed
     *
     * 逻辑思路大致为：审计+未审计+今天 = 总消耗
     * 总充值 - 总消耗 = 余额
     * 余额 - 当前余额 > 100 发邮件写日志
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
        $res = $this->getData($agency->agencyid);
        $list = [];
        $date = date("Y-m-d H:i:s");
        $current = date("Y-m-d");

        if (!empty($res)) {
            foreach ($res as $row) {
                $result = DB::table('client_balance_change')
                    ->where('account_id', $row['t_account'])
                    ->whereRaw("DATE_FORMAT(`created_time`, '%Y-%m-%d') = '{$current}'")
                    ->get();
                if (count($result) > 0) {
                    continue;
                } else {
                    $sql = "
                     INSERT INTO up_client_balance_change(
                         account_id,
                         clientname,
                         charge,cost,
                         balance,
                         true_balance,
                         sub,
                         created_time
                     )
                     VALUE (
                         {$row['t_account']},
                         '{$row['clientname']}',
                         {$row['t_charge']},
                         {$row['t_sum_price']},
                         {$row['t_balance']},
                         {$row['t_true_balance']},
                         {$row['t_sub']},
                         '{$date}'
                     )";
                    $update = DB::update($sql);
                    if (!$update) {
                        $this->notice('更新client_balance_change '. $sql);
                    }
                    if (abs($row['t_sub']) > $this->limit) {
                        $row['t_true_balance'] = Formatter::asDecimal($row['t_true_balance']);
                        $row['t_sub'] = Formatter::asDecimal($row['t_sub']);
                        $list[] = $row;
                    }
                }
            }
        }
        if (count($list) > 0) {
            //获取要发送的邮件地址
            $users = Config::get('client_balance_user', $agency->agencyid);
            $mail = [];
            $mail['subject'] = $current . '广告主余额监控告警';
            $mail['msg']['data'] = $list;
            EmailHelper::sendEmail(
                'emails.advertiser.advertiserBalanceChange',
                $mail,
                strpos($users, ';') ? explode(';', $users) : $users
            );
        }
    }
    /**
     * @获取广告变化的金额
     * @agencyId
     */
    private function getData($agencyId)
    {
        $sql = "select
                    t_account,
                    cli.clientname,
                    t_charge,
                    t_sum_price,
                    t_balance,
                    t_true_balance,
                    t_sub
                from (
                    SELECT
                        t1.account_id t_account,
                        t2.s_charge t_charge,
                        t1.s_amount t_sum_price,
                        t3.balance t_balance,
                        t2.s_charge-t1.s_amount t_true_balance,
                        t2.s_charge-t1.s_amount - t3.balance t_sub
                    FROM
                    (
                        SELECT
                            cl.account_id,
                            sum(s_amount) AS s_amount
                        FROM (
                            SELECT
                                c.campaignid AS campaign_id,
                                TRUNCATE(IFNULL(dc.total_revenue, 0) + IFNULL(d1.s_price,0) + IFNULL(d2.s_price,0), 2)
                                AS s_amount
                            FROM
                                up_campaigns AS c
                                LEFT JOIN (
                                SELECT
                                    campaign_id, sum(total_revenue) total_revenue
                                FROM
                                    up_data_hourly_daily_client
                                WHERE date <= DATE_SUB(CURDATE(),INTERVAL 1 DAY)
                                GROUP BY campaign_id
                            ) AS dc ON c.campaignid=dc.campaign_id
                            LEFT JOIN (
                                SELECT
                                    campaignid, sum(price) s_price
                                FROM
                                    up_delivery_log
                                WHERE
                                    actiontime >= DATE_SUB(DATE_FORMAT(CURDATE(),\"%Y-%m-%d 00:00:00\"),
                                    INTERVAL 8 HOUR)
                                    AND zoneid>0
                                    GROUP BY campaignid
                            ) d1 ON c.campaignid=d1.campaignid
                            LEFT JOIN (
                                SELECT
                                    c.campaign_id AS campaignid,SUM(d.total_revenue) s_price
                                FROM
                                    up_operation_clients c
                                JOIN up_data_hourly_daily d ON (
                                    c.campaign_id = d.campaign_id
                                    AND c.date = d.date
                                )
                                WHERE issue <> 0
                                GROUP BY c.campaign_id
                            ) d2 ON c.campaignid=d2.campaignid
                        ) AS dc
                        JOIN up_campaigns AS c ON dc.campaign_id=c.campaignid
                        JOIN up_clients AS cl ON cl.clientid = c.clientid
                        GROUP BY cl.account_id
                    ) AS t1
                    LEFT JOIN (
                        select
                            sum(amount) s_charge, target_acountid account_id
                        from
                            up_balance_log
                        where balance_type in(0,1,3,9,14,16) GROUP BY target_acountid
                    ) as t2 ON(t1.account_id=t2.account_id)
                    LEFT JOIN (
                        SELECT
                            account_id,balance+gift balance
                        FROM
                            `up_balances`
                    ) AS t3 ON(t1.account_id=t3.account_id)
                ) AS tmp
                JOIN up_clients cli on(cli.account_id=tmp.t_account)
                where
                    cli.agencyid = {$agencyId} and
                    tmp.t_sum_price > 0 AND tmp.t_account not in(431, 262, 478, 383)
                  AND tmp.t_sub <> 0 AND (tmp.t_sub > {$this->limit} or tmp.t_sub < - {$this->limit})";
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $rows = DB::select($sql);
        return $rows;
    }
}
