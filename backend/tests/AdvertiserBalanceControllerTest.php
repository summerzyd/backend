<?php


class AdvertiserBalanceControllerTest extends TestCase
{
    public function testPayout()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/balance/payout');
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/advertiser/balance/recharge');
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/advertiser/balance/recharge_invoice');
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/advertiser/balance/invoice_history');
        $resp->seeJson(['res' => 0, ]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testInvoice()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/balance/invoice?invoice_id=adfsdfsf');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->get('/advertiser/balance/invoice?invoice_id=13');
        $resp->seeJson(['res' => 0, ]);

        $this->assertEquals(200, $resp->response->status());
    }
}
