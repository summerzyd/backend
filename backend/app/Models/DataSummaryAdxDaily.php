<?php

namespace App\Models;

/**
 * This is the model class for table "data_summary_adx_daily".
 * @property integer $id bigint
 * @property integer $affiliateid int
 * @property integer $external_zone_id int
 * @property string $date date
 * @property integer $bid_number int
 * @property integer $win_number int
 * @property integer $impressions int
 * @property integer $clicks int
 * @property string $af_income decimal
 * @property string $created_time timestamp
 * @property string $updated_time timestamp
 */
class DataSummaryAdxDaily extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'data_summary_adx_daily';

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
        'affiliateid',
        'external_zone_id',
        'date',
        'bid_number',
        'win_number',
        'impressions',
        'clicks',
        'af_income',
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
            'affiliateid' => trans('Affiliateid'),
            'external_zone_id' => trans('External Zone Id'),
            'date' => trans('Date'),
            'bid_number' => trans('Bid Number'),
            'win_number' => trans('Win Number'),
            'impressions' => trans('Impressions'),
            'clicks' => trans('Clicks'),
            'af_income' => trans('Af Income'),
            'created_time' => trans('Created Time'),
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
