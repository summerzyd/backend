<?php

namespace App\Components;

use App\Components\Helper\ArrayHelper;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

/**
 * Formatter provides a set of commonly used data formatting methods.
 *
 * @author funson
 * @since 1.0
 */
class Config
{
    /*
     * 单体对象
     */
    private static $instance;

    /*
     * array配置数组
     * @param integer $agencyId
     */
    private $setting;

    private function __construct($agencyId = 0)
    {
        $list = [];
        $models = Setting::get();
        foreach ($models as $model) {
            if (!isset($list[$model->agencyid])) {
                $list[$model->agencyid] = [];
            }
            $list[$model->agencyid][$model->code] = $model->value;
        }
        $this->setting = $list;
    }

    /**
     * 获取当前单体静态变量
     * @param integer $agencyId
     * @return self.
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance->setting;
    }

    /**
     * 获取配置，先从config中找，找不到去数据表setting中找
     * @param string $key
     * @param integer $agencyId
     * @return string the formatted result.
     */
    public static function get($key, $agencyId = 0)
    {
        $value = \Illuminate\Support\Facades\Config::get($key);
        if ($value) {
            return $value;
        } else { //从setting中找，支持.分隔，如ad_spec.abc
            $setting = self::getInstance();
            if ($agencyId > 0) {
                $config = isset($setting[$agencyId]) ? $setting[$agencyId] : null;
            } elseif (isset(Auth::user()->agencyid)) {
                $config = isset($setting[Auth::user()->agencyid]) ? $setting[Auth::user()->agencyid] : null;
            }
            if (!isset($config)) {
                return null;
            }

            if (strpos($key, '.') === false) {
                if (isset($config[$key])) { // 在setting表中
                    return is_null(json_decode($config[$key])) ? $config[$key] : json_decode($config[$key], true);
                }
            } else {
                $arr = explode('.', $key);
                if (isset($config[$arr[0]])) {
                    $arrConfig = json_decode($config[$arr[0]], true);
                    unset($arr[0]);
                    $v = $arrConfig;
                    foreach ($arr as $item) {
                        $v = isset($v[$item]) ? $v[$item] : null;
                    }
                    return $v;
                }
            }
        }
        return null;
    }
}
