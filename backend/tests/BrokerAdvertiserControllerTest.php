<?php

class BrokerAdvertiserControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/broker/advertiser/index',['search'=> '1']);
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->post('/broker/advertiser/index');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/broker/advertiser/store',[]);
        $resp->seeJson([ 'res' => 5000]);

        $name = 'yu'.str_random(5);
        $resp = $this->actingAs($user)->post('/broker/advertiser/store',[
            'clientname' => $name,
            'brief_name' => $name,
            'username' => $name,
            'password' => '123456',
            'contact' => $name,
            'email' => $name.'@qq.com',
            'phone' => '185'.rand(10000000,99999999),
            'qq' => '1324232',
            'revenue_type'=> 3,
        ]);
        $resp->seeJson([ 'res' => 0]);
    }

    public function testUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'clientname','value'=> '1']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'brief_name','value'=> '1']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'contact']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'email','value'=> '1']);
        $resp->seeJson([ 'res' => 5018]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'email','value'=> 'testbiddingos@qq.com']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'phone']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'clients_status']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'clientname','value'=>'yu0005']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'clientname','value'=>'yu03']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'phone','value'=>'19697578322']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'phone','value'=>'18697578322']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'contact','value'=>'18697578322']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'contact','value'=>'18697578322']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'clients_status','value'=>'0']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'clients_status','value'=>'1']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'email','value'=>'yu0005@qq.com']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'email','value'=>'yu03@qq.com']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'brief_name','value'=>'yu0005']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'brief_name','value'=>'yu03']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'revenue_type','value'=>'15']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/update',['client_id'=> '224','field' => 'revenue_type','value'=>'3']);
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testBalanceValue()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/broker/advertiser/balance_value');
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/broker/advertiser/balance_value',['client_id'=> '278']);
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}