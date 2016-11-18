<?php
namespace App\Console\Commands;

use App\Models\AppInfo;
use App\Models\Campaign;
use App\Services\CampaignService;

class TempUpdateAppStoreInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_appstore_info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $result = \DB::table('campaigns AS c')
            ->leftJoin('appinfos AS a', 'a.app_id', '=', 'c.campaignname')
            ->where('c.ad_type', Campaign::AD_TYPE_APP_STORE)
            ->select('a.app_id', 'application_id')
            ->get();
        foreach ($result as $item) {
            if (!empty($item->application_id)) {
                $info = CampaignService::getAppIdInfo($item->application_id);
                AppInfo::where('app_id', $item->app_id)->update([
                    'appstore_info' => $info
                ]);
                $this->notice('app_id ' . $item->app_id . ' update appstore_info ' . $info);
            }
        }
    }
}
