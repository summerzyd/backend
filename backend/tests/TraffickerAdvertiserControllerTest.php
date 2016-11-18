<?php

class TraffickerAdvertiserControllerTest extends TestCase
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

        $resp = $this->actingAs($user)->post('/trafficker/advertiser/index',['search'=> '1','type' =>0]);
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/advertiser/index',['search'=> '1','type' =>1]);
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
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

        $resp = $this->actingAs($user)->post('/trafficker/advertiser/store',[]);
        $resp->seeJson([ 'res' => 5000]);

        $name = 'yu'.str_random(5);
        $resp = $this->actingAs($user)->post('/trafficker/advertiser/store',[
            'clientname' => $name,
            'brief_name' => $name,
            'username' => $name,
            'password' => '123456',
            'contact' => $name,
            'email' => $name.'@qq.com',
            'contact_phone' => '185'.rand(10000000,99999999),
            'qq' => '1324'.str_random(5),
            'revenue_type'=> 3,
            'creator_uid'=> DEFAULT_TRAFFICKER_SELF_USER_ID,
        ]);
        $resp->seeJson([ 'res' => 0]);

        $name = 'yu'.str_random(5);
        $resp = $this->actingAs($user)->post('/trafficker/advertiser/store',[
            'clientid' => 57,
            'clientname' => $name,
            'brief_name' => $name,
            'username' => $name,
            'password' => '123456',
            'contact' => $name,
            'email' => $name.'@qq.com',
            'contact_phone' => '185'.rand(10000000,99999999),
            'qq' => '1324232',
            'revenue_type'=> 3,
            'creator_uid'=> DEFAULT_TRAFFICKER_SELF_USER_ID,
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

        $resp = $this->actingAs($user)->post('/trafficker/advertiser/update',['id'=> '564']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/advertiser/update',['id'=> '564','field' => 'password','value'=> '1']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/advertiser/update',['id'=> '564','field' => 'password','value'=> '123456']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/advertiser/update',['id'=> '564','field' => 'clients_status','value'=> '0']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/advertiser/update',['id'=> '564','field' => 'clients_status','value'=> '1']);
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

/*    public function testBalanceValue()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/advertiser/balance_value');
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/advertiser/balance_value',['client_id'=> '278']);
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }*/
}