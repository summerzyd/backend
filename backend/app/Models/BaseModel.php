<?php
/**
 * Base Model
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    /**
     * @ignore
     * 获取Model不带前缀的表名称
     *
     * @return string
     */
    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    /**
     * @ignore
     * 获取Model带前缀的表名称
     * 如 up_campaigns
     *
     * @return string
     */
    public static function getTableFullName()
    {
        return DB::getTablePrefix() . with(new static)->getTable();
    }

    /**
     * @ignore
     * 可以使用数组方式查询多个条件
     * 例：Post::whereMulti(['title' => 'hello', 'type' => 1])
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $arr
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeWhereMulti($query, $arr)
    {
        if (!is_array($arr)) {
            return $query;
        }

        foreach ($arr as $key => $value) {
            $query = $query->where($key, $value);
        }
        return $query;
    }
}
