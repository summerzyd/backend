<?php
namespace App\Components\Symbol;

class Ios91Genuine extends Symbol
{
    /**
     * 返回请求地址
     * @return mixed|string
     */
    public function getUrl()
    {
        return 'http://exp.pgzs.com/soft.ashx?act=201&cs=bos&mt=1&bosrid='.rand(1, 9999);
    }

    /**
     * 处理返回结果
     * @param $param
     * @return array
     */
    public function getValue($param)
    {
        $apiUrl = $this->getUrl();
        $param['resId'] = $param['key'];
        $outPut = $this->search($apiUrl, $param);
        if (!empty($outPut)) {
            $ret = array();
            $output = json_decode($outPut, true);
            if (!empty($output['Result'])) {
                $data = $output['Result'];
                $ret[] = array(
                        'app_id' => $data['resId'],
                        'app_name' => $data['resName'],
                        'app_icon' => $data['icon'],
                        'app_package' => $data['identifier'],
                        'app_vendor' => $data['author']
                    );
                $dataArr = array('result'=>0,'data'=>$ret);
            } else {
                $dataArr =  array('result'=>1,'data'=>array());
            }
        } else {
            $dataArr =  array('result'=>1,'data'=>array());
        }
        return $dataArr;
    }
}
