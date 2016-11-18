<?php

class ManagerNoticeControllerTest extends TestCase
{

    public function testIndex()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/notice/index');
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
        
        $resp = $this->actingAs($user)->post('/manager/notice/store', [
            'title' => 'This is test title....',
            'content' => 'This is test content........',
            'role' => 'A'
        ]);
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testEmailIndex()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/notice/email_index');
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testEmailClient()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/notice/email_client');
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testEmailStore()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/notice/email_store', [
            'title' => 'Email Title...',
            'content' => 'Email Content...',
            'clients' => '[{"user_id": "5734",  "email_address": "3843880@abs.com",  "clientname": "bbrs3843880",  "account_id": "635" }, { "user_id": "203", "email_address": "203@test.com", "clientname": "2345","account_id": "173"}]',
            'type' => 'draft'
        ]);
        $resp->seeJson([
            'res' => 0
        ]);
    }

    public function testEmailDelete()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/notice/email_delete', [
            'id' => 13102
        ]);
        $resp->seeJson([
            'res' => 0
        ]);
    }
}
