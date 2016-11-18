<?php

namespace App\Http\Controllers\Trafficker;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\StringHelper;
use App\Models\Account;

class AccountController extends Controller
{
    /**
     * 媒体商子账户列表
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
     * | user_id | integer | users表的id |  | 是 |
     * | username | string  | 用户名  |  | 是 |
     * | password | string  | 密码  |  | 是 |
     * | contact_name | string | 联系人 |  | 是 |
     * | contact_phone | string | 手机号 |  | 是 |
     * | email_address | string | 邮箱 |  | 是 |
     * | qq | string | qq号 |  | 是 |
     * | role_name | string | 角色名称 |  | 是 |
     * | role_id | integer | 角色ID |  | 是 |
     * | date_created | timestamp | 创建时间 |  | 是 |
     */
    public function index(Request $request)
    {
        /*if (($ret = $this->validate($request, [
                'type' => 'required|integer',
            ], [], Account::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }*/

        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);

        $data = $this->getUserList($pageNo, $pageSize, $search, $sort, $filter);

        return $this->success(null, $data['map'], $data['list']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    /*public function filter(Request $request)
    {
        return $this->success(
            [
                'field' => ['k1' => 'v1', 'k2' => 'v2'],
            ]
        );
    }*/

    /**
     * Display the specified resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    /*public function view(Request $request)
    {
        $id = $request->input('id');
        $model = Account::findOrFail($id);

        return $this->success($model);
    }*/

    /**
     * 保存媒体子账号
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | user_id | integer | 为0新建 否则为更新 |  | 是 |
     * | username | string | 登录账号 |  | 是 |
     * | password | string | 登录账号 | 要么为空，要么从字符串长度>=6 | 否 |
     * | contact_name | string | 联系人 |  | 是 |
     * | contact_phone | string | 手机号 |  | 是 |
     * | email_address | string | 邮箱 |  | 是 |
     * | qq | string | qq号 |  | 是 |
     * | role_id | integer | 角色ID |  | 是 |
     * | role_name | string | 角色名 |  | 是 |
     * | active | integer | 1启用 0停用 |  | 是 |
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'contact_name' => 'required',
                'role_id' => "required",
            ], [], User::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (isset($params['user_id']) && $params['user_id'] > 0) {// update
            $model = User::find($params['user_id']);
            if (User::getAgencyUser('username', $params['username'], $params['user_id'])) {
                return $this->errorCode(5092);
            }
            if (User::getAgencyUser('email_address', $params['email_address'], $params['user_id'])) {
                return $this->errorCode(5093);
            }
            if (User::getAgencyUser('contact_phone', $params['contact_phone'], $params['user_id'])) {
                return $this->errorCode(5094);
            }
            if ($model->default_account_id != Auth::user()->default_account_id) {
                return $this->errorCode(5003);
            }
        } else {
            if (($ret = $this->validate($request, [
                    'username' => 'required|min:2|max:32',
                    'password' => 'required|min:6',
                    'contact_phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
                    'email_address' => "required|email",
                ], [], User::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            if (User::getAgencyUser('username', $params['username'])) {
                return $this->errorCode(5092);
            }
            if (User::getAgencyUser('email_address', $params['email_address'])) {
                return $this->errorCode(5093);
            }
            if (User::getAgencyUser('contact_phone', $params['contact_phone'])) {
                return $this->errorCode(5094);
            }
            $model = new User();
            $model->default_account_id = Auth::user()->default_account_id;
            $model->active = User::ACTIVE_TRUE;
        }
        $model->fill($params);
        $model->agencyid = Auth::user()->agencyid;
        if (isset($params['password']) && strlen($params['password']) >= 6) {
            $model->password = md5($params['password']);
        }

        if (!$model->save()) {
            return $this->errorCode(5001);
        }

        return $this->success();
    }

    /**
     * 修改账号
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | id | integer | 登录账号 |  | 是 |
     * | field | string | 字段 | active password | 是 |
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
            ], [], array_merge(User::attributeLabels()))) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');

        $model = User::find($id);
        if ($model->default_account_id != Auth::user()->default_account_id) {
            return $this->errorCode(5003);
        }

        if ($field == 'password') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:6'
                ], [], User::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            $model->password = md5($value);
        } elseif ($field == 'active') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|in:0,1'
                ], [], User::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            $model->active = intval($value);
        } elseif ($field == 'username') {
            if (User::getAgencyUser('username', $request->input('value'))) {
                return $this->errorCode(5092);
            }
        }
        
        if (!$model->save()) {
            return $this->errorCode(5000);
        }
            return $this->success();
    }

        

    /**
     * POST: 删除账号
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | user_id | integer | users表的id |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        if (($ret = $this->validate($request, [
                'user_id' => 'required|integer',
            ], [], array_merge(User::attributeLabels()))) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (isset($params['user_id']) && $params['user_id'] > 0) {
            $model = User::find($params['user_id']);
            if ($model->default_account_id != Auth::user()->default_account_id) {
                return $this->errorCode(5003);
            }
            $model->delete();
            return $this->success();
        }

        return $this->errorCode(5000);
    }

    /**
     * Get Account list based on search, filter, sort and page.
     * @param int $pageNo
     * @param int $pageSize
     * @param string $search
     * @param string $sort
     * @param array $filter
     * @return array
     */
    protected function getUserList(
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null,
        $filter = null
    ) {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('users')
            ->leftJoin('accounts', 'users.default_account_id', '=', 'accounts.account_id')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id');

        // search
        if (!is_null($search) && trim($search)) {
            $select->where(function ($select) use ($search) {
                $select->where('users.username', 'like', '%' . $search . '%');
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

        $select->select(
            'users.user_id',
            'users.username',
            'users.account_sub_type_id',
            'users.contact_name',
            'users.contact_phone',
            'users.email_address',
            'users.qq',
            'roles.id as role_id',
            'roles.name as role_name',
            'accounts.manager_userid',
            'users.active',
            'users.date_created'
        );
        $select->where('users.agencyid', Auth::user()->agencyid)
        ->where('users.default_account_id', Auth::user()->default_account_id);

        // sort
        if ($sort) {
            $sortType = 'asc';
            if (strncmp($sort, '-', 1) === 0) {
                $sortType = 'desc';
            }
            $sortAttr = str_replace('-', '', $sort);
            $select->orderBy($sortAttr, $sortType);
        } else {
            $select->orderBy('active', 'DESC');

        }

        // page
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        $rows = $select->get();
        $list = [];
        foreach ($rows as $row) {
            if ($row['manager_userid'] == $row['user_id']) {//主账户不返回到前端
                continue;
            }
            //$row['something'] = intval(row['field']);
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
}
