<?php

namespace App\Models;

use App\Components\Adx\AdxFactory;
use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "affiliates".
 * @property integer $affiliateid mediumint 媒体ID
 * @property integer $agencyid mediumint 联盟ID
 * @property string $name varchar 媒体名称
 * @property string $mnemonic varchar
 * @property string $comments text 备注
 * @property string $contact varchar 联系人
 * @property string $email varchar 邮件
 * @property string $payment_type varchar 支付类型
 * @property string $alipay_account varchar 支付宝账号
 * @property string $bank_account_id varchar 银行账号ID
 * @property string $bank_account_name varchar 银行账号名称
 * @property string $bank_name varchar  银行名称
 * @property string $website varchar 官网
 * @property string $updated datetime
 * @property integer $an_website_id int
 * @property string $oac_country_code char
 * @property integer $oac_language_id int
 * @property integer $oac_category_id int
 * @property integer $as_website_id int
 * @property integer $account_id mediumint
 * @property string $crypt_key varchar 密匙
 * @property string $income_rate decimal 收入分成
 * @property string $income_amount decimal
 * @property string $self_income_amount decimal
 * @property integer $mode int 接入模式
 * @property integer $kind int 媒体运营模式 1联盟，2自营
 * @property integer $delivery_type int 投放类型 1应用，2游戏
 * @property integer $creator_uid int 创建人ID
 * @property string $created date
 * @property integer $affiliates_status tinyint 媒体状态
 * @property string $symbol varchar 对外接口
 * @property integer $type tinyint
 * @property string $ad_type varchar
 * @property integer $affiliate_type 1 ADN 2 ADX
 * 广告类型
 * 0（应用市场）
 * 1（Banner图文）
 * 2（Feeds）
 * 3 （插屏半屏）
 * 4（插屏全屏）
 * 5（Banner文字链）
 * @property string $brief_name varchar 媒体商简称
 * @property integer $app_platform smallint 可接受平台
 * @property string $updated_time timestamp
 * @property integer $audit int 是否需要审核
 * @property string $condition_data text 条件数据
 * @property string $condition text
 * @property string $self_affiliate_type text
 * 自营媒体类型
 * 1.应用市场
 * 2.展示类APP
 */
