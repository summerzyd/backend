<?php

namespace App\Http\Controllers\Manager;

use App\Components\Helper\LogHelper;
use App\Models\Account;
use App\Models\AccountSubType;
use App\Models\Affiliate;
use App\Models\Broker;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Components\Config;

class AccountController extends Controller
{

    /**
     * 获取用户列表
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | type | integer | 账号类型 | 1：广告主账号   | 是 |.
     * |  |  |  |  2：代理商账号 3：媒体账号 4：联盟运营账号  | |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 |  | 否 |

     * @param  Request $request
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | user_id | integer | 用户ID |  | 是 |
     * | username | string  | 用户名  |  | 是  |
     * | name | string | 联系人 |  | 是 |
     * | account_sub_type_id | integer | 角色ID |  | 平台账号时返回 |
     * | operation_list | string | 权限，以逗号,分隔 |  | 是 |
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $type = strtoupper($request->input('type'));

        if (!in_array($type, array_keys(Account::getTypeStatusLabels()))) {
            return $this->errorCode(5000);
        }

        $info = $this->getUserList($type, $pageNo, $pageSize, $search, $sort);
        if (!$info) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }

        return $this->success(null, $info['map'], $info['list']);
    }

    /**
     * 创建帐号
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | uaername | string | 广告主名称 |  | 是 |
     * | password | string | 初始密码 |  | 是 |
     * | contact_name | string | 联系人 |  | 是 |
     * | email_address | string | 邮箱 |  | 是 |
     * | contact_phone | string | 手机号 |  | 是 |
     * | operation_list | string | 权限列表 |  | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'username' => 'required|min:2|max:96',
                'password' => 'required|min:6',
                'contact_name' => 'required',
                'contact_phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
                'email_address' => "required|email",
                'operation_list' => "required",
            ], [], User::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (User::getAgencyUser('username', $params['username'])) {
            return $this->errorCode(5092);
        }
        if (User::getAgencyUser('email_address', $params['email_address'])) {
            return $this->errorCode(5093);
        }
        if (User::getAgencyUser('contact_phone', $params['contact_phone'])) {
            return $this->errorCode(5094);
        }
        DB::beginTransaction();//事务开始
        //先创建账号
        $account = Auth::user()->account;
        if (!$account) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }

        //新增用户信息
        $user = new User();
        $user->fill($params);
        $user->agencyid = Auth::user()->agencyid;
        $user->password = md5($params['password']);
        $user->default_account_id = $account->account_id;
        $user->role_id = 0;
        $user->account_sub_type_id = isset($params['account_sub_type_id']) ?
            $params['account_sub_type_id'] : 0;
        $user->active = User::ACTIVE_TRUE;

        if (!$user->save()) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }

        //最后存 account_user_assoc关联表
        $user->accounts()->attach($account->account_id, ['linked' => date('Y-m-d h:i:s')]);

        $defaultRoleId = Config::get('default_manager_role');//获取最新的权限id
        //创建广告主权限
        $defaultRole = Role::find($defaultRoleId);
        $role = $defaultRole->replicate();
        $role->type = Role::TYPE_USER;
        $role->operation_list = $params['operation_list'];
        if (!$role->push()) {
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
     * 账号管理 更新用户字段
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | value | string | 修改的值 | operation_list是以,分隔的权限字符串 | 是 |
     * |  |  |  | 如：manager-profile,manager-password,manager-campaign | |
     * | id | integer | 用户id |  | 是 |
     * | field | string | 修改字段 | role_id，operation_list，password | 是 * |
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // 管理员类型有权限
        $user = Auth::user();
        $account = $user->account;
        /*if ($account->manager_userid != $user->user_id) {
            return $this->errorCode(5003); // @codeCoverageIgnore
        }*/

        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'id' => 'required|integer',
                'field' => 'required',
            ], [], array_merge(Affiliate::attributeLabels(), User::attributeLabels()))) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->get('id');
        $field = $request->get('field');
        $value = $request->get('value');

        // 当更新权限时，上传的id是role表的id
        if ($field == 'account_sub_type_id') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|integer'
                ], [], User::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            if (!$model = AccountSubType::find($value)) {
                return $this->errorCode(5000);
            }
        } elseif ($field == 'password') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:6'
                ], [], User::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'operation_list') {
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], User::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'active') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|in:0,1'
                ], [], User::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }

        $user = User::find($id);
        if ($field == 'account_sub_type_id') {
            $user->$field = $value;
            LogHelper::info('User'. $id . $field .'change from contact_phone to'. $value);
        } elseif ($field == 'password') {
            $user->password = md5($value);
            LogHelper::info('user'. $id . $field .'change from contact to'. md5($value));
        } elseif ($field == 'operation_list') {
            $role = $user->role;
            $role->operation_list = $value;
            $role->save();
            LogHelper::info('user'. $id . $field .'change from contact to'. md5($value));
        } elseif ($field == 'active') {
            $user->active = $value;
            LogHelper::info('User'. $id . $field .'change from active to'. $value);
        }
        if (!$user->save()) {
            return $this->errorCode(5001);// @codeCoverageIgnore
        }
        return $this->success();
    }

    /**
     * 获取权限名称和类型
     * @param string $type
     * @param int $pageNo
     * @param int $pageSize
     * @param null $search
     * @param null $sort
     * @return array
     */
    private function getUserList(
        $type,
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null
    ) {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('users as u')
            ->leftJoin('accounts as a', 'u.default_account_id', '=', 'a.account_id')
            ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
            ->orderBy('active', 'DESC');
        if ($type == Account::TYPE_ADVERTISER) {
            $select->leftJoin('clients as c', 'c.account_id', '=', 'a.account_id')
                ->select('u.user_id', 'u.username', 'c.clientname as name', 'r.operation_list')
                ->where('c.clients_status', Client::STATUS_ENABLE)
                ->where('c.affiliateid', '=', 0);
        } elseif ($type == Account::TYPE_TRAFFICKER) {
            $select->leftJoin('affiliates as af', 'af.account_id', '=', 'a.account_id')
                ->select('u.user_id', 'u.username', 'af.name as name', 'r.operation_list')
                ->where('af.affiliates_status', Affiliate::STATUS_ENABLE);
        } elseif ($type == Account::TYPE_BROKER) {
            $select->leftJoin('brokers as b', 'b.account_id', '=', 'a.account_id')
                ->select('u.user_id', 'u.username', 'b.name as name', 'r.operation_list')
                ->where('b.status', Broker::STATUS_ENABLE);
        } else {
            $select->select(
                'u.user_id',
                'u.username',
                'u.account_sub_type_id',
                'u.contact_name as name',
                'r.operation_list',
                'u.active'
            );
        }
        $select->where('u.agencyid', Auth::user()->agencyid)->where('a.account_type', '=', $type);

        // 搜索
        if (!is_null($search) && trim($search)) {
            if ($type == Account::TYPE_ADVERTISER) {
                $select->where(function ($select) use ($search) {
                    $select->where('clientname', 'like', '%' . $search . '%')
                        ->orWhere('u.username', 'like', '%' . $search . '%');
                });
            } elseif ($type == Account::TYPE_TRAFFICKER) {
                $select->where(function ($select) use ($search) {
                    $select->where('af.name', 'like', '%' . $search . '%')
                        ->orWhere('u.username', 'like', '%' . $search . '%');
                });
            } elseif ($type == Account::TYPE_BROKER) {
                $select->where(function ($select) use ($search) {
                    $select->where('b.name', 'like', '%' . $search . '%')
                        ->orWhere('u.username', 'like', '%' . $search . '%');
                });
            } else {
                $select->where(function ($select) use ($search) {
                    $select->where('u.contact_name', 'like', '%' . $search . '%')
                        ->orWhere('u.username', 'like', '%' . $search . '%');
                });
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
            $select->orderBy('user_id', 'desc');
        }

        $list = $select->get();
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
