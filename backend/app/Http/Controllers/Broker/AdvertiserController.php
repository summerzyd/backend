<?php

namespace App\Http\Controllers\Broker;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Models\Account;
use App\Models\Balance;
use App\Models\BalanceLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\OperationLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AdvertiserService;
use App\Services\CampaignService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Components\Config;

class AdvertiserController extends Controller
{

    /**
     * 获取用户列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | search |  | string | 搜索关键字 |  | 是 |
     * | sort |  | string | 排序字段 |  | 是 |
     *
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | client_id |  | integer | 广告主id |  | 是 |
     * | clientname |  | string | 广告主名称 |  | 是 |
     * | brief_name |  | string | 广告主简称 |  | 是 |
     * | username |  | string | 登录账号 |  | 是 |
     * | contact |  | string | 联系人 |  | 是 |
     * | email |  | string | 邮箱 |  | 是 |
     * | phone |  | string | 手机号 |  | 是 |
     * | balance |  | decimal | 推广金金额 |  | 是 |
     * | gift |  | decimal | 赠送金金额 |  | 是 |
     * | total |  | decimal | 总余额 |  | 是 |
     * | clients_status |  | int | 状态 | 1：激活； 0：停用 | 是 |
     * | clients_status_label |  | string | 状态 |  | 是 |
     * | comment |  | string | 暂停原因 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * | qualifications |  | string | 资质|  | 是 |
     * | address |  | string | 地址 |  | 是 |
     */
    public function index(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');

        $info = AdvertiserService::getUserList($pageNo, $pageSize, $search, $sort);
        if (!$info) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }

