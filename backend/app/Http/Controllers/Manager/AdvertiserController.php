<?php

namespace App\Http\Controllers\Manager;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\Recharge;
use App\Models\Role;
use App\Services\AccountService;
use App\Services\AdvertiserService;
use App\Services\GiftService;
use App\Services\RechargeService;
use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use App\Models\Client;
use App\Models\Account;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Qiniu\json_decode;
use App\Models\AccountSubType;

class AdvertiserController extends Controller
{
    /**
     * 获取广告主列表
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  |  |  | -status降序，降序在字段前加- | |
     * | filter | string | 筛选内容 | Json格式：{"revenue_type":3, "clients_status":1,"creator_uid":2} |  否 |
     * | |  |  | revenue_type 计费类型 | |
     * | |  |  | clients_status状态 | |
     * | |  |  | creator_uid 销售顾问ID | |
     * | type | integer | 账号类型 | 0：直客广告主 1：获客广告主| 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主id |  | 是 |
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
     * | creator_uaername | string | 创建者名称 |  | 是 |
     * | creator_contact_phone | string | 销售顾问电话/代理商电话 |  | 是 |
     * |  |  |  | 获客广告主返回代理商信息 |  |
     * | clients_status | integer | 状态 | 1：激活； 0：停用 | 是 |
     * | date_created | date | 创建时间 |  | 是 |
     */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required|in:0,1',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $user = Auth::user();
        $account = $user->account;
        $agencyId = $account->agency->agencyid;
        if (!$account->isManager()) {
            return $this->errorCode(5003); // @codeCoverageIgnore
        }

        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $type = Input::get('type');
        $filter = json_decode($request->input('filter'), true);

