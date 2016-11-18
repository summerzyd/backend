<?php
namespace App\Components\HouseAd;

use App\Components\Helper\HttpClientHelper;
use App\Models\Campaign;

class Ios91 extends BaseHouseAd
{
    private function getUrl($platform)
    {
        $data = [
            Campaign::PLATFORM_IPHONE_JAILBREAK =>
                'http://userad.sj.91.com/soft/get?restype=1&identifier=',
            Campaign::PLATFORM_IPHONE_COPYRIGHT =>
                'http://userad.sj.91.com/soft/get?restype=22&resid=',
        ];
        return isset($data[$platform]) ? $data[$platform] : null;
    }

    public function getAppList($key, $platform)
    {
        //获取91应用接口
        $url = $this->getUrl($platform);
        if ($url == null) {
            return null;
        }
        $url = $url . $key;
        $result = HttpClientHelper::call($url);
        $result = json_decode($result, true);
        $list = [];
        if ($result['Code'] == 0) {
            $item = $result['Result'];
            //解析91接口
            $list[] = [
                'appinfos_app_name' => $item['resName'],
                'products_icon' => $item['icon'],
                'appinfo_images' => $item['snapshots'],
                'downloadurl' => '',
                'app_id' => Campaign::PLATFORM_IPHONE_JAILBREAK == $platform ? $item['identifier'] : $item['resId'],
                'star' => 0,
                'appinfos_profile' => empty($item['introReason']) ? '无' : $item['introReason'],
                'versionCode' => $item['versionName'],
                'versionName' => $item['versionCode'],
                'packageName' => $item['identifier'],
                'filesize' => $this->getFileSize($item['size']),
                'appinfos_update_des' => $item['desc'],
                'appinfos_description' => empty($item['introReason']) ? '无' : $item['introReason'],
                'platform' => $platform,
            ];
        }
        return [count($list), $list];
    }

    /**
     * 转换文件大小
     * @param $size
     * @return float|int
     */
    private function getFileSize($size)
    {
        $unit = substr($size, -2);
        if ($unit == 'MB') {
            $size = floatval(str_replace('MB', '', $size));
            $size = $size * 1024 * 1024;
        } elseif ($unit == 'GB') {
            $size = floatval(str_replace('MB', '', $size));
            $size = $size * 1024 * 1024 * 1024;
        } else {
            $size = 0;
        }
        return $size;
    }
}
