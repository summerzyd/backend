<?php

class AdvertiserStatControllerTest extends TestCase {

    /*
     * 报表需求
     */
    public function testGetCampaigns()
    {
        $user = factory('App\Models\User')->make(
            ['user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/index');
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/index?period_start=2015-2-10&period_end=2016-2-17&span=2&zone_offset=-8&type=1');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/index?period_start=2015-2-10&period_end=2015-2-10&span=2&zone_offset=-8&type=1');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/index?period_start=2015-2-10&period_end=2016-2-17&span=3&zone_offset=-8&type=1');
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testReport()
    {
        $user = factory('App\Models\User')->make(
            [
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/report');
        $resp->seeJson(['res' => 0]);

    }
    /**
     * excel下载
     * excel导出需要保存才能返回，单元测试暂时无法操作。
     */
//    public function testCampaignExcel()
//    {
//        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID]);
//
//        $resp = $this->actingAs($user)->get('/advertiser/stat/campaign_excel');
//        $resp->seeJson(['res' => 5000]);
//
//        $resp = $this->actingAs($user)->get('/advertiser/stat/campaign_excel?period_start=2016-2-10&period_end=2016-2-17&zoneOffset=-8&type=0');
//        //$resp = $this->actingAs($user)->get('/advertiser/stat/campaign_excel?period_start=2016-2-10&period_end=2016-2-17&zoneOffset=-8&type=1');
//        $this->assertEquals(200, $resp->response->getContent());
//    }
    public function testSelfIndex()
    {
        $user = factory('App\Models\User')->make(
            [
                'user_id' => DEFAULT_SELF_USER_ID,
                'default_account_id' => DEFAULT_SELF_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/self_index');
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/self_index?period_start=2016-08-18&period_end=2016-09-08&span=2&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/self_index?period_start=2015-09-01&period_end=2016-08-31&span=3&zone_offset=-8');
        $resp->seeJson(['res' => 0]);
    }
    public function testSelfReport()
    {
        $user = factory('App\Models\User')->make(
            [
                'user_id' => DEFAULT_SELF_USER_ID,
                'default_account_id' => DEFAULT_SELF_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/stat/self_report');
        $resp->seeJson(['res' => 0]);
    }
}
 