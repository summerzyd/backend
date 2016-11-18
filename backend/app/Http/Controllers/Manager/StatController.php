<?php

namespace App\Http\Controllers\Manager;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Models\Affiliate;
use App\Models\AffiliateExtend;
use App\Models\AffiliateUserReport;
use App\Models\AppInfo;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ManualClientData;
use App\Models\ManualDeliveryData;
use App\Models\Product;
use App\Models\Zone;
use App\Services\StatService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Components\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\OperationClient;
use Qiniu\json_decode;
use Monolog\Logger;
use App\Services\ManualService;

class StatController extends Controller
{
    const SPAN_HOURS = 1;
    const SPAN_DAYS = 2;
    const SPAN_MONTH = 3;

    private $connect = null;

    /**
     * Auth认证，设置为全部要认证，如果不需要认证请在子类中覆盖该函数
     */
    public $fieldAll = [
        'sum_views',
        'sum_cpc_clicks',
        'sum_download_requests',
        'sum_download_complete',
        'sum_clicks',
        'sum_payment',
        'sum_payment_gift',
        'sum_revenue',
        'sum_revenue_gift',
        'sum_cpa',
        'sum_consum',
    ];
    public $fieldControl = [
        'cpd',//平均单价(广告主)
        'media_cpd',//平均单价(媒体商
        'ecpm',//eCPM
        'ctr',       //下载转化率
        'cpc_ctr',  //点击转化率
        'sum_views',
        'sum_cpc_clicks',
        'sum_download_requests',
        'sum_download_complete',
        'sum_clicks',
        'sum_payment_trafficker',
        'sum_payment',
        'sum_payment_gift',
        'sum_revenue_client',
        'sum_revenue',
        'sum_revenue_gift',
        'sum_cpa',
        'sum_consum',
        'profit',
        'profit_rate',
    ];
    public $sumData = [
        'sum_views' => 0,
        'sum_cpc_clicks'  => 0,
        'sum_download_requests'  => 0,
        'sum_download_complete'  => 0,
        'sum_clicks'  => 0,
        'sum_payment_trafficker' => 0,
        'sum_payment'  => 0,
        'sum_payment_gift'  => 0,
        'sum_revenue_client'  => 0,
        'sum_revenue'  => 0,
        'sum_revenue_gift'  => 0,
        'sum_cpa'  => 0,
        'sum_consum' => 0,
        'profit' => 0,
    ];
    public $showName = [
        'sum_views' => '展示量', //有权限
        'sum_cpc_clicks' => '点击量',
        'sum_download_requests' => '下载请求(监控)', //有权限
        'sum_download_complete' => '下载完成(监控)', //有权限
        'sum_clicks' => '下载量(上报)', //有权限
        'sum_cpa' => 'CPA量', //有权限
        'ctr' => '下载转化率', //有权限
        'cpc_ctr' => '点击转化率',
        'sum_revenue_client' => '广告主消耗（总数)',
        'sum_revenue' => '广告主消耗(充值金)', //有权限
        'sum_revenue_gift' => '广告主消耗(赠送金)', //有权限
        'client_revenue_type' => '计费方式(广告主)',
        'sum_payment_trafficker' => '媒体支出（总数）',
        'sum_payment' => '媒体支出(充值金)', //有权限
        'sum_payment_gift' => '媒体支出(赠送金)', //有权限
        'media_revenue_type' => '计费方式(媒体商)',
        'cpd' => '平均单价(广告主)', //有权限
        'media_cpd' => '平均单价(媒体商)', //有权限
        'ecpm' => 'eCPM', //有权限
        'profit' => '毛利',
        'profit_rate' => '毛利率',
    ];

    /**
     * @return array
     */
    protected static function attributeLabels()
    {
        return [
            'period_start' => '开始时间',
            'period_end' => '结束时间',
            'span' => '数据分组类型',
            'zone_offset' => '时区',
            'zoneOffset' => '时区',
            'type' => '类型',
            'date' => '日期'
        ];
    }
    /**
     * @param $request
     * @return array
     */
    private function transFormInput($request)
    {
        $param = [];
        $param['zoneOffset'] = $request->input('zone_offset');
        $span = $request->input('span');
        $period_start = $request->input('period_start');
        $period_end = $request->input('period_end');
    
        //如果是按照小时分组则转化为UTC时间
        if (self::SPAN_HOURS == $span) {
            $param['axis'] = 'hours';
            $period_start = date('Y-m-d H:i:s', strtotime($period_start . ' 00:00:00') +
                    $param['zoneOffset'] * 3600);
            $period_end = date('Y-m-d H:i:s', strtotime($period_end . ' 23:59:59') +
                    $param['zoneOffset'] * 3600);
        }
        if (self::SPAN_DAYS == $span) {
            $param['axis'] = 'days';
        }
        if (self::SPAN_MONTH == $span) {
            $param['axis'] = 'month';
        }
        //如果是不是传入北京时间
        $param['period_start'] = $period_start;
        $param['period_end'] = $period_end;
        //按小时，天，月查询
        return $param;
    }
    /**
     * 广告主概览报表
     * @return \Illuminate\Http\Response
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | sum_views | array | 展示量 |  | 是 |
     * | sum_clicks | array | 下载量 |  | 是 |
     * | sum_revenue | array | 收入 |  | 是 |
     * | profit | array | 毛利 |  | 是 |
     * | sum_cpc_clicks | array | 点击量 |  | 是 |
     */
    public function index()
    {
        $agencyId = Auth::user()->agencyid;
        $yesterday = date('Y-m-d', strtotime("-1 days"));
        $before = date(date('Y-m-d', strtotime("-2 days")));
        $real_table = DB::getTablePrefix() . 'data_hourly_daily';
        $row = DB::table('data_hourly_daily')
            ->join('campaigns', 'data_hourly_daily.campaign_id', '=', 'campaigns.campaignid')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->whereBetween('date', [$before, $yesterday])
            ->select(
                DB::raw('IFNULL(SUM(' . $real_table . '.`impressions`),0) as `sum_views`'),
                DB::raw('IFNULL(SUM(' . $real_table . '.`conversions`),0) as `sum_clicks`'),
                DB::raw('IFNULL(SUM(' . $real_table . '.`clicks`),0) as `sum_cpc_clicks`'),
                DB::raw('IFNULL(SUM(' . $real_table . '.`total_revenue`),0)as `sum_revenue`'),
                DB::raw('IFNULL(SUM(' . $real_table . '.`af_income`),0)as `sum_payment`'),
                'data_hourly_daily.date'
            )
            ->where('clients.agencyid', $agencyId)
            ->where('clients.affiliateid', 0)
            ->groupBy('data_hourly_daily.date')
            ->get();
        $res= [];
        if (!empty($row)) {
            foreach ($row as $val) {
                $res[$val->date] = [
                        $val->sum_views,
                        $val->sum_clicks,
                        $val->sum_cpc_clicks,
                        $val->sum_revenue,
                        $val->sum_revenue - $val->sum_payment
                ];
            }
        }
        if (!isset($res[$yesterday])) {
            $res = array_add($res, $yesterday, [0, 0, 0, 0, 0]);
        }
        if (!isset($res[$before])) {
            $res = array_add($res, $before, [0, 0, 0, 0, 0]);
        }
        $list = [];
        $list['sum_views'] = StatService::getRate($res[$yesterday][0], $res[$before][0]);
        $list['sum_clicks'] = StatService::getRate($res[$yesterday][1], $res[$before][1]);
        $list['sum_cpc_clicks'] = StatService::getRate($res[$yesterday][2], $res[$before][2]);
        $list['sum_revenue'] =  StatService::getRate($res[$yesterday][3], $res[$before][3]);
        $list['sum_profit'] =  StatService::getRate($res[$yesterday][4], $res[$before][4]);
    
        return $this->success(null, null, $list);
    }

    /**
     * 平台概览 30 7天趋势 展示量、下载量、 点击量、 广告主消耗、 平台毛利
     *
     *  | name | type | description | restraint | required |
     *  | :--: | :--: | :--------: | :-------: | :-----: |
     *  | sum_views | array | 展示量 |  | 是 |
     *  | sum_clicks | array | 下载量 |  | 是 |
     *  | sum_revenue | array | 收入 |  | 是 |
     *  | profit | array | 毛利 |  | 是 |
     *  | sum_cpc_clicks | array | 点击量 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | sub name | sub name | type | description | restraint | required |
     * | :--: | :--: | :--: | :--: | :--------: | :-------: | :-----: |
     * | revenue |  |  |  |  | 广告主消耗 |  | 是 |
     * | | summary |  |  |  |  |  | 是 |
     * | |  | time |  |  | 时间 |  | 是 |
     * | |  | revenue |  |  | 广告主总消耗 |  | 是 |
     * | | data |  |  |  |  |  | |
     * | |  | affiliateid |  |  | 媒体商id |  | 是 |
     * | |  | brief_name |  |  | 媒体商简称 |  | 是 |
     * | |  | child |  |  |  |  | |
     * | |  |  | time |  | 时间 |  | 是 |
     * | |  |  | revenue |  | 广告主总消耗 |  | 是 |
     * | profit |  |  |  |  | 平台毛利 |  | 是 |
     * | | summary |  |  |  |  |  | 是 |
     * | |  | time |  |  | 时间 |  | 是 |
     * | |  | profit |  |  | 平台毛利 |  | 是 |
     * | | data |  |  |  |  |  | |
     * | |  | affiliateid |  |  | 媒体商id |  | 是 |
     * | |  | brief_name |  |  | 媒体商简称 |  | 是 |
     * | |  | child |  |  |  |  | |
     * | |  |  | time |  | 时间 |  | 是 |
     * | |  |  | profit |  | 平台毛利 |  | 是 |
     * | views |  |  |  |  | 展示量 |  | 是 |
     * | | summary |  |  |  |  |  | 是 |
     * | |  | time |  |  | 时间 |  | 是 |
     * | |  | views |  |  | 展示量 |  | 是 |
     * | | data |  |  |  |  |  | |
     * | |  | affiliateid |  |  | 媒体商id |  | 是 |
     * | |  | brief_name |  |  | 媒体商简称 |  | 是 |
     * | |  | child |  |  |  |  | |
     * | |  |  | time |  | 时间 |  | 是 |
     * | |  |  | views |  | 展示量 |  | 是 |
     * | clicks |  |  |  |  | 下载量 |  | 是 |
     * | | summary |  |  |  |  |  | 是 |
     * | |  | time |  |  | 时间 |  | 是 |
     * | |  | clicks |  |  | 下载量 |  | 是 |
     * | | data |  |  |  |  |  | |
     * | |  | affiliateid |  |  | 媒体商id |  | 是 |
     * | |  | brief_name |  |  | 媒体商简称 |  | 是 |
     * | |  | child |  |  |  |  | |
     * | |  |  | time |  | 时间 |  | 是 |
     * | |  |  | clicks |  | 下载量 |  | 是 |
     */
    public function trend(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required|in:0,1',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $agencyId = Auth::user()->agencyid;
        $type = Input::get('type');
        if ($type == 0) {
            $start = date('Y-m-d', strtotime("-30 days"));
        } else {
            $start = date('Y-m-d', strtotime("-7 days"));
        }
        $end = date('Y-m-d', strtotime("-1 days"));

        $label = ['revenue', 'income', 'clicks', 'cpc_clicks', 'views'];
        $table = 'data_hourly_daily';
        $res = StatService::getTrendData($start, $end, $table, $agencyId, 0, 1);
        $list = StatService::recombinantData($label, $res);

        $list['cpc'] = StatService::getValidAd($start, $end, $table, $agencyId, 0, 'clicks');
        $list['cpd'] = StatService::getValidAd($start, $end, $table, $agencyId, 0, 'conversions');
        $list['recharge'] = StatService::getValidRecharge($start, $end, $agencyId, 0);
        return $this->success(
            [
                'start' => $start,
                'end' =>$end
            ],
            null,
            $list
        );
    }

