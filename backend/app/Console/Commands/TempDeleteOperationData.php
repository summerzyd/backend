<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\ExpenseLog;
use App\Models\DeliveryLog;
use App\Models\DeliveryManualLog;
use App\Models\ExpenseManualLog;
use Illuminate\Support\Facades\DB;
use App\Models\Affiliate;
use App\Models\Campaign;

class TempDeleteOperationData extends Command
{
    /**
     * The name and signature of the console command.
     * parameter side include all, client, affiliate
     * parameter count means the number to delete
     * @var string
     */
    protected $signature = 'temp_delete_operation_data {--build-date=}  {--agencyid=}  {--adid=}  {--zoneid=}
                            {--campaignid=}  {--side=}  {--count=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '运营需要删除数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $date = $this->option('build-date') ? $this->option('build-date') : '';
        $startTime = date('Y-m-d H:i:s', strtotime($date . ' -8 hour'));
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +1 days'));
        $dateFormat = date('Ym', strtotime($date));
        $adid = intval($this->option('adid')) ? intval($this->option('adid')) : 0;
        $zoneid = intval($this->option('zoneid')) ? intval($this->option('zoneid')) : 0;
        $campaignid = intval($this->option('campaignid')) ? intval($this->option('campaignid')) : 0;
        $side = $this->option('side');
        $count = intval($this->option('count')) ? intval($this->option('count')) : 0;
        if (! $count) {
            echo 'Please check the count to delete'. "\n";
            exit();
        }
        //判断人工还是程序化,删除不同的表;还有计费类型，目前只支持D->D的删除
        $res = DB::table("banners as b")
            ->join("campaigns as c", 'c.campaignid', '=', 'b.campaignid')
            ->join("affiliates as aff", 'aff.affiliateid', '=', 'b.affiliateid')
            ->join("clients as cli", 'cli.clientid', '=', 'c.clientid')
            ->select('b.revenue_type as b_revenue_type', 'c.revenue_type', 'aff.mode', 'cli.account_id')
            ->where('b.bannerid', $adid)
            ->where('c.campaignid', $campaignid)
            ->where('cli.agencyid', $agency->agencyid)
            ->first();
        $chk = $res->b_revenue_type == Campaign::REVENUE_TYPE_CPD && $res->revenue_type == Campaign::REVENUE_TYPE_CPD;
        if (! $chk) {
            echo 'Currently just support D->D'. "\n";
            exit();
        }
        $mode = $res->mode;
        $manaual_table = 'manual_deliverydata';
        $client_table = 'data_hourly_daily_client_'.$dateFormat;
        $client_accountid = $res->account_id;
        if ($mode == Affiliate::MODE_ARTIFICIAL_DELIVERY) {
            $delivery_table = 'delivery_manual_log';
            $expense_table = 'expense_manual_log';
        } else {
            $delivery_table = 'delivery_log';
            $expense_table = 'expense_log';
        }
        $params = array(
            'date' => $date,
            'adid' => $adid,
            'zoneid' => $zoneid,
            'campaignid' => $campaignid,
            'mode' => $mode,
            'delivery_table' => $delivery_table,
            'expense_table' => $expense_table,
            'manaual_table' => $manaual_table,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'agency' => $agency,
            'count' => $count,
            'client_table' => $client_table,
            'client_accountid' => $client_accountid
        );
        switch ($side) {
            case 'all':
                //广告主和媒体商的数据都要删除
                echo 'start to delete platform data:'.$date."\n";
                //事务执行全部的sql操作
                $transaction = DB::transaction(function () use ($params) {
                    $this->deleteClientData($params);
                    $this->deleteAffiliateData($params);
                    return true;
                });
                if (! $transaction) {
                    echo 'delete platform data occur error' . "\n";
                    $this->error("delete platform data occur error");
                    exit();
                } else {
                    echo 'delete platform data successfully:'.$date."\n";
                }
                break;
            case 'client':
                //只删除广告主的数据
                //补回广告主扣款`
                //如果是人工的还要更新up_manual_deliverydata表的数据
                echo 'start to delete client data:'.$date."\n";
                $transaction = DB::transaction(function () use ($params) {
                    $this->deleteClientData($params);
                    return true;
                });
                if (! $transaction) {
                    echo 'delete client data occur error' . "\n";
                    $this->error("delete client data occur error");
                    exit();
                } else {
                    echo 'delete client data successfully:'.$date."\n";
                }
                break;
            case 'affiliate':
                //只删除媒体商的有效数据(status=0)
                echo 'start to delete affiliate data:'.$date."\n";
                $transaction = DB::transaction(function () use ($params) {
                    $this->deleteAffiliateData($params);
                    return true;
                });
                if (! $transaction) {
                    echo 'delete affiliate data occur error' . "\n";
                    $this->error("delete affiliate data occur error");
                    exit();
                } else {
                    echo 'delete affiliate data successfully:'.$date."\n";
                }
                break;
            default:
                exit();
                break;
        }

        //运行修复脚本修复数据
        $this->notice('temp_delete_operation_data call commands day: ' . $date .'agencyid', $agency->agencyid);
        $this->call('job_repair_manage_stats', ['--build-date' => $date, '--agencyid' => $agency->agencyid]);
        echo 'successfully!';
        exit;
    }

