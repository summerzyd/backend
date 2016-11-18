<?php
class ManagerCommonControllerTest extends TestCase
{
    public function testGameStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $app = 'test' . str_random(6);
        $resp = $this->actingAs($user)->post('/manager/game/game_store', [
            'clientid' => 1,
        ]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/game/game_store', [
            'clientid' => 1,
            'appinfos_app_name' => '大话西游',
        ]);
        $resp->seeJson(['res' => 5022]);
        $resp = $this->actingAs($user)->post('/manager/game/game_store', [
            'clientid' => 1,
            'appinfos_app_name' => $app,
        ]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testGameIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/game/game_index', [
            'clientid' => 1,
        ]);
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
        $resp = $this->actingAs($user)->post('/manager/game/store', [
            'clientid' => 1,
        ]);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/manager/game/store', [
            'clientid' => 1,
            'campaignid' => 6190,
            'affiliateid' => 1,
            'date' => date('Y-m-d',strtotime("+2 days")),
            'game_client_revenue_type'=> 16,
            'game_af_revenue_type' => 16,
        ]);
        $resp->seeJson(['res' => 8001]);
        $resp = $this->actingAs($user)->post('/manager/game/store', [
            'clientid' => 1,
            'campaignid' => 6190,
            'affiliateid' => 1,
            'date' => date('Y-m-d'),
            'game_client_revenue_type'=> 16,
            'game_af_revenue_type' => 16,
            'game_client_usernum'=>1,
            'game_charge'=>2,
            'game_client_price'=>2,
            'game_client_amount'=>3,
            'game_af_price'=>5,
            'game_af_amount'=>2,
            'game_af_usernum'=>2,
        ]);
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/game/index', [
            'search'=> 's',
            'filter' => '{"date":["2016-11-01","2016-11-15"],"clientid":"1","campaignid":"1","affiliateid":"1"}',
        ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/game/index', [
            'search'=> '',
            'filter' => '{"date":["",""],"clientid":"","campaignid":"","affiliateid":""}',
        ]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testFilter()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/game/filter', [
            'type'=> '0',
        ]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/game/filter', [
            'type'=> '1',
        ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/game/filter', [
            'type'=> '2',
            'clientid' => 1,
        ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/game/filter', [
            'type'=> '3',
            'clientid' => 1,
            'campaignid'=> 6029,
        ]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testAffiliateList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/game/affiliate_list', []);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testClientList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/game/client_list', []);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

}