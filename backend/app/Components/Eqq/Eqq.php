<?php
namespace App\Components\Eqq;

use App\Components\Helper\LogHelper;
use App\Components\Config;
use App\Components\Helper\EmailHelper;

class Eqq
{
#接口相关---start	
    private $owner = '';//广点通的账户id
    private $api_url = '';//广点通接口地址
    private $g_tk = '';//广点通防csrf
    private $date = '';//请求广点通某天的数据，格式：YYYY-MM-DD
#接口相关---end

#登录相关---start
    private $pre_login_url = '';
    private $check_login_url = '';
    private $login_url = '';
    private $uin = '';
    private $password = '';
    private $cookie = array();
    private $ip = '';
    private $verifycode = '';
    private $pt_vcode_v1 = '';
    private $ptvfsession = '';
    private $salt = '';
    private $login_sig = '';
    private $cookiejar = '';
#登录相关---end	

#查询adn广告相关---start
    private $eqq_affiliate_id = '';
    private $eqq_zone_id = '';
    private $zone_type = '';
    private $delivery_mode = '';
#查询adn广告相关---end
    private $errorInfo = '';//错误信息
    private $receiver = '';//告警邮件收件人

    /**
     * @todo 初始化参数
     */
    public function __construct($subDate)
    {

        $this->cookiejar = storage_path().'/cookie/e.qq.com.cookie';

        if (!file_exists(storage_path().'/cookie/')) {
            mkdir(storage_path().'/cookie/', 666);
        }

        if (!file_exists($this->cookiejar)) {
            touch($this->cookiejar);
        }

        $this->uin = Config::get('eqq.UIN');//登录QQ号码
        $this->password = Config::get('eqq.PASSWORD');//QQ密码
        $this->pre_login_url = Config::get('eqq.PRE_LOGIN_URL');//登录页面url
        $this->check_login_url = Config::get('eqq.CHECK_LOGIN_URL');//获取验证码、salt、session
        $this->login_url = Config::get('eqq.LOGIN_URL');//提交登陆

        $this->api_url = Config::get('eqq.API_ADDR');//获取广告列表
        $this->date = date('Y-m-d', time()-$subDate*86400);//获取广点通某天的数据
        $this->ip = long2ip(mt_rand(Config::get('eqq.LONG_MIN'), Config::get('eqq.LONG_MAX')));//生成ip

        $this->eqq_affiliate_id = Config::get('eqq.EQQ_AFFILIATE_ID');//广点通媒体ID
        $this->zone_type = Config::get('eqq.ZONE_TYPE');//up_zones.type=3不接受投放
        $this->eqq_zone_id = Config::get('eqq.EQQ_ZONE_ID');//up_zones.zoneid=78腾讯广点通广告位id
        $this->delivery_mode = Config::get('eqq.DELIVERY_MODE');//up_affiliates.mode=2人工投放

        $this->receiver = Config::get('eqq.MAIL_RECEIVER');//告警邮件收件人
    }

    /**
     * @todo 访问http://xui.ptlogin2.qq.com/cgi-bin/xlogin，获取登录所需的cookie
     * @return boolean 成功 or 失败
     */
    public function login()
    {
        $res = $this->http($this->pre_login_url, null, 'GET');
        if (!$res) {
            return false;
        }

        $this->login_sig = $this->getCookie('pt_login_sig');
        return $this->checkLogin();
    }

    /**
     * @todo 获取验证码，加密密码所需的salt和ptvfsession
     * @return boolean 成功 or 失败
     */
    public function checkLogin()
    {
        $data = array();
        $data['regmaster'] = '';
        $data['pt_tea'] = 1;
        $data['pt_vcode'] = 1;
        $data['uin'] = $this->uin;
        $data['appid'] = 15000103;
        $data['js_ver'] = 10148;
        $data['js_type'] = 1;
        $data['login_sig'] = $this->login_sig;
        $data['u1'] = 'http://e.qq.com/index.shtml';
        $data['r'] = '0.5311711819376796';
        $res = $this->http($this->check_login_url, $data, 'GET');
        if (!$res) {
            return false;
        }

        preg_match('/\(([^\)]+)\)/', $res, $matches);

        $matches = explode('\',\'', trim($matches[1], '\''));

        $this->pt_vcode_v1 = $matches[0];
        $this->verifycode = $matches[1];

        //不能直接传递hex2bin后的二进制串，先转成16进制，并且去掉\x，
        //然后在js的$.Encryption.getEncryption前，调用hexchar2bin，
        //转成二进制，作为加密password的参数之一
        $this->salt = str_replace('\\x', '', $matches[2]);

        $this->ptvfsession = $matches[3];

        if ($this->ptvfsession === '') {
            $this->errorInfo = '需要输入验证码，无法自动登录'."\n";
            return false;
        }

        return $this->doLogin();
    }

