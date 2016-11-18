<?php

namespace App\Models;

/**
 * This is the model class for table "pay".
 * 在线交易表
 * @property integer $id int
 * @property string $codeid varchar 充值流水号
 * @property string $codepay varchar 充值渠道单号
 * @property integer $operator_accountid int 操作人account_id
 * @property integer $operator_userid int 操作人user_id
 * @property integer $agencyid int 所属agencyid
 * @property integer $pay_type tinyint 交易类型：0：在线充值 2：提款
 * @property string $money decimal 充值金额
 * @property string $ip varchar 充值用户ip
 * @property string $create_time timestamp 创建时间
 * @property string $update_time timestamp 到账时间
 * @property integer $status tinyint 状态 0：充值 1：申请中 2：审核通过 3：驳回
 * @property string $comment varchar 备注
 * @property string $updated_time timestamp 更新时间
 */
class Pay extends BaseModel
{
    /*
     * 支付类型
     */
    const PAY_TYPE_ONLINE = 0;
    const PAY_TYPE_DRAWINGS = 2;

    /**
     * 状态
     */
    const STATUS_RECHARGE = 0;
    const STATUS_APPLICATION = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECT = 3;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'create_time';
    
    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_time';
    
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pay';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';



    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codeid',
        'codepay',
        'operator_accountid',
        'operator_userid',
        'agencyid',
        'pay_type',
        'money',
        'ip',
        'status',
        'comment',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('Pay.id'),
            'codeid' => trans('Pay.codeid'),
            'codepay' => trans('Pay.codepay'),
            'operator_accountid' => trans('Pay.operator_accountid'),
            'operator_userid' => trans('Pay.operator_userid'),
            'agencyid' => trans('Pay.agencyid'),
            'pay_type' => trans('Pay.pay_type'),
            'money' => trans('Pay.money'),
            'ip' => trans('Pay.ip'),
            'create_time' => trans('Pay.create_time'),
            'update_time' => trans('Pay.update_time'),
            'status' => trans('Pay.status'),
            'comment' => trans('Pay.comment'),
            'updated_time' => trans('Pay.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    
    /**
     * 返回账号
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne('App\Models\Account', 'account_id', 'operator_accountid');
    }
    
    /**
     * 返回用户
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'operator_userid');
    }
    
    
    public static function getPayStatusLabel($payStatus = null)
    {
        $statuses = [
            self::STATUS_RECHARGE => '充值',
            self::STATUS_APPLICATION => '申请中',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_REJECT => '驳回',
        ];
    
        if (isset($statuses[$payStatus])) {
            return $statuses[$payStatus];
        }
    
        return $statuses;
    }
    
    
    public static function getPayTmpTypeLabel($payTmpType = null)
    {
        $types = [
            self::PAY_TYPE_ONLINE => '在线充值',
            self::PAY_TYPE_DRAWINGS => '提款',
        ];
    
        if (isset($types[$payTmpType])) {
            return $types[$payTmpType];
        }
    
        return $types;
    }
}
