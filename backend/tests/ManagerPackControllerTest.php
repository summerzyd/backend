<?php

class ManagerPackControllerTest extends TestCase
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

        $resp = $this->actingAs($user)->post('/manager/pack/index');
        $resp->seeJson(['res' => 0]);

        $resp = $this->actingAs($user)->post('/manager/pack/index', ['pageNo' => DEFAULT_PAGE_NO, 'pageSize' => DEFAULT_PAGE_SIZE, 'search' => 'lei']);
        $resp->seeJson(['res' => 0, ]);
    }

    public function testClientPackage()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/pack/client_package');
        $resp->seeJson(['res' => 5000]);


        $resp = $this->actingAs($user)->post('/manager/pack/client_package', ['campaignid' => 390]);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testDeliveryAffiliate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);

        $resp = $this->actingAs($user)->post('/manager/pack/delivery_affiliate');
        $resp->seeJson(['res' => 5000]);


        $resp = $this->actingAs($user)->post('/manager/pack/delivery_affiliate', ['attach_id' => 1029]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/pack/delivery_affiliate', ['attach_id' => 1030]);
        $resp->seeJson(['res' => 0, ]);
    }
    public function testUpdate()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_MANAGER_USER_ID,
                'default_account_id' => DEFAULT_MANAGER_ACCOUNT_ID,
                'role_id' => MANAGER_ROLE,
                'agencyid' => AGENCY_ID,
            ]);
        $resp = $this->actingAs($user)->post('/manager/pack/update');
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/manager/pack/update', ['field' => 'channel', 'value' => 'beauty' , 'attach_id' => 1029]);
        $resp->seeJson(['res' => 0, ]);

        $resp = $this->actingAs($user)->post('/manager/pack/update', ['field' => 'status', 'value' => '1' , 'attach_id' => 1189]);
        $resp->seeJson(['res' => 5003, ]);

    }

}