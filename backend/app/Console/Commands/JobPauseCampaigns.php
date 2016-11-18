<?php

namespace App\Console\Commands;

use App\Components\Helper\EmailHelper;
use App\Components\Helper\LogHelper;
use App\Models\Campaign;
use App\Models\OperationLog;
use App\Services\CampaignService;
use App\Services\MessageService;
use Illuminate\Support\Facades\DB;

class JobPauseCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_pause_campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '余额不足和每日限额，暂停投放';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //查询需要暂定的广告列表
        $pauseCampaigns = $this->getPauseCampaignList();
        $approve_comment = [
            Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT => '1:达到日限额',
            Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH => '2:因余额不足暂停',
            Campaign::PAUSE_STATUS_EXCEED_TOTAL_LIMIT => '5:达到总限额暂停',
            Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM=>'4:达到程序化日预算暂停',
        ];
        $approve_code = [
            Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH => 6001, //余额不足
            Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT => 6006, //达到日预算
            Campaign::PAUSE_STATUS_EXCEED_TOTAL_LIMIT => 6004, //达到总预算
            Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM => 6049,//达到程序化日预算
        ];

        foreach ($pauseCampaigns as $_pauseType => $_pauseCampaigns) {
            if (count($_pauseCampaigns)) {
                foreach ($_pauseCampaigns as $_campaign) {
                    $args = [];
                    LogHelper::notice('=========pause by ' . $approve_comment[$_pauseType] .
                        ',campaigns(' . $_campaign['campaignname'] . ')');
                    CampaignService::modifyStatus(
                        $_campaign['campaignid'],
                        Campaign::STATUS_SUSPENDED,
                        [
                            'approve_comment' => $approve_comment[$_pauseType],
                            'pause_status' => $_pauseType
                        ],
                        false
                    );
                    //写入日志
                    $code = $approve_code[$_pauseType];
                    switch ($_pauseType) {
                        case Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH:
                            $args[] = $_campaign['clientname'];
                            break;
                        case Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM:
                            $args[] = sprintf("%.2f", $_campaign['day_limit_program']);
                            break;
                        case Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT:
                            $args[] = sprintf("%.2f", $_campaign['day_limit']);
                            break;
                        case Campaign::PAUSE_STATUS_EXCEED_TOTAL_LIMIT:
                            $args[] = sprintf("%.2f", $_campaign['total_limit']);
                            break;
                    }
                    $message = CampaignService::formatWaring($code, $args);
                    OperationLog::store([
                        'category' => OperationLog::CATEGORY_CAMPAIGN,
                        'target_id' => $_campaign['campaignid'],
                        'type' => OperationLog::TYPE_SYSTEM,
                        'message' => $message,
                    ]);
                }
                $this->sendMail(array_column($_pauseCampaigns, 'campaignid'), $_pauseType);
            }
        }
    }

    /**
     * @todo 查询需要暂停的campaign
     * @return array(1=>array(日限额)，2=>array(余额不足))
     * @see \BiddingOS\Service\Campaign\CampaignBosInterface::getAdList()
     */
    private function getPauseCampaignList()
    {
        $revenueTypes = array_keys(Campaign::getRevenueTypeLabels());
        $prefix = DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $campaigns = DB::table('campaigns AS c')
            ->join('clients AS cl', 'c.clientid', '=', 'cl.clientid')
            ->join('balances AS b', 'b.account_id', '=', 'cl.account_id')
            ->select(
                'c.campaignid',
                'c.campaignname',
                'c.pause_status',
                'c.clientid',
                'c.day_limit',
                'c.day_limit_program',
                'c.total_limit',
                'c.revenue_type',
                'cl.clientname',
                DB::raw("({$prefix}b.balance+{$prefix}b.gift) as balance_left")
            )
            ->whereIn('c.revenue_type', $revenueTypes)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->get();
        //查询campaign的总消费，用于总预算检测
        $result = CampaignService::getTotalConsume();

        $campaigns_total_revenue = [];
        foreach ($result as $v) {
            $campaigns_total_revenue[$v['campaignid']] = $v['total_revenue'];
        }

        $pauseCampaigns = [];
        foreach ($campaigns as $_campaign) {
            $revenueToday = CampaignService::getDailyConsume(
                $_campaign['campaignid'],
                $_campaign['revenue_type']
            );

            //单个广告计划的总消费
            $campaignsTotalRevenue = 0;
            if (isset($campaigns_total_revenue[$_campaign['campaignid']])) {
                $campaignsTotalRevenue = $campaigns_total_revenue[$_campaign['campaignid']];
            }

            //超过总预算暂停
            if ($campaignsTotalRevenue >= $_campaign['total_limit'] && $_campaign['total_limit'] > 0) {
                $pauseCampaigns[Campaign::PAUSE_STATUS_EXCEED_TOTAL_LIMIT][] = $_campaign;
            } elseif ($_campaign['balance_left'] <= 0) {
                //余额不足暂停
                $pauseCampaigns[Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH][] = $_campaign;
            } elseif ($revenueToday >= $_campaign['day_limit'] && $_campaign['day_limit'] > 0) {
                //过到日限额暂停
                $pauseCampaigns[Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT][] = $_campaign;
            } elseif ($revenueToday >= $_campaign['day_limit_program'] && $_campaign['day_limit_program'] > 0) {
                $pauseCampaigns[Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM][] = $_campaign;
            }
        }
        return $pauseCampaigns;
    }

    /**
     * 发送邮件
     * @param $campaignIds
     * @param $pauseType
     */
    private function sendMail($campaignIds, $pauseType)
    {
        //获取要发送邮件广告信息
        $info4Mail = $this->getCampaignInfo4Mail($campaignIds);
        //获取所有需要发送邮件平台账号信息
        $userInfo = MessageService::getPlatUserInfo(array_column($info4Mail, 'agencyid'));
        //按照平台进行分组
        $list = [];
        foreach ($userInfo as $item) {
            $list[$item['agencyid']][] = $item['email_address'];
        }

        $title = [
            1 => '广告[ %s ]暂停了，因为达到[ %s ]设置的单日限额',
            2 => '[ %s ]暂停了，因为[ %s ]余额不足',
            5 => '广告[ %s ]暂停了，因为达到[ %s ]设置的总预算',
        ];

        $content_info = [
            1 => '[ %s ]暂停了，因为达到[ %s ]设置的单日限额，请知晓。',
            2 => '[ %s ]暂停了，因为 [ %s ] 余额不足，请及时联系广告主充值。',
            5 => '[ %s ]暂停了，因为达到[ %s ]设置的总预算，请知晓。',
        ];

        $content_info_manage = [
            1 => '[ %s ]暂停了，因为达到[ %s ]设置的单日限额，请知晓。',
            2 => '[ %s ]暂停了，因为[ %s ]余额不足，请知晓。',
            5 => '[ %s ]暂停了，因为达到[ %s ]设置的总预算，请知晓。',
        ];

        foreach ($info4Mail as $_info) {
            $email_title = sprintf($title[$pauseType], $_info['app_name'], $_info['clientname']);
            //发送联盟运营 会抄送多个运营
            $email_content = sprintf($content_info_manage[$pauseType], $_info['app_name'], $_info['clientname']);
            EmailHelper::sendEmail('emails.jobs.pauseCampaign', [
                'subject' => $email_title,
                'msg' => [
                    'content' => $email_content,
                ],
            ], $list[$_info['agencyid']]);

            $email_content = sprintf($content_info[$pauseType], $_info['app_name'], $_info['clientname']);
            $sales = MessageService::getCampaignSaleUsersInfo($_info['campaignid']);
            $sales = array_diff(array_column($sales, 'email_address'), $list[$_info['agencyid']]);
            if (count($sales) > 0) {
                //发送广告主销售
                EmailHelper::sendEmail('emails.jobs.pauseCampaign', [
                    'subject' => $email_title,
                    'msg' => [
                        'content' => $email_content,
                    ],
                ], $sales);
            }
        }
    }

    /**
     *
     * @param $campaignIds
     * @return mixed
     */
    private function getCampaignInfo4Mail($campaignIds)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $campaigns = DB::table('campaigns as c')
            ->join('clients as cl', 'c.clientid', '=', 'cl.clientid')
            ->join('appinfos as app', function ($join) {
                $join->on('c.platform', '=', 'app.platform');
                $join->on('c.campaignname', '=', 'app.app_id');
            })
            ->whereIn('c.campaignid', $campaignIds)
            ->groupBy('cl.clientid')
            ->select('cl.clientname', 'app.app_name', 'c.campaignid', 'cl.agencyid')
            ->get();
        return $campaigns;
    }
}
