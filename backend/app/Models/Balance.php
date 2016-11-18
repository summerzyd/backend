<?php

namespace App\Models;

/**
 * This is the model class for table "balances".
 * @property integer $account_id mediumint 账号ID
 * @property string $balance decimal 充值金余额
 * @property string $gift decimal 赠送金余额
 * @property string $updated_time timestamp
 */
class Balance extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'balances';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'account_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'balance',
        'gift',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'account_id' => trans('Account Id'),
            'balance' => trans('Balance'),
            'gift' => trans('Gift'),
            'updated_time' => trans('Updated Time'),
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
     * 保存账户信息
     * @param $params
     * @return Balance|null
     */
    public static function store($params)
    {
        $balance = new Balance();
        $balance->fill($params);
        if ($balance->save()) {
            return $balance;
        } else {
            unset($balance);
            return null;
        }
    }

    /**
     * 更新账户信息
     * @param $params
     * @return null
     */
    public static function updateBalance($params)
    {
        $balance = Balance::find($params['account_id']);
        $balance->balance = $params['balance'];
        $balance->gift = $params['gift'];
        if ($balance->save()) {
            return $balance;
        } else {
            unset($balance);
            return null;
        }
    }
}
