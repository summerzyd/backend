<?php
class ManagerBannerControllerTest extends TestCase
{
    public function testAffiliate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2415','ad_type'=>'2','products_type'=>'1','mode'=>'3','pageSize'=>'1000','pageNo'=>'1','search'=>'','sort'=>'af_day_limit']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2415','ad_type'=>'2','products_type'=>'1','mode'=>'3','pageSize'=>'1000','pageNo'=>'1','search'=>'',]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'','sort'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'s','sort'=>'-category_id']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'s','sort'=>'app_rank']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'s','sort'=>'media_price']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'s','sort'=>'revenue_price']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'s','sort'=>'status']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'s','sort'=>'updated']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'s','sort'=>'updated_user']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate', ['campaignid'=>'2078','ad_type'=>'0','products_type'=>'0','mode'=>'1','pageSize'=>'1000','pageNo'=>'1','search'=>'s','sort'=>'flow_ratio']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testAffiliateUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        //媒体日限额
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '', 'affiliateid' => '',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'af_day_limit',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'af_day_limit', 'value' => 900, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'af_day_limit', 'value' => 1000, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        //计费价
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'revenue_price', 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'revenue_price', 'value' => 9, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 5045]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'revenue_price', 'value' => 10, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 5045]);
        //媒体价
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'media_price', 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'media_price', 'value' => 0.7, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'media_price', 'value' => 0.8, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        //计费类型
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'revenue_type', 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'revenue_type', 'value' => REVENUE_TYPE_CPD, 'bannerid' => '4228', 'ad_type' => 0]);

        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'revenue_type', 'value' => REVENUE_TYPE_CPC, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        //流量比例
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'flow_ratio', 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'flow_ratio', 'value' => 90, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'flow_ratio', 'value' => 100, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        //安装包
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'attach_id', 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/affiliate_update', ['campaignid' => '2833', 'affiliateid' => '188', 'field' => 'attach_id', 'value' => 2873, 'old_attach_id' => 2873, 'bannerid' => '4228', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
    }

    public function testRevenueType()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/banner/revenue_type', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/revenue_type', ['affiliateid'=>'2','ad_type'=>'1','revenue_type'=>4]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
    public function testAppSearch()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        //媒体日限额
        $resp = $this->actingAs($user)->post('/manager/banner/app_search', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/app_search', ['affiliateid'=>'1','words'=>'a','platform'=>'1']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/banner/app_search', ['affiliateid'=>'65','words'=>'a','platform'=>'1']);
        $resp->seeJson(['res' => 5061]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testAppUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        //媒体日限额
        $resp = $this->actingAs($user)->post('/manager/banner/app_update', ['bannerid'=>1298,'app_id'=>'2444','app_name'=>'千王AAA']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/app_update', ['bannerid'=>1298,'app_id'=>'2444','app_icon'=>'http://appresource.mayitek.com/appResource/files/icon/2442/1450426071860.png','app_name'=>'千王AAA']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testCategory()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/banner/category', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/banner/category', ['affiliateid'=>'1', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testRank()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/banner/rank', []);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/manager/banner/rank', ['affiliateid' => '1', 'platform' => '1']);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/manager/banner/rank', ['affiliateid' => '1', 'platform' => '1', 'ad_type' => 71]);
        $resp->seeJson(['res' => 5054]);
        $resp = $this->actingAs($user)->post('/manager/banner/rank', ['affiliateid' => '1', 'platform' => '1', 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testClientPackage()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/banner/client_package', []);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/manager/banner/client_package', ['campaignid' => '1',]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}