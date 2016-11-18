<?php
 namespace App\Console\Commands;

use App\Components\Config;
use App\Components\Formatter;
use App\Components\Helper\EmailHelper;
use App\Models\Banner;
use App\Services\CampaignService;
use App\Services\MessageService;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;
use App\Models\OperationLog;
use Illuminate\Support\Facades\Redis;

class JobPauseOrRecoverBanners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_pause_recover_banners {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'pause and recover banner';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $type = $this->argument('type');

        if ($type === 'pause') {
            $this->notice('=========pause banners');

            //查询需要暂定的广告列表
            $pauseBanners = $this->getPauseBannerList();
            if (!empty($pauseBanners)) {
                foreach ($pauseBanners as $_pauseBanner) {
                    $args = [];
                    $this->notice('=========pause by media_day_limit,
                                   bannerid(' . $_pauseBanner['bannerid'] . ')');
                    CampaignService::modifyBannerStatus(
                        $_pauseBanner['bannerid'],
                        Banner::STATUS_SUSPENDED,
                        true,
                        ['pause_status' => Banner::PAUSE_STATUS_EXCEED_DAY_LIMIT]
                    );

                    $args[] = $_pauseBanner['app_name'];//广告名称
                    $args[] = $_pauseBanner['name'];//媒体全称
                    $args[] = $_pauseBanner['af_day_limit'] > 0 ?
                        Formatter::asDecimal($_pauseBanner['af_day_limit']) :
                        Formatter::asDecimal($_pauseBanner['day_limit']);//日限额
                    $args[] = $_pauseBanner['app_name'];//广告名称
                    $args[] = $_pauseBanner['name'];//媒体全称
                    $message = CampaignService::formatWaring(6045, $args);
                    OperationLog::store([
                        'category' => OperationLog::CATEGORY_BANNER,
                        'target_id' => $_pauseBanner['bannerid'],
                        'type' => OperationLog::TYPE_SYSTEM,
                        'message' => $message,
                    ]);
                }
                if (count($pauseBanners) > 0) {
                    $this->sendMail(array_column($pauseBanners, 'bannerid'), $type);
                }
            }

            $this->notice('=========pause banners accomplished!');
        } elseif ($type === 'recover') {
            $this->notice('=========recover banners');
            //查询需要恢复投放的广告列表
            $recoverBanners = $this->getRecoverBannerList();
            if (!empty($recoverBanners)) {
                foreach ($recoverBanners as $_recoverBanner) {
                    $args = [];
                    $this->notice('=========recover by media_day_limit,
                                   bannerid(' . $_recoverBanner['bannerid'] . '))');
                    CampaignService::modifyBannerStatus(
                        $_recoverBanner['bannerid'],
                        Banner::STATUS_PUT_IN,
                        true,
                        ['pause_status' => Banner::PAUSE_STATUS_MEDIA_MANUAL]
                    );

                    $args[] = $_recoverBanner['app_name'];//广告名称
                    $args[] = $_recoverBanner['name'];//媒体全称
                    $message = CampaignService::formatWaring(6047, $args);
                    OperationLog::store([
                        'category' => OperationLog::CATEGORY_BANNER,
                        'target_id' => $_recoverBanner['bannerid'],
                        'type' => OperationLog::TYPE_SYSTEM,
                        'message' => $message,
                    ]);
                }
            }
            $this->notice('=========recover banners accomplished!');

        } else {
            $this->notice('=========undefined operation type:' . $type);
            $this->notice('undefined operation type, need \'pause\' OR \'recover\'');
        }

       // $impressLimit = explode('|', Config::get('biddingos.impression_limit'));

        $deliveryRedis = Redis::connection('redis_adserver');
        $date = date('Ymd');
        $result = \DB::table('banners AS b')
            ->leftJoin('banners_billing AS bb', 'b.bannerid', '=', 'bb.bannerid')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            // ->whereIn('b.affiliateid', $impressLimit)
            ->where('b.revenue_type', Campaign::REVENUE_TYPE_CPM)
            ->whereIn('b.status', [Banner::STATUS_PUT_IN, Banner::STATUS_SUSPENDED])
            ->select('b.bannerid', 'bb.af_income', 'c.day_limit', 'b.af_day_limit')
            ->get();
        foreach ($result as $item) {
            if ($item['af_income'] > 0) {
                $impression = intval((($item['af_day_limit'] > 0 ?
                            $item['af_day_limit'] : $item['day_limit']) / $item['af_income']) * 1000);
            } else {
                $impression = 1000;
            }
            if ($impression < 1000) {
                $impression = 1000;
            }
            $this->notice(" impression_limit_{$item['bannerid']}_{$date}: " . $impression);
            $deliveryRedis->set("impression_limit_{$item['bannerid']}_{$date}", $impression);
        }
    }

