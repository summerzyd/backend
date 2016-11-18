<?php

namespace App\Models;

class ChinaMobileMaterialManager extends BaseModel
{
    const STATUS_PENDING_SUBMISSION = 1;//待提交
    const STATUS_SYSTEM_ERROR = 2;//系统错误
    const STATUS_PENDING_AUDIT = 3;//待审核
    const STATUS_ADOPT = 4;//通过
    const STATUS_REJECT = 5;//拒绝
    const STATUS_BLACK_LIST = 6;//黑名单
    const STATUS_ABNORMAL = 7;//异常
    const STATUS_DELETED = 8;//已删除
    
    /**
     * update column
     * @var unknown
     */
    const UPDATED_AT = 'updated_time';

    const CREATED_AT = 'created_time';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'china_mobile_material_manager';
}
