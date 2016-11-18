<?php

class BrokerStatControllerTest extends TestCase
{
    /**
     * 获取图表，报表显示字段
     */
    public function testColumnList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/broker/stat/column_list?type=0');
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->get('/broker/stat/column_list?type=1');
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->get('/broker/stat/column_list?type=2');
        $resp->seeJson([ 'res' => 0]);


    }
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/broker/stat/index?type=0&period_start=2016-09-01&period_end=2016-09-23&span=2&zone_offset=-8');
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->get('/broker/stat/index?type=1&period_start=2016-09-01&period_end=2016-09-23&span=2&zone_offset=-8');
        $resp->seeJson([ 'res' => 0]);

    }
    public function testReport()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/broker/stat/report');
        $resp->seeJson([ 'res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

}