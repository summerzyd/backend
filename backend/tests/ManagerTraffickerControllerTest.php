<?php

class ManagerTraffickerControllerTest extends TestCase
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

        $resp = $this->actingAs($user)->post('/manager/trafficker/index',['search' => '1']);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/manager/trafficker/index',['sort' => '-name']);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/manager/trafficker/index');
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

        $resp = $this->actingAs($user)->post('/manager/trafficker/store',[]);
        $resp->seeJson([ 'res' => 5000]);

        $rand = 'tr' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/manager/trafficker/store',[
            'username' => $rand,
            'password' => '123456',
            'name' => 'name' . $rand,
            'brief_name' => 'BriefName' . $rand,
            'contact' => 'Contact' . $rand,
            'contact_phone' => '132' . $random,
            'qq' => $random,
            'email' => $random . '@qq.com',
            'income_rate' => 89,
            'mode' => 1,
            'kind' => 1,
            'delivery_type' => 3,
            'creator_uid' => 2,
            'app_platform' => 15,
            'audit' => 2,
            'cdn' => 1,
            'delivery' => '[{"ad_type":0,"revenue_type":1,"num":1},{"ad_type":1,"revenue_type":2,"num":10},{"ad_type":1,"revenue_type":1,"num":1}]',
            'affiliate_type' => 1
        ]);
        $resp->seeJson([ 'res' => 0]);

        $rand = 'tr' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/manager/trafficker/store',[
            'username' => $rand,
            'password' => '123456',
            'name' => 'name' . $rand,
            'brief_name' => 'BriefName' . $rand,
            'contact' => 'Contact' . $rand,
            'contact_phone' => '132' . $random,
            'qq' => $random,
            'email' => $random . '@qq.com',
            'income_rate' => 89,
            'mode' => 1,
            'kind' => 2,
            'delivery_type' => 1,
            'creator_uid' => 2,
            'app_platform' => 15,
            'audit' => 2,
            'cdn' => 1,
            'delivery' => '[{"ad_type":0,"revenue_type":1,"num":1},{"ad_type":1,"revenue_type":2,"num":10},{"ad_type":1,"revenue_type":1,"num":1}]',
            'alipay_account' => 'test@qq.com',
            'affiliate_type' => 1
        ]);
        $resp->seeJson([ 'res' => 0]);

        $rand = 'tr' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/manager/trafficker/store',[
            'affiliateid' => 66,
            'username' => $rand,
            'password' => '123456',
            'name' => 'name' . $rand,
            'brief_name' => 'BriefName' . $rand,
            'contact' => 'Contact' . $rand,
            'contact_phone' => '132' . $random,
            'qq' => $random,
            'email' => $random . '@qq.com',
            'income_rate' => 89,
            'mode' => 1,
            'kind' => 2,
            'delivery_type' => 1,
            'creator_uid' => 2,
            'app_platform' => 15,
            'audit' => 2,
            'cdn' => 1,
            'delivery' => '[{"ad_type":0,"revenue_type":1,"num":1},{"ad_type":1,"revenue_type":2,"num":10},{"ad_type":1,"revenue_type":1,"num":1}]',
            'alipay_account' => 'test@qq.com',
            'affiliate_type' => 1,
        ]);
        $resp->seeJson([ 'res' => 0]);

        $rand = 'tr' . str_random(5);
        $random = rand(10000000, 99999999);
        $resp = $this->actingAs($user)->post('/manager/trafficker/store',[
            'affiliateid' => 66,
            'username' => $rand,
            'password' => '123456',
            'name' => 'name' . $rand,
            'brief_name' => 'BriefName' . $rand,
            'contact' => 'Contact' . $rand,
            'contact_phone' => '132' . $random,
            'qq' => $random,
            'email' => $random . '@qq.com',
            'income_rate' => 89,
            'mode' => 1,
            'kind' => 1,
            'delivery_type' => 1,
            'creator_uid' => 2,
            'app_platform' => 15,
            'audit' => 2,
            'cdn' => 1,
            'delivery' => '[{"ad_type":0,"revenue_type":1,"num":1},{"ad_type":1,"revenue_type":2,"num":10},{"ad_type":1,"revenue_type":1,"num":1}]',
            'alipay_account' => 'test@qq.com',
            'affiliate_type' => 1,
        ]);
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

        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'name', 'value' => '1']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'brief_name', 'value' =>  '1']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'brief_name', 'value' => '1']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'email', 'value' => '%%%']);
        $resp->seeJson(['res' => 5018]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'email', 'value' => 'testbiddingos@qq.com']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'contact']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'contact_phone', 'value' => '1234']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'mode', 'value' => '1']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'app_platform']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'affiliate_status', 'value' => '11111']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'name', 'value' => 'tr' . str_random(5)]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'brief_name', 'value' => 'tr01']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'contact_phone', 'value' => '1669752' . rand(1000, 9999)]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'mode', 'value' => '1|1']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'contact', 'value' => 'tr' . str_random(5)]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'qq', 'value' => '18697578322']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'affiliates_status', 'value' => '0']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'affiliates_status', 'value' => '1']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'delivery', 'value' => '[{"ad_type":0,"revenue_type":10,"num":1},{"ad_type":1,"revenue_type":2,"num":10},{"ad_type":1,"revenue_type":10,"num":1}]']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/trafficker/update',['id' => '66', 'field' => 'email', 'value' => str_random(6) . '@qq.com']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testSales()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/trafficker/sales');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}
