<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

/**
 * This is the model class for table "data_hourly_daily_af".
 * @property integer $id int
 * @property string $date date
 * @property integer $ad_id int
 * @property integer $campaign_id int
 * @property integer $zone_id int
 * @property integer $requests int
 * @property integer $impressions int
 * @property string $total_revenue decimal
 * @property string $total_revenue_gift decimal
 * @property string $af_income decimal
 * @property integer $clicks int
 * @property integer $conversions int
 * @property integer $cpa int
 * @property string $consum decimal
 * @property integer $file_click int
 * @property integer $file_down int
 * @property string $updated_time timestamp
 * @property integer $affiliateid mediumint
 * @property integer $pay tinyint
 */
class DataHourlyDailyAf extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const TYPE_ADVERTISER = 0;
    const TYPE_BONUS = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'data_hourly_daily_af';

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
        'ad_id',
        'campaign_id',
        'zone_id',
        'requests',
        'impressions',
        'total_revenue',
        'total_revenue_gift',
        'af_income',
        'clicks',
        'conversions',
        'cpa',
        'consum',
        'file_click',
        'file_down',
        'affiliateid',
        'pay',
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
            'ad_id' => trans('Ad Id'),
            'campaign_id' => trans('Campaign Id'),
            'zone_id' => trans('Zone Id'),
            'requests' => trans('Requests'),
            'impressions' => trans('Impressions'),
            'total_revenue' => trans('Total Revenue'),
            'total_revenue_gift' => trans('Total Revenue Gift'),
            'af_income' => trans('Af Income'),
            'clicks' => trans('Clicks'),
            'conversions' => trans('Conversions'),
            'cpa' => trans('Cpa'),
            'consum' => trans('Consum'),
            'file_click' => trans('File Click'),
            'file_down' => trans('File Down'),
            'updated_time' => trans('Updated Time'),
            'affiliateid' => trans('Affiliateid'),
            'pay' => trans('Pay'),
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
    public static function getTypeLabels($key = null)
    {
        $data = [
            self::TYPE_ADVERTISER => '广告收入',
            self::TYPE_BONUS => '与媒体分红',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    
    /**
     *
     * @param int $accountId
     * @param boolean $pay
     */
    public static function getAmount($accountId, $pay = false)
    {
            $select = DB::table('data_hourly_daily_af as daf')
                    ->join('affiliates as aff', 'aff.affiliateid', '=', 'daf.affiliateid')
                    ->where('aff.account_id', $accountId);
            (true == $pay) ? $select->where('daf.pay', 1) : '';
            return $select->sum('daf.af_income');
    }
}
