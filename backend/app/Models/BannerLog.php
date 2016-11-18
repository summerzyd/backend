<?php

namespace App\Models;

/**
 * This is the model class for table "banner_log".
 * @property integer $id int
 * @property integer $bannerid mediumint 媒体广告ID
 * @property integer $type int 类型
 * @property string $operator varchar 操作人
 * @property string $message varchar 消息
 * @property string $created_time timestamp
 * @property string $updated_time timestamp
 */
class BannerLog extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    /**
     * 增加推广计划动作
     */
    const TYPE_REMARK = 10000;
    const TYPE_MANUAL = 20000;
    const TYPE_MANUAL_ADVERTISER = 21000;
    const TYPE_MANUAL_MANAGER = 22000;
    const TYPE_SYSTEM = 30000;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'banner_log';

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
        'bannerid',
        'type',
        'operator',
        'message',
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
            'bannerid' => trans('Bannerid'),
            'type' => trans('Type'),
            'operator' => trans('Operator'),
            'message' => trans('Message'),
            'created_time' => trans('Created Time'),
            'updated_time' => trans('Updated Time'),
            'list' => '帐户明细的类型',
            'ids' => '发票申请ID',
            'title' => '发票抬头',
            'prov' => '省',
            'city' => '市',
            'dist' => '区',
            'address' => '地址',
            'type' => '发票类型',
            'receiver' => '收件人',
            'tel' => '手机号',
            'invoice_id' => '发票ID',
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


    /**
     * 返回该记录对应的推广计划
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function banner()
    {
        return $this->hasOne('App\Models\Banner', 'bannerid', 'bannerid');
    }

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

    /**
     * Get type labels
     * @param null $key
     * @return array|string
     */
    public static function getTypeLabels($key = null)
    {
        $data = [
            self::TYPE_REMARK => '人为备忘录',
            self::TYPE_MANUAL => '人为操作',
            self::TYPE_SYSTEM => '系统操作',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
