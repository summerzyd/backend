<?php

namespace App\Models;

/**
 * This is the model class for table "operations".
 * @property integer $id int
 * @property string $name varchar
 * @property string $description varchar
 * @property string $account_type varchar
 * @property string $updated_time timestamp
 */
class Operation extends BaseModel
{
    /**
     * 隐藏的ID起止段
     */
    const HIDDEN_START = 99000;
    const HIDDEN_END = 99999;

    const MANAGER_SUPER_ACCOUNT_USER = 'manager-super-account-all';
    const MANAGER_TRAFFICKER_ACCOUNT_ALL = 'manager-trafficker-account-all';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'operations';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $hidden = ['id'];

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
        'name',
        'description',
        'account_type',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('Operation.id'),
            'name' => trans('Operation.name'),
            'description' => trans('Operation.description'),
            'account_type' => trans('Operation.account_type'),
            'updated_time' => trans('Operation.updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
