<?php

namespace App\Http\Controllers\Advertiser;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Models\BalanceLog;
use App\Models\Invoice;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{
    /**
     * 推广金支出
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | int | 当前页面 | 默认为1 | 否 |
     * | pageSize | int | 每页显示数 | 默认为25 | 否 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function payout(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);

        $adAccountId = Auth::user()->account->account_id;
        $agencyId = Auth::user()->account->client->agency->agencyid;
        $res = BalanceLog::where('media_id', '=', $agencyId)
            ->where('target_acountid', '=', $adAccountId)
            ->where('pay_type', BalanceLog::PAY_TYPE_ON_SPENDING)
            ->orderBy('balance_log.create_time', 'desc')
            ->select(
                'balance_log.create_time AS day_time',
                'balance_log.pay_type',
                'balance_log.amount AS money',
                'balance_log.gift',
                'balance_log.comment'
            )->get();

        //过滤输出字段
        $total = 0;
        $priceTotal = 0;
        $giftTotal = 0;
        $result = [];
        if (!empty($res)) {
            foreach ($res as $row) {
                $result[] = [
                    'day_time' => date('Y-m-d', strtotime("-1 day", strtotime($row->day_time))),
                    'action_label' => BalanceService::getActionLabel($row->pay_type),
                    'revenue' => sprintf("%.2f", $row->money),
                    'gift' => sprintf("%.2f", $row->gift),
                    'price' => sprintf("%.2f", ($row->money - $row->gift)),
                ];
                $total += sprintf("%.2f", $row->money);
                $giftTotal += sprintf("%.2f", $row->gift);
                $priceTotal += sprintf("%.2f", ($row->money - $row->gift));
            }
        }

        //获取代理商从广告主划账部分支出
        $rows = DB::table('balance_log')
            ->where('balance_log.media_id', '=', $agencyId)
            ->where('balance_log.target_acountid', '=', $adAccountId)
            ->whereIn('balance_log.pay_type', [
                BalanceLog::PAY_TYPE_GIVE_ADVERTISER_TO_BROKER,
                BalanceLog::PAY_TYPE_GOLD_ADVERTISER_TO_BROKER,
            ])
            ->whereIn('balance_log.balance_type', [
                BalanceLog::BALANCE_TYPE_GOLD_ACCOUNT,
                BalanceLog::BALANCE_TYPE_GIVE_ACCOUNT,
            ])
            ->orderBy('balance_log.create_time', 'desc')
            ->select(
                'balance_log.create_time AS day_time',
                'balance_log.pay_type AS type',
                'balance_log.amount AS money',
                'balance_log.gift',
                'balance_log.comment'
            )->get();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $comment = $row->type == BalanceLog::PAY_TYPE_GIVE_ADVERTISER_TO_BROKER ?
                    '赠送金' . $row->comment : '充值金' . $row->comment;
                $result[] = [
                    'day_time' => $row->day_time,
                    'action_label' => $comment,
                    'revenue' => sprintf("%.2f", $row->money),
                    'gift' => sprintf("%.2f", $row->gift),
                    'price' => sprintf("%.2f", ($row->money - $row->gift)),
                ];
                $total += sprintf("%.2f", $row->money);
                $giftTotal += sprintf("%.2f", $row->gift);
                $priceTotal += sprintf("%.2f", ($row->money - $row->gift));
            }
        }
        $obj = [
            'total' => sprintf("%.2f", $total),
            'giftTotal' => sprintf("%.2f", $giftTotal),
            'priceTotal' => sprintf("%.2f", $priceTotal)
        ];
        $data = ArrayHelper::arraySort($result, 'day_time', 'desc');

        return $this->success(
            $obj,
            [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => count($result),
            ],
            array_slice($data, ($pageNo - 1) * $pageSize, $pageSize)
        );
    }

    /**
     * 充值明细
     *
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | int | 当前页面 | 默认为1 | 否 |
     * | pageSize | int | 每页显示数 | 默认为25 | 否 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function recharge(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);

        $adAccountId = Auth::user()->account->account_id;
        $agencyId = Auth::user()->account->client->agency->agencyid;
        $rows = DB::table('balance_log')
            ->leftjoin('users', 'users.user_id', '=', 'balance_log.operator_userid')
            ->where('balance_log.amount', '>', 0)
            ->where('balance_log.media_id', '=', $agencyId)
            ->where('balance_log.target_acountid', '=', $adAccountId)
            ->orderBy('balance_log.create_time', 'desc')
            ->select(
                'balance_log.id',
                'balance_log.pay_type',
                'balance_log.create_time',
                'balance_log.amount',
                'users.contact_name',
                'balance_log.comment',
                'balance_log.balance_type'
            )
            ->get();

        $result = $this->getRecharge($rows, $pageNo, $pageSize);

        return $this->success(
            ['total' => $result['total']],
            [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => $result['count'],
            ],
            $result['result']
        );
    }

    private function getRecharge($rows, $pageNo, $pageSize)
    {
        //过滤输出字段
        $total = 0;
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => $row->id,
                'create_time' => $row->create_time,
                'action_label' => BalanceService::getActionLabel($row->pay_type, $row->balance_type),
                'type_label' => BalanceService::getActionLabel($row->pay_type),
                'amount' => '+' . $row->amount,
                'comment' => $row->comment,
                'contact_name' => $row->contact_name,
            ];
            $total += $row->amount;
        }
        $count = count($result);
        return ['count' => $count, 'total' => $total,
            'result' => array_slice($result, ($pageNo - 1) * $pageSize, $pageSize)];
    }


    /**
     * 媒体自运营充值明细与赠送明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | int | 当前页面 | 默认为1 | 否 |
     * | pageSize | int | 每页显示数 | 默认为25 | 否 |
     * | type | tinyint | 查询类型 | recharge充值记录 gift赠送记录| 否 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     *
     */
    public function selfRecharge(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $type = $request->input('type', 'recharge');
        $adAccountId = Auth::user()->account->account_id;
        $agencyId = Auth::user()->account->client->agency->agencyid;
        //默认为充值金明细
        $payType = [
            BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
            BalanceLog::PAY_TYPE_OFFLINE_RECHARGE,
            BalanceLog::PAY_TYPE_GOLD_BROKER_TO_ADVERTISER
        ];

        //如果不是充值金明细，则查询赠送金明细
        if ('recharge' != $type) {
            $payType = [
                BalanceLog::PAY_TYPE_PRESENT_GOLD,
                BalanceLog::PAY_TYPE_GIVE_BROKER_TO_ADVERTISER,
            ];
        }
        $rows = DB::table('balance_log')
            ->leftjoin('users', 'users.user_id', '=', 'balance_log.operator_userid')
            ->whereIn('balance_log.pay_type', $payType)
            ->where('balance_log.media_id', '=', $agencyId)
            ->where('balance_log.target_acountid', '=', $adAccountId)
            ->orderBy('balance_log.create_time', 'desc')
            ->select(
                'balance_log.id',
                'balance_log.pay_type',
                'balance_log.create_time',
                'balance_log.amount',
                'users.contact_name',
                'balance_log.comment',
                'balance_log.balance_type'
            )
            ->get();

        $result = $this->getRecharge($rows, $pageNo, $pageSize);

        return $this->success(
            ['total' => $result['total']],
            [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => $result['count'],
            ],
            $result['result']
        );
    }

    /**
     * 申请发票
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | int | 当前页面 | 默认为1 | 否 |
     * | pageSize | int | 每页显示数 | 默认为25 | 否 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function rechargeInvoice(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);

        $adAccountId = Auth::user()->account->account_id;
        $agencyId   = Auth::user()->account->client->agency->agencyid;

        $rows = DB::table('balance_log')
            ->leftjoin(
                'invoice_balance_log_assoc',
                'invoice_balance_log_assoc.balance_log_id',
                '=',
                'balance_log.id'
            )
            ->whereIn('balance_log.pay_type', [
                BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                BalanceLog::PAY_TYPE_OFFLINE_RECHARGE,
            ])//线上和线下充值
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
            )
            ->get();

        //过滤输出字段
        $total = 0;
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => $row->id,
                'create_time' => $row->create_time,
                'action_label' => BalanceService::getActionLabel($row->pay_type, $row->balance_type),
                'amount' => $row->amount,
                'comment' => $row->comment,
                'status' => $row->status,
                'status_label' => BalanceLog::getInvoiceStatusLabel($row->status),
            ];
            $total += $row->amount;
        }

        $obj = ['total' => $total,];

        return $this->success(
            $obj,
            [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => count($result),
            ],
            array_slice($result, ($pageNo - 1) * $pageSize, $pageSize)
        );
    }

    /**
     * 发票申请记录
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | int | 当前页面 | 默认为1 | 否 |
     * | pageSize | int | 每页显示数 | 默认为25 | 否 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function invoiceHistory(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);

        $adAccountId = Auth::user()->account->account_id;
        $agencyId   = Auth::user()->account->client->agency->agencyid;

        $rows = Invoice::where('account_id', '=', $adAccountId)
            ->where('agencyid', '=', $agencyId)
            ->orderBy('create_time', 'desc')
            ->get();

        //过滤输出字段
        $result = [];
        foreach ($rows as $row) {
            $c = json_decode($row->address);
            $result[] = [
                'invoice_id' => $row->id,
                'create_time' => $row->create_time->toDateTimeString(),
                'money' => Formatter::asDecimal($row->money),
                'title' => $row->title,
                'receiver' => $row->receiver,
                'address' => BalanceService::getAddress($c->prov, $c->city, $c->dist, $c->addr),
                'status' => $row->status,
                'status_label' => Invoice::getStatusLabel($row->status),
                'comment' => $row->comment,
            ];
        }

        return $this->success(
            null,
            [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => count($result),
            ],
            array_slice($result, ($pageNo - 1) * $pageSize, $pageSize)
        );
    }

    /**
     * 获取发票明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | invoice_id | int | 发票ID | 获取发票明细 | 是 |
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function invoice(Request $request)
    {
        if (($ret = $this->validate($request, [
                'invoice_id' => 'required|numeric'
            ], [], Invoice::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id =  $request->input('invoice_id');

        $result = BalanceService::getInvoice($id);

        return $this->success(null, null, $result);
    }
}
