<?php
/**
 * Created by PhpStorm.
 * User: a2htray
 * Date: 2016/1/31
 * Time: 10:53
 */

namespace App\Http\Controllers\Advertiser;

use Illuminate\Http\Request;
use Auth;
use App\Http\Controllers\Controller;
use App\Models\BalanceLog;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    /**
     * 4.4 提交开票申请
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | ids |  | string | id字符串  |  | 是 |
     * | title |  | string | 抬头 |  | 是 |
     * | prov |  | string | 省 |  | 是 |
     * | city |  | string | 市 |  | 是 |
     * | dist |  | string | 区 |  | 是 |
     * | address |  | string | 地址 |  | 是 |
     * | type |  | integer | 发票类型 |  | 是 |
     * | receiver |  | string | 收件人 |  | 是 |
     * | tel |  | string | 手机号 |  | 是 |
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'ids' => 'required|string',
                'title' => 'required|string',
                'prov' => 'required|string',
                'city' => 'required|string',
                'dist' => 'string',
                'address' => 'required|string',
                'type' => 'required|numeric|in:0,2',
                'receiver' => 'required|string',
                'tel' => 'required|numeric',
            ], [], BalanceLog::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();

        $ret = InvoiceService::invoiceStore($params);
        if ($ret !== true) {
            return $this->errorCode($ret);
        }

        return $this->success();
    }
}
