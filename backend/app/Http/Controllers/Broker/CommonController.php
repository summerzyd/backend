<?php

namespace App\Http\Controllers\Broker;

use App\Components\Formatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;

class CommonController extends Controller
{
    /**
     * 账号余额
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | balance |  | decimal | 充值余额 |  | 是 |
     * | gift |  | decimal | 赠送金余额 |  | 是 |
     */
    public function balanceValue()
    {
        $account = Auth::user()->account;
        $balances = $account->balance;//账户
        $balance = $balances ? $balances->balance : 0;//推广金账户余额
        $gift = $balances ? $balances->gift : 0;//赠送金账户余额

        return $this->success(
            [
                'balance' => Formatter::asDecimal($balance + $gift),
                'gift' => Formatter::asDecimal($gift),
            ]
        );
    }

    /**
     * 获得所属销售顾问
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
        $broker = $account->broker;
        $creatorId = $broker->creator_uid;

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
