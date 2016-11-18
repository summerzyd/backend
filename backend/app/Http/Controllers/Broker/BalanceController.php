<?php
namespace App\Http\Controllers\Broker;

use App\Components\Formatter;
use App\Http\Controllers\Controller;
use App\Models\BalanceLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\BalanceService;
use App\Services\InvoiceService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{
    /**
     * 代理商充值账户明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | day_time | string | 操作时间 |  | 是 |
     * | type | integer | 类型 |  | 是 |
     * | type_label | string | 类型标签 | 划出，划入，充值 | 是 |
     * | money | string | 发生金额 |  | 是 |
     * | comment | string | 交易来源 |  | 是 |
     * | contact_name | string | 操作员 |  | 是 |
     * | income_total | string | 收入 | obj字段（收入） | 是 |
     * | pay_total | string | 支出 | obj字段 | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function recharge(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));

        list($obj, $map, $result) = BalanceService::getBrokerBalance($pageNo, $pageSize, 'recharge');
        return $this->success($obj, $map, $result);
    }

    /**
     * 代理商赠送金账户
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | day_time | string | 操作时间 |  | 是 |
     * | type | integer | 类型 |  | 是 |
     * | type_label | string | 类型标签 | 划出，划入，赠送 | 是 |
     * | money | string | 发生金额 |  | 是 |
     * | comment | string | 交易来源 |  | 是 |
     * | contact_name | string | 操作员 |  | 是 |
     * | income_total | string | 收入 | obj字段（收入） | 是 |
     * | pay_total | string | 支出 | obj字段 | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function gift(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));

        list($obj, $map, $result) = BalanceService::getBrokerBalance($pageNo, $pageSize, 'gift');
        return $this->success($obj, $map, $result);
    }

    /**
     * 代理商发票申请记录
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | day_time | string | 操作时间 |  | 是 |
     * | money | string | 发生金额 |  | 是 |
     * | title | string | 发票抬头 |  | 是 |
     * | receiver | string | 收件人 |  | 是 |
     * | address | string | 地址 |  | 是 |
     * | status | string | 状态 |  | 是 |
     * | status_label | string | 状态标签 |  | 是 |
     * | comment | string | 状态详细描述 |  | 是 |
     * | invoice_id | integer | 发票申请id |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function invoiceHistory(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));

        $adAccountId = Auth::user()->account->account_id;
        $agencyId = Auth::user()->account->broker->agency->agencyid;

        $select = Invoice::where('account_id', '=', $adAccountId)
            ->where('agencyid', '=', $agencyId)
            ->orderBy('create_time', 'desc');

        $count = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $rows = $select->skip($offset)->take($pageSize)->get();

        //过滤输出字段
        $result = [];
        foreach ($rows as $row) {
            $c = json_decode($row->address);
            $result[] = [
                'invoice_id' => $row->id,
                'day_time' => $row->create_time,
                'money' => Formatter::asDecimal($row->money),
                'title' => $row->title,
                'type' => $row->invoice_type,
                'type_label' => Invoice::getInvoiceTypeLabel($row->invoice_type),
                'receiver' => $row->receiver,
                'address' => BalanceService::getAddress($c->prov, $c->city, $c->dist, $c->addr),
                'status' => $row->status,
                'status_label' => Invoice::getStatusLabel($row->status),
                'comment' => $row->comment,
            ];
        }

        //发票明细中不需要使用汇总数
        $map = [
            'pageSize' => $pageSize,
            'pageNo' => $pageNo,
            'count' => $count,
        ];

        return $this->success(null, $map, $result);
    }

    /**
     * 提交发票申请
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | name | type | description | restraint | required |
     * | address | string | 地址 |  | 是 |
     * | city | string | 市 |  | 是 |
     * | dist | string | 区 |  | 否 |
     * | ids | string  | id字符串  |  用逗号隔开 | 是  |
     * | prov | string | 省 |  | 是 |
     * | receiver | string | 收件人 |  | 是 |
     * | tel | string | 手机号 |  | 是 |
     * | title | string | 抬头 |  | 是 |
     * | type | int | 发票类型 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function invoiceStore(Request $request)
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
            ], [], BalanceLog::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();

        $ret = InvoiceService::invoiceStore($params);
        if ($ret !== true) {
            return $this->errorCode($ret);
        }

        return $this->success();
    }

    /**
     * 获取发票明细
     * | name | type | description | restraint | required | |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | invoice_id | int | 发票明细Id |  |  | |
     *
     * @param Request $request
     *
     * | name | type | description | restraint | required |
     * | create_time | string | 日期(到h:m:s) |  | 是 |
     * | amount | string | 金额(小数点两位) |  | 是 |
     * | pay_type_label | string | 支付类型 |  | 是 |
     * | invoice_id |  int | 发票明细Id |  | 是 |
     * | balance_log_id | int | 财务记录表id |  | 是 |
     *
     * @return \Illuminate\Http\Response
     */
    public function invoice(Request $request)
    {
        if (($ret = $this->validate($request, [
                'invoice_id' => 'required|integer',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $id = $request->input('invoice_id');

        $result = BalanceService::getInvoice($id);

        return $this->success(null, null, $result);
    }

    /**
     * 代理商充值账户申请明细
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | day_time | string | 操作时间 |  | 是 |
     * | type | integer | 类型 |  | 是 |
     * | type_label | string | 类型标签 | 划出，划入，充值 | 是 |
     * | money | string | 发生金额 |  | 是 |
     * | status | integer | 发票申请状态 |  | 是 |
     * | status_label | string | 状态标签 |  | 是 |
     * | id | integer | 财务记录表中标识 |  | 是 |
     *
     */
    public function apply(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));

        $adAccountId = Auth::user()->account->account_id;
        $agencyId = Auth::user()->account->broker->agencyid;

        $select = DB::table('balance_log')
            ->leftjoin('invoice_balance_log_assoc', 'invoice_balance_log_assoc.balance_log_id', '=', 'balance_log.id')
            ->whereIn('balance_log.pay_type', [
                BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                BalanceLog::PAY_TYPE_OFFLINE_RECHARGE,
            ])
            ->where(
                'balance_log.balance_type',
                '=',
                BalanceLog::BALANCE_TYPE_GOLD_ACCOUNT
            )//推广金账户
            ->where('balance_log.media_id', '=', $agencyId)
            ->where('balance_log.target_acountid', '=', $adAccountId)
            ->orderBy('balance_log.create_time', 'desc')
            ->groupBy('balance_log.id')
            ->select(
                'balance_log.id',
                'balance_log.create_time',
                'balance_log.pay_type',
                'balance_log.balance_type',
                'balance_log.amount',
                'balance_log.comment',
                DB::raw('max(up_invoice_balance_log_assoc.status) status')
            );

        $count = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $rows = $select->skip($offset)->take($pageSize)->get();

        //过滤输出字段
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => $row->id,
                'day_time' => $row->create_time,
                'type' => $row->pay_type,
                'type_label' => BalanceLog::getPayTypeLabel($row->pay_type),
                'money' => $row->amount,
                'status' => $row->status,
                'status_label' => BalanceLog::getInvoiceStatusLabel($row->status),
            ];
        }

        $map = [
            'pageSize' => $pageSize,
            'pageNo' => $pageNo,
            'count' => $count,
        ];

        return $this->success(null, $map, $result);
    }
}
