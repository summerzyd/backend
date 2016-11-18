<?php
namespace App\Components\Symbol;

class Uucun extends Symbol
{
    /**
     * 返回请求地址
     * @return mixed|string
     */
    public function getUrl()
    {
        return 'http://lzh-newp-cms.plat88.com/getAppid.do?t=' . rand(1, 9999);
    }

    /**
     * 返回处理结果
     * @param $param
     * @return array|null
     */
    public function getValue($param)
    {
        return $this->getPostResult($param);
    }
}
