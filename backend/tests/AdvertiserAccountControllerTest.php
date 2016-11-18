<?php

class AdvertiserAccountControllerTest extends TestCase
{
    public function testIndex()
    {
//        $user = factory('App\Models\User')
//            ->make([
//                'user_id' => DEFAULT_ACCOUNT_SUB_USER_ID,
//                'default_account_id' => DEFAULT_ACCOUNT_ID,
//                'role_id' => ADVERTISER_ROLE
//            ]);
//        $resp = $this->actingAs($user)->post('/advertiser/account/index');
//        $resp->seeJson(['res' => 5003, ]);

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => DEFAULT_AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/index');
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/index', ['pageNo' => 2, 'pageSize' => 1]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'uc']);
        $resp->seeJson(['res' => 0, ]);
    }

    public function testUpdate()
    {
//        $user = factory('App\Models\User')
//            ->make([
//                'user_id' => DEFAULT_ACCOUNT_SUB_USER_ID,
//                'default_account_id' => DEFAULT_ACCOUNT_ID,
//                'role_id' => ADVERTISER_ROLE
//            ]);
//        $resp = $this->actingAs($user)->post('/advertiser/account/index');
//        $resp->seeJson(['res' => 5003, ]);

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => DEFAULT_AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ACCOUNT_SUB_USER_ID]);
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ACCOUNT_SUB_USER_ID, 'field' => 'username', 'value' => '%ASS']);
        $resp->seeJson(['res' => 5019, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ROLE_ID, 'field' => 'operation_list', 'value' => 'advertiser-common,advertiser-campaign']);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ACCOUNT_SUB_USER_ID, 'field' => 'username', 'value' => EXIST_USERNAME]);
        $resp->seeJson(['res' => 5092, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ACCOUNT_SUB_USER_ID, 'field' => 'email_address', 'value' => 'not email']);
        $resp->seeJson(['res' => 5018, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ACCOUNT_SUB_USER_ID, 'field' => 'email_address', 'value' => EXIST_EMAIL]);
        $resp->seeJson(['res' => 5093, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ACCOUNT_SUB_USER_ID, 'field' => 'contact_name']);
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ACCOUNT_SUB_USER_ID, 'field' => 'contact_name']);
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/advertiser/account/update', ['id' => DEFAULT_ACCOUNT_SUB_USER_ID, 'field' => 'active', 'value' => 2]);
        $resp->seeJson(['res' => 5000, ]);
    }
    
    public function testStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => DEFAULT_AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/advertiser/account/store', ['username' => 'uctest', 'password' => '12ddd34', 'contact_name' => '测试人', 'email_address' => 'test@163.com', 'phone' => '1546478', 'account_sub_type_id' => '1', 'qq' => '12345667', 'operation_list' => 'advertiser-common,advertiser-campaign']);
        $resp->seeJson([ 'res' => 5000]);

        $resp = $this->actingAs($user)->post('/advertiser/account/store', ['username' => 'uctest', 'password' => '123456', 'contact_name' => '测试人', 'email_address' => 'test@163.com', 'phone' => '15464787894', 'account_sub_type_id' => '1', 'qq' => '12345667', 'operation_list' => 'advertiser-common,advertiser']);
        $resp->seeJson([ 'res' => 5020]);

        $resp = $this->actingAs($user)->post('/advertiser/account/store', ['username' => 'uc-test', 'password' => '123456', 'contact_name' => '测试人', 'email_address' => 'test@163.com', 'phone' => '15464787894', 'account_sub_type_id' => '1', 'qq' => '12345667', 'operation_list' => 'advertiser-common,advertiser']);
        $resp->seeJson([ 'res' => 5019]);

        $resp = $this->actingAs($user)->post('/advertiser/account/store', ['username' => 'uctest', 'password' => '123456', 'contact_name' => '测试人', 'email_address' => 'test@163.com', 'phone' => '15464787894', 'account_sub_type_id' => '1000', 'qq' => '12345667', 'operation_list' => 'advertiser-campaign']);
        $resp->seeJson([ 'res' => 5026]);
        $this->assertEquals(200, $resp->response->status());
    }
}
