<?php
namespace App\Http\Controllers\Broker;

use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\User;
use App\Services\CampaignService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    /**
     * 广告列表显示字段
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | field |  | string | 列名 |  | 是 |
     * | title |  | string | 列显示名称 |  | 是 |
     * | column_set |  | string | 列排序 |  | 是 |
     */
    public function columnList()
    {
        $fields = Campaign::getColumnListFields(Account::TYPE_BROKER);
        $list = CampaignService::getColumnList($fields);
        return $this->success(null, ['count' => count($list)], $list);
    }

    /**
     * 推广计划列表
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 推广计划ID |  | 是 |
     * | clientname |  | string | 广告主 |  | 是 |
     * | products_name |  | string | 应用名称 |  | 是 |
     * | appinfos_app_show_icon |  | string | 应用图标 |  | 是 |
     * | products_type |  | integer | 应用类型 |  | 是 |
     * | products_type_label |  | string | 应用类型标签 |  | 是 |
     * | appinfos_app_name |  | string | 广告名称 |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * | ad_type_label |  | string | 广告类型标签 |  | 是 |
     * | platform |  | string | 目标平台 |  | 是 |
     * | platform_label |  | string | 目标平台标签 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * | revenue_type_label |  | string | 计费类型标签 |  | 是 |
     * | revenue |  | decimal | 出价 |  | 是 |
     * | keyword_price_up_count |  | integer | 加价关键字数量 |  | 是 |
     * | day_limit |  | int | 日预算 |  | 是 |
     * | total_limit |  | int | 总预算 |  | 是 |
     * | status |  | int | 状态 |  | 是 |
     * | status_label |  | string | 状态标签 |  | 是 |
     * | pause_status |  | string | 暂停状态 |  | 是 |
     * | approve_time |  | datetime | 审核时间 |  | 是 |
     * | approver_user |  | string | 审核人 |  | 是 |
     * | approve_comment |  | string | 审核说明 |  | 是 |
     * | materials_status |  | integer | 素材状态 |  | 是 |
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $params = $request->all();

        $filter = json_decode($request->input('filter'), true);

        $info = $this->getBrokerCampaignList($user->user_id, $filter, $pageNo, $pageSize, $params);
        if (!$info) {
            // @codeCoverageIgnoreStart
            LogHelper::warning('campaign failed to load data');
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }

        return $this->success(null, $info['map'], $info['list']);
    }

    /**
     * 代理商广告主
     * @param $userId
     * @param int $pageNo
     * @param int $pageSize
     * @param $filter
     * @param array $params
     * @return array
     */
    private function getBrokerCampaignList(
        $userId,
        $filter,
        $pageNo = DEFAULT_PAGE_NO,
        $pageSize = DEFAULT_PAGE_SIZE,
        $params = []
    ) {
    

        $user = User::find($userId);
        if (!$user) {
            return null;// @codeCoverageIgnore
        }

        $when = " CASE ";
        foreach (Campaign::getStatusSort() as $k => $v) {
            $when .= ' WHEN ' . Campaign::getTableFullName() . '.status = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_status ";

        $when .= ", CASE ";
        foreach (Campaign::getAdTypeSort() as $k => $v) {
            $when .= ' WHEN ' . Campaign::getTableFullName() . '.ad_type = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_ad_type ";

        $broker = $user->account->broker;
        $agencyId = $broker->agency->agencyid;

        $clients = Client::where('broker_id', $broker->brokerid)
            ->select('clientid')
            ->get()
            ->toArray();

        $select = DB::table('campaigns')
            ->join('appinfos', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('appinfos.platform', '=', 'campaigns.platform');
            })
            ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
            ->leftJoin('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->select(
                DB::raw($when),
                'campaigns.campaignid',
                'campaigns.campaignname',
                'clients.clientname',
                'campaigns.revenue',
                'campaigns.status',
                'campaigns.pause_status',
                'campaigns.platform',
                'campaigns.revenue_type',
                'campaigns.day_limit',
                'campaigns.approve_time',
                'campaigns.approve_comment',
                'campaigns.ad_type',
                'campaigns.total_limit',
                'appinfos.app_name as appinfos_app_name',
                'appinfos.app_show_icon as appinfos_app_icon',
                'appinfos.vender as appinfos_vender',
                'appinfos.app_rank as appinfos_app_rank',
                'appinfos.materials_status as appinfos_materials_status',
                'appinfos.check_msg as appinfos_check_msg',
                'products.icon as appinfos_app_show_icon',
                'appinfos.materials_status as appinfos_materials_status',
                'products.name as products_name',
                'products.type as products_type'
            )
            ->where('appinfos.media_id', '=', $agencyId)
            ->whereIn('campaigns.clientid', array_column($clients, 'clientid'));

        // 搜索
        if (isset($params['search']) && trim($params['search'])) {
            $select->where('appinfos.app_name', 'like', '%' . $params['search'] . '%');
        }

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'platform') {
                        //如果 iPhone正版+iPad 转换为IOS
                        if ($v == Campaign::PLATFORM_IOS) {
                            $platform = [Campaign::PLATFORM_IOS, Campaign::PLATFORM_IOS_COPYRIGHT];
                        } else {
                            $platform = [$v];
                        }
                        $select->whereIn('campaigns.platform', $platform);
                    } elseif ($k == 'revenue_type') {
                        $select->where('campaigns.revenue_type', $v);
                    } elseif ($k == 'ad_type') {
                        $select->whereIn('campaigns.ad_type', Campaign::getAdTypeToAdType($v));
                    } elseif ($k == 'products_type') {
                        $select->where('products.type', $v);
                    } elseif ($k == 'status') {
                        $select = CampaignService::getFilterCondition($select, ['status' => $v], false);
                    } elseif ($k == 'revenue') {
                        $select = CampaignService::getFilterCondition($select, ['revenue' => $v]);
                    } elseif ($k == 'day_limit') {
                        $select = CampaignService::getFilterCondition($select, ['day_limit' => $v]);
                    }
                }
            }
        }

        // 分页
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        // 排序
        if (isset($params['sort']) && strlen($params['sort']) > 0) {
            $sortType = 'asc';
            if (strncmp($params['sort'], '-', 1) === 0) {
                $sortType = 'desc';
            }

            $sortAttr = str_replace('-', '', $params['sort']);
            if ($sortAttr == 'status') {
                $select->orderBy('sort_status', $sortType);
            } elseif ($sortAttr == 'ad_type') {
                $select->orderBy('sort_ad_type', $sortType);
            } else {
                $select->orderBy($sortAttr, $sortType);
            }
        } else {
            $select->orderBy('sort_status', 'desc');
        }

        $rows = $select->get();
        $rows = json_decode(json_encode($rows), true);
        $list = CampaignService::getCampaignItems($rows);
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
     * 获取所有广告主出价
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue |  | decimal | 广告主出价 |  | 是 |
     */
    public function revenue()
    {
        //获取所有出价
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $data = DB::table('campaigns')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->join('brokers', 'clients.broker_id', '=', 'brokers.brokerid')
            ->join('users', 'users.default_account_id', '=', 'brokers.account_id')
            ->where('users.user_id', Auth::user()->user_id)
            ->orderBy('revenue', 'ASC')->distinct()->get(['revenue']);
        return $this->success(null, null, $data);
    }

    /**
     * 获取所有广告日限额
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | day_limit |  | decimal | 日预算 |  | 是 |
     */
    public function dayLimit()
    {
        //获取日限额
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $data = DB::table('campaigns')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->join('brokers', 'clients.broker_id', '=', 'brokers.brokerid')
            ->join('users', 'users.default_account_id', '=', 'brokers.account_id')
            ->where('users.user_id', Auth::user()->user_id)
            ->orderBy('day_limit', 'ASC')->distinct()->get(['day_limit']);
        return $this->success(null, null, $data);
    }

    /**
     * 获取代理商计费类型
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     */
    public function revenueType()
    {
        //获取代理商计费类型
        $revenueType = Auth::user()->account->broker->revenue_type;
        $list = CampaignService::getRevenueTypeList($revenueType);
        return $this->success(['revenue_type' => $list]);
    }
}
