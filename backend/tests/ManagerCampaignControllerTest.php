<?php
class ManagerCampaignControllerTest extends TestCase
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

        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>1000 ,'search'=> '2','sort'=>'','campaignid'=>'','ad_type'=> '','platform'=>'','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>1000 ,'search'=> '2','campaignid'=>'','ad_type'=> '','platform'=>'','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>25 ,'search'=> '2','sort'=>'-clientname','campaignid'=>'','ad_type'=> '3','platform'=>'15','status'=> '10','day_limit'=> '1','revenue'=>'2','revenue_type'=>'2']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>25 ,'search'=> '2','sort'=>'-app_name','campaignid'=>'2195','ad_type'=> '','platform'=>'','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>25 ,'search'=> '2','sort'=>'platform','campaignid'=>'','ad_type'=> '','platform'=>'','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>25 ,'search'=> '2','sort'=>'revenue','campaignid'=>'','ad_type'=> '','platform'=>'','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>25 ,'search'=> '2','sort'=>'day_limit','campaignid'=>'','ad_type'=> '','platform'=>'','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>25 ,'search'=> '2','sort'=>'ad_type','campaignid'=>'','ad_type'=> '','platform'=>'','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>25 ,'search'=> '2','sort'=>'rate','campaignid'=>'','ad_type'=> '','platform'=>'7','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/index',['pageNo'=> 1,'pageSize'=>25 ,'search'=> '2','sort'=>'operation_time','campaignid'=>'','ad_type'=> '','platform'=>'','status'=> '','day_limit'=> '','revenue'=>'','revenue_type'=>'']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testCheck()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/campaign/check', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/check', ['campaignid'=>'1576','status'=>'11',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/check', ['campaignid'=>'1576','status'=>'11','approve_comment'=>'dd']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/check', ['campaignid'=>'2235','status'=>'1']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/check', ['campaignid'=>'2235','status'=>'15']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/check', ['campaignid'=>'2235','status'=>'0']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/check', ['campaignid'=>'2235','status'=>'0']);
        $resp->seeJson(['res' => 0]);
    }

    public function testRevenueHistory()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/campaign/revenue_history', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/revenue_history', ['campaignid' => 2585]);
        $resp->seeJson(['res' => 0]);
    }



    public function testInfo()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/campaign/info', ['campaignid' => '',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/info', ['campaignid' => '1',]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/info', ['campaignid' => '171',]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/info', ['campaignid' => '2194',]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/info', ['campaignid' => '1228',]);
        $resp->seeJson(['res' => 5001]);
        $resp = $this->actingAs($user)->post('/manager/campaign/info', ['campaignid' => '2410',]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/info', ['campaignid' => '3179',]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取所有出价
     */
    public function testRevenue()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/campaign/revenue');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取所有日限额
     */
    public function testDayLimit()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->get('/manager/campaign/day_limit');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 更新媒体系数
     */
    public function testUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/campaign/update',['campaignid'=>'','field'=>'']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/update',['campaignid'=>'2609','field'=>'rate','value'=>'80']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/update',['campaignid'=>'2609','field'=>'rate','value'=>'90']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/update',['campaignid'=>'2609','field'=>'condition','value'=>'["ddd11"]']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/update',['campaignid'=>'2609','field'=>'condition','value'=>'["ddd"]']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取广告列表
     */
    public function testClientList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/campaign/client_list',['revenue_type'=>'',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/client_list',['revenue_type'=>'4',]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取广告主产品列表
     */
    public function testProductList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/campaign/product_list',['clientid'=>'',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/product_list',['clientid'=>'1',]);
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
        $resp = $this->actingAs($user)->post('/manager/campaign/store',['revenue_type'=>'',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/store',['id'=>'2992','revenue_type'=>'4','clientid'=>293,'products_id'=>1191,'platform'=>1,'products_name'=>'总预算','products_icon'=>'http://7xnoye.com1.z0.glb.clouddn.com/o_1aiabrb71jvssg1hnoq87haq1u576.jpg','appinfos_app_name'=>'总预算-其他']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testEquivalenceList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence_list',['revenue_type'=>'',]);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence_list',['campaignid'=>'785','platform'=>1,'ad_type'=>0,'revenue_type'=>1,'search'=>'xiao']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence_list',['campaignid'=>'785','platform'=>1,'ad_type'=>0,'revenue_type'=>1,'search'=>'']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence_list',['campaignid'=>'1627','platform'=>1,'ad_type'=>0,'revenue_type'=>1,'search'=>'']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testEquivalence()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence',['campaignid'=>'',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence',['campaignid'=>'2578','campaignid_relation'=>2568,'action'=>1]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence',['campaignid'=>'2578','campaignid_relation'=>2571,'action'=>1]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence',['campaignid'=>'10','campaignid_relation'=>2571,'action'=>1]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence',['campaignid'=>'2568','campaignid_relation'=>2571,'action'=>1]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence',['campaignid'=>'2578','campaignid_relation'=>2568,'action'=>2]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence',['campaignid'=>'2578','campaignid_relation'=>2571,'action'=>2]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/equivalence',['campaignid'=>'2578','campaignid_relation'=>10,'action'=>2]);
        $resp->seeJson(['res' => 0]);
    }

    public function testConsume()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/campaign/consume', ['campaignid' => '',]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/manager/campaign/consume', ['campaignid' => '2574']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/manager/campaign/consume', ['campaignid' => '2577']);
        $resp->seeJson(['res' => 0]);
    }

}