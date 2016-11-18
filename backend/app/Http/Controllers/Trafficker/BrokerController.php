<?php

namespace App\Http\Controllers\Trafficker;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Broker;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Gift;
use App\Models\Recharge;
use App\Services\AccountService;
use App\Services\AdvertiserService;
use App\Services\BrokerService;
use App\Services\GiftService;
use App\Services\RechargeService;
use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class BrokerController extends Controller
{
    /**
     * 获取代理商列表
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $account = $user->account;
        $affiliateId = $account->affiliate->affiliateid;
        $agencyId = $account->agencyid;

        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);
        $rows = BrokerService::getBrokerList($agencyId, 0, $affiliateId, $pageNo, $pageSize, $search, $sort, $filter);

        if (!$rows) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }

        return $this->success(null, $rows['map'], $rows['list']);
    }
    /**
     * 平台新增代理商
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'username' => 'required|min:2|max:32',
                'name' => 'required|min:2|max:32',
                'brief_name' => 'required|min:2|max:32',
                'contact' => 'required',
                'contact_phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
                'qq' => 'numeric|regex:/^[1-9][0-9]{4,}$/',
                'email' => "required",
                'creator_uid' => "required|integer|min:1",
            ], [], Broker::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (isset($params['brokerid']) && $params['brokerid'] > 0) { //update
            $broker = Broker::find($params['brokerid']);
            $account = $broker->account;
            $user = $account->user;
            if (User::getAgencyUser('username', $params['username'], $user->user_id)) {
                return $this->errorCode(5092);
            }
            if (User::getAgencyUser('email_address', $params['email'], $user->user_id)) {
                return $this->errorCode(5093);
            }
            if (User::getAgencyUser('contact_phone', $params['contact_phone'], $user->user_id)) {
                return $this->errorCode(5094);
            }
            if (Broker::getAgencyBroker('name', $params['name'], $params['brokerid'])) {
                return $this->errorCode(5099);
            }
            if (Broker::getAgencyBroker('brief_name', $params['brief_name'], $params['brokerid'])) {
                return $this->errorCode(5102);
            }
            $broker->name = $params['name'];
            $broker->brief_name = $params['brief_name'];
            $broker->contact = $params['contact'];
            $broker->email = $params['email'];
            $broker->creator_uid = $params['creator_uid'];
            $broker->save();


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
            if (($ret = $this->validate($request, [
                    'password' => 'required|min:6',
                ], [], Broker::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            if (User::getAgencyUser('username', $params['username'])) {
                return $this->errorCode(5092);
            }
            if (User::getAgencyUser('email_address', $params['email'])) {
                return $this->errorCode(5093);
            }
            if (User::getAgencyUser('contact_phone', $params['contact_phone'])) {
                return $this->errorCode(5094);
            }
            if (Broker::getAgencyBroker('name', $params['name'])) {
                return $this->errorCode(5099);
            }
            if (Broker::getAgencyBroker('brief_name', $params['brief_name'])) {
                return $this->errorCode(5102);
            }
            $affiliateId = Auth::user()->account->affiliate->affiliateid;
            $params['affiliateid'] = $affiliateId;
            $result = AdvertiserService::store($params, 0);
            if ($result == 'success') {
                return $this->success();
            } else {
                return $this->error($result);
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

        $brokerId = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');

        $broker = Broker::find($brokerId);
        if ($broker->affiliateid != Auth::user()->account->affiliate->affiliateid) {
            return $this->errorCode(5003);
        }
        $user = $broker->account->user;
        $accountId = $broker->account_id;

        if ($field == 'password') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:6'
                ], [], Client::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            $user->password = md5($value);
            $user->save();
        } elseif ($field == 'status') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|in:0,1'
                ], [], Client::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            $broker->$field = $value;
            $user->active = $value;
            LogHelper::info('broker'. $brokerId . $field .'change from brokers_status to'. $value);
            $usersArr = User::where('default_account_id', '=', $accountId)->get();
            //暂停时，将子账号全部暂停
            if (0 < count($usersArr)) {
                foreach ($usersArr as $ku => $uf) {
                    $uf->active = $value;
                    $uf->save();
                }
            }
            $broker->save();
            $user->save();
        }

        return $this->success();
    }

    /**获取充值账号历史记录
     * @param Request $request
     * @return \Illuminate\Http\Response
     */

    public function rechargeHistory(Request $request)
    {
        if (($ret = $this->validate($request, [
                'brokerid' => 'required',
                'way' => 'required|in:0,1',
            ], [], array_merge(Recharge::attributeLabels(), Broker::attributeLabels()))) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $way = Input::get('way');
        $broker = Broker::find(Input::get('brokerid'));
        $accountId = Auth::user()->account->account_id;
        $user_id = Auth::user()->user_id;
        $agencyId = $broker->agencyid;
        $target_accountId = $broker->account_id;
        $info = RechargeService::history($accountId, $user_id, $agencyId, $target_accountId, $way);
        return $this->success(null, null, $info);
    }

    /**提交充值申请
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function rechargeApply(Request $request)
    {
        if (($ret = $this->validate($request, [
                'brokerid' => 'required',
                'way' => 'required|in:0,1',
                'account_info' => 'required',
                'date' => 'required',
                'amount' => 'required|numeric|min:0',
            ], [], array_merge(Recharge::attributeLabels(), Client::attributeLabels()))) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $broker = Broker::find(Input::get('brokerid'));
        $agencyId = $broker->agencyid;
        $param['account_info'] = Input::get('account_info');
        $param['amount'] = Input::get('amount');
        $accountId = $broker->account_id;
        $param['way'] = Input::get('way');
        $param['date'] = Input::get('date');
        $result = RechargeService::apply($agencyId, $accountId, $param, 1);
        if ($result == 'success') {
            return $this->success();
        } else {
            return $this->error($result);
        }
    }
    /**充值明细
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function rechargeDetail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'brokerid' => 'required',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');

        $brokerId = Input::get('brokerid');
        $user = Auth::user();
        $account = $user->account;
        $agencyId = $account->agencyid;
        $rows = RechargeService::getRechargeList($agencyId, 1, $brokerId, $pageNo, $pageSize, $search, $sort);
        if (!$rows) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success(null, $rows['map'], $rows['list']);
    }
    /**赠送申请
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function giftApply(Request $request)
    {
        if (($ret = $this->validate($request, [
                'brokerid' => 'required|numeric',
                'amount' => 'required|numeric|min:0',
                'gift_info' =>'required|string',
            ], [], Gift::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $brokerId = Input::get('brokerid');
        $broker = Broker::find($brokerId);
        $agencyId = $broker->agencyid;
        $accountId = $broker->account_id;
        if ($broker->agencyid != $agencyId) {
            return $this->errorCode(5202);
        }
        $gift = new Gift();
        $gift->account_id = Auth::user()->account->account_id;
        $gift->user_id = Auth::user()->user_id;
        $gift->amount = Input::get('amount');
        $gift->gift_info = Input::get('gift_info');
        $gift->agencyid = $agencyId;
        $gift->target_accountid = $accountId;
        $gift->status = 1;
        $gift->comment = '';
        $gift->type =  1; //1为代理商0为广告主
        if (!$gift->save()) {
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function giftDetail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'brokerid' => 'required|numeric',
            ], [], Broker::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $search = $request->input('search');
        $sort = $request->input('sort');
        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));

        $brokerId = Input::get('brokerid');
        $broker = Broker::find($brokerId);
        $accountId = $broker->account_id;
        $agencyId = Auth::user()->account->agencyid;
        $userId = Auth::user()->user_id;

        $rows = GiftService::getGiftList($agencyId, 0, $userId, $accountId, $pageNo, $pageSize, $search, $sort);
        if (!$rows) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success(null, $rows['map'], $rows['list']);
    }
}
