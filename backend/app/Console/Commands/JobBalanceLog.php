<?php
namespace App\Console\Commands;

use App\Components\Helper\LogHelper;
use App\Models\Agency;
use App\Models\BalanceLog;
use Illuminate\Support\Facades\DB;

class JobBalanceLog extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_balance_log   {--build-date=} {--force=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh balance log data using delivery log.';

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
        $type = $this->option('force');
        $date = $this->option('build-date') ? $this->option('build-date') : date('Y-m-d');
        //处理历史数据
        if ('all' == $type) {
            $start = '2015-01-01';
            $end = date('Y-m-d', strtotime('-1 day', strtotime($date)));
        } else {
            $start = date('Y-m-d', strtotime('-5 day', strtotime($date)));
            $end =  date('Y-m-d', strtotime('-1 day', strtotime($date)));
        }
        $prefix = DB::getTablePrefix();
        $rows = DB::table('data_hourly_daily_client AS dc')
                ->leftJoin('clients', 'clients.clientid', '=', 'dc.clientid')
                ->leftJoin('balances', 'clients.account_id', '=', 'balances.account_id')
                ->groupBy('clients.account_id', 'dc.date')
                ->whereBetween('dc.date', [$start, $end])
                ->where('clients.agencyid', $agency->agencyid)
                ->get([
                    'clients.agencyid as media_id',
                    'clients.account_id as target_acountid',
                    'dc.date',
                    DB::raw("- cast(sum({$prefix}dc.total_revenue) as decimal(10,2)) as amount"),
                    DB::raw("- cast(sum({$prefix}dc.total_revenue_gift) as decimal(10,2)) as gift"),
                    DB::raw("{$prefix}balances.balance + {$prefix}balances.gift as balance")
                ]);
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $createTime = date('Y-m-d 00:00:00', strtotime('1 day', strtotime($row->date)));
                $bl = DB::table('balance_log')
                    ->where('target_acountid', $row->target_acountid)
                    ->where('pay_type', BalanceLog::PAY_TYPE_ON_SPENDING)
                    ->where('balance_type', BalanceLog::BALANCE_TYPE_NOT_SPECIFIED)
                    ->where('create_time', $createTime)
                    ->first();
                if ($bl) {
                    if ($bl->amount != $row->amount) {
                        DB::table('balance_log')->where('id', $bl->id)->update(['amount'=>$row->amount]);
                    }

                    if ($bl->gift != $row->gift) {
                        DB::table('balance_log')->where('id', $bl->id)->update(['gift'=>$row->gift]);
                    }

                } else {
                    DB::table('balance_log')->insert([
                        'media_id' => $row->media_id,
                        'operator_accountid' => 0,
                        'operator_userid' => 0,
                        'target_acountid' => $row->target_acountid,
                        'amount' => $row->amount,
                        'gift' => $row->gift,
                        'pay_type' => BalanceLog::PAY_TYPE_ON_SPENDING,
                        'balance' => isset($row->balance) ? $row->balance : 0,
                        'balance_type' => BalanceLog::BALANCE_TYPE_NOT_SPECIFIED,
                        'comment' => date("Y-m-d 投放支出", strtotime($row->date)),
                        'create_time' => $createTime
                    ]);
                }
            }
        }
    }
}
