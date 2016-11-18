<?php
namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Models\AdZoneKeyword;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KeywordsController extends Controller
{
    /**
     * 代理商查看关键字
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 关键字ID |  | 是 |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | keyword |  | string | 关键字ID |  | 是 |
     * | price_up |  | decimal | 加价金额 |  | 是 |
     * | rank |  | integer | 竞争力 |  | 是 |
     */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
            ], [], AdZoneKeyword::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = $request->input('campaignid');
        $campaign = Campaign::where('campaignid', $campaignId)
            ->first();
        if (!$campaign) {
            return $this->errorCode(5001);// @codeCoverageIgnore
        }

        //非当前代理商广告主不能查看
        $client = Client::find($campaign->clientid)->toArray();
        if ($client['broker_id'] != Auth::user()->account->broker->brokerid) {
            return $this->errorCode(5003);// @codeCoverageIgnore
        }

        $account = DB::table('campaigns')
            ->where('campaignid', $campaignId)
            ->leftJoin('clients', 'campaigns.clientid', '=', 'clients.clientid')
            ->leftJoin('accounts', 'clients.account_id', '=', 'accounts.account_id')
            ->select('accounts.account_id')->first();
        //获取所有用户
        $userId = User::where('default_account_id', $account->account_id)
            ->select('user_id')
            ->get()
            ->toArray();

        $list = [];
        if ($campaign) {
            $result = CampaignService::getKeyWordPriceList($campaignId, $userId);
            $result = $result[$campaignId];
            foreach ($result as $row) {
                $list[] = array(
                    'id' => $row->id,
                    'campaignid' => $row->campaignid,
                    'keyword' => $row->keyword,
                    'price_up' => $row->price_up,
                    'rank' => $row->rank,
                );
            }
        }
        return $this->success(null, null, $list);
    }
}