    /**
     * @todo 提交登陆
     */
    public function doLogin()
    {
        $loginJs = app()->resourcePath() . '/js/login.js';
        //echo 'node '. $loginJs.' '. $this->salt .' '.$this->login_sig.' '
        //.$this->ptvfsession .' '. $this->verifycode.' '. $this->password .' '. $this->uin."\n\n";

        $loginUrl = exec('node '. $loginJs .' '. $this->salt .' '. $this->login_sig .' '
            .$this->ptvfsession .' '. $this->verifycode.' '. $this->password .' '. $this->uin);
        //echo $loginUrl;exit;
        $res = $this->http($loginUrl, null, 'GET', true);
        if (!$res) {
            return false;
        }

        preg_match('/\(([^\)]+)\)/', $res, $matches);
        $matches = explode(',', str_replace('\'', '', $matches[1]));

        if ($matches[4] === '登录成功！') {
            $owner = explode('/', trim($matches[2], '/'));
            $this->owner = array_pop($owner);//'http://e.qq.com/index.shtml?refer=http://e.qq.com/atlas/1004770/';
            return true;
        }

        $this->errorInfo = $matches[4];
        return false;
    }

    /**
     * @todo 获取广告列表
     * @return string 广点通返回的json数据
     */
    public function getAdList($pageNum = 1)
    {
        //mod=%s&act=%s&owner=%s&unicode=true&g_tk=1103566537&status=999&page=1&
        //sdate=%s&edate=%s&searchname=&reportonly=0&pagesize=20&isHours=false&time_rpt=1
        $param = array(
            'mod'=>'report',
            'act'=>'adlist',
            'owner'=>$this->owner,
            'unicode'=>true,
            'g_tk'=>$this->csrfToken(),
            'status'=>999,
            'page'=>$pageNum,
            'pageSize'=>10,
            'sdate'=>$this->getDate(),
            'edate'=>$this->getDate(),
            'searchname'=>'',
            'reportonly'=>0,
            'isHours'=>false,
            'time_rpt'=>1
        );
        return $this->http($this->api_url.http_build_query($param), null, 'GET');
    }

    public function getAdDetail($orderid)
    {
        //http://e.qq.com/ec/api.php?mod=report&act=adgroupdetail&owner=1004770&unicode=true&g_tk=1079189624
        //&orderid=14973428&format=json&page=1&pagesize=10&sdate=2016-01-31&edate=2016-01-31&period=4
        //&searchact=viewcount%7Cctr&dimension=viewcount&time_rpt=1
        $param = array(
            'mod'=>'report',
            'act'=>'adgroupdetail',
            'owner'=>$this->owner,
            'unicode'=>true,
            'g_tk'=>$this->csrfToken(),
            'orderid'=>$orderid,
            'format'=>'json',
            'page'=>1,
            'pageSize'=>999,
            'sdate'=>$this->getDate(),
            'edate'=>$this->getDate(),
            'period'=>4,
            'searchact'=>urlencode('viewcount|ctr'),
            'dimension'=>'viewcount',
            'time_rpt'=>1
        );
        return $this->http($this->api_url.http_build_query($param), null, 'GET');
    }

    /**
     * @todo 从cookiejar文件所设置的cookie，存入$this->cookie，待需要时调用
     * @param string $cname cookie名
     * @return string cookie值
     */
    public function getCookie($cname, $domain = '')
    {
        $lines = file($this->cookiejar);

        foreach ($lines as $line) {
            if ($line[0] != '#' && substr_count($line, "\t") == 6) {
                $tokens = explode("\t", $line);
                if (trim($tokens[5]) === $cname) {
                    if ($domain !== '') {
                        return trim($tokens[0])===$domain ? trim($tokens[6]) : '';
                    } else {
                        return trim($tokens[6]);
                    }
                }

            }
        }
        return '';
    }

