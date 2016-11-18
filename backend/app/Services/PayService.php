<?php
namespace App\Services;

use App\Components\Helper\IpHelper;
use App\Components\Helper\LogHelper;
use App\Models\Balance;
use App\Models\BalanceLog;
use App\Models\Pay;
use App\Models\PayTmp;
use App\Components\Config;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Auth;

class PayService
{
    /**
     *获取支付宝支付页面
     * @param $out
     * @return string
     */
    public static function getAlipayHtml($money, $recharge)
    {
        //向up_pay_tmp插入数据
        $codeId = date('ymdHis') . sprintf('%03d', mt_rand(0, 999)); //订单号
        $userId = Auth::user()->user_id;
        if (Auth::user()->account->isBroker()) {
            $broker = Auth::user()->account->broker;
            $agencyId = $broker->agencyid;
            $accountId = $broker->account_id;
        } else {
            $client = Auth::user()->account->client;
            $agencyId = $client->agencyid;
            $accountId = $client->account_id;
        }

        //填充PayTmp数据行
        $payTmp = new PayTmp();
        $payTmp->codeid = $codeId;
        $payTmp->operator_accountid = $accountId;
        $payTmp->operator_userid = $userId;
        $payTmp->agencyid = $agencyId;
        $payTmp->pay_type = PayTmp::PAY_TYPE_ONLINE;
        $payTmp->money = $money;
        $payTmp->ip = IpHelper::getClientIp();
        $payTmp->status = PayTmp::STATUS_RECHARGE;
        $payTmp->comment = '';

        //保存失败，返回提示
        if (!$payTmp->save()) {
            LogHelper::warning('agencyId ' . $agencyId . ' recharge ' . $recharge . ' failed');
            return 5001;
        }

        $alipayConfig = Config::get('alipayConfig');
        //$out 生成接口链接
        $out = $payTmp->chargeGetParamUnalipay(
            [
                'codeid' => $codeId,
                'money' => $money,
                'alipay_config' => $alipayConfig
            ]
        );

        $loadIcon = URL::asset('images/default/ico_load.gif');

        //自动提交给银行
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body{background:#fff;}
        .payGotos{
            padding:25px 20px;
            width:280px;
            height:20px;
            line-height:20px;
            border:1px solid #c5d0dc;
            position: absolute;left:50%;
            top:50%;
            margin-top: -35px;
            z-index: 1;
            margin-left: -160px;
            color:#000;
            font-size:14px;
        }
</style>
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$out['api_url']}" method="post">
eot;

        foreach ($out['args'] as $key => $value) {
            $html .= "<input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }

        $html .= <<<eot
</form>
<div class="payGotos">
<img width="16" src="{$loadIcon}" style="vertical-align:middle"/>&nbsp;正在为您跳转到支付页面，请稍等...
</div>
</body>
</html>
eot;
        header("Content-Type: text/html; charset=utf-8");

        return $html;
    }

    /**
     * 支付宝通知
     * @param $params
     * @return string
     */
    public static function getAlipayNotify($params)
    {
        /*校验通知是否来自支付宝服务器*/
        $ipTables = Config::get('biddingos.ipTables');
        $alipayConfig = Config::get('alipayConfig');
        /*校验通知是否来自支付宝服务器*/
        $accessIp = $ipTables['bos_alipay_access_ips'];
        $ip = IpHelper::getClientIp();
        if ($accessIp && !IpHelper::ipCheck($ip, $accessIp)) {
            LogHelper::warning('illegal ip '.$ip.' access');
            return 'fail';
        }

        $sys_file = dirname(dirname(dirname(dirname(__FILE__)))).'/Lib/Alipay/';
        include $sys_file . 'alipay_notify.class.php';

        //计算得出通知验证结果
        $alipayConfig['cacert'] = $sys_file . $alipayConfig['cacert'];
        $alipayNotify = new \AlipayNotify($alipayConfig);
        $verifyResult = $alipayNotify->verifyNotify();
        //验证成功
        if ($verifyResult) {
            //商户订单号
            $outTradeNo = $params['out_trade_no'];
            //支付宝交易号
            $tradeNo = $params['trade_no'];
            //交易状态
            $tradeStatus = $params['trade_status'];

            if ($tradeStatus == 'TRADE_FINISHED' || $tradeStatus == 'TRADE_SUCCESS') {
                $payTmp = DB::table('pay_tmp')
                    ->where('codeid', '=', $outTradeNo)
                    ->where('pay_type', '=', PayTmp::STATUS_RECHARGE);
                $payTmpRow = $payTmp->first();
                if (!empty($payTmpRow->id)) {
                    $payData = [
                        'codeid' => $outTradeNo,
                        'codepay' => $tradeNo,
                        'operator_accountid' => $payTmpRow->operator_accountid,
                        'operator_userid' => $payTmpRow->operator_userid,
                        'agencyid' => $payTmpRow->agencyid,
                        'pay_type' => Pay::STATUS_RECHARGE,
                        'money' => $payTmpRow->money,
                        'ip' => $payTmpRow->ip,
                        'create_time' => $payTmpRow->create_time,
                        'comment' => '支付宝帐号：' . $params['buyer_email']
                    ];

                    $buyer_email = $params['buyer_email'];

                    //插入pay表，删除pay_tmp表，更新balance表，插入balance_log
                    DB::beginTransaction();//事务开始
                    $Pay = Pay::create($payData);
                    if (!empty($Pay->id)) {
                        if (!$payTmp->delete()) {
                            LogHelper::warning('buyer_email:' .
                                $buyer_email . 'alipay asynchronous notification error');
                            DB::rollBack();
                            return "fail";
                        }
                        $BalanceRow = DB::table('balances')
                            ->where('account_id', '=', $payData['operator_accountid'])
                            ->first();
                        $params = [
                            'account_id' => $payData['operator_accountid'],
                            'balance' => $payData['money'],
                            'gift' => 0
                        ];
                        if ($BalanceRow->account_id > 0) {
                            $BalanceSave = Balance::updateBalance($params);
                        } else {
                            $BalanceSave = Balance::store($params);
                        }
                        if (!$BalanceSave) {
                            LogHelper::warning('buyer_email:' .
                                $buyer_email . 'alipay asynchronous notification error');
                            DB::rollBack();
                            return "fail";
                        }

                        $agencyId = $payData['agencyid'];
                        $accountId = DB::table('agency')
                            ->where('agencyid', '=', $agencyId)
                            ->select('account_id')
                            ->first();
                        $account_id = $accountId->account_id;
                        $BalanceRow = DB::table('balances')->where('account_id', '=', $account_id)->first();
                        $params = [
                            'account_id' => $account_id,
                            'balance' => $payData['money'],
                            'gift' => 0
                        ];
                        if ($BalanceRow->account_id > 0) {
                            $BalanceSave = Balance::updateBalance($params);
                        } else {
                            $BalanceSave = Balance::store($params);
                        }

                        if (!$BalanceSave) {
                            LogHelper::warning('buyer_email:' .
                                $buyer_email . 'alipay asynchronous notification error');
                            DB::rollBack();
                            return "fail";
                        }
                        $BalanceLogBalance = $BalanceRow->balance + $payData['money'];

                        $balanceLog = new BalanceLog();
                        $balanceLog->media_id = $payData['agencyid'];
                        $balanceLog->operator_accountid = $payData['operator_accountid'];
                        $balanceLog->operator_userid = $payData['operator_userid'];
                        $balanceLog->target_acountid = $payData['operator_accountid'];
                        $balanceLog->amount = $payData['money'];
                        $balanceLog->pay_type = BalanceLog::PAY_TYPE_ONLINE_RECHARGE;
                        $balanceLog->balance = $BalanceLogBalance;
                        $balanceLog->balance_type = BalanceLog::BALANCE_TYPE_GOLD_ACCOUNT;
                        $balanceLog->comment = '支付宝帐号：' . $buyer_email;
                        $balanceLog->create_time = $payData['create_time'];

                        if (!$balanceLog->save()) {
                            LogHelper::warning('buyer_email:' .
                                $buyer_email . 'alipay asynchronous notification error');
                            DB::rollBack();
                            return "fail";
                        }
                    }
                    DB::commit();//事务结束
                    return "success";
                }

                LogHelper::error('home.pay.alipayNotify : ' . var_export($_POST, true));
                return 'fail';
            }
        }

        return "fail";
    }

    /**
     * 支付宝异步回调
     * @param $params
     * @return string
     */
    public static function getAlipayReturn($params)
    {
        $sysFile = dirname(dirname(dirname(dirname(__FILE__)))) . '../Lib/Alipay/';
        include $sysFile . 'alipay_notify.class.php';

        $alipayConfig = Config::get('alipayConfig');
        //计算得出通知验证结果
        $alipayConfig['cacert'] = $sysFile . $alipayConfig['cacert'];
        $alipayNotify = new \AlipayNotify($alipayConfig);
        $verifyResult = $alipayNotify->verifyReturn();

        if ($verifyResult) {//验证成功
            $tradeStatus = $params['trade_status'];                      //交易状态
            if ($tradeStatus == 'TRADE_FINISHED' || $tradeStatus == 'TRADE_SUCCESS') {
                return 'success';
            }
        }
        return 'failed';
    }
}
