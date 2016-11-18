<?php

namespace App\Models;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * This is the model class for table "zones".
 * @property integer $zoneid mediumint
 * @property integer $affiliateid mediumint
 * @property string $zonename varchar
 * @property string $description varchar
 * @property integer $delivery smallint
 * @property integer $zonetype smallint
 * @property string $category text
 * @property integer $width smallint
 * @property integer $height smallint
 * @property string $ad_selection text
 * @property string $chain text
 * @property string $prepend text
 * @property string $append text
 * @property integer $appendtype tinyint
 * @property string $forceappend enum
 * @property integer $inventory_forecast_type smallint
 * @property string $comments text
 * @property string $cost decimal
 * @property integer $cost_type smallint
 * @property string $cost_variable_id varchar
 * @property string $technology_cost decimal
 * @property integer $technology_cost_type smallint
 * @property string $updated datetime
 * @property integer $block int
 * @property integer $capping int
 * @property integer $session_capping int
 * @property string $what text
 * @property integer $as_zone_id int
 * @property integer $is_in_ad_direct tinyint
 * @property string $rate decimal
 * @property string $flow_percent decimal
 * @property string $pricing varchar
 * @property string $oac_category_id varchar
 * @property string $ext_adselection varchar
 * @property integer $show_capped_no_cookie tinyint
 * @property integer $platform tinyint
 * @property integer $rank_limit tinyint
 * @property integer $status tinyint
 * @property integer $type tinyint
 * @property integer $position smallint
 * @property string $created date
 * @property string $versionid varchar
 * @property string $listtypeid varchar
 * @property integer $ad_type tinyint
 * @property integer $ad_spec tinyint
 * @property integer $ad_refresh int
 * @property string $updated_time timestamp
 */
class Zone extends BaseModel
{
    const REFRESH_NO = 0;//不刷新
    const REFRESH_30 = 30;//30分钟
    const REFRESH_60 = 60;//60分钟
    const REFRESH_90 = 90;//90分钟
    const REFRESH_120 = 120;//120分钟

    /**
     * 广告位类型
     */
    const TYPE_NORMAL = 0;//普通广告位
    const TYPE_KEYWORD = 1;//关键词广告位
    const TYPE_PICTURE = 2;//纯图片
    const TYPE_FLOW = 3;//流量广告位
    const TYPE_FEEDS = 4;//Feeds广告大图文
    const TYPE_SCREEN = 5;//插屏
    const TYPE_APP_STORE = 71;//app store

    const AD_TYPE_APP_MARKET = 0;
    const AD_TYPE_BANNER_IMG = 1;
    const AD_TYPE_FEEDS = 2;
    const AD_TYPE_HALF_SCREEN = 3;
    const AD_TYPE_FULL_SCREEN = 4;
    const AD_TYPE_BANNER_TEXT_LINK = 5;
    const AD_TYPE_BANNER_ALL = 6;
    const AD_TYPE_APP_STORE = 71;
    const AD_TYPE_VIDEO = 91;

