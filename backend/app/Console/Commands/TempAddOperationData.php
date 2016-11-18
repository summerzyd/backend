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

class TempAddOperationData extends Command
{
    /**
     * The name and signature of the console command.
     * parameter side include all, client, affiliate
     * parameter count means the number to delete
     * @var string
     */
    protected $signature = 'temp_add_operation_data {--build-date=}  {--agencyid=}  {--adid=}  {--zoneid=}
                            {--campaignid=}  {--conversions=}  {--revenues=} {--expenses=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '运营需求补录数据';

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
        $dateFormat = date('Ym', strtotime($date));
        $startTime = date('Y-m-d H:i:s', strtotime($date . ' -8 hour'));
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +1 days'));
        $conversions = intval($this->option('conversions')) ? intval($this->option('conversions')) : 0;
        $revenues = floatval($this->option('revenues')) ? floatval($this->option('revenues')) : 0;
        $expenses = floatval($this->option('expenses')) ? floatval($this->option('expenses')) : 0;
        $adid = intval($this->option('adid')) ? intval($this->option('adid')) : 0;
        $zoneid = intval($this->option('zoneid')) ? intval($this->option('zoneid')) : 0;
        $campaignid = intval($this->option('campaignid')) ? intval($this->option('campaignid')) : 0;
        if (! $conversions) {
            echo 'Please check the conversions to delete'. "\n";
            exit();
        }
        //判断人工还是程序化,删除不同的表;还有计费类型，目前只支持D->D的删除
        $res = DB::table("banners as b")
            ->join("campaigns as c", 'c.campaignid', '=', 'b.campaignid')
            ->join("clients as cli", 'cli.clientid', '=', 'c.clientid')
            ->join("affiliates as aff", 'aff.affiliateid', '=', 'b.affiliateid')
            ->select('b.revenue_type as b_revenue_type', 'c.revenue_type', 'aff.mode', 'cli.account_id')
            ->where('b.bannerid', $adid)
            ->where('cli.agencyid', $agency->agencyid)
            ->where('c.campaignid', $campaignid)
            ->first();
        $chk = $res->b_revenue_type == Campaign::REVENUE_TYPE_CPD && $res->revenue_type == Campaign::REVENUE_TYPE_CPD;
        if (! $chk) {
            echo 'Currently just support D->D adding data'. "\n";
            exit();
        }
        $mode = $res->mode;
        if ($mode == Affiliate::MODE_ARTIFICIAL_DELIVERY) {
            $delivery_table = 'delivery_manual_log';
            $expense_table = 'expense_manual_log';
        } else {
            $delivery_table = 'delivery_log';
            $expense_table = 'expense_log';
        }
        $manaual_table = 'manual_deliverydata';
        $client_table = 'data_hourly_daily_client_'.$dateFormat;
        $client_accountid = $res->account_id;
        $params = array(
            'date' => $date,
            'adid' => $adid,
            'zoneid' => $zoneid,
            'campaignid' => $campaignid,
            'conversions' => $conversions,
            'revenues' => $revenues,
            'expenses' => $expenses,
            'mode' => $mode,
            'delivery_table' => $delivery_table,
            'expense_table' => $expense_table,
            'manaual_table' => $manaual_table,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'agency' => $agency,
            'client_table' => $client_table,
            'client_accountid' => $client_accountid
        );

        $transaction = DB::transaction(function () use ($params) {
            echo 'start to deal with client data:'.$params['date']."\n";
            $this->addClientData($params);
            echo 'sucessfully deal with client data:'.$params['date']."\n";
            echo 'start to deal with affiliate data:'.$params['date']."\n";
            $this->addAffiliateData($params);
            echo 'sucessfully deal with affiliate data:'.$params['date']."\n";
            return true;
        });
        if (! $transaction) {
            echo 'add data occur error' . "\n";
            exit();
        } else {
            echo 'Add data successfully:'.$date."\n";
        }

        //运行修复脚本修复数据
        $this->notice('temp_add_operation_data call commands day: ' . $date .'agencyid', $agency->agencyid);
        $this->call('job_repair_manage_stats', ['--build-date' => $date, '--agencyid' => $agency->agencyid]);
        echo 'successfully!';
        exit;
    }

