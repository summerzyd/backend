<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\StringHelper;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /*if (($ret = $this->validate($request, [
                'type' => 'required|integer',
            ], [], Setting::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }*/

        $agencyId = Auth::user()->agencyid;
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);

        $data = $this->getSettingList($agencyId, $search, $sort, $filter);

        return $this->success($data);
    }

    /**
     * Display a listing of the resource.
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
        $model = Setting::findOrFail($id);

        return $this->success($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'data' => 'required',
            ], [], Setting::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $data = json_decode($request->input('data'), true);
        foreach ($data as $k => $v) {
            Setting::where('agencyid', Auth::user()->agencyid)
                ->where('code', $k)
                ->update(['value' => is_array($v) ? json_encode($v) : $v]);
        }

        return $this->success();
    }

    /**
     * Update the specified resource in storage.
     *
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
            ], [], array_merge(Setting::attributeLabels()))) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');

        /*if ($field == 'name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32|unique:brokers,name'
                ], [], Setting::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'brief_name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32'
                ], [], Setting::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        }*/

        $model = Setting::findOrFail($id);
        $model->$field = $value;

        if ($model->save()) {
            return $this->success();
        }

        return $this->errorCode(5000);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $id = $request->input('id');
        if (Setting::destroy($id)) {
            return $this->success();
        }

        return $this->errorCode(5000);
    }

    /**
     * Get Setting list based on search, filter, sort and page.
     * @param int $agencyid
     * @param string $search
     * @param string $sort
     * @param array $filter
     * @return array
     */
    protected function getSettingList(
        $agencyid,
        $search = null,
        $sort = null,
        $filter = null
    ) {
        $select = DB::table('setting')->where('agencyid', $agencyid);

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
            $select->orderBy('id', 'asc');
        }

        // page
        /*$total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);*/

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $rows = $select->get();
        DB::setFetchMode(\PDO::FETCH_CLASS);
        $list = [];
        foreach ($rows as $row) {//var_dump($row->code);die();
            $row['label'] = trans('Setting.' . $row['code']);
            $list[] = $row;
        }
        return $list;
    }
}
