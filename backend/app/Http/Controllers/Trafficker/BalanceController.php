<?php

namespace App\Http\Controllers\Trafficker;

use App\Components\Formatter;
use App\Models\Balance;
use App\Models\BalanceLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Gift;
use App\Models\OperationLog;
use App\Models\Pay;
use App\Models\PayTmp;
use App\Models\Recharge;
use App\Services\BalanceService;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Auth;
use App\Http\Controllers\Controller;
use App\Models\DataHourlyDailyAf;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use App\Components\Config;

class BalanceController extends Controller
{
    /**
     * 提款明细
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function withdraw(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);

        $accountId = Auth::user()->account->account_id;
        $query = Pay::where('operator_accountid', $accountId)
            ->select('pay_type', 'create_time', 'money', 'status', 'comment', 'operator_userid');
        $data = PayTmp::where('operator_accountid', $accountId)
            ->select('pay_type', 'create_time', 'money', 'status', 'comment', 'operator_userid')
            ->unionAll($query->getQuery())
            ->orderBy('create_time', 'desc')
            ->get();

        $list = [];
        foreach ($data as $model) {
            $item = [];
            $item['pay_type'] = $model->pay_type;
            $item['pay_type_label'] = PayTmp::getPayTmpTypeLabel($model->pay_type);
            $item['create_time'] = $model->create_time->toDateTimeString();
            $item['money'] = Formatter::asDecimal($model->money);
            $item['status'] = $model->status;
            $item['status_label'] = Pay::getPayStatusLabel($model->status);
            $item['contact_name'] = $model->user->contact_name;

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

    /**
     * 结算明细
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function settlement(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);

        $accountId = Auth::user()->account->account_id;
        $prefix = DB::getTablePrefix();
        $select = DB::table('data_hourly_daily_af as daf')
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'daf.affiliateid')
            ->where('aff.account_id', $accountId)
            ->select(
                //'daf.date as day_time',
                DB::raw("DATE_FORMAT({$prefix}daf.`date`,'%Y-%m') AS day_time"),
                DB::raw("SUM({$prefix}daf.af_income) as amount"),
                DB::raw("SUM({$prefix}daf.total_revenue) as blance")
            )
            ->groupBy('day_time')
            ->orderBy('day_time', 'desc');

        // 分页
        $count = count($select->distinct('day_time')->get());
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        $rows = $select->get();
        $list = [];
        $total = 0;//总金额
        foreach ($rows as $row) {
            $item['day_time'] = $row->day_time;
            $item['amount'] = '+' . Formatter::asDecimal($row->amount);
            $item['blance'] = Formatter::asDecimal($row->blance);
            $item['type'] = DataHourlyDailyAf::TYPE_ADVERTISER;
            $item['type_label'] = DataHourlyDailyAf::getTypeLabels(DataHourlyDailyAf::TYPE_ADVERTISER);
            $list[] = $item;
            $total = $total + doubleval(Formatter::asDecimal($row->amount));
        }

        return $this->success(
            ['total' => '+' . Formatter::asDecimal($total)],
            [
                'count' => $count,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ],
            $list
        );
    }

    /**
     * 收入明细
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function income(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);

        $accountId = Auth::user()->account->account_id;
        $prefix = DB::getTablePrefix();
        $rows = DB::table('data_hourly_daily_af as daf')
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'daf.affiliateid')
            ->where('aff.account_id', $accountId)
            ->select('daf.date as day_time', DB::raw("SUM({$prefix}daf.af_income) as amount"))
            ->groupBy('daf.date')
            ->orderBy('daf.date')
            ->get();

        // 分页
        $count = count($rows);
        $offset = (intval($pageNo) - 1) * intval($pageSize);

        $data = array_slice($rows, $offset, $pageSize);
        $list = [];
        foreach ($data as $row) {
            $item['day_time'] = $row->day_time;
            $item['amount'] = '+' . Formatter::asDecimal($row->amount);
            $item['type'] = DataHourlyDailyAf::TYPE_ADVERTISER;
            $item['type_label'] = DataHourlyDailyAf::getTypeLabels(DataHourlyDailyAf::TYPE_ADVERTISER);
            $list[] = $item;
        }

        $amount = DataHourlyDailyAf::getAmount($accountId);

        return $this->success(
            [
                'amount' => Formatter::asDecimal($amount),
            ],
            [
                'count' => $count,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ],
            $list
        );
    }

    /**
     * 收入明细
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function drawBalance(Request $request)
    {
        $accountId = Auth::user()->account->account_id;
        $balance = Balance::where('account_id', $accountId)->sum('balance');
        $balance += PayTmp::where(
            [
                'operator_accountid' => $accountId,
                'pay_type' => PayTmp::PAY_TYPE_DRAWINGS,
                'status' => PayTmp::STATUS_APPLICATION,
            ]
        )->sum('money');

        return $this->success(
            [
                'balance' => $balance,
            ],
            null,
            null
        );
    }

    /**
     * 收入明细
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function draw(Request $request)
    {
        $accountId = Auth::user()->account->account_id;
        $balance = Balance::where('account_id', $accountId)->sum('balance');
        $agencyId = Auth::user()->account->affiliate->agency->agencyid;

        //判断输入是否合法
        if (($ret = $this->validate($request, [
            'payee' => 'required',
            'bank' => 'required',
            'bank_account' => 'required',
            'money' => 'required|numeric|min:1|max:' . $balance
        ], [], PayTmp::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $money = $request->input('money');
        $comment = json_encode([
            'payee' => $request->input('payee'),
            'bank' => $request->input('bank'),
            'bank_account' => $request->input('bank_account'),
        ]);

        $payTmp = new PayTmp([
            'codeid' => '',
            'operator_accountid' => Auth::user()->account->account_id,
            'operator_userid' => Auth::user()->user_id,
            'agencyid' => $agencyId,
            'pay_type' => PayTmp::PAY_TYPE_DRAWINGS,
            'money' => -$money,
            'ip' => $request->ip(),
            'status' => PayTmp::STATUS_APPLICATION,
            'comment' => $comment,
        ]);

        if (!$payTmp->save()) {
            return $this->errorCode(5001);
        }

        return $this->success();
    }


    public function rechargeIndex(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $search = $request->input('search');
        $prefix = DB::getTablePrefix();
        $agencyId = Auth::user()->agencyid;
        $affiliateid = Auth::user()->account->affiliate->affiliateid;
        $select =  DB::table('recharge AS r')
            ->leftJoin('users AS u', 'r.user_id', '=', 'u.user_id')
            ->leftJoin(DB::raw("(SELECT account_id, clientname, affiliateid, IF (up_clients.broker_id = 0, 0, 1)
                     AS client_type FROM up_clients UNION SELECT account_id, name AS clientname,
                     affiliateid, 2 FROM up_brokers)
                     AS {$prefix}c"), 'c.account_id', '=', 'r.target_accountid')
            ->where('r.agencyid', $agencyId)
            ->where('c.affiliateid', $affiliateid);
        if (!empty($search)) {
            $select = $select->where("c.clientname", "like", "%{$search}%")
                             ->orWhere("u.contact_name", "like", "%{$search}%");
        }

        $select = $select->select(
            'r.id',
            'r.apply_time',
            'u.contact_name',
            'c.clientname',
            'r.amount',
            'r.way',
            'r.account_info',
            'r.date',
            'r.status'
        );
        $count  =  $select->count();

        if (!empty($search)) {
            //如果有搜索的才会执行
            $totalSelect = $select->get();
        }

        $offset =  (intval($pageNo) - 1) * intval($pageSize);
        $select->orderBy(DB::raw($prefix.'r.apply_time'), 'DESC');
        $select -> skip($offset)->take($pageSize);
        $list  =   $select ->get();

        //数据汇总
        $rechargeObj = DB::table('recharge AS r')
            ->leftJoin(DB::raw("(SELECT account_id, clientname, affiliateid, IF (up_clients.broker_id = 0, 0, 1)
                     AS client_type FROM up_clients UNION SELECT account_id, name AS clientname,
                     affiliateid, 2 FROM up_brokers)
                     AS {$prefix}c"), 'r.target_accountid', '=', 'c.account_id')
            ->where('r.status', Recharge::STATUS_APPROVED)
            ->where('r.agencyid', $agencyId)
            ->where('c.affiliateid', $affiliateid);
        if (!empty($search)) {
            if (!empty($totalSelect)) {
                $ids = [];
                foreach ($totalSelect as $k => $v) {
                    $ids[] = $v->id;
                }
                $rechargeObj->whereIn('id', $ids);
            }
        }
        $total = $rechargeObj->sum('amount');
        return $this->success(
            ['total' => Formatter::asDecimal($total)],
            [
                'count' => $count,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ],
            $list
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * 通过审核，则更新广告主的推广金，驳回则修改状态
     */
    public function rechargeUpdate(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required'
            ], [], Recharge::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $id = $request->input('id');
        $value = $request->input('value');
        $field = $request->input('field');
        $content = $request->input('content');
        if ('status' == $field) {
            if (Recharge::STATUS_APPROVED == $value) {
                //通过审核
                $row = BalanceService::getOneRow($id);
                if (!empty($row)) {
                    //较验状态是否为待审核状态，
                    //联盟平台的较验的已经在下面封装的代码中处理了
                    if (Recharge::STATUS_APPLYING != $row->status) {
                        return $this->errorCode(5006);
                    }
                } else {
                    return $this->errorCode(5002);
                }

                $result = BalanceService::rechargeApproved($id, $field, $affiliateId);
                if (!$result) {
                    return $this->errorCode(5001);
                }
            } else {
                //更新状态
                $prefix = DB::getTablePrefix();
                $checkRow = Recharge::leftJoin(
                    DB::raw("(SELECT  affiliateid, account_id FROM up_clients
                    UNION
                    SELECT affiliateid,account_id FROM up_brokers)
                     AS {$prefix}c"),
                    'recharge.target_accountid',
                    '=',
                    'c.account_id'
                )
                    ->where('c.affiliateid', $affiliateId)
                    ->where('recharge.id', $id)
                    ->first();
                //没有操作权限
                if (empty($checkRow)) {
                    return $this->errorCode(5004);
                }

                $ret = BalanceService::saveCharge($id, $content);
                if (!$ret) {
                    return $this->errorCode($ret);
                }
            }
        }
        return $this->success();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function giftIndex(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $search = $request->input('search');
        $prefix = DB::getTablePrefix();
        $agencyId = Auth::user()->agencyid;
        $affiliateid = Auth::user()->account->affiliate->affiliateid;
        $select = DB::table('gift AS g')
            ->leftJoin('users AS u', 'g.user_id', '=', 'u.user_id')
            ->leftJoin(
                DB::raw("
                        (SELECT account_id, clientname, broker_id, affiliateid
                            FROM up_clients
                        WHERE broker_id = 0
                    UNION
                        SELECT account_id, name as clientname, brokerid, affiliateid FROM up_brokers) AS {$prefix}c"),
                'g.target_accountid',
                '=',
                'c.account_id'
            )
            ->where('g.agencyid', $agencyId)
            ->where('c.affiliateid', $affiliateid);
        if (!empty($search)) {
            $select = $select->where('c.clientname', 'like', "%{$search}%")
                      ->orWhere("u.contact_name", "like", "%{$search}%");
        }

        $select = $select->select(
            'g.id',
            'g.created_at',
            'u.contact_name',
            'c.clientname',
            'g.amount',
            'g.gift_info',
            'g.status'
        );

        $count = $select->count();
        if (!empty($search)) {
            //如果有搜索的才会执行
            $totalSelect = $select->get();
        }
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->orderBy(DB::raw($prefix . 'g.created_at'), 'DESC');
        $select->skip($offset)->take($pageSize);
        $list = $select->get();
        //数据汇总
        $invoiceObj = DB::table('gift AS g')
                    ->leftJoin(DB::raw("
                        (SELECT account_id, clientname, broker_id, affiliateid
                            FROM up_clients
                        WHERE broker_id = 0
                        UNION
                        SELECT account_id, name as clientname, brokerid, affiliateid
                        FROM up_brokers) AS {$prefix}c"), 'g.target_accountid', '=', 'c.account_id')
                    ->where('g.status', Gift::STATUS_TYPE_PASSED)
                    ->where('g.agencyid', $agencyId)
                    ->where('c.affiliateid', $affiliateid);
        if (!empty($search)) {
            if (!empty($totalSelect)) {
                $ids = [];
                foreach ($totalSelect as $k => $v) {
                    $ids[] = $v->id;
                }
                $invoiceObj->whereIn('id', $ids);
            }
        }
        $total = $invoiceObj->sum('amount');
        return $this->success(
            ['total' => Formatter::asDecimal($total)],
            [
                'count' => $count,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ],
            $list
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     *
     */
    public function giftUpdate(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required'
            ], [], Gift::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $field = $request->input('field');
        $value = $request->input('value');
        $id = $request->input('id');
        $affiliateid = Auth::user()->account->affiliate->affiliateid;
        if ('status' == $field) {
            if (!in_array($value, [Gift::STATUS_TYPE_PASSED, Gift::STATUS_TYPE_REJECTED])) {
                return $this->errorCode(5000);
            }

            $gift = Gift::find(intval($id));
            if (empty($gift)) {
                return $this->errorCode(5000);
            }

            if ($gift->agencyid != Auth::user()->agencyid) {
                return $this->errorCode(4001);
            }
            $prefix = DB::getTablePrefix();
            $checkRow = Gift::leftJoin(
                DB::raw("
                        (SELECT account_id, clientname, broker_id, affiliateid
                            FROM up_clients
                        WHERE broker_id = 0
                        UNION
                        SELECT account_id, name as clientname, brokerid, affiliateid
                        FROM up_brokers) AS {$prefix}c"),
                'gift.target_accountid',
                '=',
                'c.account_id'
            )
            ->where('c.affiliateid', $affiliateid)
            ->where('gift.id', $id)
            ->first();
            if (empty($checkRow)) {
                return $this->errorCode(5000);
            }
            $objectParam = [
                'id' => $id,
                'gift' => $gift,
                'field' => $field,
                'value' => $value,
                'request' => $request,
                'affiliateid' => $affiliateid,
            ];
            $giftTransaction = DB::transaction(function ($objectParam) use ($objectParam) {
                DB::beginTransaction();
                extract($objectParam);
                //如果通过审核
                if ($value == Gift::STATUS_TYPE_PASSED) {
                    $gift->$field = $value;
                    if ($gift->save()) {
                        $result = BalanceService::updateGift($gift);
                        if (true == $result) {
                            DB::commit();
                            return true;
                        } else {
                            DB::rollback();
                            return false;
                        }
                    } else {//end save
                        DB::rollback();
                        return false;
                    }
                } else {
                    //更新状态
                    $prefix = DB::getTablePrefix();
                    $checkRow = Gift::leftJoin(
                        DB::raw("
                        (SELECT account_id, clientname, broker_id, affiliateid
                            FROM up_clients
                        WHERE broker_id = 0
                        UNION
                        SELECT account_id, name as clientname, brokerid, affiliateid
                        FROM up_brokers) AS {$prefix}c"),
                        'gift.target_accountid',
                        '=',
                        'c.account_id'
                    )
                    ->where('c.affiliateid', $affiliateid)
                    ->where('gift.id', $id)
                    ->first();
                    //没有操作权限
                    if (empty($checkRow)) {
                        return false;
                    }

                    //更新状态
                    $save = DB::table("gift")
                            ->where("id", $id)
                            ->update(["status"=>3, "comment" => '媒体商驳回']);
                    DB::commit();
                    return true;
                }
            });

            if (true == $giftTransaction) {
                return $this->errorCode(0);
            } else {
                return $this->errorCode(5001);
            }
        }
    }
}
