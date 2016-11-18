<?php
namespace App\Http\Controllers\Broker;

use App\Components\Formatter;
use App\Components\Helper\LogHelper;
use App\Models\Campaign;
use App\Models\Product;
use Auth;
use App\Services\StatService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Components\Config;

class StatController extends Controller
{
    const SPAN_DAYS = 2;
    const SPAN_MONTH = 3;

    /**
     * 获取报表数据
     *
     * | name | type | description | restraint | required |
     * | :--: |  :--: | :--------: | :-------: | :-----: |
     * | type | integer |  数据类型 | 1：CPD 2：CPC|  是 |
     * | period_start | date | 起始时间 |  | 是 |
     * | period_end | date | 终止时间 |  | 是 |
     * | span | integer | 数据分组类型 | 1：hours 2：days 3：month| 是 |
     * | zone_offset | string | 时区 | -8 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | sub name | sub name | type | description | restraint | required |
     * | :--: | :------: | :--: |  :--: | :--------: | :-------: | :-----: | :-----: |
     * | statChart |  |  |  | array | 图表数据 |  | |
     * | | client_id |  |  | integer | 广告主id |  | 是 |
     * | | client_name |  |  | string | 广告主名称 |  | 是 |
     * | | product_list |  |  | array | json对象数组 |  | |
     * | |  | product_id |  | integer | 推广id |  | 是 |
     * | |  | product_name |  | string | 推广名称 |  | 是 |
     * | |  | ad_list |  | array | json对象数组 |  | 是 |
     * | |  |  | ad_id | integer | 广告id |  | 是 |
     * | |  |  | ad_name | string | 广告名称 |  | 是 |
     * | |  |  | sum_views | integer | 展示量 |  | 是 |
     * | |  |  | sum_clicks | integer | 下载量/点击量 |  | 是 |
     * | |  |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  |  | time | date | 时间 |  | 是 |
     * | statData |  |  |  | array | 表格数据 |  | 是 |
     * | |  | ad_id |  | integer |  广告id |  | 是 |
     * | |  | ad_name |  | string | 广告名称 |  | 是 |
     * | |  | ad_type |  | string | 广告类型 |  | 是 |
     * | |  | platform |  | string | 平台 |  | 是 |
     * | |  | product_id |  | integer | 推广产品id |  | 是 |
     * | |  | product_name |  | string | 推广产品名称 |  | 是 |
     * | |  | product_icon |  | string | 推广图标 |  | 是 |
     * | |  | product_type |  | string | 推广类型 |  | 是 |
     * | |  | client_id |  | integer | 广告主id |  | |
     * | |  | client_name |  | string | 广告主名称 |  | |
     * | |  |  sum_views |  | integer | 展示量 |  | 是 |
     * | |  |  sum_clicks |  | integer | 下载量/下载量 |  | 是 |
     * | |  |  sum_revenue |  | decimal | 收入 |  | 是 |
     * | |  |  ctr |  | decimal | 下载转化率/点击转化率 |  | 是 |
     * | |  |  cpd |  | decimal | 平均单价 |  | 是 |
     * | |  | child |  | array |  |  | 是 |
     * | |  |  | sum_views | integer | 展示量 |  | 是 |
     * | |  |  | sum_clicks | integer | 下载量/点击量 |  | 是 |
     * | |  |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  |  | ctr | decimal | 下载转化率/点击转化率 |  | 是 |
     * | |  |  | media_cpd | decimal | 平均单价 |  | 是 |
     * | |  |  | channel | string | 渠道号 |  | 是 |
     * | obj |  |  |  | object | 汇总信息 |  | 是 |
     * | |  | sum_views |  | integer | 展示量 |  | 是 |
     * | |  | sum_clicks |  | integer | 下载量/点击量 |  | 是 |
     * | |  | sum_revenue |  | decimal | 收入 |  | 是 |
     */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required',
                'zone_offset' => 'required',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $period_start = $request->input('period_start');
        $period_end = $request->input('period_end');
        $zoneOffset = $request->input('zone_offset');

        if ($request->input('span') == self::SPAN_MONTH) {
            $axis = StatService::AXIS_MONTH;
        } else {
            $axis = StatService::AXIS_DAYS;
        }

        $list = [];
        $sum_views = 0;
        $sum_clicks = 0;
        $sum_revenue = 0;
        //安装包下载、链接下载
        $type = $request->input('type');
        $type = empty($type) ? 0 : $type;
        $statChart = $this->getBrokerCampaignsData(
            Auth::user()->account->broker->brokerid,
            $period_start,
            $period_end,
            $axis,
            $type
        );

