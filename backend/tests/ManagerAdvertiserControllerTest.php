<?php

class ManagerAdvertiserControllerTest extends TestCase
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

        $resp = $this->actingAs($user)->post('/manager/advertiser/index');
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/manager/advertiser/index', ['account_type' => '0', 'pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'lei','type'=>0]);
        $resp->seeJson(['res' => 0, ]);
        $resp = $this->actingAs($user)->post('/manager/advertiser/index', ['account_type' => '0', 'pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'lei','type'=>1]);
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

         $resp = $this->actingAs($user)->post('/manager/advertiser/update');
         $resp->seeJson(['res' => 5000]);

         $value = rand(1000000,9999999);
         $resp = $this->actingAs($user)->post('/manager/advertiser/update', ['field' => 'clientname', 'value' => 'cl'.$value, 'clientid' => 298]);
         $resp->seeJson(['res' => 0, ]);

         $resp = $this->actingAs($user)->post('/manager/advertiser/update', ['field' => 'brief_name', 'value' => 'br'.$value, 'clientid' => 298]);
         $resp->seeJson(['res' => 0, ]);

         $resp = $this->actingAs($user)->post('/manager/advertiser/update', ['field' => 'contact', 'value' => 'co'.$value, 'clientid' => 298]);
         $resp->seeJson(['res' => 0, ]);

         $resp = $this->actingAs($user)->post('/manager/advertiser/update', ['field' => 'email', 'value' => $value.'@aa.com', 'clientid' => 298]);
         $resp->seeJson(['res' => 0, ]);

         $resp = $this->actingAs($user)->post('/manager/advertiser/update', ['field' => 'contact_phone', 'value' => '1856'.$value, 'clientid' => 298]);
         $resp->seeJson(['res' => 0, ]);

         $resp = $this->actingAs($user)->post('/manager/advertiser/update', ['field' => 'qq', 'value' =>$value, 'clientid' => 298]);
         $resp->seeJson(['res' => 0, ]);

         $resp = $this->actingAs($user)->post('/manager/advertiser/update', ['field' => 'clients_status', 'value' => 0, 'clientid' => 298]);
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
        $resp = $this->actingAs($user)->post('/manager/advertiser/store');
        $resp->seeJson(['res' => 5000, ]);
        $value = rand(1000000,9999999);
        $resp = $this->actingAs($user)->post('/manager/advertiser/store',
            [
                'clientname' => 'cls' . $value,
                'brief_name' => 'brs' . $value ,
                'username' => 'urs' . $value,
                'password' => '123456',
                'contact' => 'cos' . $value,
                'email' => $value . '@as.com',
                'contact_phone' => '1876' . $value,
                'qq' => $value,
                'creator_uid' => '5655',
                'operation_uid' => 437,
                'revenue_type' => 3,
                'address' => 'Test Test Test test001',
                'qualifications' => '{"business_license":"http://7xnoye.com1.z0.glb.clouddn.com/o_1ataeull5ro8ptkdggl7f1uerc2.jpg","network_business_license"
:"http://7xnoye.com1.z0.glb.clouddn.com/o_1ataeuomu620118u4qr1vje1tbuh2.jpg"}',
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
        $resp = $this->actingAs($user)->post('/manager/advertiser/recharge_history');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/advertiser/recharge_history', ['clientid' => '298', 'way' => '0']);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/advertiser/recharge_history', ['clientid' => '298', 'way' => '1']);
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
        $resp = $this->actingAs($user)->post('/manager/advertiser/recharge_apply');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/advertiser/recharge_apply',
            ['clientid' => '298', 'account_info' => '298080', 'date' => '2016-04-25', 'amount' => '8888', 'way' => 0]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/advertiser/recharge_apply',
            ['clientid' => '298', 'account_info' => '298080', 'date' => '2016-04-24', 'amount' => '8988', 'way' => 1]);
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
        $resp = $this->actingAs($user)->post('/manager/advertiser/recharge_detail');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/advertiser/recharge_detail', ['clientid' => '298']);
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
        $resp = $this->actingAs($user)->post('/manager/advertiser/gift_apply');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/advertiser/gift_apply', ['clientid' => '298', 'amount' => '666' , 'gift_info' => '测试赠送']);
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
        $resp = $this->actingAs($user)->post('/manager/advertiser/gift_detail');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/advertiser/gift_detail', ['clientid' => '298']);
        $resp->seeJson(['res' => 0, ]);
    }


}