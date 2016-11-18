<?php

namespace App\Models;

/**
 * This is the model class for table "youku_client_manager".
 * @property integer $clientid int
 * @property string $brand varchar
 * @property integer $firstindustry int
 * @property integer $secondindustry int
 * @property integer $status tinyint
 * @property integer $type tinyint
 * @property string $reason varchar
 * @property string $created_time datetime
 * @property string $updated_time timestamp
 */
class YoukuClientManager extends BaseModel
{
    const STATUS_PENDING_SUBMISSION = 1;//待提交
    const STATUS_SYSTEM_ERROR = 2;//系统错误
    const STATUS_PENDING_AUDIT = 3;//待审核
    const STATUS_ADOPT = 4;//通过
    const STATUS_REJECT = 5;//拒绝

    const TYPE_ADDITIONAL_QUALIFICATION = 1;//需补充资质
    const TYPE_BAN_ON_DELIVERY = 2;//广告主禁止投放

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'youku_client_manager';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'clientid';

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
        'brand',
        'firstindustry',
        'secondindustry',
        'status',
        'type',
        'reason',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'clientid' => trans('YoukuClientManager.clientid'),
            'brand' => trans('YoukuClientManager.brand'),
            'firstindustry' => trans('YoukuClientManager.firstindustry'),
            'secondindustry' => trans('YoukuClientManager.secondindustry'),
            'status' => trans('YoukuClientManager.status'),
            'type' => trans('YoukuClientManager.type'),
            'reason' => trans('YoukuClientManager.reason'),
            'created_time' => trans('YoukuClientManager.created_time'),
            'updated_time' => trans('YoukuClientManager.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
