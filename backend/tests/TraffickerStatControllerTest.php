<?php

class TraffickerStatControllerTest extends TestCase
{
    /**
     * 获取头部导航栏菜单
     */
    public function testMenu()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/menu');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取图表，报表显示字段
     */
    public function testColumnList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/column_list?revenue_type=10&item_num=1');
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/column_list?revenue_type=2&item_num=1');
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/column_list?revenue_type=10&item_num=2');
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/column_list?revenue_type=2&item_num=2');
        $resp->seeJson([ 'res' => 0]);

    }
    /**
     * 获取广告位数据
     */

    public function testZone()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/zone?revenue_type=10&period_start=2016-03-12&period_end=2016-03-18&span=2&zone_offset=-8');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/zone?revenue_type=2&period_start=2016-03-12&period_end=2016-03-18&span=2&zone_offset=-8');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
    /**
     *  获取广告数据
     */
    public function testClient()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get(
            '/trafficker/stat/client?revenue_type=10&period_start=2016-03-12&period_end=2016-03-18&span=2&zone_offset=-8');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get(
                '/trafficker/stat/client?revenue_type=2&period_start=2016-03-12&period_end=2016-03-18&span=2&zone_offset=-8');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
    /**
     * 导出报表
     */
//     public function testCampaignExcel()
//     {
//         $user = factory('App\Models\User')
//             ->make([
//                 'user_id' => DEFAULT_TRAFFICKER_USER_ID,
//                 'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID
//             ]);
//         $resp = $this->actingAs($user)->get(
//             '/trafficker/stat/campaign_excel?period_start=2016-03-12&period_end=2016-03-18&zoneOffset=-8&revenue_type=10&item_num=2');
//         $resp->seeJson([ 'res' => 0]);
//         $this->assertEquals(200, $resp->response->status());
//     }

    /**
     * 获取媒体商30天收入概览
     */

    public function testReport()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/report');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
    /**
     * 获取媒体商概览广告位收入
     */
    public function testZoneReport()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/zone_report?date_type=5');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/zone_report?date_type=4');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/zone_report?date_type=3');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/zone_report?date_type=2');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/zone_report?date_type=1');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
    /**
     * 获取媒体商概览广告主消耗
     */
    public function testClientReport()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/stat/client_report?date_type=5');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/client_report?date_type=4');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/client_report?date_type=3');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/client_report?date_type=2');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/client_report?date_type=1');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
    public function testSelfIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/self_index');
        $resp->seeJson([ 'res' => 0]);
    }
    public function testSelfTrend()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/self_trend?type=0');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/self_trend?type=1');
        $resp->seeJson([ 'res' => 0]);
    }
    public function testSelfZone()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/self_zone');
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/self_zone?period_start=2016-09-02&period_end=2016-09-08&span=2&zone_offset=-8');
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->get('/trafficker/stat/self_zone?period_start=2015-09-01&period_end=2016-08-31&span=3&zone_offset=-8');
        $resp->seeJson([ 'res' => 0]);

    }
}
