<?php

namespace App\Models;

use App\Services\CampaignService;

/**
 * This is the model class for table "appinfos".
 * @property integer $media_id mediumint 对应媒体ID
 * @property string $app_id varchar 对应媒体内的应用ID
 * @property string $app_name varchar 应用名称
 * @property string $vender varchar 厂商名称
 * @property integer $app_rank tinyint 应用级别 S A B C D
 * @property string $created_at timestamp
 * @property string $updated_at timestamp
 * @property integer $category int 分类
 * @property integer $platform int 目标平台
 * @property string $app_show_name varchar 软件显示名称
 * @property string $description text 软件介绍
 * @property string $update_des text 更新说明
 * @property string $app_show_icon varchar 图标
 * @property string $download_url varchar 下载地址
 * @property string $images text 软件截图
 * @property integer $materials_status tinyint
 * 物料状态
 * 0：无物料
 * 1：待审核
 * 2：驳回
 * 3：已生效
 * @property integer $check_user mediumint 审核人
 * @property string $check_date timestamp 审核时间
 * @property string $check_msg varchar 审核信息
 * @property string $package varchar 包名
 * @property string $materials_data text 物料数据
 * @property string $old_materials_data text 旧物料数据
 * @property integer $ad_spec tinyint 图片规格类型
 * @property string $ad_img varchar 图片
 * @property string $profile varchar 一句话简介
 * @property integer $star tinyint 星级
 * @property string $title varchar 标题
 * @property string $updated_time timestamp
 * @property string $application_id varchar 应用ID
 * @property string $appstore_info varchar 应用数据
 */
class AppInfo extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * 物料状态
     */
    const MATERIAL_STATUS_NONE = 0;
    const MATERIAL_STATUS_PENDING_APPROVAL = 1;
    const MATERIAL_STATUS_REJECT = 2;
    const MATERIAL_STATUS_APPROVAL = 3;

    /**
     * 应用级别
     */
    const RANK_S = 1;
    const RANK_A = 2;
    const RANK_B = 3;
    const RANK_C = 4;
    const RANK_D = 5;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appinfos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'app_id';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'app_name',
        'vender',
        'app_rank',
        'category',
        'app_show_name',
        'description',
        'app_show_icon',
        'download_url',
        'images',
        'materials_status',
        'check_user',
        'check_date',
        'check_msg',
        'package',
        'materials_data',
        'old_materials_data',
        'ad_spec',
        'ad_img',
        'profile',
        'star',
        'title',
        'application_id',
        'type',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'media_id' => trans('Media Id'),
            'app_id' => trans('App Id'),
            'app_name' => trans('App Name'),
            'vender' => trans('Vender'),
            'app_rank' => trans('App Rank'),
            'created_at' => trans('Created At'),
            'updated_at' => trans('Updated At'),
            'category' => trans('Category'),
            'platform' => trans('Platform'),
            'app_show_name' => trans('App Show Name'),
            'description' => trans('Description'),
            'update_des' => trans('Update Des'),
            'app_show_icon' => trans('App Show Icon'),
            'download_url' => trans('Download Url'),
            'images' => trans('Images'),
            'materials_status' => trans('Materials Status'),
            'check_user' => trans('Check User'),
            'check_date' => trans('Check Date'),
            'check_msg' => trans('Check Msg'),
            'package' => trans('Package'),
            'materials_data' => trans('Materials Data'),
            'old_materials_data' => trans('Old Materials Data'),
            'ad_spec' => trans('Ad Spec'),
            'ad_img' => trans('Ad Img'),
            'profile' => trans('Profile'),
            'star' => trans('Star'),
            'title' => trans('Title'),
            'updated_time' => trans('Updated Time'),
            'application_id' => trans('Application Id'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here

    // Add constant labels here
    /**
     * 获取物料状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getMaterialStatusLabels($key = null)
    {
        $data = [
            self::MATERIAL_STATUS_NONE => '无物料',
            self::MATERIAL_STATUS_PENDING_APPROVAL => '待审核',
            self::MATERIAL_STATUS_REJECT => '驳回',
            self::MATERIAL_STATUS_APPROVAL => '已生效'
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取应用级别标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getRankStatusLabels($key = null)
    {
        $data = [
            self::RANK_S => 'S',
            self::RANK_A => 'A',
            self::RANK_B => 'B',
            self::RANK_C => 'C',
            self::RANK_D => 'D',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }


    /**
     * 新建appInfo
     * @param string $app_id
     * @param array $params
     */
    public static function storeAppInfo($params, $imagesStr = null)
    {
        $advertiser = Client::find($params['clientid']);
        $appInfo = new AppInfo();
        $appInfo->media_id = $advertiser->agencyid;
        if ($params['products_type'] == Product::TYPE_LINK) {
            $appInfo->app_show_icon = $params['products_icon'];
        }
        $appInfo->app_id = $params['app_id'];
        $appInfo->app_name = $params['appinfos_app_name'];
        $appInfo->platform = $params['platform'];
        $appInfo->app_show_name = $params['products_show_name'];
        $appInfo->update_des = $params['appinfos_update_des'];
        $appInfo->description = $params['appinfos_description'];
        $appInfo->profile = $params['appinfos_profile'];
        $appInfo->star = $params ['star'];
        $appInfo->title = $params['link_title'];
        $appInfo->application_id = isset($params['application_id']) ? $params['application_id'] : 0;
        if ($params['ad_type'] == Campaign::AD_TYPE_APP_STORE && !empty($params['application_id'])) {
            $appInfo->appstore_info = CampaignService::getAppIdInfo($params['application_id']);
        }
        if (isset($imagesStr)) {
            $appInfo->images = $imagesStr;
        }
        $appInfo->save();
    }

    /**
     * 按照计费类型排序
     * @param null $key
     * @return array|null
     */
    public static function getStatusSort($key = null)
    {
        $data = [
            self::MATERIAL_STATUS_NONE => 1,
            self::MATERIAL_STATUS_PENDING_APPROVAL => 2,
            self::MATERIAL_STATUS_APPROVAL => 3,
            self::MATERIAL_STATUS_REJECT => 4,
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
