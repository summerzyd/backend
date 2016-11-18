<?php
namespace App\Services;

use App\Components\Config;
use App\Models\Account;
use App\Models\Balance;
use App\Models\Broker;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\DB;

class AdvertiserService
{
    /**
     * 获取代理商广告主用户
     * @param $userId
     * @param int $pageNo
     * @param int $pageSize
     * @param null $search
     * @param null $sort
     * @return array
     */
    public static function getUserList(
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null
    ) {
        $agencyId = Auth::user()->account->broker->agencyid;
        $brokerId = Auth::user()->account->broker->brokerid;
        $prefix = DB::getTablePrefix();
        $select = DB::table('clients as c')
            ->leftJoin('balances as b', 'c.account_id', '=', 'b.account_id')
            ->leftJoin('accounts as a', 'c.account_id', '=', 'a.account_id')
            ->leftJoin('users as u', 'a.manager_userid', '=', 'u.user_id')
            ->select(
                'c.clientid as client_id',
                'c.clientname',
                'c.brief_name',
                'c.address',
                'c.qualifications',
                'u.username',
                'u.contact_name AS contact',
                'c.email',
                'u.user_id',
                'u.contact_phone AS phone',
                'b.balance',
                'b.gift',
                DB::raw('('. $prefix . 'b.balance + '. $prefix . 'b.gift) as total'),
                'c.clients_status as clients_status',
                'c.comments AS comment',
                'c.revenue_type'
            )
            ->where('c.agencyid', '=', $agencyId)
            ->where('c.broker_id', '=', $brokerId);
        // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where('c.clientname', 'like', '%' . $search . '%');
            $select->orWhere('u.contact_name', 'like', '%' . $search . '%');
            $select->orWhere('c.email', 'like', '%' . $search . '%');
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
            $select->orderBy('c.clients_status', 'desc');
        }

        $rows = $select->get();
        $rows = json_decode(json_encode($rows), true);
        $list = [];
        foreach ($rows as $row) {
            $row['clients_status_label'] = Client::getStatusLabel($row['clients_status']);
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

    public static function store($params, $type)
    {
        DB::beginTransaction();//事务开始
        //先创建账号 1联盟广告主，3 自营广告主
        if ($type == 1 || $type == 3) {
            $accountType = Account::TYPE_ADVERTISER;
        } else {
            $accountType = Account::TYPE_BROKER;
        }

        $account = new Account([
            'agencyid' => Auth::user()->agencyid,
            'account_name' => $params['username'],
            'account_type' => $accountType,
        ]);
        if (!$account->save()) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return 5001;
            // @codeCoverageIgnoreEnd
        }
        // 创建广告主
        $agencyId = Auth::user()->agencyid;
        if ($type == 1 || $type == 3) {
            $client = new Client();
            $client->fill($params);
            $client->agencyid = $agencyId;
            $client->account_id = $account->account_id;
            $defaultRoleId = Config::get('default_client_role', $agencyId);//获取最新的权限id
            
            if ($type == 3) {
                $client->affiliateid = Auth::user()->account->affiliate->affiliateid;
            }

            if (!$client->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return 5001;
                // @codeCoverageIgnoreEnd
            }
        } else {
            $broker = new Broker();
            $broker->fill($params);
            $broker->agencyid = $agencyId;
            $broker->account_id = $account->account_id;
            $defaultRoleId = Config::get('default_broker_role', $agencyId);//获取最新的权限id
            if (!$broker->save()) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return 5001;
                // @codeCoverageIgnoreEnd
            }
        }

        $balance = Balance::find($account->account_id);
        if (!$balance) {
            // @codeCoverageIgnoreStart
            $result = Balance::create([
                'account_id' => $account->account_id,
                'balance' => 0,
                'gift' => 0,
            ]);
            if (!$result) {
                return 5001;
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
        $user->active =User::ACTIVE_TRUE;

        if (!$user->save()) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return 5001;
            // @codeCoverageIgnoreEnd
        }

        // account的manager_userid设置为该账号
        $account->manager_userid = $user->user_id;
        if (!$account->save()) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return 5001;
            // @codeCoverageIgnoreEnd
        }

        //最后存 account_user_assoc关联表
        $user->accounts()->attach($account->account_id, ['linked' => date('Y-m-d h:i:s')]);
        //创建权限
        $defaultRole = Role::find($defaultRoleId);
        $role = $defaultRole->replicate();
        $role->type = Role::TYPE_USER;
        if (!$role->push()) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return 5001;
            // @codeCoverageIgnoreEnd
        }
        //更新用户权限
        $user->role_id = $role->id;
        if (!$user->save()) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return 5001;
            // @codeCoverageIgnoreEnd
        }

        DB::commit();//事务结束

        return 'success';

    }
}
