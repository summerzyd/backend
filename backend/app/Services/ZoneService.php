<?php
namespace App\Services;

use App\Components\Helper\DateHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Components\Helper\UrlHelper;
use App\Models\AppInfo;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use App\Components\Formatter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use App\Models\Affiliate;

class ZoneService
{

    /**
     * 获取当前帐号下的所有广告位列表
     * @param int $affiliate_id 媒体ID
     * @return array
     */
    public static function getZones(
        $affiliateId,
        $adType = Zone::AD_TYPE_APP_MARKET,
        $pageNo = DEFAULT_PAGE_NO,
        $pageSize = DEFAULT_PAGE_SIZE,
        $search = null,
        $sort = null,
        $filter = []
    ) {
        //应用广告位包括搜索广告位
        $adType = Zone::getAdTypeToAdType($adType);
        $prefix = DB::getTablePrefix();
        $currentKind = intval(Session::get('kind'));
        $select = DB::table('zones as z')
            ->leftJoin('affiliates AS af', function ($join) {
                $join->on('z.affiliateid', '=', 'af.affiliateid');
            })
            ->leftJoin('client_version', function ($join) {
                $join->on('client_version.versionid', '=', 'z.versionid')
                    ->on('client_version.af_id', '=', 'z.affiliateid');
            })->leftJoin('zone_list_type', function ($join) {
                $join->on('zone_list_type.listtypeid', '=', 'z.listtypeid')
                     ->on('zone_list_type.af_id', '=', 'z.affiliateid')
                     ->on('zone_list_type.type', '=', 'z.type')
                     ->on('zone_list_type.ad_type', '=', 'z.ad_type');
            })
            ->where('z.affiliateid', '=', $affiliateId)
            ->whereIn('z.ad_type', $adType);
            // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where('z.zonename', 'like', '%' . $search . '%');
        }
        if (!empty($filter)) {
            //根据媒体所接受的平台，自动匹配相应的流量广告位
            foreach ($filter as $k => $v) {
                if ('' != $v) {
                    //广告位类型
                    if ('type' == $k) {
                        if (Zone::TYPE_FLOW == $v) {
                            $select->where("z.{$k}", $v)
                                   ->whereRaw("{$prefix}z.platform & {$prefix}af.app_platform > 0");
                        } else {
                            $select->where("z.{$k}", $v);
                        }
                    } elseif ('listtypeid' == $k) {
                        $select->where("zone_list_type.id", $v);
                    } else {
                        $select->where("z.{$k}", $v);
                    }
                } else {
                    //广告位类型
                    if ('type' == $k) {
                        //如果是自营，要显示流量广告位，联盟则不需要显示流量广告位
                        if (Affiliate::KIND_SELF == $currentKind) {
                            //如果是搜索，则允许搜出流量广告位
                            if (is_null($search)) {
                                $select->where('z.type', '<>', Zone::TYPE_FLOW);
                            } else {
                                $select->whereRaw("{$prefix}z.platform & {$prefix}af.app_platform > 0");
                            }
                            
                            //如果模块为空
                            if ('' == $filter['platform']) {
                                if (is_null($search)) {
                                    if ('' == $filter['listtypeid']) {
                                        $select->orWhere(function ($query) use ($affiliateId, $prefix) {
                                            $query->where('z.affiliateid', $affiliateId)
                                            ->whereRaw("{$prefix}z.platform & {$prefix}af.app_platform > 0")
                                            ->where('z.type', Zone::TYPE_FLOW);
                                        });
                                    }
                                }
                            } else {
                                //把符合条件的流量广告位也显示出来
                                if ('' == $filter['listtypeid']) {
                                    if (is_null($search)) {
                                        $platForm = $filter['platform'];
                                        $select->orWhere(function ($query) use ($affiliateId, $prefix, $platForm) {
                                            $query->where('z.affiliateid', $affiliateId)
                                            ->where("z.platform", $platForm)
                                            ->where('z.type', Zone::TYPE_FLOW);
                                        });
                                    }
                                }
                            }
                        } else {
                            $select->where('z.type', '<>', Zone::TYPE_FLOW);
                        }
                    }
                }
            }
        }
        
