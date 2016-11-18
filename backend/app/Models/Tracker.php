<?php

namespace App\Models;

/**
 * This is the model class for table "trackers".
 * @property integer $trackerid mediumint
 * @property string $trackername varchar
 * @property string $description varchar
 * @property integer $clientid mediumint
 * @property integer $viewwindow mediumint
 * @property integer $clickwindow mediumint
 * @property integer $blockwindow mediumint
 * @property integer $status smallint
 * @property integer $type smallint
 * @property string $linkcampaigns enum
 * @property string $variablemethod enum
 * @property string $appendcode text
 * @property string $updated datetime
 * @property integer $bannerid int
 * @property string $updated_time timestamp
 */
class Tracker extends BaseModel
{

    const STATUS_IGNORE = 1;//忽略
    const STATUS_UNDECIDED = 2;//未定
    const STATUS_CONFIRM = 4;//确认

    /**
     * 追踪类型
     */
    const TYPE_BUY = 1;//购买
    const TYPE_BUY_CLUES = 2;//购买线索
    const TYPE_REGISTER = 3;//注册

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'trackers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'trackerid';

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
        'trackername',
        'description',
        'clientid',
        'viewwindow',
        'clickwindow',
        'blockwindow',
        'status',
        'type',
        'linkcampaigns',
        'variablemethod',
        'appendcode',
        'bannerid',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'trackerid' => trans('Tracker.trackerid'),
            'trackername' => trans('Tracker.trackername'),
            'description' => trans('Tracker.description'),
            'clientid' => trans('Tracker.clientid'),
            'viewwindow' => trans('Tracker.viewwindow'),
            'clickwindow' => trans('Tracker.clickwindow'),
            'blockwindow' => trans('Tracker.blockwindow'),
            'status' => trans('Tracker.status'),
            'type' => trans('Tracker.type'),
            'linkcampaigns' => trans('Tracker.linkcampaigns'),
            'variablemethod' => trans('Tracker.variablemethod'),
            'appendcode' => trans('Tracker.appendcode'),
            'updated' => trans('Tracker.updated'),
            'bannerid' => trans('Tracker.bannerid'),
            'updated_time' => trans('Tracker.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 返回该跟踪器关联的所有广告计划
     * @return type
     */
    public function campaigns()
    {
        return $this->belongsToMany('App\Models\Campaign', 'campaigns_trackers', 'trackerid', 'campaignid');
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
     * 添加追踪器
     * @param $name
     * @param $clientId
     * @param null $bannerId
     * @return Tracker
     */
    public static function store($name, $clientId, $bannerId = null)
    {
        $tracker = new Tracker;
        $tracker->trackername = '推广任务跟踪器';
        $tracker->description = "推广任务[$name]的跟踪器";
        $tracker->clientid = $clientId; // 广告主
        $tracker->status = self::STATUS_CONFIRM; // 默认状态：确认
        $tracker->type = self::TYPE_BUY; // 转化类型：购买
        $tracker->linkcampaigns = 'f'; // 不自动链接广告计划
        if (!is_null($bannerId)) {
            $tracker->bannerid = $bannerId;
        }
        $tracker->save();

        return $tracker;
    }
}
