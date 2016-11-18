<?php

namespace App\Models;

/**
 * This is the model class for table "delivery_repair_log".
 * @property integer $delivery_repair_log_id int
 * @property integer $campaignid int
 * @property integer $bannerid int
 * @property integer $zoneid int
 * @property integer $source tinyint
 * @property double $amount double
 * @property string $amount_type varchar
 * @property string $expense decimal
 * @property string $revenue decimal
 * @property string $comment text
 * @property string $source_comment text
 * @property integer $status tinyint
 * @property string $date_time date
 * @property string $created_time timestamp
 * @property string $updated_time timestamp
 */
class DeliveryRepairLog extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'delivery_repair_log';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'delivery_repair_log_id';

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
        'campaignid',
        'bannerid',
        'zoneid',
        'source',
        'amount',
        'amount_type',
        'expense',
        'revenue',
        'comment',
        'source_comment',
        'status',
        'date_time',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'delivery_repair_log_id' => trans('Delivery Repair Log Id'),
            'campaignid' => trans('Campaignid'),
            'bannerid' => trans('Bannerid'),
            'zoneid' => trans('Zoneid'),
            'source' => trans('Source'),
            'amount' => trans('Amount'),
            'amount_type' => trans('Amount Type'),
            'expense' => trans('Expense'),
            'revenue' => trans('Revenue'),
            'comment' => trans('Comment'),
            'source_comment' => trans('Source Comment'),
            'status' => trans('Status'),
            'date_time' => trans('Date Time'),
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
}
