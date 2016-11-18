<?php
namespace App\Components\Adx;

use App\Components\Helper\LogHelper;
use App\Models\Banner;

abstract class BaseAdx
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
     * 上传Adx广告主和素材 0:未达到提交的条件 1：上传失败 2：上传成功
     * @return array {"code":0, "msg":"广告主的资质未上传"}|{"code":2, "msg":"m_id为1001"}
     */
    abstract public function upload($bannerId);

    /**
     * 查询Adx广告主和素材状态  0:未上传 1：审核中 2：审核通过 3:拒绝
     * @return array {"code":3, "msg":"广告素材规格不对"}
     */
    abstract public function status($bannerId);


    /**
     * 获取某广告类型的规格,  0（应用市场）1（Banner纯图片）2 (Feeds) 3(插屏半屏)
     * 4(插屏全屏) 5(banner文字链) 71(appstore) 81(其他)
     * @param $param
     * @return array e.g ["500*600", "320*50"]
     */
    abstract public function getSize($adType);

    /**
     * 获取推广计划
     * @param $bannerId
     * @return mixed
     */
    protected function getCampaign($bannerId)
    {
        $banner = Banner::find($bannerId);
        return $banner->campaign;
    }
}
