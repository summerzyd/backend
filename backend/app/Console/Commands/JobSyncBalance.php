<?php
/**
 * data_hourly_daily_af的 pay状态更改为已结算
 * 给媒体商的 balance 添加推广金
 * 添加媒体商的 balance_log记录
 * 扣除平台的推广金
 *
 * 2016-10-18 因为加了自营媒体进来，balance的资金会混为一起，所以月结不需要更新到 balance
 * 月结是按 hourly_af 汇总减掉已提现的计算出来
 * 所以这里只需记录媒体有一条月结记录就可以了
 */
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Models\Affiliate;
use App\Models\Agency;
use App\Models\BalanceLog;

class JobSyncBalance extends Command
{

    protected $signature = 'job_sync_balance {--num=}';

    protected $description = '每月月结';

    public function __construct()
    {
        parent::__construct();
    }
    
    public function handle()
    {
        //计算n个月的月结数据
        $num = $this->option('num') ? $this->option('num') : 1;
        $dateArr = $this->getLastMonth($num);
        $prefix = DB::getTablePrefix();
        //从每日收入明细中汇总出当月每个账户的分成
        $rows =  DB::table('data_hourly_daily_af AS df')
                    ->leftJoin('affiliates AS af', 'df.affiliateid', '=', 'af.affiliateid')
                    ->leftJoin('campaigns AS c', 'df.campaign_id', '=', 'c.campaignid')
                    ->leftJoin('clients AS cl', 'c.clientid', '=', 'cl.clientid')
                    ->whereBetween('df.date', [$dateArr[0], $dateArr[1]])
                    ->where('df.pay', 0)
                    ->where('df.affiliateid', '>', 0)
                    ->where('cl.affiliateid', 0)
                    ->select('af.account_id', 'df.affiliateid', DB::raw('SUM(af_income) AS total'))
                    ->groupBy('af.account_id')
                    ->get();
        if (!empty($rows)) {
            $params = [
                'date' => date('Y-m-d'),
                'rows' => $rows,
                'start'=> $dateArr[0],
                'end' => $dateArr[1],
                'prefix' => $prefix,
                'agencyArrTotal' => []
            ];

            DB::transaction(function ($params) use ($params) {
                DB::beginTransaction();
                $rows   =   $params['rows'];
                $date   =   $params['date'];
                $start  =   $params['start'];
                $end    =   $params['end'];
                $agencyArrTotal = $params['agencyArrTotal'];
                $prefix =   $params['prefix'];
                foreach ($rows as $k => $v) {
                    //修改 data_hourly_daily_af 表的状态，结算过之后 hourly的状态标识为已结算
                    $result = DB::table('data_hourly_daily_af')
                              ->where('affiliateid', $v->affiliateid)
                              ->where('pay', 0)
                              ->whereBetween('date', [$start, $end])
                              ->update(['pay' => 1]);
                    if (!$result) {
                        DB::rollBack();
                    } else {
                        /*
                        $count = $this->checkAccountExist($v->account_id);
                        $balance=   $this->getBalance($v->account_id);
                        //如果有记录，则更新
                        if ($count > 0) {
                            $bsql = "UPDATE {$prefix}balances SET balance = balance + {$v->total}
                                     WHERE account_id = {$v->account_id}";
                            $bresult = DB::update($bsql);
                        } else {
                            $bresult =  DB::table('balances')->insert([
                                            'account_id' => $v->account_id,
                                            'balance' => $v->total,
                                            'gift' => 0
                                        ]);
                        }
                        
                        if (!$bresult) {
                            DB::rollBack();
                        } else { */
                            //记录一条 log
                            $balance=   $this->getBalance($v->account_id);
                            $obj    =   $this->getAgencyId($v->account_id);
                            $blogResult = $this->updateBalanceLog(
                                $v->account_id,
                                $date,
                                [$obj->agencyid,
                                 0,
                                 0,
                                 $v->account_id,
                                 $v->total,
                                 2,
                                 $balance + $v->total,
                                 2,
                                 '媒体商收入分成',
                                 date('Y-m-d H:i:s')
                                ]
                            );
                           
                            if (!$blogResult) {
                                DB::rollBack();
                            }/* else {
                                //平台扣款结算
                                if (!isset($agencyArrTotal[$obj->agencyid])) {
                                    $agencyArrTotal[$obj->agencyid]['total'] = 0;
                                }
                                $agencyArrTotal[$obj->agencyid]['total'] += $v->total;
                            }*/
                        //}
                    }
                }// end foreach
                /*
                //扣除平台账号的余额
                if (!empty($agencyArrTotal)) {
                    foreach ($agencyArrTotal as $agencyId => $aval) {
                        $objAgency  =   $this->getAgencyAccountId($agencyId);
                        $reResult   =   $this->updateReduce([
                            $objAgency->account_id,
                            -abs($aval['total']),
                            abs($aval['total'])
                        ]);
                        if (empty($reResult)) {
                            DB::rollBack();
                        }
                    }
                } */
                
                //以上都没问题，提交
                DB::commit();
            });
        }
    }
    
    
    //取得日期
    private function getLastMonth($x = 1)
    {
        $first_day_of_month = date('Y-m', time()) . '-01 00:00:01';
        $t = strtotime($first_day_of_month);
        $first_day   =  date('Y-m-01', strtotime("-{$x} months", $t));
        $last_day    =  date('Y-m-t', strtotime(" -{$x} months", $t));
        $third      =  date('Y-m', strtotime(" -{$x} months", $t));
        return [$first_day, $last_day, $third];
    }
    
    
    /**
     * 插入收支明细表
     */
    private function updateBalanceLog($accountId, $date, $newData)
    {
        $row = BalanceLog::where('target_acountid', '=', $accountId)
                ->where('pay_type', '=', 2)
                ->where('create_time', 'like', "{$date}%")
                ->first();
        if (empty($row)) {
            $fields = [
                '`media_id`',
                '`operator_accountid`',
                '`operator_userid`',
                '`target_acountid`',
                '`amount`',
                '`pay_type`',
                '`balance`',
                '`balance_type`',
                '`comment`',
                '`create_time`'
            ];

            $arr_keys = array_keys($fields);
            $strArr = [];
            foreach ($arr_keys as $k => $v) {
                $strArr[]   =   '?';
            }
            $sql    =   "INSERT INTO up_balance_log(".implode(",", $fields).") VALUES (".implode(",", $strArr).")";
            return DB::update($sql, $newData);
        } else {
            $sql    =   "UPDATE up_balance_log SET media_id = ?,operator_accountid = ?,operator_userid = ?,
                         target_acountid = ?,amount = ?, pay_type = ?,balance = ?, balance_type = ?,
                         comment = ?, create_time = ? WHERE 1 AND id = ?";
            return DB::update($sql, array_merge($newData, [$row->id]));
        }
    }
    
    
    /**
     * 扣除平台的钱
     * @param unknown $newData
     */
    private function updateReduce($newData)
    {
        $fields =   array('`account_id`','`balance`');
        $arr_keys =  array_keys($fields);
        $strArr =   array();
        foreach ($arr_keys as $k => $v) {
            $strArr[]   =   '?';
        }
        $sql    =   "INSERT INTO up_balances(".implode(",", $fields).") VALUES (".implode(",", $strArr).")";
        $sql    .=  "ON DUPLICATE KEY UPDATE `balance` = `balance` - ?;";
        return DB::update($sql, $newData);
    }

    /**
     * 取得未提现有金额
     * @param $accountId
     * @return int
     */
    private function getBalance($accountId)
    {
        $balance    =   0;
        $row        =   DB::table('balances')
                        ->where('account_id', '=', $accountId)
                        ->select('balance', 'gift')
                        ->first();
        if (!empty($row)) {
            $amount = $row->balance + $row->gift;
        }
        return (!empty($amount))?$amount:$balance;
    }

    /**
     * 检查相应的账户是否已经存在并且有推广金
     * @param $accountId
     * @return mixed
     */
    private function checkAccountExist($accountId)
    {
        $count = DB::table('balances')
                    ->where('account_id', $accountId)
                    ->count();
        return $count;
    }

    /**
     * 根据AgencyId取得媒体商的
     * @param $agencyId
     * @return mixed
     */
    private function getAgencyAccountId($agencyId)
    {
        $row = Agency::where('agencyid', '=', $agencyId)
               ->select('account_id')
               ->firstOrFail();
        return $row;
    }
    
    private function getAgencyId($accountId)
    {
        $row = Affiliate::where('account_id', '=', $accountId)
                ->select('agencyid')
                ->firstOrFail();
        return $row;
    }
}
