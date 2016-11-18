<?php

class AdvertiserCampaignControllerTest extends TestCase
{
    public $params = [
        'platform' => 1,
        'products_type' => 0,
        'revenue_type' => 1,
        'revenue' => 10,
        'day_limit' => 1000,
        'action' => 2,
        'ad_type' => 0,
        'products_id' => null,
        'link_name' => null,
        'link_url' => null,
        'link_title' => null,
        'appinfos_update_des' => null,
        'star' => 0,
        'total_limit' => 0,
    ];

    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/index');
        $resp->seeJson(['res' => 0,]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE]);
        $resp->seeJson(['res' => 0,]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/index', ['pageNo' => 2, 'pageSize' => 1]);
        $resp->seeJson(['res' => 0,]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'uc']);
        $resp->seeJson(['res' => 0,]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'sort' => 'platform']);
        $resp->seeJson(['res' => 0,]);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'sort' => 'ad_type']);
        $resp->seeJson(['res' => 0,]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'sort' => '-platform']);
        $resp->seeJson(['res' => 0,]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'uc', 'sort' => '-status']);
        $resp->seeJson(['res' => 0,]);
        $this->assertEquals(200, $resp->response->status());
    }

    /*
     * 增加修改推广计划
     */
    public function testCampaignStore()
    {
        $params = $this->params;
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $params['products_type'] = -1;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['products_type'] = PRODUCT_TYPE_DOWNLOAD;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);
        $params['package_file'] = '{"filesize":17504767,"ext":"apk","versionName":"7.2.1","packageName":"com.qiyi.video","versionCode":80721,"app_support_os":"14","path":"/upload/package/20160323/14f37abb77ba5e8fbb79b171986ab761.apk","real_name":"aiqiyi_80721.apk","md5_file":"2e673138fc21ac17291144aea2bfa549","package_id":null}';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5025]);
        $params['appinfos_images'] = [
            '1' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg'],
            '2' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg']
        ];
        $params['products_name'] = 'uc应用下载';
        $params['products_show_name'] = 'uc应用显示';
        $params['appinfos_app_name'] = 'uc应用下载-应用下载';
        $params['products_icon'] = 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['appinfos_description'] = '应用介绍';
        $params['appinfos_profile'] = '一句话介绍';

        $params['ad_type'] = AD_TYPE_BANNER_IMG;
        $params['appinfos_app_name'] = '';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['ad_type'] = AD_TYPE_BANNER_TEXT_LINK;
        $params['appinfos_profile'] = '';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['ad_type'] = AD_TYPE_FEED;
        $params['appinfos_app_name'] = 'uc应用下载-应用下载';
        $params['appinfos_profile'] = '';
        $params['star'] = 1;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['products_type'] = PRODUCT_TYPE_LINK;
        $params['revenue_type'] = REVENUE_TYPE_CPC;
        $params['appinfos_app_name'] = 'uc应用下载-应用下载';
        $params['platform'] = 8;
        $params['star'] = 1;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['ad_type'] = AD_TYPE_FEED;
        $params['link_name'] = 'baidu';
        $params['link_url'] = 'http://www.baidu.com';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['appinfos_profile'] = 'yijuhua';
        $params['link_title'] = 'baidu';
        $params['link_name'] = 'baidu';
        $params['appinfos_images'] = [['url' => 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'ad_spec' => '9']];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5044]);

        $params['appinfos_profile'] = 'yijuhua';
        $params['link_name'] = 'baidu' . str_random(5);
        $params['link_title'] = 'baidu';
        $params['appinfos_images'] = [['url' => 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'ad_spec' => '9']];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5022]);

        $params['ad_type'] = AD_TYPE_APP_STORE;
        $params['appinfos_images'] = [
            '1' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg'],
            '2' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg']
        ];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);
        $params['application_id'] = '1111';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5039]);
        $params['application_id'] = '375380948';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5022]);

        $params['ad_type'] = AD_TYPE_MARKET;
        $params['products_type'] = PRODUCT_TYPE_DOWNLOAD;
        $params['revenue_type'] = REVENUE_TYPE_CPD;
        $params['products_name'] = 'uc应用下载' . str_random(5);
        $params['platform'] = 1;
        $params['appinfos_app_name'] = 'uc应用下载-应用下载';
        $params['appinfos_profile'] = '一句话介绍';
        $params['appinfos_images'] = [
            '1' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg'],
            '2' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg']
        ];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5022]);

        $params['ad_type'] = AD_TYPE_BANNER_IMG;
        $params['appinfos_images'] = [];
        $params['appinfos_app_name'] = 'uc应用下载-应用下载' . str_random(5);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5024]);

        $params['ad_type'] = AD_TYPE_FEED;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5024]);

        $params['keywords'] = [['price_up' => 0, 'keyword' => 'key', 'id' => ''], ['price_up' => 0.5, 'keyword' => 'key1', 'id' => '']];
        $params['ad_type'] = AD_TYPE_MARKET;
        $params['appinfos_images'] = [
            '1' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg'],
            '2' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg']
        ];
        $params['appinfos_app_name'] = 'uc应用下载-应用下载' . str_random(5);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5042]);

