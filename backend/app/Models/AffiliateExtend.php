<?php

namespace App\Models;

/**
 * This is the model class for table "affiliates_extend".
 * @property integer $id int 自增ID
 * @property integer $affiliateid int 媒体ID
 * @property integer $ad_type tinyint
 * 广告类型
 * 0（应用市场）
 * 1（Banner图文）
 * 2（Feeds）
 * 3 插屏半屏）
 * 4（插屏全屏）
 * 5（Banner文字链）
 * @property integer $revenue_type tinyint 计费类型
 * @property integer $num smallint 转换个数
 * @property string $updated_at datetime
 * @property string $created_at datetime
 * @property string $updated_time timestamp
 */
class AffiliateExtend extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'affiliates_extend';

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
    const CREATED_AT = 'created_at';

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
        'ad_type',
        'revenue_type',
        'num',
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
            'ad_type' => trans('Ad Type'),
            'revenue_type' => trans('Revenue Type'),
            'num' => trans('Num'),
            'updated_at' => trans('Updated At'),
            'created_at' => trans('Created At'),
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
