<?php
namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\EmailHelper;

class JobRecoverCampaignWarning extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_recover_campaign_warning';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover Campaign Warning';

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
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $data = DB::table('campaigns as c')
            ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
            ->where(function ($query) {
                $query->where('c.status', Campaign::STATUS_SUSPENDED);
                $query->where('c.pause_status', Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT);
            })
            ->select('c.campaignid', 'app.app_name')
            ->get();

        $failedCampaigns = array_column($data, 'campaignid');
        $cSize = count($failedCampaigns);
        $this->notice('warning: campaigns recover faild. count= ' . $cSize . ' id=' . implode(',', $failedCampaigns));
        if ($cSize > 0) {
            $subject = "【ADN WRINING】因日限额原因暂停的应用激活失败";
            $view = 'emails.command.recoverCampaignWarning';
            $tos = [
                'kerry@iwalnuts.com',
                'binsuper@126.com',
                'fengshenhuang@biddingos.com'
            ];

            EmailHelper::sendEmail($view, [
                'subject' => $subject,
                'msg' => [
                    'campaignsName' => $cSize ? implode(',', array_column($data, 'app_name')) : '无',
                ]
            ], $tos);
        }
    }
}
