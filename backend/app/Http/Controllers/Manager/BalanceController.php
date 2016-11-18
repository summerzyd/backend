<?php

namespace App\Http\Controllers\Manager;

use App\Components\Formatter;
use App\Models\Account;
use App\Models\Affiliate;
use App\Models\Balance;
use App\Models\BalanceLog;
use App\Models\Client;
use App\Models\OperationLog;
use App\Models\Pay;
use App\Models\PayTmp;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Auth;
use App\Http\Controllers\Controller;
use App\Models\Recharge;
use Illuminate\Support\Facades\DB;
use App\Services\CampaignService;
use App\Models\Campaign;
use App\Models\Invoice;
use App\Models\Gift;
use App\Components\Config;

class BalanceController extends Controller
{

    /**
     * 广告主，代理商充值申请列表
     *
     * | name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | integer | 请求页数，默认1 | 否 | |
     * | pageSize | integer | 请求每页数量，默认25 | 否 | |
     * | search | string | 搜索关键字，默认空 | 否 | |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  |  | | -status降序，降序在字段前加- | 否 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 自增 |  | 是 |
     * | apply_time | string | 申请时间 |  | 是 |
     * | contact_name | string | 申请人 |  | 是 |
     * | clientname | string | 充值对象 |  | 是 |
     * | client_type | integer | 类型 | 0 直客广告主 1 代理获客广告主  2 代理商 | 是 |
     * | amount | decimal | 充值金额 |  | 是 |
     * | way | integer | 广告主充值方式 |  0支付宝 1对公银行 | 是 |
     * | account_info | string | 广告主充值账号 |  | 是 |
     * | date | date | 广告主充值日期 |  | 是 |
     * | status | integer | 状态 | 1-待审核  2-审核通过 3-驳回 | 是 |
     * | total | decimal | 汇总总金额 | obj字段 | 是 |
     *
     */
    public function rechargeIndex(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $search = $request->input('search');
        $prefix = DB::getTablePrefix();
        $agencyId = Auth::user()->agencyid;
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        $select =  DB::table('recharge AS r')
            ->leftJoin('users AS u', 'r.user_id', '=', 'u.user_id')
            ->leftJoin(DB::raw("(SELECT account_id, clientname, affiliateid, IF (up_clients.broker_id = 0, 0, 1)
                     AS client_type FROM up_clients UNION SELECT account_id, name AS clientname, affiliateid, 2
                     FROM up_brokers)AS {$prefix}c"), 'c.account_id', '=', 'r.target_accountid')
            ->where('r.agencyid', $agencyId)
            ->where('c.affiliateid', $defaultAffiliateid); //只表出affiliateid为0（不是自营的广告主）
        if (!empty($search)) {
            $select = $select->where("c.clientname", "like", "%{$search}%")
                ->orWhere("u.contact_name", "like", "%{$search}%");
        }

        $select = $select->select(
            'r.id',
            'r.apply_time',
            'u.contact_name',
            'c.clientname',
            'c.client_type',
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
        $rechargeObj = DB::table('recharge')
            ->leftJoin('clients', 'recharge.target_accountid', '=', 'clients.account_id')
            ->where('recharge.status', Recharge::STATUS_APPROVED)
            ->where('clients.affiliateid', $defaultAffiliateid)
            ->where('recharge.agencyid', $agencyId);
        if (!empty($search)) {
            if (!empty($totalSelect)) {
                $ids = [];
                foreach ($totalSelect as $k => $v) {
                    $ids[] = $v->id;
                }
                $rechargeObj->whereIn('recharge.id', $ids);
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
     * 广告主代理商/充值更新
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 记录ID |  | 是 |
     * | field  | string | 状态值 | status   | 是 |
     * | value | integer | 2-审核通过 3-驳回 |  | 是 |
     * | content | text | 驳回原因 | 驳回原因 | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    
    public function rechargeUpdate(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required'
            ], [], Recharge::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $value = $request->input('value');
        $field = $request->input('field');
        $content = $request->input('content');
        if ('status' == $field) {
            if (Recharge::STATUS_APPROVED == $value) {
                //通过审核
                $row = BalanceService::getOneRow($id);
                if (!empty($row)) {
                    //联盟平台的较验的已经在下面封装的代码中处理了
                    if (Recharge::STATUS_APPLYING != $row->status) {
                        return $this->errorCode(5006);
                    }
                } else {
                    return $this->errorCode(5002);
                }

                $result = BalanceService::rechargeApproved($id, $field);
                if (!$result) {
                    return $this->errorCode(5001);
                }
            } else {
                //驳回
                $ret = BalanceService::saveCharge($id, $content);
                if ($ret !== true) {
                    return $this->errorCode($ret);
                }
            }
        }
        return $this->success();
    }



    /**
     * 广告主代理商/发票申请列表
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  |  |  | -status降序，降序在字段前加- | 否 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 自增 |  | 是 |
     * | create_time | string | 申请时间 |  | 是 |
     * | client_type | integer | 类型 | 0 直客广告主 2 代理商 | 是 |
     * | contact_name | string | 联系人 |  | 是 |
     * | invoice_type | integer | 发票类型 | 0增值税普通发票 2增值税专用发票 | 是 |
     * | money | decimal | 充值金额 |  | 是 |
     * | title | string | 发票抬头 |  | 是 |
     * | prov | string | 省 |  | 是 |
     * | city | string | 市 |  | 是 |
     * | dist | string |  |  |  |
     * | addr | string |  |  |  |
     * | receiver | string |  |  |  |
     * | tel | string |  |  |  |
     * | status | integer | 状态 | 1-申请中、2-审核通过 3-驳回| 是 |
     * | total | decimal | 发票金额汇总 | obj | 是 |
     */
    public function invoiceIndex(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $search = $request->input('search');
        $defaultAffiliateId = Client::DEFAULT_AFFILIATE_ID;
        $prefix = DB::getTablePrefix();
        $agencyId = Auth::user()->agencyid;
        $select =  DB::table('invoice AS inv')
            ->leftJoin('users AS u', 'inv.user_id', '=', 'u.user_id')
            ->leftJoin(
                DB::raw("
                (SELECT account_id, clientname, broker_id, affiliateid
                FROM up_clients WHERE broker_id = 0 UNION 
                SELECT account_id, name, brokerid, 0 FROM up_brokers) AS {$prefix}c"),
                'c.account_id',
                '=',
                'inv.account_id'
            )
            ->where('inv.agencyid', $agencyId)
            ->where('c.affiliateid', $defaultAffiliateId); //只表出affiliateid为0（不是自营的广告主）
        if (!empty($search)) {
            $select = $select->where("inv.title", "like", "%{$search}%")
                ->orWhere("u.contact_name", "like", "%{$search}%");
        }
        $select = $select->select(
            'inv.id',
            'inv.create_time',
            'u.contact_name',
            DB::raw("IF ({$prefix}c.broker_id = 0, 0 ,2) AS client_type"),
            'inv.invoice_type',
            'inv.money',
            'inv.title',
            'inv.address',
            'inv.receiver',
            'inv.status',
            'inv.tel'
        );

        $count  =  $select->count();
        if (!empty($search)) {
            //如果有搜索的才会执行
            $totalSelect = $select->get();
        }

        $offset =  (intval($pageNo) - 1) * intval($pageSize);
        $select->orderBy(DB::raw($prefix.'inv.create_time'), 'DESC');

        $select -> skip($offset)->take($pageSize);
        $list  =   $select ->get();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $address    =   json_decode($v->address);
                $v->prov = $address->prov;
                $v->city = $address->city=='null'?'':$address->city;
                $v->dist = $address->dist=='null'?'':$address->dist;
                $v->addr = $address->addr;
                $list[$k] = $v;
            }
        }
        //数据汇总
        $invoiceObj = DB::table('invoice')
            ->leftJoin('clients', 'invoice.account_id', '=', 'clients.account_id')
            ->where('invoice.status', Invoice::STATUS_TYPE_APPROVED)
            ->where('clients.affiliateid', $defaultAffiliateId)
            ->where('invoice.agencyid', $agencyId);
        if (!empty($search)) {
            if (!empty($totalSelect)) {
                $ids = [];
                foreach ($totalSelect as $k => $v) {
                    $ids[] = $v->id;
                }
                $invoiceObj->whereIn('id', $ids);
            }
        }
        $total = $invoiceObj->sum('money');

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
     * 发票更新管理
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 记录ID |  | 是 |
     * | field  | string | 状态值 | status   | 是 |
     * | value | integer | 2-审核通过 3-驳回 |  | 是 |
     * | content |  text |  驳回原因 |  驳回原因 | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response|boolean
     */
    public function invoiceUpdate(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required'
            ], [], Invoice::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        $content = $request->input('content');

        if ('status' == $field) {
            //如果传过来的状态值不在允许的范围
            if (!in_array($value, [Invoice::STATUS_TYPE_APPROVED,Invoice::STATUS_TYPE_REJECTED])) {
                return $this->errorCode(5000);
            }

            if (Invoice::STATUS_TYPE_APPROVED == $value) {
                //通过审核
                $row = Invoice::find($id);
                if ($row->agencyid != Auth::user()->agencyid) {
                    return $this->errorCode(0);
                }

                $row->$field = $value;
                $row->save();

                return $this->errorCode(0);
            } else {
                //驳回
                $objectParam = [
                    'id'        => $id,
                    'comment'   => $content,
                    'field' => $field,
                    'value' => $value
                ];

                $transactionResult = DB::transaction(function ($objectParam) use ($objectParam) {
                    extract($objectParam);
                    $row = Invoice::find($id);
                    if ($row->agencyid != Auth::user()->agencyid) {
                        return false;
                    }

                    $row->$field = $value;
                    $row->comment = $comment;
                    if ($row->save()) {
                        $save = DB::table("invoice_balance_log_assoc")
                            ->where("invoice_id", "=", $id)
                            ->update(array("status"=>0));
                        if (!$save) {
                            DB::rollBack();
                            return false;
                        }
                    } else {
                        DB::rollBack();
                        return false;
                    }

                    DB::commit();
                    return true;
                });

                if (false == $transactionResult) {
                    return $this->errorCode(5001);//@codeCoverageIgnore
                } else {
                    return $this->success();
                }
            }
        }
    }

    /**
     * 获取发票充值明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 发票ID |  invoice_id | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response|boolean
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | create_time | string | 充值时间 |  | 是 |
     * | amount | decimal | 发生金额 |  | 是 |
     * | pay_type | integer | 类型 | 0：在线充值 1：线下充值 | 是 |
     */
    public function invoiceDetail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required'
            ], [], Invoice::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $row = [];
        if (0 < $id) {
            $row = Invoice::find($id)->balancelogs()->select('create_time', 'amount', 'pay_type')->get()->toArray();
        }
        return $this->success(
            null,
            null,
            $row
        );
    }

    /**
     * 广告主代理商/赠送申请列表
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  |  |  | -status降序，降序在字段前加- | 否 |
     * @param Request $request
     *
     * @return array
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 自增 |  | 是 |
     * | created_at | string | 申请时间 |  | 是 |
     * | contact_name | string | 申请人 |  | 是 |
     * | name | string | 赠送广告主/代理商 |  | 是 |
     * | client_type | integer | 类型 |  | 是 |
     * | amount | decimal | 赠送金额 |  | 是 |
     * | gift_info | string | 赠送原因 |  | 是 |
     * | status | integer | 状态 | 1-申请中 2-审核通过 3-驳回 | 是 |
     * | total | decimal | 赠送充值申请 | obj | 是 |
     */
    public function giftIndex(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $search = $request->input('search');
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        $prefix = DB::getTablePrefix();
        $agencyId = Auth::user()->agencyid;
        $select = DB::table('gift AS g')
            ->leftJoin('users AS u', 'g.user_id', '=', 'u.user_id')
            ->leftJoin(
                DB::raw("
                        (SELECT account_id, clientname AS name, broker_id, affiliateid
                            FROM up_clients 
                        WHERE broker_id = 0
                    UNION
                        SELECT account_id, name, brokerid, affiliateid FROM up_brokers) AS {$prefix}c"),
                'c.account_id',
                '=',
                'g.target_accountid'
            )
            ->where('g.agencyid', $agencyId)
            ->where('c.affiliateid', $defaultAffiliateid);
        if (!empty($search)) {
            $select = $select->where('c.name', 'like', "%{$search}%")
                ->orWhere("u.contact_name", "like", "%{$search}%");
        }

        $select = $select->select(
            'g.id',
            'g.created_at',
            'u.contact_name',
            'c.name',
            DB::raw("IF ({$prefix}c.broker_id = 0, 0 ,2) AS client_type"),
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
        $invoiceObj = DB::table('gift')
            ->leftJoin('clients', 'gift.target_accountid', '=', 'clients.account_id')
            ->where('gift.status', Gift::STATUS_TYPE_PASSED)
            ->where('clients.affiliateid', $defaultAffiliateid)
            ->where('gift.agencyid', $agencyId);
        
        if (!empty($search)) {
            if (!empty($totalSelect)) {
                $ids = [];
                foreach ($totalSelect as $k => $v) {
                    $ids[] = $v->id;
                }
                $invoiceObj->whereIn('gift.id', $ids);
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
     * 广告主/代理商赠送更新
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 记录ID |  | 是 |
     * | field | string | 字段 | status  | 是 |
     * | value | integer | 值 | 1-待审核、2-审核通过、3-驳回 | |
     *
     * @param Request $request
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
        if ('status' == $field) {
            if (!in_array($value, [Gift::STATUS_TYPE_PASSED, Gift::STATUS_TYPE_REJECTED])) {
                return $this->errorCode(5000);
            }

            $gift = Gift::find(intval($id));
            if (empty($gift)) {
                return $this->errorCode(5000);
            }
            
            //检查状态是否为待审核，如果不是，则不能提交
            if (Gift::STATUS_TYPE_WAIT != $gift->status) {
                return $this->errorCode(5006);
            }

            if ($gift->agencyid != Auth::user()->agencyid) {
                return $this->errorCode(4001);
            }

            $objectParam = ['id' => $id, 'gift' => $gift, 'field' => $field, 'value' => $value];
            $giftTransaction = DB::transaction(function ($objectParam) use ($objectParam) {
                DB::beginTransaction();
                extract($objectParam);
                $gift->$field = $value;
                if ($gift->save()) {
                    //如果通过审核
                    if ($value == Gift::STATUS_TYPE_PASSED) {
                        //更新
                        $balance = Balance::find($gift->target_accountid);
                        if (!empty($balance)) {
                            $data = [
                                'gift' => $balance->gift + $gift->amount
                            ];
                            $balance_gift = $balance->balance + $balance->gift;
                            $balanceResult = Balance::where('account_id', $gift->target_accountid)->update($data);
                        } else {
                            $data = [
                                'account_id' => $gift->target_accountid,
                                'balance' => 0,
                                'gift' => $gift->amount
                            ];
                            $balance_gift = 0;
                            $balanceResult = Balance::create($data);
                        }

                        if ($balanceResult) {
                            $agencyId = Auth::user()->account->agency->agencyid;
                            $accountId = Auth::user()->account->account_id;

                            $logData = [
                                'media_id' => $agencyId,
                                'operator_accountid' => $gift->account_id,
                                'operator_userid' => $gift->user_id,
                                'target_acountid' => $gift->target_accountid,
                                'pay_type' => BalanceLog::PAY_TYPE_PRESENT_GOLD,
                                'amount' => $gift->amount,
                                'balance' => $balance_gift,
                                'balance_type' => BalanceLog::BALANCE_TYPE_GIFT_PROMOTION,
                                'comment' => $gift->gift_info,
                                'create_time' => date("Y-m-d H:i:s")
                            ];
                            $logResult = BalanceLog::create($logData);

                            //给操作的账户也加上赠送金
                            $save_agency = DB::update("INSERT INTO up_balances(account_id, gift) VALUES(?,?) ON
                            DUPLICATE KEY UPDATE gift = gift + ?;", [$accountId, $gift->amount, $gift->amount]);
                            if ($logResult) {
                                //启动因余额不足而被暂停的广告
                                BalanceService::restartCampaign($gift->target_accountid, $gift->amount, 6003);
                                DB::commit();
                                return true;
                            } else {
                                DB::rollback();
                                return false;
                            }
                        } else {
                            DB::rollback();
                            return false;
                        }
                    } else {
                        //驳回
                        DB::commit();
                        return true;
                    }
                } else {
                    DB::rollback();
                    return false;
                }
            });

            if (true == $giftTransaction) {
                return $this->errorCode(0);
            } else {
                return $this->errorCode(5001);
            }
        }
    }

    /**
     * 提款明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  |  | | -status降序，降序在字段前加- | 否 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id | integer | 自增 |  | 是 |
     * | create_time | string | 申请时间 |  | 是 |
     * | name | string | 媒体商 |  | 是 |
     * | money | decimal | 金额 |  | 是 |
     * | bank | string | 开户行 |  | 是 |
     * | bank_account | string | 银行账号 |  | 是 |
     * | payee | string | 收款人 |  | 是 |
     * | status | integer | 状态 | 1-申请中、2-审核通过、3-驳回 | 是 |
     * | total | decimal | 提款总额 | obj | 是 |
     */
    public function withdrawalIndex(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $search = e($request->input('search'));
        $prefix = DB::getTablePrefix();
        $agencyId = Auth::user()->agencyid;
        $sql = "SELECT pay.id, pay.create_time, pay.money, affiliates.name, pay.status, pay.comment 
                FROM {$prefix}pay AS pay
                LEFT JOIN {$prefix}agency AS agency ON agency.agencyid = pay.agencyid 
                LEFT JOIN {$prefix}affiliates AS affiliates ON affiliates.account_id = pay.operator_accountid
                WHERE 1 AND pay.status != 0 AND pay.pay_type = 2 and pay.agencyid = $agencyId";
        if (!empty($search)) {
            $sql .= " AND affiliates.name LIKE '%{$search}%'";
        }
        $sql .= " UNION
                    SELECT pay_tmp.id, pay_tmp.create_time, pay_tmp.money, affiliates.name, pay_tmp.status, 
                    pay_tmp.comment FROM {$prefix}pay_tmp AS pay_tmp
                    LEFT JOIN {$prefix}agency AS agency ON agency.agencyid = pay_tmp.agencyid 
                    LEFT JOIN {$prefix}affiliates AS affiliates ON affiliates.account_id = pay_tmp.operator_accountid
                    WHERE 1 AND pay_tmp.status != 0 AND pay_tmp.pay_type = 2 AND pay_tmp.agencyid = $agencyId";
        if (!empty($search)) {
            $sql .= " AND affiliates.name LIKE '%{$search}%'";
        }
        $row = DB::select($sql);
        $count = count($row);
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $string = $sql . " ORDER BY create_time DESC LIMIT {$offset} , {$pageSize}";
        $list = DB::select($string);
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $c = json_decode($v->comment);
                $v->bank = isset($c->bank) ? $c->bank : '';
                $v->payee = isset($c->payee) ? $c->payee : '';
                $v->bank_account = isset($c->bank_account) ? $c->bank_account : '';
            }
        }

        $tSql = "SELECT SUM(money) AS total FROM (SELECT pay.id, pay.money FROM {$prefix}pay AS pay
                 LEFT JOIN {$prefix}affiliates AS affiliates ON affiliates.account_id = pay.operator_accountid
                 WHERE 1 AND pay.status = 2 AND pay.pay_type = 2 AND pay.agencyid = $agencyId"; //审核通过
        if (!empty($search)) {
            $tSql .= " AND affiliates.name LIKE '%{$search}%'";
        }
        $tSql .= " UNION SELECT pay_tmp.id, pay_tmp.money FROM {$prefix}pay_tmp AS pay_tmp
                 LEFT JOIN {$prefix}affiliates AS affiliates ON affiliates.account_id = pay_tmp.operator_accountid
                 WHERE 1 AND pay_tmp.status = 2 AND pay_tmp.pay_type = 2 AND pay_tmp.agencyid = $agencyId";
        if (!empty($search)) {
            $tSql .= " AND affiliates.name LIKE '%{$search}%'";
        }
        $tSql .= ") AS payinfo";
        $total = DB::selectOne($tSql)->total;

        return $this->success(
            ['total' => empty($total) ? 0 : $total],
            [
                'count' => $count,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ],
            $list
        );
    }


    /**
     * 媒体提款更新
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 记录ID |  | 是 |
     * | field | string | 字段 | status  | 是 |
     * | value | integer | 值 | 1-待审核、2-审核通过、3-驳回 | |
     */
    public function withdrawalUpdate(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required'
            ], [], Pay::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        if ('status' == $field) {
            //通过审核
            if ($value == PayTmp::STATUS_APPROVED) {
                $payTmp =   DB::table('pay_tmp')
                    ->where('id', '=', $id)
                    ->where('pay_type', '=', PayTmp::PAY_TYPE_DRAWINGS);
                $payTmpRow = $payTmp->first();
                if (!empty($payTmpRow->id)) {
                    $payData = array(
                        'codeid' => '',
                        'codepay' => '',
                        'operator_accountid' => $payTmpRow->operator_accountid,
                        'operator_userid' => $payTmpRow->operator_userid,
                        'agencyid' => $payTmpRow->agencyid,
                        'pay_type' => PayTmp::PAY_TYPE_DRAWINGS,
                        'money' => $payTmpRow->money,
                        'ip' => $payTmpRow->ip,
                        'create_time' => $payTmpRow->create_time,
                        'status'    => PayTmp::STATUS_APPROVED,
                        'comment' => $payTmpRow->comment
                    );
                    $objectParam = array(
                        'payData' => $payData,
                        'payTmp' => $payTmp,
                    );

                    //插入pay表，删除pay_tmp表，更新balance表
                    $transactionResult = DB::transaction(function ($objectParam) use ($objectParam) {
                        extract($objectParam);
                        DB::beginTransaction();
                        $Pay = Pay::create($payData);
                        if (0 < $Pay->id) {
                            if (!$payTmp->delete()) {
                                DB::rollBack();
                                return false;
                            }

                            $sql = "update up_balances set balance = balance-".abs($Pay->money)." where 
                                    account_id = ".$Pay->operator_accountid;
                            $update = DB::update($sql);
                            if (!$update) {
                                DB::rollBack();
                                return false;
                            }

                            DB::commit();
                            return true;
                        }
                    });
                    if (true == $transactionResult) {
                        return $this->errorCode(0);
                    } else {
                        return $this->errorCode(5001);
                    }
                } else {
                    return $this->errorCode(5001);
                }
            } else {
                //驳回
                $row = PayTmp::find($id);
                if (!empty($row)) {
                    $row->$field = $value;//驳回
                    if ($row->save()) {
                        return $this->errorCode(0);
                    } else {
                        return $this->errorCode(5001);
                    }
                } else {
                    return $this->errorCode(5001);
                }
            }
        }
    }



    /**
     * 收支明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  |  |  | -status降序，降序在字段前加- | 否 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 自增 |  | 是 |
     * | create_time | string | 申请时间 |  | 是 |
     * | pay_type | integer | 收入类型 |  | 是 |
     * | bala * nce_type | integer | 金额类型 |  | 是 |
     * | client_type | integer | 用户类型 |  | 是 |
     * | name | string | 交易对象 |  | 是 |
     * | amount | decimal | 金额 | 当pay_type为2且balance_type为2，金额为负数 | 是 |
     * | total_income | decimal | 汇总收入 | obj中 | 是 |
     * | total_pay | decimal | 汇总支出 | obj中 | 是 |
     */
    public function incomeIndex(Request $request)
    {
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $search = $request->input('search');

        $agencyId = Auth::user()->account->agency->agencyid;
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        if (empty($search)) {
            $total_pay = BalanceLog::leftJoin('clients AS cl', 'balance_log.target_acountid', '=', 'cl.account_id')
                ->whereIn('balance_type', [
                    BalanceLog::BALANCE_TYPE_GIVE_ACCOUNT,
                    BalanceLog::BALANCE_TYPE_MEDIA_BUSINESS,
                    BalanceLog::BALANCE_TYPE_GIFT_PROMOTION
                ])
                ->where('media_id', $agencyId)
                ->where('cl.affiliateid', $defaultAffiliateid)
                ->whereIn('pay_type', [
                    BalanceLog::PAY_TYPE_MEDIA_DIVIDED,
                    BalanceLog::PAY_TYPE_PRESENT_GOLD,
                    BalanceLog::PAY_TYPE_ADVERTISERS_REFUND
                ])
                ->sum('amount');

            $total_income = BalanceLog::leftJoin('clients AS cl', 'balance_log.target_acountid', '=', 'cl.account_id')
                ->whereIn('balance_type', [
                    BalanceLog::BALANCE_TYPE_GOLD_ACCOUNT
                ])
                ->where('cl.affiliateid', $defaultAffiliateid)
                ->where('media_id', $agencyId)
                ->whereIn('pay_type', [
                    BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                    BalanceLog::PAY_TYPE_OFFLINE_RECHARGE
                ])->sum('amount');

            $select = BalanceLog::leftJoin('clients AS cl', 'balance_log.target_acountid', '=', 'cl.account_id')
                ->where('media_id', $agencyId)
                ->where('cl.affiliateid', $defaultAffiliateid)
                ->whereNotIn('pay_type', [
                    BalanceLog::PAY_TYPE_ON_SPENDING,
                    BalanceLog::PAY_TYPE_GOLD_BROKER_TO_ADVERTISER,
                    BalanceLog::PAY_TYPE_GIVE_BROKER_TO_ADVERTISER,
                    BalanceLog::PAY_TYPE_GOLD_ADVERTISER_TO_BROKER,
                    BalanceLog::PAY_TYPE_GIVE_ADVERTISER_TO_BROKER,
                ])
                ->select('id', 'pay_type', 'balance_type', 'create_time', 'amount', 'balance', 'target_acountid')
                ->orderBy('id', 'desc');
        } else {
            $select = DB::table("balance_log AS b")
                ->leftJoin("clients AS c", "b.target_acountid", "=", "c.account_id")
                ->leftJoin("affiliates AS a", "b.target_acountid", "=", "a.account_id")
                ->leftJoin("brokers AS bk", "b.target_acountid", "=", "bk.account_id")
                ->leftJoin("agency AS ag", "b.target_acountid", "=", "ag.account_id")
                ->select(
                    "b.id",
                    "b.pay_type",
                    "b.balance_type",
                    "b.create_time",
                    "b.amount",
                    "b.balance",
                    "b.target_acountid"
                )
                ->whereNotIn('pay_type', [
                    BalanceLog::PAY_TYPE_ON_SPENDING,
                    BalanceLog::PAY_TYPE_GOLD_BROKER_TO_ADVERTISER,
                    BalanceLog::PAY_TYPE_GIVE_BROKER_TO_ADVERTISER,
                    BalanceLog::PAY_TYPE_GOLD_ADVERTISER_TO_BROKER,
                    BalanceLog::PAY_TYPE_GIVE_ADVERTISER_TO_BROKER,
                ])
                ->where('c.affiliateid', $defaultAffiliateid)
                ->where(function ($query) use ($search) {
                    $query->orWhere("c.clientname", "LIKE", "%{$search}%")
                        ->orWhere("bk.name", "LIKE", "%{$search}%")
                        ->orWhere("a.name", "LIKE", "%{$search}%")
                        ->orWhere("ag.name", "LIKE", "%{$search}%")
                        ->orderBy('id', 'desc');
                })
                ->where('b.media_id', $agencyId);

            $balanceObj = $select->get();
            $ids = [];
            if (!empty($balanceObj)) {
                $logData = json_decode(json_encode($balanceObj), true);
                foreach ($logData as $ke => $va) {
                    $ids[] = $va['id'];
                }
            }

            $total_pay_query = BalanceLog::leftJoin(
                'clients AS cl',
                'balance_log.target_acountid',
                '=',
                'cl.account_id'
            )
                ->where('cl.affiliateid', 0)
                ->whereIn('balance_type', [
                    BalanceLog::BALANCE_TYPE_GIVE_ACCOUNT,
                    BalanceLog::BALANCE_TYPE_MEDIA_BUSINESS,
                    BalanceLog::BALANCE_TYPE_GIFT_PROMOTION
                ])
                ->whereIn('pay_type', [
                    BalanceLog::PAY_TYPE_MEDIA_DIVIDED,
                    BalanceLog::PAY_TYPE_PRESENT_GOLD,
                    BalanceLog::PAY_TYPE_ADVERTISERS_REFUND
                ])
                ->where('media_id', $agencyId);
            if (!empty($ids)) {
                $total_pay_query->whereIn('id', $ids);
            }
            $total_pay = $total_pay_query->sum('amount');

            $total_income_query = BalanceLog::leftJoin(
                'clients AS cl',
                'balance_log.target_acountid',
                '=',
                'cl.account_id'
            )
                ->where('cl.affiliateid', 0)
                ->whereIn('balance_type', [
                    BalanceLog::BALANCE_TYPE_GOLD_ACCOUNT
                ])
                ->whereIn('pay_type', [
                    BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                    BalanceLog::PAY_TYPE_OFFLINE_RECHARGE
                ])->where('media_id', $agencyId);
            if (!empty($ids)) {
                $total_income_query->whereIn('id', $ids);
            }
            $total_income = $total_income_query->sum('amount');
        }

        // 分页
        $count = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);
        if (empty($search)) {
            $rows = $select->get()->toArray();
        } else {
            $rows = $select->get();
            if (!empty($rows)) {
                $rows = json_decode(json_encode($rows), true);
            }
        }

        $list = [];
        foreach ($rows as $row) {
            $item = $row;
            $item['name'] = '-';
            $account = Account::find($row['target_acountid']);
            $item['account_type'] = $account->account_type;
            if (2 == $item['balance_type'] && 2 == $item['pay_type']) {
                $item['amount'] = -(abs($item['amount']));
            }

            if ($account) {
                if ($account->account_type == Account::TYPE_TRAFFICKER) {
                    $item['name'] = $account->affiliate->name;
                    $item['client_type'] = 3;
                } elseif ($account->account_type == Account::TYPE_BROKER) {
                    $item['name'] = $account->broker->name;
                    $item['client_type'] = 1;
                } elseif ($account->account_type == Account::TYPE_ADVERTISER) {
                    $item['name'] = $account->client->clientname;
                    $item['client_type'] = 0;
                } else {
                    $item['name'] = $account->agency->name;
                    $item['client_type'] = 4;
                }
            }
            $list[] = $item;
        }


        return $this->success(
            [
                'total_income' => Formatter::asDecimal($total_income),
                'total_pay' => Formatter::asDecimal(abs($total_pay)),
            ],
            [
                'count' => $count,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ],
            $list
        );
    }

//    /**
//     * 
//     * 获取排序类型
//     * @param $sort
//     * @return string
//     */
//    private function getSortType($sort)
//    {
//        $sortType = 'asc';
//        if (strncmp($sort, '-', 1) === 0) {
//            $sortType = 'desc';
//        }
//        return $sortType;
//    }
}