    /**
     * 处理广告主数据，增加扣款，增加记录到delivery_log或者delivery_manaul_log，
     * 并更新人工导入数据表
     * @param params 参数数组，包含adid,zoneid,campaignid,date,agency等
     */
    public function addClientData($params)
    {
        try {
            //插入数据到delivery_log表，人工的话更新delivery_manual_log和manual_deliverydata表
            //扣除广告主消耗, 删除广告主已审计的记录，以便重新生成
            if ($params['mode'] == Affiliate::MODE_ARTIFICIAL_DELIVERY) {
                DB::table($params['manaual_table'])
                    ->where('date', '=', $params['date'])
                    ->where('banner_id', $params['adid'])
                    ->where('campaign_id', $params['campaignid'])
                    ->where('zone_id', $params['zoneid'])
                    ->update([
                        'conversions' => DB::raw("conversions + {$params['conversions']}"),
                        'revenues' => DB::raw("revenues + {$params['revenues']}"),
                        'expense' => DB::raw("expense + {$params['expenses']}")
                    ]);
                DB::table($params['delivery_table'])
                    ->where('campaignid', $params['campaignid'])
                    ->where('zoneid', $params['zoneid'])
                    ->where('actiontime', '>=', $params['startTime'])
                    ->where('actiontime', '<', $params['endTime'])
                    ->update([
                        'amount' => DB::raw("amount + {$params['conversions']}"),
                        'price' => DB::raw("price + {$params['revenues']}")
                    ]);
            } else {
                $sql = '';
                $valueString = '';
                $price = round($params['revenues']/$params['conversions'], 2);
                $af_income = round($params['expenses']/$params['conversions'], 2);
                $prefix = DB::getTablePrefix();
                $sql .= "insert into {$prefix}{$params['delivery_table']}";
                $sql .= "(campaignid,zoneid,cb,price,price_gift,actiontime,af_income,source_log_type) values";
                for ($i=0; $i<$params['conversions']; $i++) {
                    $values = [
                        $params['campaignid'],
                        $params['zoneid'],
                        $params['date'].$i,
                        $price,
                        0,
                        $params['startTime'],
                        $af_income,
                        'down'
                    ];
                    $valueString .= '("' . implode('","', $values) . '"),';
                }
                $valueString = rtrim($valueString, ',');
                $sql .= $valueString;
                DB::statement($sql);
            }
            //删除已审计的广告主数据
            $client_delete = DB::table($params['client_table'])
                ->where('ad_id', $params['adid'])
                ->where('campaignid', $params['campaignid'])
                ->where('zoneid', $params['zoneid'])
                ->where('date', '=', $params['date'])
                ->delete();
            //扣除余额
            DB::table('balances')->where('account_id', $params['client_accountid'])
                ->update([
                    'balance' => DB::raw("balance - {$params['revenues']}")
                ]);
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * 插入媒体商数据
     * @param array $params
     */
    public function addAffiliateData($params)
    {
        try {
            if ($params['mode'] == Affiliate::MODE_ARTIFICIAL_DELIVERY) {
                DB::table($params['expense_table'])
                    ->where('campaignid', $params['campaignid'])
                    ->where('zoneid', $params['zoneid'])
                    ->where('actiontime', '>=', $params['startTime'])
                    ->where('actiontime', '<', $params['endTime'])
                    ->update([
                        'amount' => DB::raw("amount + {$params['conversions']}"),
                        'af_income' => DB::raw("af_income + {$params['expenses']}")
                    ]);
            } else {
                $sql = '';
                $valueStr = '';
                $price = round($params['revenues']/$params['conversions'], 2);
                $af_income = round($params['expenses']/$params['conversions'], 2);
                $prefix = DB::getTablePrefix();
                $sql .= "insert into {$prefix}{$params['expense_table']}";
                $sql .= "(campaignid,zoneid,cb,price,price_gift,actiontime,af_income,source_log_type) values";
                for ($i=0; $i<$params['conversions']; $i++) {
                    $values = [
                        $params['campaignid'],
                        $params['zoneid'],
                        $params['date'].$i,
                        $price,
                        0,
                        $params['startTime'],
                        $af_income,
                        'down'
                    ];
                    $valueStr .= '("' . implode('","', $values) . '"),';
                }
                $valueStr = rtrim($valueStr, ',');
                $sql .= $valueStr;
                DB::statement($sql);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