class Affiliate extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    const STATUS_ENABLE = 1;
    const STATUS_DISABLED = 0;

    const TYPE_ADN = 1;
    const TYPE_ADX = 2;

    const MODE_MEDIA_DOWNLOAD = 0;//媒体下载
    const MODE_PROGRAM_DELIVERY_STORAGE = 1;//程序化投放（入库）
    const MODE_ARTIFICIAL_DELIVERY = 2;//人工投放
    const MODE_PROGRAM_DELIVERY_NO_STORAGE = 3;//程序化投放(不入库)
    const MODE_ADX = 4;//ADX

    const KIND_NONE = 0; //都未选
    const KIND_ALLIANCE = 1; //联盟
    const KIND_SELF = 2; //自营
    const KIND_ALL = 3; //直接联盟+自营

    const DELIVERY_TYPE_APPLICATION = 1; //应用
    const DELIVERY_TYPE_GAME = 2; //游戏
    const DELIVERY_TYPE_ALL = 3; //应用 +游戏

    const AFFILIATE_TYPE_ADN = 1; //ADN
    const AFFILIATE_TYPE_ADX = 2; //ADX

    const TYPE_DIRECT_STORAGE_QUERY = 0;//入库直接查询
    const TYPE_SUBMIT_STORAGE_QUERY = 1;//提交入库查询
    const TYPE_NOT_STORAGE_QUERY = 2;//不直接入库查询

    const AUDIT_NOT_APPROVAL = 1;//审核不通过
    const AUDIT_APPROVAL = 2;//审核

    const D_TO_C_NUM = 10;//D转C基数

    const SELF_AFFILIATE_TYPE_MARKET = 1;//应用市场
    const SELF_AFFILIATE_TYPE_APP = 2;//展示类APP
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'affiliates';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'affiliateid';

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
        'mnemonic',
        'comments',
        'contact',
        'email',
        'payment_type',
        'alipay_account',
        'bank_account_id',
        'bank_account_name',
        'bank_name',
        'website',
        'an_website_id',
        'oac_country_code',
        'oac_language_id',
        'oac_category_id',
        'as_website_id',
        'app_platform',
        'audit',
        'cdn',
        'income_rate',
        'mode',
        'account_id',
        'crypt_key',
        'income_rate',
        'income_amount',
        'self_income_amount',
        'mode',
        'kind',
        'delivery_type',
        'creator_uid',
        'affiliates_status',
        'symbol',
        'type',
        'ad_type',
        'brief_name',
        'app_platform',
        'audit',
        'condition_data',
        'condition',
        'alipay_account',
        'affiliate_type',
        'self_affiliate_type',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'affiliateid' => trans('Model.affiliateid'),
            'agencyid' => trans('Model.agencyid'),
            'name' => '媒体全称',
            'mnemonic' => trans('Model.mnemonic'),
            'comments' => trans('Model.comments'),
            'contact' => trans('Model.contact'),
            'email' => trans('Model.email'),
            'payment_type' => trans('Model.payment_type'),
            'alipay_account' => trans('Model.alipay_account'),
            'bank_account_id' => trans('Model.bank_account_id'),
            'bank_account_name' => trans('Model.bank_account_name'),
            'bank_name' => trans('Model.bank_name'),
            'website' => trans('Model.website'),
            'updated' => trans('Model.updated'),
            'an_website_id' => trans('Model.an_website_id'),
            'oac_country_code' => trans('Model.oac_country_code'),
            'oac_language_id' => trans('Model.oac_language_id'),
            'oac_category_id' => trans('Model.oac_category_id'),
            'as_website_id' => trans('Model.as_website_id'),
            'account_id' => trans('Model.account_id'),
            'crypt_key' => trans('Model.crypt_key'),
            'income_rate' => trans('Model.income_rate'),
            'income_amount' => trans('Model.income_amount'),
            'self_income_amount' => trans('Model.self_income_amount'),
            'mode' => trans('Model.mode'),
            'kind' => trans('Model.kind'),
            'delivery_type' => trans('Model.delivery_type'),
            'creator_uid' => trans('Model.creator_uid'),
            'created' => trans('Model.created'),
            'affiliates_status' => trans('Model.affiliates_status'),
            'symbol' => trans('Model.symbol'),
            'type' => trans('Model.type'),
            'ad_type' => trans('Model.ad_type'),
            'brief_name' => trans('Model.brief_name'),
            'app_platform' => trans('Model.app_platform'),
            'updated_time' => trans('Model.updated_time'),
            'audit' => trans('Model.audit'),
            'condition_data' => trans('Model.condition_data'),
            'condition' => trans('Model.condition'),
            'username' => trans('Model.username'),
            'phone' => trans('Model.phone'),
            'contact_phone' => trans('Model.contact_phone'),
            'qq' => trans('Model.qq'),
            'id' => trans('Model.affiliate_id'),
            'field' => trans('Model.field'),
            'value' => trans('Model.value'),
            'words' => trans('Model.words'),
            'platform' => trans('Model.platform'),
            'campaignid' => trans('Model.keyword_campaignid'),
            'affiliate_type' => trans('Model.affiliate_type'),
            'self_affiliate_type' => trans('Model.self_affiliate_type'),
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


    /**
     * 该媒体对应的管理员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function account()
    {
        return $this->belongsTo('App\Models\Account', 'account_id', 'account_id');
    }

    /**
     * 该媒体对应的管理员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function agency()
    {
        return $this->belongsTo('App\Models\Agency', 'agencyid', 'agencyid');
    }
    /**
     * 返回该媒体下所有广告位
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function zones()
    {
        return $this->hasMany('App\Models\Zone', 'affiliateid', 'affiliateid');
    }

    /**
     * 代理商下面所有广告主
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients()
    {
        return $this->hasMany('App\Models\Client', 'affiliateid', 'affiliateid');
    }

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
     * 获取状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getStatusLabel($key = null)
    {
        $data = [
            self::STATUS_ENABLE => '运营中',
            self::STATUS_DISABLED => '暂停运营',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取媒体状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getAuditLabel($key = null)
    {
        $data = [
            self::AUDIT_NOT_APPROVAL => '不审核',
            self::AUDIT_APPROVAL => '审核',
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
            self::TYPE_DIRECT_STORAGE_QUERY => '直接入库查询',
            self::TYPE_SUBMIT_STORAGE_QUERY => '提交入库查询',
            self::TYPE_NOT_STORAGE_QUERY => '不入库不查询自动生成',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    /**
     * 获取接入方式标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getModeLabels($key = null)
    {
        $data = [
            self::MODE_MEDIA_DOWNLOAD => '媒体下载',
            self::MODE_PROGRAM_DELIVERY_STORAGE => '程序化投放(入库)',
            self::MODE_ARTIFICIAL_DELIVERY => '人工投放',
            self::MODE_PROGRAM_DELIVERY_NO_STORAGE => '程序化投放(不入库)',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取该联盟下是否存在相同的user
     * @param $field
     * @param $value
     * @param $affiliateId
     * @return bool
     */
    public static function getAgencyAffiliate($field, $value, $affiliateId = 0)
    {
        $agencyId = Auth::user()->agencyid;
        $affiliate = Affiliate::where('agencyid', $agencyId)
            ->where($field, $value);
        if ($affiliate) {
            $affiliate->where('affiliateid', '<>', $affiliateId);
        }
        $res = $affiliate->first();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取媒体支持的素材格式
     * @param $adType
     * @param $adxClass
     * @return mixed
     */
    public static function getAdSpec($adType, $adxClass)
    {
        if (empty($adxClass)) {
            return '';
        }
        $adxInstance = AdxFactory::getClass($adxClass);
        return $adxInstance->getSize($adType);
    }
}
