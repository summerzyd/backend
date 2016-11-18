<?php

namespace App\Models;

/**
 * This is the model class for table "recharge".
 * @property integer $id int
 * @property integer $account_id int
 * @property integer $user_id int
 * @property integer $agencyid int
 * @property integer $target_accountid int
 * @property string $amount decimal
 * @property integer $way tinyint
 * @property string $account_info varchar
 * @property string $date date
 * @property string $apply_time timestamp
 * @property string $comment varchar
 * @property string $update_time timestamp
 * @property integer $status tinyint
 * @property integer $type tinyint
 * @property string $updated_time timestamp
 */
class Recharge extends BaseModel
{
    const STATUS_APPLYING = 1;//申请中
    const STATUS_APPROVED = 2;//审核通过
    const STATUS_REJECT = 3;//驳回

    const TYPE_ADVERTISER = 0;//广告主
    const TYPE_BROKER = 1;//代理商;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'recharge';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    const UPDATED_AT = 'update_time';
    
    const CREATED_AT = 'apply_time';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'agencyid',
        'target_accountid',
        'amount',
        'way',
        'account_info',
        'date',
        'apply_time',
        'comment',
        'status',
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
            'id' => trans('Recharge.id'),
            'account_id' => trans('Recharge.account_id'),
            'user_id' => trans('Recharge.user_id'),
            'agencyid' => trans('Recharge.agencyid'),
            'target_accountid' => trans('Recharge.target_accountid'),
            'amount' => trans('Recharge.amount'),
            'way' => trans('Recharge.way'),
            'account_info' => trans('Recharge.account_info'),
            'date' => trans('Recharge.date'),
            'apply_time' => trans('Recharge.apply_time'),
            'comment' => trans('Recharge.comment'),
            'update_time' => trans('Recharge.update_time'),
            'status' => trans('Recharge.status'),
            'type' => trans('Recharge.type'),
            'updated_time' => trans('Recharge.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getStatusLabel($key = null)
    {
        $data = [
            self::STATUS_APPLYING => '申请中',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_REJECT => '驳回',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取类型标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getTypeLabel($key = null)
    {
        $data = [
            self::TYPE_ADVERTISER => '广告主',
            self::TYPE_BROKER => '代理商',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
