<?php

class ManagerStatControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/stat/index');
        $resp->seeJson(['res' => 0]);
    }
    public function testTrend()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/stat/trend?type=1');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/trend?type=0');
        $resp->seeJson(['res' => 0]);
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

        $resp = $this->actingAs($user)->get('/manager/stat/rank?date_type=0');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/rank?date_type=1');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/rank?date_type=2');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/rank?date_type=3');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/rank?date_type=4');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/rank?date_type=5');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/rank?date_type=6');
        $resp->seeJson(['res' => 0]);
    }
    public function testZone()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone?audit=0&period_start=2016-05-01&period_end=2016-05-24&span=2&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone?audit=1&period_start=2016-05-01&period_end=2016-05-24&span=2&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone?audit=0&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone?audit=1&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone?audit=0&period_start=2016-05-02&period_end=2016-05-02&span=1&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone?audit=1&period_start=2016-05-02&period_end=2016-05-02&span=2&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $user_d = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_OT_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_OT_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user_d)->get('/manager/stat/zone?audit=0&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user_d)->get('/manager/stat/zone?audit=1&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8');
        $resp->seeJson(['res' => 0]);
    }
    public function testZoneAffiliate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone_affiliate?audit=0&period_start=2016-05-01&period_end=2016-05-24&span=2&zone_offset=-8&affiliateid=46');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone_affiliate?audit=1&period_start=2016-05-01&period_end=2016-05-24&span=2&zone_offset=-8&affiliateid=72');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone_affiliate?audit=0&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8&affiliateid=72');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/zone_affiliate?audit=1&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8&affiliateid=46');
        $resp->seeJson(['res' => 0]);

        $user_d = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_OT_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_OT_ROLE
            ]);
        $resp = $this->actingAs($user_d)->get('/manager/stat/zone_affiliate?audit=0&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8&affiliateid=55');
        $resp->seeJson(['res' => 0]);

    }
    public function testClient()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/stat/client?audit=0&period_start=2016-05-01&period_end=2016-05-24&span=2&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/client?audit=1&period_start=2016-05-01&period_end=2016-05-24&span=2&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

        $user_d = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_OT_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_OT_ROLE
            ]);
        $resp = $this->actingAs($user_d)->get('/manager/stat/client?audit=0&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8');
        $resp->seeJson(['res' => 0]);
    }
    public function testClientCampaign()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/stat/client_campaign?audit=0&period_start=2016-05-01&period_end=2016-05-24&span=2&zone_offset=-8&campaignid=1384');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->get('/manager/stat/client_campaign?audit=1&period_start=2016-05-01&period_end=2016-05-24&span=2&zone_offset=-8&campaignid=1384');
        $resp->seeJson(['res' => 0]);

        $user_d = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_OT_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_OT_ROLE
            ]);
        $resp = $this->actingAs($user_d)->get('/manager/stat/client_campaign?audit=0&period_start=2015-05-01&period_end=2016-04-30&span=3&zone_offset=-8&campaignid=216');
        $resp->seeJson(['res' => 0]);
    }
    public function testManualData()
    {
        $user = factory('App\Models\User')
        ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
        ]);
    
        $resp = $this->actingAs($user)->post('/manager/stat/manual_data', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'date' => '2016-04-28']);
        $resp->seeJson(['res' => 0]);
    
        $resp = $this->actingAs($user)->post('/manager/stat/manual_data',['pageNo' => 2, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'uc', 'date' => '2016-04-28']);
        $resp->seeJson(['res' => 0]);
    }
    public function testClientData()
    {
        $user = factory('App\Models\User')
        ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
        ]);
    
        $resp = $this->actingAs($user)->post('/manager/stat/client_data',
                [
                        'pageNo' => DEFAULT_PAGE_NO,
                        'pageSize' => DEFAULT_PAGE_SIZE,
                        'date' => '2016-05-06',
                        'platform' => 0,
                        'product_id' => 0,
                        'campaignid' => 0,
                ]
    
        );
        $resp->seeJson(['res' => 0]);
    
        $resp = $this->actingAs($user)->post('/manager/stat/client_data',
                [
                        'pageNo' => DEFAULT_PAGE_NO,
                        'pageSize' => DEFAULT_PAGE_SIZE,
                        'date' => '2016-05-06',
                        'platform' => 0,
                        'product_id' => 2,
                        'campaignid' => 0,
                ]
        );
        $resp->seeJson(['res' => 0]);
    }
    public function testProduct()
    {
        $user = factory('App\Models\User')
        ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
        ]);
        $resp = $this->actingAs($user)->post('/manager/stat/product',
                [
                        'date' => '2016-03-28',
                        'platform' => 0,
                ]
        );
        $resp->seeJson(['res' => 0]);
    }
    public function testCampaign()
    {
        $user = factory('App\Models\User')
        ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
        ]);
        $resp = $this->actingAs($user)->post('/manager/stat/campaign',
                [
                        'date' => '2016-03-28',
                        'platform' => 0,
                        'product_id' => 0
                ]
        );
        $resp->seeJson(['res' => 0]);
    }
    public function testTraffickerTrend()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/manager/stat/trafficker_trend?type=0');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/trafficker_trend?type=1');
        $resp->seeJson(['res' => 0]);
    }
    public function testTraffickerDaily()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/manager/stat/trafficker_daily?type=0');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/trafficker_daily?type=1');
        $resp->seeJson(['res' => 0]);
    }
    public function testTraffickerWeekRetain()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/manager/stat/trafficker_week_retain?date=2016-07-29');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/trafficker_week_retain?date=2016-07-28');
        $resp->seeJson(['res' => 0]);
    }
    public function testTraffickerMonth()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/manager/stat/trafficker_month');
        $resp->seeJson(['res' => 0]);
    }
    public function testSaleTrend()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/manager/stat/sale_trend?type=0');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/sale_trend?type=1');
        $resp->seeJson(['res' => 0]);
    }
    public function testSaleRank()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/manager/stat/sale_rank?date_type=0');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/sale_rank?date_type=1');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/sale_rank?date_type=3');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/sale_rank?date_type=4');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/sale_rank?date_type=5');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/sale_rank?date_type=6');
        $resp->seeJson(['res' => 0]);
    }
    public function testAdxReport()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/manager/stat/adx_report?period_start=2016-09-22&period_end=2016-09-28&span=2&zone_offset=-8');
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->get('/manager/stat/adx_report?period_start=2016-09-26&period_end=2016-09-26&span=1&zone_offset=-8');
        $resp->seeJson(['res' => 0]);

    }
}