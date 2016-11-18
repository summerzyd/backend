<?php

namespace App\Http\Controllers\Trafficker;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Gift;
use App\Models\Recharge;
use App\Models\User;
use App\Services\AdvertiserService;
use App\Services\GiftService;
use App\Services\RechargeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\StringHelper;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class AdvertiserController extends Controller
{
    /**
     * 媒体商自营广告主列表
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | pageSize | integer | 页面 |  | 否 |
     * | pageNo | integer | 当前页码 |  | 否 |
     * | sort | string | 排序 | status 升序 -status降序，降序在字段前加- | 否 |
     * | search | string | 排序 |  | 否 |
     * | filter | string | 筛选 | Json格式：{"revenue_type":3, "clients_status":1,"creator_uid":2} | 否 |
     * |  |  |  | revenue_type 计费类型 clients_status状态 creator_uid 销售顾问ID |  |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientname | string | 广告主名称 |  | 是 |
     * | brief_name | string | 广告主简称 |  | 是 |
     * | username | string | 登录账号 |  | 是 |
     * | contact | string | 联系人 |  | 是 |
     * | email | string | 邮箱 |  | 是 |
     * | contact_phone | string | 手机号 |  | 是 |
     * | qq | string | qq号 |  | 是 |
     * | balance | string | 推广金金额 | 可排序 | 是 |
     * | gift | string | 赠送金金额 | 可排序 | 是 |
     * | total | string | 总余额 | 可排序 | 是 |
     * | creator_uid | string | 创建者uid | 获客广告主返回代理商信息 | 是 |
     * | creator_name | string | 创建者名称 | 获客广告主返回代理商信息 | 是 |
     * | username | string | 创建者名称 |  | 是 |
     * | creator_contact_phone | string | 销售顾问/代理商电话 | 获客返回代理商信息 | 是 |
     * | affiliates_status | integer | 状态 | 1：激活； 0：停用 | 是 |
     * | date_created | date | 创建时间 |  | 是 |
    */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required|integer',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);
        $type = $request->input('type');

        $data = $this->getClientList($pageNo, $pageSize, $type, $search, $sort, $filter);

        return $this->success(null, $data['map'], $data['list']);
    }

    /**
     * 返回可过滤的字段
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request)
    {
        return $this->success(
            [
                'field' => ['k1' => 'v1', 'k2' => 'v2'],
            ]
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request)
    {
        $id = $request->input('id');
        $model = Client::findOrFail($id);

        return $this->success($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientname | string | 广告主名称 |  | 是 |
     * | brief_name | string | 广告主简称 |  | 是 |
     * | username | string | 登录账号 |  | 是 |
     * | password | string | 初始密码 |  | 是 |
     * | contact | string | 联系人 |  | 是 |
     * | email | string | 邮箱 |  | 是 |
     * | contact_phone | string | 手机号 |  | 是 |
     * | qq | string | QQ号码 |  | 否 |
     * | creator_uid | string | 销售顾问uid |  | 是 |
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientname' => 'required|min:2|max:96',
                'brief_name' => 'required|min:2|max:96',
                'username' => 'required',
                'email' => "required|email",
                'contact' => 'required',
                'contact_phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
            ], [], array_merge(Client::attributeLabels(), User::attributeLabels()))) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();

        $affiliateId = isset(Auth::user()->account->affiliate->affiliateid)
            ? Auth::user()->account->affiliate->affiliateid : 0;

        if (isset($params['clientid']) && $params['clientid'] > 0) { //update
            $client = Client::find($params['clientid']);
            $client->clientname = $params['clientname'];
            $client->brief_name = $params['brief_name'];
            $client->contact = $params['contact'];
            $client->email = $params['email'];
            $client->creator_uid = $params['creator_uid'];
            $client->save();

            $account = $client->account;
            $account->account_name = $params['username'];
            $account->save();

            $user = $account->user;
            $user->username = $params['username'];
            $user->contact_name = $params['contact'];
            $user->qq = $params['qq'];
            $user->email_address = $params['email'];
            $user->contact_phone = $params['contact_phone'];
            $user->save();

            return $this->success();
        } else {
            if (Auth::user()->account->account_type == Account::TYPE_TRAFFICKER) {
                if (($ret = $this->validate($request, [
                        'creator_uid' => "required|integer|min:1",
                        'password' => 'required|between:6,16',
                    ], [], array_merge(Client::attributeLabels(), User::attributeLabels()))) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
                $params['affiliateid'] = Auth::user()->account->affiliate->affiliateid;
            } else {
                $params['affiliateid'] = Auth::user()->account->broker->affiliateid;
                $params['broker_id'] = Auth::user()->account->broker->brokerid;
                $params['creator_uid'] = Auth::user()->user_id;
            }

            if (Client::getAgencyClient('clientname', $params['clientname'], 0, $affiliateId)) {
                return $this->errorCode(5096);
            }
            if (Client::getAgencyClient('brief_name', $params['brief_name'], 0, $affiliateId)) {
                return $this->errorCode(5095);
            }
            if (User::getAgencyUser('username', $params['username'])) {
                return $this->errorCode(5092);
            }
            $result = AdvertiserService::store($params, 1);
            if ($result == 'success') {
                return $this->success();
            } else {
                return $this->errorCode($result);
            }
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | id | integer | clientid |  | 是 |
     * | field | string | 字段 | clients_status password | 是 |
     * | value | string | 联系人 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required|integer',
                'field' => 'required',
                'value' => 'required',
            ], [], array_merge(Client::attributeLabels()))) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');

        $client = Client::find($id);
        if ($client->affiliateid != Auth::user()->account->affiliate->affiliateid) {
            return $this->errorCode(5003);
        }
        $user = $client->account->user;

        if ($field == 'password') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:6'
                ], [], Client::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            $user->password = md5($value);
            $user->save();
        } elseif ($field == 'clients_status') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|in:0,1'
                ], [], Client::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            $client->clients_status = $value;
            $client->save();
            $user->active = $value;
            $user->save();
        }

        return $this->success();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    /*public function delete(Request $request)
    {
        $id = $request->input('id');
        if (Client::destroy($id)) {
            return $this->success();
        }

        return $this->errorCode(5000);
    }*/

    /**
     * Get Client list based on search, filter, sort and page.
     * @param int $pageNo
     * @param int $pageSize
     * @param int $type
     * @param string $search
     * @param string $sort
     * @param array $filter
     * @return array
     */
    protected function getClientList(
        $pageNo = 1,
        $pageSize = 10,
        $type = 0,
        $search = null,
        $sort = null,
        $filter = null
    ) {
        $prefix = DB::getTablePrefix();
        $select = DB::table('clients')
            ->leftJoin('balances', 'clients.account_id', '=', 'balances.account_id')
            ->leftJoin('accounts', 'clients.account_id', '=', 'accounts.account_id')
            ->leftJoin('users', 'accounts.manager_userid', '=', 'users.user_id')
            ->select(
                'clients.clientid',
                'clients.clientname',
                'clients.brief_name',
                'clients.contact',
                'clients.email',
                'clients.clients_status',
                'clients.revenue_type',
                'clients.creator_uid',
                'users.username',
                'users.user_id',
                'users.qq',
                'users.contact_phone',
                'balances.balance',
                'balances.gift',
                DB::raw('(' . $prefix . 'balances.balance + ' . $prefix . 'balances.gift) as total'),
                'users.date_created'
            )
            ->where('clients.agencyid', Auth::user()->agencyid)
            ->where('clients.affiliateid', Auth::user()->account->affiliate->affiliateid);
        //区分直客及获客广告主
        if ($type) {
            $select->leftJoin('brokers', 'clients.broker_id', '=', 'brokers.brokerid')
                ->where('clients.broker_id', '>', 0)
                ->addSelect('brokers.brief_name as broker_brief_name');
        } else {
            $select->where('clients.broker_id', 0);
        }
        // search
        if (!is_null($search) && trim($search)) {
            $select->where(function ($select) use ($search) {
                $select->where('clients.clientname', 'like', '%' . $search . '%');
                $select->orWhere('clients.brief_name', 'like', '%' . $search . '%');
                $select->orWhere('users.contact_name', 'like', '%' . $search . '%');
                $select->orWhere('users.username', 'like', '%' . $search . '%');
                $select->orWhere('clients.email', 'like', '%' . $search . '%');
            });
        }

        // filter
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    $select->where($k, $v);
                }
            }
        }

        // sort
        if ($sort) {
            $sortType = 'asc';
            if (strncmp($sort, '-', 1) === 0) {
                $sortType = 'desc';
            }
            $sortAttr = str_replace('-', '', $sort);
            $select->orderBy($sortAttr, $sortType);
        } else {
            $select->orderBy('clients_status', 'desc');
        }

        // page
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        $rows = $select->get();
        $list = [];
        foreach ($rows as $row) {
            $user = User::find($row->creator_uid);
            $row->creator_name = is_null($user) ? '-' : $user->contact_name;
            $list[] = $row;
        }
        return [
            'map' => [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => $total,
            ],
            'list' => $list,
        ];
    }

    /**
     * 获取充值账号历史记录
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主ID |  | 是 |
     * | way | integer | 方式 0:支付宝  1:对公银行卡 |  | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | account_info | string | 充值账号 |  | 是 |
     */
    public function rechargeHistory(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required',
                'way' => 'required|in:0,1',
            ], [], Recharge::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //判断是否有充值申请权限
        if (!$this->can('trafficker-self-advertiser') ||
            (!Auth::user()->account->isTrafficker() && !Auth::user()->account->isManager())
        ) {
            return $this->errorCode(5004);
        }
        $way = $request->get('way');
        $client = Client::find($request->get('clientid'));
        $agencyId = Auth::user()->agencyid;
        $accountId = Auth::user()->account->account_id;
        $user_id = Auth::user()->user_id;
        $target_accountId = $client->account_id;
        $info = RechargeService::history($accountId, $user_id, $agencyId, $target_accountId, $way);
        return $this->success(null, null, $info);
    }

    /**
     * 提交充值申请
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主ID |  | 是 |
     * | way | integer | 方式 0:支付宝  1:对公银行卡 |  | 是 |
     * | amount | decimal | 赠送金额 |  | 是 |
     * | account_info | string | 充值账号 |  | 是 |
     * | date | string | 充值时间 |  | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function rechargeApply(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required',
                'account_info' => 'required',
                'way' => 'required|in:0,1',
                'date' => 'required',
                'amount' => 'required|numeric|min:0',
            ], [], array_merge(Recharge::attributeLabels(), Client::attributeLabels()))) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //判断是否有充值申请权限
        if (!$this->can('trafficker-self-advertiser') ||
            (!Auth::user()->account->isTrafficker() && !Auth::user()->account->isManager())
        ) {
            return $this->errorCode(5004);
        }
        $client = Client::find($request->get('clientid'));
        $agencyId = Auth::user()->agencyid;
        $accountId = $client->account_id;
        $param['way'] = $request->get('way');
        $param['account_info'] = $request->get('account_info');
        $param['amount'] = $request->get('amount');
        $param['date'] = $request->get('date');
        $result = RechargeService::apply($agencyId, $accountId, $param, 0);
        if ($result == 'success') {
            return $this->success();
        } else {
            return $this->errorCode($result);
        }
    }

    /**
     * 充值明细
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主ID |  | 是 |
     * | pageSize | integer | 页面 |  | 否 |
     * | pageNo | integer | 当前页码 |  | 否 |
     * | sort | string | 排序 | status 升序 -status降序，降序在字段前加- | 否 |
     * | search | string | 排序 |  | 否 |
     * | filter | string | 筛选 | Json格式：{"revenue_type":3, "clients_status":1,"creator_uid":2} | 否 |
     * |  |  |  | revenue_type 计费类型 clients_status状态 creator_uid 销售顾问ID |  |

     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | date | date | 充值时间 |  | 是 |
     * | clientname | string | 广告主名称 |  | 是 |
     * | amount | num | 充值金额 |  | 是 |
     * | contact_name | string | 申请人 |  | 是 |
     * | status | integer | 状态 | 1：待审核 | 是 |
     * | |  |  | 2：审核通过 | |
     * | |  |  | 3：已驳回 | |
     * | comment | string | 驳回原因 |  | 驳回状态时必选 |
     */
    public function rechargeDetail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');

        $clientId = $request->get('clientid');
        $user = Auth::user();
        $account = $user->account;
        $agencyId = $account->agencyid;
        $rows = RechargeService::getRechargeList($agencyId, 0, $clientId, $pageNo, $pageSize, $search, $sort);
        if (!$rows) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success(null, $rows['map'], $rows['list']);
    }

    /**
     * 赠送申请
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主ID |  | 是 |
     * | amount | integer | 赠送金额 |  | 是 |
     * | gift_info | string | 赠送原因 |  | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function giftApply(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required|numeric',
                'amount' => 'required|numeric|min:0',
                'gift_info' =>'required|string',
            ], [], Gift::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        if (!$this->can('trafficker-self-advertiser')) {
            return $this->errorCode(5001);
        }
        $clientId = $request->get('clientid');
        $client = Client::find($clientId);
        $agencyId = Auth::user()->agencyid;
        $accountId = $client->account_id;
        if ($client->agencyid != $agencyId) {
            return $this->errorCode(5202);
        }
        $gift = new Gift();
        $gift->account_id = Auth::user()->account->account_id;
        $gift->user_id = Auth::user()->user_id;
        $gift->agencyid = $agencyId;
        $gift->target_accountid = $accountId;
        $gift->amount = $request->get('amount');
        $gift->gift_info = $request->get('gift_info');
        $gift->status = Gift::STATUS_TYPE_WAIT;
        $gift->comment = '';
        $gift->type = 0; //1为代理商0为广告主
        if (!$gift->save()) {
            return $this->errorCode(5001); // @codeCoverageIgnore
        }

        return $this->success();
    }

    /**
     * 赠送明细
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主ID |  | 是 |
     * | pageSize | integer | 页面 |  | 否 |
     * | pageNo | integer | 当前页码 |  | 否 |
     * | sort | string | 排序 | status 升序 -status降序，降序在字段前加- | 否 |
     * | search | string | 排序 |  | 否 |
     * | filter | string | 筛选 | Json格式：{"revenue_type":3, "clients_status":1,"creator_uid":2} | 否 |
     * |  |  |  | revenue_type 计费类型 clients_status状态 creator_uid 销售顾问ID |  |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | created_at | date | 申请时间 |  | 是 |
     * | contact | string | 广告主名称 | ?赠送对象 | 是 |
     * | amount | num | 赠送金额 |  | 是 * | |
     * | gift_info | string | 赠送说明 |  | 是 |
     * | contact_name | string | 申请人 |  | 是 |
     * | status | integer | 状态 | 1：待审核 | 是 |
     * | |  |  | 2：审核通过 | |
     * | |  |  | 3：已驳回 | |
     * | comment | string | 驳回原因 |  | 驳回状态时必选 |
     */
    public function giftDetail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required|numeric',
            ], [], Client::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $sort = $request->input('sort');
        $search = $request->input('search');
        $clientId = $request->get('clientid');
        $agencyId = Auth::user()->agencyid;
        $userId = Auth::user()->user_id;
        $client = Client::find($clientId);
        $accountId = $client->account_id;

        $rows = GiftService::getGiftList($agencyId, 1, $userId, $accountId, $pageNo, $pageSize, $search, $sort);
        if (!$rows) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success(null, $rows['map'], $rows['list']);
    }

    /**
     * GET:获得销售顾问
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | user_id | integer | 用户ID |  | 是 |
     * | contact_name | string | 名称 |  | 是 |
     */
    public function sales()
    {
        $accountId = Auth::user()->default_account_id;
        $models = DB::table('users')
            ->where('agencyid', Auth::user()->agencyid)
            ->where('default_account_id', $accountId)
            ->select('user_id', 'contact_name')
            ->get();
        $obj = ArrayHelper::map($models, 'user_id', 'contact_name');
        return $this->success($obj, null, null);
    }
}
