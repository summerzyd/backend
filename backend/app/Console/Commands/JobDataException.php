<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Components\Config;
use App\Components\Helper\EmailHelper;
use App\Services\MessageService;
use App\Models\Affiliate;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Agency;

class JobDataException extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_data_exception {--datetime=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for sending data exception e-mail.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        // 统一的振幅配置值
        $dataException = Config::get('biddingos.dataException');
        $this->remindRate = $dataException['uniformRate'];
        $this->warningRate = $dataException['warningRate'];
        $this->conversionLimit = $dataException['conversionLimit'];
        $this->compareRate = $dataException['compareRate'];
    }

    private $remindRate = 0.1;
    private $warningRate = 0.3;
    private $conversionLimit = 200;
    private $compareRate = 0.2;

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
        // AND的当前时间前一个小时内总展示量，下载量(上报)
        $time = $this->option('datetime') ? $this->option('datetime') : date('Y-m-d H:i:s');
        // $time = '2015-09-25 07:30:20';
        $h = date('H', strtotime('-1 hour', strtotime($time)));
        $yesterdayTime = date('Y-m-d H:i:s', strtotime('-1 day', strtotime($time)));

        //$this->checkPlatData($time, $yesterdayTime, $h);
        $this->checkCampaignData($time, $yesterdayTime, $h, $agency->agencyid);
        $this->checkAffiliateData($time, $yesterdayTime, $h, $agency->agencyid);
    }

    /**
     * 汇总数据，发一封
     * @param datetime $time
     * @param datetime $yesterdayTime
     * @param int $h
     */
    private function checkPlatData($time, $yesterdayTime, $h)
    {
        $this->notice('Check platform data begin.');
        // 获取联盟运营名单
        $users = $this->mergeUsers(MessageService::getPlatUserInfo());
        // 发送邮件给联盟运营、总经理、CEO、COO
        $usersWarning = $this->mergeUsers(MessageService::getLeaderUserInfo());

        $preHourData = $this->getADNSumData($time);
        $lastDayHourData = $this->getADNSumData($yesterdayTime);
        $mail = [];
        $mail['msg']['Hour'] = $h;
        $mail['subject'] = '程序化媒体';
        $mail['msg']['view'] = 'emails.command.sumConversionsException';
        $this->compareLogic($mail, $preHourData, $lastDayHourData, $users, $usersWarning);
    }

    private function checkCampaignData($time, $yesterdayTime, $h, $agencyId)
    {
        $this->notice('Check Campaigns data begin.');
        foreach ([$yesterdayTime, $time] as $k => $v) {
            //获取指定时间的投放数据
            $data[$v] = $this->getCampaignDataByTime($v, $agencyId);
        }
        $todayData = $data[$time]['data'];
        $yesterdayData = $data[$yesterdayTime]['data'];
        //处理今天跟昨天的数据比较
        $rows = $this->compareData($todayData, $yesterdayData);
        $yesterdayIds = $data[$yesterdayTime]['dataCampaignIds'];
        $todayIds = $data[$time]['dataCampaignIds'];

        //找出昨天有，今天没有的 campaignid，然后附加上
        $diffIds = array_diff($yesterdayIds, $todayIds);
        if (!empty($diffIds)) {
            $rows = $rows + $this->getLatestData($diffIds, $yesterdayData);
        }

        //查找到有广告主管理-all权限的人员
        $permissionAll = MessageService::getUserListByPermission('manager-super-account-all', $agencyId);
        $mail = [];
        if (!empty($permissionAll)) {
            $mail['msg']['Hour'] = $h;
            $mail['msg']['date'] = date('Y年m月d日', strtotime($time)) . "{$h}点";
            $mail['subject'] = "截至" . $mail['msg']['date'] . "，程序化投放数据";
            $mail['msg']['view'] = 'emails.command.campaignConversionsException';
            $mail['msg']['data'] = $rows;
            foreach ($permissionAll as $akey => $aval) {
                EmailHelper::sendEmail($mail['msg']['view'], $mail, $aval['email_address']);
            }
        }

        //查找到有广告主管理-self权限的人员，去重
        $permissionSelf = MessageService::getUserListByPermission('manager-super-account-self', $agencyId);
        if (!empty($permissionAll) && !empty($permissionSelf)) {
            $allAddress = [];
            foreach ($permissionAll as $ak => $av) {
                $allAddress[] = $av['email_address'];
            }

            foreach ($permissionSelf as $sk => $sv) {
                if (in_array($sv['email_address'], $allAddress)) {
                    unset($permissionSelf[$sk]);
                }
            }
        }

        //把自己负责的广告主，账号下的广告计划选择出来
        if (!empty($permissionSelf)) {
            $mail['msg']['Hour'] = $h;
            $mail['msg']['date'] = date('Y年m月d日', strtotime($time)) . "{$h}点";
            $mail['subject'] = "截至" . $mail['msg']['date'] . "，程序化投放数据";
            $mail['msg']['view'] = 'emails.command.campaignConversionsException';
            //循环给相应的运营-self发送邮件
            foreach ($permissionSelf as $skey => $sVal) {
                $newSelfData = [];
                $newCampaignIds = $this->getSelfCampaignIds($sVal['user_id']);
                //把属于的 Campaignid对应的广告列出来，发送邮件给相应的运营
                if (!empty($newCampaignIds)) {
                    foreach ($newCampaignIds as $ck => $cv) {
                        if (isset($rows[$cv['campaignid']])) {
                            $newSelfData[$cv['campaignid']] = $rows[$cv['campaignid']];
                        }
                    }
                }
                if (!empty($newSelfData)) {
                    $mail['msg']['data'] = $newSelfData;
                    EmailHelper::sendEmail($mail['msg']['view'], $mail, $sVal['email_address']);
                }
            }
        }

        $this->notice('Check Campaigns data End.');
    }

    /**
     *
     * @param datetime $time
     * @param datetime $yesterdayTime
     * @param int $h
     */
    private function checkAffiliateData($time, $yesterdayTime, $h, $agencyId)
    {
        $this->notice('Check affiliate data begin.');
        // ADN某媒体商的展示量等数据大幅异常提醒
        $affiliates = $this->getAffiliateList($agencyId);
        $mail = [];
        $mail['msg']['Hour'] = $h;
        $mail['msg']['date'] = date('Y-m-d', strtotime($time));
        $mail['msg']['view'] = 'emails.command.affiliateConversionsException';
        //保存所有邮件内容
        $data = [];
        foreach ($affiliates as $affId => $v) {
            $ad_ids = $v['ad_ids'];
            $preHourData = $this->getADNSumData($time, $ad_ids);
            $lastDayHourData = $this->getADNSumData($yesterdayTime, $ad_ids);
            // 发送邮件给对应的媒介经理
            $users = $this->mergeUsers(MessageService::getAffiliateManagerUsersInfo($affId));
            $usersWarning = $users;
            $mail['subject'] = $v['name'];
            $mail['msg']['affiliate_name'] = $v['name'];
            if ($this->compareLogic($mail, $preHourData, $lastDayHourData, $users, $usersWarning)) {
                //邮件发送成功后添加信息
                $data[] = $mail['msg'];
            }
        }
        //邮件合并发送给运营
        $users = $this->mergeUsers(MessageService::getPlatUserInfo([$agencyId]));
        $data = $this->createAffiliateMailData($affiliates, $time, $yesterdayTime);
        if (count($data) > 0) {
            $mail = [];
            $mail['subject'] = '媒体商下载量数据异常汇总';
            $mail['msg']['Hour'] = $h;
            $mail['msg']['date'] = date('Y-m-d', strtotime($time));
            $mail['msg']['data'] = $data;
            EmailHelper::sendEmail('emails.command.affiliateTotalException', $mail, $users);
        }
    }

    private function compareLogic(&$mail, $preHourData, $lastDayHourData, $users, $usersWarning)
    {

        $todayHourConversions = count($preHourData) == 0 ? 0 : $preHourData['sum_conversions'];
        $lastDayHourConversions = count($lastDayHourData) == 0 ? 0 : $lastDayHourData['sum_conversions'];

        $changeConversions = abs($todayHourConversions - $lastDayHourConversions);

        if ($todayHourConversions >= $lastDayHourConversions) {
            $conversion_action = '增长';
        } else {
            $conversion_action = '下降';
        }
        // 振幅大于10%(可以设定)发送邮件提醒
        if ($lastDayHourConversions > 0) {
            $changeConversionsRate = number_format($changeConversions / $lastDayHourConversions, 2);
        } else {
            $changeConversionsRate = 1;
        }
        $mail['msg']['todayHourConversions'] = $todayHourConversions;
        $mail['msg']['lastDayHourConversions'] = $lastDayHourConversions;
        $mail['msg']['action'] = $conversion_action;
        $mail['msg']['rate'] = number_format($changeConversionsRate * 100, 2);
        $mail['subject'] = $mail['subject'] . "下载量同比昨日{$conversion_action}" . $mail['msg']['rate'] . "%";
        //昨天的数据或今天的数据大于阈值
        if ($todayHourConversions >= $this->conversionLimit || $lastDayHourConversions >= $this->conversionLimit) {
            $this->notice('Yesterday data: ' . $lastDayHourConversions);
            $this->notice('Today data: ' . $todayHourConversions);
            //变化幅度大于配置值
            if ($changeConversionsRate >= $this->remindRate) {
                //变化幅度大于警告值
                if ($changeConversionsRate >= $this->warningRate) {
                    $mail['subject'] = "警告:" . $mail['subject'];
                    $users = $usersWarning;
                }
                $this->notice('Send mail...');
                EmailHelper::sendEmail($mail['msg']['view'], $mail, $users);
                return true;
            }
        }
        return false;
    }

    /**
     * 获取AND的给定时间前一个小时内总展示量，下载量(上报)
     */
    private function getADNSumData($time, $adIds = [])
    {
        $prefix = DB::getTablePrefix();
        $sql = DB::table('data_summary_ad_hourly as ds')
            ->leftJoin('banners as b', 'ds.ad_id', '=', 'b.bannerid')
            ->leftJoin('affiliates as af', 'b.affiliateid', '=', 'af.affiliateid')
            ->leftJoin('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('clients as cl', 'cl.clientid', '=', 'c.clientid')
            ->where('cl.affiliateid', '=', '0')
            ->where(
                'ds.date_time',
                '>=',
                DB::raw("DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 9 HOUR),'%Y-%m-%d %H:00:00')")
            )
            ->where(
                'ds.date_time',
                '<',
                DB::raw("DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 8 HOUR),'%Y-%m-%d %H:00:00')")
            )
            ->where('af.mode', '!=', Affiliate::MODE_ARTIFICIAL_DELIVERY)
            ->groupBy(DB::raw("DATE_FORMAT({$prefix}ds.date_time, '%Y-%m-%d %H:00:00')"))
            ->select(
                DB::raw("IFNULL(SUM({$prefix}ds.conversions),0) AS sum_conversions"),
                DB::raw("IFNULL(SUM({$prefix}ds.file_down),0) AS sum_filedown")
            );
        if (count($adIds) > 0) {
            $sql->whereIn('ds.ad_id', $adIds);
        }
        $sumData = $sql->first();
        return $sumData;
    }

    private function getCampaignList()
    {
        $rows = DB::table('campaigns as c')
            ->select('b.campaignid', 'app.app_name', 'b.bannerid')
            ->join('banners as b', 'b.campaignid', '=', 'c.campaignid')
            ->join('ad_zone_assoc as aza', 'b.bannerid', '=', 'aza.ad_id')
            ->leftJoin('appinfos as app', 'c.campaignname', '=', 'app.app_id')
            ->groupBy('b.campaignid', 'b.bannerid')
            ->orderBy('b.campaignid')
            ->get();
        $cList = [];
        foreach ($rows as $row) {
            $cList[$row['campaignid']]['app_name'] = $row['app_name'];
            $cList[$row['campaignid']]['ad_ids'][] = $row['bannerid'];
        }
        return $cList;
    }

    private function getAffiliateList($agencyId)
    {
        $rows = DB::table('affiliates as aff')
            ->select('aff.affiliateid', 'aff.name', 'b.bannerid')
            ->join('banners as b', 'b.affiliateid', '=', 'aff.affiliateid')
            ->join('ad_zone_assoc as aza', 'b.bannerid', '=', 'aza.ad_id')
            ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->join('clients as cl', 'cl.clientid', '=', 'c.clientid')
            ->where('cl.affiliateid', '=', 0)
            ->where('aff.agencyid', $agencyId)
            ->groupBy('aff.affiliateid', 'b.bannerid')
            ->orderBy('aff.affiliateid')
            ->get();
        $aList = [];
        $rows = json_decode(json_encode($rows), true);
        foreach ($rows as $row) {
            $aList[$row['affiliateid']]['name'] = $row['name'];
            $aList[$row['affiliateid']]['ad_ids'][] = $row['bannerid'];
        }
        return $aList;
    }

    /**
     * 合并重复的用户清单
     */
    private function mergeUsers($lst, $lst1 = [])
    {
        $ret = array_merge($lst, $lst1);
        $key = [];
        foreach ($ret as &$u) {
            if (isset($key[$u['email_address']])) {
                unset($u);
            } else {
                $key[$u['email_address']] = true;
            }
        }
        return array_keys($key);
    }

    /**
     * 根据指定时间获取相应的广告列表
     * @param datetime $time
     * @return array
     */
    private function getCampaigns($time, $agencyId)
    {
        $prefix = DB::getTablePrefix();
        $sql = "SELECT DISTINCT(b.campaignid), a.app_name, c.platform, c.revenue_type FROM {$prefix}banners AS b
                INNER JOIN (SELECT DISTINCT(ad_id) FROM {$prefix}data_summary_ad_hourly WHERE 1
                AND date_time >= DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 9 HOUR),'%Y-%m-%d %H:00:00')
                AND date_time < DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 8 HOUR),'%Y-%m-%d %H:00:00')
                ) AS h
                ON b.bannerid = h.ad_id
                LEFT JOIN {$prefix}campaigns AS c ON c.campaignid = b.campaignid
                LEFT JOIN {$prefix}appinfos AS a ON c.campaignname = a.app_id AND c.platform = a.platform
                LEFT JOIN {$prefix}clients AS cl ON cl.clientid = c.clientid
                WHERE 1 AND cl.affiliateid = 0
                AND cl.agencyid = {$agencyId}
                ";
        $row = DB::select($sql);
        return json_decode(json_encode($row), true);
    }

    /**
     * 获取相应的统计汇总数据
     * @param unknown $time
     * @param unknown $campaignIds
     * @return unknown
     */
    private function getSumData($time, $campaignIds)
    {
        $prefix = DB::getTablePrefix();
        $campaignId = implode(",", $campaignIds);
        $sql = "SELECT
                    b.campaignid,
                    SUM(h.impressions) AS impressions,
                    SUM(h.clicks) AS clicks,
                    SUM(h.conversions) AS conversions,
                    SUM(h.total_revenue) AS total_revenue,
                    SUM(af_income) AS af_income
                FROM
                {$prefix}data_summary_ad_hourly AS h
                INNER JOIN (
                    SELECT
                        {$prefix}banners.bannerid,
                        {$prefix}affiliates.affiliateid,
                        {$prefix}banners.campaignid
                    FROM
                        up_banners
                    INNER JOIN {$prefix}affiliates ON {$prefix}banners.affiliateid = {$prefix}affiliates.affiliateid
                    WHERE 1
                    AND {$prefix}affiliates.`mode` != '" . Affiliate::MODE_ARTIFICIAL_DELIVERY . "'
                    AND campaignid IN({$campaignId})
                ) AS b ON h.ad_id = b.bannerid
                LEFT JOIN {$prefix}campaigns AS c ON c.campaignid = b.campaignid
                LEFT JOIN {$prefix}clients AS cl ON cl.clientid = c.clientid
                WHERE 1
                AND h.date_time >= DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 9 HOUR),'%Y-%m-%d %H:00:00')
                AND h.date_time < DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 8 HOUR),'%Y-%m-%d %H:00:00')
                AND cl.affiliateid = 0
                GROUP BY b.campaignid
                ORDER BY total_revenue DESC
                ";
        $row = DB::select($sql);
        return json_decode(json_encode($row), true);
    }

    /**
     * 获取在投媒体的数量
     * @param integer $campaignId
     * @return integer $row['total']
     */
    private function getAffiliates($time, $campaignIds)
    {
        $prefix = DB::getTablePrefix();
        $campaignId = implode(",", $campaignIds);
        $mode = Affiliate::MODE_ARTIFICIAL_DELIVERY;

        $sql = "SELECT b.campaignid, COUNT(b.affiliateid) AS affiliate FROM {$prefix}banners AS b
                LEFT JOIN {$prefix}affiliates AS af ON b.affiliateid = af.affiliateid
                INNER JOIN (
                    SELECT DISTINCT(ad_id) FROM {$prefix}data_summary_ad_hourly WHERE 1
                    AND impressions > 0
                    AND date_time >= DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 9 HOUR),'%Y-%m-%d %H:00:00')
                    AND date_time < DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 8 HOUR),'%Y-%m-%d %H:00:00')
                ) AS h ON b.bannerid = h.ad_id
                LEFT JOIN {$prefix}campaigns AS c on c.campaignid = b.campaignid
                LEFT JOIN {$prefix}clients AS cl on cl.clientid = c.clientid
                WHERE 1 AND b.campaignid IN({$campaignId})
                AND cl.affiliateid = 0
                AND af.mode != '{$mode}'
                GROUP BY campaignid
                ";
        $row = DB::select($sql);
        return json_decode(json_encode($row), true);
    }

    private function getChange($todayData, $lastDayData, $bool = false)
    {
        $todayData = $todayData ? $todayData : 0;
        $lastDayData = $lastDayData ? $lastDayData : 0;
        $change = (false == $bool) ? abs($todayData - $lastDayData) : ($todayData - $lastDayData);
        $flag = $this->getCompareResult($todayData, $lastDayData);
        $rate = $this->getRate($change, $lastDayData);
        return [
            'data' => (false == $bool) ? $todayData : $lastDayData,
            'change' => $change,
            'flag' => $flag,
            'rate' => $rate
        ];
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

    private function getSelfCampaignIds($uid)
    {
        $row = DB::table("campaigns AS c")
            ->leftJoin("clients AS uc", "c.clientid", "=", "uc.clientid")
            ->where('uc.creator_uid', $uid)
            ->where('uc.affiliateid', 0)
            ->select('c.campaignid')
            ->get();

        return json_decode(json_encode($row), true);
    }

    /**
     * 获取指定日期的广告汇总数据
     * @param datetime $v
     */
    private function getCampaignDataByTime($v, $agencyId)
    {
        //取得当前指定投放时间的广告ID列表
        $data = [];
        $dataCampaignIds = [];
        $campaigns = $this->getCampaigns($v, $agencyId);
        if (!empty($campaigns)) {
            $cVal = [];
            foreach ($campaigns as $key => $val) {
                $cVal[$val['campaignid']]['campaignid'] = $val['campaignid'];
                $cVal[$val['campaignid']]['app_name'] = $val['app_name'];
                $cVal[$val['campaignid']]['revenue_type'] = Campaign::getRevenueTypeLabels($val['revenue_type']);
                $cVal[$val['campaignid']]['platform'] = Campaign::getPlatformLabels($val['platform']);
            }

            $campaignIds = array_keys($cVal);
            $dataCampaignIds = array_keys($cVal);
            $rowData = $this->getSumData($v, $campaignIds);
            $hVal = [];
            if (!empty($rowData)) {
                foreach ($rowData as $ke => $va) {
                    $hVal[$va['campaignid']] = $va;
                }
            }

            //获取在投放的媒体数
            $mData = $this->getAffiliates($v, $campaignIds);
            $mVal = [];
            if (!empty($mData)) {
                foreach ($mData as $me => $mv) {
                    $mVal[$mv['campaignid']] = $mv;
                }
            }

            //用汇总的数据还循环
            if (!empty($hVal)) {
                foreach ($hVal as $cid => $cv) {
                    $tmp = array_merge($cv, $cVal[$cid]);
                    if (isset($mVal[$cid])) {
                        $tmp = array_merge($tmp, $mVal[$cid]);
                    } else {
                        $tmp = array_merge($tmp, ['campaignid' => $cid, 'affiliate' => 0]);
                    }

                    $data[$cid] = $tmp;
                }
            }
        }
        return ['data' => $data, 'dataCampaignIds' => $dataCampaignIds];
    }


    private function compareData($todayData, $yesterdayData)
    {
        //今天跟昨天的进行比较
        $rows = [];
        if (count($todayData) > 0) {
            foreach ($todayData as $campaignId => $va) {
                $rows[$campaignId]['campaignid'] = $va['campaignid'];
                $rows[$campaignId]['app_name'] = $va['app_name'];
                $rows[$campaignId]['platform'] = $va['platform'];
                $rows[$campaignId]['revenue_type'] = $va['revenue_type'];
                $lastAffiliate = isset($yesterdayData[$campaignId]['affiliate']) ?
                    $yesterdayData[$campaignId]['affiliate'] : 0;
                $lastImpressions = isset($yesterdayData[$campaignId]['impressions']) ?
                    $yesterdayData[$campaignId]['impressions'] : 0;
                $lastClicks = isset($yesterdayData[$campaignId]['clicks']) ?
                    $yesterdayData[$campaignId]['clicks'] : 0;
                $lastConversions = isset($yesterdayData[$campaignId]['conversions']) ?
                    $yesterdayData[$campaignId]['conversions'] : 0;
                $lastTotalRevenue = isset($yesterdayData[$campaignId]['total_revenue']) ?
                    $yesterdayData[$campaignId]['total_revenue'] : 0;
                $lastIncome = isset($yesterdayData[$campaignId]['af_income']) ?
                    $yesterdayData[$campaignId]['af_income'] : 0;
                $rows[$campaignId]['affiliate'] = $this->getChange(
                    $va['affiliate'],
                    $lastAffiliate
                );
                $rows[$campaignId]['impressions'] = $this->getChange(
                    $va['impressions'],
                    $lastImpressions
                );
                $rows[$campaignId]['clicks'] = $this->getChange(
                    $va['clicks'],
                    $lastClicks
                );
                $rows[$campaignId]['conversions'] = $this->getChange(
                    $va['conversions'],
                    $lastConversions
                );
                $rows[$campaignId]['revenue'] = $this->getChange(
                    $va['total_revenue'],
                    $lastTotalRevenue
                );
                $rows[$campaignId]['payment'] = $this->getChange(
                    $va['af_income'],
                    $lastIncome
                );
                $rows[$campaignId]['income'] = $this->getChange(
                    ($va['total_revenue'] - $va['af_income']),
                    ($lastTotalRevenue - $lastIncome)
                );

                //如果展示量，下载量，收入，支出，毛利都为0
                if (0 == $va['impressions'] && 0 == $va['conversions'] && 0 == $va['total_revenue'] &&
                    0 == $va['af_income'] && 0 == ($va['total_revenue'] - $va['af_income'])
                ) {
                    //如果昨天有数据
                    if (isset($yesterdayData[$campaignId])) {
                        $newYesterdayData = $yesterdayData[$campaignId];
                        if (0 == $newYesterdayData['impressions'] && 0 == $newYesterdayData['conversions'] &&
                            0 == $newYesterdayData['total_revenue'] && 0 == $newYesterdayData['af_income'] &&
                            0 == ($newYesterdayData['total_revenue'] - $newYesterdayData['af_income'])
                        ) {
                            unset($rows[$campaignId]);
                        }
                    } else {
                        //昨天没数据，也要把此记录删除
                        unset($rows[$campaignId]);
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * 把昨天有，今天没有的数据处理并返回
     */
    private function getLatestData($diffIds, $yesterdayData, $bool = true)
    {
        foreach ($diffIds as $dk => $dv) {
            $yesterRow = $yesterdayData[$dv];
            $rows[$dv]['campaignid'] = $yesterRow['campaignid'];
            $rows[$dv]['app_name'] = $yesterRow['app_name'];
            $rows[$dv]['platform'] = $yesterRow['platform'];
            $rows[$dv]['revenue_type'] = $yesterRow['revenue_type'];
            $rows[$dv]['affiliate'] = $this->getChange(0, $yesterRow['affiliate'], $bool);
            $rows[$dv]['impressions'] = $this->getChange(0, $yesterRow['impressions'], $bool);
            $rows[$dv]['clicks'] = $this->getChange(0, $yesterRow['clicks'], $bool);
            $rows[$dv]['conversions'] = $this->getChange(0, $yesterRow['conversions'], $bool);
            $rows[$dv]['revenue'] = $this->getChange(0, $yesterRow['total_revenue'], $bool);
            $rows[$dv]['payment'] = $this->getChange(0, $yesterRow['af_income'], $bool);
            $income = ($yesterRow['total_revenue'] - $yesterRow['af_income']) > 0 ?
                ($yesterRow['total_revenue'] - $yesterRow['af_income']) :
                ($yesterRow['af_income'] - $yesterRow['total_revenue']);
            $rows[$dv]['income'] = $this->getChange(0, $income, $bool);
        }
        return $rows;
    }

    /**
     * 生成发送给运营的媒体商下载量异常邮件数据
     * @param array $affiliates
     * @param datetime $time
     * @param datetime $yesterdayTime
     * @return array
     */
    protected function createAffiliateMailData($affiliates, $time, $yesterdayTime)
    {
        $data = [];
        foreach ($affiliates as $affId => $affiliate) {
            $affiliateData = [];
            $ad_ids = $affiliate['ad_ids'];
            $affiliateData['affiliate_name'] = $affiliate['name'];
            //上一个小时的数据
            $lastTodayHourData = $this->getADNSumData($time, $ad_ids);
            //昨天同一小时的数据
            $lastDayHourData = $this->getADNSumData($yesterdayTime, $ad_ids);
            //比较昨天和今天同一小时的下载量变化情况
            $lastTodayHourConversions = count($lastTodayHourData) == 0 ? 0 : $lastTodayHourData['sum_conversions'];
            $lastDayHourConversions = count($lastDayHourData) == 0 ? 0 : $lastDayHourData['sum_conversions'];

            $changeConversions = abs($lastTodayHourConversions - $lastDayHourConversions);

            if ($lastTodayHourConversions >= $lastDayHourConversions) {
                $conversion_action = '增长';
            } else {
                $conversion_action = '下降';
            }
            //计算振幅
            if ($lastDayHourConversions > 0) {
                $changeConversionsRate = number_format($changeConversions / $lastDayHourConversions, 2);
            } else {
                $changeConversionsRate = 1;
            }
            $affiliateData['todayHourConversions'] = $lastTodayHourConversions;
            $affiliateData['lastDayHourConversions'] = $lastDayHourConversions;
            $affiliateData['action'] = $conversion_action;
            $affiliateData['rate'] = number_format($changeConversionsRate * 100, 2);

            //比较前一小时下载量和下载完成的对比情况
            $lastTodayHourFiledown = count($lastTodayHourData) == 0 ? 0 : $lastTodayHourData['sum_filedown'];
            $compare = abs($lastTodayHourConversions - $lastTodayHourFiledown);
            if ($lastTodayHourFiledown > $lastTodayHourConversions) {
                $compare_action = 'more';
            } else {
                $compare_action = 'less';
            }
            if ($lastTodayHourConversions > 0) {
                $compareRate = number_format($compare / $lastTodayHourConversions, 2);
            } else {
                $compareRate = 1;
            }
            $affiliateData['todayHourFiledown'] = $lastTodayHourFiledown;
            $affiliateData['compare_action'] = $compare_action;
            $affiliateData['compare_rate'] = number_format($compareRate * 100, 2);

            //对比0点到当前时间的下载量情况
            $todaySumData = $this->getAffiliateDaySumData($time, $ad_ids);
            $yesterdaySumData = $this->getAffiliateDaySumData($yesterdayTime, $ad_ids);
            $todayConversions = count($todaySumData) == 0 ? 0 : $todaySumData['sum_conversions'];
            $yesterdayConversions = count($yesterdaySumData) == 0 ? 0 : $yesterdaySumData['sum_conversions'];
            $changeSum = abs($todayConversions - $yesterdayConversions);
            if ($todayConversions > $yesterdayConversions) {
                $sum_action = '增长';
            } else {
                $sum_action = '下降';
            }
            if ($yesterdayConversions > 0) {
                $sum_Rate = number_format($changeSum / $yesterdayConversions, 2);
            } else {
                $sum_Rate = 1;
            }
            $affiliateData['todaySumConversions'] = $todayConversions;
            $affiliateData['yesterdaySumConversions'] = $yesterdayConversions;
            $affiliateData['sum_action'] = $sum_action;
            $affiliateData['sum_rate'] = number_format($sum_Rate * 100, 2);

            $affiliateData['conversion_flag'] = 0;
            $affiliateData['compare_flag'] = 0;
            //昨天的数据或今天的数据大于阈值
            if ($lastTodayHourConversions >= $this->conversionLimit
                || $lastDayHourConversions >= $this->conversionLimit
                || $lastTodayHourFiledown >= $this->conversionLimit
            ) {
                //变化幅度大于配置值
                if ($changeConversionsRate >= $this->remindRate) {
                    //变化幅度大于警告值
                    if ($changeConversionsRate >= $this->warningRate) {
                        $affiliateData['conversion_flag'] = 1;
                    }
                    $affiliateData['conversion_flag'] = 1;
                }
                if (($compareRate >= $this->compareRate)
                    && ($lastTodayHourFiledown >= $this->conversionLimit
                        || $lastTodayHourConversions >= $this->conversionLimit)
                ) {
                    $affiliateData['compare_flag'] = 1;
                }
                $data[] = $affiliateData;
            }
        }
        return $data;
    }

    //获取媒体商从0点到当前时间的总数据
    protected function getAffiliateDaySumData($time, $ad_ids)
    {
        $prefix = DB::getTablePrefix();
        $sql = DB::table('data_summary_ad_hourly as ds')
            ->leftJoin('banners as b', 'ds.ad_id', '=', 'b.bannerid')
            ->leftJoin('affiliates as af', 'b.affiliateid', '=', 'af.affiliateid')
            ->leftJoin('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('clients as cl', 'cl.clientid', '=', 'c.clientid')
            ->where('cl.affiliateid', '=', '0')
            ->where(
                'ds.date_time',
                '>=',
                DB::raw("DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 8 HOUR), '%Y-%m-%d 00:00:00')")
            )
            ->where(
                'ds.date_time',
                '<=',
                DB::raw("DATE_FORMAT(DATE_SUB('{$time}', INTERVAL 8 HOUR), '%Y-%m-%d %H:%i:%s')")
            )
            ->where('af.mode', '!=', Affiliate::MODE_ARTIFICIAL_DELIVERY)
            ->groupBy(DB::raw("DATE_FORMAT({$prefix}ds.date_time, '%Y-%m-%d')"))
            ->select(DB::raw("IFNULL(SUM({$prefix}ds.conversions),0) AS sum_conversions"));
        if (count($ad_ids) > 0) {
            $sql->whereIn('ds.ad_id', $ad_ids);
        }
        $sumData = $sql->first();
        return $sumData;
    }
}