    /**
     * 获取广告排行
     *
     * | name | type | description | restra int | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | date_type | integer | 日期类 型 | 0:今日 1:昨日| 是 |
     * |  |  | | 2：本周 3:上周 4：本月 5：上月 6：累计|  |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint  | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | campaignid | integer | 广告计划id |  | 是 |
     * | app_name | string | 广告名称 |  | 是 |
     * | income | decimal | 收入 |  | 是 |
     * | show | integer | 展示量 |  | 是 |
     * | download | integer | 下载量 |  | 是 |
     * | rank | integer | 名次 |  | 否 |
     */
    public function rank(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date_type'=>'required|in:0,1,2,3,4,5,6',
        ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        //获取日期类型
        $type = $request->input('date_type');
        $time = $this->getRankTime($type);
        $agencyId = Auth::user()->agencyid;
        $table = 'data_hourly_daily';
        $row = StatService::findSaleRankData($time['start'], $time['end'], $table, $agencyId);
        $list = [];
        if (!empty($row)) {
            if (6 ==$type) {
                foreach ($row as $item) {
                    $item['ranking'] = 0;
                    $list[] = $item;
                }
            } else {
                $compare = StatService::findSaleRankData($time['startTime'], $time['endTime'], $table, $agencyId);
                $list = $this->calculateRank($row, $compare);
            }
        }

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (10 > $k) {
                    $data[$k]['profit'] = $v['sum_revenue'];
                    $data[$k]['campaign_name'] = $v['app_name'];
                    $data[$k]['view'] = $v['sum_views'];
                    $data[$k]['download'] = $v['sum_clicks'];
                    $data[$k]['ranking'] = $v['ranking'];
                } elseif (10 <= $k) {
                    if (isset($data['other'])) {
                        $data['other']['profit'] += $v['sum_revenue'];
                    } else {
                        $data['other']['campaign_name'] = '其他';
                        $data['other']['profit'] = $v['sum_revenue'];
                    }
                }
            }
        } else {
            $data = [];
        }
        if (!empty($data)) {
            $data = array_values($data);
        }
        return $this->success(null, null, $data);
    }
    
    /**
     * 计算排名
     * @param $rows
     * @param $timeArr
     * @return mixed
     */
    private function calRanking($rows, $timeArr)
    {
        //如果是今天，则取昨天的数据进行比较
        $newData = array();
        $data = $this->compare($timeArr['startTime'], $timeArr['endTime']);
        if (!empty($data)) {
            foreach ($data as $ke => $va) {
                $newData[] = $va['campaignid'];
            }
        }
    
        foreach ($rows as $k => $v) {
            //如果之前没有，则表示排名是上升
            if (!empty($newData)) {
                if (1 == $v['is_show']) {
                    if (in_array($v['campaignid'], $newData)) {
                        foreach ($newData as $key => $val) {
                            //如果存在
                            if ($v['campaignid'] == $val) {
                                if ($k == $key) {
                                    $rows[$k]['ranking'] = 0;
                                } elseif ($k < $key) {
                                    $rows[$k]['ranking'] = 1;
                                } else {
                                    $rows[$k]['ranking'] = -1;
                                }
                            }
                        }
                    } else {
                        $rows[$k]['ranking'] = 1;
                    }
                }
            } else {
                $rows[$k]['ranking']    =   1;
            }
        }
    
        return $rows;
    }

    /**
     *
     * @param $start
     * @param $end
     * @return mixed
     */
    private function compare($start, $end)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = DB::getTablePrefix();
        $res = DB::table('manager_statistics_ranking')
        ->select(
            'campaignid',
            DB::raw('SUM(' .$prefix . 'manager_statistics_ranking.income) as profit')
        )
        ->whereBetween('day_time', [$start, $end])
        ->groupBy('campaignid')
        ->orderBy('profit', 'desc')
        ->get();
        return $res;
    }

    /**
     * 根据传入的时间取得相应的数据
     * @param $start
     * @param $end
     * @return mixed
     */
    private function getData($start, $end)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = DB::getTablePrefix();
        $res = DB::table('manager_statistics_ranking')
        ->select(
            'campaignid',
            'campaign_name',
            'is_show',
            DB::raw('SUM(' .$prefix . 'manager_statistics_ranking.income) as profit'), //收入
            DB::raw('SUM(' .$prefix . 'manager_statistics_ranking.show) as view'), //展示量
            DB::raw('SUM(' .$prefix . 'manager_statistics_ranking.download) as download')//下载量
        )
        ->where('day_time', '<=', $end)
        ->where('day_time', '>=', $start)
        ->where('is_show', 1)
        ->groupBy('campaignid', 'revenue')
        ->orderBy('profit', 'desc')
        ->get();
        return $res;
    }
    /**
     * 查询平台媒体商-广告位报表，对应平台【统计报表】-【收入报表-媒体商】
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | zone_offset | string | 时区 | -8 | 是 |
     * | span | integer | 数据分组类型 | 1：hours 2：days 3：month| 是 |
     * | period_start | date | 起始时间 |  | 是 |
     * | period_end | date | 终止时间 |  | 是 |
     * | audit | integer |  | 0收入报表 - 媒体商  1审计收入 - 媒体商|  是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | sub name | sub name | type | description | restraint | required |
     * | :--: | :--: | | :--: | :--: | :--------: | :-------: | :-----: |
     * | | mode |  |  | string | 0 媒体下载 |  | |
     * | |  |  |  |  | 1 程序化投放（入库） |  | |
     * | |  |  |  |  | 2 人工投放 |  | |
     * | |  |  |  |  | 3 程序化投放（不入库） |  | |
     * | | ecpm |  |  | decimal | eCPM |  | 是 |
     * | | aff_list |  |  | array | json对象数组 |  | 是 |
     * | | sum_download_complete |  |  | integer | 下载完成(监控) |  | 是 |
     * | |  |  | sum_download_requests | integer | 下载请求(监控) |  | |
     * | | sum_download_requests |  |  | integer | 下载请求(监控) |  | 是 |
     * | | ctr |  |  | decimal | 下载转化率 |  | 是 |
     * | |  |  | sum_clicks | integer | 下载量 |  | 是 |
     * | |  sum_clicks |  |  | integer | 下载量(上报) |  | 是 |
     * | statChart |  |  |  | array | 图表数据 |  | |
     * | | affiliateid |  |  | integer | 媒体商id |  | 是 |
     * | |  |  | affiliateid | integer | 媒体商id |  | 是 |
     * | | brief_name |  |  | string | 媒体商简称 |  | 是 |
     * | |  |  | brief_name | string | 媒体商简称 |  | 是 |
     * | | brief_name |  |  | string |  媒体商简称 |  | 是 |
     * | | sum_revenue |  |  | decimal | 媒体支出(充值金) |  | 是 |
     * | | sum_revenue_gift |  |  | decimal | 媒体支出(赠送金) |  | 是 |
     * | |  |  | sum_views | integer | 展示量 |  | 是 |
     * | | sum_views |  |  | integer | 展示量 |  | 是 |
     * | | media_cpd |  |  | decimal | 平均单价(媒体商) |  | 是 |
     * | | cpd |  |  | decimal | 平均单价(广告主) |  | 是 |
     * | | sum_payment |  |  | decimal | 广告主消耗(充值金) |  | 是 |
     * | | sum_payment_gift |  |  | decimal | 广告主消耗(赠送金) |  | 是 |
     * | |  |  | sum_consum | decimal | 广告主结算金额 |  | |
     * | | sum_consum |  |  | decimal | 广告主结算金额 |  | 是 |
     * | |  | zoneid |  | integer | 广告位id |  | 是 |
     * | |  |  | zoneid | integer | 广告位id |  | 是 |
     * | |  | zone_name |  | string | 广告位名称 |  | 是 |
     * | |  |  | zonename | string | 广告位名称 |  | 是 |
     * | |  |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  |  | time | date | 时间 |  | 是 |
     * | | cpc_ctr |  |  | decimal | 点击转化率 |  | 是 |
     * | |  |  | sum_cpc_clicks | integer | 点击量 |  | |
     * | | sum_cpc_clicks |  |  | integer | 点击量 |  | 是 |
     * | statData |  |  |  | array | 表格数据 |  | 是 |
     * | |  |  | sum_cpa | integer | 转化量 |  | |
     * | | sum_cpa |  |  | integer | 转化量 |  | 是 |
     * | |  | zone_list |  |  |  |  | |
     * | | affiliateid |  |  | integer |  |  | 是 |
     */
    public function zone(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required|in:3,1,2',
                'zone_offset' => 'required',
                'audit' => 'required|in:0,1',
        ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
    
        $audit = Input::get('audit');
        $param = $this->transFormInput($request);
        $agencyId = Auth::user()->agencyid;
        if ($audit  == 1) {
            $param['period_start'] = Input::get('period_start');
            $param['period_end'] = Input::get('period_end');
            if ($param['axis'] == 'hours') {
                $param['axis'] = 'days';
            }
        }
        $statsCharts = [];
        //权限不同查询的数据不同
        $affArr = [];
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        if ($this->can('manager-pub-all')) {
            $affArr = DB::table('affiliates')
                ->where('agencyid', $agencyId)
                ->select('affiliateid')
                ->get();
        } elseif ($this->can('manager-pub-self')) {
            $userId = Auth::user()->user_id;
            $affArr = DB::table('affiliates')
                ->where('creator_uid', $userId)
                ->where('agencyid', $agencyId)
                ->select('affiliateid')
                ->get();

        } else {
            return $this->errorCode(5020);
        }
        $affiliateArr =[];
        foreach ($affArr as $key => $val) {
            $affiliateArr[] = $val['affiliateid'];
        }
        $statsCharts = StatService::findManagerZoneStat(
            $param['period_start'],
            $param['period_end'],
            $param['axis'],
            $param['zoneOffset'],
            $agencyId,
            $audit
        );
        $eChart = [];
        $eData = [];
        $aff = DB::table('affiliates')->get();
        $affiliateIdName = ArrayHelper::map(
            $aff,
            'affiliateid',
            'brief_name'
        );
        $affiliateIdFullName = ArrayHelper::map(
            $aff,
            'affiliateid',
            'name'
        );
        $affiliateMode = ArrayHelper::map(
            $aff,
            'affiliateid',
            'mode'
        );
        foreach ($statsCharts as $val) {
            //报表数据按照媒体商进行汇总
            $val['brief_name'] = $affiliateIdName[$val['affiliateid']];
            $val['name'] = $affiliateIdFullName[$val['affiliateid']];
            $val['mode'] = $affiliateMode[$val['affiliateid']];
            if (isset($eData[$val['affiliateid']])) {
                foreach ($this->fieldAll as $label) {
                    $eData[$val['affiliateid']][$label] += $val[$label];
                }
            } else {
                if (in_array($val['affiliateid'], $affiliateArr)) {
                    $eData[$val['affiliateid']] = $val;
                }
            }
            if (isset($eData[$val['affiliateid']])) {
                $eData[$val['affiliateid']]['ctr'] = StatService::getCtr(
                    $eData[$val['affiliateid']]['sum_views'],
                    $eData[$val['affiliateid']]['sum_clicks']
                ); //有权限
                $eData[$val['affiliateid']]['cpc_ctr'] = StatService::getCtr(
                    $eData[$val['affiliateid']]['sum_views'],
                    $eData[$val['affiliateid']]['sum_cpc_clicks']
                ); //有权限
                //平均单价广告主
                $eData[$val['affiliateid']]['cpd'] = StatService::getCpd(
                    $eData[$val['affiliateid']]['sum_revenue'] + $eData[$val['affiliateid']]['sum_revenue_gift'],
                    $eData[$val['affiliateid']]['sum_clicks'] + $eData[$val['affiliateid']]['sum_cpc_clicks'] +
                    $eData[$val['affiliateid']]['sum_cpa']
                );
                //平均单价媒体商
                $eData[$val['affiliateid']]['media_cpd'] = StatService::getCpd(
                    $eData[$val['affiliateid']]['sum_payment'] + $eData[$val['affiliateid']]['sum_payment_gift'],
                    $eData[$val['affiliateid']]['sum_clicks'] + $eData[$val['affiliateid']]['sum_cpc_clicks'] +
                    $eData[$val['affiliateid']]['sum_cpa']
                );
                $eData[$val['affiliateid']]['ecpm'] = StatService::getEcpm(
                    $eData[$val['affiliateid']]['sum_revenue'] + $eData[$val['affiliateid']]['sum_revenue_gift'],
                    $eData[$val['affiliateid']]['sum_views']
                );//有权限
            }

            //第一步图表数据按照媒体商进行汇总
            if (!isset($eChart[$val['affiliateid']])) {
                if (in_array($val['affiliateid'], $affiliateArr)) {
                    $eChart[$val['affiliateid']] = [
                        'affiliateid' => $val['affiliateid'],
                        'brief_name' => $val['brief_name'],
                        'name' => $val['name'],
                        'mode' => $val['mode'],
                    ];
                }
            }
            //第二步图表数据按照广告位进行汇总
            if (isset($eChart[$val['affiliateid']])) {
                if (!isset($eChart[$val['affiliateid']]['aff_list'][$val['zoneid']])) {
                    $eChart[$val['affiliateid']]['aff_list'][$val['zoneid']] = [
                        'zoneid' => $val['zoneid'],
                        'zonename' => $val['zonename'],
                    ];
                }
            }
            if (isset($eChart[$val['affiliateid']]['aff_list'][$val['zoneid']])) {
                $eChart[$val['affiliateid']]['aff_list'][$val['zoneid']]['zone_list'][] = $val;
            }
            unset($eData[$val['affiliateid']]['zonename']);
            unset($eData[$val['affiliateid']]['media_revenue_type']);
            unset($eData[$val['affiliateid']]['client_revenue_type']);
            unset($eData[$val['affiliateid']]['business_type']);
        }

        $eChart = array_values($eChart);
        $eData = array_values($eData);
        $list['statChart'] = $eChart;
        $list['statData'] = $eData;
        return $this->success(null, null, $list);
    }
    /**
     * 查询平台媒体商-广告位对应的campaign数据，
     * 对应平台【统计报表】-【收入报表-媒体商】-选择媒体商-选择广告
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | audit | integer |  | 0收入报表 - 媒体商 1审计收入 - 媒体商  | 是 |
     * | affiliateid | integer | 媒体商ID |  | |
     * | period_start | date | 起始时间 |  | 是 |
     * | period_end | date | 终止时间 |  | 是 |
     * | span | integer | 数据分组类型 | 1：hours 2：days 3：month | 是 |
     * | zone_offset | string | 时区 | -8 | 是
     * @param Request $request
     * @return \Illuminate\Http\Response
     * name | sub name | sub name | type | description | restraint | restraint |
     * | :--: | :--: | :--: |:--------: | :-------: | :-----: |
     * | statChart |  |  |  |  |  | |
     * | |  zoneid |  |  | 广告位id |  | 是 |
     * | | ad_list |  | array |  |  | |
     * | |  | affiliateid | integer | 媒体商id |  | 是 |
     * | |  | brief_name | string | 媒体商简称 |  | 是 |
     * | |  | zoneid | integer | 广告位id |  | 是 |
     * | |  | zonename | string | 广告位名称 |  | 是 |
     * | |  | bannerid |  | 广告id |  | |
     * | |  | app_name | string | 广告名称 |  | 是 |
     * | |  | sum_views | integer | 展示量 |  | |
     * | |  | sum_clicks | integer | 下载量 |  | |
     * | |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  | sum_cpc_clicks | integer | 点击量 |  | |
     * | |  | sum_download_requests | integer | 下载请求(监控) |  | |
     * | |  | sum_download_complete | integer |  下载完成(监控) |  | |
     * | |  | sum_cpa | integer | 转化量 |  | |
     * | |  | sum_consum | decimal | 广告主结算金额 |  | |
     * | |  | time | date | 时间 |  | 是 |
     * | statData |  |  | array | 表格数据 |  | |
     * | | zonename |  |  string | 广告位名称 |  | 是 |
     * | | ad_type |  |  integer | 广告位类别 |  | 是 |
     * | | platform |  |  integer | 所属平台 |  | 是 |
     * | |  sum_views |  |  integer | 展示量 |  | 是 |
     * | | sum_cpc_clicks |  |  integer |  点击量 |  | 是 |
     * | | sum_download_requests |  |  integer |  下载请求(监控) |  | 是 |
     * | | sum_download_complete |  |  integer |  下载完成(监控) |  | 是 |
     * | |  sum_clicks |  |   integer |  下载量(上报) |  | 是 |
     * | | ctr |  |  decimal |  下载转化率 |  | 是 |
     * | | cpc_ctr |  |   decimal |  点击转化率 |  | 是 |
     * | | sum_payment |  |   decimal |  广告主消耗(充值金) |  | 是 |
     * | | sum_payment_gift |  |   decimal |  广告主消耗(赠送金) |  | 是 |
     * | | sum_revenue |  |   decimal |  媒体支出(充值金) |  | 是 |
     * | | sum_revenue_gift |  |   decimal |  媒体支出(赠送金) |  | 是 |
     * | | cpd |  |   decimal | 平均单价(广告主) |  | 是 |
     * | | media_cpd |  |   decimal | 平均单价(媒体商) |  | 是 |
     * | | ecpm |  |   decimal | eCPM |  | 是 |
     * | | sum_cpa |  |  integer | 转化量 |  | 是 |
     * | | sum_consum |  |   decimal | 广告主结算金额 |  | 是 |
     * | | child |  |  |  |  | |
     * | |  | bannerid | string | 广告id |  | |
     * | |  | product_name |  string | 产品名称 |  | 是 |
     * | |  | product_type |   integer | 产品类型 |  | 是 |
     * | |  | app_name |   string | 广告名称 |  | 是 |
     * | |  | ad_type |    integer | 广告类型 |  | 是 |
     * | |  |  sum_views |    integer | 展示量 |  | 是 |
     * | |  | sum_cpc_clicks |    integer |  点击量 |  | 是 |
     * | |  | sum_download_requests |    integer |  下载请求(监控) |  | 是 |
     * | |  | sum_download_complete |    integer |  下载完成(监控) |  | 是 |
     * | |  |  sum_clicks |   integer |  下载量(上报) |  | 是 |
     * | |  | ctr |   decimal |  下载转化率 |  | 是 |
     * | |  | cpc_ctr |   decimal |  点击转化率 |  | 是 |
     * | |  | sum_payment |   decimal |  广告主消耗(充值金) |  | 是 |
     * | |  | sum_payment_gift |   decimal |  广告主消耗(赠送金) |  | 是 |
     * | |  | sum_revenue |   decimal |  媒体支出(充值金) |  | 是 |
     * | |  | sum_revenue_gift |   decimal |  媒体支出(赠送金) |  | 是 |
     * | |  | cpd |   decimal | 平均单价(广告主) |  | 是 |
     * | |  | media_cpd |   decimal | 平均单价(媒体商) |  | 是 |
     * | |  | ecpm |  decimal | eCPM |  | 是 |
     * | |  | sum_cpa |  integer | 转化量 |  | 是 |
     * | |  | sum_consum | decimal | 广告主结算金额 |  | 是 |
     */
    public function zoneAffiliate(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required|in:3,1,2',
                'zone_offset' => 'required',
                'audit' => 'required|in:0,1',
                'affiliateid' => 'required'
        ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
    
        $audit = Input::get('audit');
        $affiliateId = Input::get('affiliateid');
        $param = $this->transFormInput($request);
        $statsCharts = [];
        //根据权限不同查询的数据不同
        if ($this->can('manager-pub-all')) {
            $statsCharts = StatService::findManagerZoneCampaignStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                Auth::user()->account->agency->agencyid,
                $affiliateId,
                $audit
            );
        } elseif ($this->can('manager-pub-self')) {
            $statsCharts = StatService::findManagerZoneCampaignStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                Auth::user()->account->agency->agencyid,
                $affiliateId,
                $audit,
                Auth::user()->user_id
            );
        } else {
            return $this->errorCode(5020);
        }
        $eChart = [];
        $eData = [];
        foreach ($statsCharts as $val) {
            //图表书
            $eChart[$val['zoneid']][] = $val;
            //报表的数据-广告位的维度
            if (isset($eData[$val['zoneid']])) {
                foreach ($this->fieldAll as $label) {
                    $eData[$val['zoneid']][$label] += $val[$label];
                }
            } else {
                $eData[$val['zoneid']] = $val;
            }
            if (($eData[$val['zoneid']]['zone_type'] == Campaign::AD_TYPE_APP_STORE) &&
                ($eData[$val['zoneid']]['platform'] == Campaign::PLATFORM_IOS_COPYRIGHT)) {
                $eData[$val['zoneid']]['platform'] = Campaign::PLATFORM_IOS;
            }
            $eData[$val['zoneid']]['ctr'] = StatService::getCtr(
                $eData[$val['zoneid']]['sum_views'],
                $eData[$val['zoneid']]['sum_clicks']
            );
            $eData[$val['zoneid']]['cpc_ctr'] = StatService::getCtr(
                $eData[$val['zoneid']]['sum_views'],
                $eData[$val['zoneid']]['sum_cpc_clicks']
            );
            //平均单价广告主
            $eData[$val['zoneid']]['cpd'] = StatService::getCpd(
                $eData[$val['zoneid']]['sum_revenue'] + $eData[$val['zoneid']]['sum_revenue_gift'],
                $eData[$val['zoneid']]['sum_clicks'] + $eData[$val['zoneid']]['sum_cpc_clicks'] +
                $eData[$val['zoneid']]['sum_cpa']
            );
            //平均单价媒体商
            $eData[$val['zoneid']]['media_cpd'] = StatService::getCpd(
                $eData[$val['zoneid']]['sum_payment'] + $eData[$val['zoneid']]['sum_payment_gift'],
                $eData[$val['zoneid']]['sum_clicks']+ $eData[$val['zoneid']]['sum_cpc_clicks'] +
                $eData[$val['zoneid']]['sum_cpa']
            );
            $eData[$val['zoneid']]['ecpm'] = StatService::getEcpm(
                $eData[$val['zoneid']]['sum_revenue'] + $eData[$val['zoneid']]['sum_revenue_gift'],
                $eData[$val['zoneid']]['sum_views']
            );
            //报表的数据-广告的维度
            if (isset($eData[$val['zoneid']]['child'][$val['bannerid']])) {
                foreach ($this->fieldAll as $label) {
                    $eData[$val['zoneid']]['child'][$val['bannerid']][$label] += $val[$label];
                }
            } else {
                $eData[$val['zoneid']]['child'][$val['bannerid']] = $val;
            }
            $eData[$val['zoneid']]['child'][$val['bannerid']]['ctr'] = StatService::getCtr(
                $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_views'],
                $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_clicks']
            );
            $eData[$val['zoneid']]['child'][$val['bannerid']]['cpc_ctr'] = StatService::getCtr(
                $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_views'],
                $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_cpc_clicks']
            );
            if ($eData[$val['zoneid']]['child'][$val['bannerid']]['client_revenue_type'] ==
                Campaign::REVENUE_TYPE_CPD) {
                $eData[$val['zoneid']]['child'][$val['bannerid']]['cpd'] = StatService::getCpd(
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_revenue'] +
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_revenue_gift'],
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_clicks']
                );
            } elseif ($eData[$val['zoneid']]['child'][$val['bannerid']]['client_revenue_type'] ==
                Campaign::REVENUE_TYPE_CPC) {
                $eData[$val['zoneid']]['child'][$val['bannerid']]['cpd'] = StatService::getCpd(
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_revenue'] +
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_revenue_gift'],
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_cpc_clicks']
                );
            }
            if ($eData[$val['zoneid']]['child'][$val['bannerid']]['media_revenue_type'] ==
                Campaign::REVENUE_TYPE_CPD) {
                $eData[$val['zoneid']]['child'][$val['bannerid']]['media_cpd'] = StatService::getCpd(
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_payment'] +
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_payment_gift'],
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_clicks']
                );
            } elseif ($eData[$val['zoneid']]['child'][$val['bannerid']]['media_revenue_type'] ==
                Campaign::REVENUE_TYPE_CPC) {
                $eData[$val['zoneid']]['child'][$val['bannerid']]['media_cpd'] = StatService::getCpd(
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_payment'] +
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_payment_gift'],
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_cpc_clicks']
                ) ;
            } elseif ($eData[$val['zoneid']]['child'][$val['bannerid']]['media_revenue_type'] ==
                Campaign::REVENUE_TYPE_CPA) {
                $eData[$val['zoneid']]['child'][$val['bannerid']]['media_cpd'] = StatService::getCpd(
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_payment'] +
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_payment_gift'],
                    $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_cpa']
                );
            }
            $eData[$val['zoneid']]['child'][$val['bannerid']]['ecpm'] = StatService::getEcpm(
                $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_revenue'] +
                $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_revenue_gift'],
                $eData[$val['zoneid']]['child'][$val['bannerid']]['sum_views']
            );
            unset($eData[$val['zoneid']]['child'][$val['bannerid']]['brief_name']);
            unset($eData[$val['zoneid']]['child'][$val['bannerid']]['zone_type']);
            unset($eData[$val['zoneid']]['child'][$val['bannerid']]['platform']);
            unset($eData[$val['zoneid']]['child'][$val['bannerid']]['zonename']);
        }
        if (!empty($eData)) {
            foreach ($eData as $v) {
                unset($eData[$v['zoneid']]['brief_name']);
                unset($eData[$v['zoneid']]['product_name']);
                unset($eData[$v['zoneid']]['app_name']);
                unset($eData[$v['zoneid']]['ad_type']);
                unset($eData[$v['zoneid']]['channel']);
                unset($eData[$v['zoneid']]['product_type']);
                unset($eData[$v['zoneid']]['client_revenue_type']);
                unset($eData[$v['zoneid']]['media_revenue_type']);
                unset($eData[$v['zoneid']]['business_type']);
            }
        }
        $eData = array_values($eData);
        $list['statChart'] = $eChart;
        $list['statData'] = $eData;
    
        return $this->success(null, null, $list);
    }
    
    /**
     * 查询导出平台媒体商-收入报表，对应平台【统计报表】-【导出报表】
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | zoneOffset | string | 时区 |  | 是 |
     * | audit | int | 0平台 1审计|  | 是 |
     * | affiliateid |  | 媒体商id |  | 是 |
     * | bannerid |  | 广告id |  | 是 |
     * | media_revenue_type |  | 媒體計費方式 |  | 是 |
     * | client_revenue_type |  | 广告計費方式 |  | 是 |
     * | business_type |  | 业务类型 |  | 是 |
     * @param Request $request |
     * @return \Illuminate\Http\Response
     */
    public function zoneExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'zone_offset' => 'required',
                'audit' => 'required|in:0,1',
        ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $audit = Input::get('audit');
        $affiliateId = Input::get('affiliateid');
        $bannerId = Input::get('bannnerid');
        $media_revenue_type = Input::get('media_revenue_type');
        $client_revenue_type = Input::get('client_revenue_type');
        $business_type = Input::get('business_type');
        $param = $this->transFormInput($request);
        $param['axis'] = 'days';
        $statsCharts = [];
        //根据权限不同查询的数据不同
        if ($this->can('manager-pub-all')) {
            $statsCharts = StatService::findManagerZoneExcelStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                Auth::user()->account->agency->agencyid,
                $affiliateId,
                $bannerId,
                $media_revenue_type,
                $client_revenue_type,
                $business_type,
                $audit
            );
        } elseif ($this->can('manager-pub-self')) {
            $statsCharts = StatService::findManagerZoneExcelStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                Auth::user()->account->agency->agencyid,
                $affiliateId,
                $bannerId,
                $media_revenue_type,
                $client_revenue_type,
                $audit,
                Auth::user()->user_id
            );
        } else {
            return $this->errorCode(5020);
        }
        $data = [];
        $i = 0;

        $column = [
            'name' => '媒体商全称',
            'affiliatesname' => '媒体商简称',
            'zone_type' => '广告位类别',
            'zonename' => '广告位',
            'platform' => '所属平台',
            'product_name' => '推广产品',
            'contact_name' => '销售负责人',
            'type' => '推广类型',
            'app_name' => '广告名称',
            'business_type' => '业务类型',
            'ad_type' => '广告类型',
            'channel' => '渠道号',
        ];
        $column = array_merge($column, $this->showName);
        foreach ($column as $key => $val) {
            if (in_array($key, $this->fieldControl)) {
                if ($this->can('manager-'.$key)) {
                    $sheetRow[$key] = $val;
                }
            } else {
                $sheetRow[$key] = $val;
            }
        }
        $sum_data = $this->sumData;
        foreach ($statsCharts as $row) {
            $data[$i][] = $row['name'];
            $data[$i][] = $row['affiliatesname'];
            $data[$i][] = Zone::getAdTypeLabels($row['zone_type']);
            $data[$i][] = $row['zonename'];
            if (($row['ad_type'] == Campaign::AD_TYPE_APP_STORE) &&
                ($row['platform'] == Campaign::PLATFORM_IOS_COPYRIGHT)) {
                $data[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
            } else {
                $data[$i][] = Campaign::getPlatformLabels($row['platform']);
            }
            $data[$i][] = $row['product_name'];
            $data[$i][] = $row['contact_name'];
            $data[$i][] = Product::getTypeLabels($row['type']);
            $data[$i][] = $row['app_name'];
            $data[$i][] = Campaign::getBusinessType($row['business_type']);
            $data[$i][] = Campaign::getAdTypeLabels($row['ad_type']);
            $data[$i][] = $row['channel'];

            if ($this->can('manager-sum_views')) {
                $data[$i][] = $row['sum_views']; //有权限
                $sum_data['sum_views'] += $row['sum_views'];
            }

            if ($this->can('manager-sum_cpc_clicks')) {
                $data[$i][] = $row['sum_cpc_clicks']; //有权限
                $sum_data['sum_cpc_clicks'] += $row['sum_cpc_clicks'];
            }
            if ($this->can('manager-sum_download_requests')) {
                $data[$i][] = $row['sum_download_requests']; //有权限
                $sum_data['sum_download_requests'] += $row['sum_download_requests'];
            }
            if ($this->can('manager-sum_download_complete')) {
                $data[$i][] = $row['sum_download_complete']; //有权限
                $sum_data['sum_download_complete'] += $row['sum_download_complete'];
            }

            if ($this->can('manager-sum_clicks')) {
                $data[$i][] = $row['sum_clicks']; //有权限
                $sum_data['sum_clicks'] += $row['sum_clicks'];
            }
            if ($this->can('manager-sum_cpa')) {
                $data[$i][] = $row['sum_cpa']; //有权限
                $sum_data['sum_cpa'] += $row['sum_cpa'];
            }
            if ($this->can('manager-ctr')) {
                $data[$i][] = StatService::getCtr($row['sum_views'], $row['sum_clicks']); //有权限
            }
            if ($this->can('manager-cpc_ctr')) {
                $data[$i][] = StatService::getCtr($row['sum_views'], $row['sum_cpc_clicks']); //有权限
            }
            //广告主消耗总数
            if ($this->can('manager-sum_revenue_client')) {
                $data[$i][] = Formatter::asDecimal($row['sum_revenue'] + $row['sum_revenue_gift']);
                $sum_data['sum_revenue_client'] += $row['sum_revenue'] + $row['sum_revenue_gift'];
            }
            if ($this->can('manager-sum_revenue')) {
                $data[$i][] = Formatter::asDecimal($row['sum_revenue']);
                $sum_data['sum_revenue'] += $row['sum_revenue'];
            }
            if ($this->can('manager-sum_revenue_gift')) {
                $data[$i][] = Formatter::asDecimal($row['sum_revenue_gift']);
                $sum_data['sum_revenue_gift'] += $row['sum_revenue_gift'];
            }

            $data[$i][] = Campaign::getRevenueTypeLabels($row['client_revenue_type']); // 计费方式广告主
            //媒体商支出总数
            if ($this->can('manager-sum_payment_trafficker')) {
                $data[$i][] = Formatter::asDecimal($row['sum_payment'] + $row['sum_payment_gift']); //有权限支出
                $sum_data['sum_payment_trafficker'] += $row['sum_payment'] + $row['sum_payment_gift'];
            }
            if ($this->can('manager-sum_payment')) {
                $data[$i][] = Formatter::asDecimal($row['sum_payment']); //有权限支出
                $sum_data['sum_payment'] += $row['sum_payment'];
            }
            if ($this->can('manager-sum_payment_gift')) {
                $data[$i][] = Formatter::asDecimal($row['sum_payment_gift']); //有权限支出
                $sum_data['sum_payment_gift'] += $row['sum_payment_gift'];
            }

            $data[$i][] = Campaign::getRevenueTypeLabels($row['media_revenue_type']); // 计费方式媒体商
            if ($this->can('manager-cpd')) {
                if ($row['client_revenue_type'] == Campaign::REVENUE_TYPE_CPD) {//有权限
                    $data[$i][] = StatService::getCpd(
                        $row['sum_revenue'] + $row['sum_revenue_gift'],
                        $row['sum_clicks']
                    );
                } elseif ($row['client_revenue_type'] == Campaign::REVENUE_TYPE_CPC) {
                    $data[$i][] = StatService::getCpd(
                        $row['sum_revenue'] + $row['sum_revenue_gift'],
                        $row['sum_cpc_clicks']
                    );
                } elseif ($row['client_revenue_type'] == Campaign::REVENUE_TYPE_CPA) {
                    $data[$i][] = StatService::getCpd(
                        $row['sum_revenue'] + $row['sum_revenue_gift'],
                        $row['sum_cpa']
                    );
                } elseif ($row['client_revenue_type'] == Campaign::REVENUE_TYPE_CPM) {
                    $data[$i][] = $row['revenue'];
                } else {
                    $data[$i][] = 0;
                }
            }
            if ($this->can('manager-media_cpd')) {
                if ($row['media_revenue_type'] ==  Campaign::REVENUE_TYPE_CPD) {//有权限
                    $data[$i][] = StatService::getCpd(
                        $row['sum_payment'] + $row['sum_payment_gift'],
                        $row['sum_clicks']
                    );
                } elseif ($row['media_revenue_type'] == Campaign::REVENUE_TYPE_CPC) {
                    $data[$i][] = StatService::getCpd(
                        $row['sum_payment'] + $row['sum_payment_gift'],
                        $row['sum_cpc_clicks']
                    );
                } elseif ($row['media_revenue_type'] == Campaign::REVENUE_TYPE_CPA) {
                    $data[$i][] = StatService::getCpd(
                        $row['sum_payment'] + $row['sum_payment_gift'],
                        $row['sum_cpa']
                    );
                } elseif ($row['media_revenue_type'] == Campaign::REVENUE_TYPE_CPM) {
                    $data[$i][] = StatService::getEcpm(
                        $row['sum_payment'] + $row['sum_payment_gift'],
                        $row['sum_views']
                    );
                } else {
                    $data[$i][] = 0;
                }
            }
            if ($this->can('manager-ecpm')) {
                $data[$i][] = StatService::getEcpm(
                    $row['sum_revenue'] + $row['sum_revenue_gift'],
                    $row['sum_views']
                );//有权限
            }
            if ($this->can('manager-profit')) {
                $data[$i][] = Formatter::asDecimal(
                    $row['sum_revenue'] - $row['sum_payment'] - $row['sum_payment_gift']
                );
                $sum_data['profit'] += $row['sum_revenue'] - $row['sum_payment'] - $row['sum_payment_gift'];
            }
            if ($this->can('manager-profit_rate')) {
                $data[$i][] = Formatter::asDecimal(
                    floatval($row['sum_revenue']) > 0 ? ($row['sum_revenue'] - $row['sum_payment'] -
                            $row['sum_payment_gift']) / $row['sum_revenue'] * 100 : 0
                ) . '%';
            }
            $i++;
        }
        $excelName = '媒体商报表-'. str_replace('-', '', $param['period_start']) . '_' .
                str_replace('-', '', $param['period_end']);
        $sum_base = [
                '汇总',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
        ];
        $sum_arr = $this->getSummaryData($sum_data, $sum_base);
        
        array_push($data, $sum_arr);
        StatService::downloadCsv($excelName, $sheetRow, $data) ;
    }
    //@codeCoverageIgnoreEnd
    /**
     * 查询导出平台媒体商-收入报表，对应平台【统计报表】-【导出每日报表】
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | zoneOffset | string | 时区 |  | 是 |
     * | audit | int | 0平台 1审计|  | 是 |
     * | affiliateid |  | 媒体商id |  | 是 |
     * | bannerid |  | 广告id |  | 是 |
     * | media_revenue_type |  | 媒體計費方式 |  | 是 |
     * | client_revenue_type |  | 广告計費方式 |  | 是 |
     * | business_type |  | 业务类型 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function zoneDailyExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'zone_offset' => 'required',
                'audit' => 'required|in:0,1',
        ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $audit = Input::get('audit');
        $affiliateId = Input::get('affiliateid');
        $bannerId = Input::get('bannnerid');
        $media_revenue_type = Input::get('media_revenue_type');
        $client_revenue_type = Input::get('client_revenue_type');
        $business_type = Input::get('business_type');
        $param = $this->transFormInput($request);
        $param['axis'] = 'days';
        $column = [
                'name' => '媒体商全称',
                'affiliatesname' => '媒体商简称',
                'zone_type' => '广告位类别',
                'zonename' => '广告位',
                'platform' => '所属平台',
                'product_name' => '推广产品',
                'contact_name' => '销售负责人',
                'type' => '推广类型',
                'app_name' => '广告名称',
                'business_type' => '业务类型',
                'ad_type' => '广告类型',
                'channel' => '渠道号',
                'show_date' => '日期',
        ];
        $column = array_merge($column, $this->showName);
        //表头权限控制
        foreach ($column as $key => $val) {
            if (in_array($key, $this->fieldControl)) {
                if ($this->can('manager-'.$key)) {
                    $sheetRow[$key] = $val;
                }
            } else {
                $sheetRow[$key] = $val;
            }
        }
        $start = Input::get('period_start');
        $end = Input::get('period_end');
        $agencyId = Auth::user()->account->agency->agencyid;
        $conditions = array(
            $audit,
            $param['zoneOffset'],
            $affiliateId,
            $bannerId,
            $media_revenue_type,
            $client_revenue_type,
            $business_type,
            $agencyId,
            $param['axis']
         );
        $files = self::generateDailyReportFileName(
            Auth::user()->user_id,
            'affiliate',
            $start,
            $end,
            $sheetRow,
            $conditions,
            $audit,
            $start,
            $end,
            $column
        );
        $file = $files[0];
        $fileShowName = $files[1];
        $headers = $files[2];
        if (file_exists($file)) {
            return response()->download($file, $fileShowName, $headers);
            exit;
        }
        //根据权限不同查询的数据不同
        $statsCharts = [];
        if ($this->can('manager-pub-all')) {
            $statsCharts = StatService::findManagerZoneExcelStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                $agencyId,
                $affiliateId,
                $bannerId,
                $media_revenue_type,
                $client_revenue_type,
                $business_type,
                $audit,
                0,
                'affiliate',
                true
            );
        } elseif ($this->can('manager-pub-self')) {
            $statsCharts = StatService::findManagerZoneExcelStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                Auth::user()->account->agency->agencyid,
                $affiliateId,
                $bannerId,
                $media_revenue_type,
                $client_revenue_type,
                $business_type,
                $audit,
                Auth::user()->user_id,
                'affiliate',
                true
            );
        } else {
            return $this->errorCode(5020);
        }
        $exportData = [];
        $i = 0;
        if (!empty($statsCharts)) {
            foreach ($statsCharts as $k => $v) {
                if (empty($baseData[$v['zoneid']][$v['bannerid']]['info'])) {
                    $exportData[$v['zoneid']][$v['bannerid']]['info'] = $v;
                }
                $exportData[$v['zoneid']][$v['bannerid']]['days'][$v['time']] = [
                        "clientname" =>$v["clientname"],
                        "product_name" =>$v["product_name"],
                        "business_type" =>$v["business_type"],
                        "type" =>$v["type"],
                        "app_name" =>$v["app_name"],
                        "contact_name" =>$v["contact_name"],
                        "ad_type" =>$v["ad_type"],
                        "channel" =>$v["channel"],
                        "platform" =>$v["platform"],
                        "zonename" =>$v["zonename"],
                        "name" =>$v["name"],
                        "affiliatesname" =>$v["affiliatesname"],
                        "zone_type" =>$v["zone_type"],
                        "media_revenue_type" =>$v["media_revenue_type"],
                        "client_revenue_type" =>$v["client_revenue_type"],
                        "sum_views" =>$v['sum_views'],
                        "sum_clicks" => $v['sum_clicks'],
                        "sum_cpc_clicks" =>$v['sum_cpc_clicks'],
                        "sum_download_requests" => $v['sum_download_requests'],
                        "sum_download_complete" => $v['sum_download_complete'],
                        "sum_cpa" => $v['sum_cpa'],
                        "sum_consum" => $v['sum_consum'],
                        "sum_payment" => $v['sum_payment'],
                        "sum_payment_gift" => $v['sum_payment_gift'],
                        "sum_revenue" => $v['sum_revenue'],
                        "sum_revenue_gift" => $v['sum_revenue_gift'],
                ];
            }
        }
        $eData = $exportData;
        $dayCount = (strtotime($end) - strtotime($start)) / 86400;
        if (sizeof($exportData) > 0) {
            foreach ($exportData as $k => $v) {
                foreach ($v as $ke => $va) {
                    $_start = $start;
                    for ($i = 0; $i <= $dayCount; $i++) {
                        $eData[$k][$ke]['days'] = array_add($eData[$k][$ke]['days'], $_start, array(
                                "sum_views" => 0,
                                "sum_clicks" => 0,
                                "sum_cpc_clicks" => 0,
                                "sum_download_requests" => 0,
                                "sum_download_complete" => 0,
                                "sum_cpa" => 0,
                                "sum_consum" => 0,
                                "sum_payment" => 0,
                                "sum_payment_gift" => 0,
                                "sum_revenue" => 0,
                                "sum_revenue_gift" => 0,
                                "ecpm" => 0
                        ));
                        $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
                    }
                }
            }
        }
        $data = [];
        $sum_data = $this->sumData;
        foreach ($eData as $k_zone => $v_zone) {
            foreach ($v_zone as $k_banner => $v_banner) {
                foreach ($v_banner ['days'] as $k_day => $item) {
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['name'];
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['affiliatesname'];
                    $data[$i][] = Zone::getAdTypeLabels($eData[$k_zone][$k_banner]['info']['zone_type']);
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['zonename'];
                    //$data[$i][] = Campaign::getPlatformLabels($eData[$k_zone][$k_banner]['info']['platform']);
                    if (($eData[$k_zone][$k_banner]['info']['zone_type'] == Campaign::AD_TYPE_APP_STORE) &&
                        ($eData[$k_zone][$k_banner]['info']['platform'] == Campaign::PLATFORM_IOS_COPYRIGHT)) {
                        $data[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                    } else {
                        $data[$i][] = Campaign::getPlatformLabels($eData[$k_zone][$k_banner]['info']['platform']);
                    }
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['product_name'];
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['contact_name'];
                    $data[$i][] = Product::getTypeLabels($eData[$k_zone][$k_banner]['info']['type']);
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['app_name'];
                    $data[$i][] = Campaign::getBusinessType($eData[$k_zone][$k_banner]['info']['business_type']);
                    $data[$i][] = Campaign::getAdTypeLabels($eData[$k_zone][$k_banner]['info']['ad_type']);
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['channel'];
                    $data[$i][] = $k_day;
                    //判断是否存在此权限
                    if ($this->can('manager-sum_views')) {
                        $data[$i][] = $item['sum_views'];
                        $sum_data['sum_views'] += $item['sum_views'];
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-sum_cpc_clicks')) {
                        $data[$i][] = $item['sum_cpc_clicks'];
                        $sum_data['sum_cpc_clicks'] += $item['sum_cpc_clicks'];
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-sum_download_requests')) {
                        $data[$i][] = $item['sum_download_requests'];
                        $sum_data['sum_download_requests'] += $item['sum_download_requests'];
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-sum_download_complete')) {
                        $data[$i][] = $item['sum_download_complete']; //有权限
                        $sum_data['sum_download_complete'] += $item['sum_download_complete'];
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-sum_clicks')) {
                        $data[$i][] = $item['sum_clicks']; //有权限
                        $sum_data['sum_clicks'] += $item['sum_clicks'];
                    }
                    if ($this->can('manager-sum_cpa')) {
                        $data[$i][] = $item['sum_cpa']; //有权限
                        $sum_data['sum_cpa'] += $item['sum_cpa'];
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-ctr')) {
                        $data[$i][] = StatService::getCtr($item['sum_views'], $item['sum_clicks']); //有权限
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-cpc_ctr')) {
                        $data[$i][] = StatService::getCtr($item['sum_views'], $item['sum_cpc_clicks']); //有权限
                    }
                    //广告主消耗总数
                    if ($this->can('manager-sum_revenue_client')) {
                        $data[$i][] = Formatter::asDecimal($item['sum_revenue'] + $item['sum_revenue_gift']);
                        $sum_data['sum_revenue_client'] += $item['sum_revenue'] + $item['sum_revenue_gift'];
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-sum_revenue')) {
                        $data[$i][] = Formatter::asDecimal($item['sum_revenue']); //有权限收入
                        $sum_data['sum_revenue'] += $item['sum_revenue'];
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-sum_revenue_gift')) {
                        $data[$i][] = Formatter::asDecimal($item['sum_revenue_gift']);
                        $sum_data['sum_revenue_gift'] += $item['sum_revenue_gift'];
                    }
                    // 计费方式广告主
                    $data[$i][] =  Campaign::getRevenueTypeLabels(
                        $eData[$k_zone][$k_banner]['info']['client_revenue_type']
                    );
                    //媒体商支出总数
                    if ($this->can('manager-sum_payment_trafficker')) {
                        $data[$i][] = Formatter::asDecimal($item['sum_payment'] + $item['sum_payment_gift']);
                        $sum_data['sum_payment_trafficker'] += $item['sum_payment'] + $item['sum_payment_gift'];
                    }
                    //判断是否存在此权限
                    if ($this->can('manager-sum_payment')) {
                        $data[$i][] = Formatter::asDecimal($item['sum_payment']); //有权限支出
                        $sum_data['sum_payment'] += $item['sum_payment'];
                    }
                    if ($this->can('manager-sum_payment_gift')) {
                        $data[$i][] = Formatter::asDecimal($item['sum_payment_gift']); //有权限支出
                        $sum_data['sum_payment_gift'] += $item['sum_payment_gift'];
                    }
                    // 计费方式媒体商
                    $data[$i][] = Campaign::getRevenueTypeLabels(
                        $eData[$k_zone][$k_banner]['info']['media_revenue_type']
                    );
                    if ($this->can('manager-cpd')) {
                        if ($eData[$k_zone][$k_banner]['info']['client_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPD) {
                            $data[$i][] = StatService::getCpd(
                                $item['sum_revenue'] + $item['sum_revenue_gift'],
                                $item['sum_clicks']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['client_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPC) {
                            $data[$i][] = StatService::getCpd(
                                $item['sum_revenue'] + $item['sum_revenue_gift'],
                                $item['sum_cpc_clicks']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['client_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPA) {
                            $data[$i][] = StatService::getCpd(
                                $item['sum_revenue'] + $item['sum_revenue_gift'],
                                $item['sum_cpa']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['client_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPM) {
                            $data[$i][] = $eData[$k_zone][$k_banner]['info']['revenue'];
                        } else {
                            $data[$i][] = 0;
                        }
                    }
                    if ($this->can('manager-media_cpd')) {
                        if ($eData[$k_zone][$k_banner]['info']['media_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPD) {
                            $data[$i][] = StatService::getCpd(
                                $item['sum_payment'] + $item['sum_payment_gift'],
                                $item['sum_clicks']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['media_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPC) {
                            $data[$i][] = StatService::getCpd(
                                $item['sum_payment'] + $item['sum_payment_gift'],
                                $item['sum_cpc_clicks']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['media_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPA) {
                            $data[$i][] = StatService::getCpd(
                                $item['sum_payment'] + $item['sum_payment_gift'],
                                $item['sum_cpa']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['media_revenue_type']
                            == Campaign::REVENUE_TYPE_CPM) {
                            $data[$i][] = StatService::getEcpm(
                                $item['sum_payment'] + $item['sum_payment_gift'],
                                $item['sum_views']
                            );
                        } else {
                            $data[$i][] = 0;
                        }
                    }
                    if ($this->can('manager-ecpm')) {
                        $data[$i][] = StatService::getEcpm(
                            $item['sum_revenue'] + $item['sum_revenue_gift'],
                            $item['sum_views']
                        );//有权限
                    }
                    if ($this->can('manager-profit')) {
                        $data[$i][] = Formatter::asDecimal(
                            $item['sum_revenue'] - $item['sum_payment'] - $item['sum_payment_gift']
                        ); //有权限支出
                        $sum_data['profit'] +=
                            $item['sum_revenue'] - $item['sum_payment'] - $item['sum_payment_gift'];
                    }
                    if ($this->can('manager-profit_rate')) {
                        $data[$i][] = Formatter::asDecimal(
                            floatval($item['sum_revenue']) > 0 ? ($item['sum_revenue'] - $item['sum_payment'] -
                                    $item['sum_payment_gift']) / $item['sum_revenue'] * 100 : 0
                        ) . '%'; //有权限支出
                    }
                    $i++;
                }
            }
        }

        $sum_base = [
                '汇总',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
            ];
            $sum_arr = $this->getSummaryData($sum_data, $sum_base);
            array_push($data, $sum_arr);
            return self::downDailyReportCsv(
                Auth::user()->user_id,
                'affiliate',
                $start,
                $end,
                $sheetRow,
                $conditions,
                $data
            );
    }
    //@codeCoverageIgnoreEnd
    /**
     * 查询平台广告主-收入报表，对应平台【统计报表】-【收入报表】
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * |  audit | integer |  | 0收入报表 - 广告主 1审计收入 - 广告主 |  是 |
     * | period_start | date | 起始时间 |  | 是 |
     * | period_end | date | 终止时间 |  | 是 |
     * | span | integer | 数据分组类型 | 1：hours 2：days 3：month| 是 |
     * | zone_offset | string | 时区 | -8 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * name | sub name | sub name | sub name | type | description | restraint | required |
     * | :--: | :--: | :--: | :--: | :--------: | :-------: | :-----: |
     * | statChart |  |  |  | array | 图表数据 |  | 是 |
     * | | product_id |  |  | integer | 产品id |  | 是 |
     * | | name |  |  | string | 产品名称 |  | 是 |
     * | | product_list |  |  | array | json对象数组 |  | 是 |
     * | |  | campaign_id |  | integer | 广告id |  | 是 |
     * | |  | app_name |  | string | 广告名称 |  | 是 |
     * | |  | campaign_list |  |  |  |  | |
     * | |  |  | product_id | integer | 产品id |  | 是 |
     * | |  |  | name | string | 产品名称 |  | 是 |
     * | |  |  | campaign_id | integer | 广告id |  | 是 |
     * | |  |  | app_name | string | 广告名称 |  | 是 |
     * | |  |  | sum_views | integer | 展示量 |  | 是 |
     * | |  |  | sum_clicks | integer | 下载量 |  | 是 |
     * | |  |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  |  | sum_cpc_clicks | integer | 点击量 |  | |
     * | |  |  | sum_download_requests | integer | 下载请求(监控) |  | |
     * | |  |  | sum_download_complete | integer | 下载完成(监控) |  | |
     * | |  |  | sum_cpa | integer | 转化量 |  | |
     * | |  |  | sum_consum | decimal | 广告主结算金额 |  | |
     * | |  |  | time | date | 时间 |  | 是 |
     * | statData |  |  |  |  |  |  | |
     * | | campaignid |  |  | integer | 广告计划id |  | 是 |
     * | | clientname |  |  | string |  广告主简称 |  | 是 |
     * | | name |  |  | string | 产品名称 |  | 是 |
     * | | type |  |  | string | 产品类型　 |  | 是 |
     * | | app_name |  |  | integer | 广告名称 |  | 是 |
     * | | ad_type |  |  | integer | 广告类型 |  | 是 |
     * | | platform |  |  | integer | 所属平台 |  | 是 |
     * | | sum_views |  |  | integer | 展示量 |  | 是 |
     * | | sum_cpc_clicks |  |  | integer | 点击量 |  | 是 |
     * | | sum_download_requests |  |  | integer | 下载请求(监控) |  | 是 |
     * | | sum_download_complete |  |  | integer | 下载完成(监控) |  | 是 |
     * | |  sum_clicks |  |  | integer | 下载量(上报) |  | 是 |
     * | | ctr |  |  | decimal | 下载转化率 |  | 是 |
     * | | cpc_ctr |  |  | decimal | 点击转化率 |  | 是 |
     * | | sum_payment |  |  | decimal | 广告主消耗(充值金) |  | 是 |
     * | | sum_payment_gift |  |  | decimal | 广告主消耗(赠送金) |  | 是 |
     * | | sum_revenue |  |  | decimal | 媒体支出(充值金) |  | 是 |
     * | | sum_revenue_gift |  |  | decimal | 媒体支出(赠送金) |  | 是 |
     * | | cpd |  |  | decimal | 平均单价(广告主) |  | 是 |
     * | | media_cpd |  |  | decimal | 平均单价(媒体商) |  | 是 |
     * | | ecpm |  |  | decimal | eCPM |  | 是 |
     * | | sum_cpa |  |  | integer | 转化量 |  | 是 |
     * | | sum_consum |  |  | decimal | 广告主结算金额 |  | 是 |
     */
    public function client(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'audit' => 'required|in:0,1',
                'span' => 'required|in:3,1,2',
                'zone_offset' => 'required',
        ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
    
        $audit = Input::get('audit');
        $agencyId = Auth::user()->agencyid;
        $param = $this->transFormInput($request);
        if ($audit  == 1) {
            $param['period_start'] = Input::get('period_start');
            $param['period_end'] = Input::get('period_end');
            if ($param['axis'] == 'hours') {
                $param['axis'] = 'days';
            }
        }
        //权限不同查询的数据不同
        $statsCharts = [];
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        if ($this->can('manager-bd-all')) {
            $camArr = DB::table('clients as cli')
                ->join('campaigns as c', 'c.clientid', '=', 'cli.clientid')
                ->where('cli.agencyid', $agencyId)
                ->where('cli.affiliateid', 0)
                ->select('campaignid')
                ->get();
            $proArr = DB::table('clients as cli')
                ->join('products as p', 'p.clientid', '=', 'cli.clientid')
                ->where('cli.agencyid', $agencyId)
                ->where('cli.affiliateid', 0)
                ->select('id')
                ->get();
        } elseif ($this->can('manager-bd-self')) {
            $userId = Auth::user()->user_id;
            $camArr = DB::table('clients as cli')
                ->join('campaigns as c', 'c.clientid', '=', 'cli.clientid')
                ->where('creator_uid', $userId)
                ->where('cli.agencyid', $agencyId)
                ->where('cli.affiliateid', 0)
                ->select('campaignid')
                ->get();
            $proArr = DB::table('clients as cli')
                ->join('products as p', 'p.clientid', '=', 'cli.clientid')
                ->where('creator_uid', $userId)
                ->where('cli.agencyid', $agencyId)
                ->where('cli.affiliateid', 0)
                ->select('id')
                ->get();
        } else {
            return $this->errorCode(5020);
        }

        $campaignArr = [];
        $productArr = [];
        foreach ($camArr as $key => $val) {
            $campaignArr[] = $val['campaignid'];
        }
        foreach ($proArr as $k => $v) {
            $productArr[] = $v['id'];
        }
        $statsCharts = StatService::findManagerCampaignStat(
            $param['period_start'],
            $param['period_end'],
            $param['axis'],
            $param['zoneOffset'],
            Auth::user()->account->agency->agencyid,
            $audit
        );
        $eChart = [];
        $eData = [];
        $clientIdName = ArrayHelper::map(
            DB::table('clients')->get(),
            'clientid',
            'brief_name'
        );
        $productInfo = DB::table('products')->get();
        $productName = ArrayHelper::map(
            $productInfo,
            'id',
            'name'
        );
        $productType = ArrayHelper::map(
            $productInfo,
            'id',
            'type'
        );
        $productIcon= ArrayHelper::map(
            $productInfo,
            'id',
            'icon'
        );
        $appInfo = DB::table('appinfos')->get();
        $appName = ArrayHelper::map(
            $appInfo,
            'app_id',
            'app_name'
        );
        $appPlatform = ArrayHelper::map(
            $appInfo,
            'app_id',
            'platform'
        );
        foreach ($statsCharts as $val) {
            //报表数据按照媒体商进行汇总
            $val['clientname'] = $clientIdName[$val['clientid']];
            $val['product_name'] = $productName[$val['product_id']];
            $val['product_icon'] = $productIcon[$val['product_id']];
            $val['type'] = $productType[$val['product_id']];
            $val['app_name'] = $appName[$val['campaignname']];
            $val['platform'] = $appPlatform[$val['campaignname']];

            if (isset($eData[$val['campaignid']])) {
                foreach ($this->fieldAll as $label) {
                    $eData[$val['campaignid']][$label] += $val[$label];
                }
            } else {
                if (in_array($val['campaignid'], $campaignArr)) {
                    $eData[$val['campaignid']] = $val;
                }
            }
            if (isset($eData[$val['campaignid']])) {
                //如果appstore 则返回所属平台为7
                if ($eData[$val['campaignid']]['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    $eData[$val['campaignid']]['platform'] = Campaign::PLATFORM_IOS;
                }
                $eData[$val['campaignid']]['ctr'] = StatService::getCtr(
                    $eData[$val['campaignid']]['sum_views'],
                    $eData[$val['campaignid']]['sum_clicks']
                ); //有权限
                $eData[$val['campaignid']]['cpc_ctr'] = StatService::getCtr(
                    $eData[$val['campaignid']]['sum_views'],
                    $eData[$val['campaignid']]['sum_cpc_clicks']
                ); //有权限
                //广告主的平均单价
                $eData[$val['campaignid']]['cpd'] = StatService::getCpd(
                    $eData[$val['campaignid']]['sum_revenue'] + $eData[$val['campaignid']]['sum_revenue_gift'],
                    $eData[$val['campaignid']]['sum_clicks'] + $eData[$val['campaignid']]['sum_cpc_clicks'] +
                    $eData[$val['campaignid']]['sum_cpa']
                );
                //媒体商的平均单价
                $eData[$val['campaignid']]['media_cpd'] = StatService::getCpd(
                    $eData[$val['campaignid']]['sum_payment'] + $eData[$val['campaignid']]['sum_payment_gift'],
                    $eData[$val['campaignid']]['sum_clicks'] +  $eData[$val['campaignid']]['sum_cpc_clicks'] +
                    $eData[$val['campaignid']]['sum_cpa']
                );
                $eData[$val['campaignid']]['ecpm'] = StatService::getEcpm(
                    $eData[$val['campaignid']]['sum_revenue'] + $eData[$val['campaignid']]['sum_revenue_gift'],
                    $eData[$val['campaignid']]['sum_views']
                );//有权限
                unset($eData[$val['campaignid']]['media_revenue_type']);
            }

            //第一步图表数据按照媒体商进行汇总
            if (!isset($eChart[$val['product_id']])) {
                if (in_array($val['product_id'], $productArr)) {
                    $eChart[$val['product_id']] = [
                        'product_id' => $val['product_id'],
                        'product_name' => $val['product_name']
                    ];
                }
            }
            //第二步图表数据按照广告位进行汇总

            if (isset($eChart[$val['product_id']])) {
                if (!isset($eChart[$val['product_id']]['product_list'][$val['campaignid']])) {
                    $eChart[$val['product_id']]['product_list'][$val['campaignid']] = [
                        'campaignid' => $val['campaignid'],
                        'app_name' => $val['app_name'],
                    ];
                }
                $eChart[$val['product_id']]['product_list'][$val['campaignid']]['campaign_list'][] = $val;
            }
        }

        $eChart = array_values($eChart);
        $eData = array_values($eData);
        $list['statChart'] = $eChart;
        $list['statData'] = $eData;
        return $this->success(null, null, $list);
    }
    /**
     *  查询平台广告主-收入报表，查询广告在每个媒体广告位的投放数据
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * |  audit | integer |  | 0收入报表 - 广告主 |  是 |
     * | |  |  | 1审计收入 - 广告主 | |
     * | campaignid | integer | 广告计划id |  | 是 |
     * | productid | integer | 产品id |  | 是 |
     * | bannerid | integer | 广告id |  | |
     * | period_start | date | 起始时间 |  | 是 |
     * | period_end | date | 终止时间 |  | 是 |
     * | span | integer | 数据分组类型 | 1：hours | 是 |
     * | |  |  | 2：days | |
     * | |  |  | 3：month | |
     * | zone_offset | string | 时区 | -8 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | sub name | type | description | restraint | required |
     * | :--: | :--: | :--: | :--------: | :-------: | :-----: |
     * | statChart |  |  |  |  |  | |
     * | | campaign_id |  | array | 广告计划id |  | 是 |
     * | | aff_list |  |  |  |  | |
     * | |  | product_id | integer | 产品id |  | 是 |
     * | |  | name | string | 产品名称 |  | 是 |
     * | |  | campaignid | integer | 广告id |  | 是 |
     * | |  | app_name | string | 广告名称 |  | 是 |
     * | |  | bannerid | integer | 广告id |  | 是 |
     * | |  | brief_name | string | 媒体简称 |  | 是 |
     * | |  | sum_views | integer | 展示量 |  | 是 |
     * | |  | sum_clicks | integer | 下载量 |  | 是 |
     * | |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  | sum_cpc_clicks |  | 点击量 |  | |
     * | |  | sum_download_requests |  | 下载请求(监控) |  | |
     * | |  | sum_download_complete |  | 下载完成(监控) |  | |
     * | |  | sum_cpa |  | 转化量 |  | |
     * | |  | sum_consum |  | 广告主结算金额 |  | |
     * | |  | time | date | 时间 |  | 是 |
     * | statData |  |  |  |  |  | 是 |
     * | | channel |  | string | 渠道号 |  | 是 |
     * | | brief_name |  | string | 媒体商名称 |  | 是 |
     * | | platform |  | integer | 所属平台 |  | 是 |
     * | |  sum_views |  | integer | 展示量 |  | 是 |
     * | | sum_cpc_clicks |  | integer |  点击量 |  | 是 |
     * | | sum_download_requests |  | integer |  下载请求(监控) |  | 是 |
     * | | sum_download_complete |  | integer |  下载完成(监控) |  | 是 |
     * | |  sum_clicks |  | integer |  下载量(上报) |  | 是 |
     * | | ctr |  | decimal |  下载转化率 |  | 是 |
     * | | cpc_ctr |  | decimal |  点击转化率 |  | 是 |
     * | | sum_payment |  | decimal |  广告主消耗(充值金) |  | 是 |
     * | | sum_payment_gift |  | decimal |  广告主消耗(赠送金) |  | 是 |
     * | | sum_revenue |  | decimal |  媒体支出(充值金) |  | 是 |
     * | | sum_revenue_gift |  | decimal |  媒体支出(赠送金) |  | 是 |
     * | | cpd |  | decimal | 平均单价(广告主) |  | 是 |
     * | | media_cpd |  | decimal | 平均单价(媒体商) |  | 是 |
     * | | ecpm |  | decimal | eCPM |  | 是 |
     * | | sum_cpa |  | integer | 转化量 |  | 是 |
     * | | sum_consum |  | decimal | 广告主结算金额 |  | 是 |
     * | | child |  | array |  |  | 是 |
     * | |  | zoneid | integer | 广告位id |  | |
     * | |  | zonename | string | 广告位名称 |  | 是 |
     * | |  | zone_type | integer | 广告位类别 |  | 是 |
     * | |  |  sum_views | integer | 展示量 |  | 是 |
     * | |  | sum_cpc_clicks | integer |  点击量 |  | 是 |
     * | |  | sum_download_requests | integer |  下载请求(监控) |  | 是 |
     * | |  | sum_download_complete | integer |  下载完成(监控) |  | 是 |
     * | |  |  sum_clicks | integer |  下载量(上报) |  | 是 |
     * | |  | ctr | decimal |  下载转化率 |  | 是 |
     * | |  | cpc_ctr | decimal |  点击转化率 |  | 是 |
     * | |  | sum_payment | decimal |  广告主消耗(充值金) |  | 是 |
     * | |  | sum_payment_gift | decimal |  广告主消耗(赠送金) |  | 是 |
     * | |  | sum_revenue | decimal |  媒体支出(充值金) |  | 是 |
     * | |  | sum_revenue_gift | decimal |  媒体支出(赠送金) |  | 是 |
     * | |  | cpd | decimal | 平均单价(广告主) |  | 是 |
     * | |  | media_cpd | decimal | 平均单价(媒体商) |  | 是 |
     * | |  | ecpm | decimal | eCPM |  | 是 |
     * | |  | sum_cpa | integer | 转化量 |  | 是 |
     * | |  | sum_consum | decimal | 广告主结算金额 |  | 是 |
     */
    public function clientCampaign(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required|in:3,1,2',
                'zone_offset' => 'required',
                'audit' => 'required|in:0,1',
                'campaignid' => 'required'
        ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $audit = Input::get('audit');
        $campaignId = Input::get('campaignid');
        $param = $this->transFormInput($request);
        if ($audit  == 1) {
            $param['period_start'] = Input::get('period_start');
            $param['period_end'] = Input::get('period_end');
            if ($param['axis'] == 'hours') {
                $param['axis'] = 'days';
            }
        }
        //根据权限不同查询的数据不同
        $statsCharts = [];
        if ($this->can('manager-bd-all')) {
            $statsCharts = StatService::findManagerCampaignZoneStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                $campaignId,
                $audit
            );
        } elseif ($this->can('manager-bd-self')) {
            $statsCharts = StatService::findManagerCampaignZoneStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                $campaignId,
                $audit,
                Auth::user()->user_id
            );
        } else {
            return $this->errorCode(5020);
        }
        $eChart = [];
        $eData = [];
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $affiliateArr =  DB::table('affiliates')->get();
        $affiliateIdName = ArrayHelper::map(
            $affiliateArr,
            'affiliateid',
            'brief_name'
        );
        $affiliateFullName = ArrayHelper::map(
            $affiliateArr,
            'affiliateid',
            'name'
        );
        $channel = ArrayHelper::map(
            DB::table('attach_files')->get(),
            'id',
            'channel'
        );
        foreach ($statsCharts as $val) {
            //广告的维度
            $val['name'] = $affiliateFullName[$val['affiliateid']];
            $val['brief_name'] = $affiliateIdName[$val['affiliateid']];
            if ($val['attach_file_id'] > 0) {
                $val['channel'] = $channel[$val['attach_file_id']];
            }
            $eChart[$val['campaignid']][] = $val;
            //报表的数据-广告位的维度
            if (isset($eData[$val['bannerid']])) {
                foreach ($this->fieldAll as $label) {
                    $eData[$val['bannerid']][$label] += $val[$label];
                }
            } else {
                $eData[$val['bannerid']] = $val;
                unset($eData[$val['bannerid']]['zonename']);
                unset($eData[$val['bannerid']]['zone_type']);
            }

            $eData[$val['bannerid']]['ctr'] = StatService::getCtr(
                $eData[$val['bannerid']]['sum_views'],
                $eData[$val['bannerid']]['sum_clicks']
            ); //有权限
            $eData[$val['bannerid']]['cpc_ctr'] = StatService::getCtr(
                $eData[$val['bannerid']]['sum_views'],
                $eData[$val['bannerid']]['sum_cpc_clicks']
            ); //有权限
            $eData[$val['bannerid']]['cpd'] = StatService::getCpd(
                $eData[$val['bannerid']]['sum_revenue'] + $eData[$val['bannerid']]['sum_revenue_gift'],
                $eData[$val['bannerid']]['sum_clicks'] + $eData[$val['bannerid']]['sum_cpc_clicks'] +
                $eData[$val['bannerid']]['sum_cpa']
            );
            $eData[$val['bannerid']]['media_cpd'] = StatService::getCpd(
                $eData[$val['bannerid']]['sum_payment'] + $eData[$val['bannerid']]['sum_payment_gift'],
                $eData[$val['bannerid']]['sum_clicks'] + $eData[$val['bannerid']]['sum_cpc_clicks']  +
                $eData[$val['bannerid']]['sum_cpa']
            );

            $eData[$val['bannerid']]['ecpm'] = StatService::getEcpm(
                $eData[$val['bannerid']]['sum_revenue'] + $eData[$val['bannerid']]['sum_revenue_gift'],
                $eData[$val['bannerid']]['sum_views']
            );//有权限

            //报表的数据-广告的维度
            if (isset($eData[$val['bannerid']]['child'][$val['zoneid']])) {
                foreach ($this->fieldAll as $label) {
                    $eData[$val['bannerid']]['child'][$val['zoneid']][$label] += $val[$label];
                }
            } else {
                $eData[$val['bannerid']]['child'][$val['zoneid']] = $val;
                unset($eData[$val['bannerid']]['child'][$val['zoneid']]['brief_name']);
                unset($eData[$val['bannerid']]['child'][$val['zoneid']]['channel']);
            }
            $eData[$val['bannerid']]['child'][$val['zoneid']]['ctr'] = StatService::getCtr(
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_views'],
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_clicks']
            ); //有权限
            $eData[$val['bannerid']]['child'][$val['zoneid']]['cpc_ctr'] = StatService::getCtr(
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_views'],
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_cpc_clicks']
            ); //有权限
            $eData[$val['bannerid']]['child'][$val['zoneid']]['cpd'] = StatService::getCpd(
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_revenue'] +
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_revenue_gift'],
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_clicks']
                + $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_cpc_clicks']
                + $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_cpa']
            );
            $eData[$val['bannerid']]['child'][$val['zoneid']]['media_cpd'] = StatService::getCpd(
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_payment'] +
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_payment_gift'],
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_clicks']
                +  $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_cpc_clicks']
                +  $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_cpa']
            );
            $eData[$val['bannerid']]['child'][$val['zoneid']]['ecpm'] = StatService::getEcpm(
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_revenue'] +
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_revenue_gift'],
                $eData[$val['bannerid']]['child'][$val['zoneid']]['sum_views']
            );
            unset($eData[$val['bannerid']]['child'][$val['zoneid']]['client_revenue_type']);
            unset($eData[$val['bannerid']]['child'][$val['zoneid']]['media_revenue_type']);
            unset($eData[$val['bannerid']]['client_revenue_type']);
        }

        $eData = array_values($eData);
        $list['statChart'] = $eChart;
        $list['statData'] = $eData;
    
        return $this->success(null, null, $list);
    }
    
    /**
     * 导出平台广告主-收入报表, 导出报表
     *
     * name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | zoneOffset | string | 时区 |  | 是 |
     * | audit | int | 0平台1审计 |  | 是 |
     * | product_id |  | 产品id |  | 是 |
     * | campaignid |  | 广告计划id |  | 是 |
     * | bannerid |  | 广告id |  | 是 |
     * | media_revenue_type |  | 媒體計費方式 |  | 是 |
     * | client_revenue_type |  | 广告計費方式 |  | 是 |
     * | business |  | 广告計費方式 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function clientExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'zone_offset' => 'required',
                'audit' => 'required|in:0,1'
        ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $audit = Input::get('audit');
        $productId = Input::get('productid');
        $campaignId = Input::get('campaignid');
        $bannerId = Input::get('bannnerid');
        $business_type = Input::get('business_type');
        $media_revenue_type = Input::get('media_revenue_type');
        $client_revenue_type = Input::get('client_revenue_type');
        $param = $this->transFormInput($request);
        $param['axis'] = 'days';
        //根据权限不同查询的数据不同
        $statsCharts = [];
        if ($this->can('manager-bd-all')) {
            $statsCharts = StatService::findManagerCampaignExcelStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                Auth::user()->account->agency->agencyid,
                $productId,
                $campaignId,
                $bannerId,
                $media_revenue_type,
                $client_revenue_type,
                $business_type,
                $audit
            );
        } elseif ($this->can('manager-bd-self')) {
            $statsCharts = StatService::findManagerCampaignExcelStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                Auth::user()->account->agency->agencyid,
                $productId,
                $campaignId,
                $bannerId,
                $media_revenue_type,
                $client_revenue_type,
                $business_type,
                $audit,
                Auth::user()->user_id
            );
        } else {
            return $this->errorCode(5020);
        }
        $data = [];
        $i = 0;
        $sum_data = $this->sumData;
        $brokerName = ArrayHelper::map(
            DB::table('brokers')->get(),
            'brokerid',
            'name'
        );
        foreach ($statsCharts as $value) {
            if ($value['broker_id'] > 0) {
                $data[$i][] = $brokerName[$value['broker_id']];
            } else {
                $data[$i][] = '';
            }
            $data[$i][] = $value['clientname'];
            $data[$i][] = $value['product_name'];
            $data[$i][] = Product::getTypeLabels($value['type']);
            $data[$i][] = $value['app_name'];
            $data[$i][] = Campaign::getBusinessType($value['business_type']);
            $data[$i][] = Campaign::getAdTypeLabels($value['ad_type']);
            $data[$i][] = $value['contact_name'];
            $data[$i][] = $value['channel'];
            //$data[$i][] = Campaign::getPlatformLabels($value['platform']);
            if ($value['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $data[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
            } else {
                $data[$i][] = Campaign::getPlatformLabels($value['platform']);
            }

            $data[$i][] = $value['zonename'];
            $data[$i][] = $value['name'];
            $data[$i][] = $value['brief_name'];
            $data[$i][] = Zone::getAdTypeLabels($value['zone_type']);
            if ($this->can('manager-sum_views')) {
                $data[$i][] = $value['sum_views'];
                $sum_data['sum_views'] += $value['sum_views'];
            }
            if ($this->can('manager-sum_cpc_clicks')) {
                $data[$i][] = $value['sum_cpc_clicks'];
                $sum_data['sum_cpc_clicks'] += $value['sum_cpc_clicks'];
            }
            if ($this->can('manager-sum_download_requests')) {
                $data[$i][] = $value['sum_download_requests'];
                $sum_data['sum_download_requests'] += $value['sum_download_requests'];
            }
            if ($this->can('manager-sum_download_complete')) {
                $data[$i][] = $value['sum_download_complete'];
                $sum_data['sum_download_complete'] += $value['sum_download_complete'];
            }
            if ($this->can('manager-sum_clicks')) {
                $data[$i][] = $value['sum_clicks'];
                $sum_data['sum_clicks'] += $value['sum_clicks'];
            }
            if ($this->can('manager-sum_cpa')) {
                $data[$i][] = $value['sum_cpa'];      //有权限
                $sum_data['sum_cpa'] += $value['sum_cpa'];
            }
            
            if ($this->can('manager-ctr')) {
                $data[$i][] = StatService::getCtr($value['sum_views'], $value['sum_clicks']);
            }
            if ($this->can('manager-cpc_ctr')) {
                $data[$i][] = StatService::getCtr($value['sum_views'], $value['sum_cpc_clicks']);
            }
            //广告主消耗总数
            if ($this->can('manager-sum_revenue_client')) {
                $data[$i][] = Formatter::asDecimal($value['sum_revenue'] + $value['sum_revenue_gift']);
                $sum_data['sum_revenue_client'] += $value['sum_revenue'] + $value['sum_revenue_gift'];
            }
            if ($this->can('manager-sum_revenue')) {
                $data[$i][] = Formatter::asDecimal($value['sum_revenue']);
                $sum_data['sum_revenue'] += $value['sum_revenue'];
            }
            if ($this->can('manager-sum_revenue_gift')) {
                $data[$i][] = Formatter::asDecimal($value['sum_revenue_gift']);
                $sum_data['sum_revenue_gift'] += $value['sum_revenue_gift'];
            }
            $data[$i][] = Campaign::getRevenueTypeLabels($value['client_revenue_type']);
            //媒体商支出总数
            if ($this->can('manager-sum_payment_trafficker')) {
                $data[$i][] = Formatter::asDecimal($value['sum_payment'] + $value['sum_payment_gift']);
                $sum_data['sum_payment_trafficker'] += $value['sum_payment'] + $value['sum_payment_gift'];
            }
            if ($this->can('manager-sum_payment')) {
                $data[$i][] = Formatter::asDecimal($value['sum_payment']);
                $sum_data['sum_payment'] += $value['sum_payment'];
            }
            if ($this->can('manager-sum_payment_gift')) {
                $data[$i][] = Formatter::asDecimal($value['sum_payment_gift']);
                $sum_data['sum_payment_gift'] += $value['sum_payment_gift'];
            }
            $data[$i][] = Campaign::getRevenueTypeLabels($value['media_revenue_type']);
            if ($this->can('manager-cpd')) {
                if ($value['client_revenue_type'] == Campaign::REVENUE_TYPE_CPD) {
                    $data[$i][] = StatService::getCpd(
                        $value['sum_revenue'] + $value['sum_revenue_gift'],
                        $value['sum_clicks']
                    );
                } elseif ($value['client_revenue_type'] == Campaign::REVENUE_TYPE_CPC) {
                    $data[$i][] = StatService::getCpd(
                        $value['sum_revenue'] + $value['sum_revenue_gift'],
                        $value['sum_cpc_clicks']
                    );
                } elseif ($value['client_revenue_type'] == Campaign::REVENUE_TYPE_CPA) {
                    $data[$i][] = StatService::getCpd(
                        $value['sum_revenue'] + $value['sum_revenue_gift'],
                        $value['sum_cpa']
                    );
                } elseif ($value['client_revenue_type'] == Campaign::REVENUE_TYPE_CPM) {
                    $data[$i][] = $value['revenue'];
                } else {
                    $data[$i][] = 0;
                }
            }
            if ($this->can('manager-media_cpd')) {
                if ($value['media_revenue_type'] == Campaign::REVENUE_TYPE_CPD) {
                    $data[$i][] = StatService::getCpd(
                        $value['sum_payment'] + $value['sum_payment_gift'],
                        $value['sum_clicks']
                    );
                } elseif ($value['media_revenue_type'] == Campaign::REVENUE_TYPE_CPC) {
                    $data[$i][] = StatService::getCpd(
                        $value['sum_payment'] + $value['sum_payment_gift'],
                        $value['sum_cpc_clicks']
                    );
                } elseif ($value['media_revenue_type'] == Campaign::REVENUE_TYPE_CPA) {
                    $data[$i][] = StatService::getCpd(
                        $value['sum_payment'] + $value['sum_payment_gift'],
                        $value['sum_cpa']
                    );
                } elseif ($value['media_revenue_type'] == Campaign::REVENUE_TYPE_CPM) {
                    $data[$i][] = StatService::getEcpm(
                        $value['sum_payment'] + $value['sum_payment_gift'],
                        $value['sum_views']
                    );
                } else {
                    $data[$i][] = 0;
                }
            }
            if ($this->can('manager-ecpm')) {
                $data[$i][] = StatService::getEcpm(
                    $value['sum_revenue'] + $value['sum_revenue_gift'],
                    $value['sum_views']
                );//有权限
            }
            if ($this->can('manager-profit')) {
                $data[$i][] = Formatter::asDecimal(
                    $value['sum_revenue'] - $value['sum_payment'] - $value['sum_payment_gift']
                ); //有权限支出
                $sum_data['profit'] += $value['sum_revenue'] - $value['sum_payment'] - $value['sum_payment_gift'];
            }
            if ($this->can('manager-profit_rate')) {
                $data[$i][] = Formatter::asDecimal(
                    floatval($value['sum_revenue']) > 0 ? ($value['sum_revenue'] - $value['sum_payment'] -
                            $value['sum_payment_gift']) / $value['sum_revenue'] * 100 : 0
                ) . '%'; //有权限支出
            }
            $i ++;
        }
        $column = [
                'broker_name' => '代理商',
                'clientname' => '广告主',
                'product_name' => '推广产品',
                'type' => '推广类型',
                'app_name' => '广告名称',
                'business_type' => '业务类型',
                'ad_type' => '广告类型',
                'contact_name' => '销售负责人',
                'channel' => '渠道号',
                'platform' => '所属平台',
                'zonename' => '广告位',
                'name' => '媒体商全称',
                'brief_name' => '媒体商简称',
                'zone_type' => '广告位类别',
        ];
        $column = array_merge($column, $this->showName);
        //表头权限控制
        foreach ($column as $key => $val) {
            if (in_array($key, $this->fieldControl)) {
                if ($this->can('manager-'.$key)) {
                    $sheetRow[$key] = $val;
                }
            } else {
                $sheetRow[$key] = $val;
            }
        }
        $excelName = '广告主报表-'. str_replace('-', '', $param['period_start']) . '_' .
                str_replace('-', '', $param['period_end']);
        $sum_base = [
                '汇总',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
        ];
        $sum_arr = $this->getSummaryData($sum_data, $sum_base);

        array_push($data, $sum_arr);
        StatService::downloadCsv($excelName, $sheetRow, $data);
    }
    /**
     * 导出平台广告主-收入报表, 导出每日报表报表
     *
     * name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | zoneOffset | string | 时区 |  | 是 |
     * | audit | int | 0平台1审计 |  | 是 |
     * | product_id |  | 产品id |  | 是 |
     * | campaignid |  | 广告计划id |  | 是 |
     * | bannerid |  | 广告id |  | 是 |
     * | media_revenue_type |  | 媒體計費方式 |  | 是 |
     * | client_revenue_type |  | 广告計費方式 |  | 是 |
     * | business |  | 广告計費方式 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function clientDailyExcel(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'zone_offset' => 'required',
                'audit' => 'required|in:0,1',
        ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $business_type = Input::get('business_type');
        $audit = Input::get('audit');
        $productId = Input::get('productid');
        $campaignId = Input::get('campaignid');
        $bannerId = Input::get('bannnerid');
        $media_revenue_type = Input::get('media_revenue_type');
        $client_revenue_type = Input::get('client_revenue_type');
        $param = $this->transFormInput($request);
        $param['axis'] = 'days';
        $column = [
                'broker_name' => '代理商',
                'clientname' => '广告主',
                'product_name' => '推广产品',
                'type' => '推广类型',
                'app_name' => '广告名称',
                'business_type' => '业务类型',
                'ad_type' => '广告类型',
                'contact_name' => '销售负责人',
                'channel' => '渠道号',
                'platform' => '所属平台',
                'zonename' => '广告位',
                'name' => '媒体商全称',
                'affiliatesname' => '媒体商简称',
                'zone_type' => '广告位类别',
                'days' => '日期',
        ];
        $column = array_merge($column, $this->showName);
        //表头权限控制
        $item = [];
        foreach ($column as $key => $val) {
            if (in_array($key, $this->fieldControl)) {
                if ($this->can('manager-'.$key)) {
                    $item[$key] = $val;
                }
            } else {
                $item[$key] = $val;
            }
        }
        $start = Input::get('period_start');
        $end =  Input::get('period_end');
        $agencyId = Auth::user()->account->agency->agencyid;
        $conditions = array(
            $audit,
            $param['zoneOffset'],
            $bannerId,
            $campaignId,
            $productId,
            $media_revenue_type,
            $client_revenue_type,
            $business_type,
            $agencyId,
            $param['axis']
         );
        $files = self::generateDailyReportFileName(
            Auth::user()->user_id,
            'client',
            $start,
            $end,
            $item,
            $conditions
        );
        $file = $files[0];
        $fileShowName = $files[1];
        $headers = $files[2];
        if (file_exists($file)) {
            return response()->download($file, $fileShowName, $headers);
            exit;
        }
        //根据权限不同查询的数据不同
        $statsCharts = [];
        if ($this->can('manager-bd-all')) {
            $statsCharts = StatService::findManagerCampaignExcelStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                $agencyId,
                $productId,
                $campaignId,
                $bannerId,
                $media_revenue_type,
                $client_revenue_type,
                $business_type,
                $audit,
                0,
                'client',
                true
            );
        } elseif ($this->can('manager-bd-self')) {
            $statsCharts = StatService::findManagerCampaignExcelStat(
                $param['period_start'],
                $param['period_end'],
                $param['axis'],
                $param['zoneOffset'],
                Auth::user()->account->agency->agencyid,
                $productId,
                $campaignId,
                $bannerId,
                $media_revenue_type,
                $client_revenue_type,
                $business_type,
                $audit,
                Auth::user()->user_id,
                'client',
                true
            );
        } else {
            return $this->errorCode(5020);
        }
        $data = [];
        $i = 0;
        $sum_data = $this->sumData;
        $exportData = [];
        $brokerName = ArrayHelper::map(
            DB::table('brokers')->get(),
            'brokerid',
            'name'
        );
        if (!empty($statsCharts)) {
            foreach ($statsCharts as $k => $v) {
                if ($v['broker_id'] > 0) {
                    $v['broker_name'] = $brokerName[$v['broker_id']];
                } else {
                    $v['broker_name'] = '';
                }
                if (empty($baseData[$v['zoneid']][$v['bannerid']]['info'])) {
                    $exportData[$v['zoneid']][$v['bannerid']]['info'] = $v;
                }
                $exportData[$v['zoneid']][$v['bannerid']]['days'][$v['time']] = [
                        "sum_views" => $v['sum_views'],
                        "sum_clicks" => $v['sum_clicks'],
                        "sum_cpc_clicks" => $v['sum_cpc_clicks'],
                        "sum_download_requests" => $v['sum_download_requests'],
                        "sum_download_complete" => $v['sum_download_complete'],
                        "sum_cpa" => $v['sum_cpa'],
                        "sum_consum" => $v['sum_consum'],
                        "sum_payment" => $v['sum_payment'],
                        "sum_payment_gift" => $v['sum_payment_gift'],
                        "sum_revenue" => $v['sum_revenue'],
                        "sum_revenue_gift" => $v['sum_revenue_gift'],
                ];
            }
        }
        $eData = $exportData;
        $dayCount = (strtotime($end) - strtotime($start)) / 86400;
        if (!empty($exportData)) {
            foreach ($exportData as $k => $v) {
                foreach ($v as $ke => $va) {
                    $_start = $start;
                    for ($i = 0; $i <= $dayCount; $i++) {
                        $eData[$k][$ke]['days']= array_add($eData[$k][$ke]['days'], $_start, [
                                "sum_views" => 0,
                                "sum_clicks" => 0,
                                "sum_cpc_clicks" => 0,
                                "sum_download_requests" => 0,
                                "sum_download_complete" => 0,
                                "sum_cpa" => 0,
                                "sum_consum" => 0,
                                "sum_payment" => 0,
                                "sum_payment_gift" => 0,
                                "sum_revenue" => 0,
                                "sum_revenue_gift" => 0,
                        ]);
                        $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
                    }
                }
            }
        }
        foreach ($eData as $k_zone => $v_zone) {
            foreach ($v_zone as $k_banner => $v_banner) {
                foreach ($v_banner ['days'] as $k_day => $val) {
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['broker_name'];
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['clientname'];
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['product_name'];
                    $data[$i][] = Product::getTypeLabels($eData[$k_zone][$k_banner]['info']['type']);
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['app_name'];
                    $data[$i][] = Campaign::getBusinessType($eData[$k_zone][$k_banner]['info']['business_type']);
                    $data[$i][] = Campaign::getAdTypeLabels($eData[$k_zone][$k_banner]['info']['ad_type']);
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['contact_name'];
                    $data[$i][] = $eData[$k_zone][$k_banner]['info']['channel'];
//                    $data[$i][] = Campaign::getPlatformLabels($eData[$k_zone][$k_banner]['info']['platform']);
                    if ($eData[$k_zone][$k_banner]['info']['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                        $data[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                    } else {
                        $data[$i][] = Campaign::getPlatformLabels($eData[$k_zone][$k_banner]['info']['platform']);
                    }
                    $data[$i][] =$eData[$k_zone][$k_banner]['info']['zonename'];
                    $data[$i][] =$eData[$k_zone][$k_banner]['info']['name'];
                    $data[$i][] =$eData[$k_zone][$k_banner]['info']['brief_name'];
                    $data[$i][] = Zone::getAdTypeLabels($eData[$k_zone][$k_banner]['info']['zone_type']);
                    $data[$i][] =  $k_day;
                    if ($this->can('manager-sum_views')) {
                        $data[$i][] = $val['sum_views'];              //有权限
                        $sum_data['sum_views'] += $val['sum_views'];
                    }
                    if ($this->can('manager-sum_cpc_clicks')) {
                        $data[$i][] = $val['sum_cpc_clicks'];         //有权限
                        $sum_data['sum_cpc_clicks'] += $val['sum_cpc_clicks'];
                    }
                    if ($this->can('manager-sum_download_requests')) {
                        $data[$i][] = $val['sum_download_requests'];  //有权限
                        $sum_data['sum_download_requests'] += $val['sum_download_requests'];
                    }
                    if ($this->can('manager-sum_download_complete')) {
                        $data[$i][] = $val['sum_download_complete'];  //有权限
                        $sum_data['sum_download_complete'] += $val['sum_download_complete'];
                    }
                    if ($this->can('manager-sum_clicks')) {
                        $data[$i][] = $val['sum_clicks'];             //有权限
                        $sum_data['sum_clicks'] += $val['sum_clicks'];
                    }
                    if ($this->can('manager-sum_cpa')) {
                        $data[$i][] = $val['sum_cpa'];      //有权限
                        $sum_data['sum_cpa'] += $val['sum_cpa'];      //有权限
                    }
                    if ($this->can('manager-ctr')) {
                        $data[$i][] = StatService::getCtr($val['sum_views'], $val['sum_clicks']); //有权限
                    }
                    if ($this->can('manager-cpc_ctr')) {
                        $data[$i][] = StatService::getCtr($val['sum_views'], $val['sum_cpc_clicks']); //有权限
                    }
                    //广告主消耗总数
                    if ($this->can('manager-sum_revenue_client')) {
                        $data[$i][] = Formatter::asDecimal($val['sum_revenue'] + $val['sum_revenue_gift']);
                        $sum_data['sum_revenue_client'] += $val['sum_revenue'] + $val['sum_revenue_gift'];
                    }
                    if ($this->can('manager-sum_revenue')) {
                        $data[$i][] = Formatter::asDecimal($val['sum_revenue']);            //有权限
                        $sum_data['sum_revenue'] += $val['sum_revenue'];
                    }
                    if ($this->can('manager-sum_revenue_gift')) {
                        $data[$i][] = Formatter::asDecimal($val['sum_revenue_gift']);            //有权限
                        $sum_data['sum_revenue_gift'] += $val['sum_revenue_gift'];            //有权限
                    }
    
                    $data[$i][] = Campaign::getRevenueTypeLabels(
                        $eData[$k_zone][$k_banner]['info']['client_revenue_type']
                    );
                    //媒体商支出总数
                    if ($this->can('manager-sum_payment_trafficker')) {
                        $data[$i][] = Formatter::asDecimal($val['sum_payment'] + $val['sum_payment_gift']);
                        $sum_data['sum_payment_trafficker'] += $val['sum_payment'] + $val['sum_payment_gift'];
                    }
                    if ($this->can('manager-sum_payment')) {
                        $data[$i][] = Formatter::asDecimal($val['sum_payment']);            //有权限
                        $sum_data['sum_payment'] += $val['sum_payment'];            //有权限
                    }
                    if ($this->can('manager-sum_payment_gift')) {
                        $data[$i][] = Formatter::asDecimal($val['sum_payment_gift']);            //有权限
                        $sum_data['sum_payment_gift'] += $val['sum_payment_gift'];
                    }
                       //有权限
    
                    $data[$i][] = Campaign::getRevenueTypeLabels(
                        $eData[$k_zone][$k_banner]['info']['media_revenue_type']
                    );
                    if ($this->can('manager-cpd')) {
                        if ($eData[$k_zone][$k_banner]['info']['client_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPD) {
                            $data[$i][] = StatService::getCpd(
                                $val['sum_revenue'] + $val['sum_revenue_gift'],
                                $val['sum_clicks']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['client_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPC) {
                            $data[$i][] = StatService::getCpd(
                                $val['sum_revenue'] + $val['sum_revenue_gift'],
                                $val['sum_cpc_clicks']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['client_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPA) {
                            $data[$i][] = StatService::getCpd(
                                $val['sum_revenue'] + $val['sum_revenue_gift'],
                                $val['sum_cpa']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['client_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPM) {
                            $data[$i][] = $eData[$k_zone][$k_banner]['info']['revenue'] ;
                        } else {
                            $data[$i][] = 0;
                        }
                    }
                    if ($this->can('manager-media_cpd')) {
                        if ($eData[$k_zone][$k_banner]['info']['media_revenue_type'] ==
                            Campaign::REVENUE_TYPE_CPD) {
                            $data[$i][] = StatService::getCpd(
                                $val['sum_payment'] + $val['sum_payment_gift'],
                                $val['sum_clicks']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['media_revenue_type']
                            == Campaign::REVENUE_TYPE_CPC) {
                            $data[$i][] = StatService::getCpd(
                                $val['sum_payment'] + $val['sum_payment_gift'],
                                $val['sum_cpc_clicks']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['media_revenue_type']
                            == Campaign::REVENUE_TYPE_CPA) {
                            $data[$i][] = StatService::getCpd(
                                $val['sum_payment'] + $val['sum_payment_gift'],
                                $val['sum_cpa']
                            );
                        } elseif ($eData[$k_zone][$k_banner]['info']['media_revenue_type']
                            == Campaign::REVENUE_TYPE_CPM) {
                            $data[$i][] = StatService::getEcpm(
                                $val['sum_payment'] + $val['sum_payment_gift'],
                                $val['sum_views']
                            );
                        } else {
                            $data[$i][] = 0;
                        }
                    }

                    if ($this->can('manager-ecpm')) {
                        $data[$i][] = StatService::getEcpm(
                            $val['sum_revenue'] + $val['sum_revenue_gift'],
                            $val['sum_views']
                        );//有权限
                    }
                    if ($this->can('manager-profit')) {
                        $data[$i][] = Formatter::asDecimal(
                            $val['sum_revenue'] - $val['sum_payment'] - $val['sum_payment_gift']
                        ); //有权限支出
                        $sum_data['profit'] +=
                            $val['sum_revenue'] - $val['sum_payment'] - $val['sum_payment_gift'];
                    }
                    if ($this->can('manager-profit_rate')) {
                        $data[$i][] = Formatter::asDecimal(
                            floatval($val['sum_revenue']) > 0 ? ($val['sum_revenue'] - $val['sum_payment'] -
                                    $val['sum_payment_gift']) / $val['sum_revenue'] * 100 : 0
                        ) . '%'; //有权限支出
                    }
                    $i ++;
                }
            }
        }
       
        $excelName = '广告主报表-'. str_replace('-', '', $param['period_start']) . '_' .
                str_replace('-', '', $param['period_end']);
        $sum_base = [
                '汇总',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
        ];

        $sum_arr = $this->getSummaryData($sum_data, $sum_base);
        array_push($data, $sum_arr);
        return self::downDailyReportCsv(Auth::user()->user_id, 'client', $start, $end, $item, $conditions, $data);
    }////@codeCoverageIgnoreEnd
    /**
     * 查看人工投放数据
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function manualData(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date' => 'required'
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //获取所有参数
        $affiliateId = $request->input('affiliateid', 0);
        $date = $request->input('date');
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认10
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1
        $search = $request->input('search');

        //报表权限
        $userId = null;//Auth::user()->user_id;

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('manual_deliverydata as manual')
            ->leftJoin('affiliates as af', 'af.affiliateid', '=', 'manual.affiliate_id')
            ->leftJoin('zones as z', 'z.zoneid', '=', 'manual.zone_id')
            ->leftjoin('campaigns as c', 'c.campaignid', '=', 'manual.campaign_id')
            ->leftjoin('appinfos as app', 'app.app_id', '=', 'c.campaignname')
            ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
            ->leftJoin('banners AS b', 'b.bannerid', '=', 'manual.banner_id')
            ->select(
                'af.name',
                'af.affiliateid',
                'z.zonename',
                'app.app_name',
                'p.name as product_name',
                'p.type',
                'c.ad_type',
                'c.revenue_type AS ad_revenue_type',
                'b.revenue_type AS af_revenue_type',
                'manual.views',
                'manual.clicks',
                'manual.conversions',
                'manual.expense',
                'manual.flag',
                'manual.action_flag',
                'manual.cpa',
                'manual.revenues AS revenue'
            )
            ->where('manual.date', '=', $date)
            ->where('af.agencyid', '=', Auth::user()->agencyid);
        if ($affiliateId) {
            $select->where('af.affiliateid', '=', $affiliateId);
        }
        if ($userId) {
            $select->where('af.creator_uid', '=', $userId);
        }

        if (!empty($search)) {
            $select->where(function ($query) use ($search) {
                $query->where('app.app_name', 'like', '%' . $search . '%')
                    ->orWhere('af.name', 'like', '%' . $search . '%');
            });
        }
        
        //$select->where('af.mode', Affiliate::MODE_ARTIFICIAL_DELIVERY);
        $select->orderBy('af.affiliateid', 'DESC');
        //===================分页==========================
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        //获取数据
        $rows = $select->get();
        //获取所有人工投放媒体
        $affiliates = Affiliate::where('mode', Affiliate::MODE_ARTIFICIAL_DELIVERY)
            ->select('affiliateid', 'name')
            ->get();
        if ($affiliates) {
            $affiliates = $affiliates->toArray();
        }
        return $this->success($affiliates, [
            'pageSize' => $pageSize,
            'count' => $total,
            'pageNo' => $pageNo,
        ], $rows);
    }

    /**
     * 查看广告主结算价数据
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function clientData(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date' => 'required',
                'platform' => 'required|integer',
                'product_id' => 'required|integer',
                'campaignid' => 'required|integer',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //获取所有参数
        $params = $request->all();
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认10
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1

        //报表权限
        $userId = null;//Auth::user()->user_id;取消初始化
//        if (empty($params['search'])) {
//            $this->initManualClientData($params['campaignid'], $params['date'], $userId);
//        }
        $select = DB::table('manual_clientdata as manual')
            ->leftJoin('affiliates as af', 'af.affiliateid', '=', 'manual.affiliate_id')
            ->leftjoin('campaigns as c', 'c.campaignid', '=', 'manual.campaign_id')
            ->leftjoin('appinfos as app', 'app.app_id', '=', 'c.campaignname')
            ->leftjoin('clients as ad', 'ad.clientid', '=', 'c.clientid')
            ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
            ->select(
                'af.name',
                'c.platform',
                'app.app_name',
                'p.name as product_name',
                'p.type',
                'c.ad_type',
                'manual.channel',
                'manual.cpa',
                'manual.consum'
            )
            ->where('manual.date', '=', $params['date'])
            ->where('af.agencyid', '=', Auth::user()->agencyid);
        if ($params['product_id']) {
            $select->where('p.id', '=', $params['product_id']);
        }
        if ($params['campaignid']) {
            $select->where('manual.campaign_id', '=', $params['campaignid']);
        }
        $platforms = array_keys(Campaign::getPlatformLabels(null, -1));
        if ($params['platform'] && in_array($params['platform'], $platforms)) {
            $select->where('c.platform', '=', $params['platform']);
        }
        if ($userId) {
            $select->where('ad.creator_uid', '=', $userId);
        }

        //===================搜索==========================
        if (!empty($params['search'])) {
            $select->where('app.app_name', 'like', "%{$params['search']}%");
        }

        //===================分页==========================
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        //默认排序
        $select->orderBy('af.affiliateid', 'DESC');

        //获取数据
        $rows = $select->get();

        return $this->success(null, [
            'pageSize' => $pageSize,
            'count' => $total,
            'pageNo' => $pageNo,
        ], $rows);
    }

    /**
     * 查看广告主结算价数据-产品
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function product(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date' => 'required',
                'platform' => 'required|integer',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $platform = $request->input('platform');
        $date = $request->input('date');
        //从 up_daily_campaigns 表中找到当日在投放的广告计划对应的产品，如果有就显示出来
        $date = isset($date) ? $date : date('Y-m-d', strtotime('-1 day'));
        $platforms = array_keys(Campaign::getPlatformLabels(null, -1));
        //权限控制
        $userId = null;//Auth::user()->user_id;

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('manual_clientdata AS dc')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'dc.campaign_id')
            ->leftJoin('products AS p', 'p.id', '=', 'c.product_id')
            ->select('p.id AS product_id', 'p.name')
            ->where('dc.date', $date);
        if ($userId) {
            $select = $select->leftJoin('clients AS adv', 'p.clientid', '=', 'adv.clientid')
                ->where('creator_uid', $userId);
        }
        if (in_array($platform, $platforms)) {
            $select = $select->where('c.platform', $platform);
        }
        $products = $select->distinct()->get();
        return $this->success($products);
    }

    /**
     * 查看广告主结算价数据-广告
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function campaigns(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date' => 'required',
                'platform' => 'required|integer',
                'product_id' => 'required|integer',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $platforms = array_keys(Campaign::getPlatformLabels(null, -1));
        $date = $request->input('date');
        $date = isset($date) ? $date : date('Y-m-d', strtotime('-1 day'));
        $platform = $request->input('platform', -1);
        $productId = $request->input('product_id');
        //权限控制
        $userId = null;//Auth::user()->user_id;

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('manual_clientdata AS dc')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'dc.campaign_id')
            ->leftJoin('appinfos AS app', 'c.campaignname', '=', 'app.app_id')
            ->leftJoin('products AS p', 'p.id', '=', 'c.product_id')
            ->select('c.campaignid', 'app.app_name')
            ->where('dc.date', $date);
        if (!empty($productId)) {
            $select = $select->leftJoin('products AS p', 'p.id', '=', 'c.product_id')
                ->where('p.id', $productId);
        }
        if (in_array($platform, $platforms)) {
            $select = $select->where('c.platform', $platform);
        }
        if ($userId) {
            $select = $select->leftJoin('clients AS adv', 'p.clientid', '=', 'adv.clientid')
                ->where('creator_uid', $userId);
        }
        $campaigns = $select->distinct()->get();
        return $this->success($campaigns);
    }

    /**
     * 导入人工数据
     * @return \Illuminate\Http\Response
     *///@codeCoverageIgnoreStart
    public function manualImport(Request $request)
    {
        set_time_limit(0);
        $excel = input::file('file', null);
        //获取导入EXCEL数据
        $importExcelData = Excel::load($excel)->getSheet(0)->toArray();
        $count = count($importExcelData);
        //EXCEL没有数据
        if ($count <= 1) {
            return $this->errorCode(5090);
        }
        //类型A2A，C2C，S2S等格式
        $dataType = $request->type;
        $preData = array_keys(config('biddingos.preData'));
        if (!in_array($dataType, $preData)) {
            return $this->errorCode(5091);
        }
        $result = ManualService::import($count, $dataType, $importExcelData);
        if (0 == $result['errorCode']) {
            return $this->success();
        } else {
            return $this->errorCode($result['errorCode'], $result['message']);
        }
    }//@codeCoverageIgnoreEnd

    /**
     * 获取要插入广告主结算表(up_manual_clientdata)数据
     * @param $campaignName
     * @param $clientName
     * @param $affiliateName
     * @param $date
     * @param $channel
     * @return mixed
     */
    private function getClientDataToInsert($campaignName, $clientName, $affiliateName, $date, $channel)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        if ($this->validateAffiliateMode($affiliateName)) {
            $select = DB::table('manual_deliverydata as umd')
                ->join('campaigns as c', 'c.campaignid', '=', 'umd.campaign_id')
                ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
                ->join('clients as adv', 'adv.clientid', '=', 'c.clientid')
                ->join('banners as b', function ($join) {
                    $join->on('b.bannerid', '=', 'umd.banner_id')
                        ->on('b.affiliateid', '=', 'umd.affiliate_id');
                })->join('affiliates as aff', 'aff.affiliateid', '=', 'b.affiliateid')
                ->select(
                    'aff.name',
                    'umd.affiliate_id as affiliateid',
                    'umd.banner_id as bannerid',
                    'umd.campaign_id as campaignid',
                    'app.app_name'
                )
                ->where('adv.clientname', $clientName)
                ->where('app.app_name', $campaignName)
                ->where('umd.date', $date)
                ->where('aff.name', $affiliateName);
            if ($this->getProductType($campaignName, $clientName) == Product::TYPE_APP_DOWNLOAD) {
                $select = $select->leftJoin('attach_files as af', 'af.id', '=', 'b.attach_file_id')
                    ->where('af.channel', $channel);
            }
        } else {
            $select = DB::table('data_hourly_daily_client AS dc')
                ->leftJoin('banners AS b', 'b.bannerid', '=', 'dc.ad_id')
                ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'dc.campaign_id')
                ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
                ->leftJoin('affiliates AS a', 'b.affiliateid', '=', 'a.affiliateid')
                ->leftJoin('clients AS adv', 'adv.clientid', '=', 'dc.clientid')
                ->select(
                    'a.name',
                    'b.affiliateid',
                    'b.bannerid',
                    'dc.campaign_id as campaignid',
                    'app.app_name'
                )
                ->where('dc.day_time', $date)
                ->where('app.app_name', $campaignName)
                ->where('a.name', $affiliateName)
                ->where('adv.clientname', $clientName);
            if ($this->getProductType($campaignName, $clientName) == Product::TYPE_APP_DOWNLOAD) {
                $select = $select->where('af.channel', $channel)->addSelect('af.channel');
            }
        }
        $data = $select->first();
        return $data;
    }

    /**判断媒体商是否创建了广告位
     * @param $affiliateName
     * @return bool
     */
    private function validateAffiliateHasZone($affiliateName)
    {
        if ($affiliateName == '') {
            return false;
        }
        $count = DB::table('zones')
            ->leftJoin('affiliates', 'zones.affiliateid', '=', 'affiliates.affiliateid')
            ->where('affiliates.name', $affiliateName)->count();
        return $count > 0 ? true : false;
    }

    /**
     * 判断广告在指定的日期在媒体商上是否有投放数据
     * @param $campaignName
     * @param $clientName
     * @param $channel
     * @param $affiliateName
     * @param $date
     * @return bool
     */
    private function validateCampaignDateAffiliateDelivery($campaignName, $clientName, $channel, $affiliateName, $date)
    {
        if ($campaignName == '' || $clientName == '' || $affiliateName == '' || $date == '') {
            return false;
        }
        $select = DB::table('data_hourly_daily_client AS dc')
            ->leftJoin('affiliates AS a', 'dc.affiliateid', '=', 'a.affiliateid')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'dc.campaign_id')
            ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
            ->leftJoin('banners AS b', 'b.bannerid', '=', 'dc.bannerid')
            ->leftJoin('clients AS adv', 'adv.clientid', '=', 'dc.clientid')
            ->where('dc.date', $date)
            ->where('app.app_name', $campaignName)
            ->where('a.name', $affiliateName)
            ->where('adv.clientname', $clientName);
        if ($this->getProductType($campaignName, $clientName) == Product::TYPE_APP_DOWNLOAD) {
            $select = $select->leftJoin('attach_files as af', 'af.id', '=', 'b.attach_file_id')
                ->where('af.channel', $channel);
        }
        $count = $select->count();
        return $count > 0 ? true : false;
    }
    /**
     * 获取导入广告的产品类型
     * @param $campaignName
     * @return mixed
     */
    private function getProductType($campaignName, $clientName)
    {
        $productType = DB::table('campaigns as c')
            ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
            ->leftJoin('clients as adv', 'c.clientid', '=', 'adv.clientid')
            ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
            ->where('app.app_name', $campaignName)
            ->where('adv.clientname', $clientName)
            ->pluck('p.type');
        return $productType;
    }

    /**
     * 判断是否已录入转化量、广告主消耗
     * @param $clientName
     * @param $campaignName
     * @param $date
     * @param $affiliateName
     * @param $channel
     * @return bool
     */
    private function hasClientCPAComSum($clientName, $campaignName, $date, $affiliateName, $channel)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('manual_clientdata AS umc')
            ->join('campaigns AS c', 'c.campaignid', '=', 'umc.campaign_id')
            ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
            ->join('clients as adv', 'adv.clientid', '=', 'c.clientid')
            ->join('banners as b', function ($join) {
                $join->on('b.bannerid', '=', 'umc.banner_id')
                    ->on('b.affiliateid', '=', 'umc.affiliate_id');
            })->join('affiliates as aff', 'aff.affiliateid', '=', 'b.affiliateid')
            ->select('umc.cpa', 'umc.consum')
            ->where('adv.clientname', $clientName)
            ->where('app.app_name', $campaignName)
            ->where('umc.date', $date)
            ->where('aff.name', $affiliateName);

        if ($this->getProductType($campaignName, $clientName) == Product::TYPE_APP_DOWNLOAD) {
            $select = $select->leftJoin('attach_files as af', 'af.id', '=', 'b.attach_file_id')
                ->where('af.channel', $channel);
        }
        $data = $select->first();
        if (count($data) > 0) {
            if ($data['cpa'] > 0 || $data['consum'] > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 导出每日报表单独生成csv文件，每小时重新生成一个文件
     * @param $userId
     * @param $obj
     * @param $start
     * @param $end
     * @param $column
     * @param $conditions
     * @param $data
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function downDailyReportCsv($userId, $obj, $start, $end, $column, $conditions, $data)
    {
        $fileInfos = self::generateDailyReportFileName($userId, $obj, $start, $end, $column, $conditions);
        $file = $fileInfos[0];
        $fileShowName = $fileInfos[1];
        $headers = $fileInfos[2];
        if (file_exists($file)) {
            return response()->download($file, $fileShowName, $headers);
            exit;
        }
        $fp = fopen($file, "w");//文件被清空后再写入
        if ($fp) {
            fputcsv($fp, $column);
            $title = implode(',', $column);
            $dataStr = '';
            $dataStr .= iconv('utf-8', 'gbk', $title) ."\n";
            foreach ($data as $k => $row) {
                $tmpStr = '';
                $tmpStr = implode("___", $row);
                $tmpStr = rtrim($tmpStr, '___');
                $tmpStr = str_replace('•', '', $tmpStr);
                $tmpStr = iconv('utf-8', 'gbk', $tmpStr);
                $tmpStr = str_replace(',', '', $tmpStr);
                $tmpStr = str_replace('___', ',', $tmpStr);
                $dataStr .= $tmpStr;
                $dataStr .= "\n";
    
            }
            file_put_contents($file, $dataStr);
        }
        fclose($fp);
        return response()->download($file, $fileShowName, $headers);
        exit;
    }
    
    /**
     * 生成导出每日报表的文件名等信息
     * @param int $userId
     * @param string $obj {'affiliate', 'client'}
     * @param date $start
     * @param date $end
     * @param $column 列名
     * @param array $bannerConditions 其他查询条件
     * @return array
     */
    public static function generateDailyReportFileName($userId, $obj, $start, $end, $column, $bannerConditions)
    {
        $path = storage_path('report/');
        $conditions = implode('', $bannerConditions);
        $fileName = $obj. $userId. str_replace('-', '', $start). str_replace('-', '', $end) . count($column).
        $conditions . date("YmdH") . '.csv';
        switch ($obj) {
            case 'affiliate':
                $fileShowName = '媒体商每日报表-'.
                    str_replace('-', '', $start).str_replace('-', '', $end) . '.csv';
                break;
            case 'client':
                $fileShowName = '广告主每日报表-'.
                    str_replace('-', '', $start).str_replace('-', '', $end) . '.csv';
                break;
        }
        $ops = array(
            //'accept-length' => filesize($file),
            'Content-type' => 'text/csv',
            'Cache-Control'=>'must-revalidate,post-check=0,pre-check=0',
            'Expires'=>'0',
            'Pragma'=>'public',
        );
        $file = $path.$fileName;
        return [$file, $fileShowName, $ops];
    }

    /**
     * 判断广告在媒体上，是否已录入点击量/下载量、支出
     * @param $clientName
     * @param $campaignName
     * @param $date
     * @param $affiliateName
     * @param $channel
     * @return bool
     */
    private function hasDeliveryData($clientName, $campaignName, $date, $affiliateName, $channel)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('manual_deliverydata as umd')
            ->join('campaigns as c', 'c.campaignid', '=', 'umd.campaign_id')
            ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
            ->join('clients as adv', 'adv.clientid', '=', 'c.clientid')
            ->join('banners as b', function ($join) {
                $join->on('b.bannerid', '=', 'umd.banner_id')
                    ->on('b.affiliateid', '=', 'umd.affiliate_id');
            })
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'b.affiliateid')
            ->select('umd.conversions', 'umd.clicks', 'umd.expense')
            ->where('adv.clientname', $clientName)
            ->where('app.app_name', $campaignName)
            ->where('umd.date', $date)
            ->where('adv.agencyid', Auth::user()->agencyid)
            ->where('aff.name', $affiliateName);
        if ($this->getProductType($campaignName, $clientName) == Product::TYPE_APP_DOWNLOAD) {
            $select = $select->leftJoin('attach_files as af', 'af.id', '=', 'b.attach_file_id')
                ->where('af.channel', $channel);
        }
        $data = $select->first();
        if (count($data) > 0) {
            if ($data['conversions'] > 0 || $data['clicks'] > 0 || $data['expense'] > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     *判断广告位是否是流量广告位
     * @param $zoneId
     * @return bool
     */
    private function isFlowZone($zoneId)
    {
        if ($zoneId == '' || !is_numeric($zoneId)) {
            return false;
        }
        $count = Zone::where('zoneid', $zoneId)
            ->where('type', Zone::TYPE_FLOW)
            ->count();
        return $count > 0 ? true : false;
    }
    
    /**
     * 验证结算数据，如CPA，广告主消耗等
     * @param $CPA
     * @param $conSum
     * @return bool
     */
    private function validateClientData($CPA, $conSum)
    {
        if (!is_numeric($CPA) || !is_numeric($conSum)) {
            return false;
        } elseif ($CPA < 0 || $conSum < 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 验证导入excel中的广告主是否创建
     * @param $clientName
     * @return bool
     */
    private function validateClient($clientName)
    {
        if ($clientName == '') {
            return false;
        }
        $count = Client::where('clientname', $clientName)
                ->where('agencyid', Auth::user()->agencyid)
                ->count();
        return $count > 0 ? true : false;
    }

    /**
     * 验证导入excel中的广告位是否创建
     * @param $zoneId
     * @return bool
     */
    private function validateZone($zoneId)
    {
        if ($zoneId == '' || !is_numeric($zoneId)) {
            return false;
        }
        $count = Zone::where('zoneid', $zoneId)->count();
        return $count > 0 ? true : false;
    }

    /**
     * 格式化导入提示信息
     * @param $code
     * @param $k
     * @param $i
     * @return string
     */
    private function formatWaring($code, $k, $i, $date = null)
    {
        $msg = Config::get('error');
        if (empty($date)) {
            return sprintf($msg[$code], $k, $i);
        } else {
            return sprintf($msg[$code], $k, $i, $date);
        }
    }

    /**
     * 获取广告主excel
     * @param $importExcelData
     * @param $i
     * @return array
     */
    private function getClientExcelData($importExcelData, $i)
    {
        return [
            'clientName' => trim($importExcelData[$i][0]),
            'campaignName' => trim($importExcelData[$i][1]),
            'date' => trim($importExcelData[$i][2]),
            'affiliateName' => trim($importExcelData[$i][3]),
            'channel' => trim($importExcelData[$i][4]),
            'cpa' => $importExcelData[$i][5],
            'conSum' => $importExcelData[$i][6],
        ];
    }

    /**
     * 获取没有权限的字段
     * @param $field
     * @return array
     */
    private function getNoPermissionField($field)
    {
        $NoPermission = [];
        foreach ($field as $val) {
            if (!$this->can('manager-'. $val)) {
                $NoPermission[] = $val;
            }
        }
        return $NoPermission;
    }
    
    
    private function getSummaryData($sum_data, $sum_base)
    {
        $sum_arr = $sum_base;
        if ($this->can('manager-sum_views')) {
            $sum_arr[] = $sum_data['sum_views'];
        }
        if ($this->can('manager-sum_cpc_clicks')) {
            $sum_arr[] = $sum_data['sum_cpc_clicks'];
        }
        if ($this->can('manager-sum_download_requests')) {
            $sum_arr[] =  $sum_data['sum_download_requests'];
        }
        if ($this->can('manager-sum_download_complete')) {
            $sum_arr[] =  $sum_data['sum_download_complete'];
        }

        if ($this->can('manager-sum_clicks')) {
            $sum_arr[] =  $sum_data['sum_clicks'];
        }

        if ($this->can('manager-sum_cpa')) {
            $sum_arr[] = $sum_data['sum_cpa'];
        }

        if ($this->can('manager-ctr')) {
            $sum_arr[] = '--';
        }
        if ($this->can('manager-cpc_ctr')) {
            $sum_arr[] = '--';
        }
        if ($this->can('manager-sum_revenue_client')) {
            $sum_arr[] = Formatter::asDecimal($sum_data['sum_revenue_client']);
        }
        if ($this->can('manager-sum_revenue')) {
            $sum_arr[] = Formatter::asDecimal($sum_data['sum_revenue']);
        }
        if ($this->can('manager-sum_revenue_gift')) {
            $sum_arr[] = Formatter::asDecimal($sum_data['sum_revenue_gift']);
        }

        $sum_arr = array_merge($sum_arr, ['--']);
        if ($this->can('manager-sum_payment_trafficker')) {
            $sum_arr[] = Formatter::asDecimal($sum_data['sum_payment_trafficker']);
        }
        if ($this->can('manager-sum_payment')) {
            $sum_arr[] = Formatter::asDecimal($sum_data['sum_payment']);
        }
        if ($this->can('manager-sum_payment_gift')) {
            $sum_arr[] = Formatter::asDecimal($sum_data['sum_payment_gift']);
        }

        $sum_arr[] = '--';

        if ($this->can('manager-cpd')) {
            $sum_arr[] = '--';
            ;
        }
        if ($this->can('manager-media_cpd')) {
            $sum_arr[] = '--';
            ;
        }
        if ($this->can('manager-ecpm')) {
            $sum_arr[] = '--';
        }
        if ($this->can('manager-profit')) {
            $sum_arr[] = Formatter::asDecimal($sum_data['profit']);
            ;
        }
        if ($this->can('manager-profit_rate')) {
            $sum_arr[] = '--';
        }
        return $sum_arr;
    }

    /**
     * 媒介概览-获取30天及七天的趋势
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | type | integer | 0:30天趋势 1:7天趋势  |  |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | sub name | sub name | type | description | restraint | required |
     * | :--: | :--: | :--: | :--: | :--------: | :-------: | :-----: |
     * | payment |  |  |  |  | 媒体商收入 |  | 是 |
     * | | summary |  |  |  |  |  | 是 |
     * | |  | time |  |  | 时间 |  | 是 |
     * | |  | payment |  |  | 媒体商收入 |  | 是 |
     * | | data |  |  |  |  |  | |
     * | |  | affiliateid |  |  | 媒体商id |  | 是 |
     * | |  | brief_name |  |  | 媒体商简称 |  | 是 |
     * | |  | child |  |  |  |  | |
     * | |  |  | time |  | 时间 |  | 是 |
     * | |  |  | payment |  | 媒体商收入 |  | 是 |
     * | views |  |  |  |  | 展示量 |  | 是 |
     * | | summary |  |  |  |  |  | 是 |
     * | |  | time |  |  | 时间 |  | 是 |
     * | |  | views |  |  | 展示量 |  | 是 |
     * | | data |  |  |  |  |  | |
     * | |  | affiliateid |  |  | 媒体商id |  | 是 |
     * | |  | brief_name |  |  | 媒体商简称 |  | 是 |
     * | |  | child |  |  |  |  | |
     * | |  |  | time |  | 时间 |  | 是 |
     * | |  |  | views |  | 展示量 |  | 是 |
     * | clicks |  |  |  |  | 下载量 |  | 是 |
     * | | summary |  |  |  |  |  | 是 |
     * | |  | time |  |  | 时间 |  | 是 |
     * | |  | clicks |  |  | 下载量 |  | 是 |
     * | | data |  |  |  |  |  | |
     * | |  | affiliateid |  |  | 媒体商id |  | 是 |
     * | |  | brief_name |  |  | 媒体商简称 |  | 是 |
     * | |  | child |  |  |  |  | |
     * | |  |  | time |  | 时间 |  | 是 |
     * | |  |  | clicks |  | 下载量 |  | 是 |
     * | cpc_clicks |  |  |  |  | 点击量 |  | 是 |
     * | |  summary |  |  |  |  |  | |
     * | |  | time |  |  | 时间 |  | 是 |
     * | |  | cpc_clicks |  |  | 点击量 |  | 是 |
     * | | data |  |  |  |  |  | |
     * | |  | affiliateid |  |  | 媒体id |  | 是 |
     * | |  | brief_name |  |  | 媒体简称 |  | 是 |
     * | |  | child |  |  |  |  | |
     * | |  |  | time |  | 时间 |  | 是 |
     * | |  |  | cpc_clicks |  | 点击量 |  | 是 |
     */
    public function traffickerTrend(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required|in:0,1',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $type = Input::get('type');
        if (0 == $type) {
            $start = date('Y-m-d', strtotime("-30 days"));
        } else {
            $start = date('Y-m-d', strtotime("-7 days"));
        }
        $end = date('Y-m-d', strtotime("-1 days"));

        $label = ['payment','clicks', 'cpc_clicks', 'views'];
        $table = 'data_hourly_daily_af';
        $user = Auth::user();
        $creator_uid = $user->user_id;
        $agencyId = $user->agencyid;
        if ($this->can('manager-pub-all')) {
            $res = StatService::getTrendData($start, $end, $table, $agencyId, 0, 1);
        } elseif ($this->can('manager-pub-self')) {
            $res = StatService::getTrendData($start, $end, $table, $agencyId, 0, 1, $creator_uid);
        } else {
            return $this->errorCode(5020);
        }
        //重组数据
        $list = StatService::recombinantData($label, $res);
        return $this->success(
            [
                'start' => $start,
                'end' =>$end
            ],
            null,
            $list
        );
    }

    /**
     * 媒体概览-30天趋势日活日新增及次日留存
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | type | integer | 0:30天趋势 1:7天趋势  |  |  |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * |name | sub_name | type | description | restraint  | required |
     * | :--: | :--: | :--: | :--------: | :-------: | :-----: |
     * | daily_active |  |  | 日活 |  | |
     * | daily_new |  |  | 日新增 |  | |
     * | daily_retain |  |  | 次日留存 |  | |
     * | | affiliateid | integer | 广告计划id |  | 是 |
     * | | brief_name | string | 媒体商简称 |  | 是 |
     * | | date | string | 时间 |  | 是 |
     * | | num | decimal | 新增数量 |  | 是 |
     * | | affiliateid | integer | 广告计划id |  | 是 |
     * | | brief_name | string | 媒体商简称 |  | 是 |
     * | |  date | string | 时间 |  | 是 |
     * | |  num | decimal | 新增数量 |  | 是 |
     * | | affiliateid | integer | 广告计划id |  | 是 |
     * | | brief_name | string | 媒体商简称 |  | 是 |
     * | |  date | string | 时间 |  | 是 |
     * | |  num | decimal | 新增数量 |  | 是 |
     */
    public function traffickerDaily(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required|in:0,1',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $type = Input::get('type');
        if (0 == $type) {
            $start = date('Y-m-d', strtotime("-30 days"));
        } else {
            $start = date('Y-m-d', strtotime("-7 days"));
        }
        $end = date('Y-m-d', strtotime("-1 days"));
        $agencyId = Auth::user()->agencyid;
        if ($this->can('manager-pub-all')) {
            $list['daily_new'] = $this->getTraffickerDaily(
                $start,
                $end,
                AffiliateUserReport::DAILY_NEW,
                $agencyId
            )['num'];
            $list['daily_active'] = $this->getTraffickerDaily(
                $start,
                $end,
                AffiliateUserReport::DAILY_ACTIVE,
                $agencyId
            )['num'];
            $list['daily_retain'] = $this->getTraffickerDaily(
                $start,
                $end,
                AffiliateUserReport::DAILY_RETAIN,
                $agencyId
            )['num'];
        } elseif ($this->can('manager-pub-self')) {
            $creator_uid = Auth::user()->user_id;
            $list['daily_new'] = $this->getTraffickerDaily(
                $start,
                $end,
                AffiliateUserReport::DAILY_NEW,
                $agencyId,
                $creator_uid
            )['num'];
            $list['daily_active'] = $this->getTraffickerDaily(
                $start,
                $end,
                AffiliateUserReport::DAILY_ACTIVE,
                $agencyId,
                $creator_uid
            )['num'];
            $list['daily_retain'] = $this->getTraffickerDaily(
                $start,
                $end,
                AffiliateUserReport::DAILY_RETAIN,
                $agencyId,
                $creator_uid
            )['num'];
        } else {
            return $this->errorCode(5020);
        }

        return $this->success(
            [
                'start' => $start,
                'end' =>$end
            ],
            null,
            $list
        );
    }

    /**
     * 媒介概览-七日留存
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | date | date | 日期  |  |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint  | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid | integer | 广告计划id |  | 是 |
     * | brief_name | string | 媒体商简称 |  | 是 |
     * | span | string | 1 :第一日留存 2：第二日留存|  | 是 |
     * | num | decimal | 留存率 |  | 是 |
     */
    public function traffickerWeekRetain(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $date =  Input::get('date');
        if ($this->can('manager-pub-all')) {
            $creator_uid = 0;
        } elseif ($this->can('manager-pub-self')) {
            $creator_uid = Auth::user()->user_id;
        } else {
            return $this->errorCode(5020);
        }
        $agencyId = Auth::user()->agencyid;
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = DB::getTablePrefix();
        $query = DB::table('affiliates_user_report as r')
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'r.affiliateid')
            ->select(
                DB::raw("ROUND({$prefix}r.num/100, 2) as num"),
                'r.affiliateid',
                'aff.brief_name',
                'r.date',
                'r.span'
            )
            ->where('r.date', $date)
            ->where('aff.agencyid', $agencyId)
            ->where('r.type', AffiliateUserReport::DAILY_RETAIN);
        if ($creator_uid) {
            $query->where('aff.creator_uid', $creator_uid);
        }
        $res = $query->get();
        $list = [];

        if (!empty($res)) {
            foreach ($res as $val) {
                if (isset($list['summary'][$val['span']])) {
                    $list['summary'][$val['span']]['num'] += $val['num'];
                } else {
                    $list['summary'][$val['span']] = [
                        'span' => $val['span'],
                        'num' => $val['num']
                    ];
                }
                $list['data'][$val['affiliateid']]['child'][$val['span']] = [
                    'num' => $val['num'],
                    'span' => $val['span']
                ];
                $list['data'][$val['affiliateid']]['brief_name'] = $val['brief_name'];
            }
            for ($i = 1; $i <= 7; $i++) {
                $list['summary'] = array_add($list['summary'], $i, [
                    'span' => $i,
                    'num' => 0
                ]);
            }
            ksort($list['summary']);
            $list['summary'] = array_values($list['summary']);
            $list['summary'] = array_slice($list['summary'], 0, 7);
            foreach ($list['data'] as $rows => $value) {
                for ($i = 1; $i <= 7; $i++) {
                    $list['data'][$rows]['child']= array_add($list['data'][$rows]['child'], $i, [
                        'span' => $i,
                        'num' => 0
                    ]);
                }
                ksort($list['data'][$rows]['child']);
                $list['data'][$rows]['child'] = array_values($list['data'][$rows]['child']);
                $list['data'][$rows]['child'] = array_slice($list['data'][$rows]['child'], 0, 7);
            }
        }
        return $this->success(
            ['date' => $date],
            null,
            $list
        );
    }

    /**
     * 媒介概览-月活及月新增
     * @return \Illuminate\Http\Response
     * | name | sub_name | type | description | restraint  | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | month_new |  |  |  |  | |
     * | | affiliateid | integer | 广告计划id |  | 是 |
     * | | brief_name | string | 媒体商简称 |  | 是 |
     * | | date | string | 日期 |  | 是 |
     * | | num | decimal | 留存率 |  | 是 |
     * | month_active |  |  |  |  | |
     * | | affiliateid | integer | 广告计划id |  | 是 |
     * | | brief_name | string | 媒体商简称 |  | 是 |
     * | | date | string | 日期 |  | 是 |
     * | | num | decimal | 留存率 |  | 是 |
     */
    public function traffickerMonth()
    {
        $start = date('Y-m-01', strtotime("-11 month"));
        $end = date('Y-m-d');
        if ($this->can('manager-pub-all')) {
            $creator_uid = 0;
        } elseif ($this->can('manager-pub-self')) {
            $creator_uid = Auth::user()->user_id;
        } else {
            return $this->errorCode(5020);
        }
        $agencyId = Auth::user()->agencyid;
        $month_new = $this->getTraffickerMonth(
            $start,
            $end,
            AffiliateUserReport::DAILY_NEW,
            $agencyId,
            $creator_uid
        );
        $month_active = $this->getTraffickerMonth(
            $start,
            $end,
            AffiliateUserReport::DAILY_ACTIVE,
            $agencyId,
            $creator_uid
        );
        $list['month_new'] = StatService::recombinantData(['num'], $month_new)['num'];
        $list['month_active'] = StatService::recombinantData(['num'], $month_active)['num'];
        return $this->success(
            [
                'start' => $start,
                'end' => $end
            ],
            null,
            $list
        );
    }
    public function getTraffickerMonth($start, $end, $type, $agencyId, $creator_uid = 0)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $real_table = DB::getTablePrefix() . 'affiliates_user_report';
        $query = DB::table('affiliates_user_report')
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'affiliates_user_report.affiliateid')
            ->select(
                DB::raw("SUM($real_table.num) as num"),
                'aff.affiliateid',
                'aff.brief_name',
                DB::raw("DATE_FORMAT({$real_table}.`date`,'%Y-%m') AS date")
            )
            ->whereBetween('affiliates_user_report.date', [$start, $end])
            ->where('affiliates_user_report.type', $type)
            ->where('aff.agencyid', $agencyId)
            ->groupBy('date', 'aff.affiliateid');
        if ($creator_uid) {
            $query->where('aff.creator_uid', $creator_uid);
        }
        $res = $query->get();
        return $res;
    }

    /**
     * 根据时间、类型筛选出日活、日新增、及次日留存
     * @param $start
     * @param $end
     * @param $type
     * @param $agencyId
     * @param int $creator_uid
     * @return array
     */
    public function getTraffickerDaily($start, $end, $type, $agencyId, $creator_uid = 0)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = \DB::getTablePrefix();
        $query = DB::table('affiliates_user_report as r')
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'r.affiliateid')
            ->select(
                'r.num',
                'r.affiliateid',
                'r.date',
                'aff.brief_name'
            )
            ->whereBetween('r.date', [$start, $end])
            ->where('aff.agencyid', $agencyId)
            ->where('r.type', $type);
        if ($creator_uid) {
            $query->where('aff.creator_uid', $creator_uid);
        }
        if ($type == AffiliateUserReport::DAILY_RETAIN) {
            $query->addSelect(DB::raw("ROUND({$prefix}r.num/100, 2) as num"))
                ->where('r.span', AffiliateUserReport::FIRST_RETAIN);
        }
        $res = $query->get();
        $label = ['num'];
        $list = StatService::recombinantData($label, $res);
        return $list;
    }

    /**
     * 获取销售概览-30天趋势
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | type | integer | 0:30天趋势 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | sub name | type | description | restraint | required |
     * | :--: | :--: | :--: | :--: | :--------: | :-------: | :-----: |
     * | summary |  |  |  |  |  | 是 |
     * | | date |  |  | 时间 |  | 是 |
     * | | sum_revenue |  |  | 广告主总消耗 |  | 是 |
     * | | sum_clicks |  |  | 下载量 |  | 是 |
     * | | sum_cpc_clicks |  |  | 点击量 |  | 是 |
     * | | sum_cpa |  |  | cpa量 |  | 是 |
     * |  data |  |  |  |  |  | |
     * | | campaignid |  |  | 广告id |  | 是 |
     * | | app_name |  |  | 媒体商简称 |  | 是 |
     * | | app_show_icon |  |  | icon |  | 是 |
     * | | revenue_type |  |  | 广告类型 |  | 是 |
     * | | child |  |  |  |  | |
     * | |  | date |  | 时间 |  | 是 |
     * | |  | sum_revenue |  | 广告主总消耗 |  | 是 |
     * | |  | sum_clicks |  | 下载量 |  | 是 |
     * | |  | sum_cpc_clicks |  | 点击量 |  | 是 |
     * | |  | sum_cpa |  | cpa量 |  | 是 |
     */
    public function saleTrend(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required|in:0,1',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $type = Input::get('type');
        if (0 == $type) {
            $start = date('Y-m-d', strtotime("-30 days"));
        } else {
            $start = date('Y-m-d', strtotime("-7 days"));
        }
        $end = date('Y-m-d', strtotime("-1 days"));
        $agencyId = Auth::user()->agencyid;
        if ($this->can('manager-bd-all')) {
            $list = $this->getSaleTrend($start, $end, $agencyId);
        } elseif ($this->can('manager-bd-self')) {
            $creator_uid = Auth::user()->user_id;
            $list = $this->getSaleTrend($start, $end, $agencyId, $creator_uid);
        } else {
            return $this->errorCode(5020);
        }

        return $this->success(
            [
                'start' => $start,
                'end' =>$end
            ],
            null,
            $list
        );
    }

    /**
     * @param $start
     * @param $end
     * @param int $creator_uid
     * @return array
     */
    private function getSaleTrend($start, $end, $agencyId, $creator_uid = 0)
    {
        $prefix = \DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        //获取下载量、点击量等
        $query = \DB::table('data_hourly_daily_client as h')
            ->join('campaigns as c', 'c.campaignid', '=', 'h.campaign_id')
            ->join('clients as cli', 'c.clientid', '=', 'cli.clientid')
            ->join('appinfos as app', function ($join) {
                $join->on('c.campaignname', '=', 'app.app_id')
                    ->on('c.platform', '=', 'app.platform')
                    ->on('cli.agencyid', '=', 'app.media_id');
            })
            ->select(
                DB::raw('IFNULL(SUM(' .$prefix . 'h.conversions),0) as sum_clicks'), //下载量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.clicks),0) as sum_cpc_clicks'), //点击量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.total_revenue),0) as sum_revenue'), //广告主消耗
                DB::raw('IFNULL(SUM(' .$prefix . 'h.cpa),0) as sum_cpa'), //cpa量
                'h.date',
                'app.app_name',
                'app.app_show_icon',
                'c.revenue_type',
                'h.campaign_id'
            )
            ->whereBetween('h.date', [$start, $end])
            ->where('cli.agencyid', $agencyId)
            ->where('cli.affiliateid', 0)
            ->groupBy('h.date', 'h.campaign_id');
        //区分权限 bd-self及bd-all
        if ($creator_uid) {
            $query->where('cli.creator_uid', $creator_uid);
        }
        $res = $query->get();

        $list = [];
        $label = ['sum_clicks', 'sum_revenue', 'sum_cpc_clicks', 'sum_cpa'];
        //$list['summary']按照每天数汇总数据 ；$list['data'] 按照每个广告每天重组数据
        if (sizeof($res) > 0) {
            foreach ($res as $val) {
                if (isset($list['summary'][$val['date']])) {
                    foreach ($label as $key) {
                        $list['summary'][$val['date']][$key] += $val[$key];
                    }
                } else {
                    $list['summary'][$val['date']]['date'] = $val['date'];
                    foreach ($label as $key) {
                        $list['summary'][$val['date']][$key] = $val[$key];
                    }
                }
                if (!isset($list['data'][$val['campaign_id']])) {
                    $list['data'][$val['campaign_id']] = [
                        'campaignid' => $val['campaign_id'],
                        'app_name' => $val['app_name'],
                        'app_show_icon' => $val['app_show_icon'],
                        'revenue_type' => $val['revenue_type'],
                        'sum_revenue' => $val['sum_revenue']
                    ];
                } else {
                    $list['data'][$val['campaign_id']]['sum_revenue'] += $val['sum_revenue'];
                }
                $list['data'][$val['campaign_id']]['child'][] = [
                    'sum_clicks' => $val['sum_clicks'],
                    'sum_cpc_clicks' => $val['sum_cpc_clicks'],
                    'sum_revenue' => $val['sum_revenue'],
                    'sum_cpa' => $val['sum_cpa'],
                    'date' => $val['date'],
                ];
            }
        }
        if (!empty($list['summary'])) {
            $list['summary'] = array_values($list['summary']);
        }
        //按照广告消耗排序
        $sum_revenue = [];
        if (!empty($list['data'])) {
            foreach ($list['data'] as $key => $val) {
                $sum_revenue[$key] = $val['sum_revenue'];
            }
            array_multisort($sum_revenue, SORT_DESC, $list['data']);
        }
        return $list;
    }

    /**
     * 获取销售概览-排行数据
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function saleRank(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date_type'=>'required|in:0,1,2,3,4,5,6',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        //获取日期类型
        $type = $request->input('date_type');
        $time = $this->getRankTime($type);
        $agencyId = Auth::user()->agencyid;
        $table = 'data_hourly_daily_client';
        if ($this->can('manager-bd-all')) {
            $row = StatService::findSaleRankData($time['start'], $time['end'], $table, $agencyId);
            $compare = StatService::findSaleRankData($time['startTime'], $time['endTime'], $table, $agencyId);
        } elseif ($this->can('manager-bd-self')) {
            $creator_uid = Auth::user()->user_id;
            $row = StatService::findSaleRankData($time['start'], $time['end'], $table, $agencyId, $creator_uid);
            $compare = StatService::findSaleRankData(
                $time['startTime'],
                $time['endTime'],
                $table,
                $agencyId,
                $creator_uid
            );
        } else {
            return $this->errorCode(5020);
        }
        $list = [];
        if (!empty($row)) {
            if (6 ==$type) {
                foreach ($row as $item) {
                    $item['ranking'] = 0;
                    $list[] = $item;
                }
            } else {
                $list = $this->calculateRank($row, $compare);
            }
        }

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (10 > $k) {
                    $data[$k] = $v;
                } elseif (10 <= $k) {
                    if (isset($data['other'])) {
                        $data['other']['sum_revenue'] += $v['sum_revenue'];
                    } else {
                        $data['other']['app_name'] = '其他';
                        $data['other']['sum_revenue'] = $v['sum_revenue'];
                    }
                }
            }
        } else {
            $data = [];
        }
        if (!empty($data)) {
            $data = array_values($data);
        }
        return $this->success(null, null, $data);

    }
    public function calculateRank($rows, $compare)
    {
        if (!empty($compare)) {
            foreach ($compare as $ke => $va) {
                $newData[] = $va['campaign_id'];
            }
        }
        foreach ($rows as $k => $v) {
            //如果之前没有，则表示排名是上升
            if (!empty($newData)) {
                if (in_array($v['campaign_id'], $newData)) {
                    foreach ($newData as $key => $val) {
                        //如果存在
                        if ($v['campaign_id'] == $val) {
                            if ($k == $key) {
                                $rows[$k]['ranking'] = 0;
                            } elseif ($k < $key) {
                                $rows[$k]['ranking'] = 1;
                            } else {
                                $rows[$k]['ranking'] = -1;
                            }
                        }
                    }
                } else {
                    $rows[$k]['ranking'] = 1;
                }
            } else {
                $rows[$k]['ranking']    =   1;
            }
        }
        return $rows;
    }
    /**
     * @param $type
     */
    private function getRankTime($type)
    {
        switch ($type) {
            case 0:
                //与上一时间段比较的时间段（今天跟昨天对比）
                $startTime = date('Y-m-d', strtotime('-1 days'));
                $endTime =  date('Y-m-d', strtotime('-1 days'));
                //开始时间与结束时间
                $start = date("Y-m-d");
                $end = date("Y-m-d");
                break;
            case 1:
                //昨天跟前一天对比
                $startTime = date('Y-m-d', strtotime('-2 days'));
                $endTime = date('Y-m-d', strtotime('-2 days'));

                $start = date("Y-m-d", strtotime("-1 days"));
                $end = date("Y-m-d", strtotime("-1 days"));
                break;
            case 2:
                $w  =  date("w");
                //星期天
                if (0 == $w) {
                    $sw = 6;
                    $x = 13;
                    $y = 7;
                } else {
                    $sw = $w - 1;
                    $x = $w + 6;
                    $y = $w;
                }
                //开始时间
                $start = date("Y-m-d", strtotime("-{$sw} days"));
                $end = date('Y-m-d');

                //上周周一至周日的数据
                $startTime = date("Y-m-d", strtotime("-{$x} days"));
                $endTime = date("Y-m-d", strtotime("-{$y} days"));
                break;
            case 3:
                //上周的数据
                $w  =  date("w");
                //星期天
                if (0 == $w) {
                    $x  = 13;
                    $y  = 7;
                    $wx     = 20;
                    $wy     = 14;
                } else {
                    $x  =   $w + 6;
                    $y  =   $w;
                    $wx     =   $w + 13;
                    $wy     =   $w + 7;
                }
                $startTime    =    date("Y-m-d", strtotime("-{$wx} days"));
                $endTime    =    date("Y-m-d", strtotime("-{$wy} days"));

                //开始时间
                $start    =    date("Y-m-d", strtotime("-{$x} days"));
                $end    =    date("Y-m-d", strtotime("-{$y} days"));
                break;
            case 4:
                //本月
                $start = date('Y-m-01', strtotime(date('Y-m-d')));
                $end = date('Y-m-d', strtotime("$start +1 month -1 day"));
                //上个月
                $startTime = date('Y-m-01', strtotime("-1 months"));
                $endTime = date('Y-m-t', strtotime("-1 months"));
                break;
            case 5:
                //上月
                $start = date('Y-m-01', strtotime("-1 months"));
                $end = date('Y-m-t', strtotime("-1 months"));
                //上上月
                $startTime = date('Y-m-01', strtotime("-2 months"));
                $endTime = date('Y-m-t', strtotime("-2 months"));
                break;
            default:
                $startTime = '';
                $endTime = '';
                $start = '0000-00-00';
                $end = date('Y-m-d');
                break;
        }
        return [
            'start' => $start,
            'end' => $end,
            'startTime' => $startTime,
            'endTime' => $endTime,
        ];
    }
    /**
     * 获取adx报表数据
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | span | integer | 数据分组类型 | 1：hours 2：days 3：month| 是 |
     * | zone_offset | integer | 时间偏移 | -8| 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub_name | sub_name | type | description | restraint | required |
     * | :--: | :--: | :--: | :--------: | :-------: | :-----: |
     * | time |  |  | date | 时间 | | |
     * | affiliateid |  |  | integer | 媒体商ID | | 是 |
     * | child |  |  | array |  |  | 是 |
     * |  | adx |  | array |  |  | 是 |
     * |  |  | external_zone_id | array | 广告位ID |  | 是 |
     * |  |  | bid_number | integer | 竞价数 |  | 是 |
     * |  |  | win_number | integer | 成功数 |  | 是 |
     * |  |  | impressions | integer | 展示量 |  | 是 |
     * |  |  | clicks | integer | 点击量 |  | 是 |
     * |  |  | af_income | integer | 花费 |  | 是 |
     * |  |  | media_revenue_type | integer | 计费类型 |  | 是 |
     * |  | client |  | array |  |  | 是 |

     * |  |  | sum_views | integer | 展示量 |  | 是 |
     * |  |  | sum_cpa | integer | cpa量 |  | 是 |
     * |  |  | sum_revenue | integer | 广告主消耗 |  | 是 |
     * |  |  | w_number | integer | 完成数 |  | 是 |
     * |  |  | sum_download_complete | integer | 下载量 |  | 是 |
     * |  |  | sum_cpc_clicks | integer | 点击量|  | 是 |
     * |  |  | bannerid | integer | 广告ID|  | 是 |
     * |  |  | app_name | string | 广告名称|  | 是 |
     * |  |  | client_revenue_type | string | 广告名称|  | 是 |
     */
    public function adxReport(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required|in:1,2',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $period_start = Input::get('period_start');
        $period_end = Input::get('period_end');
        $affiliateId = Input::get('affiliateid');
        if (Input::get('span') == self::SPAN_HOURS) {
            $axis = 'hours';
        } else {
            $axis = 'days';
        }
        $zoneOffset = Input::get('zone_offset');
        $adxData = StatService::findAdxData($period_start, $period_end, $affiliateId, $axis);
        $clientData = StatService::findClientData($period_start, $period_end, $affiliateId, $axis, $zoneOffset);
        $list = [];
        $material_size = Config::get('biddingos.material_size');
        foreach ($adxData as $item) {
            if (!isset($list[$item['time']][$item['affiliateid']])) {
                $revenue_type = AffiliateExtend::where('affiliateid', $item['affiliateid'])->first();
                $item['media_revenue_type'] = $revenue_type->revenue_type;
                $list[$item['time']][$item['affiliateid']] = [
                    'affiliateid' => $item['affiliateid'],
                    'time' => $item['time'],
                    'brief_name' => $item['brief_name'],
                    'media_revenue_type' => $item['media_revenue_type'],
                ];
            }
            if (isset($material_size[$item['affiliateid']]) &&
                isset($material_size[$item['affiliateid']][$item['external_zone_id']])) {
                $item['size'] = $material_size[$item['affiliateid']][$item['external_zone_id']];
            } else {
                $item['size'] = '未知';
            }
            $list[$item['time']][$item['affiliateid']]['child']['adx'][] = $item;
        }
        foreach ($clientData as $value) {
            if (!isset($list[$value['time']][$value['affiliateid']])) {
                $list[$value['time']][$value['affiliateid']] = [
                    'affiliateid' => $value['affiliateid'],
                    'time' => $value['time'],
                    'brief_name' => $value['brief_name'],
                    'media_revenue_type' => $value['media_revenue_type'],
                ];
            }
            $list[$value['time']][$value['affiliateid']]['child']['client'][] = $value;
        }
        return $this->success(
            null,
            null,
            $list
        );
    }
    
    /**
     * 验证导入excel中的广告主和广告名称是否对应
     * @param $clientName
     * @param $campaignName
     * @return bool
     */
    private function validateClientAssocCampaign($clientName, $campaignName)
    {
        if ($clientName == '' || $campaignName == '') {
            return false;
        }
        $count = DB::table('campaigns')
        ->leftJoin('appinfos', 'appinfos.app_id', '=', 'campaigns.campaignname')
        ->leftJoin('clients', 'campaigns.clientid', '=', 'clients.clientid')
        ->where('clients.clientname', '=', $clientName)
        ->where('appinfos.app_name', '=', $campaignName)
        ->where('clients.agencyid', Auth::user()->agencyid)
        ->count();
        return $count > 0 ? true : false;
    }
    
    /**
     * 验证导入excel中的媒体商和广告位是否对应
     * @param $affiliateName
     * @param $zoneId
     * @return bool
     */
    private function validateAffiliateAssocZone($affiliateName, $zoneId)
    {
        if ($affiliateName == '' || $zoneId == '' || !is_numeric($zoneId)) {
            return false;
        }
        $count = DB::table('zones')
        ->leftJoin('affiliates', 'zones.affiliateid', '=', 'affiliates.affiliateid')
        ->where('zones.zoneid', $zoneId)
        ->where('affiliates.name', $affiliateName)
        ->where('affiliates.agencyid', Auth::user()->agencyid)
        ->count();
        return $count > 0 ? true : false;
    }
    
    /**
     * 验证导入excel中的媒体商是否是人工媒体
     * @param $affiliateName
     * @return bool
     */
    private function validateAffiliateMode($affiliateName)
    {
        if ($affiliateName == '') {
            return false;
        }
        $count = Affiliate::where('name', $affiliateName)
        ->where('mode', Affiliate::MODE_ARTIFICIAL_DELIVERY)
        ->where('agencyid', Auth::user()->agencyid)
        ->count();
        return $count > 0 ? true : false;
    }
    
    /**
     * 验证导入excel中的媒体商是否创建
     * @param $affiliateName
     * @return bool
     */
    private function validateAffiliate($affiliateName)
    {
        if ($affiliateName == '') {
            return false;
        }
        $count = Affiliate::where('name', $affiliateName)
        ->where('agencyid', Auth::user()->agencyid)
        ->count();
        return $count > 0 ? true : false;
    }

    /**
     * 获取游戏数据
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     *  period_start | date | 起始时间 |  | 是 |
     *  period_end | date | 终止时间 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主id |  | 是 |
     * | clientname | string | 广告主名称 |  | 是 |
     * | client_brief_name | string | 广告主简称 |  | 是 |
     * | campaign_id | string | 广告ID |  | 是 |
     * | app_name | string | 游戏 |  | 是 |
     * | affiliateid | integer | 媒体id |  | 是 |
     * | name | string | 媒体全称 |  | 是 |
     * | af_brief_name | string | 媒体简称 |  | 是 |
     * | game_client_usernum | int | 新增用户数 |  | 是 |
     * | before_game_client_usernum | int | 前一天新增用户数 |  | 是 |
     * | game_charge | float | 充值金额 |  | 是 |
     * | before_game_charge | float | 前一天充值金额 |  | 是 |
     * | game_client_revenue_type | int | 广告主计费类型 |  | 是 |
     * | game_client_price | float | 广告主单价 |  | 是 |
     * | game_client_amount | string | 广告主结算金额 |  | 是 |
     *  | game_af_usernum | int | 渠道新增用户数 |  | 是 |
     * | game_af_revenue_type | int | 广告主计费类型 |  | 是 |
     * | game_af_price | string | 渠道单价 |  | 是 |
     * | game_af_amount | string | 渠道结算金额 |  | 是 |
     * | date | date | 日期 | | 是 |
     */
    public function gameReport(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $param = $request->all();
        $param['period_start'] = $before = date('Y-m-d', strtotime("{$param['period_start']} -1 day"));
        $statData = StatService::findManagerGameData($param);
        $aff = DB::table('affiliates')->get();
        $app = DB::table('appinfos')->get();
        $affiliateBriefName = ArrayHelper::map(
            $aff,
            'affiliateid',
            'brief_name'
        );
        $appName = ArrayHelper::map(
            $app,
            'app_id',
            'app_name'
        );
        $affiliateFullName = ArrayHelper::map(
            $aff,
            'affiliateid',
            'name'
        );
        $list = [];
        foreach ($statData as $val) {
            $val['af_brief_name'] = $affiliateBriefName[$val['affiliateid']];
            $val['name'] = $affiliateFullName[$val['affiliateid']];
            $val['app_name'] = $appName[$val['campaignname']];
            $list[$val['campaignid']][$val['affiliateid']][$val['date']] = $val;
        }
        //前一天的用户数量及充值金
        foreach ($list as $cam => $val) {
            foreach ($val as $aff => $v) {
                foreach ($v as $date => $value) {
                    $before = date('Y-m-d', strtotime("$date -1 day"));
                    $after = date('Y-m-d', strtotime("$date 1 day"));
                    if (isset($list[$cam][$aff][$before])) {
                        $list[$cam][$aff][$date]['before_game_client_usernum'] =
                            $list[$cam][$aff][$before]['game_client_usernum'];
                        $list[$cam][$aff][$date]['before_game_charge'] =
                            $list[$cam][$aff][$before]['game_charge'];
                    } else {
                        $list[$cam][$aff][$date]['before_game_client_usernum'] = 0;
                        $list[$cam][$aff][$date]['before_game_charge'] = 0;
                    }

                    if ($after <= $param['period_end'] && !isset($list[$cam][$aff][$after])) {
                        $list[$cam][$aff][$after] = [
                            'campaignid' => $value['campaignid'],
                            'campaignname' => $value['campaignname'],
                            'affiliateid' => $value['affiliateid'],
                            'date' => $after,
                            'af_brief_name' => $value['af_brief_name'],
                            'app_name' => $value['app_name'],
                            'before_game_charge' => $value['game_charge'],
                            'before_game_client_usernum' => $value['game_client_usernum'],
                            'client_brief_name' => $value['client_brief_name'],
                            'clientid' => $value['clientid'],
                            'clientname' => $value['clientname'],
                            'game_af_amount' => 0,
                            'game_af_price' => 0,
                            'game_af_revenue_type' => $value['game_af_revenue_type'],
                            'game_af_usernum' => 0,
                            'game_charge' => 0,
                            'game_client_amount' => 0,
                            'game_client_price' => 0,
                            'game_client_revenue_type' => $value['game_client_revenue_type'],
                            'game_client_usernum' => 0,
                            'name' => $value['name'],
                        ];
                    }
                }
            }
        }
        //去键值
        $eData = [];
        foreach ($list as $ke => $va) {
            foreach ($va as $kk => $vv) {
                foreach ($vv as $date => $value) {
                    if ($date <> $param['period_start']) {
                        $eData[]  = $value;
                    }
                }
            }
        }
        return $this->success(null, null, $eData);
    }
    /**
     * 获取游戏数据
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     *  period_start | date | 起始时间 |  | 是 |
     *  period_end | date | 终止时间 |  | 是 |
     *  clientid | int | 广告主ID |  | 是 |
     *  campaignid | int | 广告ID |  | 是 |
     *  affiliateid | int | 媒体ID |  | 是 |
     *  client_revenue_type | int | 广告计费类型 |  | 是 |
     *  af_revenue_type | int | 媒体计费类型 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | clientid | integer | 广告主id |  | 是 |
     * | clientname | string | 广告主名称 |  | 是 |
     * | client_brief_name | string | 广告主简称 |  | 是 |
     * | campaign_id | string | 广告ID |  | 是 |
     * | app_name | string | 游戏 |  | 是 |
     * | affiliateid | integer | 媒体id |  | 是 |
     * | name | string | 媒体全称 |  | 是 |
     * | af_brief_name | string | 媒体简称 |  | 是 |
     * | game_client_usernum | int | 新增用户数 |  | 是 |
     * | game_charge | float | 充值金额 |  | 是 |
     * | game_client_revenue_type | int | 广告主计费类型 |  | 是 |
     * | game_client_price | float | 广告主单价 |  | 是 |
     * | game_client_amount | string | 广告主结算金额 |  | 是 |
     * | game_af_revenue_type | int | 广告主计费类型 |  | 是 |
     * | game_af_price | string | 渠道单价 |  | 是 |
     * | game_af_amount | string | 渠道结算金额 |  | 是 |
     * | date | date | 日期 | | 是 |
     * | 客户端ARPU | date | 客户端ARPU | | 是 |
     * | profit | float | 毛利 | | 是 |
     * | profit_rate | float | 毛利率 | | 是 |
     */
    public function gameReportExcel(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $param = $request->all();
        //为了计算环比多查询一天的数据
        $param['period_start'] = date('Y-m-d', strtotime("{$param['period_start']} -1 day"));
        $statData = StatService::findManagerGameData($param);
        $aff = DB::table('affiliates')->get();
        $app = DB::table('appinfos')->get();
        $affiliateBriefName = ArrayHelper::map(
            $aff,
            'affiliateid',
            'brief_name'
        );
        $affiliateFullName = ArrayHelper::map(
            $aff,
            'affiliateid',
            'name'
        );
        $appName = ArrayHelper::map(
            $app,
            'app_id',
            'app_name'
        );
        $list = [];
        foreach ($statData as $val) {
            $val['af_brief_name'] = $affiliateBriefName[$val['affiliateid']];
            $val['name'] = $affiliateFullName[$val['affiliateid']];
            $val['app_name'] = $appName[$val['campaignname']];
            $list[$val['campaignid']][$val['affiliateid']][$val['date']] = $val;
        }
        $data = [];
        $i = 0;
        foreach ($list as $cam => $val) {
            foreach ($val as $aff => $v) {
                foreach ($v as $date => $value) {
                    //剔除掉多查询一天的数据
                    if ($date > $param['period_start']) {
                        $before = date('Y-m-d', strtotime("$date -1 day"));
                        $data[$i][] = $date;
                        $data[$i][] = $value['client_brief_name'];
                        $data[$i][] = $value['clientname'];
                        $data[$i][] = $value['app_name'];
                        $data[$i][] = $value['af_brief_name'];
                        $data[$i][] = $value['name'];
                        $data[$i][] = $value['game_client_usernum'];
                        //新增用户环比
                        if (isset($list[$cam][$aff][$before])) {
                            $data[$i][] = StatService::loopRate(
                                $value['game_client_usernum'],
                                $list[$cam][$aff][$before]['game_client_usernum']
                            ) . '%';
                        } else {
                            $data[$i][] = '100%';
                        }
                        $data[$i][] = $value['game_charge'];
                        if (isset($list[$cam][$aff][$before])) {
                            $data[$i][] = StatService::loopRate(
                                $value['game_charge'],
                                $list[$cam][$aff][$before]['game_charge']
                            ) . '%';
                        } else {
                            $data[$i][] = '100%';
                        }
                        $data[$i][] = $value['game_client_price'];
                        $data[$i][] = $value['game_client_amount'];
                        $data[$i][] = $value['game_af_price'];
                        $data[$i][] = $value['game_af_amount'];
                        $data[$i][] = $value['game_af_usernum'];
                        $data[$i][] = Formatter::asDecimal($value['game_client_usernum'] > 0 ?
                            $value['game_charge'] /$value['game_client_usernum'] : 0);
                        $data[$i][] = $value['game_client_amount'] - $value['game_af_amount'];
                        $percent = floatval($value['game_client_amount']) > 0
                            ? ($value['game_client_amount'] - $value['game_af_amount'])
                            / $value['game_client_amount'] * 100
                            : 0;
                        $data[$i][] = Formatter::asDecimal($percent) . '%';
                        $i++;
                    }
                }
            }
        }
        $sheetRow = [
            'date' => '日期',
            'client_brief_name' => '广告主简称',
            'clientname' => '广告主全称',
            'app_name' => '游戏',
            'af_brief_name' => '渠道简称',
            'name' => '渠道全称',
            'game_client_usernum' => '新增用户数',
            'game_client_usernum_rate' => '新增环比',
            'game_charge' => '充值金额',
            'game_charge_rate' => '充值环比',
            'game_client_price' => '广告主单价',
            'game_client_amount' => '广告主结算金额',
            'game_af_price' => '渠道单价',
            'game_af_amount' => '渠道结算金额',
            'game_af_usernum' => '渠道新增用户数',
            'ARPU' => '客户端ARPU',
            'profit' => '毛利',
            'profit_rate' => '毛利率',
        ];

        $excelName = '游戏报表-'. str_replace('-', '', Input::get('period_start')) . '_' .
            str_replace('-', '', $param['period_end']);
        StatService::downloadCsv($excelName, $sheetRow, $data) ;
    }
}
