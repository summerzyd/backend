<?php

class BrokerBalanceControllerTest extends TestCase
{
    public function testRecharge()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/broker/balance/recharge');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testGift()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/broker/balance/gift');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testInvoiceHistory()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/broker/balance/invoice_history');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testApply()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);
        $resp = $this->actingAs($user)->get('/broker/balance/apply');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testInvoice()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/broker/balance/invoice',[]);
        $resp->seeJson([ 'res' => 5000]);

        $resp = $this->actingAs($user)->post('/broker/balance/invoice',['invoice_id'=>11]);
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}