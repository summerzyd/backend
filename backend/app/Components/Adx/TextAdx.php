<?php
namespace App\Components\Adx;

class TextAdx extends BaseAdx
{
    public function upload($bannerId)
    {
        //return ['code' => 0, 'msg' => '广告主没有资质'];
        //return ['code'=>1,'msg'=>'上传失败'];
        return ['code'=>2,'msg'=>'上传成功'];
    }

    public function status($bannerId)
    {
        //return ['code' => 1, 'msg' => '审核中'];
        return ['code'=>2,'msg'=>'审核通过'];
        //return ['code'=>3,'msg'=>'素材规格不对'];
    }

    public function getSize($adType)
    {
        switch ($adType) {
            case 1:
                return ['500*20', '300*50'];
            case 2:
                return ['501*20', '300*50'];
            case 3:
                return ['502*20', '300*50'];
            case 4:
                return ['503*20', '300*50'];
        }
    }
}
