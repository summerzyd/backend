<?php

class TraffickerBalanceControllerTest extends TestCase
{
    /*
     * 提款明细
     */
    public function testWithdraw()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID,
                'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/balance/withdraw');
        $resp->seeJson(['res' => 0, ]);

    }
    /*
   * 结算明细
   */
    public function testSettlement()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID, 'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/balance/settlement');
        $resp->seeJson(['res' => 0, ]);

    }
    /*
   * 收入明细
    */
    public function testIncome()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID, 'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/balance/income');
        $resp->seeJson(['res' => 0, ]);
    }
    /*获取媒体商最大可提取金额
    * *
    */
    public function testDrawBalance()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID, 'role_id' => TRAFFICKER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/trafficker/balance/draw_balance');
        $resp->seeJson(['res' => 0, ]);
    }
    /*
    public function testDraw()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_TRAFFICKER_USER_ID,
                'default_account_id' => DEFAULT_TRAFFICKER_ACCOUNT_ID
            ]);

        $resp = $this->actingAs($user)->post('/trafficker/balance/draw', ['payee' => '', 'bank' => '', 'bank_account' => 'not email' ,'money' => '']);
        $resp->seeJson(['res' => 5000]);
    }*/
}