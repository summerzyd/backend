<?php
class ManagerBalanceControllerTest extends TestCase
{
    /*
     * 广告主，代理商充值申请列表
     */
    public function testRechargeIndex()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/recharge_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE]);
        $resp->seeJson(['res' => 0, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/recharge_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search'=>'爱奇艺视频']);
        $resp->seeJson(['res' => 0, ]);
        
        $this->assertEquals(200, $resp->response->status());
    }
    
    /*
    public function testRechargeUpdate()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/recharge_update');
        $resp->seeJson(['res' => 5000]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/recharge_update', ['field' => 'status', 'value' => 2, 'id' => 1]);
        $resp->seeJson(['res' => 5006, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/recharge_update', ['field' => 'status', 'value' => 2, 'id' => 255512]);
        $resp->seeJson(['res' => 5002, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/recharge_update', ['field' => 'status', 'value' => 2, 'id' => 514]);
        $resp->seeJson(['res' => 0, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/recharge_update', ['field' => 'status', 'value' => 3, 'id' => 515]);
        $resp->seeJson(['res' => 5300, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/recharge_update', ['field' => 'status', 'value' => 3, 'id' => 513, 'content' => '驳回的信息']);
        $resp->seeJson(['res' => 0, ]);
        
        $this->assertEquals(200, $resp->response->status());
    }*/
    
    public function testInvoiceIndex()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/invoice_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE]);
        $resp->seeJson(['res' => 0, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/invoice_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search'=>'爱奇艺视频']);
        $resp->seeJson(['res' => 0, ]);
        
        $this->assertEquals(200, $resp->response->status());
    }
    
    public function testInvoiceUpdate()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/invoice_update');
        $resp->seeJson(['res' => 5000]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/invoice_update', ['field' => 'status', 'value' => 2, 'id' => 12]);
        $resp->seeJson(['res' => 0, ]);
       
        $resp = $this->actingAs($user)->post('/manager/balance/invoice_update', ['field' => 'status', 'value' => 3, 'id' => 18, 'content' => '驳回的信息']);
        $resp->seeJson(['res' => 5001, ]);
        
        $this->assertEquals(200, $resp->response->status());
    }
    
    public function testInvoiceDetail()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/invoice_detail');
        $resp->seeJson(['res' => 5000]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/invoice_detail', ['id' => 11]);
        $resp->seeJson(['res' => 0, ]);
    }
    
    public function testGiftIndex()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/gift_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE]);
        $resp->seeJson(['res' => 0, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/gift_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search'=>'爱奇艺视频']);
        $resp->seeJson(['res' => 0, ]);
        
        $this->assertEquals(200, $resp->response->status());
    }

    /*
    public function testGiftUpdate()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);

        $resp = $this->actingAs($user)->post('/manager/balance/gift_update');
        $resp->seeJson(['res' => 5000]);
    
        $resp = $this->actingAs($user)->post('/manager/balance/gift_update', ['field' => 'status', 'value' => 2, 'id' => 137]);
        $resp->seeJson(['res' => 5006, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/gift_update', ['field' => 'status', 'value' => 2, 'id' => 1307]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/balance/gift_update', ['field' => 'status', 'value' => 3, 'id' => 1379]);
        $resp->seeJson(['res' => 0, ]);

        $this->assertEquals(200, $resp->response->status());
    }
    */
    
    public function testWithdrawalIndex()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/withdrawal_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE]);
        $resp->seeJson(['res' => 0, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/withdrawal_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search'=>'爱奇艺视频']);
        $resp->seeJson(['res' => 0, ]);
        
        $this->assertEquals(200, $resp->response->status());
    }
    
    public function testIncomeIndex()
    {
        $user = factory('App\Models\User')
        ->make([
            'user_id' => DEFAULT_MANAGER_USER_ID,
            'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
            'role_id' => MANAGER_ROLE,
            'agencyid' => AGENCY_ID,
        ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/income_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE]);
        $resp->seeJson(['res' => 0, ]);
        
        $resp = $this->actingAs($user)->post('/manager/balance/income_index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search'=>'爱奇艺视频']);
        $resp->seeJson(['res' => 0, ]);
        
        $this->assertEquals(200, $resp->response->status());
    }

}