<?php
namespace App\Components\HouseAd;

abstract class BaseHouseAd
{
    /**
     * 延迟绑定
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * 获取app应用列表
     * @param $key
     * @return mixed
     */
    abstract public function getAppList($key, $platform);
}
