<?php
namespace App\Components\Symbol;

class Haima extends Symbol
{
    /**
     * 返回地址
     * @return mixed|string
     */
    public function getUrl()
    {
        return 'http://biddingos.haima.me/searchapi.aspx?t=' . rand(1, 9999);
    }
}
