<?php
namespace App\Console\Commands;

use App\Components\Helper\EmailHelper;
use App\Models\Agency;
use App\Services\MessageService;
use App\Models\Affiliate;
use App\Models\Banner;
use App\Models\Campaign;
use Maatwebsite\Excel\Facades\Excel;

class JobDataHourTotal extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_data_hour_total {--datetime=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for sending hour total data e-mail.';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function finish(Agency $agency)
    {
        // AND的当前时间
        $time = $this->option('datetime') ? $this->option('datetime') : date('Y-m-d H:i:s');
        $yesterdayTime = date('Y-m-d H:i:s', strtotime('-1 day', strtotime($time)));
        //当前汇总的总数据
        $adminData[] = $this->getFormatData($time, $yesterdayTime, 0, $agency->agencyid);

        $toAdmin = [];
        //汇总数据不参与排序
        //$toAdmin[] = $adminData;
        //所有媒体商数据收入，展示等数据
        $affiliate = Affiliate::where('affiliates_status', Affiliate::STATUS_ENABLE)
            ->where('agencyid', $agency->agencyid)
            ->whereIn('kind', array('1', '3'))
            ->select('affiliateid')
            ->get();
        if (count($affiliate) > 0) {
            foreach ($affiliate as $aff) {
                $tmp = $this->getAffiliateData($time, $yesterdayTime, $aff->affiliateid, $agency->agencyid);
                if (!$tmp['isNull']) {
                    $toAdmin[] = $tmp;
                }
            }
        }
        //按收入降序排序，下载量降序
        $data = $toAdmin;
        if (!empty($data)) {
            foreach ($data as $key => $row) {
                $revenue[$key] = $row['revenue'];
                $conversions[$key] = $row['conversions'];
            }
            array_multisort($revenue, SORT_DESC, $conversions, SORT_DESC, $data);
        }
        //排序之后再跟汇总的数据合并
        $data = !empty($data) ? array_merge($adminData, $data) : $adminData;
        // 发送邮件给管理员和运营
        $H = date('Y年m月d日H点', strtotime($time));
        $mail = array();
        $mail['subject'] = "截至{$H}，ADN媒体商数据";
        $mail['msg']['data'] = $data;
        $mail['msg']['H'] = $H;
        //生成 Excel, 然后发送附件
        $name = date('YmdH', strtotime($time)) . 'All';
        $result = $this->createExcel($name, $data);
        if (!empty($result)) {
            $mail['attach']['excel'] = $result['full'];
            $mail['name'] = $mail['subject'];
        }

        //发邮件给联盟运营的用户
        $users = MessageService::getPlatUserInfo([$agency->agencyid]);

        // 测试邮件介绍账号 todo
        $debug = false;
        $debug_mail = 'yijiawu@iwalnuts.com';
        if ($debug) {
            $users = [['email_address' => $debug_mail]];
        }

        if (count($users) > 0) {
            EmailHelper::sendEmail(
                'emails.command.dataHourTotal',
                $mail,
                array_column($users, 'email_address'),
                $agency->agencyid
            );
        }

        //发送邮件给所有的媒介经理
        $managers = MessageService::getMediaUserInfo([$agency->agencyid]);
        if (count($managers) > 0) {
            foreach ($managers as $manager) {
                $toAffManager = array();
                $affiliates = $this->getAffiliatesByManagerUid($manager['user_id']);
                if (count($affiliates) > 0) {
                    foreach ($affiliates as $affiliate) {
                        $tmp = $this->getAffiliateData(
                            $time,
                            $yesterdayTime,
                            $affiliate->affiliateid,
                            $agency->agencyid
                        );
                        if (!$tmp['isNull']) {
                            $toAffManager[] = $tmp;
                        }
                    }
                }
                if (count($toAffManager) > 0) {
                    $H = date('Y年m月d日H点', strtotime($time));
                    $mail = array();
                    $mail['subject'] = "截至{$H}，ADN媒体商数据";
                    $mail['msg']['data'] = $toAffManager;
                    $mail['msg']['H'] = $H;
                    //创建相应媒体商的数据
                    $name = date('YmdH', strtotime($time)) . $manager['user_id'] . 'trafficker';
                    $result = $this->createExcel($name, $toAffManager);
                    if (!empty($result)) {
                        $mail['attach']['excel'] = $result['full'];
                        $mail['name'] = $mail['subject'];
                    }
                    // 测试邮件介绍账号 todo
                    if ($debug) {
                        $manager['email_address'] = [['email_address' => $debug_mail]];
                    }

                    EmailHelper::sendEmail(
                        'emails.command.dataHourTotal',
                        $mail,
                        $manager['email_address'],
                        $agency->agencyid
                    );

                    // 测试终止
                    if ($debug) {
                        $this->notice("debug mail: " . $debug_mail);
                        break;
                    }
                }
            }
        }
    }

