<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "operation_details".
 * 媒体商数据审计表
 * @property integer $id int 主键自增ID
 * @property string $day_time date 日期
 * @property integer $agencyid mediumint 联盟平台ID
 * @property string $clients decimal 实时 广告主消耗
 * @property string $traffickers decimal 实时 媒体分成
 * @property string $partners decimal 实时 联盟收益
 * @property string $audit_clients decimal 程序化投放审计后 广告主消耗
 * @property string $audit_traffickers decimal 程序化投放审计后 媒体分成
 * @property string $audit_partners decimal 程序化投放审计后 联盟收益
 * @property integer $status tinyint
 * 0-待审计
 * 1-待审核
 * 2-驳回
 * 6-审核通过（待生成审计报表数据）
 * 7-审核通过（生成审计收入报表数据 且 待生成媒体结算数据）
 * 8-审核通过（结算数据生成完成）
 * @property string $manual_clients decimal 人工投放 广告主消耗
 * @property string $manual_traffickers decimal 人工投放 媒体分成
 * @property string $manual_partners decimal 人工投放 联盟收益
 * @property string $updated_time timestamp 更新时间
 */

class OperationDetail extends BaseModel
{
    const STATUS_PENDING_AUDIT = 0;//0-待审计
    const STATUS_PENDING_ACCEPT = 1;//1-待审核
    const STATUS_REJECTED = 2;//驳回
    const STATUS_WAIT_AUDIT =3;//
    const STATUS_ACCEPT_PENDING_REPORT = 6;//审核通过（待生成审计报表数据）
    const STATUS_ACCEPT_REPORT_DONE = 7;//审核通过（生成审计收入报表数据且待生成媒体结算数据）
    const STATUS_ACCEPT_DONE = 8;//审核通过（结算数据生成完成）

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'operation_details';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

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
        'day_time',
        'agencyid',
        'clients',
        'traffickers',
        'partners',
        'audit_clients',
        'audit_traffickers',
        'audit_partners',
        'status',
        'manual_clients',
        'manual_traffickers',
        'manual_partners',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('OperationDetail.id'),
            'day_time' => trans('OperationDetail.day_time'),
            'agencyid' => trans('OperationDetail.agencyid'),
            'clients' => trans('OperationDetail.clients'),
            'traffickers' => trans('OperationDetail.traffickers'),
            'partners' => trans('OperationDetail.partners'),
            'audit_clients' => trans('OperationDetail.audit_clients'),
            'audit_traffickers' => trans('OperationDetail.audit_traffickers'),
            'audit_partners' => trans('OperationDetail.audit_partners'),
            'status' => trans('OperationDetail.status'),
            'manual_clients' => trans('OperationDetail.manual_clients'),
            'manual_traffickers' => trans('OperationDetail.manual_traffickers'),
            'manual_partners' => trans('OperationDetail.manual_partners'),
            'updated_time' => trans('OperationDetail.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 审计
     * @param date $date
     * @return boolean
     */
    public static function isAudit($date)
    {
        $count = OperationDetail::where('day_time', $date)
            ->where('agencyid', Auth::user()->agencyid)
            ->whereIn('status', [
                self::STATUS_ACCEPT_PENDING_REPORT,
                self::STATUS_ACCEPT_REPORT_DONE,
                self::STATUS_ACCEPT_DONE
            ])->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }
}
