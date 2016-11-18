<?php
class TraffickerKeywordsControllerTest extends TestCase
{
    /**
     * 关键字查询。
     */
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/keywords/index', []);
        $resp->seeJson(['res' => 5000]);

        $resp = $this->actingAs($user)->post('/trafficker/keywords/index', ['campaignid'=>343]);
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/trafficker/keywords/index', ['campaignid'=>238]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}