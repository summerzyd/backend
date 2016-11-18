<?php
namespace App\Console\Commands;

use App\Services\CampaignService;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;
use App\Models\Affiliate;

class JobBannerBilling extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_banner_billing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for making banner billing.';

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
        \DB::setFetchMode(\PDO::FETCH_ASSOC);
        $result = \DB::table('banners as b')
            ->leftJoin('affiliates as aff', 'b.affiliateid', '=', 'aff.affiliateid')
            ->where('aff.mode', '<>', Affiliate::MODE_ADX)
            ->where('aff.affiliate_type', Affiliate::TYPE_ADN)
            ->select('b.bannerid')
            ->get();
        $bannerid = array_column($result, 'bannerid');
        CampaignService::updateBannerBilling($bannerid);
    }
}
