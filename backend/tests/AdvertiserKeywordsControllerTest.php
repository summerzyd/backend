<?php

/**
 * Created by PhpStorm.
 * User: a2htray
 * Date: 2016/2/16
 * Time: 12:15
 */
class AdvertiserKeywordsControllerTest extends TestCase
{
    public function testStore()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
            ]);

        //缺省一个参数
        $reqData = [
            'keyword'    => 'xxx',
            'price_up'   => '999',
        ];

        $resp = $this->actingAs($user)->post('/advertiser/keywords/store', $reqData);
        $resp->seeJson(['res' => 5000, ]);

        //完整参数，验证指定id的keyword不存在
        $resp = $this->actingAs($user)->post('/advertiser/keywords/store', [
            'id' => -999,
            'campaignid' => 5,
            'keyword' => '旅游',
            'price_up' => 0.1,
        ]);
        $resp->seeJson(['res' => 5101, ]);

        //完整参数，验证指定id的keyword不存在
        $resp = $this->actingAs($user)->post('/advertiser/keywords/store', [
            'campaignid' => 160,
            'keyword' => '旅游',
            'price_up' => 0.1,
        ]);
        $resp->seeJson(['res' => 0, ]);

        //完整参数，新增关键字
        $resp = $this->actingAs($user)->post('/advertiser/keywords/store', [
            'campaignid' => 160,
            'keyword' => strtotime('now'),
            'price_up' => 0,
        ]);
        $resp->seeJson(['res' => 5042, ]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
            ]);

        //缺省一个参数
        $resp = $this->actingAs($user)->post('/advertiser/keywords/index', []);
        $resp->seeJson(['res' => 5000, ]);

        //完整参数
        /*$resp = $this->actingAs($user)->post('/advertiser/keywords/index', [
            'campaignid' => 1,
        ]);
        $resp->seeJson(['res' => 0, ]);*/

        $this->assertEquals(200, $resp->response->status());
    }

    public function testDelete()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
            ]);

        //缺省一个参数
        $resp = $this->actingAs($user)->post('/advertiser/keywords/delete', []);
        $resp->seeJson(['res' => 5000, ]);

        //指定id的关键字不存在
        $resp = $this->actingAs($user)->post('/advertiser/keywords/delete', [
            'id' => -1,
        ]);
        $resp->seeJson(['res' => 5101, ]);

        //没有权限删除
        $resp = $this->actingAs($user)->post('/advertiser/keywords/delete', [
            'id' => 6,
        ]);
        $resp->seeJson(['res' => 5004, ]);

        $this->assertEquals(200, $resp->response->status());
    }
}
