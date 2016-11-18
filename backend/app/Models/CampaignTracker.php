<?php

namespace App\Models;

/**
 * This is the model class for table "campaigns_trackers".
 * @property integer $campaign_trackerid mediumint
 * @property integer $campaignid mediumint
 * @property integer $trackerid mediumint
 * @property integer $status smallint
 * @property string $updated_time timestamp
 */
class CampaignTracker extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const STATUS_CONFIRM = 4;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'campaigns_trackers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'campaign_trackerid';

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
        'trackerid',
        'status',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'campaign_trackerid' => trans('Campaign Trackerid'),
            'campaignid' => trans('Campaignid'),
            'trackerid' => trans('Trackerid'),
            'status' => trans('Status'),
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
