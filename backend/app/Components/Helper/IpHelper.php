<?php

namespace App\Components\Helper;

use Illuminate\Support\Facades\Request;

class IpHelper
{
    /**
     * @codeCoverageIgnore
     */
    public static function getClientIp()
    {
        $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            ? $_SERVER['HTTP_X_FORWARDED_FOR'] : Request::getClientIp();

        return $ip;
    }

    /**
     * 检查输入IP是否在指定IP地址段
     * $ip: 需要检查的ip
     * $ipMask:ip掩码数组, 如 ['121.0.26.0/23','110.75.128.0/19']
     * @codeCoverageIgnore
     */
    public static function ipCheck($ip, $ipMaskList = [])
    {
        $ip = IpHelper::getDecIp($ip);

        if (empty($ip)) {
            return false;
        }

        foreach ($ipMaskList as $ipMask) {
            list($ipstr, $maskstr) = explode('/', $ipMask);
            $base = ip2long('255.255.255.255');
            $ipTmp = ip2long($ipstr);

            $mask = pow(2, 32 - intval($maskstr)) - 1;   //mask=0.0.0.255(int)
            $smask = $mask ^ $base;                     //smask=255.255.255.0(int)

            $min = $ipTmp & $smask;
            $max = $ipTmp | $mask;

            $ipAllowStart = IpHelper::getDecIp(long2ip($min));
            $ipAllowEnd = IpHelper::getDecIp(long2ip($max));

            if ($ip > $ipAllowStart && $ip < $ipAllowEnd) {
                return true;
            }
        }
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    private static function getDecIp($ip)
    {
        $ip = explode(".", $ip);
        return $ip[0] * 255 * 255 * 255 + $ip[1] * 255 * 255 + $ip[2] * 255 + $ip[3];
    }
}
