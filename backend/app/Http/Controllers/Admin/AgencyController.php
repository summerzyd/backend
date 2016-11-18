<?php

namespace App\Http\Controllers\Admin;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Models\Account;
use App\Models\Affiliate;
use App\Models\Agency;
use App\Models\Broker;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\DataSummaryAdHourly;
use App\Models\Product;
use App\Models\User;
use Auth;
use App\Services\StatService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Components\Config;
use Illuminate\Support\Facades\DB;

class AgencyController extends Controller
{
    /**
     * POST : 获取联盟平台列表
     *
     * + pageSize 页面
     * + pageNo   当前页码
     * + sort   排序
     * + search   搜索
     * + filter   筛选
     * @param  Request $request
     * @return \Illuminate\Http\Response list
     * + account_id     integer 账号id   是
     * + active     integer 状态   是
     * + agencyid     integer 联盟ID   是
     * + contact     string 联系人   是
     * + email     string email地址   是
     * + name     string 联盟名称   是
     * + child     json数组     是
     * + + manager   json数组
     * + + + active integer 状态 0暂停 1正常 是
     * + + + contact_name string 联系人   是
     * + + + email_address string 联系人邮箱地址   是
     * + + + user_id integer 切换帐号的ID，给site/change传的id值   是
     * + + + username string 用户登录账号   是
     * + + broker 子字段同manager
     * + + advertiser 子字段同manager
     * + + trafficker 子字段同manager
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | account_id  | integer |  |  | 是 |
     * | active  | integer  |  |  | 是 |
     * | name  | string  |  |  | 是 |
     */
    public function index(Request $request)
    {
        $agencies = Agency::select('agencyid', 'name', 'contact', 'email', 'active', 'account_id')->get()->toArray();

        $list = [];
        foreach ($agencies as $k => $v) {
            $list[$k] = $v;
            $manager = User::where('agencyid', '=', $v['agencyid'])
                ->where('default_account_id', '=', $v['account_id'])
                ->where('active', User::ACTIVE_TRUE)
                ->select('user_id', 'username', 'contact_name', 'email_address', 'active')
                ->orderBy('user_id', 'asc')
                ->get()->toArray();
            $list[$k]['child']['manager'] = $manager;

            $affiliateIdName = ArrayHelper::map(
                Affiliate::where('agencyid', '=', $v['agencyid'])
                    ->where('affiliates_status', Affiliate::STATUS_ENABLE)
                    ->where('kind', Affiliate::KIND_ALLIANCE)
                    ->get()->toArray(),
                'account_id',
                'name'
            );
            $affiliateIdAffiliateId = ArrayHelper::map(
                Affiliate::where('agencyid', '=', $v['agencyid'])
                    ->where('affiliates_status', Affiliate::STATUS_ENABLE)
                    ->where('kind', Affiliate::KIND_ALLIANCE)
                    ->get()->toArray(),
                'account_id',
                'affiliateid'
            );
            $affiliate = array_keys($affiliateIdName);
            $trafficker = User::whereIn('default_account_id', $affiliate)
                ->select('user_id', 'username', 'contact_name', 'email_address', 'active', 'default_account_id')
                ->get();
            $array = [];
            foreach ($trafficker as $model) {
                $item = [];
                $item['user_id'] = $model->user_id;
                $item['account_id'] = $model->default_account_id;
                $item['username'] = $model->username;
                $item['contact_name'] = $model->contact_name;
                $item['email_address'] = $model->email_address;
                $item['active'] = $model->active;
                $item['name'] = $affiliateIdName[$model->default_account_id];
                $item['affiliateid'] = $affiliateIdAffiliateId[$model->default_account_id];
                $item['kind'] = Affiliate::KIND_ALLIANCE;
                $array[] = $item;
            }
            $list[$k]['child']['trafficker'] = $array;

            $prefix = DB::getTablePrefix();
            $self = Affiliate::KIND_SELF;
            $affiliateSelf = Affiliate::where('agencyid', '=', $v['agencyid'])
                ->where('affiliates_status', Affiliate::STATUS_ENABLE)
                ->whereRaw("({$prefix}affiliates.kind & {$self} = {$self})")
                ->get();
            foreach ($affiliateSelf as $self) {
                $affiliateSelfArray = [
                    'child' => [
                        'trafficker' => [],
                        'client' => [],
                    ]
                ];
                $affiliateSelfArray['affiliateid'] = $self->affiliateid;
                $affiliateSelfArray['manager_userid'] = $self->account->manager_userid;
                $traffickerSelf = User::where('default_account_id', $self->account_id)
                    ->select('user_id', 'username', 'contact_name', 'email_address', 'active', 'default_account_id')
                    ->get();
                foreach ($traffickerSelf as $model) {
                    $item = [];
                    $item['user_id'] = $model->user_id;
                    $item['account_id'] = $model->default_account_id;
                    $item['username'] = $model->username;
                    $item['contact_name'] = $model->contact_name;
                    $item['email_address'] = $model->email_address;
                    $item['active'] = $model->active;
                    $item['kind'] = Affiliate::KIND_SELF;
                    $affiliateSelfArray['child']['trafficker'][] = $item;
                }

                $clientSelf = DB::table('clients')
                    ->leftJoin('accounts', 'accounts.account_id', '=', 'clients.account_id')
                    ->leftJoin('users', 'users.default_account_id', '=', 'accounts.account_id')
                    ->where('clients.agencyid', '=', $v['agencyid'])
                    ->where('clients.affiliateid', $self->affiliateid)
                    ->select(
                        'users.user_id',
                        'users.username',
                        'users.contact_name',
                        'users.email_address',
                        'users.active',
                        'users.default_account_id',
                        'clients.clientname'
                    )
                    ->get();
                foreach ($clientSelf as $model) {
                    $item = [];
                    $item['user_id'] = $model->user_id;
                    $item['username'] = $model->username;
                    $item['contact_name'] = $model->contact_name;
                    $item['email_address'] = $model->email_address;
                    $item['active'] = $model->active;
                    $item['name'] = $model->clientname;
                    $item['kind'] = Affiliate::KIND_SELF;
                    $affiliateSelfArray['child']['advertiser'][] = $item;
                }
                $list[$k]['child']['trafficker'][] = $affiliateSelfArray;
            }

            /*$affiliateSelf = DB::table('affiliates')
                ->leftJoin('accounts')
                ->leftJoin('users')
                ->where('agencyid', '=', $v['agencyid'])
                ->where('affiliates_status', Affiliate::STATUS_ENABLE)
                ->whereRaw("({$prefix}affiliates.kind & {$self} = {$self}")
                ->select(
                    'accounts.account_id'
                )
                ->get();*/


            $clientIdName = ArrayHelper::map(
                Client::where('agencyid', '=', $v['agencyid'])
                    ->where('clients_status', Client::STATUS_ENABLE)
                    ->where('affiliateid', 0)
                    ->get()->toArray(),
                'account_id',
                'clientname'
            );
            $client = array_keys($clientIdName);
            $advertiser = User::whereIn('default_account_id', $client)
                ->select('user_id', 'username', 'contact_name', 'email_address', 'active', 'default_account_id')
                ->get();
            $array = [];
            foreach ($advertiser as $model) {
                $item = [];
                $item['user_id'] = $model->user_id;
                $item['username'] = $model->username;
                $item['contact_name'] = $model->contact_name;
                $item['email_address'] = $model->email_address;
                $item['active'] = $model->active;
                $item['name'] = $clientIdName[$model->default_account_id];
                $array[] = $item;
            }
            $list[$k]['child']['advertiser'] = $array;

            $brokerIdName = ArrayHelper::map(
                Broker::where('agencyid', '=', $v['agencyid'])
                    ->where('status', Broker::STATUS_ENABLE)
                    ->get()->toArray(),
                'account_id',
                'name'
            );
            $brokers = array_keys($brokerIdName);
            $broker = User::whereIn('default_account_id', $brokers)
                ->select('user_id', 'username', 'contact_name', 'email_address', 'active', 'default_account_id')
                ->get();
            $array = [];
            foreach ($broker as $model) {
                $item = [];
                $item['user_id'] = $model->user_id;
                $item['username'] = $model->username;
                $item['contact_name'] = $model->contact_name;
                $item['email_address'] = $model->email_address;
                $item['active'] = $model->active;
                $item['name'] = $brokerIdName[$model->default_account_id];
                $array[] = $item;
            }
            $list[$k]['child']['broker'] = $array;
        }

        return $this->success(null, null, $list);
    }
}
