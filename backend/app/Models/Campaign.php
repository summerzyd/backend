<?php

namespace App\Models;

use Auth;

/**
 * This is the model class for table "campaigns".
 * @property integer $campaignid mediumint 推广计划ID
 * @property string $campaignname varchar 应用ID
 * @property integer $clientid mediumint 广告主ID
 * @property integer $views int 展示量
 * @property integer $clicks int 点击量
 * @property integer $conversions int 预订的转化数
 * @property integer $priority int 优先级
 * @property integer $weight tinyint 权重
 * @property integer $target_impression int 曝光量
 * @property integer $target_click int 点击
 * @property integer $target_conversion int 转化
 * @property string $anonymous enum
 * f：在报表中显示广告主和媒体名称信息，
 * t：在报表中显示广告主和媒体名称信息
 * @property integer $companion smallint 0：不关联同页广告，1：同页广告关联
 * @property string $comments text 注释
 * @property string $revenue decimal 出价
 * @property integer $revenue_type smallint 计费类型
 * @property string $updated datetime
 * @property integer $block int
 * @property integer $capping int 限定广告计划曝光量
 * @property integer $session_capping int 限定广告计划曝光量
 * @property integer $an_campaign_id int 上级ADN对应广告计划ID
 * @property integer $as_campaign_id int Google AdSense对应广告计划ID
 * @property integer $status int
 * 广告状态
 * 0：运行中
 * 1：已暂停
 * 4：草稿
 * 10：待审核
 * 11：未通过审核
 * 15：停止投放
 * @property integer $an_status int 保存上次广告状态
 * @property integer $as_reject_reason int
 * @property integer $hosted_views int
 * @property integer $hosted_clicks int
 * @property integer $viewwindow int
 * @property integer $clickwindow int
 * @property string $ecpm decimal ecpm
 * @property integer $min_impressions int
 * @property integer $ecpm_enabled tinyint
 * @property string $activate_time datetime
 * @property string $expire_time datetime
 * @property integer $type tinyint 广告计划类型 1抄底，2普通，3排他
 * @property integer $show_capped_no_cookie tinyint 0：不启动，1：cookie不可忽略限制
 * @property integer $platform tinyint
 * 推广平台
 * 1：iPhone正版
 * 2：iPad
 * 4：iPhone越狱
 * 8：Android
 * 15：iOS+ Android
 * @property string $day_limit decimal 日预算
 * @property string $day_limit_program decimal 程序化日预算
 * @property string $total_limit decimal 总预算
 * @property string $approve_time timestamp 审核时间
 * @property string $approve_comment varchar 审核原因
 * @property double $mixing_rate double 掺量比例
 * @property integer $checkor_uid int 审核人用户ID
 * @property string $created date
 * @property integer $old_status int
 * @property integer $pause_status tinyint
 * 暂停状态
 * 0：平台暂停
 * 1：日限额暂停
 * 2：余额不足暂停
 * 3：广告主暂停
 * 5：达到总限额暂停
 * @property integer $old_pause_status int 上次暂停状态
 * @property string $rate decimal
 * @property integer $ad_type tinyint
 * 广告类型
 * 0（应用市场）
 * 1（Banner纯图片）
 * 2 (Feeds)
 * 3(插屏半屏)
 * 4(插屏全屏)
 * 5(banner文字链)
 * 71(appstore)
 * 81(其他)
 * @property integer $product_id int 产品ID
 * @property string $updated_time timestamp
 * @property integer $updated_uid int
 * @property integer $is_target tinyint
 * @property string $operation_time datetime 操作时间
 * @property string $equivalence char 等价广告唯一码
 * @property string $condition text 定向投放条件
 * @property integer $business_type tinyint 业务类型
 */
