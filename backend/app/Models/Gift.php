<?php

namespace App\Models;

/**
 * This is the model class for table "gift".
 * @property integer $id int
 * @property integer $account_id int
 * @property integer $user_id int
 * @property integer $agencyid int
 * @property integer $target_accountid int
 * @property string $amount decimal
 * @property string $gift_info varchar
 * @property string $created_at timestamp
 * @property string $updated_at timestamp
 * @property integer $status tinyint
 * @property string $comment varchar
 * @property integer $type tinyint
 * @property string $updated_time timestamp
 */
class Gift extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const STATUS_TYPE_WAIT = 1;
    const STATUS_TYPE_PASSED = 2;
    const STATUS_TYPE_REJECTED = 3;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gift';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'agencyid',
        'target_accountid',
        'amount',
        'gift_info',
        'status',
        'comment',
        'type',
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
            'account_id' => trans('Account Id'),
            'user_id' => trans('User Id'),
            'agencyid' => trans('Agencyid'),
            'target_accountid' => trans('Target Accountid'),
            'amount' => trans('Amount'),
            'gift_info' => trans('Gift Info'),
            'created_at' => trans('Created At'),
            'updated_at' => trans('Updated At'),
            'status' => trans('Status'),
            'comment' => trans('Comment'),
            'type' => trans('Type'),
            'updated_time' => trans('Updated Time'),
            'clientid' => '广告主ID',
            'amount' => '赠送金额',
            'gift_info' => '赠送原因',
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
