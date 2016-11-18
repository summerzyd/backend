<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "users".
 * @property integer $user_id mediumint
 * @property integer $agencyid mediumint
 * @property string $contact_name varchar
 * @property string $email_address varchar
 * @property string $username varchar
 * @property string $password varchar
 * @property string $language varchar
 * @property integer $default_account_id mediumint
 * @property string $comments text
 * @property integer $active tinyint
 * @property integer $sso_user_id int
 * @property string $date_created datetime
 * @property string $date_last_login datetime
 * @property string $email_updated datetime
 * @property string $remember_token varchar
 * @property string $contact_phone varchar
 * @property string $deleted_at timestamp
 * @property string $qq varchar
 * @property integer $user_role tinyint
 * @property integer $account_sub_type_id int
 * @property integer $role_id int
 * @property string $updated_time timestamp
 */
class User extends BaseModel implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable,
        CanResetPassword;
    
    /**
     * 激活状态
     */
    const ACTIVE_FALSE = 0;
    const ACTIVE_TRUE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'date_created';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'date_last_login';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'agencyid',
        'contact_name',
        'email_address',
        'username',
        'password',
        'language',
        'default_account_id',
        'comments',
        'active',
        'sso_user_id',
        'date_last_login',
        'remember_token',
        'contact_phone',
        'deleted_at',
        'qq',
        'user_role',
        'account_sub_type_id',
        'role_id',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
        'date_created',
        'deleted_at'
    ];
    
    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'user_id' => trans('User.user_id'),
            'agencyid' => trans('User.agencyid'),
            'contact_name' => trans('User.contact_name'),
            'email_address' => trans('User.email_address'),
            'username' => trans('User.username'),
            'password' => trans('User.password'),
            'language' => trans('User.language'),
            'default_account_id' => trans('User.default_account_id'),
            'comments' => trans('User.comments'),
            'active' => trans('User.active'),
            'sso_user_id' => trans('User.sso_user_id'),
            'date_created' => trans('User.date_created'),
            'date_last_login' => trans('User.date_last_login'),
            'email_updated' => trans('User.email_updated'),
            'remember_token' => trans('User.remember_token'),
            'contact_phone' => trans('User.contact_phone'),
            'deleted_at' => trans('User.deleted_at'),
            'qq' => trans('User.qq'),
            'user_role' => trans('User.user_role'),
            'account_sub_type_id' => trans('User.account_sub_type_id'),
            'role_id' => trans('User.role_id'),
            'updated_time' => trans('User.updated_time'),
            'value' => '字段值',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 返回默认帐户角色
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne('App\Models\Account', 'account_id', 'default_account_id');
    }

    /**
     * 返回默认操作角色
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function role()
    {
        return $this->hasOne('App\Models\Role', 'id', 'role_id');
    }

    /**
     * 该广告主对应的管理员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function agency()
    {
        return $this->hasOne('App\Models\Agency', 'agencyid', 'agencyid');  // @codeCoverageIgnore
    }

    /**
     * 返回管辖的广告主
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients()
    {
        return $this->hasMany('App\Models\Client', 'creator_uid', 'user_id');
    }

    /**
     * 返回管辖的媒体商
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function affiliates()
    {
        return $this->hasMany('App\Models\Affiliate', 'creator_uid', 'user_id');
    }

    /**
     * 返回管辖的代理商
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function brokers()
    {
        return $this->hasMany('App\Models\Broker', 'creator_uid', 'user_id');
    }

    /**
     * 返回所有帐户角色
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function accounts()
    {
        return $this->belongsToMany('App\Models\Account', 'account_user_assoc', 'user_id', 'account_id');
    }
    /**
     * 获取激活状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getActiveStatusLabels($key = null)
    {
        $data = [
            self::ACTIVE_FALSE => '未激活',
            self::ACTIVE_TRUE => '已激活',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 取得登录账号所属的组及权限列表
     * @author arke
     * @param string
     * @return array
     */
    public function getOneRole()
    {
        if (Auth::check()) {
            if (Auth::user()->role_id > 0) {
                return $this->hasOne('App\Models\Role', 'id', 'role_id');
            }
        }
        return array();
    }


    /**
     * 获取此登录账号下的所有角色组信息
     * @author arke
     * @param string
     * @return array
     */
    public function getRoles()
    {
        if (Auth::check()) {
            return $this->hasMany('App\Models\Role', 'created_by', 'user_id');
        }
        return array();
    }


    /**
     *
     * 检查此登录账号是否有权限操作此项
     * @author arke
     * @param string
     * @return boolean
     */
    public function can($permission)
    {
        if (!empty($this->getOneRole())) {
            if ('all' ==  $this->getOneRole->operation_list) {
                return true;
            } else {
                $arr = explode(',', $permission);
                $arrOperation = explode(",", $this->getOneRole->operation_list);
                foreach ($arr as $item) {
                    if (in_array($item, $arrOperation)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 保存用户信息
     * @param $accountId
     * @param $params
     * @return static
     */
    public static function store($accountId, $roleId, $params)
    {
        $user = new User();
        $user->fill($params);
        $user->agencyid = Auth::user()->agencyid;
        $user->email_address = isset($params['email_address']) ? $params['email_address'] : $params['email'];
        $user->contact_name = isset($params['contact_name']) ? $params['contact_name'] : $params['contact'];
        $user->password = md5($params['password']);
        $user->contact_phone = $params['phone'];
        $user->comments = isset($params['comments']) ? $params['comments'] : null;
        $user->role_id = $roleId;
        $user->default_account_id = $accountId;
        $user->account_sub_type_id = isset($params['account_sub_type_id']) ?
            $params['account_sub_type_id'] : 0;
        $user->active = User::ACTIVE_TRUE;
        if ($user->save()) {
            return $user;
        } else {
            unset($user);
            return null;
        }
    }

    /**
     * 获取当前用户及子账户用户ID
     * @return mixed
     */
    public static function getAllUser()
    {
        $accountId = Auth::user()->account->account_id;
        //获取所有用户
        $userId = User::where('default_account_id', $accountId)
            ->select('user_id')
            ->get()
            ->toArray();
        return $userId;
    }

    /**
     * 获取该联盟下是否存在相同的user
     * @param $field
     * @param $value
     * @param $userId
     * @return bool
     */
    public static function getAgencyUser($field, $value, $userId = 0)
    {
        $agencyId = Auth::user()->agencyid;
        $username = User::where('agencyid', $agencyId)
            ->where($field, $value);
        if ($userId) {
            $username->where('user_id', '<>', $userId);
        }
        $res = $username->first();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }
}
