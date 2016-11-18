<?php

class TraffickerCampaignControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['status'=>'a']);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"0","platform":"","parent":"","app_rank":"","status":""}','pageNo'=>'','pageSize'=>'','search'=>'','sort'=>'' ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"0","platform":"","parent":"","app_rank":"","status":""}','pageNo'=>'','pageSize'=>'','search'=>'a', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"0","platform":"","parent":"","app_rank":"","status":""}','pageNo'=>'','pageSize'=>'','search'=>'a', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"0","platform":"","parent":"","app_rank":"","status":""}','pageNo'=>'','pageSize'=>'','search'=>'','sort'=>'af_income', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"0","platform":"","parent":"","app_rank":"","status":""}','pageNo'=>'','pageSize'=>'','search'=>'','sort'=>'affiliate_checktime', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"0","platform":"","parent":"","app_rank":"","status":""}','pageNo'=>'','pageSize'=>'','search'=>'','sort'=>'-af_income', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"0","platform":"1","parent":"1","app_rank":"1","status":"2"}','pageNo'=>'','pageSize'=>'','search'=>'','sort'=>'-af_income', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"1","platform":"1","parent":"1","app_rank":"1","status":"2"}','pageNo'=>'','pageSize'=>'','search'=>'','sort'=>'-af_income', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"1","platform":"","parent":"","app_rank":"","status":""}','pageNo'=>'1','pageSize'=>'1000','search'=>'','sort'=>'-af_income', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"2","platform":"1","parent":"1","app_rank":"1","status":"2"}','pageNo'=>'','pageSize'=>'','search'=>'','sort'=>'-af_income', ]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/index',['filter'=> '{"ad_type":"3","platform":"1","parent":"1","app_rank":"1","status":"2"}','pageNo'=>'','pageSize'=>'','search'=>'','sort'=>'-af_income', ]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/campaign/update',[]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/update',['bannerid'=>53,'field'=>'status','value'=>1]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/update',['bannerid'=>53,'field'=>'app_rank','value'=>0]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/update',['bannerid'=>53,'field'=>'app_rank','value'=>3]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/update',['bannerid'=>53,'field'=>'app_rank','value'=>2]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/update',['bannerid'=>53,'field'=>'category','value'=> '1,513']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/update',['bannerid'=>53,'field'=>'category','value'=>'1']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
    
    public function testRank()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/campaign/rank');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testStatus()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/campaign/status');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }


    /**
     * 获取广告分类
     */
    public function testCategory()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/campaign/category');
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->get('/trafficker/campaign/category?ad_type=0');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testSelfUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_update',[]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_update',['campaignid'=>241,'field'=>'category','value'=>'1,513']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_update',['campaignid'=>241,'field'=>'app_rank','value'=>'7']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_update',['campaignid'=>241,'field'=>'app_rank','value'=>'1']);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testZoneList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/campaign/zone_list', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/zone_list', ['campaignid' => 3373]);
        $resp->seeJson(['res' => 0]);
    }

    public function testSelfCheck()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_check', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_check', ['campaignid' => 3373, 'status' => 11, 'approve_comment' => '']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_check', ['campaignid' => 5986, 'status' => 11, 'approve_comment' => 123]);
        $resp->seeJson(['res' => 5028]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_check', ['campaignid' => 5986, 'status' => 1,]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_check', ['campaignid' => 5986, 'status' => 15,]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_check', ['campaignid' => 5986, 'status' => 0,]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_check', ['campaignid' => 5986, 'status' => 0, 'app_rank'=>1, 'category' => 1]);
        $resp->seeJson(['res' => 0]);
    }

    public function testSelfIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_SELF_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_SELF_ACCOUNT_ID,
                'role_id' => TRAFFICKER_SELF_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_index', ['pageSize' => 1000, 'pageNo' => 1, 'filter' => '{"status":"0"}', 'sort' => 'operation_time', 'search' => 'sb']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_index', ['pageSize' => 1000, 'pageNo' => 1, 'filter' => '{"status":"0"}', 'sort' => '-operation_time', 'search' => 'sb']);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/campaign/self_index', ['pageSize' => 1000, 'pageNo' => 1, 'filter' => '{}', 'search' => '']);
        $resp->seeJson(['res' => 0]);
    }
}
