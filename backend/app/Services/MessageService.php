<?php
namespace App\Services;

use App\Components\Formatter;
use App\Models\Account;
use App\Models\Affiliate;
use App\Models\Campaign;
use App\Models\Daily;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use App\Components\Config;
use Illuminate\Support\Facades\DB;
use App\Models\AccountSubType;
use App\Models\Broker;
use App\Components\JpGraph;
use Illuminate\Support\Facades\Mail;
use App\Models\Banner;
use App\Components\Helper\EmailHelper;

class MessageService
{

    public static function sendWebMessage($role, $msgContent, $contentClass = null)
    {
        $user = Auth::user();
        $agency = $user->account->agency;
        $agencyId = $agency->agencyid;

        if ($role == 'M') {
            $roleTable = 'affiliates';
        } else {
            $roleTable = 'clients';
        }

        $users = User::leftjoin($roleTable, 'users.default_account_id', '=', $roleTable . '.account_id')
            ->where($roleTable . '.agencyid', '=', $agencyId)
            ->where($roleTable . '.' . $roleTable . '_status', 1)
            ->select('users.user_id', 'users.default_account_id as account_id')
            ->get();

        switch ($contentClass) {
            case 'note':
                $msgContent['content'] = [
                    'class' => 'note',
                    'body' => $msgContent['content'],
                    'role' => $role,
                    'total' => count($users)
                ];
                break;
        }
        if (is_array($msgContent['content'])) {
            $msgContent['content'] = json_encode($msgContent['content']);
        }
        if (count($users) > 0) {
            foreach ($users as $u) {
                $msgContent['target_accountid'] = $u->account_id;
                $msgContent['target_userid'] = $u->user_id;
                $message = new Message($msgContent);
                $message->save();
            }
            return true;
        } else {
            return false;
        }
    }

    /*
    * 获取联盟运营的用户
    */
    public static function getPlatUserInfo($agencyIds = null)
    {
        return self::getUserInfo('MANAGER', [
            AccountSubType::ACCOUNT_DEPARTMENT_MANAGER,
            AccountSubType::ACCOUNT_DEPARTMENT_OPERATION
        ], $agencyIds);
    }

    /**
     * 获取媒体运营、总经理、CEO、COO的信息
     */
    public static function getLeaderUserInfo($agencyIds = null)
    {
        return self::getUserInfo('MANAGER', [
            AccountSubType::ACCOUNT_DEPARTMENT_MANAGER,//管理员
            AccountSubType::ACCOUNT_DEPARTMENT_OPERATION,//运营
            AccountSubType::ACCOUNT_DEPARTMENT_HANDLER,//总经理
            AccountSubType::ACCOUNT_DEPARTMENT_CEO,//CEO
            AccountSubType::ACCOUNT_DEPARTMENT_COO //COO
        ], $agencyIds);
    }

    /**
     * 获取媒介经理
     * @return mixed
     */
    public static function getMediaUserInfo($agencyIds = null)
    {
        return self::getUserInfo('MANAGER', [
            AccountSubType::ACCOUNT_DEPARTMENT_MEDIA
        ], $agencyIds);
    }

