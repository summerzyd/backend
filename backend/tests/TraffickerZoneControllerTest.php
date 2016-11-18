<?php

class TraffickerZoneControllerTest extends TestCase
{
    /**
     * 广告位列表
     */
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/index', ['ad_type' => -1]);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/index', ['ad_type' => 0, 'sort' => 'status', 'search' => 1]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/index', ['ad_type' => 0, 'sort' => '-status', 'search' => 1]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/index', ['ad_type' => 0, 'search' => 1]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/index', ['ad_type' => 1, 'sort' => 'status', 'search' => 1]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/index', ['ad_type' => 2, 'sort' => 'status', 'search' => 1]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/index', ['ad_type' => 3, 'sort' => 'status', 'search' => 1]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/index', ['ad_type' => 71, 'sort' => 'status', 'search' => 1]);
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/store', []);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_id'=>'ssdf']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_id'=>'31']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'guanggao']);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'guanggao','ad_type'=>0,'type'=>0]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'gaong','ad_type'=>1,'type'=>2,'ad_refresh'=> 10]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'gaong','ad_type'=>1,'type'=>2,'ad_refresh'=> 0]);
        $resp->seeJson(['res' => 5055]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'gaong','ad_type'=>1,'type'=>2,'ad_refresh'=> 0]);
        $resp->seeJson(['res' => 5055]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'Feeds广告位1','ad_type'=>2,'type'=>4]);
        $resp->seeJson(['res' => 5055]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'呵呵第3位','ad_type'=>0,'type'=>0,'position'=>3,'platform'=>1,'rank_limit'=>1,'category'=>'2','description'=>'','listtypeid'=>1,'ad_refresh'=>0]);
        $resp->seeJson(['res' => 5055]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'呵呵第3位','ad_type'=>0,'type'=>1,'position'=>3,'platform'=>1,'rank_limit'=>1,'category'=>'2','description'=>'','listtypeid'=>1,'ad_refresh'=>0]);
        $resp->seeJson(['res' => 5055]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'test','ad_type'=>1,'type'=>2,'zone_id'=>1541,'ad_refresh'=> 0]);
        $resp->seeJson(['res' => 5055]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'gaong1','ad_type'=>1,'type'=>2,'zone_id'=>1541,'ad_refresh'=> 0]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/store', ['zone_name'=>'gaong1'.str_random(5),'ad_type'=>1,'type'=>2,'ad_refresh'=> 0]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
    /**
     * 启用，停用广告位
     */
    public function testCheck()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/check', ['zone_id' => 1, 'action' => -1]);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/check', ['zone_id' => -1, 'action' => 0]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/check', ['zone_id' => 1, 'action' => 0]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/check', ['zone_id' => 1, 'action' => 1]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 分类添加，修改，删除
     */
    public  function testCategory()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/category_store', ['name' => '呵呵', 'parent' => 1]);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/category_store', ['name' => '影音视听' . str_random(5), 'parent' => 1, 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/category_store', ['category' => 1, 'name' => '影音视听', 'parent' => 1, 'ad_type' => 0]);
        $resp->seeJson(['res' => 5056]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 查询模块列表
     */
    public function testModelList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/zone/module_list?ad_type=0');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 添加，删除，修改模块
     */
    public function testModuleStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);
        $resp = $this->actingAs($user)->post('/trafficker/zone/module_store',['listtypeid' => 1, 'name' => '呵呵', 'type' => 0, 'ad_type' => 0]);
        $resp->seeJson(['res' => 5058]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/module_store',['listtypeid' => 1, 'name' => '呵', 'type' => 1, 'ad_type' => 0]);
        $resp->seeJson(['res' => 5057]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/module_store',['listtypeid' => 1, 'name' => '呵he',  'type' => 3]);
        $resp->seeJson(['res' => 5000]);
        
        $resp = $this->actingAs($user)->post('/trafficker/zone/module_store',['id'=>464,'listtypeid' => 4, 'name' => '呵he',  'type' => 1, 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/module_store',['id'=>464,'listtypeid' => 4, 'name' => '呵he12',  'type' => 1, 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/zone/module_store',['id'=>464,'listtypeid' => 4, 'name' => '呵he1',  'type' => 1, 'ad_type' => 0]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testAdType()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);
        $resp = $this->actingAs($user)->get('/trafficker/zone/ad_type',[]);
        $resp->seeJson(['res' => 0]);
    }
}