<?php
namespace App\Components\Symbol;

class Landing extends Symbol
{
    /**
     * 返回请求地址
     * @return mixed|string
     */
    public function getUrl()
    {
        return 'http://app.iwalnuts.com/bos/land_search?check_user=1&t=' . rand(1, 9999);
    }

    /**
     * 处理返回结果
     * @param $param
     * @return array
     */
    public function getValue($param)
    {
        $apiUrl = $this->getUrl();
        $newParam = [];
        foreach ($param as $k => $v) {
            $key = strtolower($k);
            if ($key == 'platform') {
                $newParam[$key] = 3;
            } else {
                $newParam[$key] = $v;
            }
        }
        $outPut = $this->search($apiUrl, $newParam);
        if (empty($outPut)) {
            return ['result' => 5002, 'data' => []];
        }
        $ret = [];
        $output = json_decode($outPut, true);
        if (0 < $output['data']['total']) {
            $output = $output['data']['info'];
            foreach ($output as $data) {
                $ret[] = array(
                    'app_id' => $data['app_id'],
                    'app_name' => $data['name'],
                    'app_icon' => $data['icon'],
                    'app_package' => isset($data['package']) ? $data['package'] : '',
                    'app_vendor' => $data['vender']
                );
            }
            $dataArr = ['result' => 0, 'data' => $ret];
        } else {
            $dataArr = ['result' => 5002, 'data' => []];
        }
        return $dataArr;
    }
}
