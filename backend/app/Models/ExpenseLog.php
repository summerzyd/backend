<?php

namespace App\Models;

/**
 * This is the model class for table "expense_log".
 * @property integer $expenseid int
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
class ExpenseLog extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'expense_log';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'expenseid';

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
            'expenseid' => trans('Expenseid'),
        ];

        $data = array_merge($data, DeliveryLog::attributeArray());

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
