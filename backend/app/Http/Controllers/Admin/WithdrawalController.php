<?php

namespace App\Http\Controllers\Admin;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Models\Affiliate;
use App\Models\Agency;
use App\Models\Broker;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\DataSummaryAdHourly;
use App\Models\Pay;
use App\Models\PayTmp;
use App\Models\Product;
use App\Models\User;
use Auth;
use App\Services\StatService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Components\Config;

class WithdrawalController extends Controller
{
    /**
     * 获取联盟平台列表
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  | |  | -status降序，降序在字段前加- | |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');

        $query = Pay::where('pay_type', Pay::PAY_TYPE_DRAWINGS)->where('status', '<>', 0);
        $query1 = PayTmp::where('pay_type', PayTmp::PAY_TYPE_DRAWINGS)->where('status', '<>', 0);
        if ($search) {
            $query = $query->where(function ($query) use ($search) {
                $query->where('money', $search)
                    ->orWhere('comment', 'like', '%' . substr(json_encode($search), 1, -1) . '%');
            });
            $query1 = $query1->where(function ($query) use ($search) {
                $query->where('money', $search)
                    ->orWhere('comment', 'like', '%' . substr(json_encode($search), 1, -1) . '%');
            });
        }
        $query = $query->select('pay_type', 'create_time', 'money', 'status', 'comment', 'operator_accountid');
        $query1 = $query1->select('pay_type', 'create_time', 'money', 'status', 'comment', 'operator_accountid');
        $data = $query1->unionAll($query->getQuery())->orderBy('create_time', 'desc')->get();

        $list = [];
        foreach ($data as $model) {
            $item = [];
            $item['pay_type'] = $model->pay_type;
            $item['create_time'] = $model->create_time->toDateTimeString();
            $item['money'] = Formatter::asDecimal($model->money);
            $item['status'] = $model->status;
            $item['name'] = isset($model->account->affiliate) ? $model->account->affiliate->name : '';

            $info = json_decode($model->comment);
            $item['bank'] = isset($info->bank) ? $info->bank : '';
            $item['payee'] = isset($info->payee) ? $info->payee : '';
            $item['bank_account'] = isset($info->bank_account) ? $info->bank_account : '';

            $list[] = $item;
        }

        return $this->success(
            null,
            [
                'count' => count($data),
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ],
            array_slice($list, ($pageNo - 1) * $pageSize, $pageSize)
        );
    }
}
