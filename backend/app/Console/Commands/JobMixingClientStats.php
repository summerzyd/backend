<?php
namespace App\Console\Commands;

use App\Models\ManualClientData;
use Illuminate\Support\Facades\DB;

class JobMixingClientStats extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_mixing_client_stats {--build-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADN 人工添加广告主数据';

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
        $time = date("Y-m-d H:i:s", time());
        //$update_day = array();
        
        //设置DB查询返回格式为数组
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        //获取人工添加广告主cpa数据的历史记录
        $rows = DB::table('manual_clientdata')
            ->where('affiliate_id', '>', 0)
            ->where('banner_id', '>', 0)
            ->where('campaign_id', '>', 0)
            ->where('flag', ManualClientData::FLAG_UNTREATED)
            ->where('cpa', '>', 0)
            ->select(DB::raw('*'), DB::raw("round((consum/cpa), 2) as price_revenue"))
            ->get();
        
        $this->notice("manual_clientdata rows return: " . count($rows));
        
        if (count($rows) > 0) {
            foreach ($rows as $row) {
//                $data = array();
//                $impressions_day = array();
//                $delivery_data_flag = array();
                
                $date = $row['date'];
                //24小时的展示量
                $impressionsRows = DB::table('data_summary_ad_hourly as ds')
                    ->join('zones as z', 'z.zoneid', '=', 'ds.zone_id')
                    ->where('ds.ad_id', $row['banner_id'])
                    ->where('z.affiliateid', $row['affiliate_id'])
                    ->whereRaw("date_time>=DATE_SUB('{$date} 16:00:00',INTERVAL 1 day)")
                    ->whereRaw("date_time<'{$date} 16:00:00'")
                    ->select('zone_id', DB::raw("sum(impressions) sum_impressions"), 'date_time')
                    ->groupBy('date_time', 'zone_id')
                    ->get();
                    
                $impressionsTotal = 0;
                // 获取展示量总数
                foreach ($impressionsRows as $val) {
                    $impressionsTotal += $val['sum_impressions'];
                }
                
                $cpa = $row['cpa'];
                if ($impressionsTotal > 0) {
                    //计算每个小时需要写入的数据
                    foreach ($impressionsRows as $im) {
                        if ($cpa <= 0) {
                            break;
                        }
                        $scale = $im['sum_impressions'] / $impressionsTotal;
                        $cpaInsert = ceil($row['cpa'] * $scale);
                        
                        if ($cpaInsert >= $cpa) {
                            $cpaInsert = $cpa > 0 ? $cpa : 0;
                        }
                        $cpa -= $cpaInsert;
                        $conSum = $row['price_revenue'] * $cpaInsert;
                        //将相关数据更新到数据库
                        $ret = DB::table('data_summary_ad_hourly')
                            ->where('date_time', $im['date_time'])
                            ->where('zone_id', $im['zone_id'])
                            ->where('ad_id', $row['banner_id'])
                            ->update([
                                'cpa' => DB::raw("cpa+{$cpaInsert}"),
                                'consum' => DB::raw("consum+{$conSum}")
                            ]);
                        
                        if ($ret) {
                            $this->notice("Update data_summary_ad_hourly
                                date_time='{$im['date_time']}'
                                zone_id={$im['zone_id']}
                                ad_id={$row['banner_id']}
                                cpa +{$cpaInsert}
                                consum +{$conSum}");
                        }
                    }
                    //更新的日志的状态
                    DB::table('manual_clientdata')->where('id', $row['id'])->update([
                        'flag' => ManualClientData::FLAG_ASSIGNED,
                        'update_time' => $time
                    ]);
                }
            }
        }
    }
}
