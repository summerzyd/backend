<?php

namespace App\Models;

/**
 * This is the model class for table "affiliates_user_report".
 * @property integer $id int 自增ID
 * @property integer $affiliateid mediumint 媒体ID
 * @property string $date date 日期
 * @property integer $type int 类型 1日新增，2日活，3留存率
 * @property integer $num int 用户数量/留存率
 * @property integer $span int 间隔天数
 * @property string $created_time timestamp
 * @property string $updated_time timestamp
 */
class AffiliateUserReport extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    const DAILY_NEW = 1;
    const DAILY_ACTIVE = 2;
    const DAILY_RETAIN = 3;

    const FIRST_RETAIN = 1;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'affiliates_user_report';

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
        'affiliateid',
        'date',
        'type',
        'num',
        'span',
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
            'affiliateid' => trans('Affiliateid'),
            'date' => trans('Date'),
            'type' => trans('Type'),
            'num' => trans('Num'),
            'span' => trans('Span'),
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
