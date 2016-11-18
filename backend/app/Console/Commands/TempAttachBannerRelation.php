<?php
namespace App\Console\Commands;

use App\Services\CampaignService;
use App\Models\BannerBilling;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Affiliate;
use App\Models\Zone;
use App\Models\AdZoneAssoc;

class TempAttachBannerRelation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_attach_banner_relation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'attach banner and zone relation';

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
        $result = CampaignService::getAttachRelationBanners();
        foreach ($result as $item) {
            $this->notice('fix job: attach banner relation '.$item['bannerid']);
            CampaignService::attachBannerRelationChain($item['bannerid']);
        }
    }
}
