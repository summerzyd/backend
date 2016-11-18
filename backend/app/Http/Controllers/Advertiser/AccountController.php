<?php

namespace App\Http\Controllers\Advertiser;

use App\Components\Helper\LogHelper;
use App\Models\Account;
use App\Models\AccountSubType;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Operation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{

    /**
     * 获取用户列表
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * @param  Request $request
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | user_id | integer | 用户ID |  | 是 |
     * | username | string  | 用户名  |  | 是  |
     * | contact_name | string | 联系人 |  | 是 |
     * | email_address | string | 邮件 |  | 是 |
     * | comments | string | 备注 |  | 否 |
     * | account_sub_type_id | integer | 二级类型ID |  | 是 |
     * | account_sub_type_id_label | string | 二级类型标签 |  | 是 |
     * | role_id | integer | 角色ID |  | 是 |
     * | operation_list | string | 权限，以逗号,分隔 |  | 是 |
     * | active | integer | 停用启用 | 0停用 1启用 | 是 |
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $account = $user->account;

        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');

        $info = AccountService::getUserList($account->account_id, $pageNo, $pageSize, $search);
        if (!$info) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }

        return $this->success(null, $info['map'], $info['list']);
    }

    /**
     * 新增账户
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | username | string  | 用户名  |  | 是  |
     * | password | string | 密码 |  | 是 |
     * | contact_name | string | 联系人 |  | 是 |
     * | email_address | string | 邮件 |  | 是 |
     * | phone | string | 手机号 |  | 是 |
     * | qq | string | qq号码 |  | 否 |
     * | comments | string | 备注 |  | 否 |
     * | account_sub_type_id | integer | 用户二级类型ID |  | 是 |
     * | operation_list | string | 权限 |  | 是 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'username' => 'required',
                'password' => 'required|between:6,16',
                'email_address' => "required|email",
                'contact_name' => 'required',
                'phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
                'account_sub_type_id' => 'required',
                'qq' => 'max:64',
                'operation_list' => 'required',
            ], [], User::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        if (User::getAgencyUser('username', $request->input('username'))) {
            return $this->errorCode(5092);
        }
        if (User::getAgencyUser('email_address', $request->input('email'))) {
            return $this->errorCode(5093);
        }
        $params = $request->all();//获取参数
        $accountId = Auth::user()->default_account_id;
        // 格式不对
        if (!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z\_0-9_]{2,16}$/u", $params['username'])) {
            return $this->errorCode(5019);
        }
        //分割权限，并检验权限是否正确。
        $permission = array_column(
            Operation::where('account_type', Account::TYPE_ADVERTISER)->select('name')->get()->toArray(),
            'name'
        );
        $data = explode(",", $params['operation_list']);
        foreach ($data as $item) {
            if (!in_array($item, $permission)) {
                LogHelper::warning('without the permission'. $item);
                return $this->errorCode(5020);
            }
        }
        //检验子账户类型是否存在
        $count = AccountSubType::where('id', $params['account_sub_type_id'])->count();
        if ($count <= 0) {
            LogHelper::warning('without the sub account'. $params['account_sub_type_id']);
            return $this->errorCode(5026);
        }

        // @codeCoverageIgnoreStart
        DB::beginTransaction();  //事务开始
        //添加权限信息
        $role = Role::store($params);
        if (!$role) {
            DB::rollback();
            return $this->errorCode(5001);
        }

        //新增用户信息
        $user = User::store($accountId, $role->id, $params);
        if (!$user) {
            DB::rollback();
            return $this->errorCode(5001);
        }
        DB::commit(); //事务结束
        //返回结果
        return $this->success();
        // @codeCoverageIgnoreEnd
    }


    /**
     * 更新用户字段
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 用户ID（user_id），当修改权限时候，该id为角色ID（role_id） |  | 是 |
     * | field | string | 字段名 |  | 是 |
     * | value | string | 值 |  | 是 |
     *
     * @par| am  Request $request |
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'id' => 'required|integer',
                'field' => 'required',
            ], [], User::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->get('id');
        $field = $request->get('field');
        $value = $request->get('value');

        // 当更新权限时，上传的id是role表的id
        if ($field == 'operation_list') {
            $role = Role::find($id);
            $role->operation_list = $value;
            if ($role->save()) {
                return $this->success();
            } else {
                return $this->errorCode(5001); // @codeCoverageIgnore
            }
        }

        if ($field == 'username') {
            // 格式不对
            if (!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z\_0-9_]{2,16}$/u", $value)) {
                return $this->errorCode(5019);
            }
            // 用户名不能相同
            if (User::getAgencyUser('username', $request->input('value'))) {
                return $this->errorCode(5092);
            }

        } elseif ($field == 'email_address') {
            // 格式不对
            if (!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $value)) {
                return $this->errorCode(5018);
            }
            // 邮箱不能相同
            if (User::getAgencyUser('email_address', $request->input('value'))) {
                return $this->errorCode(5093);
            }
        } elseif ($field == 'contact_name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], User::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'active') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|in:' . implode(',', array_keys(User::getActiveStatusLabels()))
                ], [], User::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        } // @codeCoverageIgnore

        // @codeCoverageIgnoreStart
        $userEdit = User::find($id);
        $userEdit->$field = $value;
        LogHelper::info('User'. $id . $field .'change from'.  $userEdit->$field .'to'. $value);
        if (!$userEdit->save()) {
            return $this->errorCode(5001);
        }

        return $this->success();
        // @codeCoverageIgnoreEnd
    }
}
