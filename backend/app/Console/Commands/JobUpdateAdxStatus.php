<?php
namespace App\Console\Commands;

use App\Components\Adx\AdxFactory;
use App\Models\Affiliate;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Client;
use App\Services\CampaignService;

class JobUpdateAdxStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_update_adx_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'request adx status and update banner status';

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
        $affiliates = \DB::table('affiliates')
            ->where('mode', Affiliate::MODE_ADX)
            ->where('affiliates_status', Affiliate::STATUS_ENABLE)
            ->select('affiliateid', 'adx_class')
            ->get();
        $list_affiliateId = [];
        foreach ($affiliates as $v) {
            if (!empty($v['adx_class'])) {
                $list_affiliateId[$v['affiliateid']] = $v['adx_class'];
            }
        }

        $affiliates = array_column($affiliates, 'affiliateid');
        $banners = \DB::table('banners AS b')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('clients AS cl', 'cl.clientid', '=', 'c.clientid')
            ->where('cl.clients_status', Client::STATUS_ENABLE)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->whereIn('b.status', [Banner::STATUS_PENDING_MEDIA, Banner::STATUS_PUT_IN])
            ->whereIn('b.affiliateid', $affiliates)
            ->select('b.bannerid', 'b.affiliateid')
            ->get();
        foreach ($banners as $item) {
            if (isset($list_affiliateId[$item['affiliateid']])) {
                //获取ADX实例
                $instance = AdxFactory::getClass($list_affiliateId[$item['affiliateid']]);
                //调用ADX状态，获取当前值。
                \DB::setFetchMode(\PDO::FETCH_CLASS);
                $this->notice('bannerid ' . $item['bannerid'] . ' status');
                $ret = $instance->status($item['bannerid']);
                $b = Banner::find($item['bannerid']);
                if ($ret['code'] == Banner::ADX_STATUS_APPROVED) {
                    CampaignService::modifyBannerStatus(
                        $item['bannerid'],
                        Banner::STATUS_PENDING_PUT,
                        false
                    );
                    $b->affiliate_checktime = date('Y-m-d h:i:s');
                    $this->notice('bannerid' . $item['bannerid'] . 'approved success');
                } elseif ($ret['code'] == Banner::ADX_STATUS_REJECT) {
                    CampaignService::modifyBannerStatus(
                        $item['bannerid'],
                        Banner::STATUS_NOT_ACCEPTED,
                        false
                    );
                    $this->notice('bannerid ' . $item['bannerid'] . ' reject');
                }
                $b->comments = $ret['msg'];
                $b->save();
            }
        }
    }
}