        //计算出汇总的数据
        $chart = [];
        foreach ($statChart as $val) {
            $sum_views += $val['sum_views'];
            $sum_clicks += $val['sum_clicks'];
            $sum_revenue += $val['sum_revenue'];
            $item = $val;
            $item['ad_type'] = Campaign::getAdTypeLabels($val['ad_type']);
            if ($val['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $item['platform'] =Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
            } else {
                $item['platform'] = Campaign::getPlatformLabels($val['platform']);
            }
            $chart[] = $item;
        }
        //获取报表及二级展开数据 ($chart, $statData)
        $statData = $this->summaryData($chart);
        $eChart = [];
        if (!empty($chart)) {
            foreach ($chart as $key => $val) {
                $eChart[$val['client_id']]['client_id'] = $val['client_id'];
                $eChart[$val['client_id']]['client_name'] = $val['client_name'];
                $eChart[$val['client_id']]['product_list'][$val['product_id']]['product_id'] = $val['product_id'];
                $eChart[$val['client_id']]['product_list'][$val['product_id']]['product_name'] = $val['product_name'];
                $eChart[$val['client_id']]['product_list'][$val['product_id']]['ad_list'][] = $val;
            }
        }
        //去键值
        foreach ($eChart as &$val) {
            $val['product_list'] = array_values($val['product_list']);
        }
        $eChart = array_values($eChart);
        $list['statChart'] = $eChart;
        $list['statData'] = $statData;
        $sum_revenue = round($sum_revenue, 2);
        return $this->success(['sum_views'=> $sum_views,
            'sum_clicks' => $sum_clicks,
            'sum_revenue' => $sum_revenue, ], null, $list);

    }

