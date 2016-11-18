<?php

namespace App\Models;

/**
 * This is the model class for table "pay_tmp".
 * up_pay_tmp 临时在线交易表
 * @property integer $id int
 * @property string $codeid varchar 充值流水号
 * @property integer $operator_accountid int 充值广告主account_id
 * @property integer $operator_userid int 充值广告主user_id
 * @property integer $agencyid int 所属agencyid
 * @property integer $pay_type tinyint 交易类型：0：在线充值 2：提款
 * @property string $money decimal 充值金额
 * @property string $ip varchar 充值用户ip
 * @property string $create_time timestamp 创建时间
 * @property string $update_time timestamp 更新时间
 * @property integer $status tinyint 0：充值 1：申请中 2：审核通过 3：驳回
 * @property string $comment varchar 备注
 * @property string $updated_time timestamp
 */
class PayTmp extends Pay
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pay_tmp';

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
        'codeid',
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
            'id' => trans('PayTmp.id'),
            'codeid' => trans('PayTmp.codeid'),
            'operator_accountid' => trans('PayTmp.operator_accountid'),
            'operator_userid' => trans('PayTmp.operator_userid'),
            'agencyid' => trans('PayTmp.agencyid'),
            'pay_type' => trans('PayTmp.pay_type'),
            'money' => trans('PayTmp.money'),
            'ip' => trans('PayTmp.ip'),
            'create_time' => trans('PayTmp.create_time'),
            'update_time' => trans('PayTmp.update_time'),
            'status' => trans('PayTmp.status'),
            'comment' => trans('PayTmp.comment'),
            'updated_time' => trans('PayTmp.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    public function chargeGetParamUnalipay($params)
    {
        $alipayConfig = $params['alipay_config'];
        $out = array(
            'args' => array(
                'service' => 'create_direct_pay_by_user',
                'payment_type' => 1,
                'partner' => $alipayConfig['partner'],
                'seller_email' => $alipayConfig['seller_email'],
                'return_url' => route('alipayReturn'),
                'notify_url' => route('alipayNotify'),
                '_input_charset' => $alipayConfig['input_charset'],
                'show_url' => '/',
                'out_trade_no' => $params['codeid'],
                'subject' => '推广金充值',
                'total_fee' => $params['money'],
                'paymethod' => 'directPay',
            ),
            'api_url' => 'https://mapi.alipay.com/gateway.do?_input_charset=utf-8',
        );
        $out['args']['sign'] = $this->signUnalipay($out['args'], $alipayConfig);
        $out['args']['sign_type'] = $alipayConfig['sign_type'];
        return $out;
    }
    
    public function signUnalipay($params, $alipay_config)
    {
        ksort($params);
        reset($params);
        $arg = "";
        foreach ($params as $key => $val) {
            $arg .= $key . '=' . $val . '&';
        }
        $sign = substr($arg, 0, strlen($arg) - 1);
        $sign = md5($sign . $alipay_config['key']);
        return $sign;
    }
}
