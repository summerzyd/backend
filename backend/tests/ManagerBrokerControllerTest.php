<?php

class ManagerBrokerControllerTest extends TestCase
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

        $resp = $this->actingAs($user)->post('/manager/broker/index', ['account_type' => '0', 'pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'lei']);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/broker/update');
        $resp->seeJson(['res' => 5000]);

        $value = rand(1000000,9999999);
        $resp = $this->actingAs($user)->post('/manager/broker/update', ['field' => 'name', 'value' => 'bcl'.$value, 'brokerid' => 2]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/update', ['field' => 'brief_name', 'value' => 'bbr'.$value, 'brokerid' => 2]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/update', ['field' => 'contact', 'value' => 'bco'.$value, 'brokerid' => 2]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/update', ['field' => 'email', 'value' => $value.'@aca.com', 'brokerid' => 2]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/update', ['field' => 'contact_phone', 'value' => '1856'.$value, 'brokerid' => 2]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/update', ['field' => 'qq', 'value' =>$value, 'brokerid' => 2]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/update', ['field' => 'status', 'value' => 0, 'brokerid' => 2]);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/broker/store');
        $resp->seeJson(['res' => 5000, ]);
        $value = rand(1000000,9999999);
        $resp = $this->actingAs($user)->post('/manager/broker/store',
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
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/broker/recharge_history');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/recharge_history', ['brokerid' => '2', 'way' => '0']);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/recharge_history', ['brokerid' => '2', 'way' => '1']);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testRechargeApply()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/broker/recharge_apply');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/recharge_apply',
            ['brokerid' => '2', 'account_info' => '23080', 'date' => '2016-04-25', 'amount' => '8888', 'way' => 0]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/recharge_apply',
            ['brokerid' => '2', 'account_info' => '23080', 'date' => '2016-04-24', 'amount' => '8988', 'way' => 1]);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testRechargeDetail()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/broker/recharge_detail');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/recharge_detail', ['brokerid' => '2']);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testGiftApply()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/broker/gift_apply');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/gift_apply', ['brokerid' => '2', 'amount' => '666' , 'gift_info' => '测试赠送']);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testGiftDetail()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/broker/gift_detail');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/broker/gift_detail', ['brokerid' => '2']);
        $resp->seeJson(['res' => 0, ]);
    }


}