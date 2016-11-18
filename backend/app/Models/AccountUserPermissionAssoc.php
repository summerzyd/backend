<?php

namespace App\Models;

use Auth;

/**
 * This is the model class for table "account_user_permission_assoc".
 * @property integer $account_id mediumint 账号ID
 * @property integer $user_id mediumint 用户账号ID
 * @property integer $permission_id mediumint 权限ID
 * @property integer $is_allowed tinyint 是否允许
 * @property string $updated_time timestamp
 */
class AccountUserPermissionAssoc extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    const BOS_PERM_VIEW_REPORT = 61;//查看报表权限
    const ALLOWED = 1;//是否允许

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'account_user_permission_assoc';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'account_id,user_id,permission_id';

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
        'is_allowed',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'account_id' => trans('Account Id'),
            'user_id' => trans('User Id'),
            'permission_id' => trans('Permission Id'),
            'is_allowed' => trans('Is Allowed'),
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
    /**
     * 检查登录用户是否具有某权限
     * @param int|array $permissions
     * @return boolean
     */
    public static function check($permissions)
    {
        if (Auth::guest()) {
            return false;
        }
        $user = Auth::user();
        return AccountUserPermissionAssoc::whereMulti([
            'account_id' => $user->account->account_id,
            'user_id' => $user->user_id,
            'is_allowed' => self::ALLOWED,
            'permission_id' => $permissions
        ])->count();
    }
}
