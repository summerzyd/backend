<?php
namespace App\Console\Commands;

use App\Models\Balance;
use App\Models\Client;
use App\Models\OperationLog;
use App\Services\CampaignService;
use App\Models\Campaign;
use App\Components\Helper\LogHelper;

class JobRecoverCampaign extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_recover_campaign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover Campaign';

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
        //启动日预算暂停
        $this->recoverCampaign(Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT, 6008);
        $this->recoverCampaign(Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM, 6051);

        //查询余额大于0的广告主，并且启动广告
        $account = Balance::whereRaw("(balance+gift) > 0")
            ->join('clients', 'balances.account_id', '=', 'clients.account_id')
            ->leftJoin('campaigns', 'campaigns.clientid', '=', 'clients.clientid')
            ->where('campaigns.status', Campaign::STATUS_SUSPENDED)
            ->where('campaigns.pause_status', Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH)
            ->where('clients.clients_status', Client::STATUS_ENABLE)
            ->distinct()
            ->get(['clients.clientid']);

        foreach ($account as $item) {
            $this->notice('start clientid ' . $item->clientid . ' balance not enough campaign');
            CampaignService::recoverActive(Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH, $item->clientid);
        }
    }

    private function recoverCampaign($pause_status, $code)
    {
        $campaignIds = CampaignService::recoverActive($pause_status);
        LogHelper::notice('job_recover_campaign End: status=1, campaigndid=(' . implode(',', $campaignIds) . ')');
        //因达到日限额暂停重启的广告
        if (!empty($campaignIds)) {
            foreach ($campaignIds as $k => $v) {
                $message = CampaignService::formatWaring($code);
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $v,
                    'type' => OperationLog::TYPE_SYSTEM,
                    'message' => $message,
                ]);
            }
        }
    }
}
