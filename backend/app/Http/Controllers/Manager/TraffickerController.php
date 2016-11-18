<?php

namespace App\Http\Controllers\Manager;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Models\Account;
use App\Models\Affiliate;
use App\Models\AffiliateExtend;
use App\Models\Balance;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\ClientVersion;
use App\Models\CrypterKey;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneListType;
use App\Services\AccountService;
use App\Services\CampaignService;
use App\Services\ZoneService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Components\Config;
use App\Models\AccountSubType;

class TraffickerController extends Controller
{
    /**
     * 获取用户列表
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | pageSize | integer | 页面 |  | 否 |
     * | pageNo | integer | 当前页码 |  | 否 |
     * | sort | string | 排序 | status 升序 -status降序，降序在字段前加- | 否 |
     * | search | string | 排序 |  | 否 |
     * | filter | string | 筛选 | Json格式：{"revenue_type":3, "clients_status":1,"creator_uid":2} | 否 |
     * |  |  |  | revenue_type 计费类型 clients_status状态 creator_uid 销售顾问ID |  |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |  | integer | 媒体商ID |  | 是 |
     * | user_id |  | integer | 媒体商用户ID |  | 是 |
     * | date_created |  | string | 创建时间 |  | 是 |
     * | name |  | string | 媒体商名称 |  | 是 |
     * | brief_name |  | string | 媒体商简称 |  | 是 |
     * | app_platform |  | integer | 平台类型 | 7=1+2+4 1到15之间 | 否 |
     * | income_rate |  | decimal | 收入分成 |  | 否 |
     * | delivery |  | json string | 投放类型 | [{"ad_type":0,"revenue_type":10}] | 否 |
     * |  |  |  |  | [{"ad_type":1,"revenue_type":2},{"ad_type":1,"revenue_type":10}] |  |
     * | | ad_type | integer | 广告类型 |  | 否 |
     * | | revenue_type | integer | 计费类型 | 10CPD 2CPC | 否 |
     * | mode |  | integer | 对接方式 |  | 否 |
     * | kind |  | integer | 类型 | 1联盟 2自营 | 是 |
     * | business_type |  | integer | 投放类型 | 1应用 2游戏 | 是 |
     * | contact |  | string | 联系人 |  | 是 |
     * | contact_phone |  | integer | 手机号 |  | 是 |
     * | qq |  | integer | qq |  | 是 |
     * | email |  | string | 邮箱 |  | 是 |
     * | username |  | string | 登录账号 |  | 是 |
     * | crypt_key |  | string | 密钥 |  | 否 |
     * | affiliates_status |  | integer | 运营状态 |  | 是 |
     * | zone_count |  | integer | 有效广告位 |  | 是 |
     * | income_amount |  | decimal | 累计收入 |  | 是 |
     * | self_income_amount |  | decimal | 自营累计收入 |  | 是 |
     * | creator_username |  | string | 销售联系人 |  | 是 |
     * | creator_contact_phone |  | integer | 销售联系电话 |  | 是 |
     * | creator_uid |  | integer | 销售顾问 |  | 是 |
     * | alipay_account |  | string | 支付宝账号 |  | 否 |
     * | affiliate_type |  | integer | 媒体类型 |  | 否 |
    */
    public function index(Request $request)
    {
        $user = Auth::user();
        $account = $user->account;
        $agencyId = $account->agency->agencyid;
        if (!$account->isManager()) {
            return $this->errorCode(5003); // @codeCoverageIgnore
        }

        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);
        $affiliate_type = $request->input('affiliate_type', Affiliate::AFFILIATE_TYPE_ADN);

