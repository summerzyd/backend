<?php
namespace App\Components\Symbol;

class Fota extends SymbolChild
{
    /**
     * 返回地址
     * @return mixed|string
     */
    public function getUrl()
    {
        return 'http://appresource.mayitek.com/appResource/jingjia_queryAppByNameADBS.do?t='.rand(1, 9999);
    }
}
