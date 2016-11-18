<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

//应用排行统计汇总
class JobRanking extends Command
{

    protected $signature = 'job_ranking    {--build-date=}';
    protected $description = 'Command for jobRanking.';


    public function __construct()
    {
        parent::__construct();
    }

    
    public function handle()
    {
        $current = $this->option('build-date') ? $this->option('build-date') : date('Y-m-d');
        $yesterday = date('Y-m-d', (strtotime($current)-24*60*60));
        $dateArr =  [$yesterday, $current];
        $prefix = DB::getTablePrefix();

        foreach ($dateArr as $k => $date) {
            $total_revenue    =  0;
            $total_impressions = 0;
            $total_conversions = 0;
            
            $otherRevenue = 0;
            $otherImpressions = 0;
            $otherConversions = 0;
            //先统计汇总出最近前10的消费记录
            $rows = DB::table('data_hourly_daily AS dc')
                    ->leftJoin('campaigns AS c', 'dc.campaign_id', '=', 'c.campaignid')
                    ->leftJoin('appinfos AS a', function ($join) {
                        $join->on('c.campaignname', '=', 'a.app_id')
                             ->on('c.platform', '=', 'a.platform');
                    })
                    ->where('dc.date', $date)
                    ->select(
                        'a.app_name',
                        'a.app_show_name',
                        'c.revenue',
                        'dc.campaign_id',
                        DB::raw("SUM({$prefix}dc.total_revenue + {$prefix}dc.total_revenue_gift) AS total_revenue,
                        SUM({$prefix}dc.impressions) AS total_impressions,
                        SUM({$prefix}dc.conversions) AS total_conversions
                        ")
                    )
                    ->groupBy('dc.campaign_id')
                    ->orderBy('total_revenue', 'DESC')
                    ->take(10)
                    ->get();
                    
            if (!empty($rows)) {
                foreach ($rows as $k => $v) {
                    $app_name = !empty($v->app_name) ? $v->app_name : $v->app_show_name;
                    $newData = [
                       $date,
                       $v->campaign_id,
                       $app_name,
                       $v->revenue,
                       $v->total_revenue,
                       $v->total_impressions,
                       $v->total_conversions,
                       1,
                       $app_name,
                       $v->revenue,
                       $v->total_revenue,
                       $v->total_impressions,
                       $v->total_conversions
                    ];
                    $total_revenue += $v->total_revenue;
                    $total_impressions += $v->total_impressions;
                    $total_conversions += $v->total_conversions;
                    $this->update($newData);
                }
            }
           
           //获取总收入
            $data =  DB::table('data_hourly_daily')
                    ->where('date', $date)
                    ->select(
                        DB::raw("SUM(total_revenue + total_revenue_gift) AS total_revenue,
                            SUM(impressions) AS total_impressions,
                            SUM(conversions) AS total_conversions
                        ")
                    )
                    ->first();

            if (!empty($data)) {
                $revenue = 0;
                $otherRevenue =  $data->total_revenue - $total_revenue;
                $otherImpressions=   $data->total_impressions - $total_impressions;
                $otherConversions=   $data->total_conversions - $total_conversions;
            }
           
            $newData    = [
               $date,
               0,
               '其它',
               $revenue,
               $otherRevenue,
               $otherImpressions,
               $otherConversions,
               0,
               '其它',
               $revenue,
               $otherRevenue,
               $otherImpressions,
               $otherConversions
            ];
            $this->update($newData);
        }
    }

    /**
     * 插入数据
     */
    private function update($newData)
    {
        $fields    =  [
            '`day_time`',
            '`campaignid`',
            '`campaign_name`',
            '`revenue`',
            '`income`',
            '`show`',
            '`download`',
            '`is_show`'
        ];
        $arr_keys=    array_keys($fields);
        $strArr    =    array();
        foreach ($arr_keys as $k => $v) {
            $strArr[]    =    '?';
        }
                
        $sql = "INSERT INTO up_manager_statistics_ranking(".implode(",", $fields).")
                VALUES (".implode(",", $strArr).")";
        $sql .= "ON DUPLICATE KEY UPDATE `campaign_name` = ?,`revenue` = ?, `income` = ?,`show` = ?,`download` = ?;";
        DB::update($sql, $newData);
    }
}
