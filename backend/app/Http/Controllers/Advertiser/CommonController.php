<?php

namespace App\Http\Controllers\Advertiser;

use App\Components\Formatter;
use App\Models\AdZoneKeyword;
use App\Models\AppInfo;
use App\Models\BalanceLog;
use App\Models\BaseModel;
use App\Models\CampaignImage;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Auth;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Components\Config;
use App\Services\CampaignService;
use App\Services\AdvertiserService;
use App\Http\Controllers\Controller;

class CommonController extends Controller
{

    /**
     * 2.1 账号余额
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | balance |  | decimal | 充值余额 |  | 是 |
     * | gift |  | decimal | 赠送金余额 |  | 是 |
     */
    public function balanceValue()
    {
        $adAccountId = Auth::user()->account->account_id;//账号ID
        $client = Auth::user()->account->client;//媒体ID
        $agencyId = $client->agency->agencyid;//媒体ID
        $adBalance = Auth::user()->account->balance;//账户
        $affiliateId = $client->affiliateid;

        $balance = $adBalance ? $adBalance->balance : 0;//推广金账户余额
        $gift = $adBalance ? $adBalance->gift : 0;//赠送账户余额
        $balance = $balance + $gift;
        $curDate = date("Y-m-d");
        $prefix = DB::getTablePrefix();

        $filePath = storage_path('app/' . 'balance' . $adAccountId);
        if (file_exists($filePath) && (time() - filemtime($filePath) < 600)) {
            $oldBalance = json_decode(file_get_contents($filePath));
            $arr = explode('|', $oldBalance);
            $balance = isset($arr[0]) ? $arr[0] : 0;
            $gift = isset($arr[1]) ? $arr[1] : 0;
        } else {
            //媒体自营广告余额计算：balance_log充值金+赠送金-消耗
            if ($affiliateId > 0) {
                $charge_revenue = DB::table('balance_log')
                    ->whereIn('balance_type', [
                        BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                        BalanceLog::PAY_TYPE_OFFLINE_RECHARGE,
                        BalanceLog::PAY_TYPE_ADVERTISERS_ADVANCE,
                    ])
                    ->where('target_acountid', $adAccountId)
                    ->select(DB::raw("cast(sum({$prefix}balance_log.amount) as decimal(10,2)) as amount"))
                    ->first();
                //广告主的赠送金总额
                $charge_gift = DB::table('balance_log')
                    ->where('balance_type', BalanceLog::PAY_TYPE_PRESENT_GOLD)
                    ->where('target_acountid', $adAccountId)
                    ->select(DB::raw("cast(sum({$prefix}balance_log.amount) as decimal(10,2)) as amount"))
                    ->first();
                $res = DB::table('balance_log')
                    ->where('balance_type', BalanceLog::PAY_TYPE_ON_SPENDING)
                    ->where('target_acountid', $adAccountId)
                    ->select(
                        DB::raw("cast(sum({$prefix}balance_log.amount) as decimal(10,2)) as amount"),
                        DB::raw("cast(sum({$prefix}balance_log.gift) as decimal(10,2)) as gift")
                    )
                    ->first();
                $chargeRevenue = $charge_revenue->amount == null ? 0 : $charge_revenue->amount;
                $chargeGift = $charge_gift->amount == null ? 0 : $charge_gift->amount;
                $consume = $res->amount == null ? 0 : $res->amount;
                $consumeGift = $res->gift == null ? 0 : $res->gift;
                $balance = Formatter::asDecimal($chargeRevenue + $chargeGift + $consume);
                $gift = Formatter::asDecimal($chargeGift + $consumeGift);
            } else {
                $res = DB::table('delivery_log as dl')
                    ->leftJoin('campaigns as c', 'c.campaignid', '=', 'dl.campaignid')
                    ->leftJoin('clients as ad', 'ad.clientid', '=', 'c.clientid')
                    ->where('ad.account_id', $adAccountId)
                    ->where('dl.campaignid', '>', 0)
                    ->where('dl.zoneid', '>', 0)
                    ->where(
                        DB::raw("DATE_FORMAT(DATE_ADD({$prefix}dl.actiontime,INTERVAL 8 HOUR),'%Y-%m-%d')"),
                        $curDate
                    )
                    ->select(DB::raw("sum({$prefix}dl.price) as sum_revenue"))
                    ->addselect(DB::raw("sum({$prefix}dl.price_gift) as sum_revenue_gift"))
                    ->first();
                $curDatePrice = $res->sum_revenue == null ? 0 : $res->sum_revenue;
                $curGift = $res->sum_revenue == null ? 0 : $res->sum_revenue_gift;
                //以campaign为纬度统计每日收入展示汇总
                $ids = DB::table('campaigns')
                    ->where('clientid', 50000)
                    ->select(DB::raw("GROUP_CONCAT(campaignid) as count"))
                    ->first();
                //推广金账户余额+实时下载数金额+以campaign为纬度统计每日收入展示汇总
                if (sizeof($ids->count) > 0) {
                    $result = DB::table('data_hourly_daily as dc')
                        ->leftJoin(
                            'operation_clients as o',
                            function ($join) {
                                $join->on('o.campaign_id', '=', 'dc.campaign_id')
                                    ->on('dc.date', '=', 'o.date');
                            }
                        )
                        ->where('o.issue', 1)
                        ->whereIn('dc.campaign_id', explode(",", $ids->count))
                        ->where('dc.total_revenue', '>', 0)
                        ->select(
                            DB::raw("sum({$prefix}dc.total_revenue) as uncheck_revenue"),
                            DB::raw("sum({$prefix}dc.total_revenue_gift) as uncheck_revenue_gift")
                        )
                        ->first();
                    $unCheckRevenue = $result->uncheck_revenue == null ? 0 : $result->uncheck_revenue;
                    $unCheckGift = $result->uncheck_revenue == null ? 0 : $result->uncheck_revenue_gift;
                } else {
                    $unCheckRevenue = 0;
                    $unCheckGift = 0;
                }

                $balance = Formatter::asDecimal($balance + $curDatePrice + $unCheckRevenue);
                $gift = Formatter::asDecimal($gift + $curGift + $unCheckGift);
            }
            $newBalance = $balance . '|' . $gift;
            file_put_contents($filePath, json_encode($newBalance));
        }
        return $this->success(['balance' => $balance, 'gift' => $gift]);
    }

    /**
     * 2.2 获得所属销售顾问
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | contact_name |  | string | 联系人 |  | 是 |
     * | contact_phone |  | string | 联系电话 |  | 是 |
     * | qq |  | string | qq |  | 是 |
     * | email_address |  | string | 邮箱 |  | 是 |
     */
    public function sales()
    {
        $account = Auth::user()->account;
        $client = $account->client;
        $creatorId = $client->creator_uid;

        if (empty($creatorId)) {
            return $this->errorCode(5002);// @codeCoverageIgnore
        }

        $creator = User::find($creatorId);
        if (empty($creator)) {
            return $this->errorCode(5002);// @codeCoverageIgnore
        }
        return $this->success(
            [
            'contact_name' => $creator->contact_name,
            'contact_phone' => $creator->contact_phone,
            'qq' => $creator->qq,
            'email_address' => $creator->email_address
            ]
        );
    }
}
