<?php

namespace App\Http\Controllers\Trafficker;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\StringHelper;
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * 角色列表
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
     * | kind | integer  | 每天类型 1为联盟 2为自营 3=1+2  | obj | 是 |
     * | id | integer |  |  | 是 |
     * | name | string  | 角色名  |  | 是 |
     * | operation_list | string  | 权限列表  | 以逗号分隔 | 是 |
     *
     */
    public function index(Request $request)
    {
        /*if (($ret = $this->validate($request, [
                'type' => 'required|integer',
            ], [], Role::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }*/

        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);

        $data = $this->getRoleList($pageNo, $pageSize, $search, $sort, $filter);

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
        $model = Role::findOrFail($id);

        return $this->success($model);
    }*/

    /**
     * 保存角色
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | id | integer | 为0新建 否则为更新 |  | 是 |
     * | name | string | 角色名  |  | 是 |
     * | operation_list | string  | 权限列表  | 以逗号分隔 | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'name' => 'required',
            ], [], Role::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (isset($params['id']) && $params['id'] > 0) {
            $model = Role::find($params['id']);
            if ($model->account_id != Auth::user()->default_account_id) {
                return $this->errorCode(5003);
            }
        } else {
            $model = new Role();
        }
        $model->name = $params['name'];
        $model->operation_list = $params['operation_list'];
        $model->account_id = Auth::user()->default_account_id;
        if (!$model->save()) {
            return $this->errorCode(5000);
        }
        return $this->success();
    }

    /**
     * 更新角色
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    /*public function update(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required|integer',
                'field' => 'required',
                'value' => 'required',
            ], [], array_merge(Role::attributeLabels()))) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');

        /*if ($field == 'name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32|unique:brokers,name'
                ], [], Role::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'brief_name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32'
                ], [], Role::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        }*/

        /*$model = Role::findOrFail($id);
        $model->$field = $value;

        if ($model->save()) {
            return $this->success();
        }

        return $this->errorCode(5000);
    }*/

    /**
     * 删除角色
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $id = $request->input('id');
        if (Role::destroy($id)) {
            return $this->success();
        }

        return $this->errorCode(5000);
    }

    /**
     * Get Role list based on search, filter, sort and page.
     * @param int $pageNo
     * @param int $pageSize
     * @param string $search
     * @param string $sort
     * @param array $filter
     * @return array
     */
    protected function getRoleList(
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null,
        $filter = null
    ) {
        $select = DB::table('roles')
            ->select(
                'id',
                'name',
                'operation_list'
            )
            ->where('account_id', '=', Auth::user()->default_account_id);

        // search
        if (!is_null($search) && trim($search)) {
            $select->where(function ($select) use ($search) {
                // $select->where('name', 'like', '%' . $search . '%');
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
            $select->orderBy('id', 'desc');
        }

        // page
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        $rows = $select->get();
        $list = [];
        foreach ($rows as $row) {
            //$row['something'] = intval(row['field']);
            //当前主账户的角色不能编辑
            if ($row->id == Auth::user()->account->user->role_id) {
                continue;
            }
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
     * 操作权限列表
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | id | integer | 权限ID |  | 是 |
     * | name | string | 角色名 |  | 是 |
     * | label | string | 权限列表 | 以逗号分隔 | 是 |
     * |  |  |  | 联盟的以trafficker-开头，自营的以trafficker-self-开头 |  |
     *
     */
    public function operationList(Request $request)
    {
        // 如果有账号管理权限，则可以看到所有的媒体商
        $list = [];
        $select = Operation::select(
            'name',
            'description'
        );
        if (Auth::user()->account->affiliate->kind  == Affiliate::KIND_ALLIANCE) {
            $select->where('account_type', '=', 'TRAFFICKER');
        } elseif (Auth::user()->account->affiliate->kind  == Affiliate::KIND_ALLIANCE) {
            $select->Where('account_type', '=', 'TRAFFICKER-SELF');
        } else {
            $select->where('account_type', '=', 'TRAFFICKER')
                ->orWhere('account_type', '=', 'TRAFFICKER-SELF');
        }
        $models = $select->orderBy('id', 'asc')->get()->toArray();

        foreach ($models as $model) {
            if (in_array($model['name'], [
                'trafficker-self-profile',
                'trafficker-self-password',
                'trafficker-self-message',
                'trafficker-profile',
                'trafficker-password',
                'trafficker-message',
                'trafficker-sdk',
            ])) {
                continue;
            }
            $item['name'] = $model['name'];
            $item['label'] = $model['description'];

            $list[] = $item;
        }

        return $this->success(null, null, $list);
    }
}