    /**
     * 获取需要暂停的banner列表,自营媒体广告不需要媒体日限额暂停
     * @return array
     */
    private function getPauseBannerList()
    {
        $pauseBannerList = [];
        $prefix = DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $deliveryBanners = DB::table('banners AS b')
            ->join('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->join('appinfos AS a', function ($join) {
                $join->on('a.app_id', '=', 'c.campaignname')
                    ->on('a.platform', '=', 'c.platform');
            })
            ->leftJoin('clients', 'clients.clientid', '=', 'c.clientid')
            ->leftJoin('affiliates AS af', 'af.affiliateid', '=', 'b.affiliateid')
            ->join('zones AS z', 'z.affiliateid', '=', 'b.affiliateid')
            ->join('delivery_log AS dl', function ($join) {
                $join->on('dl.campaignid', '=', 'b.campaignid')
                    ->on('z.zoneid', '=', 'dl.zoneid');
            })
            ->select(
                'b.af_day_limit',
                'b.campaignid',
                'b.bannerid',
                'b.affiliateid',
                'c.day_limit',
                'a.app_name',
                'af.name',
                DB::raw("sum({$prefix}dl.price) AS price")
            )
            ->where('clients.affiliateid', 0)
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.revenue_type', '<>', Campaign::REVENUE_TYPE_CPA)
            ->whereRaw("{$prefix}dl.actiontime >= DATE_SUB(CURDATE(), interval 8 hour)")
            ->whereRaw("{$prefix}dl.actiontime < date_add(DATE_SUB(CURDATE(), interval 8 hour), interval 1 day)")
            ->groupBy('b.bannerid')
            ->get();

