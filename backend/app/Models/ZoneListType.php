<?php

namespace App\Models;

/**
 * This is the model class for table "zone_list_type".
 * @property integer $id int
 * @property integer $af_id mediumint
 * @property string $listtypeid varchar
 * @property string $listtype varchar
 * @property integer $already_used tinyint
 * @property integer $type smallint
 * @property integer $ad_type int
 * @property string $updated_time timestamp
 */
class ZoneListType extends BaseModel
{
    /**
     * 广告位模块类型
     */
    const TYPE_GENERAL = 0;
    const TYPE_SEARCH = 1;

    /**
     * 广告位样式类型
     */
    const LISTTYPEID_TOP = 0;
    const LISTTYPEID_SEARCH = -1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'zone_list_type';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

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
        'af_id',
        'listtypeid',
        'listtype',
        'already_used',
        'type',
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
            'id' => trans('id'),
            'af_id' => trans('af_id'),
            'listtypeid' => trans('listtypeid'),
            'listtype' => trans('listtype'),
            'already_used' => trans('already_used'),
            'type' => trans('type'),
            'ad_type' => trans('ad_type'),
            'updated_time' => trans('updated_time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取广告位模块标签数组或单个标签
     * @var $key
     * @return array or string
     */
    public static function getTypeLabels($key = null)
    {
        $data = [
            self::TYPE_GENERAL => '通用广告位模块',
            self::TYPE_SEARCH => '搜索广告位模块',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    /**
     * 添加模块列表
     * @param $params
     * @return ZoneListType|null
     */
    public static function store($params)
    {
        $zoneListType = new ZoneListType();
        $zoneListType->listtypeid = $params['listtypeid'];
        $zoneListType->af_id = $params['affiliateid'];
        $zoneListType->listtype = $params['name'];
        $zoneListType->type = $params['type'];
        $zoneListType->ad_type = $params['ad_type'];
        if ($zoneListType->save()) {
            return $zoneListType;
        } else {
            unset($zoneListType);
            return null;
        }
    }
    /**
     * 更新模块列表
     * @param $params
     * @return null
     */
    public static function updateListType($params)
    {
        $result = ZoneListType::whereMulti([
            'id' => $params['id'],
        ])->update(['listtype' => $params['name']]);

        if ($result) {
            return $result;
        } else {
            unset($result);
            return null;
        }
    }
}
