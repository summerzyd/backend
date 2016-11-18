<?php
namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\LogHelper;

class JobUpdateColumnFileClick extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_update_column_file_click  {--start-time=} {--end-time=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update column file_click.';

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
        $startTime = $this->option('start-time') ? $this->option('start-time') : '-1 hour';
        $endTime = $this->option('end-time') ? $this->option('end-time') : '-1 hour';

        $startTime = date('Y-m-d H:00:00', strtotime($startTime));
        $startTimestamp = strtotime($startTime);
        $endTime = date('Y-m-d H:59:59', strtotime($endTime));
        $endTimestamp = strtotime($endTime);
        $this->notice("StartTime: $startTime");
        $this->notice("EndTime: $endTime");
        if ($startTimestamp > $endTimestamp) {
            LogHelper::error("arg error: start-time:{$startTime},end-time:{$endTime}");
            exit();
        }
        // 计算俩日期相差的月份
        $diff_month = (date('Y', $endTimestamp) - date('Y', $startTimestamp)) * 12
            + date('n', $endTimestamp)
            - date('n', $startTimestamp);
        $sql = false;
        $data = [];
        $startMon = date('Ym', strtotime($startTime));
        $prefix = DB::getTablePrefix();
        for ($i = 0; $i <= $diff_month; $i ++) {
            $tableName = 'download_log_' . $startMon;
            $tableName = 'download_log_201607';
            $has = DB::select("show tables like '{$prefix}{$tableName}'");
            $startMon = date('Ym', strtotime('+1 month', strtotime($startMon . '01')));
            if (count($has)) {
                $query = DB::table("{$tableName} as d")
                    ->join('banners as b', 'b.bannerid', '=', 'd.ad_id')
                    ->join('affiliates as aff', 'aff.affiliateid', '=', 'b.affiliateid')
                    ->select(
                        'd.ad_id',
                        'd.zone_id',
                        DB::raw('count(1) as cnt'),
                        DB::raw("date_format(date, '%Y-%m-%d %H:00:00') as date_time")
                    )
                    ->whereRaw("agencyid = '{$agency->agencyid}'")
                    ->whereRaw("date >= '{$startTime}'")
                    ->whereRaw("date <= '{$endTime}'")
                    ->groupBy('ad_id', 'zone_id', 'date_time');
                if ($sql === false) {
                    $sql = $query;
                } else {
                    $sql->unionAll($query);
                }
            }
        }
        
        if ($sql) {
            $rawSql = $sql->toSql();
            $sql = "SELECT ad_id, zone_id, sum(cnt) as cnt, date_time 
                FROM ($rawSql) tmp
                GROUP BY ad_id, zone_id, date_time
                ORDER BY date_time";
            $data = DB::select($sql);
        }
        try {
            foreach ($data as $row) {
                $hourly = DB::table('data_summary_ad_hourly')
                    ->select('data_summary_ad_hourly_id')
                    ->where('ad_id', $row->ad_id)
                    ->where('zone_id', $row->zone_id)
                    ->where('date_time', $row->date_time)
                    ->get();
                //存在则直接更新，不存在则将数据更新到上一个时间段
                if (count($hourly) > 0) {
                    $id = $hourly[0]->data_summary_ad_hourly_id;
                    $cnt = $row->cnt;
                } else {
                    $pre_hourly = DB::table('data_summary_ad_hourly')
                        ->select('data_summary_ad_hourly_id', 'file_click')
                        ->where('ad_id', $row->ad_id)
                        ->where('zone_id', $row->zone_id)
                        ->where('date_time', '<', $row->date_time)
                        ->orderBy('date_time', 'desc')
                        ->first();
                    $id = $pre_hourly->data_summary_ad_hourly_id;
                    $cnt = $pre_hourly->file_click + $row->cnt;
                }
                DB::table('data_summary_ad_hourly')
                    ->where('data_summary_ad_hourly_id', $id)
                    ->update(['file_click' => $cnt]);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            LogHelper::error($e->getMessage());
        }
    }
}
