<?php
namespace App\Components\HouseAd;

use App\Components\Config;
use App\Components\Helper\HttpClientHelper;
use App\Models\Campaign;

class Letv extends BaseHouseAd
{
    private function getUrl($platform)
    {
        $data = [
            Campaign::PLATFORM_ANDROID =>
                'http://123.125.91.30/mstore_api/mapi/cpd/list?wd=',
        ];
        return isset($data[$platform]) ? $data[$platform] : null;
    }

    public function getAppList($key, $platform)
    {
        //获取乐视应用接口
        $url = $this->getUrl($platform);
        if ($url == null) {
            return null;
        }
        $url = $url . $key;
        $result = HttpClientHelper::call($url);
        $result = json_decode($result, true);
        $list = [];
        if ($result['status'] == 'SUCC') {
            $items = $result['entity']['items'];

            //解析乐视接口
            foreach ($items as $item) {
                $list[] = [
                    'appinfos_app_name' => $item['name'],
                    'products_icon' => $item['icon']['url'],
                    'appinfo_images' => $item['screenshot'],
                    'downloadurl' => $item['downloadurl'],
                    'app_id' => $item['global_id'],
                    'star' => $item['score'],
                    'appinfos_profile' => $item['editorcomment'],
                    'versionCode' => $item['versioncode'],
                    'versionName' => $item['version'],
                    'packageName' => $item['packagename'],
                    'filesize' => $item['size'],
                    'appinfos_update_des' => '',
                    'appinfos_description' => '',
                    'platform' => $platform,
                ];
            }
        }
        return [$result['entity']['total'], $list];
    }
}
