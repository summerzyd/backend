<?php

namespace App\Models;

/**
 * This is the model class for table "delivery_log".
 * @property integer $deliveryid int
 * @property integer $campaignid mediumint
 * @property integer $zoneid mediumint
 * @property string $cb text
 * @property double $price float
 * @property string $price_gift decimal
 * @property string $actiontime datetime
 * @property string $target_type varchar
 * @property string $target_cat varchar
 * @property string $target_id varchar
 * @property integer $source tinyint
 * @property string $channel varchar
 * @property string $af_income decimal
 * @property integer $status tinyint
 * @property integer $ad_id int
 * @property integer $origin_zone_id int
 * @property string $refer varchar
 * @property integer $target_deliveryid int
 * @property string $source_log_type char
 * @property integer $source_log_id int
 * @property string $updated_time timestamp
 */
class DeliveryLog extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'delivery_log';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'deliveryid';

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
        'campaignid',
        'zoneid',
        'cb',
        'price',
        'price_gift',
        'actiontime',
        'target_type',
        'target_cat',
        'target_id',
        'source',
        'channel',
        'af_income',
        'status',
        'ad_id',
        'origin_zone_id',
        'refer',
        'target_deliveryid',
        'source_log_type',
        'source_log_id',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'deliveryid' => trans('ExpenseLog.deliveryid'),
        ];

        $data = array_merge($data, self::attributeArray());

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 属性数组
     * @return array
     */
    public static function attributeArray()
    {
        return [
            'campaignid' => trans('ExpenseLog.campaignid'),
            'zoneid' => trans('ExpenseLog.zoneid'),
            'cb' => trans('ExpenseLog.cb'),
            'price' => trans('ExpenseLog.price'),
            'price_gift' => trans('ExpenseLog.price_gift'),
            'actiontime' => trans('ExpenseLog.actiontime'),
            'target_type' => trans('ExpenseLog.target_type'),
            'target_cat' => trans('ExpenseLog.target_cat'),
            'target_id' => trans('ExpenseLog.target_id'),
            'source' => trans('ExpenseLog.source'),
            'channel' => trans('ExpenseLog.channel'),
            'af_income' => trans('ExpenseLog.af_income'),
            'status' => trans('ExpenseLog.status'),
            'ad_id' => trans('ExpenseLog.ad_id'),
            'origin_zone_id' => trans('ExpenseLog.origin_zone_id'),
            'refer' => trans('ExpenseLog.refer'),
            'target_deliveryid' => trans('ExpenseLog.target_deliveryid'),
            'source_log_type' => trans('ExpenseLog.source_log_type'),
            'source_log_id' => trans('ExpenseLog.source_log_id'),
            'updated_time' => trans('ExpenseLog.updated_time'),
        ];
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
}
