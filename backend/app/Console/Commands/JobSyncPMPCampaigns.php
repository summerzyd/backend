<?php
namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Support\Facades\DB;
use App\Services\CampaignService;
use App\Models\Banner;
use App\Components\Helper\EmailHelper;
use App\Models\Campaign;
use App\Components\Config;

class JobSyncPMPCampaigns extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_sync_pmp_campaigns {--clientid=} {--agencyid=}';

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
        $clientId = $this->option('clientid') ? $this->option('clientid') : 162;
        $banners = DB::table('banners as b')
            ->join('attach_files as a', 'a.id', '=', 'b.attach_file_id')
            ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->where('c.clientid', $clientId)
            ->whereIn('b.status', array(Banner::STATUS_PUT_IN, Banner::STATUS_SUSPENDED))
            ->whereNotNull('a.package_name')
            ->select(
                'b.bannerid as id',
                'b.pause_status',
                'a.package_name',
                'b.status',
                'c.campaignid',
                'b.affiliateid'
            )
            ->get();
        
        $packages = array_fetch($banners, 'package_name');
        // $banners = array_combine($packages, $banners);
        
        $conn = DB::connection('pmp');
        $pmpCampaigns = $conn->table('campaigns as c')
            ->join('clients as s', 's.clientid', '=', 'c.clientid')
            ->join('appinfos as a', function ($join) {
                $join->on('c.campaignname', '=', 'a.app_id');
                $join->on('c.platform', '=', 'a.platform');
                $join->on('s.agencyid', '=', 'a.media_id');
            })
            ->whereIn('a.package', $packages)
            ->whereIn('c.status', array(Campaign::STATUS_DELIVERING, Campaign::STATUS_SUSPENDED))
            ->select('a.package', 'a.app_name', 'c.status', 'c.revenue', 'c.day_limit')
            ->get();
        $packages = array_fetch($pmpCampaigns, 'package');
        $pmpCampaigns = array_combine($packages, $pmpCampaigns);
        
        $tomarket = array();
        
        foreach ($banners as $ban) {
            
            $pmpCampaign = isset($pmpCampaigns[$ban->package_name]) ? $pmpCampaigns[$ban->package_name] : null;
            if (!$pmpCampaign) {
                continue;
            }
                // 加入新判断媒体暂停的时候不应该同步
            if ($pmpCampaign->status != $ban->status
                && ($ban->status != Banner::STATUS_SUSPENDED
                    || !in_array($ban->pause_status, [Banner::PAUSE_STATUS_MEDIA_MANUAL, Banner::PAUSE_STATUS_PLATFORM])
                )
            ) { // 状态不同，更新状态
                if ($ban->status == Banner::STATUS_SUSPENDED && $ban->pause_status == Banner::STATUS_SUSPENDED) {
                    Banner::where('bannerid', $ban->id)->update([
                        'pause_status' => Banner::PAUSE_STATUS_PLATFORM
                    ]);
                }
                
                $param = array();
                if ($ban->status == Banner::STATUS_PUT_IN) {
                    $param = array(
                        'pause_status' => Banner::STATUS_SUSPENDED
                    );
                }
                
                CampaignService::modifyBannerStatus($ban->id, $pmpCampaign->status, true, $param);
                
                $media = $conn->table('affiliates')->where('affiliateid', $ban->affiliateid)->pluck('name');
                $pauseStatus = DB::table('banners')->where('bannerid', $ban->id)->pluck('pause_status');
                $pauseStatusLabel = Banner::getPauseLabels($pauseStatus);
                $pauseStatusLabel = $pmpCampaign->status != 0 && $pauseStatusLabel ? $pauseStatusLabel : '';
                
                $nowStatus = Banner::getStatusLabels($pmpCampaign->status);
                $nowStatus = $nowStatus ? $nowStatus : '';
                
                $prevStatus = Banner::getStatusLabels($ban->status);
                $prevStatus = $prevStatus ? $prevStatus : '';
                
                $tomarket[] = array(
                    'media' => $media, // pmp 的affilaite.name
                    'app_name' => $pmpCampaign->app_name, // pmp 的app_name
                    'now_status' => $nowStatus, // 最新状态
                    'pause_status_text' => $pauseStatusLabel, // 暂停状态
                    'prev_status' => $prevStatus
                ); // 上次状态
            }
            Campaign::where('campaignid', $ban->campaignid)
                ->update([
                    'revenue'=>$pmpCampaign->revenue,
                    'day_limit'=>$pmpCampaign->day_limit
                ]);
        }
        
        // 每次有状态变化就发送邮件给市场部 冯深皇
        $to_email = Config::get('biddingos.job.jobSyncPMPCampaigns', $agency->agencyid);
        if ($tomarket && $to_email) {
            
            $H = date('Y年m月d日H点');
            $mail = array();
            $mail['subject'] = "itools广告状态发生变化，请查收~~";
            $mail['msg']['data'] = $tomarket;
            $mail['msg']['H'] = $H;
            EmailHelper::sendEmail('emails.command.syncPMPCampaigns', $mail, $to_email);
        }
    }
}
