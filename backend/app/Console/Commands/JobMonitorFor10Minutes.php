<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Components\Helper\EmailHelper;
use App\Components\Config;
use Illuminate\Support\Facades\Redis;
use App\Models\Banner;
use App\Models\Affiliate;
use Qiniu\json_decode;
use App\Services\CampaignService;

class JobMonitorFor10Minutes extends Command
{
    private $redis;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_monitor_for_10_minutes';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '现网数据监控项，每10分钟检测一次';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->redis = Redis::connection('default');
        parent::__construct();
    }
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //监控广告和广告位关联，监控up_ad_zone_assoc
        $this->monitorBannerRelation();
        //监控up_banners表的download_url中是否存在aid=0
        $this->monitorBannerDownloadUrl();
        //监控pctr
        $this->monitorPctr();
        
    }
    
    
    private function monitorBannerRelation()
    {
        $result = CampaignService::getAttachRelationBanners();
        if (!empty($result)) {
            $newBanner = json_encode($result);
            $oldBanner = $this->redis->get('banner_relation');
            if ($oldBanner != $newBanner) {
                
                foreach ($result as $item) {
                    $this->notice('fix job: attach banner relation '.$item['bannerid']);
                    CampaignService::attachBannerRelationChain($item['bannerid']);
                }
                                
                $this->redis->set('banner_relation', $newBanner);
                $mail = [];
                $mail['subject'] = "没有创建广告位关联关系的广告";
                $mail['msg']['data'] = $result;
            
                $mailAddress = explode(';', Config::get('biddingos.monitor_banner_relation'));
                EmailHelper::sendEmail('emails.monitor.attachRelation', $mail, $mailAddress);
            }
        }
    }
    
    /**
     * 监控下载地址
     */
    private function monitorBannerDownloadUrl()
    {
        $row = DB::table('banners AS b')
                ->leftJoin('affiliates AS af', function ($join) {
                    $join->on('b.affiliateid', '=', 'af.affiliateid');
                })
                ->leftJoin('campaigns AS c', function ($join) {
                    $join->on('c.campaignid', '=', 'b.campaignid');
                })
                ->leftJoin('appinfos AS a', function ($join) {
                    $join->on('c.campaignname', '=', 'a.app_id')
                         ->on('c.platform', '=', 'a.platform');
                })
                ->where('b.status', '=', Banner::STATUS_PUT_IN)
                ->where('af.mode', '!=', Affiliate::MODE_ARTIFICIAL_DELIVERY)
                ->where('b.download_url', 'LIKE', '%aid=0')
                ->select(
                    'b.bannerid',
                    'b.download_url',
                    'c.campaignid',
                    'a.app_name',
                    'af.affiliateid',
                    'af.name'
                )
                ->get();
        if (!empty($row)) {
            $oldRow = $this->redis->get('banner_download_url');
            $newRow = json_encode($row);
            if ($oldRow != $newRow) {
                $this->redis->set('banner_download_url', $newRow);
                $data = json_decode($newRow, true);
                $mail = [];
                $mail['subject'] = "广告主下载连接地址为aid=0的广告";
                $mail['msg']['data'] = $data;
                $mailAddress = explode(';', Config::get('biddingos.monitor_download_url'));
                EmailHelper::sendEmail('emails.monitor.monitorDownloadUrl', $mail, $mailAddress);
            }
        }
    }
    
    //监控 pctr
    private function monitorPctr()
    {
        $rows = DB::table("banners AS b")
                ->join("campaigns AS c", function ($join) {
                    $join->on('b.campaignid', '=', 'c.campaignid');
                })
                ->leftJoin('affiliates AS af', function ($join) {
                    $join->on('b.affiliateid', '=', 'af.affiliateid');
                })
                ->leftJoin('appinfos AS a', function ($join) {
                    $join->on('c.campaignname', '=', 'a.app_id')
                    ->on('c.platform', '=', 'a.platform');
                })
                ->select(
                    'b.bannerid',
                    'c.campaignid',
                    'a.app_name',
                    'af.affiliateid',
                    'af.name'
                )
                ->where('b.status', 0)
                ->where('c.status', 0)
                ->get();

        $data = [];
        if (!empty($rows)) {
            $rows = json_decode(json_encode($rows), true);
            //连接到 redis 数据库获取相应 banner值
            $redisRv3 = Redis::connection('RV3');
            //$redisRv3 -> select(env('REDIS_V3_DATABASE', 3));
            foreach ($rows as $k => $v) {
                $redisValue = $redisRv3->get($v['bannerid']);
                if (null == $redisValue) {
                    $data[] = [
                        'bannerid' => $v['bannerid'],
                        'campaignid' => $v['campaignid'],
                        'app_name' => $v['app_name'],
                        'affiliateid' => $v['affiliateid'],
                        'name' => $v['name']
                    ];
                }
            }
        }
        if (!empty($data)) {
            $oldRow = $this->redis->get('banner_monitor_pctr');
            $newRow = json_encode($data);
            if ($oldRow != $newRow) {
                $this->redis->set('banner_monitor_pctr', $newRow);
                $dataRow = json_decode($newRow, true);
                $mail = [];
                $mail['subject'] = "投放中的广告在redis中找不到该key的pctr值";
                $mail['msg']['data'] = $dataRow;
                $mailAddress = explode(';', Config::get('biddingos.monitor_pctr'));
                EmailHelper::sendEmail('emails.monitor.monitorPctr', $mail, $mailAddress);
            }
        }
    }
}
