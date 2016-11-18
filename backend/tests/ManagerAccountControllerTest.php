<?php

class ManagerAccountControllerTest extends TestCase
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

        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'MANA', 'search' => '1']);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'MANAGER', 'search' => '1']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'TRAFFICKER', 'search' => '1']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'ADVERTISER', 'search' => '1']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'BROKER', 'search' => '1']);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'MANAGER', 'sort' => '-name']);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'BROKER']);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'TRAFFICKER']);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'ADVERTISER']);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/manager/account/index', ['type' => 'MANAGER', ]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
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

        $resp = $this->actingAs($user)->post('/manager/account/store',[]);
        $resp->seeJson([ 'res' => 5000]);

        $rand = 'tr' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/manager/account/store',[
            'username' => $rand,
            'password' => '123456',
            'contact_name' => 'name' . $rand,
            'contact_phone' => '132' . $random,
            'email_address' => $random . '@qq.com',
            "account_sub_type_id" => '1001',
            "operation_list" => 'manager-profile,manager-password,manager-campaign,manager-advertiser,manager-broker,manager-trafficker,manager-stat,manager-balance,manager-audit,manager-package,manager-message,manager-sdk']);
        $resp->seeJson([ 'res' => 0]);
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

        $resp = $this->actingAs($user)->post('/manager/account/update',['id' => '66']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/account/update',['id' => '66', 'field' => 'account_sub_type_id', 'value' => 'aaa']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/account/update',['id' => '66', 'field' => 'password', 'value' =>  '1']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/account/update',['id' => '569', 'field' => 'operation_list']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/account/update',['id' => '66', 'field' => 'account_sub_type_id', 'value' => '1001']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/account/update',['id' => '66', 'field' => 'password', 'value' => '123456']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/account/update',['id' => '569', 'field' => 'operation_list', 'value' => 'manager-profile,manager-password,manager-campaign,manager-advertiser,manager-broker,manager-trafficker,manager-stat,manager-balance,manager-audit,manager-package,manager-message,manager-account,manager-sdk']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}
