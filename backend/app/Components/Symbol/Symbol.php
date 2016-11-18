<?php
namespace App\Components\Symbol;

use App\Components\Helper\LogHelper;

abstract class Symbol
{
    /**
     * 获取URL地址
     * @return mixed
     */
    abstract public function getUrl();

    /**
     *返回请求结果
     * @param $param
     * @return null
     */
    protected function getPostResult($param)
    {
        $apiUrl = $this->getUrl();
        $outPut = $this->search($apiUrl, $param);
        if (!empty($outPut)) {
            $output = json_decode($outPut, true);
            return $output;
        }
        return null;
    }

    /**
     * 格式化返回结果
     * @param $param
     * @return array
     */
    public function getValue($param)
    {
        $output = $this->getPostResult($param);
        if (empty($output)) {
            return null;
        }
        $ret = [];
        if (isset($output['data']['info'])) {
            $output = $output['data']['info'];
            foreach ($output as $data) {
                $ret[] = array(
                    'app_id' => $data['app_id'],
                    'app_name' => $data['name'],
                    'app_icon' => $data['icon'],
                    'app_package' => $data['package'],
                    'app_vendor' => $data['vender']
                );
            }
        }
        return ['result' => 0, 'data' => $ret];
    }

    /**
     * 请求
     * @param $apiUrl
     * @param $param
     * @return mixed
     */
    protected function search($apiUrl, $param)
    {
        $string = '';
        foreach ($param as $k => $v) {
            $string .= "&" . $k . "=" . $v;
        }
        $apiUrl = $apiUrl . $string;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        LogHelper::info(__CLASS__ . ' getAppId: ' . $apiUrl . 'result' . $output);
        return $output;
    }

    /**
     * 发送请求
     * @param $apiUrl
     * @param $param
     * @return mixed
     */
    protected function postSearch($apiUrl, $param, $header = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}
