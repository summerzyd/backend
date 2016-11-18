<?php

namespace App\Models;

/**
 * This is the model class for table "operation_clients".
 * @property integer $id int
 * @property integer $campaign_id int 推广计划Id
 * @property integer $type tinyint 0：程序化  1：人工
 * @property integer $status tinyint 0：未处理  1：更新  2：通过
 * @property integer $issue tinyint 0：发布  1：未发布
 * @property string $check_date datetime 审核时间
 * @property integer $check_user int 审核人
 * @property string $date date
 * @property string $updated_time timestamp
 */
class OperationClient extends BaseModel
{
    const TYPE_PROGRAM_DELIVERY = 0;//程序化投放
    const TYPE_ARTIFICIAL_DELIVERY = 1;//人工投放
    
    const STATUS_PENDING_AUDIT = 0; //待更新
    const STATUS_PENDING_ACCEPT = 1; //待审核
    const STATUS_ACCEPT = 2; //审核通过
    const STATUS_REJECTED = 3; //驳回
    
    const ISSUE_APPROVAL = 0; //发布
    const ISSUE_NOT_APPROVAL =1; //不发布

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'operation_clients';

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
        'campaign_id',
        'type',
        'status',
        'issue',
        'check_date',
        'check_user',
        'date',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('OperationClient.id'),
            'campaign_id' => trans('OperationClient.campaign_id'),
            'type' => trans('OperationClient.type'),
            'status' => trans('OperationClient.status'),
            'issue' => trans('OperationClient.issue'),
            'check_date' => trans('OperationClient.check_date'),
            'check_user' => trans('OperationClient.check_user'),
            'date' => trans('OperationClient.date'),
            'updated_time' => trans('OperationClient.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
