<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Models\Banner;
use App\Models\Affiliate;
use App\Models\OperationClient;
use App\Models\Campaign;

class JobOperationClients extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_operation_clients {--build-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'operation clients';

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
        $buildDate = $this->option('build-date') ? $this->option('build-date') : date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($buildDate)));
        
        $campaigns = $this->getValidCampaigns($yesterday);
        foreach ($campaigns as $row) {
            //联盟所有数据都需要审核
            $status = OperationClient::STATUS_PENDING_AUDIT;
            $issue = OperationClient::ISSUE_NOT_APPROVAL;
            if ($row->affiliateid > 0) {
                //自营默认审核通过
                $type = OperationClient::TYPE_PROGRAM_DELIVERY;
                $status = OperationClient::STATUS_ACCEPT;
                $issue = OperationClient::ISSUE_APPROVAL;
            } elseif ($row->revenue_type == Campaign::REVENUE_TYPE_CPA
                || $row->revenue_type == Campaign::REVENUE_TYPE_CPT
                || $this->hasArtificialDelivery($row->campaignid)) {
                //CPA、CPT、人工投放需审核
                $type = OperationClient::TYPE_ARTIFICIAL_DELIVERY;
            } else {
                $type = OperationClient::TYPE_PROGRAM_DELIVERY;
            }
            DB::table('operation_clients')->insert([
                'campaign_id' => $row->campaignid,
                'type' => $type,
                'status' => $status,
                'issue' => $issue,
                'date' => $yesterday
            ]);
        }
        $this->notice("$yesterday is succeed");
    }

    private function getValidCampaigns($yesterday)
    {
        $prefix = DB::getTablePrefix();
        $rows = DB::table('campaigns as c')
            ->leftJoin('clients AS cl', 'c.clientid', '=', 'cl.clientid')
            ->select('c.campaignid', 'c.revenue_type', 'cl.affiliateid')
            ->whereExists(function ($query) use ($prefix) {
                $query->select(DB::raw(1))
                ->from('banners AS b')
                ->whereRaw("{$prefix}b.campaignid = {$prefix}c.campaignid")
                ->whereIn('b.status', [
                Banner::STATUS_PUT_IN,
                Banner::STATUS_SUSPENDED
                ]);
            })
            ->whereNotExists(function ($query) use ($prefix, $yesterday) {
                $query->select(DB::raw(1))
                ->from('operation_clients AS oc')
                ->whereRaw("{$prefix}oc.campaign_id = {$prefix}c.campaignid")
                ->where('oc.date', $yesterday);
            })
            //->where('cl.affiliateid', 0)
            ->orderBy('campaignid')
            ->get();
        return $rows;
    }

    private function hasArtificialDelivery($cid)
    {
        $prefix = DB::getTablePrefix();
        $has = DB::table('banners as b')->select(DB::raw(1))
            ->join('affiliates as af', 'af.affiliateid', '=', 'b.affiliateid')
            ->where('af.mode', Affiliate::MODE_ARTIFICIAL_DELIVERY)
            ->where('b.campaignid', $cid)
            ->first();
        return $has ? true : false;
    }
}