    /**
     * @todo 发送http请求
     * @param string $url 请求url
     * @param string $data 请求的参数
     * @param string $method 请求方式
     * @param string $returnHeader 是否输出返回头
     * @return boolean|string 返回结果
     */
    public function http($url, $data = null, $method = 'GET', $returnHeader = false)
    {
        $ch = curl_init();
        if (!empty($data)) {
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_PORT, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                $param = array();
                foreach ($data as $_k => $_v) {
                    $param[] = $_k.'='.$_v;
                }
                $url = strpos($url, '?') === false ? $url.'?'.implode('&', $param) : $url.'&'.implode('&', $param);
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        $returnHeader && curl_setopt($ch, CURLOPT_HEADER, $returnHeader);//返回结果包含返回头

        $header = array(
            'X-FORWARDED-FOR: '.$this->ip, //伪造客户端ip
            'CLIENT-IP: '.$this->ip,//伪造客户端ip
            'Connection: keep-alive',
            'Accept-Language: zh-CN,zh;q=0.8',);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置http请求头
        curl_setopt($ch, CURLOPT_REFERER, $this->pre_login_url);//http请求头referer
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.1; WOW64) '
            . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36'
        );

        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiejar);//存储cookie的目标文件
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiejar);//发送cookie的来源文件

        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        //如果你想CURL报告每一件意外的事情,设置这个选项为一个非零值
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $res = curl_exec($ch);

        if ($res===false) {
            $curl_error = curl_error($ch);
            $curl_info = curl_getinfo($ch);

            LogHelper::error('curl error :'. $curl_error ."\n".'curl_get_info: '. print_r($curl_info, true));
            $this->errorInfo = '<pre>Curl error: ' . curl_error($ch) .'<br/>curl_get_info: '
                . print_r($curl_info, true).'</pre>';
            curl_close($ch);

            return false;
        }

        curl_close($ch);
        return $res;
    }

    /**
     * @todo 返回Eqq类的错误信息
     * @return string
     */
    public function errorInfo()
    {
        return $this->errorInfo;
    }

    /**
     * @todo 返回http://e.qq.com/ec/api.php接口的账户id
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @todo 计算广点通防csrf的token
     * @return boolean
     */
    public function csrfToken()
    {
        if ($this->g_tk != '') {
            return $this->g_tk;
        }

        $str = $this->getCookie('skey', '.qq.com');
        $hash = 5381;
        if (!!$str) {
            for ($i=0,$len=strlen($str); $i<$len; ++$i) {
                $hash += ($hash << 5) + $this->uniord(mb_substr($str, $i, 1, 'utf-8'));
            }
        }

        return $this->g_tk = $hash & 0x7fffffff;
    }

    /**
     * @todo 字符转为unicode编码值
     * @param string $str 需要计算的字符
     * @param string $from_encoding 输入字符的编码
     * @return int
     */
    public function uniord($str, $from_encoding = false)
    {
        $from_encoding=$from_encoding ? $from_encoding : 'UTF-8';

        if (strlen($str)==1) {
            return ord($str);
        }

        $str = mb_convert_encoding($str, 'UCS-4BE', $from_encoding);
        $tmp = unpack('N', $str);
        return $tmp[1];
    }

    /**
     * @todo 返回日期
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @todo 返回邮件收件人邮箱地址
     * @return string
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * @todo 设置邮件收件人
     * @param string $receiver 邮件收件人邮箱地址
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
    }

    /**
     *
     * @param string $subject 邮件标题
     * @param array $data 数据
     * @param string $template 内容模版
     * @param string $to 收件人
     */
    public function sendMail($data, $view = 'emails.jobs.eqq')
    {
        $tos = $this->getReceiver();
        EmailHelper::sendEmail($view, $data, $tos);
    }
    /**
     * @todo 返回投放类型
     * @return int
     */
    public function getDeliveryMode()
    {
        return $this->delivery_mode;
    }

    /**
     * @todo 返回广点通zaiADN的媒体ID
     * @return int
     */
    public function getEqqAffiliateId()
    {
        return $this->eqq_affiliate_id;
    }

    /**
     * @todo 返回广告位类型
     * @return int
     */
    public function getZoneType()
    {
        return $this->zone_type;
    }

    /**
     * @todo 返回广告位
     * @return int
     */
    public function getZoneID()
    {
        return $this->eqq_zone_id;
    }
}
