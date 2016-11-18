<?php

namespace App\Http\Controllers\Trafficker;

use App\Components\Formatter;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Pay;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DataHourlyDailyAf;
use Illuminate\Support\Facades\DB;
use App\Models\Recharge;
use App\Models\Gift;
use App\Models\Affiliate;
use App\Models\BalanceLog;
use App\Models\PayTmp;
use Illuminate\Support\Facades\Session;

class CommonController extends Controller
{
    /**
     * 账号余额
     *
     * @return \Illuminate\Http\Response
     */
    public function balanceValue()
    {
        $account = Auth::user()->account;
        //联盟模式
        $kind = Auth::user()->account->affiliate->kind;
        if ($kind == Affiliate::KIND_ALLIANCE) {
            //联盟媒体的推广金，把只算月结的拿掉，算时时的
            $balance = DataHourlyDailyAf::getAmount($account->account_id);
            
            //提现成功的
            $draw = DB::table('pay')
                    ->where('operator_accountid', $account->account_id)
                    ->where('pay_type', Pay::PAY_TYPE_DRAWINGS)
                    ->where('status', Pay::STATUS_APPROVED)
                    ->sum('money');
            
            //提现处理中的
            $draw_tmp = DB::table('pay_tmp')
                    ->where('operator_accountid', $account->account_id)
                    ->where('pay_type', PayTmp::PAY_TYPE_DRAWINGS)
                    ->whereIn('status', [PayTmp::STATUS_APPLICATION])
                    ->sum('money');
            
            $balance_total = $balance;
            //因为提现跟处理中的值都为负，所以直接相加
            $balance = $balance + $draw + $draw_tmp;
        } else {
            //自营的推广金为广告主的充值金
            $balance = Auth::user()->account->affiliate->self_income_amount;
            $balance_total = $balance;
        }
        return $this->success(
            [
                'balance' => Formatter::asDecimal($balance),
                'balance_total' => Formatter::asDecimal($balance_total)
            ]
        );
    }

    /**
     * 获得所属销售顾问
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function sales()
    {
        $account = Auth::user()->account;
        $affiliate = $account->affiliate;
        $creatorId = $affiliate->creator_uid;

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
    
    /**
     * 返回待审核的充值数量及赠送数量
     * @return \Illuminate\Http\Response
     */
    public function balancePendingAudit()
    {
        $agencyId = Auth::user()->agencyid;
        $affiliateid = Auth::user()->account->affiliate->affiliateid;
        $prefix = DB::getTablePrefix();
        $recharge_count =  DB::table('recharge AS r')
                    ->leftJoin(DB::raw("
                        (SELECT account_id, clientname, broker_id, affiliateid
                            FROM up_clients
                        WHERE broker_id = 0
                        UNION
                        SELECT account_id, name as clientname, brokerid, affiliateid
                        FROM up_brokers) AS {$prefix}c"), 'r.target_accountid', '=', 'c.account_id')
                    ->where('r.agencyid', $agencyId)
                    ->where('c.affiliateid', $affiliateid)
                    ->where('r.status', Recharge::STATUS_APPLYING)
                    ->count();
        
        $gift_count =   DB::table('gift AS g')
                    ->leftJoin(DB::raw("
                        (SELECT account_id, clientname, broker_id, affiliateid
                            FROM up_clients
                        WHERE broker_id = 0
                        UNION
                        SELECT account_id, name as clientname, brokerid, affiliateid
                        FROM up_brokers) AS {$prefix}c"), 'g.target_accountid', '=', 'c.account_id')
                    ->where('g.agencyid', $agencyId)
                    ->where('c.affiliateid', $affiliateid)
                    ->where('g.status', Gift::STATUS_TYPE_WAIT)
                    ->count();
        
        return $this->success(
            [
                'recharge_count' => $recharge_count,
                'gift_count' => $gift_count
            ]
        );
    }

    /**
     * 获得待审核广告数量
     *
     * @return \Illuminate\Http\Response
     */
    public function campaignPendingAudit()
    {
        $account = Auth::user()->account;
        $affiliate = $account->affiliate;
        $kind = Session::get('kind');
        $select = \DB::table('campaigns AS c')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'c.campaignid')
            ->leftJoin('affiliates AS a', 'b.affiliateid', '=', 'a.affiliateid')
            ->where('a.affiliateid', $affiliate->affiliateid);
        if ($kind == Affiliate::KIND_SELF) {
            $select->where('c.status', Campaign::STATUS_PENDING_APPROVAL);
        } else {
            $select->where('c.status', Campaign::STATUS_DELIVERING)
                ->where('b.status', Banner::STATUS_PENDING_MEDIA);
        }
        $count = $select->count(['c.campaignid']);
        return $this->success(
            [
                'count' => $count,
            ]
        );
    }

    /**
     * 获取媒体商平台
     * @return \Illuminate\Http\Response
     */
    public function platform()
    {
        if (Auth::user()->account->isAdvertiser()) {
            $affiliateId = Auth::user()->account->client->affiliateid;
        } elseif (Auth::user()->account->isTrafficker()) {
            $affiliateId = Auth::user()->account->affiliate->affiliateid;
        } elseif (Auth::user()->account->isBroker()) {
            $affiliateId = Auth::user()->account->broker->affiliateid;
        }
        $list = [];
        if (!empty($affiliateId)) {
            $affiliate = Affiliate::find($affiliateId);
            $platform = Campaign::getPlatformLabels();
            foreach ($platform as $k => $v) {
                if (($k & $affiliate->app_platform) > 0) {
                    $list[] = $k;
                }
            }
        }
        return $this->success($list);
    }
}
