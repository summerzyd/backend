<?php

class AdvertiserZoneControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/advertiser/zone/index', ['campaignid' => 0]);
        $resp->seeJson(['res' => 0]);
        $resp = $this->actingAs($user)->post('/advertiser/zone/index', ['campaignid' => 1343]);
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}