    private function getCompareResult($value1, $value2)
    {
        return ($value1 >= $value2) ? ($value1 > $value2 ? 'up' : 'equal') : 'down';
    }

    private function getRate($value1, $value2)
    {
        $rate = $value2 > 0 ? number_format(($value1 / $value2), 2) : 1;
        return number_format($rate * 100, 2) . "%";
    }

    /**
     * 根据媒体商id获取截止给定时间所在小时之前，
     * 当天零点之后的总展示量，下载量(上报),收入，支出
     */
    private function getADNSumData($affiliateId, $time, $agencyId)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($time));
        $end_time = date('Y-m-d H:00:00', strtotime($time));
        \DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = \DB::getTablePrefix();
        $sql = \DB::table('data_summary_ad_hourly as ds')
            ->join('banners as b', 'ds.ad_id', '=', 'b.bannerid')
            ->join('affiliates as af', 'b.affiliateid', '=', 'af.affiliateid')
            ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->join('clients as cl', 'cl.clientid', '=', 'c.clientid')
            ->select(
                \DB::raw("IFNULL(SUM({$prefix}ds.conversions),0) AS sum_conversions"),
                \DB::raw("IFNULL(SUM({$prefix}ds.clicks),0) AS sum_clicks"),
                \DB::raw("IFNULL(SUM({$prefix}ds.impressions),0) AS sum_impressions"),
                \DB::raw("TRUNCATE(IFNULL(SUM({$prefix}ds.total_revenue),0),2) AS sum_revenue"),
                \DB::raw("TRUNCATE(IFNULL(SUM({$prefix}ds.af_income),0),2) AS sum_payment")
            )
            ->whereRaw("{$prefix}ds.date_time >= DATE_FORMAT(DATE_SUB('{$start_time}',
                INTERVAL 8 HOUR),
                '%Y-%m-%d %H:%i:%s')")
            ->whereRaw("{$prefix}ds.date_time <= DATE_FORMAT(DATE_SUB('{$end_time}',
                INTERVAL 8 HOUR),
                '%Y-%m-%d %H:%i:%s')")
            ->where('af.mode', '!=', Affiliate::MODE_ARTIFICIAL_DELIVERY);

        // 限制查询联盟媒体的广告主
        $sql->where('cl.affiliateid', '=', '0');

        $sql->where('af.agencyid', $agencyId);
        if ($affiliateId != 0) {
            $sql->where('b.affiliateid', $affiliateId);
        }
        $sumData = $sql->first();
        return $sumData;
    }

    /**
     * 根据媒体商id获取其有效（投放中）的广告数量
     */
    private function getValidCampaigns($affiliateId, $date, $agencyId)
    {
        $prefix = \DB::getTablePrefix();
        $sql = Banner::join('data_summary_ad_hourly as ds', 'banners.bannerid', '=', 'ds.ad_id')
            ->join('affiliates AS af', function ($join) {
                $join->on('banners.affiliateid', '=', 'af.affiliateid');
            })
            ->join('campaigns as c', 'c.campaignid', '=', 'banners.campaignid')
            ->join('clients as cl', 'cl.clientid', '=', 'c.clientid')
            ->where(
                'ds.date_time',
                '>=',
                \DB::raw("DATE_FORMAT(DATE_SUB('{$date}', INTERVAL 8 HOUR), '%Y-%m-%d %H:%i:%s')")
            )
            ->where(
                'ds.date_time',
                '<',
                \DB::raw("DATE_FORMAT(DATE_ADD('{$date}', INTERVAL 16 HOUR), '%Y-%m-%d %H:%i:%s')")
            );

        // 只查询联盟媒体的广告主数据
        $sql->where('cl.affiliateid', '=', '0');

        $sql->where('af.agencyid', $agencyId);
        if ($affiliateId != 0) {
            $sql->where('banners.affiliateid', $affiliateId);
        }

        return $sql->distinct()->count('banners.campaignid');
    }

    /**
     * 根据媒介经理的user_id获取其所有的媒体商
     */
    private function getAffiliatesByManagerUid($uid)
    {
        $affiliates = Affiliate::select('affiliateid')->where('creator_uid', $uid)->get();
        return $affiliates;
    }

    /**
     * 根据媒体商id和时间获取对应媒体商的数据
     */
    private function getAffiliateData($time, $yesterdayTime, $affiliateId, $agencyId)
    {
        $modeName = '';
        $affiliate = Affiliate::where('affiliateid', $affiliateId)->first();
        switch ($affiliate->mode) {
            case Affiliate::MODE_PROGRAM_DELIVERY_STORAGE:
                $modeName = '程序化投放-媒体入库';
                break;
            case Affiliate::MODE_ARTIFICIAL_DELIVERY:
                $modeName = '人工投放';
                break;
            case Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE:
                $modeName = '程序化投放-媒体不入库';
                break;
            case Affiliate::MODE_ADX:
                $modeName = 'Adx';
                break;
        }
        $appPlatform = $affiliate->app_platform;
        $platformNames = Campaign::getPlatformLabels($appPlatform);

        $tmp = $this->getFormatData($time, $yesterdayTime, $affiliateId, $agencyId);
        $tmp['media'] = $affiliate->brief_name;
        $tmp['platform'] = $platformNames;
        $tmp['deliverytype'] = $modeName;

        return $tmp;
    }

    private function getChange($todayData, $lastDayData)
    {
        $todayData = $todayData ? $todayData : 0;
        $lastDayData = $lastDayData ? $lastDayData : 0;
        $change = abs($todayData - $lastDayData);
        $flag = $this->getCompareResult($todayData, $lastDayData);
        $rate = $this->getRate($change, $lastDayData);

        $isNull = true;

        if ($todayData > 0 || $change > 0) {
            $isNull = false;
        }

        return [
            'data' => $todayData,
            'change' => $change,
            'flag' => $flag,
            'rate' => $rate,
            'isNull' => $isNull
        ];
    }

    private function getFormatData($time, $yesterdayTime, $affiliateId = 0, $agencyId = 0)
    {
        // 截止当前小时之前，当天零时之后所有媒体商的展示量，下载量，收入，支出等
        $sumData = $this->getADNSumData($affiliateId, $time, $agencyId);
        // 对应昨天所有媒体商的数据
        $lastDayData = $this->getADNSumData($affiliateId, $yesterdayTime, $agencyId);

        $validCampaigns = $this->getValidCampaigns($affiliateId, date('Y-m-d', strtotime($time)), $agencyId);

        $impressionChange = $this->getChange($sumData['sum_impressions'], $lastDayData['sum_impressions']);
        $clickChange = $this->getChange($sumData['sum_clicks'], $lastDayData['sum_clicks']);
        $conversionChange = $this->getChange($sumData['sum_conversions'], $lastDayData['sum_conversions']);
        $revenueChange = $this->getChange($sumData['sum_revenue'], $lastDayData['sum_revenue']);
        $paymentChange = $this->getChange($sumData['sum_payment'], $lastDayData['sum_payment']);

        $isNull = false;
        if ($impressionChange['isNull']
            && $clickChange['isNull']
            && $conversionChange['isNull']
            && $revenueChange['isNull']
            && $paymentChange['isNull']
        ) {
            $isNull = true;
        }

        return array(
            'media' => '程序化媒体总量',
            'platform' => '--',
            'deliverytype' => '--',
            'validcampaigns' => $validCampaigns,
            'impressions' => $impressionChange,
            'clicks' => $clickChange,
            'conversions' => $conversionChange,
            'revenue' => $revenueChange,
            'payment' => $paymentChange,
            'isNull' => $isNull
        );
    }

    /**
     * 生成 Excel表
     */
    private function createExcel($name, $data)
    {
        $result = Excel::create($name, function ($excel) use ($data) {
            $excel->sheet('Sheet1', function ($sheet) use ($data) {
                $sheet->loadView('emails.command.dataHourTotalExcel', ['data' => $data]);
            });
        })->store('xlsx', storage_path('excel'), true);

        return $result;
    }
}
