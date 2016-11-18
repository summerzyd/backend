<?php
namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\Banner;
use App\Models\Campaign;

class TempUpdateAppId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_app_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fix banner app_id is null';

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
        $result = \DB::table('banners AS b')
            ->leftJoin('campaigns AS c', 'b.campaignid', '=', 'c.campaignid')
            ->leftJoin('affiliates AS a', 'b.affiliateid', '=', 'a.affiliateid')
            ->select('b.bannerid', 'c.ad_type')
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->whereNull('app_id')
            ->whereIn('a.mode', [Affiliate::MODE_ARTIFICIAL_DELIVERY, Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE])
            ->get();

        foreach ($result as $item) {
            $this->info('fix banner ' . $item->bannerid);
            $banner = Banner::find($item->bannerid);

            //写入AppId
            if (empty($banner->app_id)) {
                if ($banner->affiliate()->first()->type == Affiliate::TYPE_NOT_STORAGE_QUERY) {
                    $banner->app_id = date('Hi') . str_random(8);
                }

                if (in_array($item->ad_type, [Campaign::AD_TYPE_APP_STORE])) {
                    $banner->app_id = $this->appStoreApplicationId($banner->campaignid);
                }
            }

            $banner->buildBannerText();
            $banner->save();
        }
    }

    /***
     * 查询application_id
     * @param $campaignId
     * @return mixed
     */
    private function appStoreApplicationId($campaignId)
    {
        $applicationId = Campaign::find($campaignId)->appInfo->application_id;
        return $applicationId;
    }
}
