<?php
namespace App\Console\Commands;

use App\Models\ExpenseLog;
use Illuminate\Support\Facades\DB;
use App\Components\Config;

class JobDeleteSogouDownloadLog extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_delete_sogou_download_log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'delete_sogou_download_log';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        // 数据库为UTC时间，需减去8小时
        $startDateTime = date("Y-m-d H:i:s", strtotime('-8 hour', strtotime($yesterday)));
        $endDateTime = date("Y-m-d H:i:s", strtotime('+16 hour', strtotime($yesterday)));
        
        $zoneId = Config::get('biddingos.sogouZoneId');
        
        $this->notice("sogou delete expense with zoneid:{$zoneId} start:{$startDateTime} end:{$endDateTime} ");
        
        //遍历前一天已经下载完成的记录中是否存在搜狗上报过来的记录中
        $rows = DB::table('delivery_log')->where("zoneid", '=', $zoneId)
            ->where('target_type', '=', 'net')
            ->where('target_cat', '=', 'ip')
            ->where('actiontime', '>=', $startDateTime)
            ->where('actiontime', '<', $endDateTime)
            ->get();
        
        $this->notice("sogou select up_delivery_log count:" . count($rows));
        
        foreach ($rows as $node) {
            $this->notice("sogou delete " . json_encode($node));
            
            // 删除delivery_log down_log 记录,并且在up_balances补回广告主的钱(区分推广金、赠送金)
            $result = DB::table('delivery_log')
                ->where('deliveryid', '=', $node->deliveryid)
                ->delete();
            if (empty($result)) {
                $this->error("sogou delete up_delivery_log failed " . json_encode($node));
            }
            
            $balance = 0; // 待补回的推广金金额
            $gift = 0; // 待补回的赠送金金额
            
            if ($node->price - $node->price_gift > 0) {
                $balance = $balance + ($node->price - $node->price_gift);
            }
            
            if ($node->price_gift > 0) {
                $gift = $gift + $node->price_gift;
            }
            
            
            $result = ExpenseLog::where("zoneid", '=', $zoneId)
                ->where('target_type', '=', 'net')
                ->where('target_cat', '=', 'ip')
                ->where('actiontime', '>=', $startDateTime)
                ->where('actiontime', '<', $endDateTime)
                ->where('campaignid', '=', $node->campaignid)
                ->where('target_id', '=', $node->target_id)
                ->delete();
            
            if (empty($result)) {
                $this->error("sogou delete up_expense_log failed " . json_encode($node));
            }
            
            $accountId = DB::table('clients as cl')
                ->join('campaigns as c', 'c.clientid', '=', 'cl.clientid')
                ->where('c.campaignid', $node->campaignid)
                ->select('cl.account_id')
                ->first();
            
            if ($accountId && $balance >= 0 && $gift >= 0) {
                $this->notice("update balance : {$balance} gift : {$gift} account_id : {$accountId->account_id}");
                
                $result = DB::table('balances')
                    ->where('account_id', $accountId->account_id)->update([
                        'balance' => DB::raw("balance+{$balance}"),
                        'gift' => DB::raw("gift+{$gift}")
                    ]);
            }
        }
    }
}
