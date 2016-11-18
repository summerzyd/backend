<?php

namespace App\Models;

use App\Components\Helper\UrlHelper;
use App\Services\CampaignService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

/**
 * This is the model class for table "banners".
 * @property integer $bannerid mediumint 媒体广告ID
 * @property integer $campaignid mediumint 推广计划ID
 * @property string $contenttype enum 广告内容类型
 * @property integer $pluginversion mediumint
 * @property string $storagetype enum 广告形式
 * @property string $filename varchar 文件路径
 * @property string $imageurl varchar 图片URL
 * @property string $htmltemplate text html广告代码
 * @property string $htmlcache text html代码缓存
 * @property integer $width smallint 广告图片宽度
 * @property integer $height smallint 广告图片高度
 * @property integer $weight tinyint 权重
 * @property integer $seq tinyint 排序
 * @property string $target varchar 目标链接显示框架
 * @property string $url text 目标URL
 * @property string $alt varchar 广告文字说明
 * @property string $statustext varchar 浏览器状态栏文字
 * @property string $bannertext text 广告下方显示文字
 * @property string $description varchar 广告描述
 * @property string $adserver varchar
 * @property integer $block int
 * @property integer $capping int 预订广告计划曝光量
 * @property integer $session_capping int 限定广告计划曝光量
 * @property string $compiledlimitation text
 * @property string $acl_plugins text
 * @property string $append text
 * @property integer $bannertype tinyint
 * @property string $alt_filename varchar 广告文字说明文件
 * @property string $alt_imageurl varchar 广告文字说明图片
 * @property string $alt_contenttype enum 广告文字说明图片格式
 * @property string $comments text 注释
 * @property string $created datetime
 * @property string $updated datetime
 * @property string $acls_updated datetime
 * @property string $keyword varchar 广告关键字
 * @property integer $transparent tinyint
 * @property string $parameters text
 * @property integer $an_banner_id int
 * @property integer $as_banner_id int
 * @property integer $status int 广告状态
 * 0 ：投放中
 * 1：暂停投放
 * 2：待选 appID
 * 3：不接受投放
 * 6：待媒体审核
 * 7：待投放
 * @property integer $ad_direct_status tinyint
 * @property integer $ad_direct_rejection_reason_id tinyint
 * @property string $ext_bannertype varchar 广告形式的key值
 * @property string $prepend text
 * @property integer $affiliateid int 媒体ID
 * @property string $category varchar 分类
 * @property integer $app_rank tinyint 等级
 * @property string $app_id varchar 安装包ID
 * @property string $download_url varchar 下载地址
 * @property integer $package_file_id int
 * @property integer $attach_file_id int 渠道包ID
 * @property integer $updated_uid int
 * @property integer $pause_status tinyint
 * 暂停状态
 * 0：媒体手动暂停
 * 2：平台暂停
 * 3：媒体超日限额暂停
 * @property integer $an_status tinyint 旧广告状态
 * @property integer $an_pause_status tinyint 旧广告暂停状态
 * @property string $app_id_icon varchar
 * @property string $app_id_word varchar
 * @property string $affiliate_checktime datetime 审核时间
 * @property string $af_manual_price decimal 媒体价
 * @property string $flow_ratio decimal 流量转换比例
 * @property integer $revenue_type smallint 计费类型
 * @property string $revenue_price decimal 计费价
 * @property string $af_day_limit decimal 媒体日限额
 * @property string $updated_time timestamp
 * @property string $condition text
 */
