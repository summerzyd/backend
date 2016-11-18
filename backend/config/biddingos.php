<?php

return [

    'selfDefaultInit' => [
        //0表示CPD
        '1' => [
            'revenue_min' => 1,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 200,
            'day_limit_max' => 1000000,
            'day_limit_step' => 50,
            'total_limit_min' => 1000,
            'total_limit_max' => 9999999,
            'total_limit_step' => 500,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 1,
        ],
        //0表示CPD
        '4' => [
            'revenue_min' => 1,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 200,
            'day_limit_max' => 1000000,
            'day_limit_step' => 1,
            'total_limit_min' => 200,
            'total_limit_max' => 9999999,
            'total_limit_step' => 1,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 2,
        ],
        '32' => [
            'revenue_min' => 0,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 0,
            'day_limit_max' => 1000000,
            'day_limit_step' => 1,
            'total_limit_min' => 0,
            'total_limit_max' => 9999999,
            'total_limit_step' => 1,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 2,
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | 前端页面限制默认值
    |--------------------------------------------------------------------------
    */
    'jsDefaultInit' => [
        //0表示CPD
        '1' => [
            'revenue_min' => 0.5,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 200,
            'day_limit_max' => 1000000,
            'day_limit_step' => 1,
            'total_limit_min' => 200,
            'total_limit_max' => 9999999,
            'total_limit_step' => 1,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 1,
        ],
        //表示CPC
        '2' =>[
            'revenue_min' => 0.01,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 200,
            'day_limit_max' => 1000000,
            'day_limit_step' => 1,
            'total_limit_min' => 200,
            'total_limit_max' => 9999999,
            'total_limit_step' => 1,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 2,
        ],
        '4' => [
            'revenue_min' => 0.5,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 200,
            'day_limit_max' => 1000000,
            'day_limit_step' => 1,
            'total_limit_min' => 200,
            'total_limit_max' => 9999999,
            'total_limit_step' => 1,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 1,
        ],
        '8' => [
            'revenue_min' => 1,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 200,
            'day_limit_max' => 1000000,
            'day_limit_step' => 1,
            'total_limit_min' => 200,
            'total_limit_max' => 9999999,
            'total_limit_step' => 1,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 1,
        ],
        //0表示CPD
        '16' => [
            'revenue_min' => 1,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 200,
            'day_limit_max' => 1000000,
            'day_limit_step' => 1,
            'total_limit_min' => 200,
            'total_limit_max' => 9999999,
            'total_limit_step' => 1,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 2,
        ],
        '32' => [
            'revenue_min' => 0,
            'revenue_max' => 100,
            'revenue_step' => 0.1,
            'price_up_min' => 0.1,
            'price_up_max' => 9999999,
            'day_limit_min' => 0,
            'day_limit_max' => 1000000,
            'day_limit_step' => 1,
            'total_limit_min' => 0,
            'total_limit_max' => 9999999,
            'total_limit_step' => 1,
            'key_min' => 0.1,
            'key_max' => 100,
            'decimal' => 2,
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | 优酷ADX错误信息
    |--------------------------------------------------------------------------
     */
    'youku_fail_reason' => [
        101 => '文件加载失败',
        102 => '不支持的文件格式,目前支持的文件格式：jpg,gif,png,swf,flv,x',
        103 => '根据素材url获取不到尺寸信息',
        104 => '执行插入过程中发生了错误',
        105 => '素材所属的广告主为空',
        106 => '素材生效时间为空或者不能解析',
        107 => '素材失效时间为空或者不能解析',
        108 => '内部错误',
        109 => '素材尺寸不符合广告位要求',
        110 => '素材时长不符合广告位要求',
        111 => '上传素材总数超过限制',
        112 => '获取素材时长时错误',
        113 => '广告主名称存在错误',
        114 => '广告主品牌存在错误',
        115 => '广告主第一统计行业存在错误',
        116 => '广告主第二统计行业存在错误',
        117 => '广告主资质存在错误',
        118 => '资质名称存在错误',
        119 => '资质操作类型错误',
        120 => '资质文件的MD5错误',
        121 => '资质文件URL存在错误',
    ],
    'iqiyi_fail_reasion_material' => [
        0 => '上传成功',
        1001 => '认证错误(token错误)',
        4001 => '参数错误',
        5001 => '服务端错误',
        5002 => '上传素材数量超过限制(每日上传限制：200，并发上传限制：5)',
        5003 => '应用请求超过限制',
        9999 => '未知',
    ],
    'iqiyi_fail_reasion_client' => [
        0 => '上传成功',
        1001 => '认证错误(token错误)',
        4001 => '参数错误',
        5001 => '服务端错误',
        5002 => '用户同时上传超过限制',
        5003 => '应用请求超过限制',
        9999 => '未知',
    ],

    /*
    |--------------------------------------------------------------------------
    | 插屏广告尺寸和Banner广告尺寸
    |--------------------------------------------------------------------------
     */
    'ad_spec' => [
        '0' => [
            '1'     => '720*1280',
            '2'     => '480*800',
        ],
        '1' => [
            '0'     => '216*36',        //coolpad   6
            /*'1' => '250*300',*/
            '2'     => '300*50',        //coolpad,youku,youxiao   6
            /*'3'=>'300*250',*/
            '4'     => '320*50',        //coolpad   6.4
            '5'     => '468*60',        //coolpad   7.8
            '6'     => '640*100',       //coolpad,youku     6.40
            '9'     => '640*120',       //coolpad   5.33
            '10'    => '640*260',       //coolpad   2.46
            '7'     => '728*90',        //coolpad   8.09
            '8'     => '1280*200',      //coolpad   6.4
            '11'    => '640*1200',      //coolpad   0.53
            '12'   => '180*150',        //iqiyi
            '13'   => '480*70',         //iqiyi
            '14'   => '480*80',         //huiwang
            '15'   => '640*200',         //yiqiying
            '16'   => '640*150',         //yiqiying
        ],
        '2' => [
            '9'     => '1000*560',
        ],
        //半屏
        '3' => [
            '0' => '600*500',       //coolpad,youku,youxiao,iqiyi
            '1' => '500*600',       //coolpad
            '2' => '300*250',       //youku
            //'3' => '250*300',
        ],
        //全屏
        '4' => [
            //'0' => '640*1136',
            //'1' => '1080*1920',
            '2' => '640*960',       //coolpad  600x900
            '3' => '720*1280',      //coolpad
            //'4' => '480*800',
            //'5' => '540*960',
            //'6' => '320*480',
            '7' => '960*640',       //coolpad
            '8' => '1280*720',      //coolpad, iqiyi
            '9' => '640*360',      //sohu
        ],
        //全屏
        '71' => [
            '1' => '640*1136',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | 不用确定完成传包的过程，直接是投放中, products 表的 type
    |--------------------------------------------------------------------------
    */
    'withoutPackage' => [1],
    /*
    |--------------------------------------------------------------------------
    | 没有分类限制
    |--------------------------------------------------------------------------
    */
    'withCategory' => [0, 71],
    /*
    |--------------------------------------------------------------------------
    | 左侧菜单
    |--------------------------------------------------------------------------
    */
    'nav_front_base' => env('NAV_FRONT_BASE', '/bos-front/web'),
    'nav' => [
        'advertiser' => [
            [
                'url'   => '/advertiser/index.html',
                'icon'  => 'fa-pie-chart',
                'label' => '推广概览',
            ],
            [
                'url'   => '/advertiser/campaign/index.html',
                'icon'  => 'fa-bullhorn',
                'label' => '我的推广',
                'operation' => 'advertiser-campaign',
            ],
            [
                'url'   => '/advertiser/stat/index.html',
                'icon'  => 'fa-bar-chart',
                'label' => '统计报表',
                'operation' => 'advertiser-stat',
            ],
            [
                'url'   => '/advertiser/balance/index.html',
                'icon'  => 'fa-rmb',
                'label' => '账户明细',
                'operation' => 'advertiser-balance',
            ],
            [
                'url'   => '/advertiser/account/index.html',
                'icon'  => 'fa-user-plus',
                'label' => '帐号管理',
            ],
            [
                'url' => '#sales',
                'icon' => 'fa-phone',
                'label' => '联系销售顾问',
            ],
        ],
        'trafficker' => [
            [
                'url'   => '/trafficker/index.html',
                'icon'  => 'fa-pie-chart',
                'label' => '运营概览',
            ],
            [
                'url'   => '/trafficker/campaign/index.html',
                'icon'  => 'fa-bullhorn',
                'label' => '广告管理',
            ],
            [
                'url'   => '/trafficker/zone/index.html',
                'icon'  => 'fa-th-large',
                'label' => '广告位管理',
            ],
            [
                'url'   => '/trafficker/stat/index.html',
                'icon'  => 'fa-bar-chart',
                'label' => '统计报表',
            ],
            [
                'url'   => '/trafficker/sdk/index.html',
                'icon'  => 'fa-share-alt',
                'label' => 'SDK下载',
            ],
            [
                'url' => '#sales',
                'icon' => 'fa-phone',
                'label' => '联系销售顾问',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => realpath(storage_path('framework/views')),
    'trf_chart_fields' => [
        [
            'field'   => 'sum_views',
            'name'  => '展示量',
            'chart_type' => 'column',
            'format'   => 'n0',
            'default'   => 0,
            'field_type' => 'basics',
        ],
        [
            'field'   => 'sum_clicks',
            'name'  => '下载量',
            'chart_type' => 'column',
            'format'   => 'n0',
            'default'   => 1,
            'field_type' => 'basics',
        ],
        [
            'field'   => 'sum_revenue',
            'name'  => '收入',
            'chart_type' => 'column',
            'format'   => 'n2',
            'default'   => 1,
            'field_type' => 'basics',
        ],
        [
            'field'   => 'ctr',
            'name'  => '下载转化率',
            'chart_type' => 'line',
            'format'   => 'p2',
            'default'   => 0,
            'field_type' => 'calculation',
            'arithmetic' => ["sum_clicks", "/", "sum_views"],
        ],
        [
            'field'   => 'media_cpd',
            'name'  => '下载均价',
            'chart_type' => 'line',
            'format'   => 'n2',
            'default'   => 0,
            'field_type' => 'calculation',
            'arithmetic' => ["sum_revenue", "/", "sum_clicks"],
        ],

    ],

    'trf_cpd_zone_fields' => [
        [
            'field'   => 'zone_name',
            'name'  => '广告位',
            'menu' => 0,
            'summary'   => 0,
            'width'     => 200,
        ],
        [
            'field'   => 'zone_type_label',
            'name'  => '广告位类别',
            'menu' => 0,
            'summary'   => 0,
        ],
        [
            'field'   => 'platform',
            'name'  => '所属平台',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'product_name',
            'name'  => '推广产品',
            'menu' => 1,
            'summary'   => 0,
            'search'   => 1,
        ],
        [
            'field'   => 'product_type',
            'name'  => '推广类型',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'ad_name',
            'name'  => '广告名称',
            'menu' => 1,
            'summary'   => 0,
            'search'   => 1,
        ],
        [
            'field'   => 'ad_type',
            'name'  => '广告类型',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'sum_views',
            'name'  => '展示量',
            'format' => 'n0',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'sum_clicks',
            'name'  => '下载量',
            'format'   => 'n0',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'ctr',
            'name'  => '下载转化率%',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'sum_revenue',
            'name'  => '收入',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'media_cpd',
            'name'  => '平均单价',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'ecpm',
            'name'  => 'eCPM',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 0,
        ],
    ],
    'trf_cpd_cam_fields' => [
        [
            'field'   => 'product_name',
            'name'  => '推广产品',
            'menu' => 0,
            'summary'   => 0,
            'width'     => 200,
            'search'   => 1,
        ],
        [
            'field'   => 'product_type',
            'name'  => '推广类型',
            'menu' => 0,
            'summary'   => 0,
        ],
        [
            'field'   => 'ad_name',
            'name'  => '广告名称',
            'menu' => 1,
            'summary'   => 0,
            'search'   => 1,
        ],
        [
            'field'   => 'ad_type',
            'name'  => '广告类型',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'zone_name',
            'name'  => '广告位',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'zone_type_label',
            'name'  => '广告位类别',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'platform',
            'name'  => '所属平台',
            'menu' => 1,
            'summary'   => 0,
        ],

        [
            'field'   => 'sum_views',
            'name'  => '展示量',
            'format' => 'n0',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'sum_clicks',
            'name'  => '下载量',
            'format'   => 'n0',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'ctr',
            'name'  => '下载转化率%',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'sum_revenue',
            'name'  => '收入',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'media_cpd',
            'name'  => '平均单价',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'ecpm',
            'name'  => 'eCPM',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 0,
        ],
    ],
    'broker_chart_fields' => [
        [
            'field'   => 'sum_views',
            'name'  => '展示量',
            'chart_type' => 'column',
            'format'   => 'n0',
            'default'   => 0,
            'field_type' => 'basics',
        ],
        [
            'field'   => 'sum_clicks',
            'name'  => '下载量',
            'chart_type' => 'column',
            'format'   => 'n0',
            'default'   => 1,
            'field_type' => 'basics',
        ],
        [
            'field'   => 'sum_revenue',
            'name'  => '支出',
            'chart_type' => 'column',
            'format'   => 'n2',
            'default'   => 1,
            'field_type' => 'basics',
        ],
        [
            'field'   => 'ctr',
            'name'  => '下载转化率',
            'chart_type' => 'line',
            'format'   => 'p2',
            'default'   => 0,
            'field_type' => 'calculation',
            'arithmetic' => ["sum_clicks", "/", "sum_views"],
        ],
        [
            'field'   => 'cpd',
            'name'  => '下载均价',
            'chart_type' => 'line',
            'format'   => 'n2',
            'default'   => 0,
            'field_type' => 'calculation',
            'arithmetic' => ["sum_revenue", "/", "sum_clicks"],
        ],

    ],
    'bro_table_column_field' => [
        [
            'field'   => 'client_name',
            'name'  => '广告主',
            'menu' => 0,
            'summary'   => 0,
            'width'     => 200,
        ],
        [
            'field'   => 'product_name',
            'name'  => '推广名称',
            'menu' => 0,
            'summary'   => 0,
            'search'   => 1,
        ],
        [
            'field'   => 'ad_name',
            'name'  => '广告名称',
            'menu' => 1,
            'summary'   => 0,
            'search'   => 1,
        ],
        [
            'field'   => 'ad_type',
            'name'  => '广告类型',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'platform',
            'name'  => '所属平台',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'channel',
            'name'  => '渠道',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'sum_views',
            'name'  => '展示量',
            'format' => 'n0',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'sum_clicks',
            'name'  => '下载量',
            'format'   => 'n0',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'ctr',
            'name'  => '下载转化率%',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 0,
        ],
        [
            'field'   => 'sum_revenue',
            'name'  => '支出',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 1,
        ],
        [
            'field'   => 'cpd',
            'name'  => '下载均价',
            'format'   => 'n2',
            'menu' => 1,
            'summary'   => 0,
        ],
    ],

    'job' => [
        //account_id,多个以|分隔如174|175
        'jobAdStartSuspendEmailAccount' => env('BIDDINGOS_JOB_AD_START_SUSPEND_EMAIL_ACCOUNT', '174|175'),
        'jobJobDeliveryRepairLog' => env('BIDDINGOS_JOB_DELIVERY_REPAIR_LOG', [
            'hexq@iwalnuts.com',
            'simon@iwalnuts.com',
            'funson@iwalnuts.com',
        ]),
        'jobSyncPMPCampaigns' => env('BIDDINGOS_JOB_SYNC_PMP_CAMPAIGNS', [
            'fengshenhuang@biddingos.com',
        ]),
    ],

    'adx' => [
        'youku' => [
            'afid'  => env('YOUKU_ADX_AFID',  ''),
            'dspid' => env('YOUKU_ADX_DSPID', ''),
            'token' => env('YOUKU_ADX_TOKEN', ''),
            'prefix_url' => env('YOUKU_ADX_URL_PREFIX', ''),

            'clientid' => env('YOUKU_VIDEO_UPLOAD_CLIENT_ID', 'cf7aa61114b3e323'),
            'client_secret' => env('YOUKU_VIDEO_UPLOAD_CLIENT_SECRET', 'c2f3132ca66f528b80d33ffda5fd03c9'),
            'access_token' => env('YOUKU_VIDEO_UPLOAD_ACCESS_TOKEN', '6ea3f0b706eb60213c83d8ea6424d39d'),
            'refresh_token' => env('YOUKU_VIDEO_UPLOAD_REFRESH_TOKEN', '695131022c7270edc0fcd367b9f0377a'),
        ],
        'iqiyi' => [
            'afid'  => env('IQIYI_ADX_AFID',  ''),
            'token' => env('IQIYI_ADX_TOKEN', ''),
            'advertiser_upload' => env('IQIYI_ADX_URL_ADVERTISER_UPLOAD', ''),
            'advertiser_status_single' => env('IQIYI_ADX_URL_ADVERTISER_STATUS_SINGLE', ''),
            'advertiser_status_multi' => env('IQIYI_ADX_URL_ADVERTISER_STATUS_MULTI', ''),
            'ad_upload' => env('IQIYI_ADX_URL_AD_UPLOAD', ''),
            'ad_status' => env('IQIYI_ADX_URL_AD_STATUS', ''),
        ],
        'chinamobile' => [
            'afid'  => env('CHINAMOBILE_ADX_AFID',  ''),
            'dspid'  => env('CHINAMOBILE_ADX_DSPID',  ''),
            'token' => env('CHINAMOBILE_ADX_TOKEN', ''),
            'advertiser_upload' => env('CHINAMOBILE_ADX_URL_ADVERTISER_UPLOAD', ''),
            'advertiser_status' => env('CHINAMOBILE_ADX_URL_ADVERTISER_STATUS', ''),
            'ad_upload' => env('CHINAMOBILE_ADX_URL_AD_UPLOAD', ''),
            'ad_status' => env('CHINAMOBILE_ADX_URL_AD_STATUS', ''),
        ],
        'letv' => [
            'dspid'  => env('LETV_ADX_DSPID',  ''),
            'token' => env('LETV_ADX_TOKEN', ''),
            'ad_upload' => env('LETV_ADX_URL_AD_UPLOAD', ''),
            'ad_status' => env('LETV_ADX_URL_AD_STATUS', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 默认权限
    |--------------------------------------------------------------------------
    */
    'default_manager_role' => 4,
    'default_broker_role' => 5,
    'default_trafficker_role' => 6,
    'default_client_role' => 7,
    // API KEY
    'apiSecretKey' => env('BIDDINGOS_API_SECRET_KEY', 'WKWf6MR5PDr29b'),
    //用于表示前端页面对应的支付金额
    'recharges' => [
        '1' => '50000',
        '2' => '100000',
        '3' => '200000',
    ],
    //支付宝配置
    'alipayConfig' => [
        'seller_email' => 'zhifubao@biddingos.com', // 支付宝账号
        'partner' => '2088911144756439', //合作身份者id，以2088开头的16位纯数字
        'key' => '1geo9k0d1n75s4p96roigjcrg4uzdllo', //安全检验码，以数字和字母组成的32位字符
        'sign_type'     => 'MD5',
        'input_charset' => 'utf-8',
        'cacert'        => 'cacert.pem',
        'transport'     => 'http'
    ],
    //支付宝IP白名单
    'ipTables' => [
        'bos_api_access_ips' => '',
        'bos_alipay_access_ips' => [
            '110.75.225.0/24',
            '110.75.242.0/24',
            '121.0.26.0/23',
            '110.75.128.0/19'
        ]
    ],
    
    'appStorePrefix' => 'https://itunes.apple.com/lookup?id=',
    'appStoreCnPrefix' => 'https://itunes.apple.com/cn/lookup?id=',
    //数据异常邮件告警配置
    'dataException' => [
        'uniformRate' => 0.1,
        'warningRate' => 0.3,
        'conversionLimit' => 100,
        'compareRate'=> 0.2
    ],
    'sogouAffiliateId' => env('SOGOU_AFFILIATE_ID', 86), //sogou媒体商id
    'sogouZoneId' => env('SOGOU_ZONE_ID', 783), //sogou用于统计的广告位id

    'add_impression_zone_id' => env('ADD_IMPRESSION_ZONE_ID', '783'),

    //广告主余额发送人员的邮件地址
    'clientBalanceUser' => [
        'hexq@iwalnuts.com',
        'funson@iwalnuts.com',
        'simon@iwalnuts.com',
        'fengshenhuang@biddingos.com',
    ],
    'manualWithoutCheckAffiliate' => ['A-AD'], //录入的只有广告主数据，不用检查媒体账号是否存在
    'manualWithAF' => ['A2D-AF','A2C-AF'],
    'manualCPACampaignAffiliate' => ['A2A', 'A2D-AF', 'A2C-AF', 'CPA'],
    'manualAffiliate' => ['D2D', 'C2C', 'T2T'], //限制为必须为人工投放的媒体
    'preData' => [
        'A2A' => ['广告主（ID）', '广告名称', '媒体商（全称）', '日期', 'CPA量', '广告主消耗', '媒体支出'],
        'A-AD' => ['广告主（ID）', '广告名称', '日期', 'CPA量', '广告主消耗'],
        'A2D-AF' => ['广告主（ID）', '广告名称', '媒体商（全称）', '日期', '下载量', '媒体支出'],
        'A2C-AF' => ['广告主（ID）', '广告名称', '媒体商（全称）', '日期', '点击量', '媒体支出'],
        'C2C' => ['广告主（ID）', '广告名称', '媒体商（全称）', '日期', '点击量', '广告主消耗', '媒体支出'],
        'D2D' => ['广告主（ID）', '广告名称', '媒体商（全称）', '日期', '下载量', '广告主消耗', '媒体支出'],
        'T2T' => ['广告主（ID）', '广告名称', '媒体商（全称）', '日期', '广告主消耗', '媒体支出'],
        'S2S' => ['广告主（ID）', '广告名称', '媒体商（全称）', '日期', '广告主消耗', '媒体支出'],
    ],
    'preAffiliateData' => [
        'A2A' => ['广告主（ID）', '广告名称', '日期', '广告主消耗'],
        'S2S' => ['广告主（ID）', '广告名称', '日期', '广告主消耗', 'CPA量',],
    ],
    'manualD2D' => 'D2D',
    'manualC2C' => 'C2C',
    //Yeahmobi 配置
    'ym' => [
        'api_id' => env('YM_API_ID', ''),//账号
        'api_token' => env('YM_API_TOKEN', ''),//密码
        'refresh_time' => '08:05:00',//每天重置时间
    ],
    'daily_mail_address' => env('DAILY_MAIL_ADDRESS', ''),
    'monitor_banner_relation' => env('MONITOR_BANNER_RELATION', ''),
    'monitor_download_url' => env('MONITOR_DOWNLOAD_URL', ''),
    'monitor_pctr' => env('MONITOR_PCTR', ''),
    'attach_banner_relation' => env('ATTACH_BANNER_RELATION', ''), 
	
    //sohu 配置
    'sohu' => [
        'afid' => env('SOHU_AFID', 102),
        'auth_consumer_key' => env('SOHU_AUTH_CONSUMER_KEY', ''),
        'auth_consumer_secret' => env('SOHU_AUTH_CONSUMER_SECRET', ''),
        'price_key' => env('SOHU_PRICE_KEY', ''),
        'v3_downloadcgi_uri' => env('V3_DOWNLOADCGI_URI', ''),
        'v3_downloadendcgi_uri' => env('V3_DOWNLOADENDCGI_URI', ''),
        'v3_clickcgi_uri' => env('V3_CLICKCGI_URI', ''),
        'v3_impressioncgi_uri' => env('V3_IMPRESSIONCGI_URI', ''),
    ],
    'letv' => env('LETV', ''),
    'ad_list' => [['ad_type' => 1],['ad_type' => 2],['ad_type' => 3],['ad_type' => 91]],
    'ios91' => env('IOS91', ''),
    'material_size' => [
        env('YOUKU_ADX_AFID') =>[
            '3' => "页面图片(300*100)",
            '50' => "页面图片(300*100)",
            '4' => "页面图片(610*100)",
            '6' => "页面图片(300*50)",
            '75' => "页面图片(300*50)",
            '17' => "页面图片(300*250)",
            '5' =>  "页面图片(610*100)",
            '47' => "页面图片(610*100)",
            '23' => "页面图片banner(300*50)",
            '74' => "页面图片banner(300*50)",
            '10' => "页面图片banner(300*250)",
            '12' => "页面图片banner(300*100)",
            '95' => "页面图片(640*100)",
            '22' => "暂停广告(600*500)",
            '76' => "暂停广告(600*500)",
            '7' =>  "暂停广告(400*300)",
            '40' => "角标(640*90)",
            '85' => "角标(337*110)",
            '41' => "移动全屏(600*500)",
            '77' => "移动全屏(600*500)",
            '31' => "贴片广告(640*480)",
            '32' => "贴片广告(640*480)",
            '25' => "贴片广告(640*480)",
            '26' => "贴片广告(640*480)",
            '33' => "贴片广告(640*480)",
            '19' => "贴片广告(640*480)",
            '34' => "贴片广告(640*480)",
            '35' => "贴片广告(640*480)",
            '27' => "贴片广告(640*480)",
            '28' => "贴片广告(640*480)",
            '36' => "贴片广告(640*480)",
            '20' => "贴片广告(640*480)",
            '37' => "贴片广告(640*480)",
            '38' => "贴片广告(640*480)",
            '29' => "贴片广告(640*480)",
            '30' => "贴片广告(640*480)",
            '39' => "贴片广告(640*480)",
            '21' => "贴片广告(640*480)",
            '54' => "贴片广告(640*480)",
            '55' => "贴片广告(640*480)",
            '56' => "贴片广告(640*480)",
            '78' => "贴片广告(640*480)",
            '81' => "贴片广告(640*480)",
            '79' => "贴片广告(640*480)",
            '82' => "贴片广告(640*480)",
            '80' => "贴片广告(640*480)",
            '83' => "贴片广告(640*480)",
            '57' => "贴片广告(640*480)",
            '58' => "贴片广告(640*480)",
            '59' => "贴片广告(640*480)",
            '86' => "贴片广告(640*480)",
            '87' => "贴片广告(640*480)",
            '88' => "贴片广告(640*480)",
        ]
    ],
    'impression_limit' => env('IMPRESSION_LIMIT', ''),
    'appid_url'=> env('APPLE_ID_URL', ''),
    'affiliate_download_complete' => explode(',', env('AFFILIATE_DOWNLOAD_COMPLETE', '')),
];
