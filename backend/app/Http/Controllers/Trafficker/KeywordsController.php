<?php
namespace App\Http\Controllers\Trafficker;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AdZoneKeyword;
use App\Models\Campaign;
use App\Services\CampaignService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeywordsController extends Controller
{
    /**
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
            ], [], Campaign::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = $request->input('campaignid');
        //获取推广计划所有用户
        $createdIds = AdZoneKeyword::getCreatedId($campaignId);
        //获取所有广告主账户
        $userId = [];
        foreach ($createdIds as $cid) {
            $accountType = DB::table('users')->where('user_id', $cid)
                ->leftJoin('accounts', 'users.default_account_id', '=', 'accounts.account_id')
                ->pluck('accounts.account_type');
            if ($accountType == Account::TYPE_ADVERTISER) {
                array_push($userId, $cid['created_uid']);
            }
        }
        $keyword = [];
        $keyPrice = CampaignService::getKeyWordPriceList($campaignId, $userId);
        if (!empty($keyPrice)) {
            $keyPrice = $keyPrice[$campaignId];
            $adZoneKeyword = [];
            foreach ($keyPrice as $kw => $vw) {
                if (0 == $vw->operator) {
                    $adZoneKeyword[] = $vw;
                }
            }
            if (count($adZoneKeyword) > 0) {
                $incomeRate = (Auth::user()->account->affiliate->income_rate / 100);
                foreach ($adZoneKeyword as $key) {
                    $price = Formatter::asDecimal(
                        floor($key->price_up * $incomeRate * ($key->rate / 100) * 10) / 10,
                        1
                    );
                    $keyword[] = [
                        'id' => $key->id,
                        'campaignid' => $key->campaignid,
                        'keyword' => $key->keyword,
                        'price_up' => $price == 0 ? 0.1 : $price,
                        'rank' => $key->rank,
                        'type' => $key->type,
                    ];
                }
            }
        }
        return $this->success(null, null, $keyword);
    }


    /**
     * 修改关键字类型
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $type = ArrayHelper::getRequiredIn(AdZoneKeyword::getTypeLabels());
        if (($ret = $this->validate($request, [
                'id' => 'required',
                'type' => "required|in:{$type}",
            ], [], Campaign::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $type = $request->input('type', AdZoneKeyword::TYPE_COMPETING);

        //修改关键字类型
        AdZoneKeyword::where('id', $id)->update([
            'type' => $type,
        ]);

        return $this->success();
    }
}
