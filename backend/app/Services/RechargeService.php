<?php

namespace App\Services;

use App\Components\Helper\LogHelper;
use App\Models\Recharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Tests\Controller;

class RechargeService
{
    public static function getRechargeList(
        $agencyId,
        $type,
        $userId,
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null
    ) {
        $select = DB::table('recharge')
            ->leftjoin('users', 'users.user_id', '=', 'recharge.user_id')
            ->where('recharge.agencyid', '=', $agencyId)
            ->select(
                'users.contact_name',
                'recharge.amount',
                'recharge.status',
                'recharge.date',
                'recharge.comment'
            )
            ->orderBy('apply_time', 'desc');
        //代理商或者广告主
        if ($type == 0) {
            $select->leftjoin('clients', 'clients.account_id', '=', 'recharge.target_accountid')
                ->where('clients.clientid', $userId)
                ->where('recharge.type', $type)
                ->addselect('clients.clientname');
            ;
        } else {
            $select->leftjoin('brokers', 'brokers.account_id', '=', 'recharge.target_accountid')
                ->where('brokers.brokerid', $userId)
                ->where('recharge.type', $type)
                ->addselect('brokers.name');
        }
        // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where('appinfos.app_name', 'like', '%' . $search . '%');
        }
        // 分页
        $total = $select->count();
        $count = ceil($total / intval($pageSize));
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $rows = $select->get();
        return [
            'map' => [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => $total,
            ],
            'list' => $rows,
        ];
    }
    public static function apply($agencyId, $accountId, $param, $type)
    {
        $recharge = new Recharge();
        $recharge->account_id = Auth::user()->account->account_id;
        $recharge->user_id = Auth::user()->user_id;
        $recharge->agencyid = $agencyId;
        $recharge->target_accountid = $accountId;
        $recharge->way = $param['way'];
        $recharge->account_info = $param['account_info'];
        $recharge->date =  $param['date'];
        $recharge->amount = $param['amount'];
        $recharge->apply_time = date('Y-m-d H:i:s');
        $recharge->status = 1;
        $recharge->type = $type;
        if (!$recharge->save()) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return 5001; // @codeCoverageIgnore
        }
        return 'success';
    }

    /**获取充值记录的账号
     * @param $accountId
     * @param $user_id
     * @param $agencyId
     * @param $target_accountId
     * @param $way
     * @return int
     */
    public static function history($accountId, $user_id, $agencyId, $target_accountId, $way)
    {
        $info = Recharge::where('account_id', $accountId)
            ->where('user_id', $user_id)
            ->where('agencyid', $agencyId)
            ->where('target_accountid', $target_accountId)
            ->where('way', $way)
            ->select('account_info')
            ->groupBy('account_info')
            ->get()
            ->toArray();
        if (!$info) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return 5001; // @codeCoverageIgnore
        }
        return $info;
    }
}