class Campaign extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    /**
     * 增加推广计划动作
     */
    const ACTION_APPROVAL = 1;
    const ACTION_DRAFT = 2;
    const ACTION_EDIT = 3;

    const ACTION_EQUIVALENCE_RELATION = 1;//建立等价关系
    const ACTION_EQUIVALENCE_DELETE = 2;//删除等价关系

    /**
     * 媒体广告动作
     */
    const ACTION_ACCEPT = 2;//接受投放
    const ACTION_NOT_ACCEPT = 3;//暂不接受

    /**
     * 状态常量
     */
    const STATUS_DELIVERING = 0;
    const STATUS_SUSPENDED = 1;
    const STATUS_DRAFT = 4;
    const STATUS_PENDING_APPROVAL = 10;
    const STATUS_REJECTED = 11;
    const STATUS_STOP_DELIVERING = 15;

    /**
     *
     * 计费类型
     */
    const REVENUE_TYPE_CPD = 1;
    const REVENUE_TYPE_CPC = 2;
    const REVENUE_TYPE_CPA = 4;
    const REVENUE_TYPE_CPT = 8;
    const REVENUE_TYPE_CPM = 16;
    const REVENUE_TYPE_CPS = 32;

    //推广类型
    const DELIVERY_TYPE_ADN = 1;
    const DELIVERY_TYPE_GAME = 2;

    /**
     * 目标平台常量
     */
    const PLATFORM_IPHONE_COPYRIGHT = 1;
    const PLATFORM_IPAD = 2;
    const PLATFORM_IOS_COPYRIGHT = 3; // iPhone正版+iPad
    const PLATFORM_IPHONE_JAILBREAK = 4;
    const PLATFORM_IOS = 7; // IOS
    const PLATFORM_ANDROID = 8;
    const PLATFORM_IOS_ANDROID = 15;

    /**
     * 素材审核
     */
    const ACTION_MATERIAL_APPROVAL = 1;
    const ACTION_MATERIAL_REJECTED = 2;
    /**
     * 广告类型
     */
    const AD_TYPE_APP_MARKET = 0;
    const AD_TYPE_BANNER_IMG = 1;
    const AD_TYPE_FEEDS = 2;
    const AD_TYPE_HALF_SCREEN = 3;
    const AD_TYPE_FULL_SCREEN = 4;
    const AD_TYPE_BANNER_TEXT_LINK = 5;
    const AD_TYPE_APP_STORE = 71; // app store
    const AD_TYPE_OTHER = 81;//其他
    const AD_TYPE_VIDEO = 91;//视频

    const PAUSE_STATUS_PLATFORM = 0;//平台暂停
    const PAUSE_STATUS_EXCEED_DAY_LIMIT = 1;//达到日限额暂停
    const PAUSE_STATUS_BALANCE_NOT_ENOUGH = 2;//余额不足暂停
    const PAUSE_STATUS_ADVERTISER_PAUSE = 3;//广告主暂停
    const PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM = 4;//程序化暂停
    const PAUSE_STATUS_EXCEED_TOTAL_LIMIT = 5;//达到总预算暂停

    const FILTER_PAUSE_STATUS_PLATFORM = 100;//平台暂停
    const FILTER_PAUSE_STATUS_DAY_LIMIT = 101;//日预算暂停
    const FILTER_PAUSE_STATUS_NOT_ENOUGH = 102;//余额不足暂停
    const FILTER_PAUSE_STATUS_DAY_LIMIT_PROGRAM = 104;//程序化日预算暂停
    const FILTER_PAUSE_STATUS_TOTAL_LIMIT = 105;//总预算暂停

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'campaigns';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'campaignid';

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
     * 可搜索的字段名
     *
     * @var array
     */
    public $searchableFields = ['appinfos_app_name'];

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaignname',
        'clientid',
        'views',
        'clicks',
        'conversions',
        'priority',
        'weight',
        'target_impression',
        'target_click',
        'target_conversion',
        'anonymous',
        'companion',
        'comments',
        'revenue',
        'revenue_type',
        'block',
        'capping',
        'session_capping',
        'an_campaign_id',
        'as_campaign_id',
        'status',
        'an_status',
        'as_reject_reason',
        'hosted_views',
        'hosted_clicks',
        'viewwindow',
        'clickwindow',
        'ecpm',
        'min_impressions',
        'ecpm_enabled',
        'activate_time',
        'expire_time',
        'type',
        'show_capped_no_cookie',
        'platform',
        'day_limit',
        'day_limit_program',
        'total_limit',
        'approve_time',
        'approve_comment',
        'mixing_rate',
        'checkor_uid',
        'old_status',
        'pause_status',
        'old_pause_status',
        'rate',
        'ad_type',
        'product_id',
        'is_target',
        'operation_time',
        'equivalence',
        'condition',
        'business_type',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'campaignid' => trans('Model.campaignid'),
            'campaignname' => trans('Model.campaignname'),
            'clientid' => trans('Model.clientid'),
            'views' => trans('Model.views'),
            'clicks' => trans('Model.clicks'),
            'conversions' => trans('Model.conversions'),
            'priority' => trans('Model.priority'),
            'weight' => trans('Model.weight'),
            'target_impression' =>trans('Model.target_impression'),
            'target_click' => trans('Model.target_click'),
            'target_conversion' => trans('Model.target_conversion'),
            'anonymous' =>trans('Model.anonymous'),
            'companion' => trans('Model.companion'),
            'comments' =>trans('Model.comments'),
            'revenue' => trans('Model.revenue'),
            'revenue_type' => trans('Model.revenue_type'),
            'updated' =>trans('Model.updated'),
            'block' => trans('Model.block'),
            'capping' => trans('Model.capping'),
            'session_capping' => trans('Model.session_capping'),
            'an_campaign_id' => trans('Model.an_campaign_id'),
            'as_campaign_id' =>trans('Model.as_campaign_id'),
            'status' => trans('Model.status'),
            'an_status' => trans('Model.an_status'),
            'as_reject_reason' => trans('Model.as_reject_reason'),
            'hosted_views' => trans('Model.hosted_views'),
            'hosted_clicks' => trans('Model.hosted_clicks'),
            'viewwindow' => trans('Model.viewwindow'),
            'clickwindow' => trans('Model.clickwindow'),
            'ecpm' => trans('Model.ecpm'),
            'min_impressions' => trans('Model.min_impressions'),
            'ecpm_enabled' => trans('Model.ecpm_enabled'),
            'activate_time' => trans('Model.activate_time'),
            'expire_time' => trans('Model.expire_time'),
            'type' => trans('Model.type'),
            'show_capped_no_cookie' => trans('Model.show_capped_no_cookie'),
            'platform' => trans('Model.platform'),
            'day_limit' => trans('Model.day_limit'),
            'day_limit_program' => trans('Model.day_limit_program'),
            'total_limit' => trans('Model.total_limit'),
            'approve_time' => trans('Model.approve_time'),
            'approve_comment' =>trans('Model.approve_comment'),
            'mixing_rate' => trans('Model.mixing_rate'),
            'checkor_uid' => trans('Model.checkor_uid'),
            'created' => trans('Model.created'),
            'old_status' => trans('Model.old_status'),
            'pause_status' => trans('Model.pause_status'),
            'old_pause_status' => trans('Model.old_pause_status'),
            'rate' => trans('Model.rate'),
            'ad_type' => trans('Model.ad_type'),
            'product_id' => trans('Model.product_id'),
            'updated_time' => trans('Model.updated_time'),
            'updated_uid' => trans('Model.updated_uid'),
            'is_target' => trans('Model.is_target'),
            'operation_time' => trans('Model.operation_time'),
            'equivalence' =>trans('Model.equivalence'),
            'condition' => trans('Model.condition'),
            'products_name' => trans('Model.products_name'),
            'products_type' => trans('Model.products_type'),
            'appinfos_app_name' => trans('Model.appinfos_app_name'),
            'keyword_price_up_count' => trans('Model.keyword_price_up_count'),
            'sort' => trans('Model.sort'),
            'parent' => trans('Model.parent'),
            'appinfos_app_rank' => trans('Model.appinfos_app_rank'),
            'field' => trans('Model.field'),
            'value' => trans('Model.value'),
            'action' => trans('Model.action'),
            'appinfos_images' => trans('Model.appinfos_images'),
            'keywords' => trans('Model.keywords'),
            'products_icon' => trans('Model.products_icon'),
            'appinfos_description' => trans('Model.appinfos_description'),
            'appinfos_profile' => trans('Model.appinfos_profile'),
            'star' => trans('Model.star'),
            'link_name' => trans('Model.link_name'),
            'link_url' => trans('Model.link_url'),
            'link_title' => trans('Model.link_title'),
            'clientname' => trans('Model.clientname'),
            'mode' => trans('Model.mode'),
            'business_type' => trans('Model.business_type'),
            'vedio' => trans('Model.vedio'),
            'date' => '日期',
            'affiliateid' => '渠道',
            'game_client_revenue_type' => '广告主计费类型',
            'game_af_revenue_type' => '渠道计费类型',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * 返回该广告计划对应的广告主
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'clientid', 'clientid');
    }
    /**
     * 返回默认帐户角色
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne('App\Models\Account', 'account_id', 'default_account_id');
    }

    /**
     * 返回推广包信息
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function appInfo()
    {
        return $this->hasOne('App\Models\AppInfo', 'app_id', 'campaignname');
    }

    /**
     * 返回关联改广告计划的广告位列表
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function zones()
    {
        return $this->belongsToMany('App\Models\Zone', 'placement_zone_assoc', 'placement_id', 'zone_id');
    }
    /**
     * 返回该广告计划对应的广告列表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function banners()
    {
        return $this->hasMany('App\Models\Banner', "campaignid", "campaignid");
    }

    /**
     * 返回该广告计划关联的所有跟踪器
     * @return type
     */
    public function trackers()
    {
        return $this->belongsToMany('App\Models\Tracker', 'campaigns_trackers', 'campaignid', 'trackerid');
    }

    /**
     * 返回改广告管理的产品
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'product_id', 'id');
    }

    // Add constant labels here
    /**
     * 获取状态标签数组或单个标签
     * @var $key
     * @var $role
     * @return array or string
     */
    public static function getStatusLabels($key = null)
    {
        $data = [
            self::STATUS_DELIVERING => '投放中',
            self::STATUS_SUSPENDED => '已暂停',
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PENDING_APPROVAL => '待审核',
            self::STATUS_REJECTED => '未通过审核',
            self::STATUS_STOP_DELIVERING => '停止投放',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取状态下次可转换值
     *
     * @var $key
     * @return array
     */
    public static function getStatusNext($key)
    {
        $data = [
            self::STATUS_DELIVERING => [self::STATUS_SUSPENDED,],
            self::STATUS_SUSPENDED => [self::STATUS_DELIVERING,
                self::STATUS_STOP_DELIVERING],
            self::STATUS_DRAFT => [self::STATUS_PENDING_APPROVAL,],
            self::STATUS_PENDING_APPROVAL =>
                [
                    self::STATUS_DELIVERING,
                    self::STATUS_REJECTED,
                ],
            self::STATUS_REJECTED => [self::STATUS_PENDING_APPROVAL,],
            self::STATUS_STOP_DELIVERING => [self::STATUS_SUSPENDED],
        ];
        return isset($data[$key]) ? $data[$key] : null;
    }

    /**
     * 获取上级状态
     * @param $key
     * @return null
     */
    public static function getStatusPrevious($key)
    {
        $data = [
            self::STATUS_DELIVERING => [
                self::STATUS_SUSPENDED,
                self::STATUS_PENDING_APPROVAL,
                self::STATUS_REJECTED,
                self::STATUS_STOP_DELIVERING,
            ],
            self::STATUS_SUSPENDED => [
                self::STATUS_SUSPENDED,
                self::STATUS_STOP_DELIVERING,
                self::STATUS_DELIVERING,
            ],
            self::STATUS_REJECTED => [
                self::STATUS_PENDING_APPROVAL,
                self::STATUS_REJECTED,
            ],
            self::STATUS_STOP_DELIVERING => [
                self::STATUS_SUSPENDED,
            ],
        ];
        return isset($data[$key]) ? $data[$key] : null;
    }

    /**
     * 获取计费类型标签数组或单个标签
     *
     * @var $key
     * @return array or string
     */
    public static function getRevenueTypeLabels($key = null)
    {
        $data = [
            self::REVENUE_TYPE_CPD => 'CPD',
            self::REVENUE_TYPE_CPC => 'CPC',
            self::REVENUE_TYPE_CPA => 'CPA',
            self::REVENUE_TYPE_CPT => 'CPT',
            self::REVENUE_TYPE_CPM => 'CPM',
            self::REVENUE_TYPE_CPS => 'CPS',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     *  获取推广计划暂停标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getPauseStatusLabels($key = null)
    {
        $data = [
            self::PAUSE_STATUS_PLATFORM => '平台暂停',
            self::PAUSE_STATUS_EXCEED_DAY_LIMIT => '达到日限额暂停',
            self::PAUSE_STATUS_BALANCE_NOT_ENOUGH => '余额不足暂停',
            self::PAUSE_STATUS_ADVERTISER_PAUSE => '广告主暂停',
            self::PAUSE_STATUS_EXCEED_TOTAL_LIMIT => '达到预算暂停',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取素材审核标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getActionMaterialLabels($key = null)
    {
        $data = [
            self::ACTION_MATERIAL_APPROVAL => '通过审核',
            self::ACTION_MATERIAL_REJECTED => '不通过审核',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取目标平台标签数组或单个标签
     *
     * @var $key
     * @var $type
     * @return array or string
     */
    public static function getPlatformLabels($key = null, $type = Product::TYPE_APP_DOWNLOAD)
    {
        $data = [
            self::PLATFORM_IPHONE_COPYRIGHT => 'iPhone正版',
            self::PLATFORM_IPAD => 'iPad',
            self::PLATFORM_IPHONE_JAILBREAK => 'iPhone越狱',
            self::PLATFORM_ANDROID => 'Android',
        ];
        $dataEx = [
            self::PLATFORM_ANDROID => 'Android',
            self::PLATFORM_IOS => 'iOS',
            self::PLATFORM_IOS_ANDROID => 'iOS + Android',
            self::PLATFORM_IOS_COPYRIGHT => 'iPhone+iPad',
        ];

        if ($key !== null) {
            if (isset($dataEx[$key])) {
                return $dataEx[$key];
            }

            $labels = array_values($data);
            $count = count($labels);
            $key = strrev(decbin($key));
            $key = strlen($key) < $count ? str_pad($key, $count, '0') : substr($key, 0, $count);

            for ($i = 0; $i < $count; $i++) {
                if ($key[$i] == '0') {
                    unset($labels[$i]);
                }
            }

            if (count($labels) == 0) {
                return null;
            }

            return implode('|', $labels);

        } else {
            //应用下载
            if ($type == Product::TYPE_APP_DOWNLOAD) {
                return $data;
            } elseif ($type == Product::TYPE_LINK) { //链接推广
                return $dataEx;
            } else {
                return $data + $dataEx;
            }
        }
    }

    /**
     * 获取广告类型
     *
     * @var $key
     * @var $type
     * @return array or string
     */
    public static function getAdTypeLabels($key = null, $type = 0)
    {
        $data = [
            self::AD_TYPE_APP_MARKET => '应用市场',
            self::AD_TYPE_BANNER_IMG => 'Banner',
            self::AD_TYPE_FEEDS => 'Feeds',
            self::AD_TYPE_HALF_SCREEN => '插屏广告',
            self::AD_TYPE_FULL_SCREEN => '插屏广告',
            self::AD_TYPE_BANNER_TEXT_LINK => 'Banner',
            self::AD_TYPE_APP_STORE => 'App Store',
            self::AD_TYPE_OTHER => '其他',
            self::AD_TYPE_VIDEO => '视频广告',
        ];
        if ($key !== null) {
            if ($type == 0) {
                if ($key == self::AD_TYPE_HALF_SCREEN || $key == self::AD_TYPE_FULL_SCREEN) {
                    return '插屏广告';
                }
            }
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 可排序的字段名
     * @param string $module
     * @return array
     */
    public static function getSortableFields($module = Account::TYPE_ADVERTISER)
    {
        $data = [
            'ad_type',
            'platform',
            'revenue',
            'day_limit',
            'status'
        ];
        if ($module == Account::TYPE_BROKER) {
            $data = array_merge($data, [
                'products_type',
                'appinfos_app_name',
                'revenue_type',
                'approve_time',
            ]);
        }

        return $data;
    }

    /**
     * 获取默认可以排序的字段
     *
     * @return array
     */
    public static function getDefaultSortField()
    {
        return [
            'status' => 'desc'
        ];
    }

    /**
     * 获取默认可以排序的字段
     * @param string $module
     * @return array
     */
    public static function getColumnListFields($module = Account::TYPE_ADVERTISER)
    {
        $data = [
            'products_name' => '',
            'products_type' => 'products_type_label',
            'appinfos_app_name' => '',
            'ad_type' => 'ad_type_label',
            'platform' => 'platform_label',
            'revenue_type' => 'revenue_type_label',
            'revenue' => '',
            'keyword_price_up_count' => '',
            'day_limit' => '',
            'total_limit' => '',
            'status' => 'status_label',
            'approve_time' => '',
        ];

        if ($module == Account::TYPE_ADVERTISER) {
            $data['campaignid'] = '';
        } elseif ($module == Account::TYPE_BROKER) {
            $data = array_merge(['clientname' => ''], $data);
        }
        return $data;
    }

    /**
     * 可排序的字段名
     *
     * @return array
     */
    public static function getSearchableFields()
    {
        return [
            'appinfos_app_name'
        ];
    }

    /**
     * 获取状态排序
     *
     * @var $key
     * @return array or string
     */
    public static function getStatusSort($key = null)
    {
        $data = [
            self::STATUS_DELIVERING => 97,
            self::STATUS_SUSPENDED => 95,
            self::STATUS_DRAFT => 100,
            self::STATUS_PENDING_APPROVAL => 99,
            self::STATUS_STOP_DELIVERING => 94,
            self::STATUS_REJECTED => 93,

        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     *
     * 广告暂停状态排序
     * @param null $key
     * @return array|null
     */
    public static function getPauseStatusSort($key = null)
    {
        $data = [
            self::PAUSE_STATUS_BALANCE_NOT_ENOUGH => 1,
            self::PAUSE_STATUS_EXCEED_TOTAL_LIMIT => 2,
            self::PAUSE_STATUS_EXCEED_DAY_LIMIT => 3,
            self::PAUSE_STATUS_ADVERTISER_PAUSE => 4,
            self::PAUSE_STATUS_PLATFORM => 5,
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 按照计费类型排序
     * @param null $key
     * @return array|null
     */
    public static function getRevenueTypeSort($key = null)
    {
        $data = [
            self::REVENUE_TYPE_CPT => 1,
            self::REVENUE_TYPE_CPA => 2,
            self::REVENUE_TYPE_CPD => 3,
            self::REVENUE_TYPE_CPC => 4,
            self::REVENUE_TYPE_CPM => 5,
            self::REVENUE_TYPE_CPS => 6,
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取广告类型排序
     *
     * @var $key
     * @return array or string
     */
    public static function getAdTypeSort($key = null)
    {
        $data = [
            self::AD_TYPE_APP_MARKET => 100,
            self::AD_TYPE_BANNER_IMG => 90,
            self::AD_TYPE_FEEDS => 80,
            self::AD_TYPE_HALF_SCREEN => 70,
            self::AD_TYPE_FULL_SCREEN => 71,
            self::AD_TYPE_BANNER_TEXT_LINK => 91,
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取操作状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getActionLabels($key = null)
    {
        $data = [
            self::ACTION_DRAFT => '保存草稿',
            self::ACTION_APPROVAL => '提交审核',
            self::ACTION_EDIT => '保存修改',
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
    public static function getZoneTypeToAdType($adType)
    {
        $data = [
            self::AD_TYPE_APP_MARKET => [Zone::AD_TYPE_APP_MARKET],
            self::AD_TYPE_BANNER_IMG => [
                Zone::AD_TYPE_BANNER_IMG,
                Zone::AD_TYPE_BANNER_ALL,
            ],
            self::AD_TYPE_BANNER_TEXT_LINK => [
                Zone::AD_TYPE_BANNER_TEXT_LINK,
                Zone::AD_TYPE_BANNER_ALL,
            ],
            self::AD_TYPE_FEEDS => [Zone::AD_TYPE_FEEDS],
            self::AD_TYPE_HALF_SCREEN => [Zone::AD_TYPE_HALF_SCREEN],
            self::AD_TYPE_FULL_SCREEN => [Zone::AD_TYPE_FULL_SCREEN],
            self::AD_TYPE_APP_STORE => [Zone::AD_TYPE_APP_STORE],
            self::AD_TYPE_OTHER => [Zone::AD_TYPE_APP_MARKET],
            self::AD_TYPE_VIDEO => [Zone::AD_TYPE_VIDEO],
        ];
        return $data[$adType];
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
            ],
            self::AD_TYPE_FEEDS => [self::AD_TYPE_FEEDS],
            self::AD_TYPE_HALF_SCREEN => [
                self::AD_TYPE_HALF_SCREEN, self::AD_TYPE_FULL_SCREEN],
            self::AD_TYPE_APP_STORE => [self::AD_TYPE_APP_STORE],
            self::AD_TYPE_OTHER => [self::AD_TYPE_OTHER],
            self::AD_TYPE_VIDEO => [self::AD_TYPE_VIDEO],
        ];
        return $data[$adType];
    }


    /**
     * 获取媒体商操作状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getMediaActionLabels($key = null)
    {
        $data = [
            self::ACTION_ACCEPT => '接受投放',
            self::ACTION_NOT_ACCEPT => '暂不接受',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 添加广告计划信息
     * @param array $param
     * @return \App\Models\Campaign|NULL
     */
    public static function storeCampaign($params)
    {
        $campaign = new Campaign();
        $campaign->campaignname = $params['app_id'];
        $campaign->clientid = $params['clientid']; // 创建的广告主id
        $campaign->priority = 5;
        $campaign->weight = 0;
        $campaign->target_impression = 100000000; // 3个target_*中只能设置1个
        $campaign->revenue_type = $params['revenue_type']; // 计费类型 define('MAX_FINANCE_CPD',  10);
        $campaign->min_impressions = 100;
        $campaign->viewwindow = 999999999;
        $campaign->clickwindow = 999999999;
        $campaign->product_id = $params['products_id'];
        //CPA,CPT类型广告直接添加直接通过审核
        if ($params['ad_type'] == Campaign::AD_TYPE_OTHER &&
            in_array($params['action'], [self::ACTION_APPROVAL, self::ACTION_EDIT])
        ) {
            $campaign->status = Campaign::STATUS_DELIVERING;
            $campaign->approve_time = date('Y-m-d h:i:s');
            $campaign->updated_uid = Auth::user()->user_id;
            $campaign->operation_time = date('Y-m-d h:i:s');
        } else {
            $campaign->status = $params['action'] == self::ACTION_DRAFT
                ? self::STATUS_DRAFT : self::STATUS_PENDING_APPROVAL;
        }
        if (isset($params['delivery_type'])) {
            $campaign->delivery_type = $params['delivery_type'];
        }
        //新表的价格字段
        $campaign->revenue = isset($params['revenue']) ? $params['revenue'] : 0;
        $campaign->platform = $params['platform'];
        $campaign->day_limit = isset($params['day_limit']) ? $params['day_limit'] : 0;
        $campaign->day_limit_program = isset($params['day_limit']) ? $params['day_limit'] : 0;
        $campaign->total_limit = isset($params['total_limit']) ? $params['total_limit'] : 0;
        $campaign->ad_type = $params['ad_type'];
        if ($campaign->save()) {
            return $campaign;
        } else {
            unset($campaign);
            return null;
        }
    }

    public static function getReportAdType()
    {
        return
            [
                self::AD_TYPE_APP_MARKET,
                self::AD_TYPE_BANNER_IMG,
                self::AD_TYPE_HALF_SCREEN,
                self::AD_TYPE_FEEDS,
                self::AD_TYPE_APP_STORE,
            ];
    }

    /**
     * 广告计费类型对应媒体计费类型
     * @param $revenueType
     * @return mixed
     */
    public static function getCRevenueTypeToARevenueType($revenueType)
    {
        $data = [
            self::REVENUE_TYPE_CPD => [self::REVENUE_TYPE_CPD, self::REVENUE_TYPE_CPC, self::REVENUE_TYPE_CPM],
            self::REVENUE_TYPE_CPC => [self::REVENUE_TYPE_CPC, self::REVENUE_TYPE_CPM],
            self::REVENUE_TYPE_CPA => [self::REVENUE_TYPE_CPD, self::REVENUE_TYPE_CPA,
                self::REVENUE_TYPE_CPC, self::REVENUE_TYPE_CPM],
            self::REVENUE_TYPE_CPT => [self::REVENUE_TYPE_CPT,],
            self::REVENUE_TYPE_CPM => [self::REVENUE_TYPE_CPM,],
            self::REVENUE_TYPE_CPS => [self::REVENUE_TYPE_CPS,],
        ];
        return $data[$revenueType];
    }
    /**
     * 广告计费类型对应SourceLogType
     * @param $revenueType
     * @return mixed
     */
    public static function getRevenueTypeToLogType()
    {
        return [
            self::REVENUE_TYPE_CPD => 'down',
            self::REVENUE_TYPE_CPC => 'click',
            self::REVENUE_TYPE_CPA => 'action',
           // self::REVENUE_TYPE_CPT => 'time',
            self::REVENUE_TYPE_CPM => 'impression',
        ];
    }
    
    public static function getBusinessType($businessType = null)
    {
        $data = [
            '0' => trans('Model.unkown'),
            '1' => trans('Model.game'),
            '2' => trans('Model.appstore'),
            '3' => trans('Model.android'),
            '4' => trans('Model.androidA'),
            '5' => trans('Model.androidInstall'),
        ];
        if (null === $businessType) {
            return $data;
        }
        return $data[$businessType];
    }
}
