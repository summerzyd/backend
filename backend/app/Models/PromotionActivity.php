<?php

namespace App\Models;

use App\Components\Helper\UrlHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * This is the model class for table "promotion_activities".
 * @property integer $id int
 * @property string $title varchar
 * @property string $content text
 * @property string $imageurl varchar
 * @property string $filename varchar
 * @property string $startdate date
 * @property string $enddate date
 * @property string $publishtime datetime
 * @property integer $operator_accountid int
 * @property integer $operator_userid int
 * @property integer $agencyid int
 * @property integer $status tinyint
 * @property string $created_at timestamp
 * @property string $updated_at timestamp
 * @property string $role varchar
 * @property string $updated_time timestamp
 */
class PromotionActivity extends BaseModel
{
    const STATUS_PUBLISH = 1;

    const STATUS_OFFLINE = 0;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'promotion_activities';

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
    const CREATED_AT = 'created_at';

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
        'title',
        'content',
        'imageurl',
        'filename',
        'startdate',
        'enddate',
        'publishtime',
        'operator_accountid',
        'operator_userid',
        'agencyid',
        'status',
        'role',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('PromotionActivity.id'),
            'title' => trans('PromotionActivity.title'),
            'content' => trans('PromotionActivity.content'),
            'imageurl' => trans('PromotionActivity.imageurl'),
            'filename' => trans('PromotionActivity.filename'),
            'startdate' => trans('PromotionActivity.startdate'),
            'enddate' => trans('PromotionActivity.enddate'),
            'publishtime' => trans('PromotionActivity.publishtime'),
            'operator_accountid' => trans('PromotionActivity.operator_accountid'),
            'operator_userid' => trans('PromotionActivity.operator_userid'),
            'agencyid' => trans('PromotionActivity.agencyid'),
            'status' => trans('PromotionActivity.status'),
            'created_at' => trans('PromotionActivity.created_at'),
            'updated_at' => trans('PromotionActivity.updated_at'),
            'role' => trans('PromotionActivity.role'),
            'updated_time' => trans('PromotionActivity.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    public static function getStatusLabel($status)
    {
        $statuses = [
            self::STATUS_OFFLINE => '下线（未发布）状态',
            self::STATUS_PUBLISH => '已发布状态'
        ];
        
        if (isset($statuses[$status])) {
            return $statuses[$status];
        }
        
        return $statuses;
    }

    public static function getActivity($operatorAccountId, $status, $role = 'A')
    {
        $rows = PromotionActivity::where('operator_accountid', $operatorAccountId)->where('status', $status)
            ->where('role', $role)
            ->orderby('publishtime', 'desc')
            ->get();
        
        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'id' => $row->id,
                'image_url' => isset($row->imageurl) ? UrlHelper::imageFullUrl($row->imageurl) : '',
                'title' => $row->title,
                'content' => $row->content,
                'start_date' => $row->startdate,
                'end_date' => $row->enddate,
                'status' => $row->status,
                'status_label' => self::getStatusLabel($row->status)
            ];
        }
        
        // 如果没有活动，返回 null
        if (count($results) == 0) {
            return null;
        }
        
        return $results[0];
    }

    public static function getActivityList($role, $pageNo, $pageSize)
    {
        $user = Auth::user();

        $sql = DB::table('promotion_activities as act')
            ->leftJoin('users as u', 'act.operator_userid', '=', 'u.user_id')
            ->select(
                'act.id',
                'act.title',
                'act.content',
                'act.imageurl',
                'act.filename',
                'act.startdate',
                'act.enddate',
                'act.publishtime',
                'act.operator_accountid',
                'act.operator_userid',
                'act.agencyid',
                'act.status',
                'act.role',
                'u.contact_name'
            )
            ->where('act.operator_userid', '=', $user->user_id)
            ->where('act.role', '=', $role)
            ->orderBy('act.publishtime', 'desc');
        
            $total = $sql->count();
            $offset = (intval($pageNo) - 1) * intval($pageSize);
            $data = $sql->skip($offset)
            ->take($pageSize)
            ->get();

        return [
            'data' => $data,
            'total' => $total
        ];
    }
}