//        $params['keywords'] = [['price_up' => 0.5, 'keyword' => 'key', 'id' => ''],['price_up' => 0.5, 'keyword' => 'key1', 'id' => '']];
//        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
//        $resp->seeJson(['res' => 0]);
//        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 修改推广计划
     */
    public function testCampaignUpdate()
    {
        $params = $this->params;
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $params['package_file'] = '{"filesize":17504767,"ext":"apk","versionName":"7.2.1","packageName":"com.qiyi.video","versionCode":80721,"app_support_os":"14","path":"/upload/package/20160323/14f37abb77ba5e8fbb79b171986ab761.apk","real_name":"aiqiyi_80721.apk","md5_file":"2e673138fc21ac17291144aea2bfa549","package_id":null}';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5025]);
        $params['id'] = 418;
        $params['products_id'] = 513;
        $params['appinfos_images'] = [
            '1' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg'],
            '2' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg']
        ];
        $params['products_name'] = 'uc应用下载';
        $params['products_show_name'] = 'uc应用显示';
        $params['appinfos_app_name'] = 'uc应用下载-应用下载';
        $params['products_icon'] = 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['appinfos_description'] = '应用介绍';
        $params['appinfos_profile'] = '一句话介绍';
//        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
//        $resp->seeJson(['res' => 5103]);

        $params['products_type'] = PRODUCT_TYPE_LINK;
        $params['revenue_type'] = REVENUE_TYPE_CPC;
        $params['ad_type'] = AD_TYPE_BANNER_IMG;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['ad_type'] = AD_TYPE_BANNER_TEXT_LINK;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['products_type'] = PRODUCT_TYPE_LINK;
        $params['ad_type'] = AD_TYPE_FEED;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['products_type'] = PRODUCT_TYPE_DOWNLOAD;
        $params['revenue_type'] = REVENUE_TYPE_CPD;
        $params['id'] = 408;
        $params['ad_type'] = AD_TYPE_BANNER_IMG;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5023]);

        $params['ad_type'] = AD_TYPE_BANNER_TEXT_LINK;
        $params['appinfos_profile'] = '';
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5000]);

        $params['appinfos_profile'] = 'yijuhua';
        $params['ad_type'] = AD_TYPE_BANNER_IMG;
        $params['id'] = 403;
        $params['appinfos_app_name'] = 'uc应用下载-应用下载' . str_random(5);
        $params['appinfos_images'] = [];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5024]);

        $params['id'] = 398;
        $params['ad_type'] = AD_TYPE_FEED;
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 5024]);

        $params['id'] = 408;
        $params['ad_type'] = AD_TYPE_MARKET;
        $params['keywords'] = [['price_up' => 0.5, 'keyword' => 'key', 'id' => '156'], ['price_up' => 0.5, 'keyword' => 'key1', 'id' => '157']];
        $params['appinfos_images'] = [
            '1' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg'],
            '2' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg']
        ];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 0]);

        $params['id'] = 577;
        $params['ad_type'] = AD_TYPE_MARKET;
        $params['appinfos_app_name'] = 'uc应用下载-应用下载' . str_random(5);
        $params['appinfos_images'] = [
            '1' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg'],
            '2' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg']
        ];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 0]);

        $params['id'] = 435;
        $params['ad_type'] = AD_TYPE_BANNER_IMG;
        $params['appinfos_images'] = [['url' => 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'ad_spec' => '2']];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 0]);

        $params['id'] = 748;
        $params['ad_type'] = AD_TYPE_BANNER_IMG;
        $params['appinfos_images'] = [['url' => 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'ad_spec' => '2']];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 0]);

        $params['id'] = 1405;
        $params['ad_type'] = AD_TYPE_FEED;
        $params['appinfos_images'] = [['url' => 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'ad_spec' => '9']];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 0]);

        $params['id'] = 403;
        $params['ad_type'] = AD_TYPE_FEED;
        $params['appinfos_app_name'] = 'uc应用下载-应用下载' . str_random(5);
        $params['appinfos_images'] = [['url' => 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'ad_spec' => '9']];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 0]);

        $params['id'] = 408;
        $params['ad_type'] = AD_TYPE_MARKET;
        $params['appinfos_images'] = [
            '1' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg'],
            '2' => ['http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg', 'http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg']
        ];
        $params['keywords'] = [['price_up' => 0.5, 'keyword' => 'key', 'id' => '156']];
        $resp = $this->actingAs($user)->post('/advertiser/campaign/store', $params);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 查看某个推广计划
     */
    public function testCampaignView()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/view');
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/view', ['id' => -1]);
        $resp->seeJson(['res' => 5002]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/view', ['id' => 468]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/view', ['id' => 383]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/view', ['id' => 460]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/view', ['id' => 1516]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/view', ['id' => 571]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/view', ['id' => 6]);
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 推广页表格显示字段
     */
    public function testColumnList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/campaign/column_list');
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 2.8 推广产品列表
     */
    public function testProductList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/product_list', [
            'products_type' => 3
        ]);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/product_list', [
            'products_type' => 0,
            'pageSize' => 10000,
            'pageNo' => 1,
        ]);
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

