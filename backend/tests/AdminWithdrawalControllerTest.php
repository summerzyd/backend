<?php

class AdminWithdrawalControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_ADMIN_USER_ID,
                'default_account_id' => DEFAULT_ADMIN_ACCOUNT_ID,
                'role_id' => ADMIN_ROLE,
            ]);

        $resp = $this->actingAs($user)->post('/admin/withdrawal/index');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/admin/withdrawal/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/admin/withdrawal/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'uc']);
        $resp->seeJson(['res' => 0, ]);

        $this->assertEquals(200, $resp->response->status());
    }
}