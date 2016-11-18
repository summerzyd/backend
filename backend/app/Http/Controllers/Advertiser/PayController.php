<?php

namespace App\Http\Controllers\Advertiser;

use App\Models\PayTmp;
use App\Models\PromotionActivity;
use App\Services\PayService;
use Illuminate\Http\Request;
use Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Invoice;
use App\Components\Config;

class PayController extends Controller
{
    /**
     * 4.3 获取支付活动列表
     *
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 活动id |  | 是 |
     * | image_url |  | string | 活动图片地址 |  | 是 |
     * | title |  | string | 活动标题 |  | 是 |
     * | start_date |  | datetime | 活动开始时间 |  | 是 |
     * | end_date |  | datetime | 活动结束时间 |  | 是 |
     * | content |  | string | 活动内容描述 |  | 是 |
     * | status |  | tinyint | 活动状态码 |  | 是 |
     * | status_label |  | string | 状态描述 |  | 是 |
     */
    public function activity(Request $request)
    {
        //获取广告主所属媒体商的优惠活动
        $creatorUid = Auth::user()->account->client->creator_uid;
        $creatorAccountId = User::where('user_id', '=', $creatorUid)->select('default_account_id')->get()->toArray();

        //查不到用户时返回
        if (count($creatorAccountId) == 0) {
            return $this->errorCode(5002);//@codeCoverageIgnore
        }

        $operatorAccountId = $creatorAccountId[0]['default_account_id'];

        $activity = PromotionActivity::getActivity($operatorAccountId, PromotionActivity::STATUS_PUBLISH);

        return $this->success($activity, null, null);
    }

    /**
     * 4.5 取广告主的收件信息
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | address |  | string | 地址 |  | 是 |
     * | receiver |  | string | 收件人 |  | 是 |
     * | prov |  | string | 省 |  | 是 |
     * | city |  | string | 市 |  | 是 |
     * | dist |  | string | 区 |  | 是 |
     * | tel |  | string | 手机号 |  | 是 |
     */
    public function receiverInfo(Request $request)
    {
        $adAccountId = Auth::user()->account->account_id;
        $result = Invoice::receiverInfo($adAccountId);
        return $this->success($result);
    }

    /**
     * 4.6 提交到支付宝
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | money |  | decimal | 金额 |  | 是 |
     * | recharge |  | int | 金额类型 |  | 是 |
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function store(Request $request)
    {
        //参数不正确，返回5000
        if (($ret = $this->validate($request, [
                'money' => 'numeric|min:1',
                'recharge' => 'required|numeric|min:1|max:4'
            ], [], PayTmp::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //获取配置金额
        $recharges = Config::get('biddingos.recharges');
        //确定充值金额
        $recharge = $request->recharge;
        $money = isset($recharges[$recharge]) ? $recharges[$recharge] : $request->money;

        $html = PayService::getAlipayHtml($money, $recharge);
        if ($html === 5001) {
            return $this->errorCode($html);
        }
        return $html;
    }

    /**
     * 支付宝异步通知
     * @codeCoverageIgnore
     */
    public function alipayNotify(Request $request)
    {
        $params = $request->all();
        $result = PayService::getAlipayNotify($params);
        return $result;
    }

    /**
     * 支付宝同步通知
     *
     * @return Response
     * @codeCoverageIgnore
     */
    public function alipayReturn(Request $request)
    {
        $params = $request->all();
        $result = PayService::getAlipayReturn($params);
        return $result;
    }
}
