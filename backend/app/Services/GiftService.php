<?php

namespace App\Services;

use App\Models\Gift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Tests\Controller;
use App\Models\Account;

class GiftService
{
    public static function getGiftList(
        $agencyId,
        $type,
        $userId,
        $accountId,
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null
    ) {
        $select = DB::table('gift')
            ->leftjoin('users', 'users.user_id', '=', 'gift.user_id')
            ->where('gift.agencyid', $agencyId)
            ->where('gift.target_accountid', $accountId);
        if (Account::TYPE_MANAGER != Auth::user()->account->account_type) {
            $select->where('gift.user_id', $userId);
        }
            $select->select(
                'gift.created_at',
                'gift.status',
                'gift.gift_info',
                DB::raw('FORMAT('.Gift::getTableFullName().'.amount,1) AS amount'),
                'users.contact_name'
            )
            ->orderBy('created_at', 'desc');
            ;
        //代理商或者广告主
            if ($type == 1) {
                $select->leftjoin('clients', 'clients.account_id', '=', 'gift.target_accountid')
                ->addselect('clients.contact');
                ;
            } else {
                $select->leftjoin('brokers', 'brokers.account_id', '=', 'gift.target_accountid')
                ->addselect('brokers.contact');
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
}
