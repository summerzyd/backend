<?php

class AdvertiserCommonControllerTest extends TestCase
{
    /**
     * 账户余额
     */
    public function testBalanceValue()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID, 'role_id' => ADVERTISER_ROLE]);

        $resp = $this->actingAs($user)->get('/advertiser/common/balance_value');
        $resp->seeJson([ 'res' => 0]);

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_SELF_USER_ID,
                'default_account_id' => DEFAULT_SELF_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/advertiser/common/balance_value');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取销售顾问
     */
    public function testSales()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID, 'role_id' => ADVERTISER_ROLE]);

        $resp = $this->actingAs($user)->post('/advertiser/common/sales');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

}