    /**
     * 广告位状态
     */
    const STATUS_OPEN_IN = 0;
    const STATUS_SUSPEND = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'zones';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'zoneid';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'affiliateid',
        'zonename',
        'description',
        'delivery',
        'zonetype',
        'category',
        'width',
        'height',
        'ad_selection',
        'chain',
        'prepend',
        'append',
        'appendtype',
        'forceappend',
        'inventory_forecast_type',
        'comments',
        'cost',
        'cost_type',
        'cost_variable_id',
        'technology_cost',
        'technology_cost_type',
        'block',
        'capping',
        'session_capping',
        'what',
        'as_zone_id',
        'is_in_ad_direct',
        'rate',
        'flow_percent',
        'pricing',
        'oac_category_id',
        'ext_adselection',
        'show_capped_no_cookie',
        'platform',
        'rank_limit',
        'status',
        'type',
        'position',
        'versionid',
        'listtypeid',
        'ad_type',
        'ad_spec',
        'ad_refresh',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'zoneid' => trans('Zone.zoneid'),
            'affiliateid' => trans('Zone.affiliateid'),
            'zonename' => trans('Zone.zonename'),
            'description' => trans('Zone.description'),
            'delivery' => trans('Zone.delivery'),
            'zonetype' => trans('Zone.zonetype'),
            'category' => trans('Zone.category'),
            'width' => trans('Zone.width'),
            'height' => trans('Zone.height'),
            'ad_selection' => trans('Zone.ad_selection'),
            'chain' => trans('Zone.chain'),
            'prepend' => trans('Zone.prepend'),
            'append' => trans('Zone.append'),
            'appendtype' => trans('Zone.appendtype'),
            'forceappend' => trans('Zone.forceappend'),
            'inventory_forecast_type' => trans('Zone.inventory_forecast_type'),
            'comments' => trans('Zone.comments'),
            'cost' => trans('Zone.cost'),
            'cost_type' => trans('Zone.cost_type'),
            'cost_variable_id' => trans('Zone.cost_variable_id'),
            'technology_cost' => trans('Zone.technology_cost'),
            'technology_cost_type' => trans('Zone.technology_cost_type'),
            'updated' => trans('Zone.updated'),
            'block' => trans('Zone.block'),
            'capping' => trans('Zone.capping'),
            'session_capping' => trans('Zone.session_capping'),
            'what' => trans('Zone.what'),
            'as_zone_id' => trans('Zone.as_zone_id'),
            'is_in_ad_direct' => trans('Zone.is_in_ad_direct'),
            'rate' => trans('Zone.rate'),
            'pricing' => trans('Zone.pricing'),
            'oac_category_id' => trans('Zone.oac_category_id'),
            'ext_adselection' => trans('Zone.ext_adselection'),
            'show_capped_no_cookie' => trans('Zone.show_capped_no_cookie'),
            'platform' => trans('Zone.platform'),
            'rank_limit' => trans('Zone.rank_limit'),
            'status' => trans('Zone.status'),
            'type' => trans('Zone.type'),
            'position' => trans('Zone.position'),
            'created' => trans('Zone.created'),
            'versionid' => trans('Zone.versionid'),
            'listtypeid' => trans('Zone.listtypeid'),
            'ad_type' => trans('Zone.ad_type'),
            'ad_spec' => trans('Zone.ad_spec'),
            'ad_refresh' => trans('Zone.ad_refresh'),
            'updated_time' => trans('Zone.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 返回关联该广告位的所有广告计划
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function campaigns()
    {
        return $this->belongsToMany('App\Models\Campaign', 'placement_zone_assoc', 'zone_id', 'placement_id');
    }
    /**
     * 返回关联该广告位的所有广告
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function banners()
    {
        return $this->belongsToMany('App\Models\Banner', 'ad_zone_assoc', 'zone_id', 'ad_id');
    }

    /**
     * 返回该广告位所属媒体
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function affiliate()
    {
        return $this->belongsTo('App\Models\Affiliate', 'affiliateid', 'affiliateid');
    }

    /**
     * 获取广告位状态类型标签数组或单个标签
     * @var $key
     * @return array or string
     */
    public static function getStatusLabels($key = null)
    {
        $data = [
            self::STATUS_OPEN_IN => '启用中',
            self::STATUS_SUSPEND => '已暂停',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取广告位刷新标签数组或单个标签
     * @var $key
     * @return array or string
     */
    public static function getRefreshLabels($key = null)
    {
        $data = [
            self::REFRESH_NO => '不刷新',
            self::REFRESH_30 => '30分钟',
            self::REFRESH_60 => '60分钟',
            self::REFRESH_90 => '90分钟',
            self::REFRESH_120 => '120分钟',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取广告位类型标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getTypeLabels($key = null)
    {
        $data = [
            self::TYPE_NORMAL => '普通广告位',
            self::TYPE_KEYWORD => '关键词广告位',
            self::TYPE_PICTURE => '纯图片',
            self::TYPE_FLOW => '流量广告位',
            self::TYPE_FEEDS => '大图文',
            self::TYPE_SCREEN => '插屏半屏',
            self::TYPE_APP_STORE => 'App Store',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    public static function getAdTypeLabels($key = null)
    {
        $data =[
            self::AD_TYPE_APP_MARKET => '应用市场',
            self::AD_TYPE_BANNER_IMG => 'Banner纯图片',
            self::AD_TYPE_FEEDS => 'Feeds',
            self::AD_TYPE_HALF_SCREEN => '插屏半屏',
            self::AD_TYPE_FULL_SCREEN => '插屏全屏',
            self::AD_TYPE_BANNER_TEXT_LINK => 'Banner文字链',
            self::AD_TYPE_BANNER_ALL => '不限',
            self::AD_TYPE_APP_STORE => 'App Store',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 广告类型映射
     * @param $adType
     * @return mixed
     */
    public static function getAdTypeToAdType($adType)
    {
        $data = [
            self::AD_TYPE_APP_MARKET => [self::AD_TYPE_APP_MARKET],
            self::AD_TYPE_BANNER_IMG => [
                self::AD_TYPE_BANNER_IMG,
                self::AD_TYPE_BANNER_TEXT_LINK,
                self::AD_TYPE_BANNER_ALL,
            ],
            self::AD_TYPE_FEEDS => [self::AD_TYPE_FEEDS],
            self::AD_TYPE_HALF_SCREEN => [
                self::AD_TYPE_HALF_SCREEN, self::AD_TYPE_FULL_SCREEN],
            self::AD_TYPE_APP_STORE => [self::AD_TYPE_APP_STORE],
        ];
        return $data[$adType];
    }

    /**
     * 指定分类需要区分的ad_type
     * @var $key
     * @return array or string
     */
    public static function getAdTypeCategory()
    {
        return [
            self::AD_TYPE_APP_MARKET,
            self::AD_TYPE_APP_STORE,
        ];
    }

    /**
     *
     * @param $adType
     * @param $type
     * @return string
     */
    public static function getZoneLabels($adType, $type)
    {
        switch ($type) {
            case self::TYPE_NORMAL:
                $zone = '普通广告位';
                break;
            case self::TYPE_KEYWORD:
                $zone = '关键词广告位';
                break;
            case self::TYPE_PICTURE:
                if ($adType == Zone::AD_TYPE_BANNER_IMG) {
                    $zone = '纯图片';
                } elseif ($adType == Zone::AD_TYPE_BANNER_TEXT_LINK) {
                    $zone = '文字链';
                } else {
                    $zone = '不限';
                }
                break;
            case self::TYPE_FLOW:
                $zone = '流量广告位';
                break;
            case self::TYPE_FEEDS:
                $zone = '大图文';
                break;
            case self::TYPE_SCREEN:
                if ($adType == Zone::AD_TYPE_FULL_SCREEN) {
                    $zone = '插屏全屏';
                } else {
                    $zone = '插屏半屏';
                }
                break;
            default:
                $zone = '';
        }
        return $zone;
    }

    /**
     * 广告位对应广告类型
     * @param $zoneType
     * @return mixed
     *
     * Add by arke.wu 2016-05-24
     * 根据广告位的 ad_type 类型返回对应广告类型
     * 0（应用市场）1（纯图片 Banner）2（Feeds）3（插屏半屏）
     * 4（插屏全屏）5（文字链 Banner）6（Banner不限）
     * 71(app Store)
     */
    public static function getZoneTypeToAdType($adType)
    {
        $data = [
            self::AD_TYPE_APP_MARKET => [Campaign::AD_TYPE_APP_MARKET],
            self::AD_TYPE_BANNER_IMG => [Campaign::AD_TYPE_BANNER_IMG],
            self::AD_TYPE_FEEDS => [Campaign::AD_TYPE_FEEDS],
            self::AD_TYPE_HALF_SCREEN => [Campaign::AD_TYPE_HALF_SCREEN],
            self::AD_TYPE_FULL_SCREEN => [Campaign::AD_TYPE_FULL_SCREEN],
            self::AD_TYPE_BANNER_TEXT_LINK => [Campaign::AD_TYPE_BANNER_TEXT_LINK],
            self::AD_TYPE_BANNER_ALL => [
                Campaign::AD_TYPE_BANNER_IMG,
                Campaign::AD_TYPE_BANNER_TEXT_LINK,
            ],
            self::AD_TYPE_APP_STORE => [Campaign::AD_TYPE_APP_STORE,],
        ];
        return $data[$adType];
    }


    /**
     * 新增广告位
     * @param $affiliateId
     * @param $params
     * @return Zone|null
     */
    public static function store($affiliateId, $params)
    {
        // 新建广告位
        $zone = new Zone();
        $zone->affiliateid = $affiliateId;
        $zone->zonename = $params['zone_name'];
        $zone->type = $params['type'];
        $zone->ad_type = $params['ad_type'];
        $zone->delivery = 58;
        $zone->zonetype = 3;
        if (!empty($params['listtypeid'])) {
            $zone->listtypeid = $params['listtypeid'];
        }
        if (!empty($params['platform'])) {
            $zone->platform = $params['platform'];
        } else {
            $zone->platform = Campaign::PLATFORM_IOS_ANDROID;
        }
        if (!empty($params['rank_limit'])) {
            $zone->rank_limit = $params['rank_limit'];
        } else {
            $zone->rank_limit = AppInfo::RANK_S;
        }
        if (!empty($params['category'])) {
            $zone->oac_category_id = $params['category'];
        } else {
            $zone->oac_category_id = 0;
        }
        
        if (!empty($params['flow_percent'])) {
            $zone->flow_percent = $params['flow_percent'];
        } else {
            $zone->flow_percent = 0;
        }
        
        if (!empty($params['description'])) {
            $zone->description = $params['description'];
        }
        if (!empty($params['position'])) {
            $zone->position = $params['position'];
        }
        if (!empty($params['ad_refresh'])) {
            $zone->ad_refresh = $params['ad_refresh'];
        }
        if ($zone->save()) {
            return $zone;
        } else {
            unset($zone);
            return null;
        }
    }

    /**
     * 修改广告位
     * @param $params
     * @return null
     */
    public static function updateZone($params)
    {
        $zone = Zone::find($params['zone_id']);
        $zone->rank_limit = $params['rank_limit'];
        $zone->oac_category_id = $params['category'];
        $zone->ad_type = $params['ad_type'];
        $zone->type = $params['type'];
        $zone->ad_refresh = $params['ad_refresh'];
        $zone->description = $params['description'];
        $zone->zonename = $params['zone_name'];
        $zone->flow_percent = $params['flow_percent'];
        if ($zone->save()) {
            return $zone;
        } else {
            unset($zone);
            return null;
        }
    }
}
