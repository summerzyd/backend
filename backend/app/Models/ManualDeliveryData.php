<?php

namespace App\Models;

use App\Components\Config;

/**
 * This is the model class for table "manual_deliverydata".
 * 运营导入人工投放数据表
 * @property integer $id int 自增ID
 * @property string $data_type char
 * 录入数据类型：
 * D2D
 * A2A
 * T2T
 * C2C
 * A2D-AD
 * A2D-AF
 * @property string $date date 投放日期
 * @property integer $affiliate_id int 媒体商ID
 * @property integer $zone_id int 广告位ID
 * @property integer $banner_id int bannerid
 * @property integer $campaign_id int 广告计划ID
 * @property integer $views int 展示量
 * @property integer $conversions int 下载量
 * @property string $revenues decimal 收入
 * @property string $expense decimal 支出
 * @property integer $cpa int CPA量
 * @property integer $flag int
 * 处理标志
 * 0 未处理
 * 1 已分配
 * 2 已处理
 * @property string $update_time datetime 更新时间
 * @property integer $repair_type tinyint 0不执行，1多录，2少录
 * @property integer $repair_status tinyint 0未处理，1已处理
 * @property integer $clicks int 点击数
 * @property string $eqqflag varchar eqq状态
 * @property integer $action_flag int 0处理成功，1处理失败
 * @property string $updated_time timestamp 更新时间
 */
class ManualDeliveryData extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const FLAG_UNTREATED = 0;
    const FLAG_ASSIGNED = 1;
    const FLAG_PROCESSED = 2;

    const REPAIR_STATUS_UNTREATED = 0;
    const REPAIR_STATUS_PROCESSED = 1;

    const MANUAL_YES = 1;
    const MANUAL_NO = 0;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'manual_deliverydata';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'data_type',
        'date',
        'affiliate_id',
        'zone_id',
        'banner_id',
        'campaign_id',
        'views',
        'conversions',
        'revenues',
        'expense',
        'cpa',
        'flag',
        'repair_type',
        'repair_status',
        'clicks',
        'is_manual',
        'eqqflag',
        'action_flag',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('ManualDeliveryData.id'),
            'data_type' => trans('ManualDeliveryData.data_type'),
            'date' => trans('ManualDeliveryData.date'),
            'affiliate_id' => trans('ManualDeliveryData.affiliate_id'),
            'zone_id' => trans('ManualDeliveryData.zone_id'),
            'banner_id' => trans('ManualDeliveryData.banner_id'),
            'campaign_id' => trans('ManualDeliveryData.campaign_id'),
            'views' => trans('ManualDeliveryData.views'),
            'conversions' => trans('ManualDeliveryData.conversions'),
            'revenues' => trans('ManualDeliveryData.revenues'),
            'expense' => trans('ManualDeliveryData.expense'),
            'cpa' => trans('ManualDeliveryData.cpa'),
            'flag' => trans('ManualDeliveryData.flag'),
            'update_time' => trans('ManualDeliveryData.update_time'),
            'repair_type' => trans('ManualDeliveryData.repair_type'),
            'repair_status' => trans('ManualDeliveryData.repair_status'),
            'clicks' => trans('ManualDeliveryData.clicks'),
            'is_manual' => trans('ManualDeliveryData.is_manual'),
            'eqqflag' => trans('ManualDeliveryData.eqqflag'),
            'action_flag' => trans('ManualDeliveryData.action_flag'),
            'updated_time' => trans('ManualDeliveryData.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add constant labels here
    /**
     * 获取处理标志标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getFlagLabels($key = null)
    {
        $data = [
            self::FLAG_UNTREATED => '未处理',
            self::FLAG_ASSIGNED => '已分配',
            self::FLAG_PROCESSED => '已处理',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 保存人工数据
     * @param $params
     * @return bool
     */
    public static function store($params)
    {
        if (!in_array($params['data_type'], Config::get('biddingos.manualWithoutCheckAffiliate'))) {
            $count = ManualDeliveryData::whereMulti([
                'date' => $params['date'],
                'affiliate_id' => $params['affiliate_id'],
                'zone_id' => $params['zone_id'],
                'banner_id' => $params['banner_id'],
                'campaign_id' => $params['campaign_id'],
            ])->count();

            if ($count > 0) {
                $result = ManualDeliveryData::whereMulti([
                    'date' => $params['date'],
                    'affiliate_id' => $params['affiliate_id'],
                    'zone_id' => $params['zone_id'],
                    'banner_id' => $params['banner_id'],
                    'campaign_id' => $params['campaign_id'],
                ])->update([
                    'data_type' => $params['data_type'],
                    'views' => $params['views'],
                    'clicks' => $params['clicks'],
                    'is_manual' => $params['is_manual'],
                    'conversions' => $params['conversions'],
                    'revenues' => $params['revenues'],
                    'expense' => $params['expense'],
                    'cpa' => $params['cpa']
                ]);
            } else {
                $manualDeliveryData = new ManualDeliveryData();
                $manualDeliveryData->fill($params);
                $result = $manualDeliveryData->save();
            }
        } else {
            $count = ManualDeliveryData::whereMulti([
                'data_type' => $params['data_type'],
                'date' => $params['date'],
                'campaign_id' => $params['campaign_id']
            ])->count();

            if ($count > 0) {
                $result = ManualDeliveryData::whereMulti([
                    'data_type' => $params['data_type'],
                    'date' => $params['date'],
                    'campaign_id' => $params['campaign_id']
                ])->update([
                    'views' => $params['views'],
                    'clicks' => $params['clicks'],
                    'is_manual' => $params['is_manual'],
                    'conversions' => $params['conversions'],
                    'revenues' => $params['revenues'],
                    'expense' => $params['expense'],
                    'cpa' => $params['cpa']
                ]);
            } else {
                $manualDeliveryData = new ManualDeliveryData();
                $manualDeliveryData->fill($params);
                $result = $manualDeliveryData->save();
            }
        }

        return $result;
    }
    
    /**
     *
     * @param integer $campaignId
     * @param date $date
     */
    public static function checkManualCampanginStatus($campaignId, $date)
    {
        $count = ManualDeliveryData::where('campaign_id', $campaignId)
                ->where('date', $date)
                ->whereRaw(" (0 < expense OR 0 < revenues) ")
                ->where('flag', 0)
                ->count();
        return (0 < $count) ? false : true;
    }
}