        if ($this->can('manager-super-account-all')) {
            $rows = $this->getAdvertiserList(
                $agencyId,
                $type,
                0,
                $pageNo,
                $pageSize,
                $search,
                $sort,
                $filter
            );
        } elseif ($this->can('manager-super-account-self')) {
            $rows = $this->getAdvertiserList(
                $agencyId,
                $type,
                $user->user_id,
                $pageNo,
                $pageSize,
                $search,
                $sort,
                $filter
            );
        } else {
            $rows = [
                'map' => [
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize,
                    'count' => 0,
                ],
                'list' => [],
            ];
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
        if (($ret = $this->validate($request, [
                'type' => 'required|in:0,1',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $type = Input::get('type');
        if ($type == 0) {
            return $this->success(
                [
                    'creator_uid' => AccountService::getSales(AccountSubType::ACCOUNT_DEPARTMENT_SALES),
                    'operation_uid' => AccountService::getSales(AccountSubType::ACCOUNT_DEPARTMENT_OPERATION),
                ],
                null,
                null
            );
        } else {
            $brokers = ArrayHelper::map(DB::table('brokers')->get(), 'brokerid', 'brief_name');
            return $this->success(
                [
                    'creator_uid' => AccountService::getSales(AccountSubType::ACCOUNT_DEPARTMENT_SALES),
                    'operation_uid' => AccountService::getSales(AccountSubType::ACCOUNT_DEPARTMENT_OPERATION),
                    'broker_id' => $brokers,
                ],
                null,
                null
            );
        }
    }

    /**
     * 广告主信息修改/暂停/启用
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主id |  | 是 |
     * | field | string | 修改字段 | 广告主名称，简称 | 是 |
     * | field | string | 修改字段 | 联系人，邮箱，手机号，状态可修改 | 是 |
     * | |  |  | client_name,brief_name,contact_name,email,phone,qq,creator_uid,clients_status,website | |
     * | |  |  | business_license, network_business_license, operation_uid| |
     * | value | string | 修改后的值 |  |  是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required|integer',
                'field' => 'required',
                'value' => 'required'
            ], [], array_merge(Client::attributeLabels(), User::attributeLabels()))) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $clientId = $request->get('clientid');
        $field = $request->get('field');
        $value = $request->get('value');

        if ($field == 'clientname') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            if (Client::getAgencyClient('clientname', $value, 0, 0)) {
                return $this->errorCode(5096);
            }
        } elseif ($field == 'brief_name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            if (Client::getAgencyClient('brief_name', $value, 0, 0)) {
                return $this->errorCode(5095);
            }
        } elseif ($field == 'contact') {
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'email') {
            // 格式不对
            if (!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $value)) {
                return $this->errorCode(5018);
            }
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'contact_phone') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'qq' || $field == 'revenue_type') {
            if (($ret = $this->validate($request, [
                    'value' => "required"
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'client_status') {
            $status = ArrayHelper::getRequiredIn(Client::getStatusLabel());
            if (($ret = $this->validate($request, [
                    'value' => "required|in:{$status}"
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'website') {
            if (($ret = $this->validate($request, [
                    'value' => "required"
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            //检查输入的网址长度
            if (192 < strlen($value)) {
                return $this->errorCode(7001);
            }
        } elseif ($field == 'address') {
            if (($ret = $this->validate($request, [
                    'value' => "required"
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            //检查输入的网址长度
            if (192 < strlen($value)) {
                return $this->errorCode(7001);
            }
        } elseif ($field == 'qualifications') {
            if (($ret = $this->validate($request, [
                    'value' => "required"
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }

            $data = json_decode($value, true);
            $business_license = isset($data['business_license']) ? $data['business_license'] : '';
            $network_business_license = isset($data['network_business_license']) ?
                $data['network_business_license'] : '';
            if (!empty($business_license)) {
                $arr['business_license'] = [
                    'image' => $business_license,
                    'md5' => md5_file($business_license)
                ];
            } else {
                $arr['business_license'] = [
                    'image' => '',
                    'md5' => ''
                ];
            }

            if (!empty($network_business_license)) {
                $arr['network_business_license'] = [
                    'image' => $network_business_license,
                    'md5' => md5_file($network_business_license)
                ];
            } else {
                $arr['network_business_license'] = [
                    'image' => '',
                    'md5' => ''
                ];
            }
            $value = json_encode($arr);
            
            AccountService::updateAdxAdvertiser($clientId);
        }

        $client = Client::find($clientId);
        $userId = $client->account->user->user_id;
        $accountId = $client->account_id;
        $user = User::find($userId);
        if ($field == 'contact_phone' || $field == 'qq') {
            $user->$field = $value;
            LogHelper::info('User' . $clientId . $field . 'change from contact_phone to' . $value);
        } else {
            if ($field == 'contact') {
                $client->$field = $value;
                $user->contact_name = $value;
                LogHelper::info('client' . $clientId . $field . 'change from contact to' . $value);
            } elseif ($field == 'clients_status') {
                //启用，暂停广告主
                $client->$field = $value;
                $user->active = $value;
                LogHelper::info('client' . $clientId . $field . 'change from clients_status to' . $value);
                //暂停时，将子账号全部暂停
                if ($value == Client::STATUS_DISABLED) {
                    $usersArr = User::where('default_account_id', '=', $accountId)->get();
                    if (0 < count($usersArr)) {
                        foreach ($usersArr as $ku => $uf) {
                            $uf->active = $value;
                            $uf->save();
                        }
                    }
                }
            } elseif ($field == 'email') {
                $client->$field = $value;
                $user->email_address = $value;
                LogHelper::info('client' . $clientId . $field . 'change from email to ' . $value);
            } else {
                $client->$field = $value;
                LogHelper::info('client' . $clientId . $field . 'change from ' . $client->$field . ' to ' . $value);
            }
            if (!$client->save()) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
        }
        if (!$user->save()) {
            return $this->errorCode(5001);// @codeCoverageIgnore
        }
        return $this->success();
    }

    /**
     * 广告主新建
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | brief_name | string | 广告主简称 |  | 是 |
     * | clientname | string | 广告主名称 |  | 是 |
     * | contact | string | 联系人 |  | 是 |
     * | contact_phone | string | 手机号 |  | 是 |
     * | creator_uid | string | 销售顾问uid |  | |
     * | email | string | 邮箱 |  | 是 |
     * | password | string | 初始密码 |  | 是 |
     * | qq | string | QQ号码 |  | 否 |
     * | revenue_type | integer | 计费类型 |  | 是 |
     * | username | string | 登录账号 |  | 是 |
     * | website | string | 网址 | | 否 |
     * | business_license | string | 营业执照 | | 否 |
     * | network_business_license | string | 网络文化经营许可证 | | 否 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'username' => 'required|min:2|max:32',
                'password' => 'required|min:6',
                'clientname' => 'required|min:2|max:32',
                'brief_name' => 'required|min:2|max:32',
                'contact' => 'required',
                'contact_phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
                'qq' => 'numeric|regex:/^[1-9][0-9]{4,}$/',
                'email' => "required|email",
                'creator_uid' => "required|integer|min:1",
                'operation_uid' => "required|integer|min:1",
                'revenue_type' => 'required',
            ], [], array_merge(Client::attributeLabels(), User::attributeLabels())))!== true) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (Client::getAgencyClient('clientname', $params['clientname'], 0, 0)) {
            return $this->errorCode(5096);
        }
        if (Client::getAgencyClient('brief_name', $params['brief_name'], 0, 0)) {
            return $this->errorCode(5095);
        }
        if (User::getAgencyUser('username', $params['username'])) {
            return $this->errorCode(5092);
        }
        //处理图片，图片加上 md5
        $data = json_decode($params['qualifications'], true);
        $business_license = isset($data['business_license']) ? $data['business_license'] : '';
        $network_business_license = isset($data['network_business_license']) ? $data['network_business_license'] : '';
        if (!empty($business_license)) {
            $arr['business_license'] = [
                'image' => $business_license,
                'md5' => md5_file($business_license)
            ];
        } else {
            $arr['business_license'] = [
                'image' => '',
                'md5' => ''
            ];
        }
        
        if (!empty($network_business_license)) {
            $arr['network_business_license'] = [
                'image' => $network_business_license,
                'md5' => md5_file($network_business_license)
            ];
        } else {
            $arr['network_business_license'] = [
                'image' => '',
                'md5' => ''
            ];
        }
        $params['qualifications'] = json_encode($arr);
        $result = AdvertiserService::store($params, 1);
        if ($result == 'success') {
            return $this->success();
        } else {
            return $this->errorCode($result);
        }
    }

    /**
     * 获取充值账号历史记录
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | type | integer | 账号类型 | 0：直客广告主 | 是 |
     * | |  |  | 1：获客广告主 | |
     * | |  |  | 2：代理商 | |
     * | clientid | integer | 广告主id |  | 是 |
     * | way | integer | 充值方式 | 0：支付宝 1：对公银行卡| 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | account_info | string | 账号 |  | 是 |
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
        if (!$this->can('manager-advertiser') ||
            (!Auth::user()->account->isTrafficker() && !Auth::user()->account->isManager())
        ) {
            return $this->errorCode(5004);
        }
        $way = Input::get('way');
        $client = Client::find(Input::get('clientid'));
        $agencyId = Auth::user()->account->agency->agencyid;
        $accountId = Auth::user()->account->account_id;
        $user_id = Auth::user()->user_id;
        $target_accountId = $client->account_id;
        $info = RechargeService::history($accountId, $user_id, $agencyId, $target_accountId, $way);
        return $this->success(null, null, $info);
    }

    /**
     * 提交充值申请
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | type | integer | 账号类型 | 0：直客广告主 | 是 |
     * | |  |  | 1：获客广告主 | |
     * | |  |  | 2：代理商 | |
     * | clientid | integer | 广告主id |  | 是 |
     * | way | integer | 充值方式 | 0：支付宝 | 是 |
     * | |  |  | 1：对公银行卡 | |
     * | account_info | string | 充值账号 |  | 是 |
     * | date | date | 充值时间 |  | 是 |
     * | amount | string | 充值金额 |  | |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function rechargeApply(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required',
                'way' => 'required|in:0,1',
                'account_info' => 'required',
                'date' => 'required',
                'amount' => 'required|numeric|min:0',
            ], [], array_merge(Recharge::attributeLabels(), Client::attributeLabels()))) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //判断是否有充值申请权限
        if (!$this->can('manager-advertiser') ||
            (!Auth::user()->account->isTrafficker() && !Auth::user()->account->isManager())
        ) {
            return $this->errorCode(5004);
        }
        $client = Client::find(Input::get('clientid'));
        $agencyId = Auth::user()->account->agency->agencyid;
        $accountId = $client->account_id;
        $param['way'] = Input::get('way');
        $param['account_info'] = Input::get('account_info');
        $param['amount'] = Input::get('amount');
        $param['date'] = Input::get('date');
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
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | type | integer | 账号类型 | 0：直客广告主 | 是 |
     * | |  |  | 1：获客广告主 | |
     * | |  |  | 2：代理商 | |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 |  | 否 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | amount | num | 充值金额 |  | 是 |
     * | clientname | string | 广告主名称 |  | 是 |
     * | comment | string | 驳回原因 |  | 驳回状态时必选 |
     * | contact_name | string | 申请人 |  | 是 |
     * | date | date | 充值时间 |  | 是 |
     * | status | integer | 状态 | 1：待审核 2：审核通过 3：已驳回| 是 |
     */
    public function rechargeDetail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required',
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

        $clientId = Input::get('clientid');
        $user = Auth::user();
        $account = $user->account;
        $agencyId = $account->agency->agencyid;
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
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | amount | num | 赠送金额 |  | 是
     * | clientid | string | 广告主id |  | 是
     * | gift_info | string | 说明 |  | 是
     * | type | integer | 账号类型 | 0：直客广告主 1：获客广告主 2：代理商 | 是
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
        if (!$this->can('manager-advertiser')) {
            return $this->errorCode(5001);
        }
        $clientId = Input::get('clientid');
        $client = Client::find($clientId);
        $agencyId = Auth::user()->account->agency->agencyid;
        $accountId = $client->account_id;
        if ($client->agencyid != $agencyId) {
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
        $gift->type =  0; //1为代理商0为广告主
        if (!$gift->save()) {
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success();
    }

    /**
     * 赠送明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | type | integer | 账号类型 | 0：直客广告主 1：获客广告主 2：代理商| 是 |
     * | clientid | string | 广告主id |  | 是 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 |  | 否 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | created_at | date | 申请时间 |  | 是 |
     * | contact | string | 广告主名称 |  赠送对象 | 是 |
     * | amount | num | 赠送金额 |  | 是 |
     * | gift_info | string | 赠送说明 |  | 是 |
     * | contact_name | string | 申请人 |  | 是 |
     * | status | integer | 状态 | 1：待审核 2：审核通过 3：已驳回| 是 |
     * | comment | string | 驳回原因 |  | 驳回状态时必选 |
     */
    public function giftDetail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required|numeric',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $clientId = Input::get('clientid');
        $agencyId = Auth::user()->account->agency->agencyid;
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
     * 获取媒体商用户
     * @param integer $agencyId
     * @param integer $userId
     * @param int $pageNo
     * @param int $pageSize
     * @param null $search
     * @param null $sort
     * @param string $filter
     * @return array
     */
    protected function getAdvertiserList(
        $agencyId,
        $type,
        $userId,
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null,
        $filter = null
    ) {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('clients')
            ->leftJoin('balances', 'clients.account_id', '=', 'balances.account_id')
            ->leftJoin('accounts', 'clients.account_id', '=', 'accounts.account_id')
            ->select(
                'clients.clientid',
                'clients.clientname',
                'clients.brief_name',
                'clients.contact',
                'clients.email',
                'clients.clients_status',
                'clients.revenue_type',
                'clients.website',
                'clients.address',
                'clients.qualifications'
            )
            ->where('clients.agencyid', $agencyId)
            ->where('clients.affiliateid', '=', 0)
            ->whereNull('clients.deleted_at');
        $prefix = DB::getTablePrefix();
        if ($type == 0) {
            $select->leftJoin('users', 'accounts.manager_userid', '=', 'users.user_id')
                ->addselect(
                    'clients.creator_uid',
                    'clients.operation_uid',
                    'users.username',
                    'users.user_id',
                    'users.qq',
                    'users.contact_phone',
                    'balances.balance',
                    'balances.gift',
                    DB::raw('(' . $prefix . 'balances.balance + ' . $prefix . 'balances.gift) as total'),
                    'users.date_created'
                );
        } else {
            $select->leftJoin('users', 'accounts.manager_userid', '=', 'users.user_id')
                ->leftJoin('brokers', 'clients.broker_id', '=', 'brokers.brokerid')
                ->addselect(
                    'brokers.creator_uid',
                    'brokers.operation_uid',
                    'brokers.brief_name as broker_brief_name',
                    'users.username',
                    'users.user_id',
                    'users.qq',
                    'users.contact_phone',
                    'balances.balance',
                    'balances.gift',
                    DB::raw('(' . $prefix . 'balances.balance + ' . $prefix . 'balances.gift) as total'),
                    'users.date_created'
                );
        }

        // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where(function ($select) use ($search) {
                $select->where('clients.clientname', 'like', '%' . $search . '%');
                $select->orWhere('clients.brief_name', 'like', '%' . $search . '%');
                $select->orWhere('users.contact_name', 'like', '%' . $search . '%');
                $select->orWhere('users.username', 'like', '%' . $search . '%');
                $select->orWhere('clients.email', 'like', '%' . $search . '%');
            });
        }

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'revenue_type') {
                        $select->where('clients.' . $k, '&', $v);
                    } elseif ($k == 'creator_uid' && $type == 1) {
                        $select->where('brokers.' . $k, $v);
                    } else {
                        $select->where('clients.' . $k, $v);
                    }
                }
            }
        }

        //直客广告主
        if ($type == 0) {
            $select->where('clients.affiliateid', '=', 0);
            $select->where('clients.broker_id', '=', 0);
            if ($userId) {
                $select->where('clients.creator_uid', '=', $userId);
            }
        }
        //获客广告主
        if ($type == 1) {
            $select->where('clients.affiliateid', '=', 0);
            $select->where('clients.broker_id', '>', 0);
            if ($userId) {
                $select->where('brokers.creator_uid', '=', $userId);
            }
        }

        // 分页
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        //排序
        if ($sort) {
            $sortType = 'asc';
            if (strncmp($sort, '-', 1) === 0) {
                $sortType = 'desc';
            }
            $sortAttr = str_replace('-', '', $sort);
            $select->orderBy($sortAttr, $sortType);
        } else {
            $select->orderBy('clients_status', 'desc')->orderBy('clientid', 'desc');
        }

        $rows = $select->get();
        $list = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $creator = User::find($row['creator_uid']);
                $row['creator_username'] = $creator['contact_name'];
                $row['creator_contact_phone'] = $creator['contact_phone'];

                //如果运营人员的id大于0
                if (0 < $row['operation_uid']) {
                    $operation = User::find($row['operation_uid']);
                    $row['operation_username'] = $operation['contact_name'];
                    $row['operation_contact_phone'] = $operation['contact_phone'];
                } else {
                    $row['operation_username'] = '-';
                    $row['operation_contact_phone'] = '-';
                }
                $list[] = $row;
            }
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
}
