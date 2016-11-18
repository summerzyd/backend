<?php
namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\DataHourlyDailyAf;
use App\Models\Product;
use App\Models\Recharge;
use App\Services\CampaignService;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\EmailHelper;
use App\Models\Affiliate;
use App\Models\Agency;
use App\Components\Config;

class JobMonitorForEveryDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_monitor_for_every_day {--agencyid=}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '现网数据监控项，每天凌晨3点';
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
    
    public function finish(Agency $agency)
    {
        //生成affiliates表的income_amount字段
        $this->generateAffiliateMode();
        $this->monitorAppStoreLink($agency);
    }

    private function generateAffiliateMode()
    {
        $models = Affiliate::get();
        foreach ($models as $model) {
            $model->income_amount = DataHourlyDailyAf::getAmount($model->account_id);
            //自营媒体
            if (($model->kind & Affiliate::KIND_SELF) == Affiliate::KIND_SELF) {
                $model->self_income_amount = DB::table('recharge AS r')
                    ->leftJoin('users AS u', 'r.user_id', '=', 'u.user_id')
                    ->leftJoin('clients AS c', 'r.target_accountid', '=', 'c.account_id')
                    ->where('c.affiliateid', $model->affiliateid)
                    ->where('r.status', Recharge::STATUS_APPROVED)
                    ->sum('r.amount');
            }
            $model->save();
        }
    }
    
    /**
     *
     */
    private function monitorAppStoreLink($agency)
    {
        $invalid_links = [];
        $rows = DB::table('campaigns')
            ->leftJoin('appinfos', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('campaigns.platform', '=', 'appinfos.platform');
            })
            ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
            ->leftJoin('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->select(
                ['campaigns.clientid',
                    'appinfos.app_name',
                    'products.id',
                    'products.link_url',
                    'products.link_name']
            )
            ->where('campaigns.ad_type', '=', Campaign::AD_TYPE_APP_STORE)
            ->where('clients.agencyid', '=', $agency->agencyid)
            ->get();

        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (!CampaignService::validURL($row->link_url)) {
                    $invalid_links[] = array(
                        'link_name' => $row->link_name,
                        'app_name' => $row->app_name,
                        'link_url' => $row->link_url
                    );
                    Product::where('id', $row->id)->update([
                        'link_status' => Product::LINK_STATUS_DISABLE,
                    ]);
                }
            }
            $mail_addresses = Config::get('mail_invalid_appstorelink', $agency->agencyid);
            $mailto_addresses = explode(";", $mail_addresses);
            $mail = [];
            $mail['subject'] = "{$agency->name}-ADN失效的appstore推广链接汇总";
            $mail['msg']['data'] = $invalid_links;
            if (count($mailto_addresses) > 0) {
                foreach ($mailto_addresses as $mailto) {
                    EmailHelper::sendEmail(
                        'emails.command.invalidAppstorelink',
                        $mail,
                        $mailto
                    );
                }
            }
        }
    }
}
