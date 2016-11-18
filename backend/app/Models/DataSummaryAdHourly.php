<?php

namespace App\Models;

/**
 * This is the model class for table "data_summary_ad_hourly".
 * @property integer $data_summary_ad_hourly_id bigint
 * @property integer $ad_unique_user_count bigint
 * @property integer $zone_unique_user_count bigint
 * @property string $date_time datetime
 * @property integer $ad_id int
 * @property integer $creative_id int
 * @property integer $zone_id int
 * @property integer $requests int
 * @property integer $impressions int
 * @property integer $clicks int
 * @property integer $conversions int
 * @property string $total_basket_value decimal
 * @property integer $total_num_items int
 * @property string $total_revenue decimal
 * @property string $total_revenue_gift decimal
 * @property string $total_cost decimal
 * @property string $total_techcost decimal
 * @property string $updated datetime
 * @property string $af_income decimal
 * @property integer $impressions_mixing int
 * @property integer $conversions_mixing int
 * @property integer $cpa int
 * @property string $consum decimal
 * @property integer $file_click int
 * @property integer $file_down int
 * @property string $updated_time timestamp
 */
class DataSummaryAdHourly extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'data_summary_ad_hourly';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'data_summary_ad_hourly_id';

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
        'ad_unique_user_count',
        'zone_unique_user_count',
        'date_time',
        'ad_id',
        'creative_id',
        'zone_id',
        'requests',
        'impressions',
        'clicks',
        'conversions',
        'total_basket_value',
        'total_num_items',
        'total_revenue',
        'total_revenue_gift',
        'total_cost',
        'total_techcost',
        'af_income',
        'impressions_mixing',
        'conversions_mixing',
        'cpa',
        'consum',
        'file_click',
        'file_down',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'data_summary_ad_hourly_id' => trans('Data Summary Ad Hourly Id'),
            'ad_unique_user_count' => trans('Ad Unique User Count'),
            'zone_unique_user_count' => trans('Zone Unique User Count'),
            'date_time' => trans('Date Time'),
            'ad_id' => trans('Ad Id'),
            'creative_id' => trans('Creative Id'),
            'zone_id' => trans('Zone Id'),
            'requests' => trans('Requests'),
            'impressions' => trans('Impressions'),
            'clicks' => trans('Clicks'),
            'conversions' => trans('Conversions'),
            'total_basket_value' => trans('Total Basket Value'),
            'total_num_items' => trans('Total Num Items'),
            'total_revenue' => trans('Total Revenue'),
            'total_revenue_gift' => trans('Total Revenue Gift'),
            'total_cost' => trans('Total Cost'),
            'total_techcost' => trans('Total Techcost'),
            'updated' => trans('Updated'),
            'af_income' => trans('Af Income'),
            'impressions_mixing' => trans('Impressions Mixing'),
            'conversions_mixing' => trans('Conversions Mixing'),
            'cpa' => trans('Cpa'),
            'consum' => trans('Consum'),
            'file_click' => trans('File Click'),
            'file_down' => trans('File Down'),
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
     * Get status labels
     * @param null $key
     * @return array|string
     */
    /*public static function getStatusLabels($key = null)
    {
        $data = [
            self::STATUS_DISABLE => trans('Disable'),
            self::STATUS_ENABLE => trans('Enable'),
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }*/
}
