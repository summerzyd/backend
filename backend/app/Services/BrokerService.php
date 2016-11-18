<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrokerService
{
    /**
     * 获取代理商列表
     * @param $agencyId
     * @param $userId
     * @param $affiliateId
     * @param int $pageNo
     * @param int $pageSize
     * @param string $search
     * @param string $sort
     * @param array $filter
     * @return array
     */
    public static function getBrokerList(
        $agencyId,
        $userId,
        $affiliateId = 0,
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null,
        $filter = null
    ) {
        DB::setFetchMode(\PDO::FETCH_ASSOC);

        $select = DB::table('brokers')
            ->leftJoin('balances', 'brokers.account_id', '=', 'balances.account_id')
            ->leftJoin('accounts', 'brokers.account_id', '=', 'accounts.account_id')
            ->select(
                'brokers.brokerid',
                'brokers.name',
                'brokers.brief_name',
                'brokers.contact',
                'brokers.email',
                'brokers.status',
                'brokers.creator_uid',
                'brokers.operation_uid',
                'brokers.revenue_type'
            )
            ->where('brokers.agencyid', $agencyId);

        $prefix = DB::getTablePrefix();
        $select->leftJoin('users', 'accounts.manager_userid', '=', 'users.user_id')
            ->addselect(
                'users.username',
                'users.user_id',
                'users.qq',
                'users.contact_phone',
                'balances.balance',
                'balances.gift',
                DB::raw('('. $prefix . 'balances.balance + '. $prefix . 'balances.gift) as total'),
                'users.date_created'
            );
        //区分houseAd和平台的代理商
        if ($affiliateId) {
            $select->where('brokers.affiliateid', $affiliateId);
        } else {
            $select->where('brokers.affiliateid', 0);
        }
        // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where(function ($select) use ($search) {
                $select->where('brokers.name', 'like', '%' . $search . '%');
                $select->orWhere('brokers.brief_name', 'like', '%' . $search . '%');
                $select->orWhere('users.contact_name', 'like', '%' . $search . '%');
                $select->orWhere('users.username', 'like', '%' . $search . '%');
                $select->orWhere('brokers.email', 'like', '%' . $search . '%');
            });
        }

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'revenue_type') {
                        $select->where('brokers.' . $k, '&', $v);
                    } else {
                        $select->where('brokers.' . $k, $v);
                    }
                }
            }
        }

        if ($userId) {
            $select->where('brokers.creator_uid', '=', $userId);
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
            $select->orderBy('status', 'desc')->orderBy('brokerid', 'desc');
        }

        $rows = $select->get();
        $list = [];
        foreach ($rows as $row) {
            $creator = User::find($row['creator_uid']);
            $row['creator_username'] = $creator['contact_name'];
            $row['creator_contact_phone'] = $creator['contact_phone'];
            //如果运营人员的id大于0
            if (0 < $row['operation_uid']) {
                $operation = User::find($row['operation_uid']);
                $row['operation_username'] = $operation['contact_name'];
                $row['operation_contact_phone'] = $operation['contact_phone'];
            } else {
                $row['operation_username'] = '-';
                $row['operation_contact_phone'] = '-';
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
}
