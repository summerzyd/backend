<?php
namespace App\Console\Commands;

use App\Components\Formatter;
use App\Models\Agency;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use App\Components\Helper\EmailHelper;
use App\Services\MessageService;

class JobMakeBalanceReminder extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_make_balance_reminder {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job for making reminder of advertisers account balance.';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function finish(Agency $agency)
    {
        // 遍历所有的广告主
        $clientsInfo = $this->getClientsInfo($agency->agencyid);
        $platForm = Campaign::getPlatformLabels(null, -1);
        $clientArr = array();
        $sales = [];
        foreach ($clientsInfo as $client) {
            $sum_day_limit = $this->getClientSumDayLimitByID($client['clientid']);
            $sum_balance = $client['balance'] + $client['gift'];
            $sum_balance = Formatter::asDecimal($sum_balance);
            if ($sum_day_limit <= $sum_balance) {
                continue;
            } else {
                // 根据广告主的id取得广告应用列表
                $campaigns = $this->getClientsCampaigns($client['clientid']);
                $clientArr[] = [
                    'clientname' => $client['clientname'],
                    'balance' => $sum_balance,
                    'target' => 'sales',
                    'campaigns' => $campaigns
                ];

                //获取所有广告主销售邮件
                $email = MessageService::getSaleUserInfoByClient($client);
                if (!empty($email)) {
                    foreach ($email as $item) {
                        $sales[] = $item['email_address'];
                    }
                }
            }
        }

        if (!empty($clientArr)) {
            //发送邮件给所有运营及管理
            $agencies = MessageService::getPlatUserInfo([$agency->agencyid]);
            $email['msg']['target'] = 'agency';
            $email['msg']['val'] = $clientArr;
            $email['msg']['platForm'] = $platForm;
            $email['subject'] = $clientArr[0]['clientname']
                . "等"
                . count($clientArr)
                . "个广告主余额不足，即将暂停";
            $agencies = array_column($agencies, 'email_address');
            EmailHelper::sendEmail('emails.command.makeBalanceReminder', $email, $agencies);

            //发送邮件给所有广告主销售
            $mail['subject'] = $clientArr[0]['clientname']
                . "等"
                . count($clientArr)
                . "个广告主余额不足，即将暂停";
            $mail['msg']['val'] = $clientArr;
            $mail['msg']['platForm'] = $platForm;
            $mail['msg']['target'] = 'sales';

            EmailHelper::sendEmail('emails.command.makeBalanceReminder', $mail, array_diff($sales, $agencies));
        }
    }

    /**
     * 查询所有广告主的名称，邮箱和余额等信息
     *
     * @return array
     */
    private function getClientsInfo($agencyId)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $rows = DB::table('clients as cl')
            ->where('cl.agencyid', $agencyId)
            ->leftJoin('balances as b', 'b.account_id', '=', 'cl.account_id')
            ->select(
                'cl.creator_uid',
                'cl.clientid',
                'cl.broker_id',
                'cl.clientname',
                'b.balance',
                'b.gift'
            )
            ->get();
        return $rows;
    }


    /**
     * 计算某广告主所有在投广告的日限额之和
     * @param $clientId
     * @return int
     */
    private function getClientSumDayLimitByID($clientId)
    {
        $sum_day_limit = 0;
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $res = DB::table('campaigns')
            ->select('clientid', DB::raw('SUM(day_limit) as day_limit'))
            ->where('clientid', $clientId)
            ->where('status', Campaign::STATUS_DELIVERING)
            ->first();

        if ($res) {
            $sum_day_limit = $res['day_limit'];
        }
        return $sum_day_limit;
    }

    private function getClientsCampaigns($clientId)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $rows = DB::table('campaigns')
            ->leftJoin('appinfos', "campaigns.campaignname", '=', 'appinfos.app_id')
            ->where('campaigns.clientid', $clientId)
            ->select('appinfos.app_show_name', 'campaigns.platform')
            ->get();
        return $rows;
    }
}
