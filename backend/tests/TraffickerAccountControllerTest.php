<?php

class TraffickerAccountControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/account/index', ['search' => '1']);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/account/index', ['search' => 'test']);
        $resp->seeJson(['res' => 0]);
    }

    public function testStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/account/store',[]);
        $resp->seeJson([ 'res' => 5000]);

        $rand = 'tr' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/trafficker/account/store',[
            'username' => $rand,
            'password' => '123456',
            'contact_name' => 'name' . $rand,
            'contact_phone' => '132' . $random,
            'email_address' => $random . '@qq.com',
            'role_id' => 300,
        ]);
        $resp->seeJson([ 'res' => 0]);

        $rand = 'tr' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/trafficker/account/store',[
            'user_id' => 114,
            'username' => $rand,
            'password' => '123456',
            'contact_name' => 'name' . $rand,
            'contact_phone' => '132' . $random,
            'email_address' => $random . '@qq.com',
            'role_id' => 300,
        ]);
        $resp->seeJson([ 'res' => 0]);
    }

    public function testUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/account/update',['id' => '114']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/account/update',['id' => '114', 'field' => 'password', 'value' =>  '1']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/account/update',['id' => '114', 'field' => 'password', 'value' =>  'adbdfsdf']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/account/update',['id' => '114', 'field' => 'active', 'value' =>  '2']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/account/update',['id' => '114', 'field' => 'active', 'value' =>  '0']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/account/update',['id' => '114', 'field' => 'active', 'value' =>  '1']);
        $resp->seeJson(['res' => 0]);
    }
}