class Banner extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    /**
     * 广告状态
     */
    const STATUS_PUT_IN = 0;//投放中
    const STATUS_APP_ID = 2;//接受
    const STATUS_SUSPENDED = 1;//暂停投放
    const STATUS_NOT_ACCEPTED = 3;//未接受
    const STATUS_PENDING_MEDIA = 6;//待审核媒体
    const STATUS_PENDING_PUT = 7;//待投放
    const STATUS_PENDING_SUBMIT = 8;//待提交

    /**
     * 暂停状态
     */
    const PAUSE_STATUS_MEDIA_MANUAL = 0;//媒体手动暂停
    const PAUSE_STATUS_PLATFORM = 2;//平台暂停
    const PAUSE_STATUS_EXCEED_DAY_LIMIT = 3;//媒体超过日限额

    const ACTION_APPROVAL = 1;
    const ACTION_CANCEL = 0;

    const ACTION_PUT_IN = 1;
    const ACTION_PUT_IN_SUSPEND = 2;

    const ADX_NO_REACH_UPLOAD = 0;//未达到上传条件
    const ADX_UPLOAD_FAIL = 1;//上传失败
    const ADX_UPLOAD_SUCCESS =2;//上传成功

    const ADX_STATUS_NO_UPLOAD = 0;//未上传
    const ADX_STATUS_APPROVING = 1;//审核中
    const ADX_STATUS_APPROVED = 2;//审核通过
    const ADX_STATUS_REJECT = 3;//拒绝
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'banners';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'bannerid';

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
        'campaignid',
        'contenttype',
        'pluginversion',
        'storagetype',
        'filename',
        'imageurl',
        'htmltemplate',
        'htmlcache',
        'width',
        'height',
        'weight',
        'seq',
        'target',
        'url',
        'alt',
        'statustext',
        'bannertext',
        'description',
        'adserver',
        'block',
        'capping',
        'session_capping',
        'compiledlimitation',
        'acl_plugins',
        'append',
        'bannertype',
        'alt_filename',
        'alt_imageurl',
        'alt_contenttype',
        'comments',
        'keyword',
        'transparent',
        'parameters',
        'an_banner_id',
        'as_banner_id',
        'status',
        'ad_direct_status',
        'ad_direct_rejection_reason_id',
        'ext_bannertype',
        'prepend',
        'affiliateid',
        'category',
        'app_rank',
        'app_id',
        'download_url',
        'package_file_id',
        'attach_file_id',
        'pause_status',
        'an_status',
        'an_pause_status',
        'app_id_icon',
        'app_id_word',
        'affiliate_checktime',
        'af_manual_price',
        'flow_ratio',
        'revenue_type',
        'revenue_price',
        'af_day_limit',
        'condition',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'bannerid' => trans('Model.bannerid'),
            'campaignid' => trans('Model.keyword_campaignid'),
            'contenttype' => trans('Contenttype'),
            'pluginversion' => trans('Pluginversion'),
            'storagetype' => trans('Storagetype'),
            'filename' => trans('Filename'),
            'imageurl' => trans('Imageurl'),
            'htmltemplate' => trans('Htmltemplate'),
            'htmlcache' => trans('Htmlcache'),
            'width' => trans('Width'),
            'height' => trans('Height'),
            'weight' => trans('Weight'),
            'seq' => trans('Seq'),
            'target' => trans('Target'),
            'url' => trans('Url'),
            'alt' => trans('Alt'),
            'statustext' => trans('Statustext'),
            'bannertext' => trans('Bannertext'),
            'description' => trans('Description'),
            'adserver' => trans('Adserver'),
            'block' => trans('Block'),
            'capping' => trans('Capping'),
            'session_capping' => trans('Session Capping'),
            'compiledlimitation' => trans('Compiledlimitation'),
            'acl_plugins' => trans('Acl Plugins'),
            'append' => trans('Append'),
            'bannertype' => trans('Bannertype'),
            'alt_filename' => trans('Alt Filename'),
            'alt_imageurl' => trans('Alt Imageurl'),
            'alt_contenttype' => trans('Alt Contenttype'),
            'comments' => trans('Comments'),
            'created' => trans('Created'),
            'updated' => trans('Updated'),
            'acls_updated' => trans('Acls Updated'),
            'keyword' => trans('Keyword'),
            'transparent' => trans('Transparent'),
            'parameters' => trans('Parameters'),
            'an_banner_id' => trans('An Banner Id'),
            'as_banner_id' => trans('As Banner Id'),
            'status' => trans('Status'),
            'ad_direct_status' => trans('Ad Direct Status'),
            'ad_direct_rejection_reason_id' => trans('Ad Direct Rejection Reason Id'),
            'ext_bannertype' => trans('Ext Bannertype'),
            'prepend' => trans('Prepend'),
            'affiliateid' => trans('Model.affiliateid'),
            'category' => trans('Model.category'),
            'app_rank' => trans('App Rank'),
            'app_id' => trans('App Id'),
            'download_url' => trans('Download Url'),
            'package_file_id' => trans('Package File Id'),
            'attach_file_id' => trans('Attach File Id'),
            'updated_uid' => trans('Updated Uid'),
            'pause_status' => trans('Pause Status'),
            'an_status' => trans('An Status'),
            'an_pause_status' => trans('An Pause Status'),
            'app_id_icon' => trans('App Id Icon'),
            'app_id_word' => trans('App Id Word'),
            'affiliate_checktime' => trans('Affiliate Checktime'),
            'af_manual_price' => trans('Af Manual Price'),
            'flow_ratio' => trans('Flow Ratio'),
            'revenue_type' => trans('Revenue Type'),
            'revenue_price' => trans('Revenue Price'),
            'af_day_limit' => trans('Af Day Limit'),
            'updated_time' => trans('Updated Time'),
            'condition' => trans('Condition'),
            'field' => trans('Model.field'),
            'value ' =>trans('Model.value'),
            'action' => trans('Model.action'),
            'appinfos_app_rank' => trans('Model.appinfos_app_rank'),
            'appid' =>trans('Model.appid'),
            'app_icon' => trans('Model.app_icon'),
            'app_name' => trans('Model.app_name'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * 返回关联改广告的广告位列表
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function zones()
    {
        return $this->belongsToMany('App\Models\Zone', 'ad_zone_assoc', 'ad_id', 'zone_id');
    }

    /**
     * 返回该广告对应的广告计划
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo('App\Models\Campaign', 'campaignid', 'campaignid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function attachFile()
    {
        return $this->hasOne('App\Models\AttachFile', 'id', 'attach_file_id');
    }

    /**
     * 所属媒体商
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function affiliate()
    {
        return $this->belongsTo('App\Models\Affiliate', 'affiliateid', 'affiliateid');
    }
    /**
     * 广告追踪
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tracker()
    {
        return $this->belongsTo('App\Models\Tracker', 'bannerid', 'bannerid');
    }
    // Add constant labels here
    /**
     * 获取广告状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getStatusLabels($key = null)
    {
        $data = [
            self::STATUS_PUT_IN => '投放中',
            self::STATUS_APP_ID => '待选AppID',
            self::STATUS_SUSPENDED => '已暂停',
            self::STATUS_NOT_ACCEPTED => '未接受',
            self::STATUS_PENDING_MEDIA => '待媒体审核',
            self::STATUS_PENDING_PUT => '待投放',
            self::STATUS_PENDING_SUBMIT => '待提交',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取暂停状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getPauseLabels($key = null)
    {
        $data = [
            self::PAUSE_STATUS_MEDIA_MANUAL => '媒体手动暂停',
            self::PAUSE_STATUS_PLATFORM => '平台暂停',
            self::PAUSE_STATUS_EXCEED_DAY_LIMIT => '媒体超过日限额',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 解决依赖关系
     * @param array $options
     * @return boolean
     */
    public function save(array $options = [])
    {
        $this->download_url = CampaignService::attachFileLink($this->attach_file_id);
        $this->buildBannerText();
        return parent::save();
    }

    /**
     * 更新Banner广告
     * @param $params
     * @param $affiliateId
     * @param $bannerId
     */
    public static function updateBanner($params, $affiliateId, $bannerId)
    {
        $data = [
            'status' => $params['status'],
            'bannerid' => $params['bannerid'],
            'campaignid' => $params['campaignid'],
            'updated' => $params['updated'],
            'updated_uid' => $params['updated_uid'],
            'affiliate_checktime' => $params['affiliate_checktime']
        ];
        if (isset($params['category'])) {
            $data['category'] = $params['category'];
        }
        if (isset($params['appinfos_app_rank'])) {
            $data['app_rank'] = $params['appinfos_app_rank'];
        }
        Banner::whereMulti([
            'campaignid' => $params['campaignid'],
            'affiliateid' => $affiliateId,
            'bannerid' => $bannerId,
        ])->update($data);
    }
    /**
     * 检测banner所属的媒体商的类型
     * @param int $mode 0=媒体下载；1=联盟下载入库；2=人工投放；3=联盟下载不入库
     */
    public function checkMode($mode = [Affiliate::MODE_PROGRAM_DELIVERY_STORAGE])
    {
        if (isset(self::affiliate()->first()->mode)) {
            if (in_array(self::affiliate()->first()->mode, $mode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 广告下方显示文字
     * @param $campaignId
     * @param $affiliateId
     */
    public function buildBannerText()
    {
        $attach = $this->attachFile;
        $reserve = $attach ? ($attach->reserve ? json_decode($attach->reserve, true) : false) : false;
        if ($this->tracker()->first()) {
            $tracker =  $this->tracker()->first()->trackerid;
        } else {
            $tracker = '';
        }
        $bannerText = [
            'app_id' => $this->app_id,
            'app_level' => $this->app_rank,
        ];
        //如果有地址，写入up地址
        if (isset($_SERVER['HTTP_HOST'])) {
            $bannerText['app_tracker'] = 'http://' . $_SERVER['HTTP_HOST'] . '/up/www/delivery/ti.php?trackerid=' .
                $tracker . '&amp;cb=%%RANDOM_NUMBER%%';
        }

        //联盟下载才需要的数据
        if ($this->checkMode([Affiliate::MODE_PROGRAM_DELIVERY_STORAGE,
            Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE])
        ) {
            $bannerText['app_downloadurl'] = $this->download_url;
            $bannerText['app_md5'] = array_get($reserve, 'md5');
            $bannerText['app_size'] = array_get($reserve, 'filesize');
            $ext = $attach ? strtolower(File::extension($attach->file)) : '';
            if ($ext === 'ipa') {
                $bannerText['app_bundle_id'] = array_get($reserve, 'packageName');
                $bannerText['app_pkg_name'] = array_get($reserve, 'packageName');
                $bannerText['app_pkg_versionName'] = array_get($reserve, 'versionName');
                $bannerText['app_pkg_versionCode'] = array_get($reserve, 'versionCode');
                $bannerText['app_support_os'] = array_get($reserve, 'app_support_os');  //无
            } elseif ($ext === 'apk') {
                $bannerText['app_pkg_name'] = array_get($reserve, 'packageName');
                $bannerText['app_pkg_versionName'] = array_get($reserve, 'versionName');
                $bannerText['app_pkg_versionCode'] = array_get($reserve, 'versionCode');
                $bannerText['app_support_os'] = array_get($reserve, 'app_support_os');  //无
                $bannerText['app_crc32'] = array_get($reserve, 'app_crc32');  //无
                $bannerText['app_sign'] = array_get($reserve, 'app_sign');  //无
                $bannerText['h8192_md5'] = array_get($reserve, 'h8192_md5');  //无
            }

            $app = AppInfo::find($this->campaign()->first()->campaignname);
            if ($app) {
                //获取图标
                $icon = $this->campaign()->first()->product->icon;
                $bannerText['app_logo'] = UrlHelper::imageFullUrl($icon);
                $bannerText['app_name'] = $app['app_show_name'];
                $bannerText['app_desc'] = $app['description'];
                $bannerText['app_download_count'] = "1600000";
                $bannerText['app_update_date'] = $app['updated_at']->toDateTimeString();
                $bannerText['language'] = "zh-cn";
                $bannerText['author'] = "品效通";
                $bannerText['app_rank'] = $this->app_rank;
                $bannerText['app_release_note'] = $app['update_des'];
                //一句话点评 - 无
                $bannerText['app_comment'] = $app['profile'];
                //语言是数组，数据上需注意，暂时包一层
                $bannerText['app_support_lang'] = array('zh');
                //支持的国家
                $bannerText['app_support_country'] = array('cn');

                $category = Category::find($this->category);
                if (0 == $category['parent']) {
                    $bannerText['maincategory'] = "不限";
                } elseif (Category::PARENT_APP == $category['parent']) {
                    $bannerText['maincategory'] = "应用";
                } else {
                    $bannerText['maincategory'] = "游戏";
                }
                $bannerText['secondcategory'] = $category['name'];
                $imageUrl = [];
                $campaignObj = $this->campaign()->first();
                if (Campaign::AD_TYPE_APP_MARKET == $campaignObj->ad_type) {
                    $images = unserialize($app->images);
                } else {
                    $images = CampaignImage::where(
                        'campaignid',
                        $campaignObj->campaignid
                    )->select('url')->get();
                    $images = json_decode(json_encode($images), true);
                }
                $firstArr = reset($images);
                if (is_array($firstArr)) {
                    $images = $firstArr;
                }
                if (!empty($images)) {
                    foreach ($images as $image) {
                        $imageUrl[] = UrlHelper::imageFullUrl($image);
                    }
                    $bannerText['app_screenshots'] = $imageUrl;
                }
            }
        }
        $this->bannertext = json_encode($bannerText);
    }

    /**
     * 创建banner
     * @param $campaignId
     * @param $affiliateId
     * @param array $params
     * @return bool
     */
    public static function getBannerOrCreate($campaignId, $affiliateId, $params = [])
    {
        $campaign = Campaign::find($campaignId);
        $banner = Banner::where('campaignid', $campaignId)->where('affiliateid', $affiliateId)->first();
        if (!$banner) {
            if ($campaign->product->type) {
                $revenueType = Campaign::REVENUE_TYPE_CPC;
            } else {
                $revenueType = Campaign::REVENUE_TYPE_CPD;
            }

            $param = array(
                'campaignid' => $campaignId,
                'contenttype' => 'app',
                'storagetype' => 'app',
                'htmltemplate' => '',
                'htmlcache' => '',
                'description' => $campaign->campaignname,
                'width' => 0,
                'height' => 0,
                'url' => '',
                'target' => '',
                'parameters' => 'N;',
                'compiledlimitation' => '',
                'append' => '',
                'prepend' => '',
                'affiliateid' => $affiliateId,
                'ext_bannertype' => 'bannerTypeApp:oxApp:genericApp',
                'revenue_type' => $revenueType
            );
            if (!empty($params)) {
                foreach ($params as $k => $v) {
                    $param[$k] = $v;
                }
            }

            $bid = DB::table('banners')->insertGetId($param);
            if (!$bid) {
                return false;
            }
            $banner = Banner::find($bid);
        }
        return $banner;
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
            self::STATUS_PENDING_SUBMIT => 1,
            self::STATUS_PENDING_MEDIA => 2,
            self::STATUS_PENDING_PUT => 3,
            self::STATUS_APP_ID => 4,
            self::STATUS_SUSPENDED => 5,
            self::STATUS_PUT_IN => 6,
            self::STATUS_NOT_ACCEPTED => 7,
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
