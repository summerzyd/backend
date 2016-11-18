<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "roles".
 * @property integer $id int
 * @property string $name varchar 名称
 * @property string $description varchar 描述
 * @property integer $type int 类型 1系统 2用户自定义
 * @property string $operation_list text 权限列表，以逗号分隔
 * @property integer $account_id mediumint 所属accountID
 * @property integer $created_by int
 * @property integer $updated_by int
 * @property integer $created_time timestamp
 * @property string $updated_time timestamp
 */
class Role extends BaseModel
{

    const TYPE_DEFAULT = 1; //默认
    const TYPE_USER = 2;//用户创建

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'operation_list',
        'account_id',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('Role.id'),
            'name' => trans('Role.name'),
            'description' => trans('Role.description'),
            'type' => trans('Role.type'),
            'operation_list' => trans('Role.operation_list List'),
            'account_id' => trans('Role.account_id'),
            'created_by' => trans('Role.created_by'),
            'updated_by' => trans('Role.updated_by'),
            'updated_time' => trans('Role.updated_time'),
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
    public function users()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'created_by');
    }

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

    /**
     * 获取角色状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getRoleTypeStatusLabels($key = null)
    {
        $data = [
            self::TYPE_DEFAULT => '默认',
            self::TYPE_USER => '用户创建',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 保存子账号
     * @param $params
     * @return static
     */
    public static function store($params)
    {
        //获取子账户类型名称
        $accountSubType = AccountSubType::find($params['account_sub_type_id']);
        $role = new Role();
        $role->name = $accountSubType->name;
        $role->description = $accountSubType->name;
        $role->type = self::TYPE_USER;
        $role->operation_list = $params['operation_list'];
        $role->created_by = Auth::user()->user_id;
        if ($role->save()) {
            return $role;
        } else {
            unset($role);
            return null;
        }
    }

    /**
     * 代理商创建子账号
     * @param $roleId
     * @return Role|null
     */
    public static function brokerStore($roleId)
    {
        $defaultRole = Role::find($roleId);
        $role = new Role();
        $role->name = $defaultRole->name;
        $role->description = $defaultRole->description;
        $role->type = Role::TYPE_USER;
        $role->operation_list = $defaultRole->operation_list;
        $role->created_by = Auth::user()->user_id;
        $role->updated_by = Auth::user()->user_id;
        if ($role->save()) {
            return $role;
        } else {
            unset($role);
            return null;
        }
    }
}