//    /*
//     * 2.7 修改推广计划字段
//     */
//    public function testUpdate()
//    {
//        $user = factory('App\Models\User')->make([
//            'user_id' => DEFAULT_USER_ID,
//            'default_account_id' => DEFAULT_ACCOUNT_ID,
//            'role_id' => ADVERTISER_ROLE
//        ]);
//
//        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', []);
//        $resp->seeJson([ 'res' => 5000]);
//
//        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', [
//            'id' => 0,
//            'type' => REVENUE_TYPE_CPD,
//            'field' => 'undefined_field',
//            'value' => 'undefined_value'
//        ]);
//        $resp->seeJson([ 'res' => 5000]);
//
////        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', [
////            'id' => 1,
////            'type' => 1,
////            'field' => 'revenue',
////            'value' => '-1'
////        ]);
////        $resp->seeJson([ 'res' => 5041]);
////
////        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', [
////            'id' => 1,
////            'type' => 1,
////            'field' => 'revenue',
////            'value' => '9999999999999999'
////        ]);
////        $resp->seeJson([ 'res' => 5041]);
//
//        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', [
//            'id' => 1,
//            'type' => 1,
//            'field' => 'day_limit',
//            'value' => '9999999999999999'
//        ]);
//        $resp->seeJson([ 'res' => 5041]);
//
//        /*$resp = $this->actingAs($user)->post('/advertiser/campaign/update', [
//            'id' => 1,
//            'type' => 1,
//            'field' => 'revenue',
//            'value' => 4.99
//        ]);
//        $resp->seeJson([ 'res' => 0]);
//
//        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', [
//            'id' => 2,
//            'type' => 1,
//            'field' => 'revenue',
//            'value' => 5.99
//        ]);
//        $resp->seeJson([ 'res' => 0]);*/
//
//        $this->assertEquals(200, $resp->response->status());
//    }

    /*
     * 2.6.3 获取Banner广告尺寸 advertiser/campaign/banner_demand
     */
    public function testDemand()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/campaign/demand');
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    /*
     * 2.6.2 获取出价/日预算限制 advertiser/campaign/money_limit
     */
    public function testMoneyLimit()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/campaign/money_limit');
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testRevenueType()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/campaign/revenue_type');
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testAppStoreView()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/campaign/app_store_view');
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->get('/advertiser/campaign/app_store_view?id=111');
        $resp->seeJson(['res' => 5039]);
        $resp = $this->actingAs($user)->get('/advertiser/campaign/app_store_view?id=836500024');
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testProductExist()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/product_exist', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/product_exist', ['name' => 'UC浏览器1', 'id' => 1]);
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 测试应用列表
     */
    public function testAppList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_SELF_USER_ID,
                'default_account_id' => DEFAULT_SELF_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/app_list', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/app_list', ['wd' => 'w']);
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 查看自营广告主
     */
    public function testSelfView()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_SELF_USER_ID,
                'default_account_id' => DEFAULT_SELF_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/self_view', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/self_view', ['campaignid' => 6024]);
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 广告主暂停和继续投放
     */
    public function testUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_SELF_USER_ID,
                'default_account_id' => DEFAULT_SELF_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', ['campaignid' => 392,'status'=>1]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/advertiser/campaign/update', ['campaignid' => 392,'status'=>0]);
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }
}
