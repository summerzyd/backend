<?php
namespace App\Components\HouseAd;

class HouseAdFactory
{
    /**
     * 获取ADX类实例
     * @param $adx_class
     * @return string
     */
    public static function getClass($adx_class)
    {
        $adx_class = 'App\Components\HouseAd\\' . $adx_class;
        $adxInstance = $adx_class::create();
        return $adxInstance;
    }
}
