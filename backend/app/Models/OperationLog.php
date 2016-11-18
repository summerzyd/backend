<?php
namespace App\Models;

use Illuminate\Support\Facades\Auth;
use App\Components\Config;

/**
 * This is the model class for table "operation_log".
 * 操作日志
 * @property integer $id
 * @property integer $target_id 广告id,bannerid
 * @property integer $category 分类 推广计划110 媒体120
 * @property integer $type 类型 1000：人工备忘录 2000：人工操作 3000：系统Job
 * @property integer $user_id 用户ID
 * @property string $operator 操作者
 * @property string $message 内容
 * @property string $created_time
 * @property string $updated_time
 */
class OperationLog extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // category
    const CATEGORY_CAMPAIGN = 110;
    const CATEGORY_BANNER = 120;

    // type
    const TYPE_REMARK = 1000;
    const TYPE_MANUAL = 2000;
    const TYPE_MANUAL_ADVERTISER = 2100;
    const TYPE_MANUAL_MANAGER = 2200;
    const TYPE_SYSTEM = 3000;

    // string
    const ADVERTISER = '广告主';
    const TRAFFICKER = '媒体商';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'operation_log';

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
        'target_id',
        'category',
        'type',
        'user_id',
        'operator',
        'message',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('OperationLog.Id'),
            'target_id' => trans('OperationLog.target_id'),
            'category' => trans('OperationLog.category'),
            'type' => trans('OperationLog.type'),
            'user_id' => trans('OperationLog.user_id'),
            'operator' => trans('OperationLog.operator'),
            'message' => trans('OperationLog.message'),
            'created_time' => trans('OperationLog.created_time'),
            'updated_time' => trans('OperationLog.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * 返回该记录对应的推广计划
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function campaign()
    {
        return $this->hasOne('App\Models\Campaign', 'campaignid', 'campaignid');
    }

    /**
     * 获取广告投放标签
     * @var $key
     * @return array or string
     */
    public static function getCategoryLabels($key = null)
    {
        $data = [
            self::CATEGORY_CAMPAIGN => '推广计划',
            self::CATEGORY_BANNER => '广告投放',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取类型标签数组或单个标签
     * @var $key
     * @return array or string
     */
    public static function getTypeLabels($key = null)
    {
        $data = [
            self::TYPE_REMARK => '人为备忘录',
            self::TYPE_MANUAL => '人为操作',
            self::TYPE_SYSTEM => '系统操作',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 存储日志
     * @var $key
     * @return array or string
     */
    public static function store($params = [])
    {
        if (!isset($params['category']) || !isset($params['type']) || !isset($params['message'])) {
            return false;
        }

        $model = new OperationLog();
        $model->category = $params['category'];
        $model->type = $params['type'];
        $model->target_id = isset($params['target_id']) ? $params['target_id'] : 0;
        $model->user_id = isset(Auth::user()->user_id) ? Auth::user()->user_id : 0;
        $model->operator = isset($params['operator'])
            ? $params['operator']
            : (isset(Auth::user()->contact_name) ? Auth::user()->contact_name : Config::get('error')[6000]);
        $model->message = $params['message'];
        if (!$model->save()) {
            return false;
        }
        return true;
    }
}