    /**
     * 删除广告主数据，补回扣款，如是人工投放，更新人工导入数据表
     * @param params 参数数组，包含adid,zoneid,campaignid,date,agency等
     */
    public function deleteClientData($params)
    {
        try {
            //计算删除数据的总金额
            $query = DB::table($params['delivery_table'])
                ->where('campaignid', $params['campaignid'])
                ->where('zoneid', $params['zoneid'])
                ->where('actiontime', '>=', $params['startTime'])
                ->where('actiontime', '<', $params['endTime'])
                ->select(DB::raw('sum(price) as sum_price'))
                ->limit($params['count'])
                ->first();
            $sum_price = $query->sum_price;
            //如果删除的是人工投放数据，还要更新人工导入的表数据
            if ($params['mode'] == Affiliate::MODE_ARTIFICIAL_DELIVERY) {
                DB::table($params['manaual_table'])
                    ->where('date', '=', $params['date'])
                    ->where('banner_id', $params['adid'])
                    ->where('campaign_id', $params['campaignid'])
                    ->where('zone_id', $params['zoneid'])
                    ->update([
                        'conversions' => DB::raw("conversions - {$params['count']}"),
                        'revenues' => DB::raw("revenues - {$sum_price}")
                    ]);
                //减掉delivery_manual_log的count条数据，减掉删除掉的price
                DB::table($params['delivery_table'])
                    ->where('campaignid', $params['campaignid'])
                    ->where('zoneid', $params['zoneid'])
                    ->where('actiontime', '>=', $params['startTime'])
                    ->where('actiontime', '<', $params['endTime'])
                    ->update([
                        'amount' => DB::raw("amount - {$params['count']}"),
                        'price' => DB::raw("price - {$sum_price}")
                    ]);
            } else {
                //删除delivery_log的count条数据
                $log_delete = DB::table($params['delivery_table'])
                    ->where('campaignid', $params['campaignid'])
                    ->where('zoneid', $params['zoneid'])
                    ->where('actiontime', '>=', $params['startTime'])
                    ->where('actiontime', '<', $params['endTime'])
                    ->limit($params['count'])
                    ->delete();
            }
            //删除已审计的广告主数据
            $client_delete = DB::table($params['client_table'])
                ->where('ad_id', $params['adid'])
                ->where('campaignid', $params['campaignid'])
                ->where('zoneid', $params['zoneid'])
                ->where('date', '=', $params['date'])
                ->delete();
            //补回余额
            DB::table('balances')->where('account_id', $params['client_accountid'])
                ->update([
                    'balance' => DB::raw("balance + {$sum_price}")
                ]);
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * 删除媒体商数据，有效数据(status=0)
     * @param array $params
     */
    public function deleteAffiliateData($params)
    {
        try {
            if ($params['mode'] == Affiliate::MODE_ARTIFICIAL_DELIVERY) {
                $select = DB::table($params['expense_table'])
                    ->where('campaignid', $params['campaignid'])
                    ->where('zoneid', $params['zoneid'])
                    ->where('actiontime', '>=', $params['startTime'])
                    ->where('actiontime', '<', $params['endTime'])
                    ->select(DB::raw('af_income/amount as aff_income'))
                    ->first();
                $af_income = $select->aff_income;
                //更新expense_manaul_log表数据
                DB::table($params['expense_table'])
                    ->where('campaignid', $params['campaignid'])
                    ->where('zoneid', $params['zoneid'])
                    ->where('actiontime', '>=', $params['startTime'])
                    ->where('actiontime', '<', $params['endTime'])
                    ->update([
                        'amount' => DB::raw("amount - {$params['count']}"),
                        'af_income' => DB::raw("af_income - {$af_income}*{$params['count']}")
                    ]);
                //更新人工表数据
                DB::table($params['manaual_table'])
                    ->where('date', '=', $params['date'])
                    ->where('banner_id', $params['adid'])
                    ->where('campaign_id', $params['campaignid'])
                    ->where('zone_id', $params['zoneid'])
                    ->update([
                        'conversions' => DB::raw("conversions - {$params['count']}"),
                        'expense' => DB::raw("expense - {$af_income}*{$params['count']}")
                    ]);
            } else {
                //删除expense_log
                $log_delete = DB::table($params['expense_table'])
                    ->where('campaignid', $params['campaignid'])
                    ->where('zoneid', $params['zoneid'])
                    ->where('actiontime', '>=', $params['startTime'])
                    ->where('actiontime', '<', $params['endTime'])
                    ->where('status', 0)
                    ->limit($params['count'])
                    ->delete();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
