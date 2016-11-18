<?php

class AdvertiserPayControllerTest extends TestCase
{
    public function testActivity()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
            ]);

        $resp = $this->actingAs($user)->get('/advertiser/pay/activity');
        $resp->seeJson(['res' => 0, ]);

         $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => 36,
                'role_id' => ADVERTISER_ROLE
            ]);

         $resp = $this->actingAs($user)->get('/advertiser/pay/activity');
         $resp->seeJson(['res' => 0, ]);

         $this->assertEquals(200, $resp->response->status());
    }

    public function testReceiverInfo()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE
            ]);
        
        $resp = $this->actingAs($user)->get('/advertiser/pay/receiver_info');
        $resp->seeJson(['res' => 0, ]);

        $this->assertEquals(200, $resp->response->status());
    }
}