        // 如果有账号管理权限，则可以看到所有的媒体商
        if ($this->can('manager-trafficker-account-all')) {
            $info = $this->getTraffickerList(
                $agencyId,
                0,
                $pageNo,
                $pageSize,
                $search,
                $sort,
                $filter,
                $affiliate_type
            );
        } elseif ($this->can('manager-trafficker-account-self')) {
            $info = $this->getTraffickerList(
                $agencyId,
                $user->user_id,
                $pageNo,
                $pageSize,
                $search,
                $sort,
                $filter,
                $affiliate_type
            );
        } else {
            $info = [
                'map' => [
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize,
                    'count' => 0,
                ],
                'list' => [],
            ];
        }
        if (!$info) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }

        return $this->success(null, $info['map'], $info['list']);
    }

    /**
     * 获取筛选
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request)
    {
        return $this->success(
            [
                'creator_uid' => AccountService::getSales(
                    [
                        AccountSubType::ACCOUNT_DEPARTMENT_MEDIA,
                        AccountSubType::ACCOUNT_DEPARTMENT_OPERATION
                    ]
                ),
            ],
            null,
            null
        );
    }

    /**
     * 创建媒体商
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |  | integer | 为0新建 否则为更新 |  | 是 |
     * | username |  | string | 登录账号 |  | 是 |
     * | password |  | string | 密码 |  | 是 |
     * | name |  | string | 媒体商全称 |  | 是 |
     * | brief_name |  | string | 媒体商简称 |  | 是 |
     * | contact |  | string | 联系人姓名 |  | 是 |
     * | contact_phone |  | integer | 手机号 |  | 是 |
     * | qq |  | integer | qq |  | 是 |
     * | email |  | string | 邮箱 |  | 是 |
     * | platform |  | string | 平台类型 |  | 是 |
     * | delivery |  | json string | 投放类型 | [{"ad_type":0,"revenue_type":10} | 是 |
     * |  |  |  |  | {"ad_type":1,"revenue_type":2}] |  |
     * |  |  |  |  | [{"ad_type":0,"revenue_type":10},{"ad_type":1,"revenue_type":2}] |  |
     * | | ad_type | integer | 广告类型 |  | 是 |
     * | | revenue_type | integer | 计费类型 | 10CPD 2CPC | 是 |
     * | income_rate |  | decimal | 收入分成 |  | 是 |
     * | mode |  | integer | 对接方式 |  | 是 |
     * | creator_uid |  | integer | 销售顾问ID |  | 是 |
     * | kind |  | integer | 类型 1联盟 2自营 |  | 是 |
     * | delivery_type |  | integer | 投放类型 | 1应用 2游戏 | 是 |
     * | alipay_account |  | string | 支付宝账号 |  | 否 |
     * | affiliate_type |  | integer | 媒体类型 |  | 否 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'contact' => 'required',
                'kind' => "required|integer|in:1,2,3",
                'delivery_type' => "required|integer|in:1,2,3",
                'creator_uid' => "required|integer|min:1",
            ], [], Affiliate::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (($params['kind'] & Affiliate::KIND_ALLIANCE) == Affiliate::KIND_ALLIANCE) {// 联盟
            if (($ret = $this->validate($request, [
                    'income_rate' => "required|numeric",
                    'mode' => "required|integer|in:0,1,2,3,4",
                    'app_platform' => "required|integer|min:1|max:15",
                    'audit' => "required|integer|in:1,2",
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }
        if (($params['kind'] & Affiliate::KIND_SELF) == Affiliate::KIND_SELF) {//自营
            if (($ret = $this->validate($request, [
                    'alipay_account' => "required|email",
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }

        if (isset($params['affiliateid']) && $params['affiliateid'] > 0) {//更新
            $userId = Affiliate::find($params['affiliateid'])->account->user;
            if (User::getAgencyUser('username', $params['username'], $userId->user_id)) {
                return $this->errorCode(5092);
            }
            if (Affiliate::getAgencyAffiliate('name', $params['name'], $params['affiliateid'])) {
                return $this->errorCode(5098);
            }
            if (Affiliate::getAgencyAffiliate('brief_name', $params['brief_name'], $params['affiliateid'])) {
                return $this->errorCode(5097);
            }
            if (User::getAgencyUser('contact_phone', $params['contact_phone'], $userId->user_id)) {
                return $this->errorCode(5094);
            }
            if (User::getAgencyUser('email_address', $params['email'], $userId->user_id)) {
                return $this->errorCode(5093);
            }
            DB::beginTransaction();//事务开始
            $affiliate = Affiliate::find($params['affiliateid']);
            $oldKind = $affiliate->kind;
            if (!$affiliate) {
                return $this->errorCode(5000);
            }
            $affiliate->fill($params);

            if (Affiliate::AFFILIATE_TYPE_ADN == $affiliate->affiliate_type) {
                $delivery = json_decode($params['delivery']);
                if (!empty($delivery)) {
                    $adList = $this->setAffiliateAdType($delivery, $params);
                    $affiliate->ad_type = implode(',', $adList);
                }
                $delivery = $this->setAffiliateTypeList($delivery);
            } else {
                //ADX允许的广告类型
                $biddingosAdList = Config::get('biddingos.ad_list');
                $adList = $this->setAffiliateAdType((object)$biddingosAdList, $params);
                $affiliate->ad_type = implode(',', $adList);
                $revenueType = $request->input('revenue_type');
                foreach ($adList as $k => $v) {
                    $deliveryData[] = [
                        'ad_type' => $v,
                        'revenue_type' => $revenueType,
                    ];
                }
                //转为对象
                $delivery = json_decode(json_encode($deliveryData));
            }
            
            AffiliateExtend::where('affiliateid', $params['affiliateid'])->delete();
            $this->insertAffiliateExtend($delivery, $affiliate);

            //修改媒体的资料信息
            if (!$affiliate->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }
            $account = $affiliate->account;
            $account->account_name = $params['username'];
            if (!$account->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }
            $user = $affiliate->account->user;
            if (isset($params['password']) && strlen($params['password']) >= 6) {
                $user->password = md5($params['password']);
            }
            $user->email_address = $params['email'];
            $user->contact_name = $params['contact'];
            $user->contact_phone = $params['contact_phone'];
            $user->qq = $params['qq'];
            if (!$user->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }

            if (intval(CrypterKey::where('afid', $affiliate->affiliateid)->count()) <= 0) {
                $crypterKey = new CrypterKey([
                    'afid' => $affiliate->affiliateid,
                    'key' => $affiliate->crypt_key,
                ]);
                if (!$crypterKey->save()) {
                    // @codeCoverageIgnoreStart
                    DB::rollback();
                    return $this->errorCode(5001);
                    // @codeCoverageIgnoreEnd
                }
            }

            if ((($params['kind'] & Affiliate::KIND_ALLIANCE) == Affiliate::KIND_ALLIANCE)
                && $oldKind == Affiliate::KIND_SELF
            ) {
                $exist = ClientVersion::where('af_id', $params['affiliateid'])->get();
                if (empty($exist)) {
                    $clientVersion = new ClientVersion([
                        'af_id' => $affiliate->affiliateid,
                        'versionid' => 0,
                        'version' => '默认版本',
                    ]);
                    if (!$clientVersion->save()) {
                        // @codeCoverageIgnoreStart
                        DB::rollback();
                        return $this->errorCode(5001);
                        // @codeCoverageIgnoreEnd
                    }
                }

                $exist = ZoneListType::where('af_id', $params['affiliateid'])->get();
                if (empty($exist)) {
                    $adTypes = Zone::getAdTypeCategory();
                    foreach ($adTypes as $adType) {
                        $zoneListType = new ZoneListType([
                            'af_id' => $affiliate->affiliateid,
                            'type' => ZoneListType::TYPE_GENERAL,
                            'listtypeid' => ZoneListType::LISTTYPEID_TOP,
                            'listtype' => '默认榜单模块',
                            'ad_type' => $adType,
                        ]);
                        $zoneListTypeAnother = new ZoneListType([
                            'af_id' => $affiliate->affiliateid,
                            'type' => ZoneListType::TYPE_SEARCH,
                            'listtypeid' => ZoneListType::LISTTYPEID_SEARCH,
                            'listtype' => '默认搜索模块',
                            'ad_type' => $adType,
                        ]);
                        if (!$zoneListTypeAnother->save() || !$zoneListType->save()) {
                            // @codeCoverageIgnoreStart
                            DB::rollback();
                            return $this->errorCode(5001);
                            // @codeCoverageIgnoreEnd
                        }
                    }
                }

                $exist = Zone::where('affiliateid', $params['affiliateid'])->where('type', Zone::TYPE_FLOW)->get();
                //流量广告位
                if (empty($exist)) {
                    $platforms = Campaign::getPlatformLabels(null, Product::TYPE_APP_DOWNLOAD);
                    foreach ($platforms as $key => $value) {
                        $zoneName = '流量广告' . $value;
                        $zone = new Zone([
                            'affiliateid' => $affiliate->affiliateid,
                            'zonename' => $zoneName,
                            'zonetype' => Zone::TYPE_FLOW,
                            'delivery' => 58,
                            'platform' => $key,
                            'rank_limit' => 5,
                            'oac_category_id' => 0,
                            'type' => Zone::TYPE_FLOW,
                        ]);
                        if (!$zone->save()) {
                            // @codeCoverageIgnoreStart
                            DB::rollback();
                            return $this->errorCode(5001);
                            // @codeCoverageIgnoreEnd
                        }
                        if (!ZoneService::attachRelationChain($zone->zoneid)) {
                            // @codeCoverageIgnoreStart
                            DB::rollback();
                            return $this->errorCode(5001);
                            // @codeCoverageIgnoreEnd
                        }
                    }
                }
                //$zonListType = ZoneListType::where('af_id', $affiliate->affiliateid);

                $exist = Zone::where('affiliateid', $params['affiliateid'])->get();
                // 添加默认分类
                if (empty($exist)) {
                    $categories = Category::getParentLabels();
                    $adTypes = Zone::getAdTypeCategory();
                    foreach ($categories as $key => $value) {
                        foreach ($adTypes as $adType) {
                            $category = new Category([
                                'name' => '默认' . $value,
                                'media_id' => Auth::user()->agencyid,
                                'parent' => $key,
                                'platform' => Campaign::PLATFORM_IPHONE_COPYRIGHT,
                                'affiliateid' => $affiliate->affiliateid,
                                'ad_type' => $adType,
                            ]);
                            if (!$category->save()) {
                                // @codeCoverageIgnoreStart
                                DB::rollback();
                                return $this->errorCode(5001);
                                // @codeCoverageIgnoreEnd
                            }
                        }
                    }
                }
            }
            DB::commit();//事务结束
        } else {// 新增
            if (($ret = $this->validate($request, [
                    'username' => 'required|min:2|max:32',
                    'password' => 'required|min:6',
                    'name' => 'required|min:2|max:64',
                    'brief_name' => 'required|min:2|max:32',
                    'contact_phone' => 'required|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/',
                    'email' => "required|email",
                    'qq' => 'numeric|unique:users,qq|regex:/^[1-9][0-9]{4,}$/',
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            if (User::getAgencyUser('username', $params['username'])) {
                return $this->errorCode(5092);
            }
            if (Affiliate::getAgencyAffiliate('name', $params['name'])) {
                return $this->errorCode(5098);
            }
            if (Affiliate::getAgencyAffiliate('brief_name', $params['brief_name'])) {
                return $this->errorCode(5097);
            }
            if (User::getAgencyUser('contact_phone', $params['contact_phone'])) {
                return $this->errorCode(5094);
            }
            if (User::getAgencyUser('email_address', $params['email'])) {
                return $this->errorCode(5093);
            }
            DB::beginTransaction();//事务开始
            //先创建账号
            $account = new Account([
                'agencyid' => Auth::user()->agencyid,
                'account_name' => $params['username'],
                'account_type' => Account::TYPE_TRAFFICKER,
            ]);
            if (!$account->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }

            // 创建媒体商
            $affiliate = new Affiliate();
            $affiliate->fill($params);
            $agencyId = Auth::user()->agencyid;
            $affiliate->agencyid = $agencyId;
            $affiliate->account_id = $account->account_id;
            $affiliate->crypt_key = md5('biddingOS' . time());

            if (($params['kind'] & Affiliate::KIND_SELF) == Affiliate::KIND_SELF) {
                $affiliate->alipay_account = $params['alipay_account'];
                $affiliate->income_rate = 100;
            }

            if (!$affiliate->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }

            // 处理ad_type 和 extend表
            if (Affiliate::AFFILIATE_TYPE_ADN == $params['affiliate_type']) {
                $delivery = json_decode($params['delivery']);
                if (!empty($delivery)) {
                    $adList = $this->setAffiliateAdType($delivery, $params);
                    $affiliate->ad_type = implode(',', $adList);
                }
                //返回转换过的计费类型
                $delivery = $this->setAffiliateTypeList($delivery);
            } else {
                //ADX允许的广告类型
                $biddingosAdList = Config::get('biddingos.ad_list');
                $adList = $this->setAffiliateAdType((object)$biddingosAdList, $params);
                $affiliate->ad_type = implode(',', $adList);
                $revenueType = $request->input('revenue_type');
                foreach ($adList as $k => $v) {
                    $deliveryData[] = [
                        'ad_type' => $v,
                        'revenue_type' => $revenueType,
                    ];
                }
                //转为对象
                $delivery = json_decode(json_encode($deliveryData));
            }
            
            $this->insertAffiliateExtend($delivery, $affiliate, $params['app_platform']);
            
            $affiliate->save();
            $crypterKey = new CrypterKey([
                'afid' => $affiliate->affiliateid,
                'key' => $affiliate->crypt_key,
            ]);
            if (!$crypterKey->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }

            $clientVersion = new ClientVersion([
                'af_id' => $affiliate->affiliateid,
                'versionid' => 0,
                'version' => '默认版本',
            ]);
            if (!$clientVersion->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }

            $adTypes = Zone::getAdTypeCategory();
            foreach ($adTypes as $adType) {
                $zoneListType = new ZoneListType([
                    'af_id' => $affiliate->affiliateid,
                    'type' => ZoneListType::TYPE_GENERAL,
                    'listtypeid' => ZoneListType::LISTTYPEID_TOP,
                    'listtype' => '默认榜单模块',
                    'ad_type' => $adType,
                ]);
                $zoneListTypeAnother = new ZoneListType([
                    'af_id' => $affiliate->affiliateid,
                    'type' => ZoneListType::TYPE_SEARCH,
                    'listtypeid' => ZoneListType::LISTTYPEID_SEARCH,
                    'listtype' => '默认搜索模块',
                    'ad_type' => $adType,
                ]);
                if (!$zoneListType->save() || !$zoneListTypeAnother->save()) {
                    // @codeCoverageIgnoreStart
                    DB::rollback();
                    return $this->errorCode(5001);
                    // @codeCoverageIgnoreEnd
                }
            }

            // 余额表初始化为0
            $balance = Balance::find($account->account_id);
            if (!$balance) {
                // @codeCoverageIgnoreStart
                $result = Balance::create([
                    'account_id' => $account->account_id,
                    'balance' => 0,
                    'gift' => 0,
                ]);
                if (!$result) {
                    DB::rollback();
                    return $this->errorCode(5001);
                }
                // @codeCoverageIgnoreEnd
            }

            //新增用户信息
            $user = new User();
            $user->fill($params);
            $user->agencyid = $agencyId;
            $user->password = md5($params['password']);
            $user->email_address = $params['email'];
            $user->contact_name = $params['contact'];
            $user->contact_phone = $params['contact_phone'];
            $user->default_account_id = $account->account_id;
            $user->role_id = 0;
            $user->account_sub_type_id = isset($params['account_sub_type_id']) ?
                $params['account_sub_type_id'] : 0;
            $user->active = User::ACTIVE_TRUE;

            if (!$user->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }

            // account的manager_userid设置为该账号
            $account->manager_userid = $user->user_id;
            if (!$account->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }

            //最后存 account_user_assoc关联表
            $user->accounts()->attach($account->account_id, ['linked' => date('Y-m-d h:i:s')]);

            $defaultRoleId = Config::get('default_trafficker_role', $agencyId);//获取最新的权限id
            //创建广告主权限
            $defaultRole = Role::find($defaultRoleId);
            $role = $defaultRole->replicate();
            $role->type = Role::TYPE_USER;
            $role->account_id = $account->account_id;
            if (!$role->push()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }
            //更新用户权限
            $user->role_id = $role->id;
            if (!$user->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }

            //流量广告位
            $platforms = Campaign::getPlatformLabels(null, Product::TYPE_APP_DOWNLOAD);
            foreach ($platforms as $key => $value) {
                $zoneName = '流量广告' . $value;
                $zone = new Zone([
                    'affiliateid' => $affiliate->affiliateid,
                    'zonename' => $zoneName,
                    'delivery' => 58,
                    'zonetype' => Zone::TYPE_FLOW,
                    'platform' => $key,
                    'rank_limit' => 5,
                    'oac_category_id' => 0,
                    'type' => Zone::TYPE_FLOW,
                ]);
                if (!$zone->save()) {
                    // @codeCoverageIgnoreStart
                    DB::rollback();
                    return $this->errorCode(5001);
                    // @codeCoverageIgnoreEnd
                }
                if (!ZoneService::attachRelationChain($zone->zoneid)) {
                    // @codeCoverageIgnoreStart
                    DB::rollback();
                    return $this->errorCode(5001);
                    // @codeCoverageIgnoreEnd
                }/*if (!ZoneListType::whereMulti([
                'af_id' => $affiliate->affiliateid,
                //'listtypeid' => ZoneListType::LISTTYPEID_SEARCH,
            ])->update(['already_used' => 1])) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }*/;
            }
            //$zonListType = ZoneListType::where('af_id', $affiliate->affiliateid);

            // 添加默认分类
            $categories = Category::getParentLabels();
            $adTypes = Zone::getAdTypeCategory();
            foreach ($categories as $key => $value) {
                foreach ($adTypes as $adType) {
                    $category = new Category([
                        'name' => '默认' . $value,
                        'media_id' => Auth::user()->agencyid,
                        'parent' => $key,
                        'platform' => Campaign::PLATFORM_IPHONE_COPYRIGHT,
                        'affiliateid' => $affiliate->affiliateid,
                        'ad_type' => $adType,
                    ]);
                    if (!$category->save()) {
                        // @codeCoverageIgnoreStart
                        DB::rollback();
                        return $this->errorCode(5001);
                        // @codeCoverageIgnoreEnd
                    }
                }
            }

            DB::commit();//事务结束
        }

        return $this->success();
    }

    /**
     * 媒体商管理 更新用户字段
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'id' => 'required|integer',
                'field' => 'required',
            ], [], array_merge(Affiliate::attributeLabels(), User::attributeLabels()))) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->get('id');
        $field = $request->get('field');
        $value = $request->get('value');

        // 当更新权限时，上传的id是role表的id
        if ($field == 'name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32|unique:affiliates,name'
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'brief_name') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|min:2|max:32'
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'contact') {
            if (($ret = $this->validate($request, [
                    'value' => 'required'
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'mode') {
            if (strpos($value, '|') === false) {
                return $this->errorCode(5000);
            }
        } elseif ($field == 'app_platform') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|integer|min:1|max:15'
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'email') {
            // 格式不对
            if (!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $value)) {
                return $this->errorCode(5018);
            }
            if (($ret = $this->validate($request, [
                    'value' => 'required|unique:users,email_address'
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'contact_phone') {
            if (($ret = $this->validate($request, [
                    'value' => 'required|unique:users,contact_phone|max:11|regex:/^(\+\d{2,3}\-)?\d{11}$/'
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        } elseif ($field == 'affiliate_status') {
            $status = ArrayHelper::getRequiredIn(Affiliate::getStatusLabel());
            if (($ret = $this->validate($request, [
                    'value' => "required|in:{$status}"
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }

        $affiliate = Affiliate::find($id);
        $userId = $affiliate->account->user->user_id;
        $user = User::find($userId);
        if ($field == 'contact_phone' || $field == 'qq') {
            $user->$field = $value;
        } else {
            if ($field == 'contact') {
                $affiliate->$field = $value;
                $user->contact_name = $value;
            } elseif ($field == 'affiliates_status') {
                //启用，暂停广告主
                $affiliate->$field = $value;
                $user->active = $value;
            } elseif ($field == 'email') {
                $affiliate->$field = $value;
                $user->email_address = $value;
            } elseif ($field == 'app_platform') {
                $affiliate->$field = $value;
                // 如果平台为iOS越狱 或者包含 Android，则删除App_store
                if ($value == Campaign::PLATFORM_IPHONE_JAILBREAK
                    || (intval($value) & Campaign::PLATFORM_ANDROID) == Campaign::PLATFORM_ANDROID) {
                    AffiliateExtend::where('affiliateid', $id)->where('ad_type', Campaign::AD_TYPE_APP_STORE)
                        ->delete();

                    // ad要删除ad_type 为71的
                    $arrAdType = explode(',', $affiliate->ad_type);
                    foreach ($arrAdType as $k => $v) {
                        if ($v == Campaign::AD_TYPE_APP_STORE) {
                            unset($arrAdType[$k]);
                        }
                    }
                    $affiliate->ad_type = implode(',', $arrAdType);
                }
            } elseif ($field == 'delivery') {
                $delivery = json_decode($value);
                if (!empty($delivery)) {
                    $adList = array_unique(ArrayHelper::getColumn($delivery, 'ad_type'));
                    if (in_array(Campaign::AD_TYPE_BANNER_IMG, $adList)) {
                        $adList[] = Campaign::AD_TYPE_BANNER_TEXT_LINK;
                    }
                    if (in_array(Campaign::AD_TYPE_HALF_SCREEN, $adList)) {
                        $adList[] = Campaign::AD_TYPE_FULL_SCREEN;
                    }
                    $affiliate->ad_type = implode(',', $adList);
                }
                AffiliateExtend::where('affiliateid', $id)->delete();
                $this->insertAffiliateExtend($delivery, $affiliate);
            } elseif ('revenue_type' == $field) {
                //修改计费类型
                $adList = explode(",", $affiliate->ad_type);
                $revenueType = $request->input('revenue_type');
                foreach ($adList as $k => $v) {
                    $deliveryData[] = [
                        'ad_type' => $v,
                        'revenue_type' => $revenueType,
                    ];
                }
                //转为对象
                $delivery = (object)$deliveryData;
                
                AffiliateExtend::where('affiliateid', $id)->delete();
                $this->insertAffiliateExtend($delivery, $affiliate);
            } elseif ($field == 'mode') {
                $arr = explode('|', $value);
                if ($arr[0] < 0 || $arr[0] > 3 || $arr[1] < 1 || $arr[1] > 2) {
                    return $this->errorCode(5000);
                }
                $affiliate->mode = $arr[0];
                $affiliate->audit = $arr[1];
            } else {
                $affiliate->$field = $value;
            }
            
            if (!$affiliate->save()) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
        }
        if (!$user->save()) {
            return $this->errorCode(5001);// @codeCoverageIgnore
        }

        if ($field == 'income_rate') {
            //修改媒体分成时候刷新媒体价
            $banners = DB::table('banners')->where('affiliateid', $id)->select('bannerid')->get();
            if ($banners) {
                foreach ($banners as $item) {
                    CampaignService::updateBannerBilling($item->bannerid);
                }
            }
        }
        
        return $this->success();
    }

    /**
     * 获取销售顾问
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function sales(Request $request)
    {
        // 如果有账号管理权限，则可以看到所有的媒体商
        if ($this->can('manager-account')) {
            //媒体商的销售顾问为媒介+运营
            $accountType = [
                AccountSubType::ACCOUNT_DEPARTMENT_MEDIA,
                AccountSubType::ACCOUNT_DEPARTMENT_OPERATION
            ];
            $result = AccountService::getAccountList($accountType);
            $obj = [];
            if (!empty($result)) {
                $obj = ArrayHelper::map($result, 'user_id', 'contact_name');
            }
        } else {
            $obj = [Auth::user()->user_id => Auth::user()->contact_name];
        }

        return $this->success($obj, null, null);

    }

    private function insertAffiliateExtend($delivery, $affiliate, $platform = 0)
    {
        $delivery = json_decode(json_encode($delivery), true);
        foreach ($delivery as $item) {
            if (($platform == Campaign::PLATFORM_IPHONE_JAILBREAK
                    || (intval($platform) & Campaign::PLATFORM_ANDROID) == Campaign::PLATFORM_ANDROID)
                && ($item['ad_type'] == Campaign::AD_TYPE_APP_STORE)) {
                continue;
            }
            $affiliateExtend = new AffiliateExtend([
                'affiliateid' => $affiliate->affiliateid,
                'ad_type' => $item['ad_type'],
                'revenue_type' => $item['revenue_type'],
                'num' => 1,
            ]);
            
            if (!$affiliateExtend->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5001);
                // @codeCoverageIgnoreEnd
            }
/*
            if ($item->ad_type == Campaign::AD_TYPE_BANNER_IMG) {
                $clone = $affiliateExtend->replicate();
                $clone->ad_type = Campaign::AD_TYPE_BANNER_TEXT_LINK;
                if (!$clone->push()) {
                    // @codeCoverageIgnoreStart
                    DB::rollback();
                    return $this->errorCode(5001);
                    // @codeCoverageIgnoreEnd
                }
            }
            
            if ($item->ad_type == Campaign::AD_TYPE_HALF_SCREEN) {
                $clone = $affiliateExtend->replicate();
                $clone->ad_type = Campaign::AD_TYPE_FULL_SCREEN;
                if (!$clone->push()) {
                    // @codeCoverageIgnoreStart
                    DB::rollback();
                    return $this->errorCode(5001);
                    // @codeCoverageIgnoreEnd
                }
            }
*/
        }
    }

    /**
     * 获取媒体商用户
     * @param $userId
     * @param int $pageNo
     * @param int $pageSize
     * @param null $search
     * @param null $sort
     * @param string $filter
     * @return array
     */
    public function getTraffickerList(
        $agencyId,
        $userId = 0,
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null,
        $filter = null,
        $affiliate_type = 1
    ) {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('affiliates as af')
            ->leftJoin('accounts as a', 'af.account_id', '=', 'a.account_id')
            ->leftJoin('users as u', 'a.manager_userid', '=', 'u.user_id')
            ->select(
                'af.affiliateid',
                'af.name',
                'af.brief_name',
                'af.contact',
                'af.app_platform',
                'af.audit',
                'af.cdn',
                'af.income_rate',
                'af.mode',
                'af.email',
                'af.creator_uid',
                'af.crypt_key',
                'af.income_rate',
                'af.income_rate',
                'af.affiliates_status',
                'af.comments',
                'af.account_id',
                'af.income_amount',
                'af.self_income_amount',
                'af.kind',
                'af.delivery_type',
                'af.alipay_account',
                'af.affiliate_type',
                'u.user_id',
                'u.username',
                'u.contact_phone',
                'u.qq',
                'u.active',
                'u.date_created'
            )
            ->where('af.affiliate_type', $affiliate_type)
            ->where('af.agencyid', '=', $agencyId);
        if ($userId > 0) {
            $select = $select->where('af.creator_uid', '=', $userId);
        }

        // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where(function ($select) use ($search) {
                $select->where('af.name', 'like', '%' . $search . '%');
                $select->orWhere('u.contact_name', 'like', '%' . $search . '%');
                $select->orWhere('u.username', 'like', '%' . $search . '%');
                $select->orWhere('af.email', 'like', '%' . $search . '%');
            });
        }

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            if ((isset($filter['ad_type']) &&!StringHelper::isEmpty($filter['ad_type']))
                || (isset($filter['revenue_type']) &&!StringHelper::isEmpty($filter['revenue_type']))) {
                $select->join('affiliates_extend as afe', 'af.affiliateid', '=', 'afe.affiliateid');
            }
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'ad_type' || $k == 'revenue_type') {
                        $select->where('afe.' . $k, $v);
                    } elseif ($k == 'app_platform') {
                        $select->where('af.' . $k, '&', $v);
                    } else {
                        $select->where('af.' . $k, $v);
                    }
                }
            }
        }

        // 分页
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);
        //排序
        if ($sort) {
            $sortType = 'asc';
            if (strncmp($sort, '-', 1) === 0) {
                $sortType = 'desc';
            }
            $sortAttr = str_replace('-', '', $sort);
            $select->orderBy($sortAttr, $sortType);
        } else {
            $select->orderBy('affiliates_status', 'desc')->orderBy('affiliateid', 'desc');
        }

        $rows = $select->get();
        $list = [];
        foreach ($rows as $row) {
            $creator = User::find($row['creator_uid']);
            $row['creator_username'] = $creator['contact_name'];
            $row['creator_contact_phone'] = $creator['contact_phone'];
            $row['zone_count'] = Zone::where('affiliateid', $row['affiliateid'])->count();
            if (Affiliate::AFFILIATE_TYPE_ADN == $row['affiliate_type']) {
                $row['delivery'] = AffiliateExtend::where('affiliateid', $row['affiliateid'])
                    ->select('ad_type', 'revenue_type')
                    ->orderBy('ad_type', 'asc')->orderBy('revenue_type', 'desc')
                    ->get()->toArray();
            } else {
                $data = AffiliateExtend::where('affiliateid', $row['affiliateid'])
                                ->select('revenue_type')
                                ->first();
                if (!empty($data)) {
                    $data = json_decode(json_encode($data), true);
                    $row = array_merge($data, $row);
                }
            }
            $list[] = $row;
        }
        return [
            'map' => [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => $total,
            ],
            'list' => $list,
        ];
    }
    
    /**
     * 根据 delivery的值返回媒体的 ad_type值
     */
    private function setAffiliateAdType($delivery, $params)
    {
        $adList = array_unique(ArrayHelper::getColumn($delivery, 'ad_type'));
        if ($params['app_platform'] == Campaign::PLATFORM_IPHONE_JAILBREAK
            || ((intval($params['app_platform']) &
                Campaign::PLATFORM_ANDROID) == Campaign::PLATFORM_ANDROID)
        ) {
            ArrayHelper::removeValue($adList, Campaign::AD_TYPE_APP_STORE);
        }
        if (in_array(Campaign::AD_TYPE_BANNER_IMG, $adList)) {
            $adList[] = Campaign::AD_TYPE_BANNER_TEXT_LINK;
        }
        if (in_array(Campaign::AD_TYPE_HALF_SCREEN, $adList)) {
            $adList[] = Campaign::AD_TYPE_FULL_SCREEN;
        }
        
        return $adList;
    }
    
    
    private function setAffiliateTypeList($delivery)
    {
        $data = [];
        if (!empty($delivery)) {
            foreach ($delivery as $k => $v) {
                $data[] = $v;
                if (Campaign::AD_TYPE_BANNER_IMG == $v->ad_type) {
                    $data[] = [
                        'ad_type' => Campaign::AD_TYPE_BANNER_TEXT_LINK,
                        'revenue_type' => $v->revenue_type,
                    ];
                }
                
                if (Campaign::AD_TYPE_HALF_SCREEN == $v->ad_type) {
                    $data[] = [
                        'ad_type' => Campaign::AD_TYPE_FULL_SCREEN,
                        'revenue_type' => $v->revenue_type,
                    ];
                }
            }
        }
        return (object)$data;
    }
}
