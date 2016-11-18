<?php

namespace App\Models;

/**
 * This is the model class for table "invoice".
 * @property integer $id int
 * @property integer $account_id int
 * @property integer $user_id int
 * @property integer $agencyid int
 * @property integer $invoice_type tinyint
 * @property string $title varchar
 * @property string $money decimal
 * @property string $address varchar
 * @property string $receiver varchar
 * @property string $tel varchar
 * @property string $comment varchar
 * @property string $create_time timestamp
 * @property string $update_time timestamp
 * @property integer $status tinyint
 * @property string $updated_time timestamp
 */
class Invoice extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const STATUS_TYPE_APPLICATION = 1;
    const STATUS_TYPE_APPROVED = 2;
    const STATUS_TYPE_REJECTED = 3;

    const INVOICE_TYPE_NORMAL = 0;//增值税普通发票
    const INVOICE_TYPE_SPECIAL = 2;//增值税专用发票

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'invoice';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_time';
    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const CREATED_AT = 'create_time';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'agencyid',
        'invoice_type',
        'title',
        'money',
        'address',
        'receiver',
        'tel',
        'comment',
        'status',
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
            'account_id' => trans('Account Id'),
            'user_id' => trans('User Id'),
            'agencyid' => trans('Agencyid'),
            'invoice_type' => trans('Invoice Type'),
            'title' => trans('Title'),
            'money' => trans('Money'),
            'address' => trans('Address'),
            'receiver' => trans('Receiver'),
            'tel' => trans('Tel'),
            'comment' => trans('Comment'),
            'create_time' => trans('Create Time'),
            'update_time' => trans('Update Time'),
            'status' => trans('Status'),
            'updated_time' => trans('Updated Time'),
            'invoice_id' => '发票明细ID',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * 返回
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function balancelogs()
    {
        return $this->belongsToMany(
            '\App\Models\BalanceLog',
            'invoice_balance_log_assoc',
            'invoice_id',
            'balance_log_id'
        );
    }


    // Add constant labels here
    public static function getStatusLabel($statusType)
    {
        $statusLabels = [
            self::STATUS_TYPE_APPLICATION => '申请中',
            self::STATUS_TYPE_APPROVED => '审核通过',
            self::STATUS_TYPE_REJECTED => '驳回',
        ];

        if (isset($statusLabels[$statusType])) {
            return $statusLabels[$statusType];
        }

        return $statusLabels;
    }

    /**
     *获取发票类型
     * @param null $key
     * @return array|null
     */
    public static function getInvoiceTypeLabel($key = null)
    {
        $data = [
            self::INVOICE_TYPE_NORMAL => '增值税普通发票',
            self::INVOICE_TYPE_SPECIAL => '增值税专用发票',
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 返回收件人的信息
     * @param $adAccountId
     * @return array
     */
    public static function receiverInfo($adAccountId)
    {
        $adInfo = Invoice::where("account_id", "=", $adAccountId)->orderBy('create_time', 'desc')->first();
        $result = [];
        if (isset($adInfo)) {
            $addressInfo = json_decode($adInfo->address);

            $result['address'] = $addressInfo->addr;
            $result['receiver'] = $adInfo->receiver;
            $result['prov'] = $addressInfo->prov;
            $result['city'] = $addressInfo->city;
            $result['dist'] = $addressInfo->dist;
            $result['tel'] = $adInfo->tel;
        }
        return $result;
    }
}
