<?php

class ManagerAuditControllerTest extends TestCase
{

    public function testTraffickerIndex()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/audit/trafficker_index');
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testAdvertiserIndex()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/audit/advertiser_index');
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testAdvertiserUpdate()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/audit/advertiser_update', [
            'campaignid' => 2594,
            'field' => 'status',
            'value' => 1,
            'date' => '2016-05-17'
        ]);
        $resp->seeJson([
            'res' => 4001
        ]);
    }
    
    public function testAdvertiserUpdateBatch()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
    
        $resp = $this->actingAs($user)->post('/manager/audit/advertiser_update_batch', [
            'data' => '[{"campaignid":2594,"field":"status","value":2,"date":"2016-05-31","time":1473002402},{"campaignid":2593,"field":"status","value":2,"date":"2016-05-31"}]'
        ]);
        $resp->seeJson([
            'res' => 5310
        ]);
        
    }
    
    public function testAdvertiserDelivery()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
    
        $resp = $this->actingAs($user)->post('/manager/audit/advertiser_delivery', [
            'campaignid' => 2594,
            'date' => '2016-05-17',
        ]);
        $resp->seeJson([
            'res' => 0
        ]);
    }
}
