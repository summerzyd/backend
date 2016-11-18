<?php

namespace App\Console\Commands;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\EmailHelper;
use App\Components\Helper\LogHelper;
use App\Models\Affiliate;
use App\Models\Agency;
use App\Models\AppInfo;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Account;
use App\Components\Config;

class JobAdStartSuspendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_ad_start_suspend_email {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For 2345 email notification while starting or suspending an ad';

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
        $str = Config::get('job_ad_start_suspend_account', $agency->agencyid);
        if (strlen($str) > 0) {
            $arr = explode('|', $str);
            foreach ($arr as $accountId) {
                $account = Account::find($accountId);
                $affiliate = $account->affiliate;
                $banner = Banner::whereIn(
                    'affiliateid',
                    ArrayHelper::map(
                        Affiliate::where('account_id', $account->account_id)->get()->toArray(),
                        'affiliateid',
                        'affiliateid'
                    )
                )->select('bannerid', 'status')->get()->toArray();


                $oldBanner = null;
                $filePath = storage_path('app/' . 'banner_' . $accountId);
                if (file_exists($filePath)) {
                    $oldBanner = file_get_contents($filePath);
                }

                if (strlen($oldBanner) < 1) {
                    file_put_contents($filePath, json_encode($banner));
                    continue;
                }

                $oldBanner = json_decode($oldBanner, true);

                LogHelper::notice(json_encode($banner) . json_encode($oldBanner));
                // 如果新的有，旧的没有，则为新增
                $mapOldBanner = ArrayHelper::index($oldBanner, 'bannerid');
                foreach ($banner as $item) {
                    if (isset($mapOldBanner[$item['bannerid']])) {
                        // 如果已经存在，则比较状态是否相同，不相同则要发邮件
                        if ($mapOldBanner[$item['bannerid']]['status'] == $item['status']) {
                            continue;
                        } else {
                            $this->conduct($item, 2, $account->user->email_address, $affiliate->mode, $item['status']);
                        }
                    } else {
                        $this->conduct($item, 1, $account->user->email_address, $affiliate->mode, $item['status']);
                    }
                }

                file_put_contents($filePath, json_encode($banner));
            }
        }
    }

    private function conduct($item, $type, $email, $mode, $status)
    {
        $banner = Banner::find($item['bannerid']);
        $campaign = $banner->campaign;
        $appInfo = $campaign->appInfo;

        $subject = "品效通广告-" . $appInfo->app_name .  "-已暂停";
        $view = 'emails.trafficker.adSuspend';
        if ($type == 1 || ($type == 2 && $status != 1)) {
            $subject = '品效通有新广告投放过来了';
            if ($mode == Affiliate::MODE_PROGRAM_DELIVERY_STORAGE) {
                $view = 'emails.trafficker.adStartStorage';
            } else {
                $view = 'emails.trafficker.adStartNoStorage';
            }
        }

        LogHelper::notice($email . $subject . $item['bannerid']);
        EmailHelper::sendEmail(
            $view,
            [
                'subject' => $subject,
                'msg' => [
                    'app_name' => $appInfo->app_name,
                ],
            ],
            $email
        );
    }
}