    /**
     * 获取用户信息
     * @param $accountType
     * @param $arrSubType
     * @param $agencyIds
     * @return mixed
     */
    public static function getUserInfo($accountType, $arrSubType, $agencyIds)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('users as u')
            ->leftJoin('account_sub_type as ast', 'ast.id', '=', 'u.account_sub_type_id')
            ->select('u.user_id', 'u.contact_name', 'u.email_address', 'u.username', 'u.agencyid')
            ->where('u.active', User::ACTIVE_TRUE)
            ->where('ast.account_type', $accountType)
            ->whereIn('ast.account_department', $arrSubType);
        if ($agencyIds) {
            $select = $select->whereIn('u.agencyid', $agencyIds);
        }
        return $select->get();
    }

    /**
     * 根据campaignid获取对应的广告销售的信息
     * @param $campaignId
     * @return mixed
     */
    public static function getCampaignSaleUsersInfo($campaignId)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $client = DB::table('campaigns AS c')
            ->join('clients AS c1', 'c.clientid', '=', 'c1.clientid')
            ->select('c1.creator_uid', 'c1.broker_id')
            ->where('c.campaignid', $campaignId)
            ->first();
        return self::getSaleUserInfoByClient($client);
    }

    /**
     * 根据广告主信息获取广告主销售邮件
     * @param $client
     * @return mixed
     */
    public static function getSaleUserInfoByClient($client)
    {
        if ($client['broker_id'] > 0) {
            $result = DB::table('users AS u')
                ->join('brokers AS b', 'b.creator_uid', '=', 'u.user_id')
                ->join('account_sub_type AS ast', 'u.account_sub_type_id', '=', 'ast.id')
                ->select('u.contact_name', 'u.email_address', 'u.username')
                ->where('u.active', User::ACTIVE_TRUE)
                ->where('ast.account_type', AccountSubType::TYPE_MANAGER)
                ->where('ast.account_department', AccountSubType::ACCOUNT_DEPARTMENT_SALES)
                ->where('b.brokerid', $client['broker_id'])
                ->get();
        } else {
            $result = DB::table('users AS u')
                ->join('account_sub_type AS ast', 'u.account_sub_type_id', '=', 'ast.id')
                ->select('u.contact_name', 'email_address', 'username')
                ->where('u.active', User::ACTIVE_TRUE)
                ->where('ast.account_type', AccountSubType::TYPE_MANAGER)
                ->where('ast.account_department', AccountSubType::ACCOUNT_DEPARTMENT_SALES)
                ->where('u.user_id', $client['creator_uid'])
                ->get();
        }
        return $result;
    }

    /**
     * 根据媒体商id获取对应的媒介经理的信息
     */
    public static function getAffiliateManagerUsersInfo($affiliateId)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $userInfo = DB::table('users AS u')
            ->join('affiliates AS af', 'u.user_id', '=', 'af.creator_uid')
            ->leftJoin('account_sub_type AS ast', 'ast.id', '=', 'u.account_sub_type_id')
            ->where('u.active', User::ACTIVE_TRUE)
            ->where('ast.account_type', Account::TYPE_MANAGER)
            ->where('ast.account_department', AccountSubType::ACCOUNT_DEPARTMENT_MEDIA)
            ->where('af.affiliateid', $affiliateId)
            ->select('u.contact_name', 'email_address', 'username')
            ->get();
        return $userInfo;
    }

    /**
     * 获取biddingos日报
     * @param $date
     * @return bool|int
     */
    public static function getDailyReport($id)
    {
        $record = DB::table('daily')
            ->where('id', $id)
            ->where('type', Daily::TYPE_DAILY)
            ->first();
        if (!$record) {
            return false;
        }
        $date = explode('~', $record->date)[0];
        $yesterday = date('Y-m-d', strtotime("$date -1 day"));
        $month_first_day = date('Y-m-01', strtotime($yesterday));
        $revenue_type = Campaign::REVENUE_TYPE_CPD;
        //当月的汇总
        $sum_month = self::getReportData($month_first_day, $yesterday, $revenue_type);
        //昨日的消耗及下载量
        $sum_yesterday = self::getReportData($yesterday, $yesterday, $revenue_type);
        //昨日有效的广告
        $yesterday_ad = self::getValidCampaign($yesterday, $yesterday, $revenue_type);
        //昨日有效的广告主
        $yesterday_client = self::getValidClient($yesterday, $yesterday, $revenue_type);
        //获取当月排广告行前十
        $res = self::getCampaignTopTen($month_first_day, $yesterday, $revenue_type);
        //代理商的消耗
        $sum_broker = self::getBrokerRevenue($month_first_day, $yesterday, $revenue_type);
        //近七天下载量及消耗
        $sevenDays = self::getDailyData(date('Y-m-d', strtotime("$date -7 day")), $yesterday, $revenue_type);
        //获取媒体商top10数据
        $sum_trafficker = self::getTraffickerRevenue($month_first_day, $yesterday, $revenue_type);
        $data = [];
        if (sizeof($sum_month) > 0) {
            $data['sum_month'] = [
                'sum_revenue' => Formatter::asDecimal($sum_month->sum_revenue, 0, '.', ','),
                'sum_clicks' => Formatter::asDecimal($sum_month->sum_clicks, 0, '.', ','),
                'price' => Formatter::asDecimal($sum_month->sum_clicks ?
                    $sum_month->sum_revenue / $sum_month->sum_clicks : 0, 1),
            ];
        }

        if (sizeof($sum_yesterday) > 0) {
            $data['yesterday'] = [
                'sum_revenue' => Formatter::asDecimal($sum_yesterday->sum_revenue, 0, '.', ','),
                'sum_clicks' => Formatter::asDecimal($sum_yesterday->sum_clicks, 0, '.', ','),
                'price' => Formatter::asDecimal($sum_yesterday->sum_clicks ?
                    $sum_yesterday->sum_revenue / $sum_yesterday->sum_clicks : 0, 1),
                'sum_client' => $yesterday_client->sum_client,
                'sum_ad' => $yesterday_ad->sum_ad,
            ];
        }
        //生成代理商图片
        $file = base_path('public') . '/images/report/' . $date;
        JpGraph::reportBarBroker($sum_broker['revenue'], $sum_broker['name'], $file . '_01.jpg');
        //生成最近七天的图片
        $_start = date('Y-m-d', strtotime("$date -7 day"));
        $list = [];
        $sumRevenue = 0;
        $sumClicks = 0;
        if (sizeof($sevenDays) > 0) {
            foreach ($sevenDays as $row) {
                $list[$row['date']] = $row;
                $sumRevenue += $row['sum_revenue'];
                $sumClicks += $row['sum_clicks'];
            }
        }

        for ($i = 0; $i < 7; $i++) {
            $list = array_add($list, $_start, ['sum_clicks' => 0, 'sum_revenue' => 0]);
            $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
        }
        ksort($list);
        $chart = [];
        foreach ($list as $k => $v) {
            $chart['date'][] = date("m-d", strtotime($k));
            $chart['data'][0][] = $v['sum_clicks'];
            $chart['data'][1][] = $v['sum_revenue'];
        }
        JpGraph::reportBarDownload($chart['data'], $chart['date'], $file . '_02.jpg');
        //生成媒体商top5及媒体的饼图
        if (sizeof($sum_trafficker) > 0) {
            JpGraph::reportBarBroker(
                array_slice($sum_trafficker['sum_payment'], 0, 5),
                array_slice($sum_trafficker['name'], 0, 5),
                $file . '_04.jpg'
            );
            JpGraph::reportPiePayment(
                array_reverse($sum_trafficker['sum_payment']),
                array_reverse($sum_trafficker['label']),
                $file . '_03.jpg'
            );
        } else {
            JpGraph::reportPiePayment([1], ['none'], $file . '_03.jpg');
            JpGraph::reportBarBroker([0], ['none'], $file . '_04.jpg');
        }
        $data['chart'] = [
            'avg_clicks' => Formatter::asDecimal($sumClicks / 70000, 1, '.', ','),
            'avg_revenue' => Formatter::asDecimal($sumRevenue / 70000, 1, '.', ','),
        ];

        $current_revenue = 0;
        $current_clicks = 0;
        $current_price = 0;
        $current_rate = 0;
        $data['rank'] = [];
        if (sizeof($res) > 0) {
            foreach ($res as $val) {
                $item = $val;
                if ($val['broker_id'] > 0) {
                    $item['brief_name'] = Broker::find($val['broker_id'])['brief_name'];
                } else {
                    $item['brief_name'] = '核聚';
                }
                $item['rate'] = Formatter::asDecimal($data['sum_month']['sum_revenue'] ?
                    $val['sum_revenue'] / $sum_month->sum_revenue * 100 : 0);
                $item['price'] = Formatter::asDecimal($val['sum_clicks'] ?
                    $val['sum_revenue'] / $val['sum_clicks'] : 0);
                $item['sum_revenue'] = Formatter::asDecimal($val['sum_revenue']);
                $data['rank'][] = $item;
                $current_revenue += $val['sum_revenue'];
                $current_clicks += $val['sum_clicks'];
                $current_price = Formatter::asDecimal($current_clicks ?  $current_revenue / $current_clicks : 0);
                $current_rate = Formatter::asDecimal($data['sum_month']['sum_revenue'] ?
                    $current_revenue / $sum_month->sum_revenue * 100 : 0);
            }
        }
        //top10汇总信息
        $data['sum'] = [
            'sum_revenue' => Formatter::asDecimal($current_revenue),
            'sum_clicks' => $current_clicks,
            'price' => $current_price,
            'rate' => $current_rate,
        ];
        $image_url = 'http://'.$_SERVER['SERVER_NAME'].'/bos-backend-api/images/report/'.$date;
        $data['image'] = [
            'seven_url' => $image_url . '_02.jpg',
            'broker_url' => $image_url . '_01.jpg',
            'trafficker_url' => $image_url . '_03.jpg',
            'trafficker_rank_url' => $image_url . '_04.jpg',
        ];
        $data['subject'] = [
            'logo_url' => 'http://'.$_SERVER['SERVER_NAME'].'/bos-backend-api/images/default/logo.jpg',
            'date' => $date,
            'limit_date' => $yesterday,
        ];
        $subject = 'BiddingOS每日报告 - ' . $date;
        $receiver = self::getMailReceiver();
        if (!$receiver) {
            return 5038;
        }
        $result = Mail::send('emails.jobs.dailyReport', $data, function ($message) use ($receiver, $subject) {
            $message->subject($subject);
            //获取邮件发送地址
            foreach ($receiver as $item) {
                $message->to($item);
            }
        });
        if ($result > 0) {
            DB::table('daily')->where('id', $id)->update([
                'status' => Daily::STATUS_SEND,
                'receiver' => implode(",", $receiver),
                'send_time' => date('Y-m-d H:i:s')
            ]);
        } else {
            DB::table('daily')->where('id', $id)->update([
                'status' => Daily::STATUS_FAIL,
                'send_time' => date('Y-m-d H:i:s')
            ]);
        }
        return true;
    }

    /**
     * 获取biddingos周报
     * @param $id
     * @return bool|int
     */
    public static function getWeeklyReport($id)
    {
        $record = DB::table('daily')
            ->where('id', $id)
            ->where('type', Daily::TYPE_WEEKLY)
            ->first();
        if (!$record) {
            return false;
        }

        $start = explode('~', $record->date)[0];
        $end = date('Y-m-d', strtotime("$start 6 day"));
        $revenue_type = Campaign::REVENUE_TYPE_CPD;
        $file = base_path('public') . '/images/report/weekly' . $start . '-'. $end;
        $data = [];

        $sum_week = self::getReportData($start, $end, $revenue_type);//周汇总
        $valid_campaign = self::getValidCampaign($start, $end, $revenue_type);//本周有效的广告
        $valid_client = self::getValidClient($start, $end, $revenue_type);//本周有效的广告主
        if (sizeof($sum_week) > 0) {
            $data['sum_week'] = [
                'sum_revenue' => Formatter::asDecimal($sum_week->sum_revenue, 0, '.', ','),
                'sum_clicks' => Formatter::asDecimal($sum_week->sum_clicks, 0, '.', ','),
                'price' => Formatter::asDecimal($sum_week->sum_clicks ?
                    $sum_week->sum_revenue / $sum_week->sum_clicks : 0, 1),
                'sum_ad' => $valid_campaign->sum_ad,
                'sum_client' => $valid_client->sum_client,
            ];
        }
        //获取最近五周的数据
        $five_week = self::getDailyData(
            date('Y-m-d', strtotime("$start -28 day")),
            date('Y-m-d', strtotime("$start 6 day")),
            $revenue_type
        );
        $_start = date('Y-m-d', strtotime("$start -28 day"));
        $list = [];
        $sumRevenue = 0;
        $sumClicks = 0;
        if (sizeof($five_week) > 0) {
            foreach ($five_week as $row) {
                $k = (strtotime($row['date']) - strtotime($_start))/ 86400/7 + 1;
                if (isset($list[$k])) {
                    $list[$k]['sum_revenue'] += $row['sum_revenue'];
                    $list[$k]['sum_clicks'] += $row['sum_clicks'];
                } else {
                    $list[$k] = $row;
                }
                $sumClicks += $row['sum_clicks'];
                $sumRevenue += $row['sum_revenue'];
            }
        }
        for ($i = 1; $i <= 5; $i++) {
            $list = array_add($list, $i, ['sum_clicks' => 0, 'sum_revenue' => 0]);
        }
        ksort($list);
        $chart = [];
        foreach ($list as $k => $v) {
            $_end = date('Y-m-d', strtotime("$_start 6 day"));
            $chart['date'][] = date('m-d', strtotime($_start)) . '~'. date('d', strtotime($_end));
            $chart['data'][0][] = $v['sum_clicks'];
            $chart['data'][1][] = $v['sum_revenue'];
            $_start = date('Y-m-d', strtotime("$_start 7 day"));
        }
        $data['chart'] = [
            'avg_clicks' => Formatter::asDecimal($sumClicks / 50000, 1, '.', ','),
            'avg_revenue' => Formatter::asDecimal($sumRevenue / 50000, 1, '.', ','),
        ];
        JpGraph::reportBarDownload($chart['data'], $chart['date'], $file . '_02.jpg');
        //代理商的消耗 并生成代理商图片
        $sum_broker = self::getBrokerRevenue($start, $end, $revenue_type);
        JpGraph::reportBarBroker($sum_broker['revenue'], $sum_broker['name'], $file . '_01.jpg');
        //获取周排行前十
        $res = self::getCampaignTopTen($start, $end, $revenue_type);
        $data['rank'] = [];
        $current_revenue = 0;
        $current_clicks = 0;
        $current_price = 0;
        $current_rate = 0;
        if (sizeof($res) > 0) {
            foreach ($res as $val) {
                $item = $val;
                if ($val['broker_id'] > 0) {
                    $item['brief_name'] = Broker::find($val['broker_id'])['brief_name'];
                } else {
                    $item['brief_name'] = '核聚';
                }
                $item['rate'] = Formatter::asDecimal($data['sum_week']['sum_revenue'] ?
                    $val['sum_revenue'] / $sum_week->sum_revenue * 100 : 0);
                $item['price'] = Formatter::asDecimal($val['sum_clicks'] ?
                    $val['sum_revenue'] / $val['sum_clicks'] : 0);
                $item['sum_revenue'] = Formatter::asDecimal($val['sum_revenue']);
                $data['rank'][] = $item;
                $current_revenue += $val['sum_revenue'];
                $current_clicks += $val['sum_clicks'];
                $current_price = Formatter::asDecimal($current_clicks ?  $current_revenue / $current_clicks : 0);
                $current_rate = Formatter::asDecimal($data['sum_week']['sum_revenue'] ?
                    $current_revenue / $sum_week->sum_revenue * 100 : 0);
            }
        }
        //计算周排行的汇总信息
        $data['sum'] = [
            'sum_revenue' => Formatter::asDecimal($current_revenue),
            'sum_clicks' => $current_clicks,
            'price' => $current_price,
            'rate' => $current_rate,
        ];
        //获取媒体商消耗数据
        $sum_trafficker = self::getTraffickerRevenue($start, $end, $revenue_type);
        //生成媒体商top5及媒体的饼图
        if (sizeof($sum_trafficker) > 0) {
            JpGraph::reportBarBroker(
                array_slice($sum_trafficker['sum_payment'], 0, 5),
                array_slice($sum_trafficker['name'], 0, 5),
                $file . '_04.jpg'
            );
            JpGraph::reportPiePayment(
                array_reverse($sum_trafficker['sum_payment']),
                array_reverse($sum_trafficker['label']),
                $file . '_03.jpg'
            );
        } else {
            JpGraph::reportPiePayment([1], ['none'], $file . '_03.jpg');
            JpGraph::reportBarBroker([0], ['none'], $file . '_04.jpg');
        }
        $image_url = 'http://'.$_SERVER['SERVER_NAME'].'/bos-backend-api/images/report/weekly'. $start .'-'. $end;
        $data['image'] = [
            'seven_url' => $image_url . '_02.jpg',
            'broker_url' => $image_url . '_01.jpg',
            'trafficker_url' => $image_url . '_03.jpg',
            'trafficker_rank_url' => $image_url . '_04.jpg',
        ];
        $data['subject'] = [
            'logo_url' => 'http://'.$_SERVER['SERVER_NAME'].'/bos-backend-api/images/default/logo.jpg',
            'start_date' => $start,
            'end_date' => $end,
        ];
        $subject = 'BiddingOS周报 - ' . $start . '~' . date('m-d', strtotime($end));
        $receiver = self::getMailReceiver();

        if (!$receiver) {
            return 5038;
        }

        $result = Mail::send('emails.jobs.weeklyReport', $data, function ($message) use ($receiver, $subject) {
            $message->subject($subject);
            //获取邮件发送地址
            foreach ($receiver as $item) {
                $message->to($item);
            }
        });

        if ($result > 0) {
            DB::table('daily')->where('id', $id)->update([
                'status' => Daily::STATUS_SEND,
                'receiver' => implode(",", $receiver),
                'send_time' => date('Y-m-d H:i:s')
            ]);
        } else {
            DB::table('daily')->where('date', $id)->update([
                'status' => Daily::STATUS_FAIL,
                'send_time' => date('Y-m-d H:i:s')
            ]);
        }
        return true;
    }

    /**
     * 获取默认发送邮件地址
     * @return array|static[]
     */
    private static function getMailReceiver()
    {
        $mail = Config::get('daily_mail_address');
        return explode(';', $mail);
    }

    /**
     *
     */
    public static function getUserListByPermission($permission, $agencyId)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $rows = DB::table('users AS u')
            ->join('roles AS r', 'u.role_id', '=', 'r.id')
            ->whereRaw("FIND_IN_SET('{$permission}', operation_list)")
            ->where('u.agencyid', $agencyId)
            ->select('u.user_id', 'u.role_id', 'u.contact_name', 'u.email_address', 'u.username')
            ->get();
        return $rows;
    }

    /**
     * 根据日期获取审核之后的总下载量，总消耗
     * @param $start
     * @param $end
     * @param $revenue_type
     * @return mixed
     */
    public static function getReportData($start, $end, $revenue_type)
    {
        $real_table = DB::getTablePrefix() . 'data_hourly_daily_client';
        $res = DB::table('data_hourly_daily_client')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_client.campaign_id')
            ->whereBetween('date', [$start, $end])
            ->where('campaigns.revenue_type', $revenue_type)
            ->select(
                DB::raw('IFNULL(SUM(' . $real_table . '.`conversions`),0) as `sum_clicks`'),
                DB::raw('IFNULL(SUM(' . $real_table . '.`total_revenue`),0)as `sum_revenue`')
            )
            ->first();
        return $res;
    }

    /**
     * 获取每天的下载量及消耗
     * @param $start
     * @param $end
     * @param $revenue_type
     * @return mixed
     */
    public static function getDailyData($start, $end, $revenue_type)
    {
        $real_table = DB::getTablePrefix() . 'data_hourly_daily_client';
        $res = DB::table('data_hourly_daily_client')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_client.campaign_id')
            ->where('campaigns.revenue_type', $revenue_type)
            ->select(
                DB::raw('SUM(' . $real_table . '.`conversions`) as `sum_clicks`'),
                DB::raw('SUM(' . $real_table . '.`total_revenue`) as `sum_revenue`'),
                'data_hourly_daily_client.date'
            )
            ->whereBetween('data_hourly_daily_client.date', [$start, $end])
            ->groupBy('date')
            ->get();
        return json_decode(json_encode($res), true);
    }

    /**
     * 获取有效的广告数量
     * @param $start
     * @param $end
     * @param $revenue_type
     * @return mixed
     */
    public static function getValidCampaign($start, $end, $revenue_type)
    {
        $prefix =  DB::getTablePrefix();
        $real_table = $prefix . 'data_hourly_daily_client';
        $res =   DB::table(
            DB::raw(
                "(SELECT SUM( {$real_table}.total_revenue) as sum_revenue
                    FROM {$real_table}
                    INNER JOIN {$prefix}campaigns ON
                    {$prefix}campaigns.campaignid = {$real_table}.campaign_id
                    WHERE {$real_table}.date between '{$start}' and '{$end}'
                    AND {$prefix}campaigns.`revenue_type` = {$revenue_type}
                    GROUP BY {$real_table}.campaign_id) as t"
            )
        )
            ->where('sum_revenue', '>', 0)
            ->select(
                DB::raw('COUNT(*) as sum_ad')
            )
            ->first();
        return $res;
    }

    /**
     * 获取有效的广告主
     * @param $start
     * @param $end
     * @param $revenue_type
     * @return mixed
     */
    public static function getValidClient($start, $end, $revenue_type)
    {
        $prefix =  DB::getTablePrefix();
        $real_table = $prefix . 'data_hourly_daily_client';
        $res = DB::table(
            DB::raw(
                "(SELECT SUM( {$real_table} .total_revenue) as sum_revenue
                    FROM {$real_table}
                    INNER JOIN {$prefix}campaigns ON
                    {$prefix}campaigns.campaignid = {$real_table}.campaign_id
                    WHERE {$real_table}.date between '{$start}' and '{$end}'
                    AND {$prefix}campaigns.`revenue_type` = {$revenue_type}
                    GROUP BY {$real_table}.clientid
                ) as t"
            )
        )
            ->where('sum_revenue', '>', 0)
            ->select(
                DB::raw('COUNT(*) sum_client')
            )->first();
        return $res;
    }

    /**
     * 获取广告排名前10的数据
     * @param $start
     * @param $end
     * @param $revenue_type
     * @return mixed
     */
    public static function getCampaignTopTen($start, $end, $revenue_type)
    {
        $real_table = DB::getTablePrefix() . 'data_hourly_daily_client';
        $res = DB::table('data_hourly_daily_client')
            ->join('campaigns as c', 'c.campaignid', '=', 'data_hourly_daily_client.campaign_id')
            ->join('clients as cli', 'cli.clientid', '=', 'data_hourly_daily_client.clientid')
            ->join('appinfos as app', function ($join) {
                $join->on('c.campaignname', '=', 'app.app_id')
                    ->on('c.platform', '=', 'app.platform')
                    ->on('cli.agencyid', '=', 'app.media_id');
            })
            ->whereBetween('date', [$start, $end])
            ->where('c.revenue_type', $revenue_type)
            ->select(
                DB::raw('SUM(' . $real_table . '.`conversions`) as `sum_clicks`'),
                DB::raw('SUM(' . $real_table . '.`total_revenue`) as `sum_revenue`'),
                'app.app_name',
                'broker_id'
            )
            ->groupBy('campaign_id')
            ->orderBy('sum_revenue', 'DESC')
            ->limit(10)
            ->get();
        return json_decode(json_encode($res), true);
    }

    /**
     * 获取代理商消耗
     * @param $start
     * @param $end
     * @param $revenue_type
     * @return mixed
     */
    public static function getBrokerRevenue($start, $end, $revenue_type)
    {
        $real_table = DB::getTablePrefix() . 'data_hourly_daily_client';
        $res = DB::table('data_hourly_daily_client')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_client.campaign_id')
            ->join('clients', 'clients.clientid', '=', 'data_hourly_daily_client.clientid')
            ->where('campaigns.revenue_type', $revenue_type)
            ->select(
                DB::raw('SUM(' . $real_table . '.`total_revenue`) as `sum_revenue`'),
                'clients.broker_id'
            )
            ->whereBetween('date', [$start, $end])
            ->groupBy('broker_id')
            ->orderBy('sum_revenue', 'DESC')
            ->get();
        $res = json_decode(json_encode($res), true);

        $broker = [];
        if (sizeof($res) > 0) {
            foreach ($res as $val) {
                $broker['revenue'][] = $val['sum_revenue'];
                if ($val['broker_id'] > 0) {
                    $broker['name'][] = Broker::find($val['broker_id'])['brief_name'];
                } else {
                    $broker['name'][] = '核聚';
                }
            }
        } else {
            $broker['revenue'] = [0];
            $broker['name'] = ['none'];
        }
        return $broker;
    }

    /**
     * 获取媒体消耗
     * @param $start
     * @param $end
     * @param $revenue_type
     * @return mixed
     */
    public static function getTraffickerRevenue($start, $end, $revenue_type)
    {
        $real_table = DB::getTablePrefix() . 'data_hourly_daily_client';
        $res = DB::table('data_hourly_daily_client')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_client.campaign_id')
            ->join('banners', 'banners.bannerid', '=', 'data_hourly_daily_client.ad_id')
            ->where('campaigns.revenue_type', $revenue_type)
            ->select(
                DB::raw('SUM(' . $real_table . '.`total_revenue`) as `sum_payment`'),
                'banners.affiliateid'
            )
            ->whereBetween('date', [$start, $end])
            ->where('total_revenue', '>', 0)
            ->groupBy('banners.affiliateid')
            ->orderBy('sum_payment', 'DESC')
            ->get();
        $sum_trafficker = json_decode(json_encode($res), true);
        $trafficker = [];
        foreach ($sum_trafficker as $k => $val) {
            if ($k < 10) {
                $trafficker['sum_payment'][$k] = $val['sum_payment'];
                $trafficker['name'][$k] = Affiliate::find($val['affiliateid'])['brief_name'];
                $trafficker['label'][$k] = $trafficker['name'][$k] . " %.1f%%";
            } else {
                if (isset($trafficker['sum_payment'][10])) {
                    $trafficker['sum_payment'][10] += $val['sum_payment'];
                    $trafficker['name'][10] = '其他';
                    $trafficker['label'][10] = $trafficker['name'][10] . " %.1f%%";
                } else {
                    $trafficker['sum_payment'][10] = $val['sum_payment'];
                }
            }
        }
        return $trafficker;
    }
    
    /**
     * 更换渠道包时，给对应媒体商的联系人发送邮件
     * @param int $campaignId
     * @param int $new_attach_id
     */
    public static function sendPackageChangeMail($campaignId, $new_attach_id)
    {
        $config_affiliateIds = Config::get('mail_package_notice_afid');
        $affiliateIds = explode("|", $config_affiliateIds);
        foreach ($affiliateIds as $affiliateId) {
            $old_attach_id = Banner::where('affiliateid', $affiliateId)
                ->where('campaignid', $campaignId)
                ->pluck('attach_file_id');
            if ($old_attach_id > 0 && $old_attach_id != $new_attach_id && $new_attach_id > 0) {
                \DB::setFetchMode(\PDO::FETCH_ASSOC);
                $info = DB::table('banners')
                    ->join('affiliates', 'affiliates.affiliateid', '=', 'banners.affiliateid')
                    ->join('campaigns', 'campaigns.campaignid', '=', 'banners.campaignid')
                    ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
                    ->where('campaigns.campaignid', $campaignId)
                    ->where('affiliates.affiliateid', $affiliateId)
                    ->select(
                        'affiliates.email',
                        'products.name'
                    )
                    ->first();
                \DB::setFetchMode(\PDO::FETCH_CLASS);
                $mail = [];
                $product_name = $info['name'];
                $mail['subject'] = "{$product_name}安装包更新-BiddingOS";
                $mail['msg']['product_name'] = $product_name;
                $mailTo = $info['email'];
                EmailHelper::sendEmail(
                    'emails.trafficker.packageChangeNotice',
                    $mail,
                    $mailTo
                );
            }
        }
    }
}
