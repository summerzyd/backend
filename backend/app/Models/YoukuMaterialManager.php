<?php

namespace App\Models;

/**
 * This is the model class for table "youku_material_manager".
 * @property integer $id int
 * @property string $url varchar
 * @property string $source_url varchar
 * @property string $enddate date
 * @property integer $status tinyint
 * @property integer $upload_status tinyint
 * @property integer $type tinyint
 * @property string $reason varchar
 * @property string $created_time datetime
 * @property string $updated_time timestamp
 */
class YoukuMaterialManager extends BaseModel
{
    const STATUS_PENDING_SUBMISSION = 1;//待提交
    const STATUS_SYSTEM_ERROR = 2;//系统错误
    const STATUS_PENDING_AUDIT = 3;//待审核
    const STATUS_ADOPT = 4;//通过
    const STATUS_REJECT = 5;//拒绝

    const UPLOAD_STATUS_PENGDING = 1;//待上传
    const UPLOAD_STATUS_LOADING = 2;//上传中
    const UPLOAD_STATUS_FINISH = 3;//已上传

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'youku_material_manager';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_time';

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
        'enddate',
        'url',
        'source_url',
        'status',
        'type',
        'upload_status',
        'reason',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('YoukuMaterialManager.id'),
            'enddate' => trans('YoukuMaterialManager.enddate'),
            'status' => trans('YoukuMaterialManager.status'),
            'reason' => trans('YoukuMaterialManager.reason'),
            'created_time' => trans('YoukuMaterialManager.created_time'),
            'updated_time' => trans('YoukuMaterialManager.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