        $capBanners = DB::table('banners AS b')
            ->join('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->join('appinfos AS a', function ($join) {
                $join->on('a.app_id', '=', 'c.campaignname')
                    ->on('a.platform', '=', 'c.platform');
            })
            ->leftJoin('clients', 'clients.clientid', '=', 'c.clientid')
            ->leftJoin('affiliates AS af', 'af.affiliateid', '=', 'b.affiliateid')
            ->join('zones AS z', 'z.affiliateid', '=', 'b.affiliateid')
            ->join('expense_log AS dl', function ($join) {
                $join->on('dl.campaignid', '=', 'b.campaignid')
                    ->on('z.zoneid', '=', 'dl.zoneid');
            })
            ->select(
                'b.af_day_limit',
                'b.campaignid',
                'b.bannerid',
                'b.affiliateid',
                'c.day_limit',
                'a.app_name',
                'af.name',
                DB::raw("sum({$prefix}dl.af_income) AS price")
            )
            ->where('clients.affiliateid', 0)
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.revenue_type', '=', Campaign::REVENUE_TYPE_CPA)
            ->whereRaw("{$prefix}dl.actiontime >= DATE_SUB(CURDATE(), interval 8 hour)")
            ->whereRaw("{$prefix}dl.actiontime < date_add(DATE_SUB(CURDATE(), interval 8 hour), interval 1 day)")
            ->groupBy('b.bannerid')
            ->get();

        $banners = array_merge($deliveryBanners, $capBanners);
        $sogou = Config::get('biddingos.sogouAffiliateId');
        if (!empty($banners)) {
            foreach ($banners as $_banner) {
                if ($_banner['affiliateid'] == $sogou) {
                    //搜狗媒体日限额为设置的80%
                    $afDayLimit = $_banner['af_day_limit'] == 0 ?
                        floatval($_banner['day_limit']) * 0.8 : floatval($_banner['af_day_limit']) * 0.8;

                    if ($_banner['day_limit'] > 0 && $_banner['af_day_limit'] == 0
                        && $afDayLimit <= $_banner['price']
                    ) {
                        $pauseBannerList[] = $_banner;
                    }

                    if ($_banner['af_day_limit'] > 0 && $afDayLimit <= $_banner['price']) {
                        $pauseBannerList[] = $_banner;
                    }
                } else {
                    if ($_banner['day_limit'] > 0 && $_banner['af_day_limit'] == 0
                        && $_banner['day_limit'] <= $_banner['price']
                    ) {
                        $pauseBannerList[] = $_banner;
                    }

                    if ($_banner['af_day_limit'] > 0 && $_banner['af_day_limit'] <= $_banner['price']) {
                        $pauseBannerList[] = $_banner;
                    }
                }
            }
        }
        return $pauseBannerList;
    }

    /**
     * 需要启动的banner列表
     * @return mixed
     */
    private function getRecoverBannerList()
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $banners = DB::table('banners AS b')
            ->join('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->join('appinfos AS a', function ($join) {
                $join->on('a.app_id', '=', 'c.campaignname')
                     ->on('a.platform', '=', 'c.platform');
            })
            ->leftJoin('affiliates AS af', 'af.affiliateid', '=', 'b.affiliateid')
            ->where('b.status', Banner::STATUS_SUSPENDED)
            ->where('b.pause_status', Banner::PAUSE_STATUS_EXCEED_DAY_LIMIT)
            ->select(
                'b.af_day_limit',
                'b.campaignid',
                'b.bannerid',
                'a.app_name',
                'c.day_limit',
                'af.name'
            )
            ->get();

        return $banners;
    }
    /**
     * 发送邮件
     * @param $campaignIds
     * @param $operationType
     */
    private function sendMail($bannerIds, $operationType)
    {
        //获取要发送邮件的banner广告信息
        $info4Mail = $this->getBannerInfo4Mail($bannerIds);

        $title = [
            'pause' => '广告[ %s ]暂停了，因为达到%s设定的媒体日限额'
        ];
        $content_info_manage = [
            'pause' => '[ %s ]暂停了，因为达到%s设定的媒体日限额，请知晓。'
        ];

        //获取联盟运营的邮箱
        //user_role=1销售  2媒介 3财务 4管理员 5运营
        $userInfo = MessageService::getPlatUserInfo(array_column($info4Mail, 'agencyid'));
        $list = [];
        foreach ($userInfo as $item) {
            $list[$item['agencyid']][] =  $item['email_address'];
        }
        foreach ($info4Mail as $item) {
            $email_title = sprintf($title[$operationType], $item['app_name'], $item['name']);

            //发送联盟运营 会抄送多个运营
            $email_content = sprintf($content_info_manage[$operationType], $item['app_name'], $item['name']);
            EmailHelper::sendEmail('emails.jobs.pauseBanner', [
                'subject' => $email_title,
                'msg' => [
                    'content' => $email_content,
                ],
            ], $list[$item['agencyid']]);
        }
    }

    /**
     * 获取达到媒体日限额的媒体商名称及广告名称
     * @param $bannerIds
     * @return mixed
     */
    private function getBannerInfo4Mail($bannerIds)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $banners = DB::table('banners AS b')
            ->join('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->join('affiliates AS a', 'b.affiliateid', '=', 'a.affiliateid')
            ->join('appinfos AS ua', function ($join) {
                $join->on('ua.app_id', '=', 'c.campaignname')
                    ->on('ua.platform', '=', 'c.platform');
            })
            ->whereIn('b.bannerid', $bannerIds)
            ->select('ua.app_name', 'a.name', 'a.agencyid')
            ->get();
        return $banners;
    }
}
