<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "clients".
 * @property integer $clientid mediumint
 * @property integer $agencyid mediumint
 * @property integer $broker_id mediumint
 * @property integer $affiliateid int
 * @property string $clientname varchar
 * @property string $brief_name varchar
 * @property string $contact varchar
 * @property string $email varchar
 * @property string $report enum
 * @property integer $reportinterval mediumint
 * @property string $reportlastdate date
 * @property string $reportdeactivate enum
 * @property string $comments text
 * @property string $updated datetime
 * @property integer $an_adnetwork_id int
 * @property integer $as_advertiser_id int
 * @property integer $account_id mediumint
 * @property integer $advertiser_limitation tinyint
 * @property integer $type tinyint
 * @property string $deleted_at timestamp
 * @property integer $creator_uid int
 * @property integer $operation_uid
 * @property integer $clients_status tinyint
 * @property string $updated_time timestamp
 * @property integer $revenue_type smallint
 * @property string $qualifications text
 * @property string $address varchar
 * @property string $website varchar
 * @property integer $industry int
 */

class Client extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const STATUS_ENABLE = 1;//激活
    const STATUS_DISABLED = 0;//停用

    const DEFAULT_AFFILIATE_ID = 0;//默认的媒体Id

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'clients';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'clientid';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated';
    protected $dates = ['deleted_at'];

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
        'broker_id',
        'affiliateid',
        'clientname',
        'brief_name',
        'contact',
        'email',
        'report',
        'reportinterval',
        'reportlastdate',
        'reportdeactivate',
        'comments',
        'an_adnetwork_id',
        'as_advertiser_id',
        'account_id',
        'advertiser_limitation',
        'type',
        'deleted_at',
        'creator_uid',
        'operation_uid',
        'clients_status',
        'revenue_type',
        'qualifications',
        'address',
        'website',
        'industry',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'clientid' => trans('Clientid'),
            'agencyid' => trans('Agencyid'),
            'broker_id' => trans('Broker Id'),
            'affiliateid' => trans('Affiliateid'),
            'clientname' => trans('Clientname'),
            'brief_name' => trans('Brief Name'),
            'contact' => trans('Contact'),
            'email' => trans('Email'),
            'report' => trans('Report'),
            'reportinterval' => trans('Reportinterval'),
            'reportlastdate' => trans('Reportlastdate'),
            'reportdeactivate' => trans('Reportdeactivate'),
            'comments' => trans('Comments'),
            'updated' => trans('Updated'),
            'an_adnetwork_id' => trans('An Adnetwork Id'),
            'as_advertiser_id' => trans('As Advertiser Id'),
            'account_id' => trans('Account Id'),
            'advertiser_limitation' => trans('Advertiser Limitation'),
            'type' => trans('Type'),
            'deleted_at' => trans('Deleted At'),
            'creator_uid' => trans('Creator Uid'),
            'operation_uid' => '运营人员Id',
            'clients_status' => trans('Clients Status'),
            'updated_time' => trans('Updated Time'),
            'revenue_type' => trans('Revenue Type'),
            'qualifications' => trans('Qualifications'),
            'website' => trans('Website'),
            'industry' => trans('Industry'),
            'clientname' => '广告主名称',
            'brief_name' => '广告主简称',
            'username' => '登录账号',
            'password' => '初始密码',
            'contact_name' => '联系人',
            'email_address' => '邮箱',
            'phone' => '手机号',
            'qq' => 'QQ号码',
            'creator_uid' => '销售顾问',
            'client_id' => '广告主ID',
            'field' => '字段名',
            'value' => '字段值',
            'account_type' => '账户类型',
            'type' => '类型',
            'action' => '划账方向',
            'balance' => '划账金额',
            'revenue_type' => '计费类型',
            'qualifications' => '营业执照与网络文化经营许可证',
            'address' => '地址',
            'website' => '网址',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * 该广告主对应的管理员
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agency()
    {
        return $this->belongsTo('App\Models\Agency', 'agencyid', 'agencyid');
    }

    /**
     * 获取账号
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'account_id', 'account_id');
    }
    /**
     * 该广告主下所有广告计划
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns()
    {
        return $this->hasMany('App\Models\Campaign', 'clientid', 'clientid');
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
    /**
     * 新增广告主
     * @param $params
     * @return Client|null
     */
    public static function store($params)
    {
        $client = new Client();
        $client->fill($params);
        $client->agencyid = Auth::user()->agencyid;
        $client->updated = date('Y-m-d h:i:s');
        if ($client->save()) {
            return $client;
        } else {
            unset($client);
            return null;
        }
    }
    /**
     * 获取该联盟下是否存在相同的user
     * @param $field
     * @param $value
     * @param $brokerId
     * @param $affiliateId
     * @return bool
     */
    public static function getAgencyClient($field, $value, $brokerId, $affiliateId)
    {
        $agencyId = Auth::user()->agencyid;
        $client = Client::where('agencyid', $agencyId)
            ->where($field, $value)
            ->where('broker_id', $brokerId)
            ->where('affiliateid', $affiliateId)
            ->first();
        if ($client) {
            return true;
        } else {
            return false;
        }
    }
}
