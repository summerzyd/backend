<?php

namespace App\Components\Helper;

class UrlHelper
{
    /**
     * 图片路径地址装换
     * 如果不是网络地址，加上服务器图片地址前缀
     * @param string $imgUrl
     * @return string
     */
    public static function imageFullUrl($imgUrl)
    {
        if (stripos($imgUrl, 'http://') === false &&  $imgUrl !='') {
            $imgUrl = config('filesystems.img_web').$imgUrl;    // @codeCoverageIgnore
        } // @codeCoverageIgnore
        return $imgUrl;
    }

    /**
     * 文件服务器全路径
     * @param $fileUrl
     * @return string
     */
    public static function fileFullUrl($fileUrl, $realName)
    {
        if (stripos($fileUrl, 'http://') === false && $fileUrl != '') {
            $fileUrl = config('filesystems.f_web').'/file/download_raw?path=' .
                $fileUrl . '&real_name=' . $realName;    // @codeCoverageIgnore
        } // @codeCoverageIgnore
        return $fileUrl;
    }

    /**
     * 媒体商下载地址
     * @param $fileUrl
     * @param $realName
     * @return string
     */
    public static function fileTraffickerFullUrl($fileUrl)
    {
        if (stripos($fileUrl, 'http://') === false && $fileUrl != '') {
            $fileUrl = config('filesystems.f_web') . $fileUrl;    // @codeCoverageIgnore
        } // @codeCoverageIgnore
        return $fileUrl;
    }
}
