<?php

class AdvertiserInvoiceControllerTest extends TestCase
{
    public function testStore()
    {
        //不合格的数据
        $reqData = [
            'ids' => '1,2,3',
            'title' => '测试用例开票申请',
            'prov' => '青海',
            'city' => '辖西宁',
            'dist' => '',
            'address' => '大青海的海',
            'type' => 3,
            'receiver' => '测试用例receiver',
            'tel' => '19999999999',
        ];

        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/advertiser/invoice/store', $reqData);
        $resp->seeJson(['res' => 5000, ]);
        
        //已提交到的发票申请
        $reqData['ids'] = '264';
        $reqData['type'] = 2;
        $resp = $this->actingAs($user)->post('/advertiser/invoice/store', $reqData);
        $resp->seeJson(['res' => 5031, ]);

        //正确的数据
        $reqData['ids'] = ''.rand(10000, 99999);
        $resp = $this->actingAs($user)->post('/advertiser/invoice/store', $reqData);
        $resp->seeJson(['res' => 0, ]);

        

        
        $this->assertEquals(200, $resp->response->status());
    }
}
