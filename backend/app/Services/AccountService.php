<?php

namespace App\Services;

use App\Components\Helper\ArrayHelper;
use App\Models\Account;
use App\Models\User;
use App\Models\YoukuClientManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountService
{
    /**
     * 获取帐号下所有子用户的信息
     * @param int $accountId           账户ID
     * @param array $arrayStatus   【可选】广告任务的状态
     * @param int $pageNo          【可选】页码
     * @param int $pageSize        【可选】每页数量
     * @param string $search       【可选】搜索关键字
     * @param string $sort         【可选】排序字段
     * @return array
     */
    public static function getUserList(
        $accountId,
        $pageNo = 1,
        $pageSize = 10,
        $search = null
    ) {
    
        $account = Account::find($accountId);
        if (!$account) {
            return null; // @codeCoverageIgnore
        }

        $select = $account->users()->getQuery()
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->leftJoin('account_sub_type', 'users.account_sub_type_id', '=', 'account_sub_type.id')
            ->select(
                'user_id',
                'username',
                'contact_name',
                'email_address',
                'comments',
                'account_sub_type_id',
                'role_id',
                'operation_list',
                'active',
                'account_sub_type.name as account_sub_type_id_label'
            )
            ->where('users.default_account_id', $accountId)
            ->where('users.user_id', '<>', $account->manager_userid);

        // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where('users.username', 'like', '%' . $search . '%');
            $select->orWhere('users.contact_name', 'like', '%' . $search . '%');
            $select->orWhere('users.email_address', 'like', '%' . $search . '%');
            $select->orWhere('account_sub_type.name', 'like', '%' . $search . '%');
        }
        // 分页
        $total = $select->count();
//        $count = ceil($total / intval($pageSize));
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        $rows = $select->get()->toArray();
        $list = [];
        foreach ($rows as $row) {
            $item = $row;
            $item['active_label'] = User::getActiveStatusLabels($row['active']);
            $list[] = $item;
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
     * 获取所有销售顾问
     * @param string $accountType   账户类型 ADVERTISER,BROKER,TRAFFICKER
     * @return array
     */
    public static function getSales($accountDepartment)
    {
        $models = self::getAccountList($accountDepartment);
        $obj = ArrayHelper::map($models, 'user_id', 'contact_name');

        return $obj;
    }

    /**
     * 更新广告主素材
     * @param $clientId
     */
    public static function updateAdxAdvertiser($clientId)
    {
        //修改优酷ADX素材信息
        YoukuClientManager::where('clientid', $clientId)->update([
            'status' => YoukuClientManager::STATUS_PENDING_SUBMISSION,
        ]);
    }
    
    /**
     * 返回相应类型的账号
     * @param integer $accountDepartment
     * @return array $result;
     */
    public static function getAccountList($accountDepartment, $accountType = 'MANAGER')
    {
        if (!is_array($accountDepartment)) {
            $accountDepartment = explode(",", $accountDepartment);
        }
        $result = DB::table('users AS u')
                ->leftJoin('account_sub_type AS ast', 'ast.id', '=', 'u.account_sub_type_id')
                ->select('u.user_id', 'u.contact_name', 'ast.account_department')
                ->whereIn('ast.account_department', $accountDepartment)
                ->where('ast.account_type', $accountType)
                ->where('u.agencyid', Auth::user()->agencyid)
                ->where('u.active', User::ACTIVE_TRUE)
                ->groupBy('ast.account_department', 'u.user_id')
                ->get();
    
        return $result;
    }
}
