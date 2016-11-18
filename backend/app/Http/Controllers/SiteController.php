<?php

namespace App\Http\Controllers;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Models\AccountSubType;
use App\Models\Affiliate;
use App\Models\Broker;
use App\Models\Client;
use App\Models\Operation;
use App\Services\CampaignService;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use Auth;
use App\Components\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Account;
use App\Models\Message;
use Illuminate\Contracts\Pagination\Paginator;
use App\Models\PromotionActivity;
use Qiniu\Auth as QiniuAuth;
use Qiniu\json_decode;
use Qiniu\Storage\BucketManager;
use App\Models\Campaign;

class SiteController extends Controller
{
    /**
     * Auth认证，除了登录login/verificationCode函数不用认证，其他都要
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['login', 'captcha']]);
        $this->middleware('permission');
    }

    /**
     * Display a listing of the stat info.
     *
     * @return \Illuminate\Http\Response
     */
    public function platform()
    {
        $obj = Campaign::getPlatformLabels();
        return $this->success($obj, null, null);
    }

    /**
     * 检查是否登录
     *
     * @return \Illuminate\Http\Response
     */
    public function isLogin()
    {
        $user = Auth::user();
        return $this->success($this->returnUser($user));
    }

    /**
     * 登录
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | username | string | 登录名 |  | 是 |
     * | password | string | 密码，最短6位 |  | 是 |
     * | captcha | string | 验证码 |  | 是 |
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | user_id | integer | 用户ID |  | 是 |
     * | username | string | 登录名 |  | 是 |
     * | account_type | string | 账户类型 | ADMIN,MANAGER,TRAFFICKER,ADVERTISER | 是 |
     * | operation_list | string | 权限列表 |  | 是 |
     * | kind | integer | 1联盟 2自营 |  | 是 |
     */
    public function login(Request $request)
    {
        if (($ret = $this->validate($request, [
                'username' => 'required',
                'password' => 'required|min:6',
                'captcha' => 'required',
            ], [], User::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        // 检查验证码
        $captcha = Session::get('__captcha');
        if ($captcha != $request->input('captcha')) {
            return $this->errorCode(5012);
        }

        // 如果账号被暂停，则不能登录
        $user = User::where('username', $request->username)->first();
        if (!$user) {
            return $this->errorCode(5011);
        }
        if ($user->active != User::ACTIVE_TRUE) {
            return $this->errorCode(5010);
        }

        // @codeCoverageIgnoreStart
        $credentials = $request->only('username', 'password');
        $credentials['active'] = 1;

        if (!Auth::attempt($credentials, $request->has('remember'))) {
            return $this->errorCode(5011);
        }

        $user = Auth::user();
        $account = $user->account;
        $change = [
            'account_type' =>  $account->account_type,
            'main' => ($account->manager_userid === $user->user_id) ? 1 : 0,
            'user_id' =>  $user->user_id,
        ];
        Session::put('change', $change);
        //如果是媒体商
        if (Account::TYPE_TRAFFICKER == $account->account_type) {
            $this->setAffiliateKind($user);
        }
        
        return $this->success($this->returnUser($user));
        // @codeCoverageIgnoreEnd
    }

    /**
     * Logout
     * 注销
     *
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function logout()
    {
        Auth::logout();
        Session::forget('kind');
        return $this->success();
    }

    /**
     * 切换帐号
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function change(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required|integer',
            ], [], User::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $change = Session::get('change');
        $accountType = $change['account_type'];
        $main = $change['main'];
        $userId = $change['user_id'];

        $id = $request->id;
        $user = User::find($id);
        if (!$user) {
            return $this->errorCode(5000);
        }
        
        //如果是媒体商
        if (Account::TYPE_TRAFFICKER == $user->account->account_type) {
            $this->setAffiliateKind($user);
        }
        // @codeCoverageIgnoreStart
        if ($accountType == Account::TYPE_ADMIN) {
            // admin可登录任何帐号
            Auth::login($user);
            return $this->success($this->returnUser($user));
        } elseif ($accountType == Account::TYPE_MANAGER) {
            if ($main == 1) {
                // 如果主账户，则可以随意切换
                Auth::login($user);
                return $this->success($this->returnUser($user));
            } else {
                // 如果不是主账户，则只能切换到管辖的用户
                $account = $user->account;
                $loginUser = User::find($userId);

                if ($account->account_type == Account::TYPE_ADVERTISER) {
                    if ($this->can(Operation::MANAGER_SUPER_ACCOUNT_USER)) {
                        Auth::login($user);
                        return $this->success($this->returnUser($user));
                    } else {
                        $models = $loginUser->clients;
                    }
                } elseif ($account->account_type == Account::TYPE_TRAFFICKER) {
                    if ($this->can(Operation::MANAGER_TRAFFICKER_ACCOUNT_ALL)) {
                        Auth::login($user);
                        return $this->success($this->returnUser($user));
                    } else {
                        $models = $loginUser->affiliates;
                    }
                } elseif ($account->account_type == Account::TYPE_BROKER) {
                    if ($this->can(Operation::MANAGER_SUPER_ACCOUNT_USER)) {
                        Auth::login($user);
                        return $this->success($this->returnUser($user));
                    } else {
                        $models = $loginUser->brokers;
                    }
                }

                if (!isset($models) || !$models) {
                    return $this->errorCode(5004);
                }

                $accountIds = ArrayHelper::getColumn($models, 'account_id');
                $users = User::whereIn('default_account_id', $accountIds)->get()->toArray();
                $userIds = ArrayHelper::getColumn($users, 'user_id');
                if (!empty($userIds)) {
                    if (in_array($id, $userIds)) {
                        Auth::login($user);
                        return $this->success($this->returnUser($user));
                    }
                }
            }
        } elseif ($accountType == Account::TYPE_BROKER) {
            // 代理商只能登录所属自己的广告主 account_id->broker->clients->accounts->users
            $loginUser = User::find($userId);
            $broker = $loginUser->account->broker;
            $clients = $broker->clients;
            $accountIds = ArrayHelper::getColumn($clients, 'account_id');
            $users = User::whereIn('default_account_id', $accountIds)->get()->toArray();
            $userIds = ArrayHelper::getColumn($users, 'user_id');
            if (!empty($userIds)) {
                if (in_array($id, $userIds)) {
                    Auth::login($user);
                    return $this->success($this->returnUser($user));
                }
            }
        } elseif ($accountType == Account::TYPE_TRAFFICKER) {
            // 可以登录其他子账户
            if ($user->default_account_id == Auth::user()->default_account_id) {
                Auth::login($user);
                return $this->success($this->returnUser($user));
            }

            $loginUser = User::find($userId);
            // 代理商只能登录所属自己的广告主 account_id->broker->clients->accounts->users
            $affiliate = $loginUser->account->affiliate;
            $clients = $affiliate->clients;
            $accountIds = ArrayHelper::getColumn($clients, 'account_id');
            $users = User::whereIn('default_account_id', $accountIds)->get()->toArray();
            $userIds = ArrayHelper::getColumn($users, 'user_id');
            if (!empty($userIds)) {
                if (in_array($id, $userIds)) {
                    Auth::login($user);
                    return $this->success($this->returnUser($user));
                }
            }
        }
        // @codeCoverageIgnoreEnd

        return $this->errorCode(5004);
    }

    /**
     * 显示验证码，前端使用<img src='this_url'>方式访问
     *
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function captcha()
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
        header("Cache-Control: no-cache, must-revalidate");
        header('Content-Type: image/jpeg');
        return $builder->output();
    }

    /**
     * 修改用户密码
     *
     * @return \Illuminate\Http\Response
     */
    public function password(Request $request)
    {
        if ((($request->password != $request->password_confirmation) || $ret = $this->validate($request, [
                    'password_old' => 'required|min:6|max:16',
                    'password' => 'required|min:6|max:16',
                    'password_confirmation' => 'required|min:6|max:16',
                ], [], User::attributeLabels())) !== true) {
            return $this->errorCode(5013, $ret);
        }

        $user = Auth::user();
        if ($user->password === md5($request->password_old)) {
            $user->password = md5($request->password);  // @codeCoverageIgnore
        } else {    // @codeCoverageIgnore
            return $this->errorCode(5013);
        }
        LogHelper::info('user '.$user->user_id.' modify password');

        // @codeCoverageIgnoreStart
        if (!$user->save()) {
            LogHelper::error('user '.$user->user_id.' modify password failed');
            return $this->errorCode(5001);
        }
        return $this->success();
        // @codeCoverageIgnoreEnd
    }

    /**
     * 修改用户资料
     *
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request)
    {
        $user = Auth::user();
        if (($ret = $this->validate($request, [
                'contact_name' => 'required',
                'contact_phone' => 'required|digits:11'
            ], [], User::attributeLabels())) !== true) {
            return $this->errorCode(5014, $ret);
        }

        //判断与其他用户email是否相同
        $other = User::where('user_id', '<>', $user->user_id)
            ->where('email_address', $request->email_address)->get();

        if (count($other) != 0) {
            return $this->errorCode(5016);
        }

        //通过事务更新数据
        $transactionResult = DB::transaction(
            function () use ($request, $user) {
                $user = User::find($user->user_id);
                $user->contact_name = $request->contact_name;
                $user->email_address = $request->email_address;
                $user->contact_phone = $request->contact_phone;
                $user->qq = $request->qq;
                $user->save();
                // @codeCoverageIgnoreStart
                if (!$user->save()) {
                    DB::rollBack();
                    return false;
                }
                // @codeCoverageIgnoreEnd
                LogHelper::info('user '.$user->user_id.'modify user data');
                $account = $user->account;
                //广告主修改信息
                if ($account->isAdvertiser()) {
                    $clientId = $user->account->client->clientid;
                    //如果是主帐户
                    if ($user->user_id == $account->manager_userid) {
                        $isUpdate = Client::find($clientId);
                    }
                } elseif ($account->isTrafficker()) {//媒体商信息修改
                    $affiliateId = $account->affiliate->affiliateid;
                    $isUpdate = Affiliate::find($affiliateId);
                } elseif ($account->isBroker()) {//代理商修改信息
                    $brokerId = $account->broker->brokerid;
                    $isUpdate = Broker::find($brokerId);
                }

                if (isset($isUpdate)) {
                    $isUpdate->contact = $request->contact_name;
                    $isUpdate->email = $request->email_address;
                    if (!$isUpdate->save()) {
                        // @codeCoverageIgnoreStart
                        DB::rollBack();
                        return false;
                        // @codeCoverageIgnoreEnd
                    }
                }

                return true;
            }
        );

        if (!$transactionResult) {
            return $this->errorCode(5001);  // @codeCoverageIgnore
        }

        $user = User::where('user_id', $user->user_id)->first();

        return $this->success($this->returnUser($user));
    }

    /**
     * 获取用户资料
     *
     * @return \Illuminate\Http\Response
     */
    public function profileView()
    {
        $user = Auth::user();
        return $this->success(
            [
                'contact_name' => $user->contact_name,
                'email_address' => $user->email_address,
                'contact_phone' => $user->contact_phone,
                'qq' => $user->qq,
            ]
        );
    }

    /**
     * 消息列表
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function noticeList(Request $request)
    {
        $status = ArrayHelper::getRequiredIn(Message::getMessageStatusLabels());
        if (($ret = $this->validate($request, [
                'status' => "integer|in:{$status}",
            ], [], Message::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认10
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1
        $status = $request->input('status');
        $search = $request->input('search');

        $select = Message::where('target_userid', Auth::user()->user_id)
            ->where('type', Message::TYPE_WEB)
            ->whereIn('status', isset($status) ? [$status] : [Message::STATUS_SENT, Message::STATUS_READ]);
        if (!is_null($search) && trim($search)) {
            $select->where('title', 'like', '%'. $search .'%');
            if (preg_match("/[\x7f-\xff]/", $search)) {
                $select->orWhere('content', 'like', '%'. StringHelper::unicodeEncode($search) .'%');
            }
        }
        $select->orderBy('create_time', 'desc');
        $messages = $select->paginate($pageSize, ['*'], 'page', $pageNo);
        if (!$messages) {
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        $total = $messages->total();//统计总共条数
        $notes = array();
        $activities = array();
        if (count($messages) > 0) {
            foreach ($messages as $row) {
                $content = json_decode($row->content);
                if ($content->class == 'note') {
                    $notes[] = array(
                        'id' => $row->id,
                        'title' => $row->title,
                        'create_time' => $row->create_time->toDateTimeString(),
                        'content' => $content,
                        'type' => Message::NOTE,
                        'status' => $row->status,
                    );
                } elseif ($content->class == 'activity') {
                    $activities[] = array(
                        'id' => $row->id,
                        'title' => $row->title,
                        'create_time' => $row->create_time->toDateTimeString(),
                        'content' => $content,
                        'type'=> Message::ACTIVITY,
                        'status' => $row->status,
                    );
                }
            }
            $messages = array_merge($notes, $activities);
        }

        return $this->success(
            null,
            [
                'pageSize' => $pageSize,
                'count' => $total,
                'pageNo' => $pageNo,
            ],
            $messages
        );
    }

    /**
     * 删除消息
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function noticeStore(Request $request)
    {
        $status = ArrayHelper::getRequiredIn(Message::getMessageStatusLabels());
        if (($ret = $this->validate($request, [
                'ids' => 'required',
                'status' => "required|integer|in:{$status}",
            ], [], Message::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $ids = $request->input('ids');
        $status = $request->input('status');

        //如果ids结尾带",",去出结尾逗号。
        if (substr($ids, strlen($ids) - 1) == ",") {
            $ids = substr($ids, 0, strlen($ids) - 1);
        }
        $ids = explode(',', $ids);//转换为数组

        LogHelper::info('Message'. implode(',', $ids) .'update status '. $status);
        $result = Message::whereIn('id', $ids)->update(['status' => $status]);
        if (!$result) {
            // @codeCoverageIgnoreStart
            return $this->errorCode(5002);
            // @codeCoverageIgnoreEnd
        }

        return $this->success();
    }

    /**
     * 预览优惠活动
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function activity(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required',
            ], [], Message::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $id = $request->input('id');//获取优惠ID

        $active = PromotionActivity::find($id);
        if (!$active) {
            return $this->errorCode(5002);
        }

        return $this->success(
            [
                'title' => $active->title,
                'imageurl' => $active->imageurl,
                'startdate' => $active->startdate,
                'enddate' => $active->enddate,
                'status' => $active->status,
                'content' => $active->content,
            ]
        );
    }

    /**
     * 左侧菜单
     *
     * @return \Illuminate\Http\Response
     */
    public function nav()
    {
        $accountType = $this->getAccountType();
        $nav = Config::get('biddingos.nav.' . strtolower($accountType));
        //$navFrontBase = Config::get('biddingos.nav_front_base');
        $list = [];
        foreach ($nav as $item) {
            // 如果帐号管理，非主账户不可见
            if ((strpos($item['url'], 'account/index') !== false) && !$this->isAccountMain()) {
                continue;
            }
            // 如果有operation配置，则需要当前帐号有对应的权限
            if (isset($item['operation']) && !$this->can($item['operation'])) {
                continue;
            }
            //$item['url'] = $navFrontBase . $item['url'];
            $list[] = $item;
        }
        return $this->success(null, null, $list);
    }

    /**
     * 2.6.4图片上传，返回Token
     *
     * @return \Illuminate\Http\Response
     */
    public function qiniuToken()
    {
        $accessKey = Config::get('filesystems.qiniu.accessKey');
        $secretKey = Config::get('filesystems.qiniu.secretKey');

        $auth = new QiniuAuth($accessKey, $secretKey);

        $bucket = Config::get('filesystems.qiniu.bucket');
        $upToken = $auth->uploadToken($bucket);

        return $this->success(['token' => $upToken], null, null);
    }

    /**
     * 2.6.5 Qiniu图片删除
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function deleteFile(Request $request)
    {
        if (($ret = $this->validate($request, [
                'imgName' => 'required|string',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $accessKey = Config::get('filesystems.qiniu.accessKey');
        $secretKey = Config::get('filesystems.qiniu.secretKey');

        $auth = new QiniuAuth($accessKey, $secretKey);
        $bucketMgr = new BucketManager($auth);

        $bucket = Config::get('filesystems.qiniu.bucket');
        $key = $request->input('imgName');
        $err = $bucketMgr->delete($bucket, $key);
        LogHelper::info('delete picture '.$key .' from qiniu');
        //Qiniu返回null表成功
        if ($err === null) {
            // @codeCoverageIgnoreStart
            return $this->success();
            // @codeCoverageIgnoreEnd
        } else {
            /* 备用，后续能需要Qiniu提示信息
             * $code = $err->code();
            $message = $err->message();*/
            return $this->errorCode(5015);
        }
    }

    /**
     * 获取二级类型和权限
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function accountSubType(Request $request)
    {
        // 如果有账号管理权限，则可以看到所有的媒体商
        $type = strtoupper($request->get('type'));
        if (!in_array($type, array_keys(Account::getTypeStatusLabels()))) {
            return $this->errorCode(5000);
        }

        $list = [];
        $models = AccountSubType::whereMulti(['account_type' => $type])->get();
        foreach ($models as $model) {
            $item['account_sub_type_id'] = $model['id'];
            $item['name'] = $model['name'];
            $item['default_operation'] = $model->defaultRole->operation_list;

            $list[] = $item;
        }

        return $this->success(null, null, $list);
    }

    /**
     * 获取权限名称和类型
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function operation(Request $request)
    {
        // 如果有账号管理权限，则可以看到所有的媒体商
        $type = strtoupper($request->get('type'));
        if (!in_array($type, array_keys(Account::getTypeStatusLabels()))) {
            return $this->errorCode(5000);
        }

        $list = [];
        $models = Operation::where('account_type', '=', $type)
            ->where(function ($query) {
                $query->where('id', '<', Operation::HIDDEN_START)
                    ->orWhere('id', '>', Operation::HIDDEN_END);
            })
            ->get();

        foreach ($models as $model) {
            $item['id'] = $model['id'];
            $item['name'] = $model['name'];
            $item['label'] = $model['description'];

            $list[] = $item;
        }

        return $this->success(null, null, $list);
    }

    /**
     * 账号切换
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | kind | integer | 账号类型 | 1联盟 2自营 | 是 |
     *
     * @param  \Illuminate\Http\Request $request
     */
    public function changeKind(Request $request)
    {
        //先判断是否登录，没有登录则不允许切换
        $this->isLogin();

        if (($ret = $this->validate($request, [
            'kind' => 'required|integer|in:1,2',
        ], [], Affiliate::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $account_type = Auth::user()->account->account_type;
        if ('TRAFFICKER' != $account_type) {
            return $this->errorCode(5203);
        }
        
        $affiliateKind = Auth::user()->account->affiliate->kind;
        $kind = $request->get('kind');
        $currentKind = Session::get('kind');
        
        //只有联盟+运营才能切换，否则不能切换
        if ($affiliateKind != Affiliate::KIND_ALL) {
            return $this->errorCode(5206);
        }

        if (!empty($currentKind)) {
            if ($kind == $currentKind) {
                $code = (Affiliate::KIND_ALLIANCE == $kind) ? 5205 : 5204;
                return $this->errorCode($code);
            }
        } else {
            //联盟+自营模式，默认是以自营模式登录
            if ($kind == Affiliate::KIND_SELF) {
                //不能转到自营模式
                return $this->errorCode(5204);
            }
        }
        
        //允许切换
        Session::put('kind', $kind);
        $user = Auth::user();
        return $this->success($this->returnUser($user));
    }
    
    
    public static function attributeLabels()
    {
        return [
            'imgName' => '图片名称',
        ];
    }

    private function returnUser($user)
    {
        if (!$user) {
            return false;
        }
        
        $currentKind = intval(Session::get('kind'));
        $accountType = $user->account->account_type;
        if ($accountType == Account::TYPE_TRAFFICKER) {
            $deliveryType = $user->account->affiliate->delivery_type;
        }
        if ($accountType == 'TRAFFICKER' || $accountType == 'ADVERTISER' || $accountType == 'BROKER') {
            if ($accountType == 'TRAFFICKER' && $user->account->affiliate->kind >= Affiliate::KIND_SELF) {
                $mode = $user->account->affiliate->mode;
                $kind = !empty($currentKind) ? $currentKind : Affiliate::KIND_SELF;
                $kindType = $user->account->affiliate->kind;
                $deliveryType = $user->account->affiliate->delivery_type;
            } elseif ($accountType == 'ADVERTISER' && $user->account->client->affiliateid > 0) {
                $kind = Affiliate::KIND_SELF;
                $mode = Affiliate::MODE_PROGRAM_DELIVERY_STORAGE;
            } elseif ($accountType == 'BROKER' && $user->account->broker->affiliateid > 0) {
                $kind = Affiliate::KIND_SELF;
            }
        }
        return [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'account_type' => $user->account->account_type,
            'contact_name' => $user->contact_name,
            'operation_list' => $user->role ? $user->role->operation_list : null,
            'kind' => isset($kind) ? $kind : Affiliate::KIND_ALLIANCE,
            'delivery_type' => isset($deliveryType) ? $deliveryType : Affiliate::DELIVERY_TYPE_APPLICATION,
            'mode' => isset($mode) ? $mode : Affiliate::MODE_PROGRAM_DELIVERY_STORAGE,
            'kindType' => isset($kindType) ? $kindType : Affiliate::KIND_ALLIANCE,
        ];
    }
    
    private function setAffiliateKind(User $user)
    {
        $kindType = $user->account->affiliate->kind;
        $kind = (Affiliate::KIND_ALLIANCE == $kindType) ? $kindType : Affiliate::KIND_SELF;
        Session::put('kind', $kind);
    }
}
