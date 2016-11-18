<?php

namespace App\Components\Helper;

use Illuminate\Log\Writer;
use Illuminate\Support\Facades\File;
use Monolog\Logger;

class LogHelper
{
    private static $logger = [];

    /**
     * 获取日志Handle
     *
     * @param  string  $path
     * @param  boolean  $daily
     * @param  string  $type
     * @return  \Illuminate\Log\Writer
     */
    public static function getLogger($path = 'logs', $daily = true, $type = 'info')
    {
        if (!isset(self::$logger[$type])) {
            self::$logger[$type] = new Writer(new Logger($path));
            if (!File::exists(storage_path($path))) {
                File::makeDirectory(storage_path($path));
            }
            if ($daily) {
                self::$logger[$type]->useDailyFiles(storage_path($path . '/' . $type . '.log'));
            } else {
                self::$logger[$type]->useFiles(storage_path($path. '/' . $type . '.log'));
            }
        }
        return self::$logger[$type];
    }

    /**
     * 保存错误日志
     *
     * @param  string  $str
     * @param  string  $path
     * @param  boolean  $daily
     * @param  integer  $level
     * @return  boolean
     */
    public static function error($str, $path = 'logs', $daily = false, $level = 1)
    {
        return self::save(self::convertData($str, $level), $path, $daily, 'error');
    }
    /**
     * 保存警告日志
     *
     * @param  string  $str
     * @param  string  $path
     * @param  boolean  $daily
     * @param  integer  $level
     * @return  boolean
     */
    public static function warning($str, $path = 'logs', $daily = false, $level = 1)
    {
        return self::save(self::convertData($str, $level), $path, $daily, 'warning');
    }

    /**
     * 保存notice日志
     *
     * @param  string  $str
     * @param  string  $path
     * @param  boolean  $daily
     * @param  integer  $level
     * @return  boolean
     */
    public static function notice($str, $path = 'logs', $daily = false, $level = 1)
    {
        return self::save(self::convertData($str, $level), $path, $daily, 'notice');
    }

    /**
     * 保存info日志
     *
     * @param  string  $str
     * @param  string  $path
     * @param  boolean  $daily
     * @param  integer  $level
     * @return  boolean
     */
    public static function info($str, $path = 'logs', $daily = false, $level = 1)
    {
        return self::save(self::convertData($str, $level), $path, $daily, 'info');
    }

    /**
     * 将字符串加上日志自定义前缀，包括唯一标识和执行位置
     *
     * @param  string  $str
     * @param  integer  $level
     * @return string
     */
    private static function convertData($str, $level = 1)
    {
        $timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        $time = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), $timezone)->setTimezone($timezone);
        $lumenSession = isset($_COOKIE['lumen_session']) ? $_COOKIE['lumen_session'] : '';
        $userId = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '';
        $debug = debug_backtrace();
        $file = $debug[$level]['file'];
        $line = $debug[$level]['line'];
        return '[' . $time->format('u') . ']['
            . $lumenSession . '(' . $userId . ')]['
            . $file . '(' . $line . ')]: '
            . $str;
    }

    /**
     * 将字符串保存到文件
     *
     * @param  string  $str
     * @param  string  $path
     * @param  boolean  $daily
     * @param  string  $type
     * @return string
     */
    private static function save($str, $path = 'logs', $daily = true, $type = 'info')
    {
        return self::getLogger($path, $daily, $type)->$type($str);
    }
}
