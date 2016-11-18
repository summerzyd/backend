<?php
namespace App\Http\Controllers\Manager;

use App\Components\Formatter;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AppInfo;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Product;
use App\Services\CampaignService;
use App\Services\ManualService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Input;

class GameController extends Controller
{
    /**
     * 新增游戏
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientid |  | integer | 广告主ID |  | 是 |
     * | appinfos_app_name |  | string | 游戏名称 |  | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|int
     *
     */
    public function gameStore(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => 'required',
                'appinfos_app_name' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $params = $request->all();
        //存储campaign信息
        $row = CampaignService::getCampaignCount($params);
        if ($row > 0) {
            LogHelper::info('The same application already exists' . $params['appinfos_app_name']);
            return $this->errorCode(5022);
        }

        //CPA/CPT广告类型为其他
        $params['ad_type'] = Campaign::AD_TYPE_APP_MARKET;

        //获取广告主代理id
        $client = Client::find($params['clientid']);
        $params['agencyid'] = $client->agencyid;
        $params['action'] = Campaign::ACTION_APPROVAL;
        $params['revenue_type'] = Campaign::REVENUE_TYPE_CPD;
        $params['platform'] = Campaign::PLATFORM_IPHONE_COPYRIGHT;

        DB::beginTransaction();  //事务开始

        //保存产品信息
        $product = new Product();
        $product->type = Product::TYPE_GAME;
        $product->platform = Campaign::PLATFORM_IPHONE_COPYRIGHT;
        $product->clientid = $params['clientid'];
        $product->name = $params['appinfos_app_name'];
        $product->show_name = $params['appinfos_app_name'];
        $ret = $product->save();
        // @codeCoverageIgnoreStart
        if (!$ret) {
            DB::rollback();
            return 5001;
        }
        // @codeCoverageIgnoreEnd
        $params['products_id'] = $product->id;
        $params['delivery_type'] = Campaign::DELIVERY_TYPE_GAME;
        //生成AppId
        $params['app_id'] = 'app' . str_random(12);
        //新建推广计划
        $campaign = Campaign::storeCampaign($params);
        // @codeCoverageIgnoreStart
        if (!$campaign) {
            DB::rollback();
            return 5101;
        }
        // @codeCoverageIgnoreEnd
        //存储应用信息
        $appInfo = new AppInfo();
        $appInfo->media_id = $params['agencyid'];
        $appInfo->app_id = $params['app_id'];
        $appInfo->app_name = $params['appinfos_app_name'];
        $appInfo->platform = $params['platform'];
        $appInfo->app_show_name = $params['appinfos_app_name'];
        $appInfo->application_id = 0;
        $ret = $appInfo->save();
        // @codeCoverageIgnoreStart
        if (!$ret) {
            DB::rollback();
            return 5001;
        }
        // @codeCoverageIgnoreEnd

        DB::commit(); //事务结束

        return $this->success();
    }

    /**
     * 游戏列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientid |  | integer | 广告主ID |  | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 游戏ID |  | 是 |
     * | appinfos_app_name |  | string | 游戏名称 |  | 是 |
     */
    public function gameIndex(Request $request)
    {
        $clientId = $request->input('clientid');
        $select = \DB::table('campaigns AS c')
            ->leftJoin('appinfos AS a', 'a.app_id', '=', 'c.campaignname')
            ->select('c.campaignid', 'a.app_name AS appinfos_app_name')
            ->where('c.delivery_type', Campaign::DELIVERY_TYPE_GAME);
        if (!empty($clientId)) {
            $select = $select->where('c.clientid', $clientId);
        }
        $ret = $select->get();
        return $this->success($ret);
    }

