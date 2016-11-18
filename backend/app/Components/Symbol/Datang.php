<?php
namespace App\Components\Symbol;

use App\Components\Helper\UrlHelper;
use App\Components\Helper\LogHelper;
use App\Models\Banner;
use App\Services\CampaignService;
use Illuminate\Support\Facades\DB;

class Datang extends Symbol
{
    /**
     * 返回请求地址
     * @return mixed|string
     */
    public function getUrl()
    {
        return 'http://61.160.242.46:3030/api/advArrondi_collect.action?t='.rand(1, 9999);
    }

    /**
     * 获取请求结果
     * @param $param
     * @return array
     */
    public function getValue($param)
    {
        //在此把 $param['key'] 当成 bannerid
        if (0 >= $param['key']) {
            return ['result' => 5062, 'data' => []];
        }
        $bannerId = $param['key'];
        $bannerInfo = Banner::find($bannerId)->toArray();
        if (empty($bannerInfo)) {
            return ['result' => 5062, 'data' => []];
        }

        $appId = (null !== $bannerInfo['app_id']) ? $bannerInfo['app_id'] : 0;

        //根据 campaignid 获取 appinfo 的信息
        $appRow = DB::table('campaigns')
            ->leftJoin('appinfos', 'campaigns.campaignname', '=', 'appinfos.app_id')
            ->leftJoin('banners', 'banners.campaignid', '=', 'campaigns.campaignid')
            ->leftJoin('attach_files', 'attach_files.id', '=', 'banners.attach_file_id')
            ->leftJoin('category', 'banners.category', '=', 'category.category_id')
            ->leftJoin('products', 'campaigns.product_id', '=', 'products.id')
            ->select(
                'appinfos.app_name',
                'appinfos.description',
                'appinfos.app_show_name',
                'appinfos.update_des',
                'products.icon',
                'appinfos.images',
                'appinfos.package',
                'appinfos.vender',
                'attach_files.reserve',
                'category.aidant_value',
                'banners.category'
            )
            ->where('campaigns.campaignid', $bannerInfo['campaignid'])
            ->where('banners.bannerid', $bannerId)
            ->first();
        if (empty($appRow)) {
            return ['result' => 5063, 'data' => []];
        }
        //应用截图
        $imgUrl = '';
        if (!empty($appRow->images)) {
            $list = unserialize($appRow->images);
            $images = [];
            foreach ($list as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $vSpec) {
                        $images[] = UrlHelper::imageFullUrl($vSpec);
                    }
                } else {
                    $images[] = UrlHelper::imageFullUrl($v);
                }
            }
            $imgUrl = implode(",", $images);
        }

        if (0 == $appRow->category || 1 == $appRow->category) {
            $dataArr = ['result' => 5064, 'data' => []];
        } else {
            if (!empty($appRow->reserve)) {
                $reserve = (json_decode($appRow->reserve, true));
                $appInfo = [
                    'appDesc' => $appRow->description,  //应用介绍
                    'appNameCh' => $appRow->app_name, //应用名称
                    'appSize' => (0 < $reserve['filesize']) ?
                     round($reserve['filesize'] / 1048576, 2) . 'MB' : ' 未知大小 ', //文件大小
                    'appUrl' => $bannerInfo['download_url'], //下载地址
                    'appId' => $appId, //应用 id
                    'fileMd5' => $reserve['md5'], //文件md5值
                    'jpgUrl' => $imgUrl, //应用截图
                    'newVersion' => $reserve['versionName'], //应用版本
                    'packageName' => $reserve['packageName'], //应用包名
                    'pngUrl' => UrlHelper::imageFullUrl($appRow->icon), //应用图标
                    'updateInfo' => $appRow->update_des, //更新说明
                    'versionCode' => $reserve['versionCode'], //当前应用版本
                    'code' => $appRow->aidant_value,
                ];
                $apiUrl = $this->getUrl();
                $data = json_encode($appInfo, JSON_UNESCAPED_UNICODE);
                $outPut = $this->postSearch($apiUrl, $data, [ 'Content-type: application/json;charset=UTF-8']);
                $outPut = json_decode($outPut, true);
                LogHelper::info('DaTang: Param ' . json_encode($appInfo) . 'output' . json_encode($outPut));
                if (!empty($outPut)) {
                    //更新 banner 表的app_id 及图标
                    $banner = Banner::find($bannerId);
                    $banner->app_id = $outPut['appId'];
                    $banner->app_id_icon = UrlHelper::imageFullUrl($appRow->icon);
                    $banner->app_id_word = $appRow->app_show_name;
                    $banner->buildBannerText();
                    $banner->save();
                    if ($banner->status != Banner::STATUS_PUT_IN) {
                        CampaignService::modifyBannerStatus($bannerId, Banner::STATUS_PUT_IN, true);
                    }
                    $ret = array(
                        'app_id' => $outPut['appId'],
                        'app_name' => $appRow->app_name,
                        'app_icon' => UrlHelper::imageFullUrl($appRow->icon),
                        'app_package' => $reserve['packageName'],
                        'app_vendor' => $appRow->vender
                    );
                    $dataArr = ['result' => 0, 'data' => $ret];
                } else {
                    $dataArr = ['result' => 5065, 'data' => []];
                }
            } else {
                $dataArr = ['result' => 5066, 'data' => []];
            }
        }
        return $dataArr;
    }
}
