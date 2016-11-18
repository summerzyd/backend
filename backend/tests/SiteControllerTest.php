<?php

use Gregwar\Captcha\CaptchaBuilder;

class SiteControllerTest extends TestCase
{
    public function testIsLogin()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_USER_ID,
            'default_account_id' => DEFAULT_ACCOUNT_ID,
            'role_id' => ADVERTISER_ROLE
        ]);

        $resp = $this->actingAs($user)->get('/site/is_login');
        $resp->seeJson([
            'res' => 0,
        ]);
        
        $this->assertEquals(200, $resp->response->status());
    }

    public function testCaptchaDepends()
    {
        $charset = 'abcdefghkmnpqrstuvwxyz23456789';
        $phrase = '';
        $chars = str_split($charset);

        for ($i = 0; $i < 4; $i++) {
            $phrase .= $chars[array_rand($chars)];
        }

        $builder = new CaptchaBuilder($phrase);
        $builder->setMaxBehindLines(1);
        $builder->setMaxFrontLines(1);
        $builder->setInterpolation(false);
        $builder->setDistortion(false);
        $builder->setIgnoreAllEffects(true);
        $builder->build(100);
        $phrase = $builder->getPhrase();
        Session::set('__captcha', $phrase);
        return $phrase;
    }

    /**
     * @depends testCaptchaDepends
     */
    public function testLogin($phrase)
    {
        //缺省参数
        $this->post('/site/login',  [
            'password' => 'xxxx',
            'captcha' => 'xxxx',
        ])->seeJson([
                'res' => 5000,
        ]);

        //完整参数，验证码错误
        $this->post('/site/login',  [
            'username' => 'uc浏览器',
            'password' => '123456',
            'captcha' => 'xxxx',
        ])->withSession(['__captcha' => $phrase])->seeJson([
            'res' => 5012,
        ]);

    }

    /**
     * @depends testCaptchaDepends
     */
    public function testChange($phrase)
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_USER_ID,
            'default_account_id' => DEFAULT_ACCOUNT_ID,
            'role_id' => ADVERTISER_ROLE
        ]);

        //缺省参数
        $resp = $this->actingAs($user)->post('/site/change',  [
            'a' => 'xxxx',
        ]);
        $resp->seeJson([ 'res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/site/change',  [
            'id' => -1,
        ]);
        $resp->seeJson(['res' => 5000, ]);

        $resp = $this->actingAs($user)->post('/site/change',  [
            'id' => DEFAULT_USER_ID,
        ]);
        $resp->seeJson(['res' => 5004, ]);
    }

    public function testCaptcha()
    {
        //$resp = $this->get('/site/captcha');
        //$this->assertContains('img', $resp->response->getContent());
    }

    public function testProfile()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_USER_ID,
            'default_account_id' => DEFAULT_ACCOUNT_ID,
            'role_id' => ADVERTISER_ROLE
        ]);

        //缺省参数
        $resp = $this->actingAs($user)->post('/site/profile', []);
        $resp->seeJson([ 'res' => 5014, ]);

        //完整参数，但手机号格式错误
        $resp = $this->actingAs($user)->post('/site/profile', [
            'contact_name' => '田君君统一',
            'contact_phone' => '1111'
        ]);
        $resp->seeJson([ 'res' => 5014, ]);

        //完整参数，email和其它用户相同
        $resp = $this->actingAs($user)->post('/site/profile', [
            'contact_name' => '田君君统一',
            'contact_phone' => '18888888888',
            'email_address' => 'testbiddingos@qq.com',
        ]);
        $resp->seeJson([ 'res' => 5016, ]);

        //完整参数，正常执行
        $resp = $this->actingAs($user)->post('/site/profile', [
            'contact_name' => '田君君统一',
            'contact_phone' => '18888888888',
            'email_address' =>  strtotime("now").'@test.com',
            'qq' => '10086',
        ]);
        $resp->seeJson([ 'res' => 0, ]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 测试消息列表
     */
    public function testNoticeList()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE
            ]);

        $resp = $this->actingAs($user)->post('/site/notice_list', ['status' => 5]);
        $resp->seeJson([ 'res' => 5000]);

        $resp = $this->actingAs($user)->post('/site/notice_list', ['status' => 1]);
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->post('/site/notice_list', ['status' => 0]);
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->post('/site/notice_list', ['status' => 1, 'search' => '测']);
        $resp->seeJson([ 'res' => 0]);

        $resp = $this->actingAs($user)->post('/site/notice_list');
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 删除消息
     */
    public function testNoticeStore()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID,
            'role_id' => ADVERTISER_ROLE]);

        $resp = $this->actingAs($user)->post('/site/notice_store', ['status' => 5]);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/site/notice_store', ['status' => 0,'ids' => '']);
        $resp->seeJson([ 'res' => 5000]);
        $resp = $this->actingAs($user)->post('/site/notice_store', ['status' => 0,'ids' => '0']);
        $resp->seeJson([ 'res' => 5002]);
        $resp = $this->actingAs($user)->post('/site/notice_store', ['status' => 0,'ids' => '1']);
        $resp->seeJson([ 'res' => 0]);
        $resp = $this->actingAs($user)->post('/site/notice_store', ['status' => 0,'ids' => '2,']);
        $resp->seeJson([ 'res' => 0]);
        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 获取优惠活动
     */
    public function testActivity()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID,          'role_id' => ADVERTISER_ROLE]);

        $resp = $this->actingAs($user)->get('/site/activity');
        $resp->seeJson([ 'res' => 5000]);

        $resp = $this->actingAs($user)->get('/site/activity?id=-1');
        $resp->seeJson([ 'res' => 5002]);

        $resp = $this->actingAs($user)->get('/site/activity?id=1');
        $resp->seeJson([ 'res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 左侧菜单
     */
    public function testNav()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID,          'role_id' => ADVERTISER_ROLE]);
        $resp = $this->actingAs($user)->get('/site/nav');
        $resp->seeJson([ 'res' => 0, ]);
        $this->assertEquals(200, $resp->response->status());
    }
    /**
     * 2.6.4图片上传，返回Token
     */
    public function testQiniuToken()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID,          'role_id' => ADVERTISER_ROLE]);

        $resp = $this->actingAs($user)->get('/site/qiniu_token');
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testDeleteFile()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_USER_ID,
            'default_account_id' => DEFAULT_ACCOUNT_ID,
            'role_id' => ADVERTISER_ROLE
        ]);

        //缺省参数
        $resp = $this->actingAs($user)->post('/site/delete_file', []);
        $resp->seeJson(['res' => 5000]);

        //缺省参数
        $resp = $this->actingAs($user)->post('/site/delete_file', [
            'imgName' => 'xxx.jpg',
        ]);
        $resp->seeJson(['res' => 5015]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 1.4.1 获取用户资料 site/profile_view
     */
    public function testProfileView()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID    ,      'role_id' => ADVERTISER_ROLE]);

        $resp = $this->actingAs($user)->get('/site/profile_view');
        $resp->seeJson(['res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    /**
     * 1.5 修改密码 site/password
     */
    public function testPassword()
    {
        $user = factory('App\Models\User')->make([
            'user_id' => DEFAULT_USER_ID,
            'default_account_id' => DEFAULT_ACCOUNT_ID,
            'role_id' => ADVERTISER_ROLE,
            'password' => 'e10adc3949ba59abbe56e057f20f883e'
        ]);

        //验证参数格式错误
        $resp = $this->actingAs($user)->post('/site/password', [
            'password_old' => '23',
            'password' => '46',
            'password_confirmation' => '46',
        ]);
        $resp->seeJson(['res' => 5013]);

        //验证参数格式正确，但密码和确认密码不一致
        $resp = $this->actingAs($user)->post('/site/password', [
            'password_old' => '111111',
            'password' => '222222',
            'password_confirmation' => '333333',
        ]);
        $resp->seeJson(['res' => 5013]);

        //验证密码和数据库的密码不一致
        $resp = $this->actingAs($user)->post('/site/password', [
            'password_old' => '1234567',
            'password' => '222222',
            'password_confirmation' => '222222',
        ]);
        $resp->seeJson(['res' => 5013]);

        /*$resp = $this->actingAs($user)->post('/site/password', [
            'password_old' => '123456',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $resp->seeJson(['res' => 0]);*/

        $this->assertEquals(200, $resp->response->status());
    }

    /*
     * 2.6.1 获取推广目标平台 advertiser/campaign/platform
     */
    public function testPlatform()
    {
        $user = factory('App\Models\User')->make(['user_id' => DEFAULT_USER_ID, 'default_account_id' => DEFAULT_ACCOUNT_ID,          'role_id' => ADVERTISER_ROLE]);

        $resp = $this->actingAs($user)->get('/site/platform');
        $resp->seeJson([ 'res' => 0]);

        $this->assertEquals(200, $resp->response->status());
    }

    public function testAccountSubType()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,
                'role_id' => ADVERTISER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/site/account_sub_type?type=MANAGER');
        $resp->seeJson(['res' => 0, ]);
    }

    public function testOperation()
    {
        $user = factory('App\Models\User')
            ->make([
                'user_id' => DEFAULT_USER_ID,
                'default_account_id' => DEFAULT_ACCOUNT_ID,          'role_id' => ADVERTISER_ROLE
            ]);

        $resp = $this->actingAs($user)->get('/site/operation?type=MANAGER');
        $resp->seeJson(['res' => 0, ]);
    }
}
