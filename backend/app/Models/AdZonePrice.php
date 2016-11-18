<?php

namespace App\Models;

use App\Components\Helper\LogHelper;

/**
 * This is the model class for table "ad_zone_price".
 * @property integer $zone_id mediumint 广告位id
 * @property integer $ad_id mediumint 媒体广告ID
 * @property string $price_up decimal 加价金额
 * @property string $created_time datetime
 * @property string $updated_time timestamp
 */
class AdZonePrice extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ad_zone_price';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_time';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_time';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'zone_id',
        'ad_id',
        'price_up',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'zone_id' => trans('Zone Id'),
            'ad_id' => trans('Ad Id'),
            'price_up' => trans('Price Up'),
            'created_time' => trans('Created Time'),
            'updated_time' => trans('Updated Time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * return user default role
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    /*public function role()
    {
        return $this->hasOne('App\Models\Role', 'id', 'role_id');
    }*/

    // Add constant labels here
    /**
     * Get status labels
     * @param null $key
     * @return array|string
     */
    /*public static function getStatusLabels($key = null)
    {
        $data = [
            self::STATUS_DISABLE => trans('Disable'),
            self::STATUS_ENABLE => trans('Enable'),
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }*/

    /**
     * 修改广告位加价信息
     * @param $bannerId
     * @param $params
     */
    public static function updateZoneAndPrice($bannerId, $params)
    {
        foreach ($params as $item) {
            //价格为0删除
            if ($item['price_up'] == 0) {
                AdZonePrice::where('ad_id', $bannerId)
                    ->where('zone_id', $item['zoneid'])->delete();
            } else {
                //新增或者修改
                if (!empty($item['id'])) {
                    AdZonePrice::updateAdZonePrice($item['id'], $item);
                } else {
                    AdZonePrice::storeAdZonePrice($bannerId, $item);
                }
            }
        }
    }

    /**
     * 新增广告位加价
     * @param $campaignId 推广计划ID
     * @param $params
     */
    public static function storeAdZonePrice($bannerId, $params)
    {
        $adZonePrice = new AdZonePrice();
        $adZonePrice->ad_id = $bannerId;
        $adZonePrice->zone_id = $params['zoneid'];
        $adZonePrice->price_up = $params['price_up'];
        $adZonePrice->save();
    }

    /**
     * 更新广告位加价
     * @param $id
     * @param $params
     */
    public static function updateAdZonePrice($id, $params)
    {
        AdZonePrice::where('id', $id)->update([
            'zone_id' => $params['zoneid'],
            'price_up' => $params['price_up'],
        ]);
    }
}
