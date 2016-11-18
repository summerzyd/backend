<?php
namespace App\Http\Controllers;

use Auth;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;

class CommonController extends Controller
{
    /**
     * 获取当前登录账号对应媒体的计费类型
     *
     * @return \Illuminate\Http\Response
     */
    public function affiliateRevenueType()
    {
        if (Account::TYPE_TRAFFICKER == Auth::user()->account->account_type) {
            $rows = [];
            $affiliateid = Auth::user()->account->affiliate->affiliateid;
            if (!empty($affiliateid)) {
                $rows = $this->getRevenueType($affiliateid);
            }
            return $this->success(
                [
                    'revenue_type' => $rows,
                ]
            );
        } elseif (Account::TYPE_ADVERTISER == Auth::user()->account->account_type) {
            $rows = [];
            $affiliateid = Auth::user()->account->client->affiliateid;
            if (!empty($affiliateid)) {
                $rows = $this->getRevenueType($affiliateid);
            }
            return $this->success(
                [
                    'revenue_type' => $rows,
                ]
            );
        } else {
            $rows = [];
            return $this->success(['revenue_type' => $rows]);
        }
    }
    
    
    /**
     *
     */
    private function getRevenueType($affiliateid)
    {
        $rows = [];
        $row = DB::table('affiliates_extend')
                ->where('affiliateid', $affiliateid)
                ->where('ad_type', Campaign::AD_TYPE_APP_MARKET)
                ->distinct()
                ->get(['revenue_type']);
        
        if (!empty($row)) {
            foreach ($row as $k => $v) {
                $rows[] = $v->revenue_type;
            }
        }
        return $rows;
    }
}