    /**
     * 获取概览数据
     * @return \Illuminate\Http\Response
     *
     * | name | type | description | restraint | required
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | id | date | 广告ID |  | 是 |
     * | name | string | 广告名称 |  | 是 |
     * | icon | string | 图标 |  | |
     * | type | integer | 0 安装包下载 1 链接推广|  | 是 |
     * | ad_type | string | 0（应用市场）1（Banner）2 (Feeds) |  | 是 |
     * | sum_clicks | string | 下载量/点击量 |  | 是 |
     * | sum_revenue | string | 消耗 |  | 是 |
     * | price | string | 单价 |  | 是 |
     * | data | array | 从昨天开始的前30天每一天的数据 |  |  是 |
     * |  |  | 点击或下载量，消耗，单价和时间 |  |  |
     */
    public function report()
    {
        //获取前三十天数据
        $result = $this->getReport();
        $eData = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $price = $row['sum_clicks'] ? $row['sum_revenue'] / $row['sum_clicks'] : 0;
                if (isset($eData[$row['id']])) {
                    $eData[$row['id']]['sum_revenue'] += $row['sum_revenue'];
                    $eData[$row['id']]['sum_clicks'] += $row['sum_clicks'];
                    if ($eData[$row['id']]['sum_clicks'] > 0) {
                        $eData[$row['id']]['price'] = $eData[$row['id']]['sum_revenue']
                            /$eData[$row['id']]['sum_clicks'];
                    } else {
                        $eData[$row['id']]['price'] = 0;
                    }

                } else {
                    $eData[$row['id']] = $row;
                }
                $eData[$row['id']]['data'][$row['date']] = [
                    'sum_clicks' => $row['sum_clicks'] ,
                    'sum_revenue' => $row['sum_revenue'],
                    'time' => $row['date'],
                    'price' => $price
                ];

            }
            //补全数据为零的天数
            $start = date('Y-m-d', strtotime('-30 day'));
            for ($i = 0; $i < 30; $i++) {
                foreach ($eData as $k => $value) {
                    if (!isset($eData[$k]['data'][$start])) {
                        $eData[$k]['data'] = array_add($eData[$k]['data'], $start, array('sum_clicks' => 0,
                            'sum_revenue'=> 0, 'time' => $start, 'price' => 0));
                    } ;
                    $eData[$k]['data'][$start]['sum_revenue'] =
                        Formatter::asDecimal($eData[$k]['data'][$start]['sum_revenue']);
                }
                $start=date("Y-m-d", strtotime('+1 day', strtotime($start)));
            }
            foreach ($eData as $ke => &$va) {
                if ($eData[$ke]['sum_revenue'] <= 0 && $eData[$ke]['sum_clicks'] == 0) {
                    unset($eData[$ke]);
                } else {
                    $eData[$ke]['sum_revenue'] = Formatter::asDecimal($eData[$ke]['sum_revenue']);
                    $eData[$ke]['data'] = array_values($va['data']);
                }
            }
            //对消耗进行排序
            if (!empty($eData)) {
                foreach ($eData as $k => $v) {
                    $revenue[$k] = $v['sum_revenue'];
                }
                array_multisort($revenue, SORT_DESC, $eData);
            }
        }
        return $this->success(null, null, $eData);
    }

    /**
     * 获取报表表头
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | type | integer |  数据类型 | 0：安装包 1：链接推广| 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required |
     * | :--: | :--: |  :--: |:--------: | :-------: | :-----: |
     * | chart_item |  | array | 报表下拉框选择字段 |  json数组 | 是 |
     * | | field | sting | 对应的字段 |  | 是 |
     * | | name | string | 字段名称 |  | 是 |
     * | | default | integer | 默认显示 | 0：非默认 | 是 |
     * | |  |  |  | 1：默认 | |
     * | | chart_type | string | 图示类型 | ‘column’: 柱状图 | 是 |
     * | |  |  |  | 'line':现状图 | |
     * | | format | string | 格式化类型 | ‘n2’: 数字，保留两位小数 | 是 |
     * | |  |  |  | 'p2': 百分比，保留两位小数 | |
     * | |  |  |  | 注：第一个字符表示数据类型，第一个字符表示保留小数点位数 | |
     * | | field_type | string | 数据类型 | ‘basics’： 基础数据 | 是 |
     * | |  |  |  | ‘calculation’： 算数数据 | |
     * | | arithmetic | array | 算数表达式数字 | ["sum_clicks", "/", "sum_views"] | |
     */
    public function columnList(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $list = [];
        //获取图表的表头及报表的表头
        $chart_item = Config::get('biddingos.broker_chart_fields');
        $table_column_item = Config::get('biddingos.bro_table_column_field');
        //如果是cpc将下载量改为点击量
        if ($request->input('type') == Product::TYPE_LINK) {
            foreach ($chart_item as $key => $val) {
                if ($val['field'] == 'sum_clicks') {
                    $chart_item[$key]['name'] = '点击量';
                }
                if ($val['field'] =='ctr') {
                    $chart_item[$key]['name'] = '点击转化率';
                }
                if ($val['field'] =='cpd') {
                    $chart_item[$key]['name'] = '点击单价';
                }
            }
            foreach ($table_column_item as $ke => $va) {
                if ($va['field'] == 'sum_clicks') {
                    $table_column_item[$ke]['name'] = '点击量';
                }
                if ($va['field'] == 'ctr') {
                    $table_column_item[$ke]['name'] = '点击转化率%';
                }
                if ($va['field'] == 'cpd') {
                    $table_column_item[$ke]['name'] = '平均单价';
                }
            }
        }
        $list['chart_item'] = $chart_item;
        $list['table_column_item'] = $table_column_item;
        return $this->success(null, null, $list);
    }
    /**
     * @return mixed
     */
    protected function getReport()
    {
        $broker_id = Auth::user()->account->broker->brokerid;
        $period_start = date('Y-m-d', strtotime('-30 day'));
        $period_end = date('Y-m-d', strtotime('-1 day'));

        $campaign_table = DB::getTablePrefix() . 'campaigns';
        $prefix = DB::getTablePrefix();
        if (Auth::user()->account->broker->affiliateid > 0) {
            $table = 'data_hourly_daily';
        } else {
            $table = 'data_hourly_daily_client';
        }
        $cpc_revenue = Campaign::REVENUE_TYPE_CPC;
        $cpd_revenue =  Campaign::REVENUE_TYPE_CPD;
        $query = DB::table("{$table} as h")
            ->join('campaigns', 'h.campaign_id', '=', 'campaigns.campaignid')
            ->join('appinfos', 'appinfos.app_id', '=', 'campaigns.campaignname')
            ->join('products', 'products.id', '=', 'campaigns.product_id')
            ->join('clients', 'campaigns.clientid', '=', 'clients.clientid')
            ->select(
                DB::raw('SUM(total_revenue) as sum_revenue'),
                'clients.clientid',
                'campaigns.campaignid as id',
                'campaigns.revenue_type',
                'h.date',
                'appinfos.app_name as name',
                'campaigns.ad_type',
                'products.type',
                'products.icon'
            )
            ->where('clients.broker_id', $broker_id)
            ->whereBetween('h.date', [$period_start, $period_end])
            ->groupBy('h.campaign_id', 'h.date');

        //如果是cpc 汇总点击量； 如果是cpd 汇总下载量
        $query->addSelect(
            DB::raw('CASE ' .$campaign_table . '.`revenue_type`
            WHEN '.$cpd_revenue.'
            THEN IFNULL(SUM(' .$prefix . 'h.`conversions`), 0)
            WHEN '.$cpc_revenue.'
            THEN IFNULL(SUM(' .$prefix . 'h.`clicks`), 0)
            END as `sum_clicks`')
        );
        $res = $query->get();
        return json_decode(json_encode($res), true);
    }

    /**
     * @param $brokerId
     * @param $start_date
     * @param $end_date
     * @param $axis
     * @param $zoneOffset
     * @param int $productType
     * @return mixed
     */
    protected function getBrokerCampaignsData(
        $brokerId,
        $start_date,
        $end_date,
        $axis,
        $productType = Product::TYPE_LINK
    ) {
        $selectInfo = array(
            'clients.clientname as client_name',
            'clients.clientid as client_id',
            'campaigns.campaignid as ad_id',
            'appinfos.app_name as ad_name',
            'campaigns.ad_type',
            'appinfos.platform',
            'products.name as product_name',
            'products.icon as product_icon',
            'products.id as product_id',
            'products.type',
            'attach_files.channel',
            'campaigns.revenue_type'

        );
        if ($productType == Product::TYPE_APP_DOWNLOAD) {
            $groupBy = [ 'attach_files.channel', 'campaigns.campaignid'];
        } elseif ($productType == Product::TYPE_LINK) {
            $groupBy = [ 'campaigns.campaignid'];
        }
        $search = ['clients.broker_id' => $brokerId , 'products.type' => $productType];
        $res = $this->getCampaignData($selectInfo, $start_date, $end_date, $search, $groupBy, $axis);
        $eData = [];
        if ($productType == Product::TYPE_LINK) {
            foreach ($res as $row) {
                $item = $row;
                $item['sum_clicks'] = $row['sum_cpc_clicks'];
                $eData[] = $item;
            }
        } else {
            $eData =  $res;
        }
        return $eData;

    }

    /**
     * @param $statsCharts
     * @return array
     */
    protected function summaryData($statsCharts)
    {
        $entitiesData = [];
        //获取需要汇总的列名 展示量，点击量，下载量及支出
        $label=  [
            'sum_views',
            'sum_clicks',
            'sum_revenue',
        ];
        if (count($statsCharts) > 0) {
            foreach ($statsCharts as $key => $value) {
                //按广告计划进行汇总
                if (isset($entitiesData[$value['ad_id']])) {
                    foreach ($label as $k => $v) {
                        $entitiesData[$value['ad_id']][$v] += $value[$v];
                    }
                } else {
                    $entitiesData[$value['ad_id']] = $value;
                }
                //计算广告计划的ctr 和 cpd
                $entitiesData[$value['ad_id']]['ctr'] = StatService::getCtr(
                    $entitiesData[$value['ad_id']]['sum_views'],
                    $entitiesData[$value['ad_id']]['sum_clicks']
                );
                $entitiesData[$value['ad_id']]['cpd'] = StatService::getCpd(
                    $entitiesData[$value['ad_id']]['sum_revenue'],
                    $entitiesData[$value['ad_id']]['sum_clicks']
                );
                //按照渠道号进行汇总
                if (isset($entitiesData[$value['ad_id']]['child'][$value['channel']])) {
                    foreach ($label as $k => $v) {
                        $entitiesData[$value['ad_id']]['child'][$value['channel']][$v] += $value[$v];
                    }
                } else {
                    foreach ($label as $k => $v) {
                        $entitiesData[$value['ad_id']]['child'][$value['channel']][$v] = $value[$v];
                        $entitiesData[$value['ad_id']]['child'][$value['channel']]['channel'] = $value['channel'];
                    }
                }
                //计算按照渠道号的ctr 和 cpd
                $entitiesData[$value['ad_id']]['child'][$value['channel']]['ctr'] =
                    StatService::getCtr(
                        $entitiesData[$value['ad_id']]['child'][$value['channel']]['sum_views'],
                        $entitiesData[$value['ad_id']]['child'][$value['channel']]['sum_clicks']
                    );
                $entitiesData[$value['ad_id']]['child'][$value['channel']]['cpd'] =
                    StatService::getCpd(
                        $entitiesData[$value['ad_id']]['child'][$value['channel']]['sum_revenue'],
                        $entitiesData[$value['ad_id']]['child'][$value['channel']]['sum_clicks']
                    );
            }
        }
        //格式化支出
        foreach ($entitiesData as $k => $v) {
            $entitiesData[$k]['sum_revenue'] = Formatter::asDecimal($entitiesData[$k]['sum_revenue']);
            unset($entitiesData[$k]['channel']);
            foreach ($v['child'] as $key => $val) {
                $entitiesData[$k]['child'][$key]['sum_revenue'] =
                    Formatter::asDecimal($entitiesData[$k]['child'][$key]['sum_revenue']);
            }

        }
        return array_values($entitiesData);
    }

    /**
     *导出报表
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | zone_offset | string | 时区 |  | 是 |
     * | type | integer | 计费类型 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function campaignExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'zone_offset' => 'required',
                'type' => 'required|integer|in:0,1',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $period_start = $request->input('period_start');
        $period_end = $request->input('period_end');
        $type = $request->input('type');
        $client_id = $request->input('client_id');
        $productId = $request->input('product_id');
        $campaignId = $request->input('campaign_id');

        //需要查询的字段
        $selectInfo = [
            'appinfos.app_name',
            'appinfos.platform',
            'products.type',
            'products.name as product_name',
            'products.icon as product_icon',
            'products.id as product_id',
            'campaigns.ad_type',
            'campaigns.revenue_type as client_revenue_type',
            'attach_files.channel',
            'clients.clientname'
        ];
        $broker_id = Auth::user()->account->broker->brokerid;
        $search = ['clients.broker_id' => $broker_id , 'products.type' => $type];
        //增加筛选条件
        if ($client_id) {
            $search['clients.clientid'] = $client_id;
        }
        if ($productId) {
            $search = array_merge(['products.id' => $productId]);
        }
        if ($campaignId) {
            $search = array_merge(['data_hourly_daily_client.campaign_id' => $campaignId]);
        }
        if ($type == Product::TYPE_APP_DOWNLOAD) {
            $groupBy = [ 'attach_files.channel', 'campaigns.campaignid'];
        } elseif ($type == Product::TYPE_LINK) {
            $groupBy = [ 'campaigns.campaignid'];
        }
        $res = $this->getCampaignData($selectInfo, $period_start, $period_end, $search, $groupBy);
        $eData = [];
        if ($type == Product::TYPE_LINK) {
            foreach ($res as $row) {
                $item = $row;
                $item['sum_clicks'] = $row['sum_cpc_clicks'];
                $eData[] = $item;
            }
        } else {
            $eData =  $res;
        }
        $sum_clicks = 0;
        $sum_views = 0;
        $sum_revenue = 0;
        $data = [];
        $i = 0;
        foreach ($eData as $row) {
            $data[$i][] = $row['clientname'];
            $data[$i][] = $row['product_name'];
            if ($type == Product::TYPE_APP_DOWNLOAD) {
                $data[$i][] = Product::getTypeLabels($row['type']);
            }
            $data[$i][] = $row['app_name'];
            $data[$i][] = Campaign::getAdTypeLabels($row['ad_type']);
            //$data[$i][] = Campaign::getPlatformLabels($row['platform']);
            if ($row['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $data[$i][]  =Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
            } else {
                $data[$i][] = Campaign::getPlatformLabels($row['platform']);
            }
            if ($type == Product::TYPE_APP_DOWNLOAD) {
                $data[$i][] = $row['channel'];
            }
            $data[$i][] = $row['sum_views'];
            $data[$i][] = $row['sum_clicks'];
            $ctr = StatService::getCtr($row['sum_views'], $row['sum_clicks']);
            $data[$i][] = $ctr.'%';
            $data[$i][] = $row['sum_revenue'];
            $data[$i][] = StatService::getCpd($row['sum_revenue'], $row['sum_clicks']);
            $sum_clicks += $row['sum_clicks'];
            $sum_views += $row['sum_views'];
            $sum_revenue += $row['sum_revenue'];
            $i ++;
        }
        //excel文件名称
        $excelName = '我的报表-'. str_replace('-', '', $period_start) . '_' .
            str_replace('-', '', $period_start);
        //excel标题
        $sheetRow = [
            '广告主',
            '推广名称',
            '广告名称',
            '广告类型',
            '所属平台',
            '展示量',
            '点击量',
            '点击转化率',
            '支出',
            '平均单价'
        ];
        //excel列宽
        $sheetWidth = [
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15
        ];
        $rows = count($data) + 1;
        //excel统计
        $sheetBorder = $type == Product::TYPE_APP_DOWNLOAD ? "A1:L{$rows}" : "A1:J{$rows}";
        $sum_arr = [
            '汇总',
            '--',
            '--',
            '--',
            '--',
            round($sum_views, 2),
            round($sum_clicks, 2),
            '--',
            round($sum_revenue, 2),
            '--'
        ];
        //应用下载需要加入推广类型,渠道
        if ($type == Product::TYPE_APP_DOWNLOAD) {
            $sheetRow[6] = '下载量';
            $sheetRow[7] = '下载转化率';
            array_splice($sheetRow, 2, 0, '推广类型');
            array_splice($sheetRow, 6, 0, '渠道');

            array_splice($sum_arr, 2, 0, '--');
            array_splice($sum_arr, 6, 0, '--');
        }
        array_push($data, $sum_arr);
        //生成Excel
        if (!StatService::downloadExcel($excelName, $sheetRow, $sheetWidth, $sheetBorder, $data)) {
            return $this->errorCode(5027);
        }
        return $this->success();//@codeCoverageIgnoreEnd
    }

    /**
     * 代理商导出每日报表
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | zone_offset | string | 时区 |  | 是 |
     * | type | integer | 计费类型 |  | 是 |
     * | client_id | integer | 广告主id |  | 是 |
     * | product_id | integer | 推广产品id |  | 是 |
     * | campaign_id | integer | 广告id |  | 是 |
     * | span | integer | 时间类型 | 1：小时 2：天 3：月| 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function dailyCampaignExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'type' => 'required',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $sumView = 0; //展示量
        $sumDown = 0; //下载量
        $sumRevenue = 0; //支出

        $period_start = $request->input('period_start');
        $period_end = $request->input('period_end');
        $clientId = $request->input('client_id');
        $campaignId = $request->input('campaign_id');
        $productId =$request->input('product_id');
        $type = $request->get('type', Product::TYPE_APP_DOWNLOAD);

        $statsCharts = StatService::findBrokerDailyExcelData(
            $period_start,
            $period_end,
            $type,
            Auth::user()->account->broker->brokerid,
            $clientId,
            $productId,
            $campaignId
        );
        $statsCharts = StatService::makeData($statsCharts);
        $eData = [];
        if ($type == Product::TYPE_APP_DOWNLOAD) {
            foreach ($statsCharts as $row) {
                $eData[$row['campaignid'].$row['channel']]['info']= $row;
                $eData[$row['campaignid'].$row['channel']]['days'][$row['time']]= $row;
            }
        } else {
            foreach ($statsCharts as $row) {
                $eData[$row['campaignid']]['info']= $row;
                $eData[$row['campaignid']]['days'][$row['time']]= $row;
            }
        }
        $dayCount = (strtotime($period_end) - strtotime($period_start)) / 86400;
        foreach ($eData as $k => $v) {
            $_start = $period_start;
            for ($i = 0; $i <= $dayCount; $i++) {
                $eData[$k]['days']  = array_add($eData[$k]['days'], $_start, [
                    "sum_views" => 0,
                    "cpc_ctr" => '0%',
                    "sum_clicks" => 0,
                    "sum_cpc_clicks" => 0,
                    "sum_revenue" => 0,
                    "ctr" => '0%',
                    "cpd" => 0,
                ]);
                $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
            }
        }
        $i = 0;
        $data = [];
        foreach ($eData as $cam => $val) {
            foreach ($eData[$cam]['days'] as $key => $item) {
                $data[$i][] = $row['clientname'];
                $data[$i][] = $eData[$cam]['info']['product_name'];
                if ($type == Product::TYPE_APP_DOWNLOAD) {
                    $data[$i][] = $eData[$cam]['info']['type'];
                }
                $data[$i][] = $eData[$cam]['info']['app_name'];
                $data[$i][] = $eData[$cam]['info']['ad_type'];
                if ($eData[$cam]['info']['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    $data[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                } else {
                    $data[$i][] = $eData[$cam]['info']['platform'];
                }
                if ($type == Product::TYPE_APP_DOWNLOAD) {
                    $data[$i][] = $eData[$cam]['info']['channel'];
                }
                $data[$i][] = $key;
                $data[$i][] = $item['sum_views'];
                if ($type == Product::TYPE_APP_DOWNLOAD) {
                    $data[$i][] = $item['sum_clicks'];
                    $data[$i][] = $item['ctr'];
                } elseif ($type == Product::TYPE_LINK) {
                    $data[$i][] = $item['sum_cpc_clicks'];
                    $data[$i][] = $item['cpc_ctr'];
                }
                $data[$i][] = $item['sum_revenue'];
                $data[$i][] = round($item['cpd'], 2);
                $sumView += intval(str_replace(',', '', $item['sum_views']));
                $sumRevenue += floatval(str_replace(',', '', $item['sum_revenue']));
                if ($type == Product::TYPE_APP_DOWNLOAD) {
                    $sumDown += intval(str_replace(',', '', $item['sum_clicks']));
                } elseif ($type == Product::TYPE_LINK) {
                    $sumDown += intval(str_replace(',', '', $item['sum_cpc_clicks']));
                }
                $i ++;
            }
        }
        $sum_arr = [
            '汇总',
            '--',
            '--',
            '--',
            '--',
            '--',
            round($sumView, 2),
            round($sumDown, 2),
            '--',
            round($sumRevenue, 2),
            '--'
        ];
        //excel文件名称
        $excelName = '报表-'. str_replace('-', '', $period_start) . '_' .
            str_replace('-', '', $period_end);
        //excel标题
        $sheetRow = [
            '广告主',
            '推广名称',
            '广告名称',
            '广告类型',
            '所属平台',
            '日期',
            '展示量',
            '点击量',
            '点击转化率',
            '支出',
            '平均单价'
        ];
        //excel列宽
        $sheetWidth = [
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 15,
        ];
        $rows = count($data) + 1;
        //excel统计
        $sheetBorder = $type == Product::TYPE_APP_DOWNLOAD ? "A1:M{$rows}" : "A1:K{$rows}";

        //应用下载需要加入推广类型,渠道
        if ($type == Product::TYPE_APP_DOWNLOAD) {
            array_splice($sheetRow, 2, 0, '推广类型');
            array_splice($sheetRow, 6, 0, '渠道');

            array_splice($sum_arr, 1, 0, '--');
            array_splice($sum_arr, 5, 0, '--');
            $sheetRow[9] = '下载量';
            $sheetRow[10] = '下载转化率';
        }
        array_push($data, $sum_arr);
        //生成Excel
        if (!StatService::downloadExcel($excelName, $sheetRow, $sheetWidth, $sheetBorder, $data)) {
            return $this->errorCode(5027);
        }
        return $this->success();//@codeCoverageIgnoreEnd
    }

    /**
     * 按时间导出报表
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | zone_offset | string | 时区 |  | 是 |
     * | type | integer | 计费类型 |  | 是 |
     * | client_id | integer | 广告主id |  | 是 |
     * | product_id | integer | 推广产品id |  | 是 |
     * | campaign_id | integer | 广告id |  | 是 |
     * | span | integer | 时间类型 | 1：小时 2：天 3：月| 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function timeCampaignExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'zone_offset' => "required",
                'type' => 'required|integer|in:0,1',
                'span' => 'required|integer|in:2,3'
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $broker_id = Auth::user()->account->broker->brokerid;
        $period_start = $request->input('period_start');
        $period_end = $request->input('period_end');
        $type = $request->input('type');
        $client_id = $request->input('client_id');
        $product_id = $request->input('product_id');
        $campaign_id = $request->input('campaign_id');

        if (self::SPAN_DAYS == $request->input('span')) {
            $axis = 'days';
        }
        if (self::SPAN_MONTH == $request->input('span')) {
            $axis = 'month';
        }

        $search = ['clients.broker_id' => $broker_id , 'products.type' => $type];
        $groupBy = [];
        if ($client_id) {
            $groupBy = [ 'clients.clientid'];
            $search['clients.clientid'] = $client_id;
        }
        if ($product_id) {
            $groupBy = [ 'products.id'];
            $search['products.id'] = $product_id;
        }
        if ($campaign_id) {
            $groupBy = [ 'campaigns.campaignid'];
            $search['campaigns.campaignid'] = $campaign_id;
        }
        $groupBy = array_merge($groupBy, ['campaigns.revenue_type']);
        $selectInfo = [];
        $res = $this->getCampaignData($selectInfo, $period_start, $period_end, $search, $groupBy, $axis);
        $eData = [];
        if ($type == Product::TYPE_LINK) {
            foreach ($res as $row) {
                $item = $row;
                $item['sum_clicks'] = $row['sum_cpc_clicks'];
                $eData[] = $item;
            }
        } else {
            $eData =  $res;
        }

        //将数据按照时间为键值，重组数据
        $data = StatService::timeCampaignExcel(
            $eData,
            $axis,
            $request->input('period_start'),
            $request->input('period_end'),
            $type
        );
        if (!StatService::downloadExcel($data[0], $data[1], $data[2], $data[3], $data[4])) {
            return $this->errorCode(5027);
        }
        return $this->success();//@codeCoverageIgnoreEnd
    }

    /**
     * @param $selectInfo
     * @param $period_start
     * @param $period_end
     * @param $search
     * @param $groupBy
     * @param string $axis
     * @param string $orderBy
     * @return mixed
     */
    protected function getCampaignData(
        $selectInfo,
        $period_start,
        $period_end,
        $search,
        $groupBy,
        $axis = '',
        $orderBy = ''
    ) {
        $prefix = DB::getTablePrefix();
        $table = 'data_hourly_daily_client';

        if (Auth::user()->account->broker->affiliateid > 0) {
            if (Auth::user()->account->broker->affiliateid > 0) {
                if ($period_start == $period_end) {
                    list($period_start, $period_end, $axis) = StatService::dateConversion(
                        $period_start,
                        $period_end
                    );
                    $table = 'data_summary_ad_hourly';
                } else {
                    $table =  'data_hourly_daily';
                }
            }
        }

        $query = DB::table("{$table} as h")
            ->join('banners', 'h.ad_id', '=', 'banners.bannerid')
            ->join('campaigns', 'banners.campaignid', '=', 'campaigns.campaignid')
            ->join('appinfos', 'appinfos.app_id', '=', 'campaigns.campaignname')
            ->join('products', 'products.id', '=', 'campaigns.product_id')
            ->join('clients', 'campaigns.clientid', '=', 'clients.clientid')
            ->leftjoin('attach_files', 'banners.attach_file_id', '=', 'attach_files.id')
            ->select(
                DB::raw('SUM(total_revenue) as sum_revenue'),
                DB::raw('SUM(impressions) as sum_views')
            );
        //如果是cpc 汇总点击量； 如果是cpd 汇总下载量
        $query->addSelect(DB::raw('IFNULL(SUM(' .$prefix . 'h.`conversions`),0) as `sum_clicks`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'h.`clicks`),0) as `sum_cpc_clicks`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'h.`file_down`),0) as `sum_download_complete`'));

        //查询的数据
        $query->addSelect($selectInfo);

        foreach ($groupBy as $val) {
            $query->groupBy($val);
        }
        foreach ($search as $key => $val) {
            $query->where($key, '=', $val);
        }
        switch ($axis) {
            case 'days':
                $query->addSelect('h.date AS time')
                    ->whereBetween('h.date', [$period_start, $period_end])
                    ->groupBy('time');
                break;
            case 'month':
                $query->addSelect(DB::raw("DATE_FORMAT({$prefix}h.`date`,'%Y-%m') AS time"))
                    ->whereBetween('h.date', [$period_start, $period_end])
                    ->groupBy('time');
                break;
            case 'hours'://小时数查询up_data_summary_ad_hourly表
                $query->addSelect(DB::raw("DATE_FORMAT(DATE_SUB({$prefix}h.`date_time`,
                    INTERVAL -8 HOUR),'%Y-%m-%d %H:00:00') AS time"))
                    ->whereBetween('h.date_time', [$period_start, $period_end])->groupBy('time');
                break;
        }
        $summary = $query->get();
        return json_decode(json_encode($summary), true);
    }
    protected static function attributeLabels()
    {
        return [
            'period_start' => '开始时间',
            'period_end' => '结束时间',
            'span' => '数据分组类型',
            'zone_offset' => '时区',
            'type' => '类型',
            'revenue_type' => '计费类型',
        ];
    }
}
