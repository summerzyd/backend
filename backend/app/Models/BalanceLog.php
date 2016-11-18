<?php

namespace App\Models;

/**
 * This is the model class for table "balance_log".
 * @property integer $id int 财务记录ID
 * @property integer $media_id mediumint 联盟ID
 * @property integer $kind int 运营类型 1联盟，2自营
 * @property integer $operator_accountid mediumint 操作人账号
 * @property integer $operator_userid mediumint 操作人用户账号
 * @property integer $target_acountid mediumint 目标账号
 * @property string $amount decimal 交易金额
 * @property string $gift decimal
 * @property integer $pay_type tinyint 交易类型
 * 0：在线充值
 * 1：线下充值
 * 2：媒体商分成 (ADN)
 * 3：赠送推广金充值(ADN)
 * 4：代理向广告主划账(PMP)
 * 5：广告主收到代理商的划账(PMP)
 * 6：广告主向代理商划账(PMP)
 * 7：代理商收到广告主的划账(PMP)
 * 8：广告主退款
 * 9：垫付扣款
 * 10：投放支出
 * 14：代理商向广告主划账(充值)
 * 15：代理商向广告主赠送推广金(赠送)
 * 16：代理商从广告主划账(充值)
 * 17：代理商从广告主划回推广金(赠送
 * @property string $balance decimal 账户余额
 * @property integer $balance_type tinyint
 * 记录类型
 * 0：推广金账户
 * 1：赠送账户
 * 2：媒体商分成(ADN)
 * 3：赠送推广金充值(ADN)
 * 10：未指定
 * @property string $comment varchar 备注
 * @property string $create_time timestamp
 * @property string $updated_time timestamp
 */
