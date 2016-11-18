<?php
namespace App\Components\Sohu;

class SohuEncrypt
{
    const CIPHER = MCRYPT_RIJNDAEL_128;
    const MODE = MCRYPT_MODE_ECB;
    
    const SIGNATURE_METHOD = 'HMAC-SHA1';

    private $normalized;
    private $url;
    private $params;
    
    private $key;
    private $secret;

    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
        
        $this->params = array(
            'auth_consumer_key' => $this->key,
            'auth_nonce' => md5(time() . rand(1, 99)),
            'auth_signature_method' => self::SIGNATURE_METHOD,
            'auth_timestamp' => time()
        );
    }
    
    /**
     * 解密
     * @param type $key
     * @param type $str
     * @return type
     */
    public static function decode($key, $str)
    {
        $key = self::hex2String($key);
        $str = self::hex2String($str);
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER, self::MODE), MCRYPT_RAND);
        $value = mcrypt_decrypt(self::CIPHER, $key, $str, self::MODE, $iv);
        $len = intval(substr($value, strlen($value)-16));
        return substr($value, 0, $len);
    }
    
    public static function hex2String($hex)
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }
    
    public function setParams($param)
    {
        if (is_array($param)) {
            $this->params = array_merge($this->params, $param);
        }
        $normalized = array();
        
        ksort($this->params, SORT_STRING);
        foreach ($this->params as $key => &$value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if ($key != 'auth_signature') {
                //文件上传不参与加密
                if (substr($value, 0, 1) != '@') {
                    $normalized[] = urlencode($key) . '=' . urlencode($value);
                }
            }
        }
        $this->normalized = implode('&', $normalized);
        return $this;
    }

    public function setUrl($url)
    {
        $this->url = strtolower($url);
        return $this;
    }

    private function getBaseString($method)
    {
        $base_string = strtoupper($method) . '&';
        $base_string .= rawurlencode($this->url) . '&';
        $base_string .= rawurlencode($this->normalized);
        return $base_string;
    }

    public function get()
    {
        if ($this->normalized) {
            $signature = $this->generateSignature('GET');
            return $this->curl($this->url . '?' . $this->normalized . '&auth_signature=' . $signature);
        }
    }

    public function post()
    {
        if ($this->normalized) {
            $data = $this->params;
            $data['auth_signature'] = $this->generateSignature('POST');
            return $this->curl($this->url, 'POST', $data);
        }
    }

    public function generateSignature($method)
    {
        return base64_encode(hash_hmac('sha1', $this->getBaseString($method), $this->secret, true));
    }
    
    public function curl($url, $method = 'GET', $post_data = null, $timeout = 40)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
    
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
            if ($post_data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
    
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);
        return $result;
    }
}
