<?php
namespace App\Components\Symbol;

class Zhuoyi extends SymbolChild
{
    /**
     * 请求地址
     * @return mixed|string
     */
    public function getUrl()
    {
        return 'http://bidding-ad.tt286.com:6100/bidding?t='.rand(1, 9999);
    }

//    /**
//     * 返回结果
//     * @param $param
//     * @return array
//     */
//    public function getValue($param)
//    {
//        $output = $this->getPostResult($param);
//        if (empty($output)) {
//            return null;
//        }
//        $ret = [];
//        foreach ($output as $data) {
//            $ret[] = array(
//                'app_id' => $data['app_id'],
//                'app_name' => $data['name'],
//                'app_icon' => $data['icon'],
//                'app_vendor' => $data['vender']
//            );
//        }
//        return ['result' => 0, 'data' => $ret];
//    }
}
