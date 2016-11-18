<?php

namespace App\Models;

/**
 * This is the model class for table "account_sub_type".
 * @property integer $id int 账号类型ID
 * @property string $name varchar 账号类型名称
 * @property string $account_type varchar 账号类型
 * @property integer $account_department int
 * @property integer $default_role_id int 默认权限ID
 * @property string $created_at datetime
 * @property string $updated_at datetime
 * @property string $updated_time timestamp
 */
class AccountSubType extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    const ACCOUNT_DEPARTMENT_SALES = 1; //销售
    const ACCOUNT_DEPARTMENT_MEDIA = 2; //媒介
    const ACCOUNT_DEPARTMENT_FINANCE = 3; //财务
    const ACCOUNT_DEPARTMENT_MANAGER = 4; //管理员
    const ACCOUNT_DEPARTMENT_OPERATION = 5; //运营
    const ACCOUNT_DEPARTMENT_AUDITOR = 6; //审计
    const ACCOUNT_DEPARTMENT_HANDLER = 7; //总经理
    const ACCOUNT_DEPARTMENT_CEO = 8; //CEO
    const ACCOUNT_DEPARTMENT_COO = 9; //COO

    const TYPE_MANAGER = 'MANAGER';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'account_sub_type';

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
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'account_type',
        'account_department',
        'default_role_id',
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
            'name' => trans('Name'),
            'account_type' => trans('Account Type'),
            'account_department' => trans('Account Department'),
            'default_role_id' => trans('Default Role Id'),
            'created_at' => trans('Created At'),
            'updated_at' => trans('Updated At'),
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
     * 返回默认角色
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function defaultRole()
    {
        return $this->hasOne('App\Models\Role', 'id', 'default_role_id');
    }

    // Add constant labels here
    /**
     * 获取所属部门
     *
     * @var $key
     * @return array or string
     */
    public static function getAccountDepartmentLabels($key = null)
    {
        $data = [
            self::ACCOUNT_DEPARTMENT_SALES => '销售',
            self::ACCOUNT_DEPARTMENT_MEDIA => '媒介',
            self::ACCOUNT_DEPARTMENT_FINANCE => '财务',
            self::ACCOUNT_DEPARTMENT_MANAGER => '管理员',
            self::ACCOUNT_DEPARTMENT_OPERATION => '运营',
            self::ACCOUNT_DEPARTMENT_AUDITOR => '审计',
            self::ACCOUNT_DEPARTMENT_HANDLER => '总经理',
            self::ACCOUNT_DEPARTMENT_CEO => 'CEO',
            self::ACCOUNT_DEPARTMENT_COO => 'COO',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
}
