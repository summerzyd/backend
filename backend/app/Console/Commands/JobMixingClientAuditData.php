<?php
namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\OperationClient;
use Illuminate\Support\Facades\DB;

class JobMixingClientAuditData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_mixing_client_audit_data {--build-date=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '刷新广告主审计数据';

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
    /**
     * Execute the console command with agency
     * @param $agency
     * @return mixed
     */
    public function finish(Agency $agency)
    {
        $buildDate = $this->option('build-date');
        $dates = [];
        if ($buildDate) {
            $dates[] = $buildDate;
        } else {
            $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
            $subHour = date('Y-m-d H:00:00', strtotime('-1 hour'));
            //获取近1天的审计的修改记录
            $rows = DB::table('operation_clients as p')
                ->join('campaigns as c', 'p.campaign_id', '=', 'c.campaignid')
                ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
                ->where('cli.agencyid', '>=', $agency->agencyid)
                ->where('p.check_date', '>=', $yesterday)
                ->where('p.check_date', '<', $subHour)
                ->select('p.date')
                ->distinct()
                ->get();
            foreach ($rows as $row) {
                $dates[] = $row->date;
            }
        }
        foreach ($dates as $date) {
            $this->call('job_recover_daily_data', array(
                '--start-date' => $date,
                '--end-date' => $date,
                '--role' => 'client',
                '--agencyid' => $agency->agencyid
            ));
            
            $this->call('job_balance_log', array(
                '--build-date' => date("Y-m-d", strtotime("$date +1 day")),
                '--agencyid' => $agency->agencyid
            ));
        }
    }
}
