<?php
namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\ExpenseLog;
use App\Models\DeliveryLog;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use App\Models\OperationDetail;

class TempRepairOperationDetail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_repair_operation_detail {--build-date=}  {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修复 operation_detail的数据';

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
        $agencyId = $this->option('agencyid') ? $this->option('agencyid') : 0;
        if ($agencyId > 0) {
            $model = Agency::find($agencyId);
            if ($model) {
                $this->finish($model);
            }
        } else {
            $models = Agency::get();
            foreach ($models as $model) {
                $this->finish($model);
            }
        }
    }
    
    public function finish(Agency $agency)
    {
        $date = $this->option('build-date') ? $this->option('build-date') : date('Y-m-d');
        $startTime = date('Y-m-d H:i:s', strtotime($date . ' -8 hour'));
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +1 days'));
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        $clients = 0;
        $partners = 0;
        $affiliates = 0;
        
        //获取媒体商的支出
        $res = ExpenseLog::leftJoin('campaigns', 'expense_log.campaignid', '=', 'campaigns.campaignid')
                ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
                ->where('source', '<>', 1)
                ->where('zoneid', '>', 0)
                ->where('clients.agencyid', $agency->agencyid)
                ->where('clients.affiliateid', $defaultAffiliateid)
                ->where('actiontime', '>=', $startTime)
                ->where('actiontime', '<', $endTime)
                ->select(
                    DB::raw('sum(af_income) as affiliates')
                )
                ->first();
        if (!empty($res)) {
            $affiliates =  number_format($res->affiliates, 2, '.', '');
        }

        //获取广告主的支出
        $query = DeliveryLog::leftJoin('campaigns', 'delivery_log.campaignid', '=', 'campaigns.campaignid')
                ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
                ->where('source', '<>', 1)
                ->where('zoneid', '>', 0)
                ->where('clients.agencyid', $agency->agencyid)
                ->where('clients.affiliateid', $defaultAffiliateid)
                ->where('actiontime', '>=', $startTime)
                ->where('actiontime', '<', $endTime)
                ->select(
                    DB::raw('sum(price) as clients')
                )
                ->first();
        if (!empty($query)) {
            $clients = number_format($query->clients, 2, '.', '');
        }
        
        //平台
        $partners = number_format(($clients - $affiliates), 2, '.', '');
        
        $result = OperationDetail::where('day_time', '=', $date)
                ->where('agencyid', $agency->agencyid)
                ->update(array(
                    'audit_clients' => $clients,
                    'audit_traffickers' => $affiliates,
                    'audit_partners' => $partners,
                ));
        $this->notice('Success');
  
    }
}
