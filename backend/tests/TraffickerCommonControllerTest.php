<?php

class TraffickerCommonControllerTest extends TestCase
{
    /**
     * 账户余额
     */
    public function testBalanceValue()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/common/balance_value');
        $resp->seeJson([ 'res' => 0]);

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/common/balance_value');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取销售顾问
     */
    public function testSales()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/common/sales');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取销售顾问
     */
    public function testCampaignPendingAudit()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/common/campaign_pending_audit');
        $resp->seeJson([ 'res' => 0]);
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/common/campaign_pending_audit');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testBalancePendingAudit()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/common/balance_pending_audit');
        $resp->seeJson(['res' => 0]);

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/common/balance_pending_audit');
        $resp->seeJson(['res' => 0]);
    }
}
