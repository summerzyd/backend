<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;

/**
 * 账户表
 * @property integer $account_id mediumint
 * @property integer $agencyid mediumint 所属联盟ID
 * @property string $account_type varchar
 * 账户类型：
 * 'ADMIN'：系统管理员
 * 'MANAGER'：媒体主管理员
 * 'ADVERTISER'：广告主
 * 'TRAFFICKER'：媒体运营
 * @property string $account_name varchar 账户名称，对应公司名
 * @property string $m2m_password varchar 用于AdExchange的秘钥，暂未使用
 * @property string $m2m_ticket varchar 用于AdExchange的Token，暂未使用
 * @property integer $manager_userid mediumint 该账户的管理账号userid
 * @property string $updated_time timestamp
 */
class Account extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const TYPE_ADMIN = 'ADMIN';
    const TYPE_MANAGER = 'MANAGER';
    const TYPE_ADVERTISER = 'ADVERTISER';
    const TYPE_TRAFFICKER = 'TRAFFICKER';
    const TYPE_BROKER = 'BROKER';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'accounts';

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
        'agencyid',
        'account_type',
        'account_name',
        'm2m_password',
        'm2m_ticket',
        'manager_userid',
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
            'agencyid' => trans('Agencyid'),
            'account_type' => trans('Account Type'),
            'account_name' => trans('Account Name'),
            'm2m_password' => trans('M2m Password'),
            'm2m_ticket' => trans('M2m Ticket'),
            'manager_userid' => trans('Manager Userid'),
            'updated_time' => trans('Updated Time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 返回对应推广金
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function balance()
    {
        return $this->hasOne('App\Models\Balance', "account_id", "account_id");
    }

    /**
     * 该广告主对应的管理员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function agency()
    {
        return $this->hasOne('App\Models\Agency', 'agencyid', 'account_id');  // @codeCoverageIgnore
    }

    /**
     * 返回对应广告主角色账户对象
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function client()
    {
        return $this->hasOne('App\Models\Client', 'account_id', 'account_id');
    }
    /**
     * 返回属于该账户的用户
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function clients()
    {
        return $this->hasMany('App\Models\Client', 'account_id', 'account_id');
    }

    /**
     * 返回对应代理商账户信息
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function broker()
    {
        return $this->hasOne('App\Models\Broker', 'account_id', 'account_id');
    }

    /**
     * 返回管理用户
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'manager_userid');
    }

    /**
     * 返回属于该账户的用户
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function users()
    {
        return $this->hasMany('App\Models\User', 'default_account_id', 'account_id');
    }

    /**
     * 返回对应媒体运营角色账户对象
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function affiliate()
    {
        return $this->hasOne('App\Models\Affiliate', 'account_id', 'account_id');
    }

    /**
     * 返回属于该账户的媒体商
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function affiliates()
    {
        return $this->hasMany('App\Models\Affiliate', 'account_id', 'account_id');
    }

    /**
     * 是否是管理员(agency manager)
     * @return boolean
     */
    public function isAdmin()
    {
        return self::TYPE_ADMIN === $this->account_type;
    }

    /**
     * 是否是管理员(agency manager)
     * @return boolean
     */
    public function isManager()
    {
        return self::TYPE_MANAGER === $this->account_type;
    }
    /**
     * 是否是广告主(client advertiser)
     * @return bool
     */
    public function isAdvertiser()
    {
        return self::TYPE_ADVERTISER === $this->account_type;
    }

    /**
     * 是否是媒体商(affiliate trafficker)
     * @return bool
     */
    public function isTrafficker()
    {
        return self::TYPE_TRAFFICKER === $this->account_type;   // @codeCoverageIgnore
    }

    /**
     * 是否代理商
     * @return bool
     */
    public function isBroker()
    {
        return self::TYPE_BROKER === $this->account_type;
    }

    /**
     * 获取类型标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getTypeStatusLabels($key = null)
    {
        $data = [
            self::TYPE_ADMIN => 'ADMIN',
            self::TYPE_MANAGER => '管理员',
            self::TYPE_TRAFFICKER => '媒体商',
            self::TYPE_ADVERTISER => '广告主',
            self::TYPE_BROKER => '代理商',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 保存账号
     * @param $userName
     * @return Account|null
     */
    public static function store($userName)
    {
        $account = new Account();
        $account->agencyid = Auth::user()->agencyid;
        $account->account_type = self::TYPE_ADVERTISER;
        $account->account_name = $userName;
        if ($account->save()) {
            return $account;
        } else {
            unset($account);
            return null;
        }
    }
}
