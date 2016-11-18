<?php

class AdminAgencyControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_ADMIN_USER_ID,
                'default_account_id' => DEFAULT_ADMIN_ACCOUNT_ID,
                'role_id' => ADMIN_ROLE,
            ]);

        $resp = $this->actingAs($user)->get('/admin/agency/index');
        $resp->seeJson(['res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }
}