        $select->select(
            'z.zoneid as zone_id',
            'z.ad_type',
            'z.type',
            'z.status',
            'z.platform',
            'z.listtypeid',
            'z.oac_category_id as category',
            'z.rank_limit',
            'z.zonename as zone_name',
            'z.description',
            'z.position',
            'z.ad_refresh',
            'z.flow_percent',
            'client_version.version',
            'zone_list_type.listtype as list_type_name'
        );

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
            if ($sortAttr == 'status') {
                $select->orderBy('z.status', $sortType);
            } elseif ($sortAttr == 'zone_id') {
                $select->orderBy('z.zoneid', $sortType);
            } elseif ($sortAttr == 'ad_type') {
                $select->orderBy('z.ad_type', $sortType);
            } else {
                $select->orderBy('z.'.$sortAttr, $sortType);
            }
        } else {
            $select->orderBy('z.status', 'asc');
        }
        $data = $select->get();
        //序列化为数组
        $data = json_decode(json_encode($data), true);
        $list = [];
        foreach ($data as $temp) {
            //调用分类查询
            $categories = CategoryService::getCategories($temp['category'], $affiliateId);
            $temp['category_label'] = $categories['category_label'];
            $temp['parent'] = $categories['parent'];
            $temp['type_label'] = Zone::getZoneLabels($temp['ad_type'], $temp['type']);
            $temp['status_label'] = Zone::getStatusLabels($temp['status']);
            $temp['platform_label'] = Campaign::getPlatformLabels($temp['platform']);
            $temp['rank_limit_label'] = (Zone::TYPE_FLOW != $temp['type']) ?
            AppInfo::getRankStatusLabels($temp['rank_limit']) : '-';
            $temp['description'] = empty($temp['description']) ? '' : UrlHelper::imageFullUrl($temp['description']);
            $temp['self_flow_percent'] = (100 - $temp['flow_percent']);
            if ($temp['ad_refresh'] == 0) {
                $temp['ad_refresh_label'] = '不刷新';
            } else {
                $temp['ad_refresh_label'] = $temp['ad_refresh'];
            }
            $temp['action'] = 0;//$perm;
            $list[] = $temp;
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
     * 增加广告位与应用的关联
     * @param int $zone_id 广告位ID
     * @return bool
     */
    public static function attachRelationChain($zoneId)
    {
        $zone = Zone::find($zoneId);
        if (!$zone) {
            return 5002;
        }
        $prefix = DB::getTablePrefix();
        $affiliateId = $zone->affiliate->affiliateid;
        $platform = $zone->platform;
        $rankLimit = $zone->rank_limit;
        $categoryId = $zone->oac_category_id;
        //广告类型为不限时，支持文字链和banner广告
        $adType = Zone::getZoneTypeToAdType($zone->ad_type);
        
        $campaigns = [];
        $banners = [];

        $select = DB::table('campaigns AS camp')
            ->leftJoin('banners AS bann', 'camp.campaignid', '=', 'bann.campaignid')
            ->where('camp.status', '=', Campaign::STATUS_DELIVERING)
            ->where('bann.affiliateid', '=', $affiliateId)
            ->whereRaw("({$prefix}camp.platform & {$platform}) > 0")
            ->where('bann.app_rank', '<=', $rankLimit)
            ->where('bann.status', Banner::STATUS_PUT_IN)
            ->whereIn('camp.ad_type', $adType)
            ->select('camp.campaignid', 'camp.ad_type', 'bann.bannerid', 'bann.category');
        
        $rows = $select->get();
        $campaigns = [];
        $banners = [];
        if (!empty($rows)) {
            foreach ($rows as $k => $v) {
                //广告位如果为空或者为0，则不限制，可以直接投放
                if (empty($categoryId)) {
                    $campaigns[] = $v->campaignid;
                    $banners[] = $v->bannerid;
                } else {
                    //如果广告位不为空或者不为0
                    if (in_array($v->ad_type, Config::get('biddingos.withCategory'))) {
                        //用 banner的分类与广告位的分类进行匹配
                        $result =   Zone::where('zoneid', $zoneId)
                        ->whereRaw("has_intersection(oac_category_id,'{$v->category}') > 0")
                        ->count();
                        if (1 == $result) {
                            $campaigns[] = $v->campaignid;
                            $banners[] = $v->bannerid;
                        }
                    } else {
                        //不用设置分类的，直接可以使用相应的广告位bannerid
                        $campaigns[] = $v->campaignid;
                        $banners[] = $v->bannerid;
                    }
                }
            }//end foreach
        }
        
        // 删除广告位关联
        $ret = self::detachRelationChain($zoneId);
        if ($ret !== true) {
            return $ret;
        }
        
        //增加关联
        if (!empty($campaigns)) {
            $zone->campaigns()->sync($campaigns);
        }
        if (!empty($banners)) {
            $zone->banners()->sync($banners);
        }
        return true;
    }

    /**
     * 解除广告位与应用的关联
     * @param int $zone_id 广告位ID
     * @return bool
     */
    public static function detachRelationChain($zoneId)
    {
        $zone = Zone::find($zoneId);
        if (!$zone) {
            return 5002;
        }
        $zone->banners()->detach();
        $zone->campaigns()->detach();
        return true;
    }

    /**
     * 广告位数量
     * @param $affiliateId
     * @param $params
     * @return mixed
     */
    public static function getZoneCount($affiliateId, $params)
    {
        $platform = isset($params['platform']) ?  $params['platform'] : '';
        //判断是否是重复的广告位
        if ($params['type'] == Zone::TYPE_NORMAL) {//普通广告位
            $count = Zone::where('affiliateid', $affiliateId)
                ->where('position', $params['position'])
                ->where(DB::raw("(platform & {$platform})"), '>', 0)
                ->where('listtypeid', $params['listtypeid'])
                ->where('zonename', $params['zone_name'])
                ->count();
        } elseif ($params['type'] == Zone::TYPE_KEYWORD) {  //搜索广告位
            $count = Zone::where('affiliateid', $affiliateId)
                ->where('position', $params['position'])
                ->where(DB::raw("(platform & {$platform})"), '>', 0)
                ->where('type', $params['type'])
                ->where('zonename', $params['zone_name'])
                ->count();
        } elseif (Zone::TYPE_PICTURE == $params['type']
            || $params['type'] == Zone::TYPE_SCREEN
        ) {
            // Banner 及插屏 广告位
            $count = Zone::where('affiliateid', $affiliateId)
                ->where('type', $params['type'])
                ->where('zonename', $params['zone_name'])
                ->count();
        } elseif ($params['type'] == Zone::TYPE_FEEDS) {
            // feeds 广告位
            $count = Zone::where('affiliateid', $affiliateId)
                ->where('type', $params['type'])
                ->where('zonename', $params['zone_name'])
                ->count();
        } else {
            $count = 0;
        }
        return $count;
    }

    /**
     * 获取广告位近期平均曝光
     * @param $zoneIds 广告位数组
     * @param $banner
     * @return array
     */
    public static function getZoneImpressions($zoneIds, $banner)
    {
        /* 当前时间-广告审核时间不足7天，
                          则计算审核时间至昨天曝光平均值。
                     当前时间-广告审核时间超过7天，
                          则计算昨天起7天曝光平均值。*/
        $startData = date('Y-m-d', strtotime($banner->affiliate_checktime));
        $endDate = date('Y-m-d', strtotime("-1 day"));
        $days = DateHelper::getDays($startData, $endDate);
        if ($days > 7) {
            $startData = date('Y-m-d', strtotime("-8 day"));
        }

        //获取最近曝光
        $impressions = DB::table('data_hourly_daily')
            ->select('zone_id', DB::raw("SUM(impressions) AS impressions"))
            ->where('ad_id', $banner->bannerid)
            ->where('campaign_id', $banner->campaignid)
            ->where('date', '>=', $startData)
            ->where('date', '<=', $endDate)
            ->whereIn('zone_id', $zoneIds)
            ->get();

        $listImpressions = [];
        foreach ($impressions as $item) {
            if (empty($item['zone_id'])) {
                continue;
            }
            $listImpressions[$item['zone_id']] = Formatter::asDecimal($item['impressions'] / $days, 0);
        }

        return $listImpressions;
    }

    /**
     * 广告位加价排序
     * @param $campaignId
     * @return array
     */
    public static function getZonePrice($zones, $banner)
    {
        $listImpressions = ZoneService::getZoneImpressions(array_column($zones, 'zoneid'), $banner);

        $impressions = [];
        $price = [];
        $list = [];
        foreach ($zones as $k => $v) {
            $price[$k] = $v['price_up'];
            $impressions[$k] = isset($listImpressions[$v['zoneid']]) ? $listImpressions[$v['zoneid']] : 0;
            $list[] = [
                'id' => $v['id'],
                'zoneid' => $v['zoneid'],
                'zonename' => $v['zonename'],
                'platform' => $v['platform'],
                'description' => $v['description'],
                'price_up' => empty($v['price_up']) ? 0 : $v['price_up'],
                'impressions' => isset($listImpressions[$v['zoneid']]) ? $listImpressions[$v['zoneid']] : 0,
            ];
        }

        array_multisort($price, SORT_NUMERIC, SORT_DESC, $impressions, SORT_NUMERIC, SORT_DESC, $list);

        return $list;
    }

    /**
     * 获取指定广告任务在广告位中的排名状况
     * @param array|int $campaignId     广告应用ID
     * @param int $dataType
     *  可选】指定返回结果，默认不指定；
     * （1=同时返回没有竞争力的广告位）
     * @return array
     */
    public static function getRankOfZone($campaignId, $dataType = 0)
    {
        if (empty($campaignId)) {
            return array();
        } elseif (!is_array($campaignId)) {
            $campaignId = array($campaignId);
        }
        //获取可用的广告位列表
        $zone_rank_list = array();
        \DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('ad_zone_assoc as a')
            ->leftjoin('zones', 'zones.zoneid', '=', 'a.zone_id')
            ->leftJoin('banners', 'a.ad_id', '=', 'banners.bannerid')
            ->whereIn('banners.campaignid', $campaignId)
            ->where('a.zone_id', '!=', '0')
            ->where('zones.status', '=', Zone::STATUS_OPEN_IN)
            ->whereIn('zones.type', [Zone::TYPE_NORMAL, Zone::TYPE_KEYWORD,])
            ->select(DB::raw(
                '(select count(1) from up_ad_zone_assoc as b where up_a.zone_id = b.zone_id) as total'
                . ',(select count(1) from up_ad_zone_assoc as c where up_a.zone_id =
                c.zone_id and c.priority >= up_a.priority) as pos'
                . ',up_banners.campaignid'
                . ',up_zones.zonename'
                . ',up_zones.type'
                . ',up_zones.position'
                . ',up_a.priority'
                . ',up_a.zone_id'
                . ',up_zones.description'
            ));
        if ($dataType != 1) {
            $select->where('a.priority', '!=', '0');
        }
        $zones = $select->get();

        foreach ($zones as $info) {
            if (!isset($zone_rank_list[$info['campaignid']])) {
                $zone_rank_list[$info['campaignid']] = array();
            }
            $info['rank'] = CampaignService::calculateRank($info);
            unset($info['total'], $info['pos'], $info['priority']);
            $zone_rank_list[$info['campaignid']][$info['zone_id']] = $info;
        }

        //竞争力显示排序
        $temp = array();
        foreach ($zone_rank_list as $k => &$rankList) {
            foreach ($rankList as $key => $row) {
                $temp[$row['zone_id']] = $row['rank'];
            }
        }
        return $temp;
    }

    /**
     * 查看广告位加价列表
     * @param $campaignId
     * @return array
     */
    public static function getZonesList($campaignId)
    {
        //获取banner信息
        $banner = Banner::where('campaignid', $campaignId)->first();
        \DB::setFetchMode(\PDO::FETCH_ASSOC);
        $zones = \DB::table('zones AS z')
            ->leftJoin('ad_zone_price AS azp', 'z.zoneid', '=', 'azp.zone_id')
            ->where('ad_id', $banner->bannerid)
            ->where('platform', $banner->campaign->platform)
            ->where('z.status', Zone::STATUS_OPEN_IN)
            ->select('azp.id', 'z.zoneid', 'z.zonename', 'z.description', 'azp.price_up', 'z.platform')
            ->get();
        $zones = ZoneService::getZonePrice($zones, $banner);//获取广告位
        $ranks = ZoneService::getRankOfZone($campaignId);//获取广告位排名
        $list = [];
        $sortRank = [];
        $sortPrice = [];
        $sortKey = [];
        foreach ($zones as $k => $v) {
            $sortRank[$k] = isset($ranks[$v['zoneid']]) ?
                ($ranks[$v['zoneid']] == 0 ? 10 : $ranks[$v['zoneid']]) : 10;
            $sortPrice[$k] = $v['price_up'];
            $sortKey[$k] = StringHelper::getFirstCharter($v['zonename']);
            $list[] = [
                'id' => $v['id'],
                'zoneid' => $v['zoneid'],
                'zonename' => $v['zonename'],
                'platform' => $v['platform'],
                'description' => $v['description'],
                'impressions' => $v['impressions'],
                'price_up' => $v['price_up'],
                'rank' => isset($ranks[$v['zoneid']]) ? $ranks[$v['zoneid']] : 0,
            ];
        }
        //先按竞争力从小到大排序，然后按照价格从小到大
        array_multisort(
            $sortRank,
            SORT_NUMERIC,
            SORT_ASC,
            $sortPrice,
            SORT_NUMERIC,
            SORT_ASC,
            $sortKey,
            SORT_STRING,
            SORT_ASC,
            $list
        );
        return $list;
    }
}
