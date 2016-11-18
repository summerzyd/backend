<?php
namespace App\Console\Commands;

use App\Components\Formatter;
use App\Models\Agency;
use App\Models\ManualDeliveryData;
use Illuminate\Support\Facades\DB;

class JobRepairDeliveryData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_repair_delivery_data {--force=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        $forceDate = $this->option('force') ? $this->option('force') : null;
        $updateDay = array(); // 需要更新的日期，要去重
        
        $manualTable = 'manual_deliverydata';
        $deliveryLogSource = 1;
        // 查询出人工投放数据时需要修复的数据及相应的account_id
        $current = date("Y-m-d H:i:s", time());
        $prefix = DB::getTablePrefix();
        $rows = DB::table("{$manualTable} as m")
            ->join('campaigns as cam', 'cam.campaignid', '=', 'm.campaign_id')
            ->join('clients as c', 'c.clientid', '=', 'cam.clientid')
            ->where('c.agencyid', $agency->agencyid)
            ->where('m.zone_id', '>', 0)
            ->where('m.banner_id', '>', 0)
            ->where('m.campaign_id', '>', 0)
            ->where('m.repair_type', '>', 0)
            ->where('m.views', '>', 0)
            ->where('m.repair_status', 0)
            ->where('m.flag', ManualDeliveryData::FLAG_ASSIGNED)
            ->select(
                'm.*',
                'c.account_id',
                'c.clientname',
                DB::raw("CASE {$prefix}m.conversions
                WHEN 0 THEN round(({$prefix}m.revenues/{$prefix}m.clicks), 2)
                ELSE round(({$prefix}m.revenues/{$prefix}m.conversions), 2)
                END as price_revenue"),
                DB::raw("CASE {$prefix}m.conversions
                WHEN 0 THEN round(({$prefix}m.expense/{$prefix}m.clicks), 2)
                ELSE round(({$prefix}m.expense/{$prefix}m.conversions), 2)
                END as price_income"),
                DB::raw("CASE {$prefix}m.conversions
                WHEN 0 THEN 'click' ELSE 'down'
                END as source_log_type"),
                DB::raw("CASE {$prefix}m.conversions
                WHEN 0 THEN {$prefix}m.clicks ELSE {$prefix}m.conversions
                END as all_count")
            )
            ->get();
        $this->notice("`$manualTable` rows return: " . count($rows));
        if (count($rows) > 0) {
            foreach ($rows as $v) {
                // eqq（广点通）的数据修复只修复7+1天范围内第一天的数据，
                // 往后都是这样一天天的递归（例如1-7号的数据如果有发生变化，
                // 那么8号修复1号数据，2号数据到9号才修复）
                $eqqDate = date('Y-m-d', time() - 8 * 86400);
                if ($v->affiliate_id == 65 && $v->zone_id == 78) {
                    if (isset($forceDate) && $forceDate == $v->date) {
                        // do the follow
                    } elseif ($eqqDate != $v->date) {
                        continue;
                    }
                }
                $dateFrom = date("Y-m-d H:i:s", strtotime('-8 hour', strtotime($v->date)));
                $dateTo = date("Y-m-d H:i:s", strtotime('+16 hour', strtotime($v->date)));

                $result = \DB::table('delivery_manual_log')
                    ->where('zoneid', $v->zone_id)
                    ->where('bannerid', $v->banner_id)
                    ->whereBetween('actiontime', [$dateFrom, $dateTo])
                    ->select(
                        "amount AS num",
                        "price AS s_price",
                        "af_income AS s_expense"
                    )
                    ->first();
                //只处理数据不一致的情况
                if ($result && $result->num != $v->all_count) {
                    $num = $v->all_count - $result->num;
                    $expense = 0;
                    $revenue = 0;
                    //$num > 0, 少录
                    if ($num > 0) {
                        $revenue = Formatter::asDecimal($result->s_price / abs($result->num) * $num);
                    }

                    if ($num < 0) {
                        $revenue = - Formatter::asDecimal($result->s_price / abs($result->num) * $num);
                    }
                    $expense = $v->expense - $result->s_expense;

                    // 修复原因
                    $source_comment = array(
                        'source' => 'job:RepairDeliverydata',
                        'source_table' => $prefix . $manualTable,
                        'id' => $v
                    );
                    if ($v->data_type == 'A2D-AF' || $v->data_type == 'A2C-AF') {
                        $v->source_log_type = $v->data_type;
                    }
                    //数据只插入delivery_repair_log表，不做其他处理
                    DB::table('delivery_repair_log')->insert(array(
                        'campaignid' => $v->campaign_id,
                        'bannerid' => $v->banner_id,
                        'zoneid' => $v->zone_id,
                        'source' => $deliveryLogSource,
                        'amount' => $num,
                        'amount_type' => $v->source_log_type,
                        'expense' => $expense,
                        'revenue' => $revenue,
                        'source_comment' => json_encode($source_comment),
                        'created_time' => date("Y-m-d H:i:s")
                    ));
                }
                
                // 修改人工投放修复的数据的状态
                DB::table($manualTable)->where('id', $v->id)->update(['repair_status' => 1]);
                $updateDay[$v->date] = $v->date;
            }
        }
    }
}
