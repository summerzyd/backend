<?php
return array(
        'UIN' => env('EQQ_UIN', ''),
        'PASSWORD' => env('EQQ_PASSWORD', ''),
        'PRE_LOGIN_URL' => 'http://xui.ptlogin2.qq.com/cgi-bin/xlogin?appid=15000103&s_url=http%3A%2F%2Fe.qq.com%2Findex.shtml&style=20&border_radius=1&target=top&maskOpacity=40&',
        'CHECK_LOGIN_URL' => 'http://check.ptlogin2.qq.com/check',
        'LOGIN_URL' => 'http://ptlogin2.qq.com/login',
        'API_ADDR' => 'http://e.qq.com/ec/api.php?',//正式环境
        'LONG_MAX' => 236257279,//最大ip
        'LONG_MIN' => 236191744,//最小ip
        'MAIL_RECEIVER' => [
            'harper@biddingos.com',
            'fengshenhuang@biddingos.com',
            'simon@iwalnuts.com',
            'hexq@iwalnuts.com',
            'funson@iwalnuts.com',
        ],//告警接收邮箱地址
        'EQQ_ZONE_ID' => 78,//广点通广告位id
        'EQQ_AFFILIATE_ID' => 65,//广点通媒体ID
        'ZONE_TYPE' => 3,//zones.type=3不接受投放
        'DELIVERY_MODE' => 2,//affiliates.mode=2人工投放
    );
