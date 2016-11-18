<?php

namespace App\Models;

/**
 * This is the model class for table "campaign_revenue_history".
 * @property integer $id int
 * @property integer $campaignid int
 * @property string $time datetime
 * @property string $history_revenue decimal
 * @property string $current_revenue decimal
 * @property string $updated_time timestamp
 */
class CampaignRevenueHistory extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const TIME_ZONE_HOUR = 8;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'campaign_revenue_history';

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
        'campaignid',
        'time',
        'history_revenue',
        'current_revenue',
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
            'campaignid' => trans('Campaignid'),
            'time' => trans('Time'),
            'history_revenue' => trans('History Revenue'),
            'current_revenue' => trans('Current Revenue'),
            'updated_time' => trans('Updated Time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    /**
     * 添加广告计划历史出价表数据
     * @param $params
     * @return bool
     */
    public static function storeCampaignHistoryRevenue($params)
    {
        $result = CampaignRevenueHistory::whereMulti([
            'time' => $params['time'],
            'campaignid' => $params['campaignid']
        ])->first();

        if (count($result)) {
            $campaignRevenueHistory = CampaignRevenueHistory::find($result->id);
            $campaignRevenueHistory->history_revenue = $params['history_revenue'];
            $campaignRevenueHistory->current_revenue = $params['current_revenue'];
            return $campaignRevenueHistory->save() ? true :false;
        } else {
            $campaignRevenueHistory = new CampaignRevenueHistory();
            $campaignRevenueHistory->time = $params['time'];
            $campaignRevenueHistory->campaignid = $params['campaignid'];
            $campaignRevenueHistory->history_revenue = $params['history_revenue'];
            $campaignRevenueHistory->current_revenue = $params['current_revenue'];
            return $campaignRevenueHistory->save() ? true :false;
        }
    }
}
