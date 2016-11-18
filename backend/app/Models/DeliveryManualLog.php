<?php

namespace App\Models;

/**
 * This is the model class for table "delivery_manual_log".
 * @property integer $deliveryid int
 * @property integer $campaignid int
 * @property integer $zoneid int
 * @property integer $bannerid int
 * @property double $price float
 * @property string $price_gift decimal
 * @property string $actiontime datetime
 * @property string $af_income decimal
 * @property string $channel varchar
 * @property string $source_log_type char
 * @property integer $amount int
 * @property string $updated_time timestamp
 */
class DeliveryManualLog extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'delivery_manual_log';

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
        'bannerid',
        'price',
        'price_gift',
        'actiontime',
        'af_income',
        'channel',
        'source_log_type',
        'amount',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'deliveryid' => trans('Deliveryid'),
            'campaignid' => trans('Campaignid'),
            'zoneid' => trans('Zoneid'),
            'bannerid' => trans('Bannerid'),
            'price' => trans('Price'),
            'price_gift' => trans('Price Gift'),
            'actiontime' => trans('Actiontime'),
            'af_income' => trans('Af Income'),
            'channel' => trans('Channel'),
            'source_log_type' => trans('Source Log Type'),
            'amount' => trans('Amount'),
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
}
