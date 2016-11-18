<?php

namespace App\Components\Helper;

class HttpClientHelper
{
    /**
     *
     * 如果不是网络地址，加上服务器图片地址前缀
     * @param string $url
     * @param array $data
     * @param array $header
     * @param integer $timeout
     * @return string
     */
    public static function call($url, $data = null, $header = null, $timeout = 30)
    {
        if (!strstr($url, 'http://') && !strstr($url, 'https://')) {
            $url = 'http://' . $url;
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($curl, CURLOPT_POST, false);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        //执行
        $result = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($http_code == 200) {
            return $result;
        }

        if (curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
            return false;
        }

        curl_close($curl);

        return false;
    }
}
