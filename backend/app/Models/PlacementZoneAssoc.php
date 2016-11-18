<?php

namespace App\Models;

/**
 * This is the model class for table "placement_zone_assoc".
 * @property integer $placement_zone_assoc_id mediumint
 * @property integer $zone_id mediumint
 * @property integer $placement_id mediumint
 * @property string $updated_time timestamp
 */
class PlacementZoneAssoc extends BaseModel
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'placement_zone_assoc';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'placement_zone_assoc_id';

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
        'zone_id',
        'placement_id',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'placement_zone_assoc_id' => trans('PlacementZoneAssoc.id'),
            'zone_id' => trans('PlacementZoneAssoc.zone_id'),
            'placement_id' => trans('PlacementZoneAssoc.placement_id'),
            'updated_time' => trans('PlacementZoneAssoc.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
