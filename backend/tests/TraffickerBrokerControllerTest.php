<?php

class TraffickerBrokerControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => 1266,
                'default_account_id' => 1201,
                'role_id' => 1747,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'lei']);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => 1266,
                'default_account_id' => 1201,
                'role_id' => 1747,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/update');
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/update', ['field' => 'status', 'value' => 0, 'id' => 44]);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => 1266,
                'default_account_id' => 1201,
                'role_id' => 1747,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/broker/store');
        $resp->seeJson(['res' => 5000, ]);
        $value = rand(1000000,9999999);
        $resp = $this->actingAs($user)->post('/trafficker/broker/store',
            [
                'name' => 'bcls' . $value,
                'brief_name' => 'bbrs' . $value ,
                'username' => 'burs' . $value,
                'password' => '123456',
                'contact' => 'bcos' . $value,
                'email' => $value . '@abs.com',
                'contact_phone' => '1876' . $value,
                'qq' => $value,
                'creator_uid' => '5655',
                'operation_uid' => 453,
                'revenue_type' => 3,
            ]);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testRechargeHistory()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => 1266,
                'default_account_id' => 1201,
                'role_id' => 1747,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/broker/recharge_history');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/recharge_history', ['brokerid' => '2', 'way' => '0']);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/recharge_history', ['brokerid' => '2', 'way' => '1']);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testRechargeApply()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => 1266,
                'default_account_id' => 1201,
                'role_id' => 1747,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/broker/recharge_apply');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/recharge_apply',
            ['brokerid' => '2', 'account_info' => '23080', 'date' => '2016-04-25', 'amount' => '8888', 'way' => 0]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/recharge_apply',
            ['brokerid' => '2', 'account_info' => '23080', 'date' => '2016-04-24', 'amount' => '8988', 'way' => 1]);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testRechargeDetail()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => 1266,
                'default_account_id' => 1201,
                'role_id' => 1747,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/broker/recharge_detail');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/recharge_detail', ['brokerid' => '2']);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testGiftApply()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => 1266,
                'default_account_id' => 1201,
                'role_id' => 1747,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/broker/gift_apply');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/gift_apply', ['brokerid' => '2', 'amount' => '666' , 'gift_info' => '测试赠送']);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testGiftDetail()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => 1266,
                'default_account_id' => 1201,
                'role_id' => 1747,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/broker/gift_detail');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/trafficker/broker/gift_detail', ['brokerid' => '2']);
        $resp->seeJson(['res' => 0, ]);
    }


}