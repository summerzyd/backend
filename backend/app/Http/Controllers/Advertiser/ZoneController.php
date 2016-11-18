<?php
namespace App\Http\Controllers\Advertiser;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Zone;
use App\Services\ZoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ZoneController extends Controller
{
    /**
     * 获取广告位加价信息
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | campaignid | integer | 推广计划ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 广告位加价ID |  | 是 |
     * | zoneid |  | integer | 广告位ID |  | 是 |
     * | zonename |  | string | 广告位名称 |  | 是 |
     * | description |  | string | 示意图 |  | 是 |
     * | impressions |  | integer | 曝光 |  | 是 |
     * | price_up |  | decimal | 加价金额 |  | 是 |
     * | rank |  | int | 等级 |  | 是 |
     */
    public function index(Request $request)
    {
        $campaignId = $request->input('campaignid');
        //获取当前用户所属媒体ID
        $affiliateId = Auth::user()->account->client->affiliateid;

        //新建推广计划曝光及竞争力,价格都为0
        if (empty($campaignId)) {
            \DB::setFetchMode(\PDO::FETCH_ASSOC);
            $list = \DB::table('zones')
                ->where('affiliateid', $affiliateId)
                ->where('status', Zone::STATUS_OPEN_IN)
                ->where('type', '<>', Zone::TYPE_FLOW)
                ->select(
                    'zoneid',
                    'zonename',
                    'description',
                    'platform',
                    \DB::raw('0 AS impressions'),
                    \DB::raw('0 AS price_up'),
                    \DB::raw('0 AS rank')
                )
                ->get();
        } else {
            \DB::setFetchMode(\PDO::FETCH_ASSOC);
            $list = ZoneService::getZonesList($campaignId);
        }
        return $this->success(null, null, $list);
    }
}
