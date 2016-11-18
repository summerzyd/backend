<?php
class BrokerCampaignControllerTest extends TestCase
{
    public function testColumnList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/broker/campaign/column_list');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','sort'=>'status']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','sort'=>'-ad_type']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','sort'=>'day_limit']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','products_type'=>'1']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','ad_type'=>'1']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','platform'=>'7']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','platform'=>'1']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','revenue_type'=>'1']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'s','sort'=>'']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/broker/campaign/index',['pageSize'=>1000,'pageNo'=>1,'search'=>'','sort'=>'']);
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testRevenue()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID, 'role_id' => ADVERTISER_ROLE]);
        $resp = $this->actingAs($user)->get('/broker/campaign/revenue');
        $resp->seeJson([ 'res' => 5003]);

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/broker/campaign/revenue');
        $resp->seeJson([ 'res' => 0]);
    }

    public function testDayLimit()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID, 'role_id' => ADVERTISER_ROLE]);
        $resp = $this->actingAs($user)->get('/broker/campaign/day_limit');
        $resp->seeJson([ 'res' => 5003]);

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/broker/campaign/day_limit');
        $resp->seeJson([ 'res' => 0]);
    }

    public function testRevenueType()
    {

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_BROKER_USER_ID,
                'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                'role_id' => BROKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/broker/campaign/revenue_type');
        $resp->seeJson([ 'res' => 0]);
    }
}