class BalanceLog extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    /**
     * pay_type: 交易类型
     */
    const PAY_TYPE_ONLINE_RECHARGE = 0;
    const PAY_TYPE_OFFLINE_RECHARGE = 1;
    const PAY_TYPE_MEDIA_DIVIDED = 2;
    const PAY_TYPE_PRESENT_GOLD = 3;
    const PAY_TYPE_AGENT_ADVERTISERS = 4;
    const PAY_TYPE_ADVERTISERS_RECEIVE = 5;
    const PAY_TYPE_ADVERTISERS_AGENCY = 6;
    const PAY_TYPE_AGENT_RECEIVED = 7;
    const PAY_TYPE_ADVERTISERS_REFUND = 8;
    const PAY_TYPE_ADVERTISERS_ADVANCE = 9;
    const PAY_TYPE_ON_SPENDING = 10;

    const PAY_TYPE_GOLD_BROKER_TO_ADVERTISER = 14;
    const PAY_TYPE_GIVE_BROKER_TO_ADVERTISER = 15;
    const PAY_TYPE_GOLD_ADVERTISER_TO_BROKER = 16;
    const PAY_TYPE_GIVE_ADVERTISER_TO_BROKER = 17;

    /**
     * balance_type: 记录类型
     */
    const BALANCE_TYPE_GOLD_ACCOUNT = 0;
    const BALANCE_TYPE_GIVE_ACCOUNT = 1;
    const BALANCE_TYPE_MEDIA_BUSINESS = 2;
    const BALANCE_TYPE_GIFT_PROMOTION = 3;
    const BALANCE_TYPE_NOT_SPECIFIED = 10;

    /**
     *  发票状态
     */
    const INVOICE_STATUS_YES = 1;
    const INVOICE_STATUS_NO = 0;

    const ACTION_BROKER_TO_ADVERTISER = 1;//划账代理商->广告主
    const ACTION_ADVERTISER_TO_BROKER = 2;//划账广告主->代理商
    const ACCOUNT_TYPE_GOLD = 1;//充值金账户
    const ACCOUNT_TYPE_GIVE = 2;//赠送金账户
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'balance_log';

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
    const CREATED_AT = 'create_time';

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
        'media_id',
        'kind',
        'operator_accountid',
        'operator_userid',
        'target_acountid',
        'amount',
        'gift',
        'pay_type',
        'balance',
        'balance_type',
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
            'id' => trans('Id'),
            'media_id' => trans('Media Id'),
            'kind' => trans('Kind'),
            'operator_accountid' => trans('Operator Accountid'),
            'operator_userid' => trans('Operator Userid'),
            'target_acountid' => trans('Target Acountid'),
            'amount' => trans('Amount'),
            'gift' => trans('Gift'),
            'pay_type' => trans('Pay Type'),
            'balance' => trans('Balance'),
            'balance_type' => trans('Balance Type'),
            'comment' => trans('Comment'),
            'create_time' => trans('Create Time'),
            'updated_time' => trans('Updated Time'),
            'list' => trans('model.list'),
            'ids' => trans('model.ids'),
            'title' => trans('model.title'),
            'prov' => trans('model.prov'),
            'city' => trans('model.city'),
            'dist' => trans('model.dist'),
            'address' => trans('model.address'),
            'type' =>trans('model.balance_log_type'),
            'receiver' => trans('model.receiver'),
            'tel' => trans('model.tel'),
            'invoice_id' => trans('model.invoice_id'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here

    // Add constant labels here
    /**
     * 获取发票状态标签数组或单个标签
     * @param null $status
     * @return array
     */
    public static function getInvoiceStatusLabel($status = null)
    {
        $statuses = [
            self::INVOICE_STATUS_NO => '未申请',
            self::INVOICE_STATUS_YES => '已申请'
        ];

        if ($status == null) {
            return $statuses[self::INVOICE_STATUS_NO];
        }

        if (isset($statuses[$status])) {
            return $statuses[$status];
        }

        return $statuses;
    }

    /**
     * 获取支付类型标签数组或单个标签
     * @param null $payType
     * @return array
     */
    public static function getPayTypeLabel($payType = null)
    {
        $payTypes = [
            self::PAY_TYPE_ONLINE_RECHARGE => '在线充值',
            self::PAY_TYPE_OFFLINE_RECHARGE => '线下充值',
            self::PAY_TYPE_MEDIA_DIVIDED => '媒体商分成',
            self::PAY_TYPE_PRESENT_GOLD => '赠送推广金充值',
            self::PAY_TYPE_AGENT_ADVERTISERS => '代理向广告主划账',
            self::PAY_TYPE_ADVERTISERS_RECEIVE => '广告主收到代理商的划账',
            self::PAY_TYPE_ADVERTISERS_AGENCY => '广告主向代理商划账',
            self::PAY_TYPE_AGENT_RECEIVED => '代理商收到广告主的划账',
            self::PAY_TYPE_ADVERTISERS_REFUND => '广告主退款',
            self::PAY_TYPE_ON_SPENDING => '投放支出',
            self::PAY_TYPE_GOLD_BROKER_TO_ADVERTISER => '划出',
            self::PAY_TYPE_GIVE_BROKER_TO_ADVERTISER => '划出',
            self::PAY_TYPE_GOLD_ADVERTISER_TO_BROKER => '划入',
            self::PAY_TYPE_GIVE_ADVERTISER_TO_BROKER => '划入',
        ];

        if (!isset($payTypes[$payType])) {
            return $payTypes;
        }

        return $payTypes[$payType];
    }

    /**
     * 获取账户类型标签数组或单个标签
     * @param $balanceType
     * @return array
     */
    public static function getBalanceType($balanceType)
    {
        $balanceTypes = [
            self::BALANCE_TYPE_GOLD_ACCOUNT => '推广金账户',
            self::BALANCE_TYPE_GIVE_ACCOUNT => '赠送账户',
            self::BALANCE_TYPE_MEDIA_BUSINESS => '媒体商分成',
            self::BALANCE_TYPE_GIFT_PROMOTION => '赠送推广金充值',
            self::BALANCE_TYPE_NOT_SPECIFIED => '未指定',
        ];

        if (!isset($balanceTypes[$balanceType])) {
            return $balanceTypes;
        }

        return $balanceTypes[$balanceType];
    }

    /**
     * 获取操作标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getActionLabel($key = null)
    {
        $data = [
            self::ACTION_BROKER_TO_ADVERTISER => '代理到广告主',
            self::ACTION_ADVERTISER_TO_BROKER => '广告主到代理',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取账户类型标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getAccountTypeLabel($key = null)
    {
        $data = [
            self::ACCOUNT_TYPE_GOLD => '充值账户',
            self::ACCOUNT_TYPE_GIVE => '赠送账户',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 返回关联所属发票申请列表
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function invoices()
    {
        return $this->belongsToMany(
            '\App\Models\Invoice',
            'invoice_balance_log_assoc',
            'balance_log_id',
            'invoice_id'
        );
    }
}
