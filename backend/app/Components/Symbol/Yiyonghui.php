<?php
namespace App\Components\Symbol;

class Yiyonghui extends SymbolChild
{
    /**
     * 返回地址
     * @return string
     */
    public function getUrl()
    {
        return 'http://www.anzhuoapk.com/api/BaiFenDian/getinfo?t=' . rand(1, 9999);
    }
}
