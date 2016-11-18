<?php
class BrokerKeywordsControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
                ->make(
                    [
                     'user_id' => DEFAULT_BROKER_USER_ID,
                     'default_account_id' => DEFAULT_BROKER_ACCOUNT_ID,
                     'role_id' => BROKER_ROLE]
                );
        $resp = $this->actingAs($user)->post('/broker/keywords/index',[]);
        $resp->seeJson(['res' => 5000]);
        $resp = $this->actingAs($user)->post('/broker/keywords/index',['campaignid'=> 408]);
        $resp->seeJson(['res' => 5003]);
        $resp = $this->actingAs($user)->post('/broker/keywords/index',['campaignid'=> 215]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

}