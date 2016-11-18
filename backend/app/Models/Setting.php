<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "setting".
 * @property integer $id
 * @property integer $agencyid
 * @property integer $parent_id
 * @property string $code
 * @property string $type
 * @property string $store_range
 * @property string $store_dir
 * @property string $value
 * @property integer $sort_order
 * @property string $created_time
 * @property string $updated_time
 */
class Setting extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'setting';

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
        'agencyid',
        'parent_id',
        'code',
        'type',
        'store_range',
        'store_dir',
        'value',
        'sort_order',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('Setting.id'),
            'agencyid' => trans('Setting.agencyid'),
            'parent_id' => trans('Setting.parent_id'),
            'code' => trans('Setting.code'),
            'type' => trans('Setting.type'),
            'store_range' => trans('Setting.store_range'),
            'store_dir' => trans('Setting.store_dir'),
            'value' => trans('Setting.value'),
            'sort_order' => trans('Setting.sort_order'),
            'created_time' => trans('Setting.created_time'),
            'updated_time' => trans('Setting.updated_time'),
            'data' => trans('Setting.data'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
