<?php

namespace App\Models;

/**
 * This is the model class for table "iqiyi_material_manager".
 * @property integer $id int
 * @property string $url varchar
 * @property string $m_id varchar
 * @property string $tv_id varchar
 * @property string $startdate date
 * @property string $enddate date
 * @property integer $status tinyint
 * @property string $reason varchar
 * @property string $created_time datetime
 * @property string $updated_time timestamp
 */
class IQiyiMaterialManager extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const STATUS_PENDING_SUBMISSION = 1;//待提交
    const STATUS_SYSTEM_ERROR = 2;//系统错误
    const STATUS_PENDING_AUDIT = 3;//待审核
    const STATUS_ADOPT = 4;//通过
    const STATUS_REJECT = 5;//拒绝
    const STATUS_OFFLINE = 6;//下线
    const STATUS_UPLOAD_DEAL = 7;//上传成功，处理中

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'iqiyi_material_manager';

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
        'url',
        'm_id',
        'tv_id',
        'startdate',
        'enddate',
        'status',
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
            'id' => trans('Id'),
            'url' => trans('Url'),
            'm_id' => trans('M Id'),
            'tv_id' => trans('Tv Id'),
            'startdate' => trans('Startdate'),
            'enddate' => trans('Enddate'),
            'status' => trans('Status'),
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
