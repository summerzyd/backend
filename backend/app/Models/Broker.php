<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "brokers".
 * @property integer $brokerid mediumint 代理商ID
 * @property integer $agencyid mediumint 联盟ID
 * @property string $name varchar 代理商名称
 * @property string $brief_name varchar 代理商简称
 * @property string $contact varchar 联系人
 * @property string $email varchar 邮件
 * @property string $created datetime
 * @property string $updated datetime
 * @property integer $account_id mediumint 账号ID
 * @property integer $creator_uid int 创建人用户ID
 * @property integer $status tinyint 状态 0停用，1激活
 * @property string $updated_time timestamp
 * @property integer $revenue_type smallint
 */
class Broker extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    const STATUS_ENABLE = 1;//激活
    const STATUS_DISABLED = 0;//停用
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'brokers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'brokerid';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created';

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
        'agencyid',
        'name',
        'brief_name',
        'contact',
        'email',
        'account_id',
        'creator_uid',
        'operation_uid',
        'status',
        'revenue_type',
        'affiliateid',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'brokerid' => trans('Brokerid'),
            'agencyid' => trans('Agencyid'),
            'name' => trans('Name'),
            'brief_name' => trans('Brief Name'),
            'contact' => trans('Contact'),
            'email' => trans('Email'),
            'created' => trans('Created'),
            'updated' => trans('Updated'),
            'account_id' => trans('Account Id'),
            'creator_uid' => trans('Creator Uid'),
            'operation_uid' => trans('operation_uid'),
            'status' => trans('Status'),
            'updated_time' => trans('Updated Time'),
            'revenue_type' => trans('Revenue Type'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * 代理商对应的管理员
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agency()
    {
        return $this->belongsTo('App\Models\Agency', 'agencyid', 'agencyid');
    }
    /**
     * 代理商下面所有广告主
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients()
    {
        return $this->hasMany('App\Models\Client', 'broker_id', 'brokerid');
    }
    /**
     * 获取账号
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'account_id', 'account_id');
    }

    // Add constant labels here
    /**
     * 获取状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getStatusLabel($key = null)
    {
        $data = [
            self::STATUS_ENABLE => '激活',
            self::STATUS_DISABLED => '停用',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    public static function getAgencyBroker($field, $value, $brokerId = 0)
    {
        $agencyId = Auth::user()->agencyid;
        $broker = Broker::where('agencyid', $agencyId)
            ->where($field, $value);
        if ($brokerId) {
            $broker->where('brokerid', '<>', $brokerId);
        }
        $res = $broker->first();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }
}
