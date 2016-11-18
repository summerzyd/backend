<?php

namespace App\Models;

/**
 * This is the model class for table "category".
 * @property integer $category_id int
 * @property string $name varchar
 * @property integer $media_id mediumint
 * @property integer $parent int
 * @property string $created_at timestamp
 * @property string $updated_at timestamp
 * @property integer $platform tinyint
 * @property integer $affiliateid mediumint
 * @property string $aidant_value varchar
 * @property integer $ad_type int
 * @property string $updated_time timestamp
 */
class Category extends BaseModel
{
    // add your constant definition based on {field + meaning}
    /**
     * 分类，一级类别
     */
    const PARENT_APP = 1;
    const PARENT_GAME = 2;

    /**
     * 是否被使用
     */
    const USED_NO = 0;//未使用
    const USED_YES = 1;//使用

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'category';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'category_id';

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
        'media_id',
        'parent',
        'platform',
        'affiliateid',
        'aidant_value',
        'ad_type',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'category_id' => trans('Category Id'),
            'name' => trans('Name'),
            'media_id' => trans('Media Id'),
            'parent' => trans('Parent'),
            'created_at' => trans('Created At'),
            'updated_at' => trans('Updated At'),
            'platform' => trans('Platform'),
            'affiliateid' => trans('Affiliateid'),
            'aidant_value' => trans('Aidant Value'),
            'ad_type' => trans('Ad Type'),
            'updated_time' => trans('Updated Time'),
            'name' => '类别名称',
            'action' => '操作',
            'parent '=> '分类',
            'ad_type '=> '类型',
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
     * 获取一级类别标签数组或单个标签
     * @var $key
     * @return array or string
     */
    public static function getParentLabels($key = null)
    {
        $data = [
            self::PARENT_APP => '应用',
            self::PARENT_GAME => '游戏',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取一级类别标签数组或单个标签
     * @var $key
     * @return array or string
     */
    public static function getUsedLabels($key = null)
    {
        $data = [
            self::USED_NO => '没有使用',
            self::USED_YES => '正在使用',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    /**
     * 添加分类
     * @param $params
     * @return Category|null
     */
    public static function store($params)
    {
        $category = new Category();
        $category->name = $params['name'];
        $category->media_id = $params['agencyid'];
        $category->parent = $params['parent'];
        $category->platform = $params['platform'];
        $category->affiliateid = $params['affiliateid'];
        $category->ad_type = $params['ad_type'];
        if ($category->save()) {
            return $category;
        } else {
            unset($category);
            return null;
        }
    }
}
