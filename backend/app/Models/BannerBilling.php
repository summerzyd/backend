<?php

namespace App\Models;

/**
 * This is the model class for table "banners_billing".
 * @property integer $bannerid int 媒体广告ID
 * @property string $af_income decimal 媒体价
 * @property string $revenue decimal 计费价
 * @property string $updated_time timestamp
 */
class BannerBilling extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'banners_billing';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'bannerid';

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
        'af_income',
        'revenue',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'bannerid' => trans('Bannerid'),
            'af_income' => trans('Af Income'),
            'revenue' => trans('Revenue'),
            'updated_time' => trans('Updated Time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here

    // Add constant labels here
}
