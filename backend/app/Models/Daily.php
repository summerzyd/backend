<?php

namespace App\Models;

/**
 * This is the model class for table "daily".
 * @property integer $id int
 * @property integer $agencyid mediumint
 * @property string $date varchar
 * @property integer $status tinyint
 * @property string $send_time datetime
 * @property string $receiver text
 * @property integer $type tinyint
 * @property string $created_time timestamp
 * @property string $updated_time timestamp
 */
class Daily extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const STATUS_PENDING_SEND = 1;
    const STATUS_SUSPENDED = 2;
    const STATUS_SEND = 3;
    const STATUS_FAIL = 4;

    const TYPE_DAILY = 1;
    const TYPE_WEEKLY = 2;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'daily';

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
        'agencyid',
        'date',
        'status',
        'send_time',
        'receiver',
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
            'id' => trans('Id'),
            'agencyid' => trans('Agencyid'),
            'date' => trans('Date'),
            'status' => trans('Status'),
            'send_time' => trans('Send Time'),
            'receiver' => trans('Receiver'),
            'type' => trans('Type'),
            'created_time' => trans('Created Time'),
            'updated_time' => trans('Updated Time'),
            'date' => '日报日期',
            'type' => '报表类型',
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
