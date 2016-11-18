<?php

namespace App\Models;

/**
 * This is the model class for table "iqiyi_client_manager".
 * @property integer $clientid int
 * @property string $clientname varchar
 * @property string $industry varchar
 * @property integer $status tinyint
 * @property string $upload_op varchar
 * @property string $reason varchar
 * @property string $created_time datetime
 * @property string $updated_time timestamp
 */
class IQiyiClientManager extends BaseModel
{
    // add your constant definition based on {field + meaning}
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
    protected $table = 'iqiyi_client_manager';

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
        'clientname',
        'industry',
        'status',
        'upload_op',
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
            'clientid' => trans('Clientid'),
            'clientname' => trans('Clientname'),
            'industry' => trans('Industry'),
            'status' => trans('Status'),
            'upload_op' => trans('Upload Op'),
            'reason' => trans('Reason'),
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
