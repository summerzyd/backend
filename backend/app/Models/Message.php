<?php

namespace App\Models;

/**
 * This is the model class for table "message".
 * 消息中心
 * @property integer $id int
 * @property integer $target_accountid int 目标account_id
 * @property integer $target_userid int 目标用户id
 * @property integer $operator_accountid int 操作人account_id 系统的填 0
 * @property integer $operator_userid int 操作人 userid
 * @property integer $agencyid int 所属 agencyid
 * @property string $title varchar 消息标题
 * @property string $create_time timestamp 创建时间
 * @property string $end_time timestamp 消息过期时间
 * @property string $content text 消息内容
 * @property integer $type tinyint 消息类型1：微信通知 2：短信通知 3：邮件通知 4：Web消息
 * @property integer $status tinyint 消息状态0：已发送 1：已读 2：未发送 3：发送中 4：发送失败
 * @property integer $retry_times tinyint 重发次数
 * @property string $error_code varchar 错误码
 * @property string $update_time timestamp 更新时间
 * @property string $updated_time timestamp
 */
class Message extends BaseModel
{
    /**
     * 消息状态
     */
    const STATUS_SENT = 0;
    const STATUS_READ = 1;
    const STATUS_NOT_SEND = 2;
    const STATUS_SENDING = 3;
    const STATUS_SEND_FAIL = 4;


    /**
     * 消息类型
     */
    const TYPE_WE_CHAT = 1;
    const TYPE_MESSAGE = 2;
    const TYPE_EMAIL = 3;
    const TYPE_WEB = 4;

    /**
     * 消息或者活动
     */
    const NOTE = 1;
    const ACTIVITY = 2;

    /**
     * update column
     */
    const UPDATED_AT = 'update_time';

    const CREATED_AT = 'create_time';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'message';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';


    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'target_accountid',
        'target_userid',
        'operator_accountid',
        'operator_userid',
        'agencyid',
        'title',
        'create_time',
        'end_time',
        'content',
        'type',
        'status',
        'retry_times',
        'error_code'
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('Message.id'),
            'ids' => trans('Message.ids'),
            'target_accountid' => trans('Message.target_accountid'),
            'target_userid' => trans('Message.target_userid'),
            'operator_accountid' => trans('Message.operator_accountid'),
            'operator_userid' => trans('Message.operator_userid'),
            'agencyid' => trans('Message.agencyid'),
            'title' => trans('Message.title'),
            'create_time' => trans('Message.create_time'),
            'end_time' => trans('Message.end_time'),
            'content' => trans('Message.content'),
            'type' => trans('Message.type'),
            'status' => trans('Message.message.status'),
            'retry_times' => trans('Message.retry_times'),
            'error_code' => trans('Message.error_code'),
            'update_time' => trans('Message.update_time'),
            'updated_time' => trans('Message.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取消息状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getMessageStatusLabels($key = null)
    {
        $data = [
            self::STATUS_SENT => '已发送',
            self::STATUS_READ => '已读',
            self::STATUS_SENDING => '发送中',
            self::STATUS_NOT_SEND => '未发送',
            self::STATUS_SEND_FAIL => '发送失败',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取消息类型状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getMessageTypeStatusLabels($key = null)
    {
        $data = [
            self::TYPE_WE_CHAT => '微信通知',
            self::TYPE_MESSAGE => '短信通知',
            self::TYPE_EMAIL => '邮件通知',
            self::TYPE_WEB => 'WEB消息',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
