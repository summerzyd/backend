<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class JobMonitorForOneHour extends Command
{
    protected $signature = 'job_monitor_for_one_hour';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '现网数据监控项，每小时监测一次';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    public function handle()
    {
        //监控广告主每小时的资金变动情况
        $this->monitorClientsBalance();
    }
    
    private function monitorClientsBalance()
    {
        $time = date('Y-m-d H', strtotime("-1 hour"));
        $inserData = [];
        $sql = "
                SELECT c.account_id, u.balance, u.gift
                FROM up_balances AS u
                INNER JOIN (SELECT account_id FROM up_clients) AS c ON u.account_id = c.account_id
                WHERE 1
            ";
        $rows = DB::select($sql);
        
        //上一次的数据信息
        $mSql = "
                SELECT account_id, balance, gift FROM
                up_monitor_balance WHERE 1 
                AND DATE_FORMAT(created_time, '%Y-%m-%d %H') = '{$time}' 
            ";
        $mData = [];
        $mRows = DB::select($mSql);
        if (!empty($mRows)) {
            foreach ($mRows as $key => $val) {
                $mData[$val->account_id] = [
                    'balance' => $val->balance,
                    'gift' => $val->gift
                ];
            }
        }
            
        if (!empty($rows)) {
            foreach ($rows as $k => $v) {
                if (!empty($mData)) {
                    $lastBalance = !(empty($mData[$v->account_id]['balance'])) ? $mData[$v->account_id]['balance'] : 0;
                    $lastGift = !(empty($mData[$v->account_id]['gift'])) ? $mData[$v->account_id]['gift'] : 0;
                        $inserData[$v->account_id] = [
                            'balance' => $v->balance,
                            'last_balance' => $lastBalance,
                            'differ_balance' => $v->balance - $lastBalance,
                            'gift' => $v->gift,
                            'last_gift' => $lastGift,
                            'differ_gift' => $v->gift - $lastGift
                        ];
                } else {
                    $inserData[$v->account_id] = [
                        'balance' => $v->balance,
                        'last_balance' => $v->balance,
                        'differ_balance' => 0,
                        'gift' => $v->gift,
                        'last_gift' => $v->gift,
                        'differ_gift' => 0
                    ];
                }
            }
        }
            
        if (!empty($inserData)) {
            $inData = [];
            $actionTime = date('Y-m-d H:i:s');
            foreach ($inserData as $accountId => $av) {
                $data = [
                    $accountId,
                    $time,
                    $av['balance'],
                    $av['last_balance'],
                    $av['differ_balance'],
                    $av['gift'],
                    $av['last_gift'],
                    $av['differ_gift'],
                    $actionTime
                ];
                    
                $inData[] = "'".implode("','", $data)."'";
            }
            $sql = "INSERT INTO up_monitor_balance(account_id, actiontime, balance, last_balance, differ_balance,
                        gift, last_gift, differ_gift, created_time) VALUES (".implode("),(", $inData).")
                        ON DUPLICATE KEY UPDATE actiontime = VALUES(actiontime);";
                
            DB::insert($sql);
        }
    }
}
