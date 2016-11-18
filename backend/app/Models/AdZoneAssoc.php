<?php

namespace App\Models;

/**
 * This is the model class for table "ad_zone_assoc".
 * @property integer $ad_zone_assoc_id mediumint 关联ID
 * @property integer $zone_id mediumint 广告位ID
 * @property integer $ad_id mediumint 媒体广告ID
 * @property double $priority double 广告位展示概率
 * @property integer $link_type smallint
 * @property double $priority_factor double
 * @property integer $to_be_delivered tinyint
 * @property string $revenue decimal 出价
 * @property integer $revenue_type smallint 计费类型
 * @property string $updated_time timestamp
 */
class AdZoneAssoc extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ad_zone_assoc';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ad_zone_assoc_id';

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
        'zone_id',
        'ad_id',
        'priority',
        'link_type',
        'priority_factor',
        'to_be_delivered',
        'revenue',
        'revenue_type',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'ad_zone_assoc_id' => trans('Model.ad_zone_assoc_id'),
            'zone_id' => trans('Model.zone_id'),
            'ad_id' => trans('Model.ad_id'),
            'priority' => trans('Model.priority'),
            'link_type' => trans('Model.link_type'),
            'priority_factor' => trans('Model.priority_factor'),
            'to_be_delivered' => trans('Model.to_be_delivered'),
            'revenue' => trans('Model.revenue'),
            'revenue_type' => trans('Model.revenue_type'),
            'updated_time' => trans('Model.updated_time'),
            'campaignid' => trans('Model.keyword_campaignid'),
            'keyword' => trans('Model.keyword'),
            'price_up' => trans('Model.price_up'),
            'id' =>trans('Model.keyword_id'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * 对应的广告位
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function zone()
    {
        return $this->belongsTo('App\Models\Zone', 'zoneid', 'zone_id');
    }

    /**
     * 对应的广告
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function banner()
    {
        return $this->belongsTo('App\Models\Banner', 'bannerid', 'ad_id');
    }

    // Add constant labels here
}