        return $this->success(null, $info['map'], $info['list']);
    }

    /**
     * 代理商创建广告主
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientname |  | string | 广告主名称 |  | 是 |
     * | brief_name |  | string | 广告主简称 |  | 是 |
     * | username |  | string | 登录账号 |  | 是 |
     * | password |  | string | 初始密码 |  | 是 |
     * | contact |  | string | 联系人 |  | 是 |
     * | email |  | string | 邮箱 |  | 是 |
     * | phone |  | string | 手机号 |  | 是 |
     * | qq |  | string | QQ号码 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientname' => 'required|min:2|max:96',
                'brief_name' => 'required|min:2|max:96',
                'username' => 'required',
                'password' => 'required|between:6,16',
                'email' => "required|email",
                'contact' => 'required',
                'phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
                'qq' => 'max:64',
                'revenue_type' => 'required',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        $brokerId = isset(Auth::user()->account->broker->brokerid) ? Auth::user()->account->broker->brokerid : 0;
        $affiliateId = isset(Auth::user()->account->broker->affiliateid)
            ? Auth::user()->account->broker->affiliateid : 0;
        if (Client::getAgencyClient('clientname', $params['clientname'], $brokerId, $affiliateId)) {
            return $this->errorCode(5096);
        }
        if (Client::getAgencyClient('brief_name', $params['brief_name'], $brokerId, $affiliateId)) {
            return $this->errorCode(5095);
        }
        if (User::getAgencyUser('username', $params['username'])) {
            return $this->errorCode(5092);
        }

        DB::beginTransaction();//事务开始
        //先创建账号
        $account = Account::store($params['username']);
        if (!$account) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }

        $agencyId = Auth::user()->account->broker->agencyid;
        $params['agencyid'] = $agencyId;
        $params['account_id'] = $account->account_id;
        $params['broker_id'] = Auth::user()->account->broker->brokerid;
        $params['creator_uid'] = Auth::user()->user_id;
        //创建广告主信息
        $client = Client::store($params);
        if (!$client) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }

        $balance = Balance::find($account->account_id);
        if (!$balance) {
            // @codeCoverageIgnoreStart
            $result = Balance::create([
                'account_id' => $account->account_id,
                'balance' => 0,
                'gift' => 0,
            ]);
            if (!$result) {
                return $this->errorCode(5001);
            }
            // @codeCoverageIgnoreEnd
        }

        //新增用户信息
        $user = User::store($account->account_id, 0, $params);
        if (!$user) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }

        // account的manager_userid设置为该账号
        $account->manager_userid = $user->user_id;
        if (!$account->save()) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }

        //最后存 account_user_assoc关联表
        $user->accounts()->attach($account->account_id, ['linked' => date('Y-m-d h:i:s')]);

        $defaultRoleId = Config::get('default_client_role', Auth::user()->agencyid);//获取最新的权限id
        //创建广告主权限
        $role = Role::brokerStore($defaultRoleId);
        if (!$role) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }
        //更新用户权限
        $user->role_id = $role->id;
        if (!$user->save()) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }

        DB::commit();//事务结束

        return $this->success();
    }

    /**
     * 更新用户字段
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | client_id |  | integer | 广告主id |  | 是 |
     * | field |  | string | 修改字段 |  | 是 |
     * | value |  | string | 修改后的值 |  | 是 |
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $brokerId = isset(Auth::user()->account->broker->brokerid) ? Auth::user()->account->broker->brokerid : 0;
        $affiliateId = isset(Auth::user()->account->broker->affiliateid)
            ? Auth::user()->account->broker->affiliateid : 0;

        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'client_id' => 'required|integer',
                'field' => 'required',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->get('client_id');
        $field = $request->get('field');
        $value = $request->get('value');

        // 当更新权限时，上传的id是role表的id
        if ($field == 'clientname') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:96'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            if (Client::getAgencyClient('clientname', $value, $brokerId, $affiliateId)) {
                return $this->errorCode(5096);
            }
        } elseif ($field == 'brief_name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:96'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            if (Client::getAgencyClient('brief_name', $value, $brokerId, $affiliateId)) {
                return $this->errorCode(5095);
            }
        } elseif ($field == 'contact') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|max:96'
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
        } elseif ($field == 'phone') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'clients_status') {
            $status = ArrayHelper::getRequiredIn(Client::getStatusLabel());
            if (($ret = $this->validate($request, [
                    'value' => "required|in:{$status}"
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'revenue_type') {
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], Client::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }

        $client = Client::find($id);
        $userId = $client->account->user->user_id;
        $user = User::find($userId);
        if ($field == 'phone') {
            $user->contact_phone = $value;
            LogHelper::info('User' . $id . $field . 'change from contact_phone to' . $value);
        } else {
            if ($field == 'contact') {
                $client->$field = $value;
                $user->contact_name = $value;
                LogHelper::info('client' . $id . $field . 'change from contact to' . $value);
            } elseif ($field == 'clients_status') {
                //启用，暂停广告主
                $client->$field = $value;
                $user->active = $value;
                LogHelper::info('client' . $id . $field . 'change from clients_status to' . $value);
            } elseif ($field == 'email') {
                $client->$field = $value;
                $user->email_address = $value;
                LogHelper::info('client' . $id . $field . 'change from email to' . $value);
            } else {
                $client->$field = $value;
                LogHelper::info('client' . $id . $field . 'change from ' . $client->$field . ' to' . $value);
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
     * 代理划账余额
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | client_id |  | integer | 广告主id |  | 是 |
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | broker_balance |  | decimal | 代理商充值余额 |  | 是 |
     * | broker_gift |  | decimal | 代理商赠送余额 |  | 是 |
     * | client_balance |  | decimal | 广告主充值余额 |  | 是 |
     * | client_gift |  | decimal | 广告主赠送余额 |  | 是 |
     */
    public function balanceValue(Request $request)
    {
        if (($ret = $this->validate($request, [
                'client_id' => 'required|integer',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //广告主信息
        $client = Client::find($request->input('client_id'));
        //广告主管理账户不是当前登录用户
        if (Auth::user()->user_id != $client->creator_uid) {
            return $this->errorCode(5003);// @codeCoverageIgnore
        }
        //代理余额
        $brokerBalance = Balance::find(Auth::user()->default_account_id);
        //广告主余额
        $clientBalance = Balance::find($client->account_id);
        return $this->success([
            'broker_balance' => Formatter::asDecimal($brokerBalance->balance),
            'broker_gift' => Formatter::asDecimal($brokerBalance->gift),
            'client_balance' => Formatter::asDecimal($clientBalance->balance),
            'client_gift' => Formatter::asDecimal($clientBalance->gift),
        ]);
    }

    /**
     * 代理商划账
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | client_id |  | integer | 广告主id |  | 是 |
     * | account_type |  | integer | 账户类型 | 1：充值账户；2：赠送账户 | 是 |
     * | action |  | integer | 划账方向 | 1：代理商 → 广告主；2：广告主 → 代理商 | 是 |
     * | balance |  | decimal | 划账金额 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function transfer(Request $request)
    {
        $action = ArrayHelper::getRequiredIn(BalanceLog::getPayTypeLabel());
        $accountType = ArrayHelper::getRequiredIn(BalanceLog::getAccountTypeLabel());
        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'client_id' => 'required|integer',
                'account_type' => "required|integer|in:{$accountType}",
                'action' => "required|integer|in:{$action}",
                'balance' => 'required',
            ], [], Client::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        $userId = Auth::user()->user_id;
        $clientId = $params['client_id'];//广告主ID
        //划账必须是代理商下的广告主
        $client = Client::where('clientid', $clientId)->first();
        if ($client->creator_uid != $userId) {
            return $this->errorCode(5003);
        }
        $broker = Auth::user()->account->broker;
        if (!$broker) {
            return $this->errorCode(5001);
        }
        //代理商到广告主
        if ($params['action'] == BalanceLog::ACTION_BROKER_TO_ADVERTISER) {
            //代理商账户余额
            $balance = Balance::find($broker->account_id);
            if (!$balance) {
                return $this->errorCode(5001);
            }
            if (BalanceLog::ACCOUNT_TYPE_GOLD == $params['account_type']) {
                if (floatval($params['balance']) > floatval($balance->balance)) {
                    return $this->errorCode(5032);
                }
            } elseif (BalanceLog::ACCOUNT_TYPE_GIVE == $params['account_type']) {
                if (floatval($params['balance']) > floatval($balance->gift)) {
                    return $this->errorCode(5033);
                }
            }
        } else {
            $balance = Balance::find($client->account_id);
            if (!$balance) {
                return $this->errorCode(5001);
            }
            if (BalanceLog::ACCOUNT_TYPE_GOLD == $params['account_type']) {
                if (floatval($params['balance']) > floatval($balance->balance)) {
                    $params['balance'] = floatval($balance->balance);
                }
            } elseif (BalanceLog::ACCOUNT_TYPE_GIVE == $params['account_type']) {
                if (floatval($params['balance']) > floatval($balance->gift)) {
                    $params['balance'] = floatval($balance->gift);
                }
            }
        }

        //开始划账，写入财务记录
        DB::beginTransaction();  //事务开始
        $brokerBalance = Balance::find($broker->account_id);

        if ($params['action'] == BalanceLog::ACTION_ADVERTISER_TO_BROKER) {
            //代理商加余额
            if ($params['account_type'] == BalanceLog::ACCOUNT_TYPE_GOLD) {
                $brokerBalance->balance = floatval($brokerBalance->balance) + floatval($params['balance']);
            } else {
                $brokerBalance->gift = floatval($brokerBalance->gift) + floatval($params['balance']);
            }
        } else {
            //代理商减余额
            if ($params['account_type'] == BalanceLog::ACCOUNT_TYPE_GOLD) {
                $brokerBalance->balance = floatval($brokerBalance->balance) - floatval($params['balance']);
            } else {
                $brokerBalance->gift = floatval($brokerBalance->gift) - floatval($params['balance']);
            }
        }
        if (!$brokerBalance->save()) {
            DB::rollback();
            return 5001;
        }
        $clientBalance = Balance::find($client->account_id);
        if ($params['action'] == BalanceLog::ACTION_ADVERTISER_TO_BROKER) {
            //广告主增减余额
            if ($params['account_type'] == BalanceLog::ACCOUNT_TYPE_GOLD) {
                $clientBalance->balance = floatval($clientBalance->balance) - $params['balance'];
            } else {
                $clientBalance->gift = floatval($clientBalance->gift) - floatval($params['balance']);
            }
        } else {
            //广告主增加余额
            if ($params['account_type'] == BalanceLog::ACCOUNT_TYPE_GOLD) {
                $clientBalance->balance = floatval($clientBalance->balance) + floatval($params['balance']);
            } else {
                $clientBalance->gift = floatval($clientBalance->gift) + floatval($params['balance']);
            }
        }
        if (!$clientBalance->save()) {
            DB::rollback();
            return 5001;
        }

        $data = [
            'media_id' => $broker->agencyid,
            'operator_accountid' => $broker->account_id,
            'operator_userid' => Auth::user()->user_id,
            'balance_type' => $params['account_type'] == BalanceLog::ACCOUNT_TYPE_GOLD ?
                BalanceLog::BALANCE_TYPE_GOLD_ACCOUNT : BalanceLog::BALANCE_TYPE_GIVE_ACCOUNT,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        if ($params['action'] == BalanceLog::ACTION_ADVERTISER_TO_BROKER) {
            $data['pay_type'] = $params['account_type'] == BalanceLog::ACCOUNT_TYPE_GOLD ?
                BalanceLog::PAY_TYPE_GOLD_ADVERTISER_TO_BROKER :
                BalanceLog::PAY_TYPE_GIVE_ADVERTISER_TO_BROKER;
        } else {
            $data['pay_type'] = $params['account_type'] == BalanceLog::ACCOUNT_TYPE_GOLD ?
                BalanceLog::PAY_TYPE_GOLD_BROKER_TO_ADVERTISER :
                BalanceLog::PAY_TYPE_GIVE_BROKER_TO_ADVERTISER;
        }
        //代理商写入财务负记录
        $brokerData = [
            'balance' => $brokerBalance->balance,
            'target_acountid' => $broker->account_id,
        ];
        if ($params['action'] == BalanceLog::ACTION_ADVERTISER_TO_BROKER) {
            $brokerData['amount'] = floatval($params['balance']);
            $brokerData['comment'] = '从"' . $client->clientname . '"划入';

        } else {
            $brokerData['amount'] = -floatval($params['balance']);
            $brokerData['comment'] = '划给"' . $client->clientname . '"';
        }
        $brokerData = array_merge($data, $brokerData);
        $result = BalanceLog::create($brokerData);
        if (!$result) {
            DB::rollback();
            return 5001;
        }
        //广告主增加财务正记录
        $clientData = [
            'balance' => $clientBalance->balance,
            'target_acountid' => $client->account_id,
        ];
        if ($params['action'] == BalanceLog::ACTION_ADVERTISER_TO_BROKER) {
            $clientData['amount'] = -floatval($params['balance']);
            $clientData['comment'] = '划给代理商';
        } else {
            $clientData['amount'] = floatval($params['balance']);
            $clientData['comment'] = '代理商划账' .
                ($params['account_type'] == BalanceLog::ACCOUNT_TYPE_GOLD ? '充值' : '赠送');
        }
        $clientData = array_merge($data, $clientData);
        $result = BalanceLog::create($clientData);
        if (!$result) {
            DB::rollback();
            return 5001;
        }

        //代理商给广告主划账充值时启动广告
        if ($params['action'] == BalanceLog::ACTION_BROKER_TO_ADVERTISER) {
            $campaigns = Campaign::where('clientid', $client->clientid)
                ->select('campaignid', 'status', 'pause_status')
                ->get();
            foreach ($campaigns as $item) {
                LogHelper::info('start campaign ' . $item->campaignid);
                if ($item->status == Campaign::STATUS_SUSPENDED &&
                    $item->pause_status == Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH
                ) {
                    $ret = CampaignService::modifyStatus(
                        $item->campaignid,
                        Campaign::STATUS_DELIVERING,
                        [
                        'pause_status' => Campaign::PAUSE_STATUS_PLATFORM,
                        ],
                        false
                    );
                    if ($ret !== true) {
                        LogHelper::error('start campaign' . $item->campaignid . ' fail,' .
                            Config::get('error')[$ret]);
                    }
                    
                    //添加一条记录
                    $code = 6026;
                    $message = CampaignService::formatWaring(
                        $code,
                        [
                            $broker->name,
                            sprintf("%.2f", $params['balance']),
                            $client->clientname,
                        ]
                    );
                    OperationLog::store([
                        'category' => OperationLog::CATEGORY_CAMPAIGN,
                        'target_id' => $item->campaignid,
                        'type' => OperationLog::TYPE_SYSTEM,
                        'operator' => Config::get('error')[6000],
                        'message' => $message,
                    ]);
                }
            }
        }

        DB::commit(); //事务结束
        return $this->success();
    }
}
