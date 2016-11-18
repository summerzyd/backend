<?php

class BrokerCommonControllerTest extends TestCase
{
    /**
     * 账户余额
     */
    public function testBalanceValue()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_BROKER_USER_ID, 'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,'role_id' => BROKER_ROLE]);

        $resp = $this->actingAs($user)->get('/broker/common/balance_value');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取销售顾问
     */
    public function testSales()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_BROKER_USER_ID, 'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,'role_id' => BROKER_ROLE]);

        $resp = $this->actingAs($user)->post('/broker/common/sales');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}
