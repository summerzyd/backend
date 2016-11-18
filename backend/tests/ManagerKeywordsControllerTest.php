<?php
class ManagerKeywordsControllerTest extends TestCase
{
    public function testStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        //缺省一个参数
        $reqData = [
            'keyword'    => 'xxx',
            'price_up'   => '999',
        ];
        $resp = $this->actingAs($user)->post('/manager/keyword/store', $reqData);
        $resp->seeJson(['res' => 5000, ]);
        //完整参数，验证指定id的keyword不存在
        $resp = $this->actingAs($user)->post('/manager/keyword/store', [
            'id' => -999,
            'campaignid' => 5,
            'keyword' => '旅游',
            'price_up' => 0.1,
        ]);
        $resp->seeJson(['res' => 5101, ]);
        //完整参数，验证指定id的keyword不存在
        $resp = $this->actingAs($user)->post('/manager/keyword/store', [
            'campaignid' => 160,
            'keyword' => '旅游',
            'price_up' => 0.1,
        ]);
        $resp->seeJson(['res' => 5100, ]);
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

        //缺省一个参数
        $resp = $this->actingAs($user)->post('/manager/keyword/index', []);
        $resp->seeJson(['res' => 5000, ]);
        $resp = $this->actingAs($user)->post('/manager/keyword/index', ['campaignid'=> 2426]);
        $resp->seeJson(['res' => 0, ]);
        $this->assertEquals(200, $resp->response->status());
    }

    public function testDelete()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        //缺省一个参数
        $resp = $this->actingAs($user)->post('/manager/keyword/delete', []);
        $resp->seeJson(['res' => 5000, ]);
        //指定id的关键字不存在
        $resp = $this->actingAs($user)->post('/manager/keyword/delete', [
            'id' => -1,
        ]);
        $resp->seeJson(['res' => 5101, ]);
        //没有权限删除
        $resp = $this->actingAs($user)->post('/manager/keyword/delete', [
            'id' => 6,
        ]);
        $resp->seeJson(['res' => 5004, ]);
        $this->assertEquals(200, $resp->response->status());
    }
}
