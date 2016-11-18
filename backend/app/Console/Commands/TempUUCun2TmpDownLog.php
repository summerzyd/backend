<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TempUUCun2TmpDownLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uucun_2_tmp_down_log {afid} {root_dir} {file_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uucun download data reported';

    /**
     * Create a new command instance.
     *
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
//        $afid=1;
//        $root_dir = "E:/file";
//        $file_name = "20160804";

        $afid = $this->argument('afid');
        $root_dir = $this->argument('root_dir');
        $file_name = $this->argument('file_name');
        $file_name_time = strtotime(gmdate("Y-m-d H:i:s", strtotime($file_name)));
        $file_name_time_start = date("Y-m-d H:i:s", $file_name_time);
        $file_name_time_end = date("Y-m-d H:i:s", $file_name_time + 86400);

        $sql = "show TABLES like 'tmp_down_log'";
        $table = \DB::select($sql);
        if (empty($table)) {
            $sql = "create table tmp_down_log like up_down_log";
            \DB::statement($sql);
            $this->notice("create table tmp_down_log");
        }

        // get files
        $root_file_dir = File::allFiles($root_dir);
        foreach ($root_file_dir as $file) {
            if (strpos($file, $file_name) !== false) {
                $this->notice("--------- run file " . $file . " START ---------");
                // 读取文件内容信息，存入临时表中
                $handle = fopen($file, 'rb');
                while (($line = fgets($handle, 4096)) !== false) {
                    $line = trim($line);
                    $json = json_decode($line);
                    $app_id = $json->app_id;
                    $listtypeid = $json->list;
                    $position = $json->position;
                    $target_id = $json->uids;
                    $action_time = gmdate("Y-m-d H:i:s", strtotime($json->time));
                    if ($action_time < $file_name_time_start || $action_time > $file_name_time_end) {
                        $this->notice("action_time is error " . $line);
                        continue;
                    }
                    $cb = !empty($json->log_num) ? $json->log_num : "uucun" . time();
                    if (empty($app_id)) {
                        //$this->notice("app_id is null " . $line);
                        continue;
                    }

                    if ($listtypeid == '' || $position == '') {
                        //$this->notice("list or position is null " . $line);
                        continue;
                    }

                    if (empty($target_id)) {
                        //$this->notice("uids is null " . $line);
                        continue;
                    }

                    // get banner_id
                    $banner_id = DB::table('banners')->where('app_id', $app_id)
                        ->orderBy('bannerid', 'desc')->pluck('bannerid');
                    if (empty($banner_id)) {
                        //$this->notice(" app_id get banner is null " . $line);
                        continue;
                    }

                    //  get zone_id
                    $zone_row = \DB::table('zones')
                        ->where('listtypeid', $listtypeid)
                        ->where('affiliateid', $afid)
                        ->where('position', $position)->first();
                    if (empty($zone_row)) {
                        //$this->notice(" afid: $afid listtypeid: $listtypeid position:
                        // $position search zone_id is null " . $line);
                        continue;
                    }
                    $zone_id = $zone_row->zoneid;

                    $sql_price = "
                        SELECT
                            CASE WHEN b.revenue_type = 1 AND c.revenue_type = 4 THEN
                                0
                            ELSE
                                bil.revenue + IFNULL(p.price_up,0)
                            END as price,
                            c.campaignid,
                            CASE WHEN b.revenue_type = 2 AND c.revenue_type = 1 THEN
                                TRUNCATE(IFNULL(p.price_up,0) * (1 / IFNULL(e.num, 1)) + bil.af_income, 2)
                            ELSE
                                TRUNCATE(
                                    IFNULL(p.price_up,0)
                                    * IFNULL(aff.income_rate / 100, 1)
                                    * IFNULL(c.rate / 100, 1) + bil.af_income,
                                    2)
                            END AS af_income,
                            CASE WHEN b.revenue_type = 1 and c.revenue_type = 4 THEN 0 ELSE 2 END as status
                        FROM
                            up_campaigns as c
                            JOIN up_banners AS b ON b.campaignid = c.campaignid
                            JOIN up_banners_billing AS bil ON b.bannerid = bil.bannerid
                            LEFT JOIN up_ad_zone_price p ON(p.ad_id=b.bannerid AND p.zone_id = $zone_id)
                            LEFT JOIN up_affiliates aff ON(aff.affiliateid = b.affiliateid)
                            LEFT JOIN up_affiliates_extend e
                            ON (
                              b.affiliateid = e.affiliateid
                              AND b.revenue_type = e.revenue_type
                              AND c.ad_type = e.ad_type
                            )
                        WHERE
                            c.campaignid = b.campaignid
                        AND b.bannerid = '{$banner_id}' limit 1;";
                    $list = \DB::select($sql_price);
                    $row = end($list);

                    // 去重
                    $repeat_sql = "select * from tmp_down_log
                                    where campaignid = '{$row->campaignid}' and target_id = '{$target_id}'";
                    $repeat = \DB::select($repeat_sql);
                    if (!empty($repeat)) {
                        //$this->notice(" campaignid: {$row->campaignid} and target_id: {$target_id}
                        // is repeat at tmp_down_log" . $line);
                        continue;
                    }

                    // 写入数据
                    $sql = "
                    INSERT INTO `tmp_down_log`
                        (
                            `campaignid`, `zoneid`, `cb`, `price`, `actiontime`, `target_type`,
                            `target_cat`, `target_id`,`source`,`af_income`, `status`
                        )
                    VALUES
                        (
                            $row->campaignid, $zone_id, '$cb', $row->price, '$action_time', 'phone',
                            'deviceid', '$target_id', '0', '{$row->af_income}', '2'
                        )";
                    $res = \DB::statement($sql);
                    if (empty($res)) {
                        $this->notice("insert tmp down sql:" . $sql . "\n");
                    }
                }

                $this->notice("--------- run file " . $file . " END ---------");
                File::delete($file);


            }
        }
    }
}
