<?php

namespace App\Models;

/**
 * This is the model class for table "manual_clientdata".
 * @property integer $id int
 * @property string $date date
 * @property integer $affiliate_id int
 * @property string $channel varchar
 * @property integer $banner_id int
 * @property integer $campaign_id int
 * @property integer $cpa int
 * @property string $consum decimal
 * @property integer $flag int
 * @property string $update_time datetime
 * @property string $updated_time timestamp
 */
class ManualClientData extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const FLAG_UNTREATED = 0;
    const FLAG_ASSIGNED = 1;
    const FLAG_PROCESSED = 2;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'manual_clientdata';

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
        'date',
        'affiliate_id',
        'channel',
        'banner_id',
        'campaign_id',
        'cpa',
        'consum',
        'flag',
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
            'date' => trans('Date'),
            'affiliate_id' => trans('Affiliate Id'),
            'channel' => trans('Channel'),
            'banner_id' => trans('Banner Id'),
            'campaign_id' => trans('Campaign Id'),
            'cpa' => trans('Cpa'),
            'consum' => trans('Consum'),
            'flag' => trans('Flag'),
            'update_time' => trans('Update Time'),
            'updated_time' => trans('Updated Time'),
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

    // Add constant labels here
    /**
     * 获取处理标志标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getFlagLabels($key = null)
    {
        $data = [
            self::FLAG_UNTREATED => '未处理',
            self::FLAG_ASSIGNED => '已分配',
            self::FLAG_PROCESSED => '已处理',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     *
     * @param $params
     * @return bool
     */
    public static function store($params)
    {
        $count = ManualClientData::whereMulti([
            'date' => $params['date'],
            'affiliate_id' => $params['affiliate_id'],
            'banner_id' => $params['banner_id'],
            'campaign_id' => $params['campaign_id'],
        ])->count();
        if ($count > 0) {
            $result = ManualClientData::whereMulti([
                'date' => $params['date'],
                'affiliate_id' => $params['affiliate_id'],
                'banner_id' => $params['banner_id'],
                'campaign_id' => $params['campaign_id'],
            ])->update([
                'cpa' => $params['cpa'],
                'consum' => $params['consum'],
            ]);
        } else {
            $manualClientData = new ManualClientData();
            $manualClientData->fill($params);
            $result = $manualClientData->save();
        }
        return $result;
    }
}
