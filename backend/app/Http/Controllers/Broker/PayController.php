<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use App\Models\PayTmp;
use App\Services\PayService;
use Auth;
use Illuminate\Http\Request;
use App\Components\Config;

class PayController extends Controller
{
    /**
     * 提交到支付宝
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | money |  | decimal | 金额 |  | 是 |
     * | recharge |  | int | 金额类型 |  | 是 |
     * @param Request $request
     * @return string
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
        $recharge = $request->input('recharge');
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
     * 支付宝回调
     * @param Request $request
     * @return string
     * @codeCoverageIgnore
     */
    public function alipayReturn(Request $request)
    {
        $params = $request->all();
        $result = PayService::getAlipayReturn($params);
        return $result;
    }
}
