<?php
namespace App\Http\Controllers\Trafficker;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Http\Controllers\Controller;
use App\Models\AppInfo;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Zone;
use App\Models\ZoneListType;
use App\Services\ZoneService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Qiniu\json_decode;
use App\Services\CategoryService;

class ZoneController extends Controller
{
    /**
     * 广告位列表
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $adType = ArrayHelper::getRequiredIn(Campaign::getAdTypeLabels());
        if (($ret = $this->validate($request, [
                'ad_type' => "required|in:{$adType}",
                'sort' => 'string',
            ], [], Zone::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();//获取所有参数
        $affiliates = Auth::user()->account->affiliate;
        //获取媒体商ID
        $affiliateId = $affiliates->affiliateid;
        $kindType = $affiliates->kind;
        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);
        $zones = ZoneService::getZones($affiliateId, $params['ad_type'], $pageNo, $pageSize, $search, $sort, $filter);
        if (!$zones) {
            // @codeCoverageIgnoreStart
            LogHelper::warning('zone failed to load data');
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }
        return $this->success(['kindType' => $kindType], $zones['map'], $zones['list']);
    }

    /**
     * 广告位新增，修改
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $platform = ArrayHelper::getRequiredIn(Campaign::getPlatformLabels());//平台
        $type = ArrayHelper::getRequiredIn(Zone::getTypeLabels());//广告位类型
        $rank = ArrayHelper::getRequiredIn(AppInfo::getRankStatusLabels());//获取等级
        if (($ret = $this->validate($request, [
                'zone_id' => 'integer',
            ], [], Zone::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (!isset($params['zone_id']) || $params['zone_id'] <= 0) {
            if (($ret = $this->validate($request, [
                    'ad_type' => 'required',
                    'type' => "required|numeric|in:{$type}",
                    'zone_name' => 'required|string',
                ], [], Zone::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
        } else {
            $zones = Zone::find($params['zone_id']);
            $params['zone_name'] = isset($params['zone_name']) ? $params['zone_name'] :$zones->zonename;
            $params['type'] = isset($params['type']) ? $params['type'] :$zones->type;
            $params['ad_type'] = isset($params['ad_type']) ? $params['ad_type'] :$zones->type;
            $params['platform'] = isset($params['platform']) ? $params['platform'] : $zones->platform;
            $params['position'] = isset($params['position']) ? $params['position'] : $zones->position;
            $params['listtypeid'] = isset($params['listtypeid']) ? $params['listtypeid'] : $zones->listtypeid;
            $params['rank_limit'] = isset($params['rank_limit']) ? $params['rank_limit'] : $zones->rank_limit;
            $params['category'] = isset($params['category']) ? $params['category'] :$zones->oac_category_id;
            $params['description'] = isset($params['description']) ? $params['description'] : $zones->description;
            $params['ad_refresh'] = isset($params['ad_refresh']) ? $params['ad_refresh'] : $zones->ad_refresh;
            $params['flow_percent'] = isset($params['flow_percent']) ? $params['flow_percent'] : $zones->flow_percent;
        }

        //应用广告位新增
        if ($params['ad_type'] == Campaign::AD_TYPE_APP_MARKET) {
            if (!isset($params['zone_id']) || $params['zone_id'] <= 0) {
                if (($ret = $this->validate($request, [
                        'platform' => "required|in:{$platform}",
                        'rank_limit' => "required|integer|in:{$rank}",
                        'category' => 'required|string',
                        'position' => 'required|integer',
                        'listtypeid' => 'required',
                    ], [], Zone::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
            } else {
                if (($ret = $this->validate($request, [
                        'rank_limit' => "required|integer|in:{$rank}",
                        'category' => 'required|string',
                    ], [], Zone::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
            }
        } elseif ($params['ad_type'] == Campaign::AD_TYPE_BANNER_IMG
            || $params['ad_type'] == Campaign::AD_TYPE_BANNER_TEXT_LINK) {
            $refresh = ArrayHelper::getRequiredIn(Zone::getRefreshLabels());//广告位类型
            if (($ret = $this->validate($request, [
                    'ad_refresh' => "required|integer|in:{$refresh}",
                ], [], Zone::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($params['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
            $params['platform'] = Campaign::PLATFORM_IOS_COPYRIGHT;
        }
        //媒体商ID
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $platform = isset($params['platform']) ? $params['platform'] : '';
        if (isset($params['zone_id']) && $params['zone_id'] > 0) {
            //修改广告位
            $zone = Zone::find($params['zone_id']);
            if (empty($zone)) {
                return $this->errorCode(5002);//@codeCoverageIgnore
            }
            $query = Zone::where('zoneid', '<>', $params['zone_id'])
                ->where('affiliateid', $affiliateId)
                ->where('type', $params['type'])
                ->where('zonename', $params['zone_name']);
            if (!empty($platform)) {
                $query->where(DB::raw("(platform & {$platform})"), '>', 0);//@codeCoverageIgnore
            }//@codeCoverageIgnore
            $count = $query->count();
            if ($count > 0) {
                return $this->errorCode(5055);
            }
            if ($zone->affiliateid != $affiliateId) {
                $this->errorCode(5004);//@codeCoverageIgnore
            }//@codeCoverageIgnore
            DB::beginTransaction();  //事务开始
            $zone = Zone::updateZone($params);
            if (!$zone) {
                //@codeCoverageIgnoreStart
                DB::rollBack();
                return $this->errorCode(5001);
                //@codeCoverageIgnoreEnd
            }
            // 已激活的状态下才关联
            if ($zone->status == Zone::STATUS_OPEN_IN) {
                // 增加关联
                $ret = ZoneService::attachRelationChain($params['zone_id']);
                if ($ret !== true) {
                    //@codeCoverageIgnoreStart
                    DB::rollBack();
                    return $this->errorCode($ret);
                    //@codeCoverageIgnoreEnd
                }
            }
            DB::commit(); //事务结束
            return $this->success();

        } else {
            //新增广告位
            $count = ZoneService::getZoneCount($affiliateId, $params);
            if ($count > 0) {
                return $this->errorCode(5055);//@codeCoverageIgnore
            }
            DB::beginTransaction();  //事务开始
            //新增广告位
            $zone = Zone::store($affiliateId, $params);
            if (!$zone) {
                //@codeCoverageIgnoreStart
                LogHelper::warning("ZoneController store add zone faild: " . $params['zone_name']);
                DB::rollback();
                return $this->errorCode(5001);
                //@codeCoverageIgnoreEnd
            }
            //增加关联
            $zoneId = $zone->zoneid;
            $ret = ZoneService::attachRelationChain($zoneId);
            if ($ret !== true) {
                //@codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                //@codeCoverageIgnoreEnd
            }
            //@codeCoverageIgnoreStart
            if (isset($params['listtypeid'])) {
                DB::table('zone_list_type')
                    ->where('af_id', $affiliateId)
                    ->where('listtypeid', $params['listtypeid'])
                    ->update(['already_used' => 1]);
            }
            //@codeCoverageIgnoreEnd

            DB::commit(); //事务结束
            return $this->success(['zone_id' =>  $zoneId]);
        }
    }

    /**
     * 启用，停用广告位
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request)
    {
        $status = ArrayHelper::getRequiredIn(Zone::getStatusLabels());
        if (($ret = $this->validate($request, [
                'zone_id' => 'required|integer',
                'action' => "required|integer|in:{$status}"
            ], [], Zone::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $zoneId = intval($request->input('zone_id'));
        if ($zoneId <= 0) {
            return $this->errorCode(5000);
        }
        //媒体商ID
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $zone = Zone::find($zoneId);
        if ($zone->affiliateid != $affiliateId) {
            $this->errorCode(5004);//@codeCoverageIgnore
        }//@codeCoverageIgnore
        if ($zone->status == Zone::STATUS_SUSPEND) {
            DB::beginTransaction();  //事务开始
            // 更新操作：暂停->开放
            $zone->status = Zone::STATUS_OPEN_IN;
            if (!$zone->save()) {
                //@codeCoverageIgnoreStart
                DB::rollback();
                $this->errorCode(5001);
                //@codeCoverageIgnoreEnd
            }//@codeCoverageIgnore
            //关联广告位
            $ret = ZoneService::attachRelationChain($zoneId);
            if ($ret !== true) {
                //@codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode($ret);
                //@codeCoverageIgnoreEnd
            }
            DB::commit(); //事务结束
        } else {
            // 更新操作：开放->暂停
            DB::beginTransaction();  //事务开始

            $zone->status = Zone::STATUS_SUSPEND;
            if (!$zone->save()) {
                //@codeCoverageIgnoreStart
                DB::rollback();
                $this->errorCode(5001);
                //@codeCoverageIgnoreEnd
            }//@codeCoverageIgnore
            // 删除广告位关联
            $zone->banners()->detach();
            $zone->campaigns()->detach();

            DB::commit(); //事务结束
        }
        return $this->success();
    }

    /**
     * 新增，修改分类
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function categoryStore(Request $request)
    {
        $parentCategory = ArrayHelper::getRequiredIn(Category::getParentLabels());//获取父级分类
        $adTypes = implode(',', Zone::getAdTypeCategory());
        if (($ret = $this->validate($request, [
                'name' => 'required',
                'parent' => "required|integer",
                'ad_type' => "required|integer|in:{$adTypes}",
            ], [], Category::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();//获取所有参数
        //媒体商ID
        $params['affiliateid'] = Auth::user()->account->affiliate->affiliateid;
        $params['agencyid'] = Auth::user()->account->affiliate->agencyid;

        if (empty($params['category'])) {
            $count = Category::whereMulti([
                'media_id' => $params['agencyid'],
                'affiliateid' => $params['affiliateid'],
                'parent' => $params['parent'],
                'ad_type' => $params['ad_type'],
                'name' => $params['name']])
                ->count();
            if ($count > 0) {
                return $this->errorCode(5056);
            }

            //新增分类
            $params['platform'] = Campaign::PLATFORM_IPHONE_COPYRIGHT;
            //新增分类
            $category = Category::store($params);
            if (!$category) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
            
            //如果添加的是父分类并且在广告位分类中查找到有使用
            $zoneList = CategoryService::getZoneList($params['affiliateid'], $params['parent']);
            if (!empty($zoneList)) {
                foreach ($zoneList as $ck => $cv) {
                    //把新添加的分类附加到原来的分类里
                    $oac_category_id = array_merge(
                        explode(",", $cv->oac_category_id),
                        [$category->category_id]
                    );
                    //更新分类
                    Zone::where('zoneid', $cv->zoneid)
                        ->update([
                            'oac_category_id' => implode(",", $oac_category_id)
                        ]);
                    
                    //如果广告位是投放中的状态，则刷新关联关系
                    $zoneDetail = Zone::where('zoneid', $cv->zoneid)->first();
                    if ($zoneDetail->status == Zone::STATUS_OPEN_IN) {
                        ZoneService::detachRelationChain($cv->zoneid);
                        ZoneService::attachRelationChain($cv->zoneid);
                    }
                }
            }
            
            return $this->success(['category' => $category->category_id,
                'name' => $category->name, 'parent' => $category->parent]);
        } else {
            $count = Category::whereMulti([
                'media_id' => $params['agencyid'],
                'affiliateid' => $params['affiliateid'],
                'ad_type' => $params['ad_type'],
                'name' => $params['name']])
                ->where('category_id', '<>', $params['category'])
                ->count();
            if ($count > 0) {
                return $this->errorCode(5056);
            }

            //修改分类
            Category::where('category_id', $params['category'])->update(['name' => $params['name']]);
        }
        return $this->success();
    }

    /**
     * 删除分类
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function categoryDelete(Request $request)
    {
        if (($ret = $this->validate($request, [
                'category' => 'required',
            ], [], Zone::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $categoryId = $request->input('category');
        $count = Banner::whereRaw("FIND_IN_SET('{$categoryId}',category)")->count();
        if ($count > 0) {
            return $this->errorCode(5059);
        }

        //删除分类
        $category = Category::where('category_id', $categoryId)->delete();
        if (!$category) {
            return $this->errorCode(5001);
        }
        return $this->success();
    }

    /**
     * 模块列表
     *  @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function moduleList(Request $request)
    {
        $adType = $request->input('ad_type');
        $zoneType = array_keys(ZoneListType::getTypeLabels());
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $result = ZoneListType::where('af_id', $affiliateId)
            ->where('ad_type', $adType)
            ->select('id', 'listtypeid', 'type', 'listtype AS name', 'already_used')
            ->get()->toArray();
        $list = [];
        foreach ($zoneType as $item) {
            foreach ($result as $ret) {
                if ($item == $ret['type']) {
                    $list[$item][] = [
                        'id' => $ret['id'],
                        'listtypeid' => $ret['listtypeid'],
                        'name' => $ret['name'],
                        'already_used' => $ret['already_used']
                    ];
                }
            }
        }
        return $this->success($list);
    }

    /**
     * 模块添加，修改
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function moduleStore(Request $request)
    {
        $type = ArrayHelper::getRequiredIn(ZoneListType::getTypeLabels());//获取广告位模块类型
        $adType = implode(',', Zone::getAdTypeCategory());//获取广告位模块类型
        if (($ret = $this->validate($request, [
                'listtypeid' => 'required',
                'name' => 'required',
                'type' => "required|integer|in:{$type}",
                'ad_type' => "required|integer|in:{$adType}",
            ], [], ZoneListType::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (!preg_match(
            "/^[\x{4e00}-\x{9fa5}\w\@\-\{\}\[\]\(\)\（\）\｛\｝\【\】\#\s+]{2,32}$/u",
            $params['name']
        )
        ) {
            return $this->errorCode(5057);
        }
        //媒体商ID
        $params['affiliateid'] = Auth::user()->account->affiliate->affiliateid;
        if (empty($params['id'])) {
            //检测是否存在重复数据
            $count = ZoneListType::whereMulti(
                [
                    'listtypeid' => $params['listtypeid'],
                    'af_id' => $params['affiliateid'],
                    'type' => $params['type'],
                    'ad_type' => $params['ad_type']
                ]
            )
            ->count();
            if ($count > 0) {
                return $this->errorCode(5058);
            }
            // @codeCoverageIgnoreStart
            //添加模块列表
            $ret = ZoneListType::store($params);
            if (!$ret) {
                return $this->errorCode(5001);
            }
            return $this->success([
                'id' => $ret->id,
                'listtypeid' => $ret->listtypeid,
                'name' => $ret->listtype,
                'type' => $ret->type,
                'ad_type' => $ret->ad_type,
            ]);
            // @codeCoverageIgnoreEnd
        } else {
            //修改模块列表
            $listTypeInfo = ZoneListType::whereMulti([
                'id' => $params['id'],
            ])->first();
            
            ZoneListType::updateListType($params);
             //@codeCoverageIgnoreStart
             //需要同步更新广告位表广告位名称
            $zonesInfo = Zone::whereMulti([
                'affiliateid' => $params['affiliateid'],
                'type' => $params['type'],
                'ad_type' => $params['ad_type'],
                'listtypeid' => $params['listtypeid']
            ])->get();

            if (count($zonesInfo) > 0) {
                foreach ($zonesInfo as $z) {
                    $newZoneName = str_replace($listTypeInfo->listtype, $params['name'], $z->zonename);
                    Zone::where('zoneid', $z->zoneid)
                        ->update([
                            'zonename' => $newZoneName,
                        ]);
                }
            }
            //@codeCoverageIgnoreEnd
        }
        return $this->success();
    }

    /**
     * @codeCoverageIgnore
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function moduleDelete(Request $request)
    {
        $adType = implode(',', Zone::getAdTypeCategory());//获取广告位模块类型
        if (($ret = $this->validate($request, [
                'listtypeid' => 'required',
                'ad_type' => "required|integer|in:{$adType}",
            ], [], ZoneListType::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();

        $ret = ZoneListType::whereMulti([
            'id' => $params['id'],
            'listtypeid' => $params['listtypeid'],
            'af_id' => Auth::user()->account->affiliate->affiliateid,
            'ad_type' => $params['ad_type'],
            'already_used' => 0,
        ])->delete();
        if (!$ret) {
            return $this->errorCode(5001);
        }
        return $this->success();
    }

    /**
     * 获取媒体商的类型
     */
    public function getAffiliateKind()
    {
        return Auth::user()->account->affiliate->kind;
    }
    
    
    /**
     * 获取媒体商支持广告类型
     * @return \Illuminate\Http\Response
     */
    public function adType()
    {
        return $this->success([
            'ad_type' => explode(',', Auth::user()->account->affiliate->ad_type),
        ]);
    }
    
    
    /**
     * 修改广告位的值
     */
    public function update(Request $request)
    {
        $zoneId = $request->input('zoneid');
        $flow_percent = $request->input('flow_percent');
        $zone = Zone::where('zoneid', $zoneId)
                ->where('affiliateid', Auth::user()->account->affiliate->affiliateid)
                ->first();
        if (empty($zone)) {
            return $this->errorCode(5002);
        }
        
        //判断是否为流量广告位，不是流量广告位不允许修改
        if (Zone::TYPE_FLOW != $zone->type) {
            return $this->errorCode(5053);
        }
        
        //整型
        $flow_percent = intval($flow_percent);
        if (0 <= $flow_percent && $flow_percent <= 100) {
            $zone->flow_percent = $flow_percent;
            if ($zone->save()) {
                return $this->success();
            } else {
                return $this->errorCode(5001);
            }
        } else {
            return $this->errorCode(5052);
        }
    }
}
