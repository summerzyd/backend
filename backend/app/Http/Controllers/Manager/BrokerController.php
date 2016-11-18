<?php

namespace App\Http\Controllers\Manager;

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
use App\Models\AccountSubType;

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
        $agencyId = $account->agency->agencyid;

        if (!Auth::user()->account->isManager()) {
            return $this->errorCode(5003); // @codeCoverageIgnore
        }

        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);

        if ($this->can('manager-super-account-all')) {
            $rows = BrokerService::getBrokerList($agencyId, 0, 0, $pageNo, $pageSize, $search, $sort, $filter);
        } else {
            $rows = BrokerService::getBrokerList(
                $agencyId,
                $user->user_id,
                0,
                $pageNo,
                $pageSize,
                $search,
                $sort,
                $filter
            );
        }
        if (!$rows) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }

        return $this->success(null, $rows['map'], $rows['list']);
    }

    /**获取筛选
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request)
    {
        return $this->success(
            [
                'creator_uid' => AccountService::getSales(AccountSubType::ACCOUNT_DEPARTMENT_SALES),
                'operation_uid' => AccountService::getSales(AccountSubType::ACCOUNT_DEPARTMENT_OPERATION),
            ],
            null,
            null
        );
    }

    /**
     * 平台代理商信息修改
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (($ret = $this->validate($request, [
                'brokerid' => 'required|integer',
                'field' => 'required',
                'value' => 'required',
            ], [], array_merge(broker::attributeLabels(), User::attributeLabels()))) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $brokerId = $request->get('brokerid');
        $field = $request->get('field');
        $value = $request->get('value');

        if ($field == 'name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32'
                ], [], Broker::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            if (Broker::getAgencyBroker('name', $value)) {
                return $this->errorCode(5099);
            }
        } elseif ($field == 'brief_name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32'
                ], [], Broker::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            if (Broker::getAgencyBroker('brief_name', $value)) {
                return $this->errorCode(5102);
            }
        } elseif ($field == 'contact') {
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], Broker::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'email') {
            // 格式不对
            if (!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $value)) {
                return $this->errorCode(5018);
            }
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], Broker::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            if (User::getAgencyUser('email_address', $value)) {
                return $this->errorCode(5093);
            }
        } elseif ($field == 'contact_phone') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/'
                ], [], Broker::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            if (User::getAgencyUser('contact_phone', $value)) {
                return $this->errorCode(5094);
            }
        } elseif ($field == 'qq') {
            if (($ret = $this->validate($request, [
                    'value' => "required"
                ], [], Broker::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'status') {
            $status = ArrayHelper::getRequiredIn(Broker::getStatusLabel());
            if (($ret = $this->validate($request, [
                    'value' => "required|in:{$status}"
                ], [], Broker::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'revenue_type') {
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], Broker::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        }
        $broker = Broker::find($brokerId);
        $accountId = $broker->account_id;
        $userId = $broker->account->user->user_id;
        $user = User::find($userId);
        if ($field == 'contact_phone' || $field == 'qq') {
            $user->$field = $value;
            LogHelper::info('User'. $brokerId . $field .'change from contact_phone to'. $value);
        } else {
            if ($field == 'contact') {
                $broker->$field = $value;
                $user->contact_name = $value;
                LogHelper::info('broker'. $brokerId . $field .'change from contact to'. $value);
            } elseif ($field == 'status') {
                //启用，暂停代理商
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
            } elseif ($field == 'email') {
                $broker->$field = $value;
                $user->email_address = $value;
                LogHelper::info('broker'. $brokerId . $field .'change from email to ' . $value);
            } else {
                $broker->$field = $value;
                LogHelper::info('broker'. $brokerId . $field .'change from ' . $broker->$field . ' to '. $value);
            }
            if (!$broker->save()) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
        }
        if (!$user->save()) {
            return $this->errorCode(5001);// @codeCoverageIgnore
        }
        return $this->success();
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
                'password' => 'required|min:6',
                'name' => 'required|min:2|max:32',
                'brief_name' => 'required|min:2|max:32',
                'contact' => 'required',
                'contact_phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
                'qq' => 'numeric|regex:/^[1-9][0-9]{4,}$/',
                'email' => "required",
                'creator_uid' => "required|integer|min:1",
                'operation_uid' => "required|integer|min:1",
                'revenue_type' => 'required',
            ], [], Broker::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (Broker::getAgencyBroker('name', $params['name'])) {
            return $this->errorCode(5099);
        }
        if (Broker::getAgencyBroker('brief_name', $params['brief_name'])) {
            return $this->errorCode(5102);
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
        $result = AdvertiserService::store($params, 0);
        if ($result == 'success') {
            return $this->success();
        } else {
            return $this->error($result);
        }
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
        //判断是否有充值申请权限
        if (!$this->can('manager-recharge') ||
            (!Auth::user()->account->isTrafficker() && !Auth::user()->account->isManager())
        ) {
            return $this->errorCode(5004);
        }
        $way = Input::get('way');
        $broker = Broker::find(Input::get('brokerid'));
        $agencyId = Auth::user()->account->agency->agencyid;
        $accountId = Auth::user()->account->account_id;
        $user_id = Auth::user()->user_id;
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
        //判断是否有充值申请权限
        if (!$this->can('manager-broker') ||
            (!Auth::user()->account->isTrafficker() && !Auth::user()->account->isManager())
        ) {
            return $this->errorCode(5004);
        }
        $broker = Broker::find(Input::get('brokerid'));
        $agencyId = Auth::user()->account->agency->agencyid;
        $accountId = $broker->account_id;
        $param['way'] = Input::get('way');
        $param['account_info'] = Input::get('account_info');
        $param['amount'] = Input::get('amount');
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
        if (!Auth::user()->account->isManager()) {
            return $this->errorCode(5001);
        }
        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');

        $brokerId = Input::get('brokerid');
        $user = Auth::user();
        $account = $user->account;
        $agencyId = $account->agency->agencyid;
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
        if (!$this->can('manager-broker')) {
            return $this->errorCode(5001);
        }
        $brokerId = Input::get('brokerid');
        $broker = Broker::find($brokerId);
        $agencyId = Auth::user()->account->agency->agencyid;
        $accountId = $broker->account_id;
        if ($broker->agencyid != $agencyId) {
            return $this->errorCode(5202);
        }
        $gift = new Gift();
        $gift->account_id = Auth::user()->account->account_id;
        $gift->user_id = Auth::user()->user_id;
        $gift->agencyid = $agencyId;
        $gift->target_accountid = $accountId;
        $gift->amount = Input::get('amount');
        $gift->gift_info = Input::get('gift_info');
        $gift->status = 1;
        $gift->comment = '';
        $gift->type =  1; //1为代理商0为广告主
        if (!$gift->save()) {
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success();
    }
    public function giftDetail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'brokerid' => 'required|numeric',
            ], [], Broker::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');

        $brokerId = Input::get('brokerid');
        $agencyId = Auth::user()->account->agency->agencyid;
        $userId = Auth::user()->user_id;
        $broker = Broker::find($brokerId);
        $accountId = $broker->account_id;

        $rows = GiftService::getGiftList($agencyId, 0, $userId, $accountId, $pageNo, $pageSize, $search, $sort);
        if (!$rows) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success(null, $rows['map'], $rows['list']);
    }
}
