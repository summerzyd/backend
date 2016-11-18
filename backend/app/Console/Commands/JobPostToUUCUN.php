<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class JobPostToUUCUN extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_post_to_uucun {--afid=} {--begin-date=} {--end-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'post data to uucun';

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
        $afid = $this->option('afid');
        if (! $afid) {
            exit('--afid=null');
        }
        $beginDate = $this->option('begin-date') ? $this->option('begin-date') : date("Y-m-d", strtotime("-1 day"));
        $endDate = $this->option('end-date') ? $this->option('end-date') : $beginDate;
        
        // UTC时间转换
        $beginTime = date('Y-m-d', strtotime('-1 day', strtotime($beginDate))) . ' 16:00:00';
        $endTime = $endDate . ' 15:59:59';
        
        $prefix = DB::getTablePrefix();
        // up_zones.type == 3 表示自然量下载
        $rows = DB::table('delivery_log as dl')->join('zones as z', 'z.zoneid', '=', 'dl.zoneid')
            ->join('banners as b', function ($join) {
                $join->on('dl.campaignid', '=', 'b.campaignid')
                    ->on('z.affiliateid', '=', 'b.affiliateid');
            })
            ->where('z.affiliateid', $afid)
            ->where('dl.actiontime', '>=', $beginTime)
            ->where('dl.actiontime', '<', $endTime)
            ->select(
                DB::raw("DATE_FORMAT(DATE_ADD({$prefix}dl.actiontime,INTERVAL 8 HOUR),'%Y%m%d') AS rpdt"),
                'b.app_id AS id',
                DB::raw("sum({$prefix}dl.af_income) AS num"),
                'dl.channel AS apkid',
                DB::raw("CASE {$prefix}z.type WHEN 3 THEN 1 ELSE 0 END AS refer")
            )
            ->groupBy('rpdt', 'id', 'apkid')
            ->get();
        
        if (! empty($rows)) {
            $url = 'http://lzh-newp-cms.plat88.com/syncHarvest.do';
            // $url = 'http://localhost/t/uucunSyncHarvest.php';
            foreach ($rows as $data) {
                $dataarr = array(
                    'rpdt' => $data->rpdt,
                    'id' => $data->id,
                    'num' => $data->num,
                    'apkid' => $data->apkid,
                    'refer' => $data->refer
                );
                
                $args = array();
                
                array_walk($dataarr, function ($val, $key) use (&$args) {
                    $args[] = $key . '=' . $val;
                });
                
                
                $posturl = $url . '?' . join('&', $args);
                
                $res = $this->httpRequest($posturl, 'get');
            }
        }
    }

    protected function httpRequest($url, $type = 'post', $post_data = '', $timeout = 10)
    {
        $this->notice($type . ' URL:' . $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ('post' == $type) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($post_data != '') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $file_contents = curl_exec($ch);
        curl_close($ch);
        
        $this->notice('Return:' . $file_contents);
        // TODO：：失败判断失败重连
        return $file_contents;
    }
}
