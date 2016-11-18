<?php

class ManagerActivityControllerTest extends TestCase
{

    public function testIndex()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/activity/index');
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testGet()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/activity/get', [
            'id' => 6
        ]);
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testStore()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/activity/store', [
            'title' => 'This is test title....',
            'imageurl' => 'http:////www.baidu.com/img/baidu_jgylogo3.gif',
            'startdate' => '2016-05-06',
            'enddate' => '2016-05-18',
            'content' => 'This is test content........',
            'role' => 'A'
        ]);
        $resp->seeJson([
            'res' => 0
        ]);
    }
    
    public function testDeal()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);

        $resp = $this->actingAs($user)->post('/manager/activity/deal', [
            'id' => '0',
            'status' => '1',
            'role' => 'B'
        ]);
        $resp->seeJson([
            'res' => 5000
        ]);

        $resp = $this->actingAs($user)->post('/manager/activity/deal', [
            'id' => '0',
            'status' => '0',
            'role' => 'A'
        ]);
        $resp->seeJson([
            'res' => 0
        ]);

        $resp = $this->actingAs($user)->post('/manager/activity/deal', [
            'id' => '0',
            'status' => '2',
            'role' => 'A'
        ]);
        $resp->seeJson([
            'res' => 5214
        ]);

        $resp = $this->actingAs($user)->post('/manager/activity/deal', [
            'id' => '0',
            'status' => '1',
            'role' => 'A'
        ]);
        $resp->seeJson([
            'res' => 5210
        ]);

        $resp = $this->actingAs($user)->post('/manager/activity/deal', [
            'id' => '6',
            'status' => '1',
            'role' => 'A'
        ]);
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testDailyList()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);

        $resp = $this->actingAs($user)->post('/manager/activity/report_list', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/activity/report_list', ['type' => 1]);
        $resp->seeJson(['res' => 0]);
    }
}
