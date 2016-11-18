<?php

namespace App\Models;

/**
 * This is the model class for table "agency".
 * @property integer $agencyid mediumint 联盟ID
 * @property string $name varchar 联盟名称
 * @property string $contact varchar 联系人
 * @property string $email varchar 邮件
 * @property string $logout_url varchar
 * @property integer $active smallint 是否启用
 * @property string $updated datetime
 * @property integer $account_id mediumint
 * @property string $conf_name varchar
 * @property string $crypt_key varchar 密匙
 * @property string $updated_time timestamp
 */
class Agency extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'agency';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'agencyid';

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
        'name',
        'contact',
        'email',
        'logout_url',
        'active',
        'account_id',
        'conf_name',
        'crypt_key',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'agencyid' => trans('Agencyid'),
            'name' => trans('Name'),
            'contact' => trans('Contact'),
            'email' => trans('Email'),
            'logout_url' => trans('Logout Url'),
            'active' => trans('Active'),
            'updated' => trans('Updated'),
            'account_id' => trans('Account Id'),
            'conf_name' => trans('Conf Name'),
            'crypt_key' => trans('Crypt Key'),
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

    /**
     * 该媒体商下的所有广告主
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients()
    {
        return $this->hasMany('App\Models\Client', 'agencyid', 'agencyid');
    }

    /**
     * 该媒体商下的所有媒体运营
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function traffickers()
    {
        return $this->hasMany('App\Models\Trafficker', 'agencyid', 'agencyid');
    }

    /**
     * 该媒体商下的所有广告任务，使用 hasManyThrough方法远程一对多
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns()
    {
        return $this->hasManyThrough('App\Models\Campaign', 'App\Models\Client', 'agencyid', 'clientid');
    }

    /**
     * 该媒体商下的所有广告位，使用 hasManyThrough方法远程一对多
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function zones()
    {
        return $this->hasManyThrough('App\Models\Zone', 'App\Models\Trafficker', 'agencyid', 'affiliateid');
    }

    //返回此媒体下的所有应用
    public function categories()
    {
        return $this->hasMany('App\Models\Category', 'media_id', 'agencyid');
    }

    /**
     * 返回对应账号
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->hasOne('App\Models\Account', 'account_id', 'account_id');
    }
}
