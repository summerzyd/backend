<?php
namespace App\Console\Commands;

use App\Components\JpGraph;
use App\Models\Agency;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class JobConsumptionTrend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_consumption_trend {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for Generate consumption trend pic';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /*
     * 极限值为100
     */
    private $limit = 100;

    /**
     * Execute the console command.
     *
     * @return mixed
     *
     *
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
        $cids =  $this->getCampaigns($agency->agencyid);
        $start = $start = date('Y-m-d', strtotime("-30 days"));
        $end = date('Y-m-d', strtotime("-1 days"));
        $prefix = DB::getTablePrefix();
        if (count($cids) > 0) {
            foreach ($cids as $campaign) {
                $res = DB::table("data_hourly_daily as h")
                    ->join('banners as b', 'b.bannerid', '=', 'h.ad_id')
                    ->join('affiliates as aff', 'aff.affiliateid', '=', 'b.affiliateid')
                    ->join('campaigns as c', 'h.campaign_id', '=', 'c.campaignid')
                    ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
                    ->where('h.campaign_id', $campaign->campaignid)
                    ->whereBetween('h.date', [$start, $end])
                    ->select(
                        DB::raw('IFNULL(SUM(' .$prefix . 'h.total_revenue),0) as revenue'), //广告主消耗
                        'h.date'
                    )
                    ->groupBy('h.date')
                    ->orderBy('h.date')
                    ->get();
                $list = [];
                foreach ($res as $val) {
                    $list[$val->date] = $val->revenue;
                }
                $file = base_path('public') . '/images/report/campaign'.$campaign->campaignid;
                $_start = $start;
                for ($i = 0; $i < 30; $i++) {
                    $list = array_add($list, $_start, 0);
                    $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
                }
                ksort($list);
                $data = array_values($list);//print_r($data);exit;
                $flag = JpGraph::reportLineConsumption($data, $file . '.jpg');
                if (!$flag) {
                    LogHelper::info("Generate consumption trend pic,
                        campaign {$campaign->campaignid} failed");
                }
            }
        }
    }
    private function getCampaigns($agencyId)
    {
        $sql = \DB::table('campaigns as c')
            ->join('clients as cli', 'c.clientid', '=', 'cli.clientid')
            ->where('cli.agencyid', $agencyId)
            ->select('c.campaignid')
            ->get();
        return $sql;
    }
}
