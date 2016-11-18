<?php

class TraffickerRoleControllerTest extends TestCase
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

        $resp = $this->actingAs($user)->post('/trafficker/role/index');
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

        $resp = $this->actingAs($user)->post('/trafficker/role/store',[]);
        $resp->seeJson([ 'res' => 5000]);

        $rand = 'role' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/trafficker/role/store',[
            'name' => 'role' . $rand,
            "operation_list" => 'trafficker-profile,trafficker-password,trafficker-campaign,trafficker-advertiser,trafficker-broker,trafficker-trafficker,trafficker-stat,trafficker-balance,trafficker-audit,trafficker-package,trafficker-message,trafficker-sdk']);
        $resp->seeJson([ 'res' => 0]);

        $rand = 'role' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/trafficker/role/store',[
            'id' => 300,
            'name' => 'role' . $rand,
            "operation_list" => 'trafficker-profile,trafficker-password,trafficker-campaign,trafficker-advertiser,trafficker-broker,trafficker-trafficker,trafficker-stat,trafficker-balance,trafficker-audit,trafficker-package,trafficker-message,trafficker-sdk']);
        $resp->seeJson([ 'res' => 0]);
    }

    public function testOperationList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/role/operation_list');
        $resp->seeJson(['res' => 0]);

    }
}
