<?php
namespace App\Services;

use App\Models\Banner;
use Illuminate\Support\Facades\DB;

class BannerService
{
    /**
     * 启动因日限额暂停的媒体广告
     * @param $campaignId
     */
    public static function recoverBanner($campaignId)
    {
        $prefix = DB::getTablePrefix();
        \DB::setFetchMode(\PDO::FETCH_ASSOC);
        $banners = DB::table('banners AS b')
            ->join('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->join('zones AS z', 'z.affiliateid', '=', 'b.affiliateid')
            ->join('delivery_log AS dl', function ($join) {
                $join->on('dl.campaignid', '=', 'b.campaignid')
                    ->on('z.zoneid', '=', 'dl.zoneid');
            })
            ->select(
                'b.af_day_limit',
                'b.bannerid',
                'c.day_limit',
                DB::raw("sum({$prefix}dl.price) AS price")
            )
            ->where('b.campaignid', $campaignId)
            ->where('b.status', Banner::STATUS_SUSPENDED)
            ->where('b.pause_status', Banner::PAUSE_STATUS_EXCEED_DAY_LIMIT)
            ->whereRaw("{$prefix}dl.actiontime >= DATE_SUB(CURDATE(), interval 8 hour)")
            ->whereRaw("{$prefix}dl.actiontime < date_add(DATE_SUB(CURDATE(), interval 8 hour), interval 1 day)")
            ->groupBy('b.bannerid')
            ->get();
        \DB::setFetchMode(\PDO::FETCH_CLASS);
        foreach ($banners as $item) {
            if ($item['day_limit'] > 0 && $item['af_day_limit'] == 0
                && $item['day_limit'] > $item['price']
            ) {
                CampaignService::modifyBannerStatus(
                    $item['bannerid'],
                    Banner::STATUS_PUT_IN,
                    true,
                    ['pause_status' => Banner::PAUSE_STATUS_MEDIA_MANUAL]
                );
            }
            if ($item['af_day_limit'] > 0 && $item['af_day_limit'] > $item['price']) {
                CampaignService::modifyBannerStatus(
                    $item['bannerid'],
                    Banner::STATUS_PUT_IN,
                    true,
                    ['pause_status' => Banner::PAUSE_STATUS_MEDIA_MANUAL]
                );
            }
        }
    }
}