    /**
     * 新增，修改数据
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientid |  | integer | 游戏ID |  | 是 |
     * | campaignid |  | integer | 游戏名称 |  | 是 |
     * | affiliateid |  | integer | 游戏名称 |  | 是 |
     * | date |  | string | 游戏名称 |  | 是 |
     * | game_client_usernum |  | integer | 游戏名称 |  | 是 |
     * | game_charge |  | string | 游戏名称 |  | 是 |
     * | game_client_revenue_type |  | integer | 游戏名称 |  | 是 |
     * | game_client_price |  | string | 游戏名称 |  | 是 |
     * | game_client_amount |  | string | 游戏名称 |  | 是 |
     * | game_af_revenue_type |  | integer | 游戏名称 |  | 是 |
     * | game_af_price |  | string | 游戏名称 |  | 是 |
     * | game_af_amount |  | string | 游戏名称 |  | 是 |
     * | game_af_usernum |  | integer | 游戏名称 |  | 是 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $revenueType = implode(',', [
            Campaign::REVENUE_TYPE_CPD,
            Campaign::REVENUE_TYPE_CPC,
            Campaign::REVENUE_TYPE_CPM,
            Campaign::REVENUE_TYPE_CPS,
            Campaign::REVENUE_TYPE_CPA,
        ]);
        if (($ret = $this->validate($request, [
                'clientid' => 'required',
                'campaignid' => 'required',
                'affiliateid' => 'required',
                'date' => 'required',
                'game_client_revenue_type' => "required|in:{$revenueType}",
                'game_af_revenue_type' => "required|in:{$revenueType}",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if ($params['date'] > date('Y-m-d')) {
            return $this->errorCode(8001);
        }
        $table = 'data_hourly_daily_' . date('Ym', strtotime($params['date']));

        $count = \DB::table($table)
            ->where('campaign_id', $params['campaignid'])
            ->where('affiliateid', $params['affiliateid'])
            ->where('date', $params['date'])
            ->count();
        //存在则覆盖
        if ($count > 0) {
            // @codeCoverageIgnoreStart
            \DB::table($table)
                ->where('campaign_id', $params['campaignid'])
                ->where('affiliateid', $params['affiliateid'])
                ->where('date', $params['date'])
                ->update([
                    'game_client_usernum' => $params['game_client_usernum'],
                    'game_charge' => $params['game_charge'],
                    'game_client_revenue_type' => $params['game_client_revenue_type'],
                    'game_client_price' => $params['game_client_price'],
                    'game_client_amount' => $params['game_client_amount'],
                    'game_af_revenue_type' => $params['game_af_revenue_type'],
                    'game_af_price' => $params['game_af_price'],
                    'game_af_amount' => $params['game_af_amount'],
                    'game_af_usernum' => $params['game_af_usernum'],
                ]);
            // @codeCoverageIgnoreEnd
        } else {
            \DB::table($table)->insert([
                'date' => $params['date'],
                'ad_id' => 0,
                'campaign_id' => $params['campaignid'],
                'zone_id' => 0,
                'affiliateid' => $params['affiliateid'],
                'game_client_usernum' => $params['game_client_usernum'],
                'game_charge' => $params['game_charge'],
                'game_client_revenue_type' => $params['game_client_revenue_type'],
                'game_client_price' => $params['game_client_price'],
                'game_client_amount' => $params['game_client_amount'],
                'game_af_revenue_type' => $params['game_af_revenue_type'],
                'game_af_price' => $params['game_af_price'],
                'game_af_amount' => $params['game_af_amount'],
                'game_af_usernum' => $params['game_af_usernum'],
            ]);
        }

        return $this->success();
    }

    /**
     * 录数列表
     *
     * | name | sub name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | pageNo |  | integer | 请求页数 |  | 是 |
     * | pageSize |  | integer | 请求每页数量 |  | 是 |
     * | search |  | string | 搜索关键字 |  | 是 |
     * | sort |  | integer | 排序字段 |  | 是 |
     * | filter |  | integer | 过滤 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | date |  | date | 日期 |  | 是 |
     * | clientname |  | string | 广告主 |  | 是 |
     * | affiliatename |  | string | 渠道 |  | 是 |
     * | app_name |  | string | 游戏 |  | 是 |
     * | game_client_usernum |  | integer | 新增用户数 |  | 是 |
     * | game_charge |  | decimal | 充值金额 |  | 是 |
     * | game_client_revenue_type |  | integer | 广告主计费方式 |  | 是 |
     * | game_client_price |  | decimal | 广告主单价 |  | 是 |
     * | game_client_amount |  | decimal | 广告主结算金额 |  | 是 |
     * | game_af_revenue_type |  | integer | 渠道计费方式 |  | 是 |
     * | game_af_price |  | decimal | 渠道单价 |  | 是 |
     * | game_af_amount |  | decimal | 渠道结算金额 |  | 是 |
     * | game_af_usernum |  | integer | 渠道新增用户数 |  | 是 |
     */
    public function index(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $filter = json_decode($request->input('filter'), true);

        $user = Auth::user();
        $account = $user->account;
        $agencyId = $account->agency->agencyid;

        $select = \DB::table('data_hourly_daily AS dhd')
            ->join('affiliates AS a', 'a.affiliateid', '=', 'dhd.affiliateid')
            ->join('campaigns AS c', 'c.campaignid', '=', 'dhd.campaign_id')
            ->join('clients AS cl', 'cl.clientid', '=', 'c.clientid')
            ->join('appinfos AS ai', function ($join) {
                $join->on('ai.app_id', '=', 'c.campaignname')
                    ->on('ai.platform', '=', 'c.platform');
            })
            ->select(
                'c.campaignid',
                'c.clientid',
                'dhd.affiliateid',
                'dhd.date',
                'cl.clientname',
                'a.name AS affiliatename',
                'ai.app_name',
                'dhd.game_client_usernum',
                'dhd.game_charge',
                'dhd.game_client_revenue_type',
                'dhd.game_client_price',
                'dhd.game_client_amount',
                'dhd.game_af_revenue_type',
                'dhd.game_af_price',
                'dhd.game_af_amount',
                'dhd.game_af_usernum'
            )
            ->where('cl.agencyid', $agencyId)
            ->where('cl.affiliateid', 0)
            ->where('c.delivery_type', Campaign::DELIVERY_TYPE_GAME);

        // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where(function ($select) use ($search) {
                $select->where('a.name', 'like', '%' . $search . '%')
                    ->orWhere('cl.clientname', 'like', '%' . $search . '%')
                    ->orWhere('ai.app_name', 'like', '%' . $search . '%');
            });
        }

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'clientid') {
                        $select->where('cl.' . $k, $v);
                    } elseif ($k == 'affiliateid') {
                        $select->where('a.' . $k, $v);
                    } elseif ($k == 'campaign_id') {
                        $select->where('dhd.' . $k, $v);
                    } elseif ($k == 'date') {
                        if (!StringHelper::isEmpty($v[0])) {
                            $select = $select->where('dhd.' . $k, '>', $v[0]);
                        }
                        if (!StringHelper::isEmpty($v[1])) {
                            $select = $select->where('dhd.' . $k, '<=', $v[1]);
                        }
                    }
                }
            }
        }

        // 分页
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        $rows = $select->orderBy('date', 'DESC')->get();

        foreach ($rows as &$item) {
            $item->game_client_price = Formatter::asDecimal($item->game_client_price);
            $item->game_client_amount = Formatter::asDecimal($item->game_client_amount);
            $item->game_af_price = Formatter::asDecimal($item->game_af_price);
            $item->game_af_amount = Formatter::asDecimal($item->game_af_amount);
        }

        return $this->success(null, [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $total,
        ], $rows);
    }

    /**
     * 删除数据
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 游戏名称 |  | 是 |
     * | affiliateid |  | integer | 渠道 |  | 是 |
     * | date |  | string | 日期 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function delete(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'affiliateid' => 'required',
                'date' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        $table = 'data_hourly_daily_' . date('Ym', strtotime($params['date']));
        $ret = \DB::table($table)
            ->where('campaign_id', $params['campaignid'])
            ->where('affiliateid', $params['affiliateid'])
            ->where('date', $params['date'])
            ->delete();
        if (!$ret) {
            return $this->errorCode(5001);
        }
        return $this->success();
    }

    /**
     * 列表下拉列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 游戏名称 |  | 是 |
     * | affiliateid |  | integer | 渠道 |  | 是 |
     * | clientid |  | integer | 广告主 |  | 是 |
     * | type |  | integer | 过滤类型,1广告主，2游戏，3渠道 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientid |  | integer | 广告主 type=1|  | 是 |
     * | clientname |  | string | 广告主名称 type=1|  | 是 |
     * | campaignid |  | integer | 游戏 type=2|  | 是 |
     * | app_name |  | string | 游戏名称 type=2|  | 是 |
     * | affiliateid |  | integer | 渠道 type=3|  | 是 |
     * | name |  | string | 渠道名称 type=3|  | 是 |
     */
    public function filter(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required|in:1,2,3',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if ($params['type'] == 1) {
            $select = \DB::table('clients AS cl')
                ->where('cl.agencyid', Auth::user()->agencyid)
                ->where('cl.affiliateid', 0)
                ->select('cl.clientid', 'cl.clientname');
        } elseif ($params['type'] == 2) {
            $select = \DB::table('campaigns AS c')
                ->leftJoin('appinfos AS a', 'a.app_id', '=', 'c.campaignname')
                ->where('c.delivery_type', Campaign::DELIVERY_TYPE_GAME)
                ->select('c.campaignid', 'a.app_name AS appinfos_app_name');
            if (!empty($params['clientid'])) {
                $select->where('c.clientid', $params['clientid']);
            }
        } else {
            $select = \DB::table('data_hourly_daily AS dhd')
                ->leftJoin('campaigns AS c', 'dhd.campaign_id', '=', 'c.campaignid')
                ->leftJoin('affiliates AS a', 'a.affiliateid', '=', 'dhd.affiliateid')
                ->where('a.agencyid', Auth::user()->agencyid)
                ->select('dhd.affiliateid', 'a.name');
            if (!empty($params['clientid'])) {
                $select->where('c.clientid', $params['clientid']);
            }
            if (!empty($params['campaignid'])) {
                $select->where('dhd.campaign_id', $params['campaignid']);
            }
        }

        $rows = $select->distinct()->get();

        return $this->success($rows);
    }

    /**
     * 渠道列表
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |  | integer | 渠道ID |  | 是 |
     * | name |  | string | 渠道名称|  | 是 |
     */
    public function affiliateList(Request $request)
    {
        $game = Campaign::DELIVERY_TYPE_GAME;
        $rows = \DB::table('affiliates')
            ->where('agencyid', Auth::user()->agencyid)
            ->where('affiliates_status', Affiliate::STATUS_ENABLE)
            ->whereRaw("(delivery_type & {$game})>0")
            ->select('affiliateid', 'name')
            ->get();
        return $this->success($rows);
    }

    /**
     * 广告主列表
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientid |  | integer | 广告主 |  | 是 |
     * | clientname |  | string | 广告主名称 |  | 是 |
     */
    public function clientList(Request $request)
    {
        $rows = \DB::table('clients')
            ->where('agencyid', Auth::user()->agencyid)
            ->select('clientid', 'clientname')
            ->where('clients_status', Client::STATUS_ENABLE)
            ->where('affiliateid', 0)
            ->get();

        return $this->success($rows);
    }

    /**
     * 导入数据
     * @param Request $request
     * @return array|\Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function gameImport(Request $request)
    {
        set_time_limit(0);
        $excel = input::file('file', null);
        //获取导入EXCEL数据
        $importExcelData = Excel::load($excel)->getSheet(0)->toArray();
        $count = count($importExcelData);
        //EXCEL没有数据
        if ($count <= 1) {
            return $this->errorCode(5090);
        }

        try {
            //检查EXCEL数据是否正确
            $warnings = $this->validateImportData($count, $importExcelData);
            //有错误，提示
            if (count($warnings) > 0) {
                return $this->errorCode(1, $warnings);
            }
            //保存EXCEL数据
            DB::beginTransaction();
            $tips = [];
            for ($i = 1; $i < $count; $i++) {
                //获取导入的数据
                $manualExcelData = $this->getExcelData($importExcelData, $i);

                $table = 'data_hourly_daily_' . date('Ym', strtotime($manualExcelData['date']));

                $ret = \DB::table('campaigns AS c')
                    ->leftJoin('appinfos AS a', 'c.campaignname', '=', 'a.app_id')
                    ->leftJoin('clients AS cl', 'cl.clientid', '=', 'c.clientid')
                    ->select('campaignid')
                    ->where('cl.agencyid', Auth::user()->agencyid)
                    ->where('a.app_name', $manualExcelData['app_name'])
                    ->first();
                if ($ret) {
                    $count = \DB::table($table)->where('date', $manualExcelData['date'])
                        ->where('campaign_id', $ret->campaignid)
                        ->where('affiliateid', $manualExcelData['affiliateid'])
                        ->count();
                    if ($count > 0) {
                        \DB::table($table)
                            ->where('date', $manualExcelData['date'])
                            ->where('campaign_id', $ret->campaignid)
                            ->where('affiliateid', $manualExcelData['affiliateid'])
                            ->update([
                                'game_client_usernum' => $manualExcelData['game_client_usernum'],
                                'game_charge' => $manualExcelData['game_charge'],
                                'game_client_revenue_type' =>
                                    $this->getRevenueType($manualExcelData['game_client_revenue_type']),
                                'game_client_price' => $manualExcelData['game_client_price'],
                                'game_client_amount' => $manualExcelData['game_client_amount'],
                                'game_af_revenue_type' =>
                                    $this->getRevenueType($manualExcelData['game_af_revenue_type']),
                                'game_af_price' => $manualExcelData['game_af_price'],
                                'game_af_amount' => $manualExcelData['game_af_amount'],
                                'game_af_usernum' => $manualExcelData['game_af_usernum'],
                            ]);
                    } else {
                        \DB::table($table)->insert([
                            'date' => $manualExcelData['date'],
                            'ad_id' => 0,
                            'campaign_id' => $ret->campaignid,
                            'zone_id' => 0,
                            'affiliateid' => $manualExcelData['affiliateid'],
                            'game_client_usernum' => $manualExcelData['game_client_usernum'],
                            'game_charge' => $manualExcelData['game_charge'],
                            'game_client_revenue_type' =>
                                $this->getRevenueType($manualExcelData['game_client_revenue_type']),
                            'game_client_price' => $manualExcelData['game_client_price'],
                            'game_client_amount' => $manualExcelData['game_client_amount'],
                            'game_af_revenue_type' =>
                                $this->getRevenueType($manualExcelData['game_af_revenue_type']),
                            'game_af_price' => $manualExcelData['game_af_price'],
                            'game_af_amount' => $manualExcelData['game_af_amount'],
                            'game_af_usernum' => $manualExcelData['game_af_usernum'],
                        ]);
                    }

                } else {
                    //2016-07-08，如果查找不到媒体，广告的信息
                    $tips[] = ManualService::formatWaring(5262, $i, $i);
                    LogHelper::info(ManualService::formatWaring(5262, $i, $i));
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorCode(5005);
        }

        if (0 == count($tips)) {
            DB::commit();
            return $this->success();
        } else {
            return $this->errorCode(1, $tips);
        }
    }

    /**
     * 获取计费类型
     * @param $key
     * @return array|mixed|null
     * @codeCoverageIgnore
     */
    private function getRevenueType($key)
    {
        $data = [
            'CPA' => Campaign::REVENUE_TYPE_CPA,
            'CPM' => Campaign::REVENUE_TYPE_CPM,
            'CPD' => Campaign::REVENUE_TYPE_CPD,
            'CPC' => Campaign::REVENUE_TYPE_CPC,
            'CPS' => Campaign::REVENUE_TYPE_CPS,
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 验证导入数据
     * @param $count
     * @param $importExcelData
     * @return array
     * @codeCoverageIgnore
     */
    private function validateImportData($count, $importExcelData)
    {
        $k = 1;
        $warnings = [];
        $date = date('Y-m-d');
        for ($i = 1; $i < $count; $i++) {
            $excelData = $this->getExcelData($importExcelData, $i);
            //验证广告主ID是否为空
            if (empty($excelData['clientid'])) {
                $warnings[] = ManualService::formatWaring(5243, $k, $i);
                $k++;
            }
            //游戏
            if (empty($excelData['app_name'])) {
                $warnings[] = ManualService::formatWaring(5243, $k, $i);
                $k++;
            }
            //渠道ID
            if (empty($excelData['affiliateid'])) {
                $warnings[] = ManualService::formatWaring(5243, $k, $i);
                $k++;
            }
            //日期
            if (empty($excelData['date'])) {
                $warnings[] = ManualService::formatWaring(5243, $k, $i);
                $k++;
            }
            //广告主计费方式
            if (empty($excelData['game_client_revenue_type'])) {
                $warnings[] = ManualService::formatWaring(5243, $k, $i);
                $k++;
            }
            //渠道计费方式
            if (empty($excelData['game_af_revenue_type'])) {
                $warnings[] = ManualService::formatWaring(5243, $k, $i);
                $k++;
            }
            //验证导入日期
            if (date('Y-m-d', strtotime($excelData['date'])) > $date) {
                $warnings[] = ManualService::formatWaring(5263, $k, $i);
                $k++;
            }
            //验证广告主是否存在
            if (!$this->validateClient($excelData['clientid'])) {
                $warnings[] = ManualService::formatWaring(5224, $k, $i);
                $k++;
            }

            //验证游戏是否存在
            if (!$this->validateCampaign($excelData['app_name'])) {
                $warnings[] = ManualService::formatWaring(5258, $k, $i);
                $k++;
            }
            //验证渠道是否存在
            if (!$this->validateAffiliate($excelData['affiliateid'])) {
                $warnings[] = ManualService::formatWaring(5259, $k, $i);
                $k++;
            }
            //验证计费类型是否正确
            if (!$this->validateRevenueType($excelData['game_client_revenue_type'])) {
                $warnings[] = ManualService::formatWaring(5260, $k, $i);
                $k++;
            }
            //验证计费类型是否正确
            if (!$this->validateRevenueType($excelData['game_af_revenue_type'])) {
                $warnings[] = ManualService::formatWaring(5260, $k, $i);
                $k++;
            }
            if ($excelData['game_client_usernum'] == '' && $excelData['game_charge'] == ''
                && $excelData['game_client_price'] == '' && $excelData['game_client_amount'] == ''
                && $excelData['game_af_price'] == '' && $excelData['game_af_amount'] == ''
                && $excelData['game_af_usernum'] == ''
            ) {
                $warnings[] = ManualService::formatWaring(5261, $k, $i);
                $k++;
            }
        }
        return $warnings;
    }

    /**
     * 验证媒体是否存在
     * @param $affiliteId
     * @return bool
     * @codeCoverageIgnore
     */
    private function validateAffiliate($affiliteId)
    {
        if ($affiliteId == '') {
            return false;
        }
        $count = Affiliate::where('affiliateid', $affiliteId)
            ->where('agencyid', Auth::user()->agencyid)
            ->count();
        return $count > 0 ? true : false;
    }

    /**
     * 验证计费类型是否正确
     * @param $revenueType
     * @return bool
     * @codeCoverageIgnore
     */
    private function validateRevenueType($revenueType)
    {
        $revenueType = array_search($revenueType, Campaign::getRevenueTypeLabels());
        if (in_array($revenueType, [
            Campaign::REVENUE_TYPE_CPD,
            Campaign::REVENUE_TYPE_CPC,
            Campaign::REVENUE_TYPE_CPM,
            Campaign::REVENUE_TYPE_CPS,
            Campaign::REVENUE_TYPE_CPA,
        ])
        ) {
            return true;
        };
        return false;
    }

    /**
     * 验证广告主
     * @param $clientId
     * @return bool
     * @codeCoverageIgnore
     */
    private function validateClient($clientId)
    {
        if ($clientId == '') {
            return false;
        }
        $count = Client::where('clientid', $clientId)
            ->where('agencyid', Auth::user()->agencyid)
            ->count();
        return $count > 0 ? true : false;
    }

    /**
     * 验证游戏是否创建
     * @param $appName
     * @return bool
     * @codeCoverageIgnore
     */
    private function validateCampaign($appName)
    {
        if ($appName == '') {
            return false;
        }
        $count = \DB::table('campaigns AS c')
            ->leftJoin('appinfos AS a', 'c.campaignname', '=', 'a.app_id')
            ->leftJoin('clients AS cl', 'cl.clientid', '=', 'c.clientid')
            ->where('cl.agencyid', Auth::user()->agencyid)
            ->where('a.app_name', $appName)
            ->count();
        return $count > 0 ? true : false;
    }

    /**
     * 获取excel数据
     * @param $importExcelData
     * @param $i
     * @return array
     * @codeCoverageIgnore
     */
    private function getExcelData($importExcelData, $i)
    {
        return [
            'clientid' => trim($importExcelData[$i][0]),
            'clientname' => trim($importExcelData[$i][1]),
            'app_name' => trim($importExcelData[$i][2]),
            'affiliateid' => trim($importExcelData[$i][3]),
            'affiliatename' => trim($importExcelData[$i][4]),
            'date' => trim($importExcelData[$i][5]),
            'game_client_usernum' => trim($importExcelData[$i][6]),
            'game_charge' => trim($importExcelData[$i][7]),
            'game_client_revenue_type' => trim($importExcelData[$i][8]),
            'game_client_price' => trim($importExcelData[$i][9]),
            'game_client_amount' => trim($importExcelData[$i][10]),
            'game_af_revenue_type' => trim($importExcelData[$i][11]),
            'game_af_price' => trim($importExcelData[$i][12]),
            'game_af_amount' => trim($importExcelData[$i][13]),
            'game_af_usernum' => trim($importExcelData[$i][14]),
        ];
    }
}
