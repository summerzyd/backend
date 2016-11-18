<?php

namespace App\Http\Controllers\Advertiser;

use App\Components\Formatter;
use App\Models\AffiliateExtend;
use App\Models\Campaign;
use App\Models\Product;
use Auth;
use App\Services\StatService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class StatController extends Controller
{
    const SPAN_HOURS = 1;
    const SPAN_DAYS = 2;
    const SPAN_MONTH = 3;
    /*
     * $axis 分组方式
     * $zoneOffset 时差；
     * $period_start 开始时间
     * $period_end 结束时间
     */

    private $axis;
    private $zoneOffset;
    private $period_start;
    private $period_end;
    /*
     * 对统计报表传入参数进行处理
     *
     */
    private function transFormInput($request)
    {
        $this->zoneOffset = $request->input('zone_offset');
        $span = $request->input('span');
        $this->period_start = $request->input('period_start');
        $this->period_end = $request->input('period_end');

        //按小时，天，月查询
        if (self::SPAN_HOURS == $span) {
            $this->axis='hours';
        }
        if (self::SPAN_DAYS == $span) {
            $this->axis='days';
        }
        if (self::SPAN_MONTH == $span) {
            $this->axis='month';
        }
        return true;
    }
    /**
     * 获取统计报表数据
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * name | type | description | restraint | required
     * period_start | date | 开始时间 |  | 是|
     * period_end | date |  结束时间 |  | 是|
     * span | string | 数据分组类型 | (hours days month) | 是|
     * zone_offset | string | 时区 |  | 是|
     * type | int | 类型 | 0：安装包下载，1：链接推广 | 否|
     *
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | sub name | type | description | restraint | required|
     * | :--: | :--:  |:--: | :--: | :--------: | :-------: | :-----: |
     * | obj |  |  | object | 汇总信息 |  | 是|
     * | |  sum_views |  |integer | 展示量 |  | 是|
     * | |  sum_clicks |  | integer | 下载量 |  | 是|
     * | |  sum_cpc_clicks |  |integer | 点击量 |  | 是|
     * | |  sum_revenue|  |decimal | 支出 |  | 是|
     * | statsCharts |  | | array | 图表数据 |  | 是|
     * | |  id |  | integer | 广告计划id |  | 是|
     * | |  product_id |  | integer | 产品ID |  | 是|
     * | |  product_name |  | string | 广告名称 |  | 是|
     * | |  product_icon |  | string | 图标 |  | 是|
     * | |  name |  | string | 推广名称 |  | 是|
     * | |  platform |  | int | 所属平台 |  | 是|
     * | |  ad_type |  | int | 广告类型 |  | 是|
     * | |  sum_views |  | integer | 展示量 |  | 是|
     * | |  sum_clicks |  | integer | 下载量 |  | 是|
     * | |  sum_cpc_clicks |  | integer | 点击量 |  | 是|
     * | |  sum_revenue |  | decimal | 支出 |  | 是|
     * | |  type |  | int | 类型 |  | 是|
     * | |  time |  | string | 时间 |  | 是|
     * | entitiesData |  |  | string | 表格数据 |  | 是|
     * | |  id |  | integer | 广告计划id |  | 是|
     * | |  product_id |  |  integer | 产品ID |  | 是|
     * | |  product_name |  | string | 广告名称 |  | 是|
     * | |  product_icon |  | string | 图标 |  | 是|
     * | |  name |  |  string | 推广名称 |  | 是|
     * | |  platform |  |  integer | 所属平台 |  | 是|
     * | |  ad_type |  |  integer | 广告类型 |  | 是|
     * | |  sum_views |  |  integer | 展示量 |  | 是|
     * | |  sum_clicks |  |  integer | 下载量 |  | 是|
     * | |  sum_cpc_clicks |  |  integer | 点击量 |  | 是|
     * | |  sum_revenue |  | decimal | 支出 |  | 是|
     * | |  type |  | integer | 类型 |  | 是|
     * | |  time |  | string | 时间 |  | 是|
     * | |  ctr |  | decimal | 下载转化率 | ctr = 下载数/展示量 | 是|
     * | |  cpc_ctr |  |decimal | 点击转化率 | cpc_ctr=点击数/展示量 | 是|
     * | |  cpd |  | decimal | 下载单价 | cpd = 支出/下载数 | 是|
     * | child |  |  | array |  |  | 是|
     * |  | | channel | string | 渠道 |  | 是|
     * |  | | sum_views | integer | 展示量 |  | 是|
     * |  | |sum_clicks | integer | 下载量 |  | 是|
     * |  | | sum_cpc_clicks | integer | 点击量 |  | 是|
     * |  | | cpc_ctr | decimal | 点击转化率 |  | 是|
     * |  | | ctr | decimal | 下载转化率 |  | 是|
     * |  | | sum_revenue | decimal | 支出 |  | 是|
     * |  | | cpd | decimal | 下载单价 |  | 是|
     */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required|in:2,3',
                'zone_offset' => 'required',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $list = [];
        $sum_views = 0;
        $sum_clicks = 0;
        $sum_cpc_clicks = 0;
        $sum_revenue = 0;
        if ($this->transFormInput($request)) {
            //安装包下载、链接下载
            $type = $request->input('type');
            $type = empty($type) ? 0 : $type;
            $statChart = StatService::getCampaignsData(
                Auth::user()->account->client->clientid,
                $this->period_start,
                $this->period_end,
                $this->axis,
                $this->zoneOffset,
                $type
            );

            //计算出汇总的数据
            $chart = [];
            foreach ($statChart as $val) {
                $sum_views += $val['sum_views'];
                $sum_clicks += $val['sum_clicks'];
                $sum_cpc_clicks += $val['sum_cpc_clicks'];
                $sum_revenue += $val['sum_revenue'];
                $item = $val;
                $item['ad_type'] = Campaign::getAdTypeLabels($val['ad_type']);
                //如果广告类型为AppStore 则显示IOS
                if ($val['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    $item['platform'] =Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                } else {
                    $item['platform'] = Campaign::getPlatformLabels($val['platform']);
                }
                $chart[] = $item;
            }
            //获取报表及二级展开数据 ($chart, $statData)
            $statData = StatService::summaryData($chart);
            $list['statChart'] = $chart;
            $list['statData'] = $statData;
            $sum_revenue = round($sum_revenue, 2);
        }
        return $this->success(['sum_views'=> $sum_views,
            'sum_clicks' => $sum_clicks,
            'sum_cpc_clicks' => $sum_cpc_clicks,
            'sum_revenue' => $sum_revenue, ], null, $list);
    }

    /**
     * 3.3 导出报表
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start |  integer | 开始时间|  | 是 |
     * | period_end |  string | 结束时间 |  | 是 |
     * | zoneOffset |  string | 时差 |  | 是 |
     * | type |  string | 类型 |  | 是 |
     * | campaign_id |  string | 广告ID |  | 否 |
     * | product_id |  string | 产品ID |  | 否 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function campaignExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'zoneOffset' => "required",
                'type' => 'required',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        $sumView = 0; //展示量
        $sumDown = 0; //下载量
        $sumRevenue = 0; //支出
        $data = [];

        if ($this->transFormInput($request)) {
            $this->axis = 'total';
            $type = $request->get('type', Product::TYPE_APP_DOWNLOAD);
            $productId = $request->input('product_id');
            $campaignId = $request->input('campaign_id');
            $statsCharts = StatService::getCampaignsExcelData(
                Auth::user()->account->client->clientid,
                $this->period_start,
                $this->period_end,
                $this->axis,
                $request->get('zoneOffset'),
                $type,
                $productId,
                $campaignId
            );

            $periodStart = date('Ymd', strtotime($request->input('period_start')));
            $periodEnd = date('Ymd', strtotime($request->input('period_end')));

            $entitiesData = StatService::makeData($statsCharts);//计算CTR信息
            $i = 0;
            foreach ($entitiesData as $row) {
                $data[$i][] = $row['product_name'];
                if ($type == Product::TYPE_APP_DOWNLOAD) {
                    $data[$i][] = $row['type'];
                }
                $data[$i][] = $row['app_name'];
                $data[$i][] = $row['ad_type'];
               // $data[$i][] = $row['platform'];
                if ($row['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    $data[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                } else {
                    $data[$i][] = $row['platform'];
                }
                if ($type == Product::TYPE_APP_DOWNLOAD) {
                    $data[$i][] = $row['channel'];
                }
                $data[$i][] = $row['sum_views'];
                if ($type == Product::TYPE_APP_DOWNLOAD) {
                    $data[$i][] = $row['sum_clicks'];
                    $data[$i][] = $row['ctr'];
                } elseif ($type == Product::TYPE_LINK) {
                    $data[$i][] = $row['sum_cpc_clicks'];
                    $data[$i][] = $row['cpc_ctr'];
                }
                $data[$i][] = $row['sum_revenue'];
                $data[$i][] = round($row['cpd'], 2);
                if ($type == Product::TYPE_APP_DOWNLOAD) {
                    $sumDown += intval(str_replace(',', '', $row['sum_clicks']));
                } elseif ($type == Product::TYPE_LINK) {
                    $sumDown += intval(str_replace(',', '', $row['sum_cpc_clicks']));
                }
                $sumView += intval(str_replace(',', '', $row['sum_views']));
                $sumRevenue += floatval(str_replace(',', '', $row['sum_revenue']));
                $i ++;
            }
            //excel文件名称
            $excelName = '我的报表-'. str_replace('-', '', $periodStart) . '_' .
                str_replace('-', '', $periodEnd);
            //excel标题
            $sheetRow = [
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
                'K' => 15
            ];
            $rows = count($data) + 1;
            //excel统计
            $sheetBorder = $type == Product::TYPE_APP_DOWNLOAD ? "A1:K{$rows}" : "A1:I{$rows}";
            $sum_arr = [
                '汇总',
                '--',
                '--',
                '--',
                round($sumView, 2),
                round($sumDown, 2),
                '--',
                round($sumRevenue, 2),
                '--'
            ];
            //应用下载需要加入推广类型,渠道
            if ($type == Product::TYPE_APP_DOWNLOAD) {
                $sheetRow[5] = '下载量';
                $sheetRow[6] = '下载转化率';
                array_splice($sheetRow, 1, 0, '推广类型');
                array_splice($sheetRow, 5, 0, '渠道');

                array_splice($sum_arr, 1, 0, '--');
                array_splice($sum_arr, 5, 0, '--');
            }
            array_push($data, $sum_arr);
            //生成Excel
            if (!StatService::downloadExcel($excelName, $sheetRow, $sheetWidth, $sheetBorder, $data)) {
                return $this->errorCode(5027);
            }
            return $this->success();
        }
        return $this->errorCode(5002);//@codeCoverageIgnoreEnd
    }

    /**
     * 广告主导出每日报表
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start |  integer | 开始时间|  | 是 |
     * | period_end |  string | 结束时间 |  | 是 |
     * | type |  string | 类型 |  | 是 |
     * | campaign_id |  string | 广告ID |  | 否 |
     * | product_id |  string | 产品ID |  | 否 |
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
        $data = [];
        if ($this->transFormInput($request)) {
            $campaignId = $request->input('campaign_id');
            $productId =$request->input('product_id');
            $type = $request->get('type', Product::TYPE_APP_DOWNLOAD);
            //获取数据
            $statsCharts = StatService::findAdvertiserDailyExcelData(
                $this->period_start,
                $this->period_end,
                $this->axis,
                $type,
                Auth::user()->account->client->clientid,
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
            $dayCount = (strtotime($this->period_end) - strtotime($this->period_start)) / 86400;
            //对数据进行循环，不存在的日期需补0
            foreach ($eData as $k => $v) {
                $_start = $this->period_start;
                for ($i = 0; $i <= $dayCount; $i++) {
                    $eData[$k]['days']  = array_add($eData[$k]['days'], $_start, [
                        "sum_views" => 0,
                        "sum_clicks" => 0,
                        "sum_cpc_clicks" => 0,
                        "ctr" => '0%',
                        "cpc_ctr" => '0%',
                        "cpd" => 0,
                        "sum_revenue" => 0,
                    ]);
                    $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
                }
            }
            $i = 0;
            $data = [];
            foreach ($eData as $campaign => $val) {
                foreach ($eData[$campaign]['days'] as $k_day => $item) {
                    $data[$i][] = $eData[$campaign]['info']['product_name'];
                    if ($type == Product::TYPE_APP_DOWNLOAD) {
                        $data[$i][] = $eData[$campaign]['info']['type'];
                    }
                    $data[$i][] = $eData[$campaign]['info']['app_name'];
                    $data[$i][] = $eData[$campaign]['info']['ad_type'];
                    if ($eData[$campaign]['info']['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                        $data[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                    } else {
                        $data[$i][] = $eData[$campaign]['info']['platform'];
                    }
                    if ($type == Product::TYPE_APP_DOWNLOAD) {
                        $data[$i][] = $eData[$campaign]['info']['channel'];
                    }
                    $data[$i][] = $k_day;
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
                    if ($type == Product::TYPE_APP_DOWNLOAD) {
                        $sumDown += intval(str_replace(',', '', $item['sum_clicks']));
                    } elseif ($type == Product::TYPE_LINK) {
                        $sumDown += intval(str_replace(',', '', $item['sum_cpc_clicks']));
                    }
                    $sumView += intval(str_replace(',', '', $item['sum_views']));
                    $sumRevenue += floatval(str_replace(',', '', $item['sum_revenue']));
                    $i ++;
                }
            }
            //excel文件名称
            $excelName = '我的报表-'. str_replace('-', '', $this->period_start) . '_' .
                str_replace('-', '', $this->period_end);
            //excel标题
            $sheetRow = [
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
                round($sumView, 2),
                round($sumDown, 2),
                '--',
                round($sumRevenue, 2),
                '--'
            ];
            //应用下载需要加入推广类型,渠道
            if ($type == Product::TYPE_APP_DOWNLOAD) {
                $sheetRow[6] = '下载量';
                $sheetRow[7] = '下载转化率';
                array_splice($sheetRow, 1, 0, '推广类型');
                array_splice($sheetRow, 5, 0, '渠道');

                array_splice($sum_arr, 1, 0, '--');
                array_splice($sum_arr, 5, 0, '--');
            }
            array_push($data, $sum_arr);
            //生成Excel
            if (!StatService::downloadExcel($excelName, $sheetRow, $sheetWidth, $sheetBorder, $data)) {
                return $this->errorCode(5027);
            }
            return $this->success();//@codeCoverageIgnoreEnd
        }
    }

    /**
     * /**
     * 导出自营媒体广告主数据
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start |  integer | 开始时间|  | 是 |
     * | period_end |  string | 结束时间 |  | 是 |
     * | zone_offset |  string | 广告ID |  | 是 |
     * | type |  string | 类型 |  | 是 |
     * | campaign_id |  string | 广告ID |  | 否 |
     * | product_id |  string | 产品ID |  | 否 |
     * @param Request $request
     * 按时间导出报表
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
                'type' => 'required',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $data = [];
        $campaign_id = $request->input('campaign_id');
        $product_id =$request->input('product_id');
        if ($this->transFormInput($request)) {
            $type = $request->get('type', Product::TYPE_APP_DOWNLOAD);
            $statsCharts = StatService::getTimeCampaignsExcelData(
                Auth::user()->account->client->clientid,
                $this->period_start,
                $this->period_end,
                $this->axis,
                $this->zoneOffset,
                $campaign_id,
                $product_id,
                $type
            );
            $eData = [];
            if ($type == Product::TYPE_LINK) {
                foreach ($statsCharts as $row) {
                    $item = $row;
                    $item['sum_clicks'] = $row['sum_cpc_clicks'];
                    $eData[] = $item;
                }
            } else {
                $eData =  $statsCharts;
            }

            //将数据按照时间为键值，重组数据
            $data = StatService::timeCampaignExcel(
                $eData,
                $this->axis,
                $request->input('period_start'),
                $request->input('period_end'),
                $type
            );
            //excel文件名称
            if (!StatService::downloadExcel($data[0], $data[1], $data[2], $data[3], $data[4])) {
                return $this->errorCode(5027);
            }
            return $this->success();
            //@codeCoverageIgnoreEnd
        }
    }
    /**
     * 广告主首页概览报表
     * @return \Illuminate\Http\Response
     *
     * | name | sub_name | type | description | restraint | required|
     * | :--: | :--: | :--: | :--------: | :-------: | :-----: |
     * | revenue_type | | integer | 计费类型 |  | 是 |
     * | sum_revenue | | integer | 消耗 |  | 是 |
     * | sum_cpc_clicks | | integer | 点击量 |  | 是 |
     * | sum_clicks | | integer | 下载量 |  | 是 |
     * | icon | | string | 图标 |  | 是 |
     * | data | | array | 每天的数据 |  | 是 |
     * |  | price | integer | 单价 |  | 是 |
     * |  | sum_clicks | integer | 下载量/点击量 |  | 是 |
     * |  | sum_revenue | integer | 消耗 |  | 是 |
     * |  | time | array | 时间 |  | 是 |
     */
    public function report()
    {
        $client_id = Auth::user()->account->client->clientid;
        $this->period_start = date('Y-m-d', strtotime('-30 day'));
        $this->period_end = date('Y-m-d', strtotime('-1 day'));
        $axis = 'report';
        $list = StatService::getReport(
            $client_id,
            $this->period_start,
            $this->period_end,
            $axis
        );
        return $this->success(null, null, $list);
    }

    protected static function attributeLabels()
    {
        return [
            'period_start' => '开始时间',
            'period_end' => '结束时间',
            'span' => '数据分组类型',
            'zone_offset' => '时区',
            'zoneOffset' => '时区',
            'type' => '类型'
        ];
    }
    /**
     * 自营媒体广告主-报表数据
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start |  integer | 开始时间|  | 是 |
     * | period_end |  string | 结束时间 |  | 是 |
     * | span |  string | 分组类型 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | app_name | string | 广告名称 |  | 是|
     * | campaignid | string | 广告id |  | 是|
     * | zoneid | integer | 广告位id |  | 是|
     * | zonename | string | 广告位名称 |  | 是|
     * | sum_views | integer | 展示量 |  | 是|
     * | sum_clicks | integer | 下载量 |  | 是|
     * | sum_revenue | decimal | 消耗总数 |  | 是|
     * | sum_revenue_gift | decimal | 消耗赠送金 |  | 是|
     * | time | date | 时间 |  | 是|
     */
    public function selfIndex(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        if (Input::get('span') == self::SPAN_MONTH) {
            $axis = StatService::AXIS_MONTH;
        } else {
            $axis = StatService::AXIS_DAYS;
        }
        $start = Input::get('period_start');
        $end = Input::get('period_end');
        $zoneOffset = Input::get('zone_offset');
        $client = Auth::user()->account->client;
        if ($start == $end) {
            list($start, $end, $axis) = StatService::dateConversion($start, $end, $zoneOffset);
        }
        $statsCharts = StatService::findSelfTrafficker(
            $start,
            $end,
            $axis,
            $client->affiliateid,
            $client->clientid
        );
        return $this->success(
            [
                'start' => Input::get('period_start'),
                'end' => Input::get('period_end')
            ],
            null,
            $statsCharts
        );
    }
    /**
     * 导出自营媒体广告主数据
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start |  integer | 开始时间|  | 是 |
     * | period_end |  string | 结束时间 |  | 是 |
     * | campaignid |  string | 广告ID |  | 是 |
     * | zoneid |  string | 广告位ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | app_name | string | 广告名称 |  | 是|
     * | zonename | string | 广告位名称 |  | 是|
     * | sum_views | integer | 展示量 |  | 是|
     * | sum_clicks | integer | 下载量 |  | 是|
     * | sum_revenue | decimal | 消耗总数 |  | 是|
     * | sum_revenue_recharge | decimal | 消耗充值金 |  | 是|
     * | sum_revenue_gift | decimal | 消耗赠送金 |  | 是|
     * | time | date | 时间 |  | 是|
     */
    public function selfZoneExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $start = Input::get('period_start');
        $end = Input::get('period_end');
        $client = Auth::user()->account->client;
        $campaignId = Input::get('campaignid');
        $zoneId = Input::get('zoneid');
        $cpa_revenue_type = AffiliateExtend::whereMulti(
            [
                'revenue_type' => Campaign::REVENUE_TYPE_CPA,
                'affiliateid' =>$client->affiliateid
            ]
        )
            ->first();
        $statsCharts = StatService::findSelfTrafficker(
            $start,
            $end,
            'days',
            $client->affiliateid,
            $client->clientid,
            $campaignId,
            $zoneId
        );
        $eData = [];
        foreach ($statsCharts as $row) {
            $eData[$row['ad_id'].$row['zoneid']]['info']= $row;
            $eData[$row['ad_id'].$row['zoneid']]['days'][$row['time']]= $row;
        }
        $dayCount = (strtotime($end) - strtotime($start)) / 86400;
        //对数据进行循环，不存在的日期需补0
        foreach ($eData as $k => $v) {
            $_start = $start;
            for ($i = 0; $i <= $dayCount; $i++) {
                $eData[$k]['days']  = array_add($eData[$k]['days'], $_start, [
                    "sum_views" => 0,
                    "sum_clicks" => 0,
                    "sum_cpa" => 0,
                    "sum_cpc_clicks" => 0,
                    "ctr" => '0%',
                    "cpc_ctr" => '0%',
                    "cpd" => 0,
                    "sum_revenue" => 0,
                    "sum_revenue_gift" => 0,
                ]);
                $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
            }
        }
        $column = [
            'app_name' => '广告',
            'zonename' => '广告位',
            'date' => '日期',
            'sum_views' => '展示量',
            'sum_clicks' => '下载量',
            'ctr' => '下载转化率',
            'sum_revenue' => '消耗（总数）',
            'sum_revenue_recharge' => '消耗（充值金）',
            'sum_revenue_gift' => '消耗（赠送金）',
            'cpd' => '下载单价',
        ];
        $list = [];
        $i = 0;
        $sum_views = 0;
        $sum_clicks = 0;
        $sum_revenue = 0;
        $sum_revenue_gift = 0;
        $sum_cpa = 0;
        foreach ($eData as $campaign => $val) {
            foreach ($eData[$campaign]['days'] as $k_day => $item) {
                $list[$i][] = $eData[$campaign]['info']['app_name'];
                $list[$i][] = $eData[$campaign]['info']['zonename'];
                $list[$i][] = $k_day;
                $list[$i][] = Formatter::asDecimal($item['sum_views']);
                $sum_views += $item['sum_views'];
                $list[$i][] = Formatter::asDecimal($item['sum_clicks']);
                $sum_clicks += $item['sum_clicks'];
                $list[$i][] = StatService::getCtr($item['sum_views'], $item['sum_clicks']);
                $list[$i][] = Formatter::asDecimal($item['sum_revenue']);
                $sum_revenue += $item['sum_revenue'];
                $list[$i][] = Formatter::asDecimal($item['sum_revenue'] - $item['sum_revenue_gift']) ;
                $list[$i][] = Formatter::asDecimal($item['sum_revenue_gift']);
                $sum_revenue_gift += $item['sum_revenue_gift'];
                $list[$i][] = StatService::getCpd($item['sum_revenue'], $item['sum_clicks']);
                if ($cpa_revenue_type) {
                    $list[$i][] = Formatter::asDecimal($item['sum_cpa']);
                    $list[$i][] = StatService::getCpd($item['sum_revenue'], $item['sum_cpa']);
                    $sum_cpa += $item['sum_cpa'];
                }
                $i ++;
            }
        }
        $excelName = '广告主报表-'. str_replace('-', '', $start) . '_' .
            str_replace('-', '', $end);
        $sum_arr = [
            '汇总',
            '--',
            '--',
            Formatter::asDecimal($sum_views),
            Formatter::asDecimal($sum_clicks),
            '--',
            Formatter::asDecimal($sum_revenue),
            Formatter::asDecimal($sum_revenue - $sum_revenue_gift),
            Formatter::asDecimal($sum_revenue_gift),
            '--'
        ];
        if ($cpa_revenue_type) {
            array_push($column, 'CPA量');
            array_push($column, 'CPA单价');
            array_push($sum_arr, $sum_cpa);
        }
        array_push($list, $sum_arr);
        StatService::downloadCsv($excelName, $column, $list);//@codeCoverageIgnoreEnd
    }
    /**
     *  * @return \Illuminate\Http\Response
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | app_name | string | 广告名称 |  | 是|
     * | zonename | string | 广告位名称 |  | 是|
     * | sum_views | integer | 展示量 |  | 是|
     * | sum_clicks | integer | 下载量 |  | 是|
     * | sum_revenue | decimal | 消耗总数 |  | 是|
     * | revenue_type | integer | 计费类型 |  | 是|
     * | price | decimal | 单价 |  | 是|
     * | time | date | 时间 |  | 是|
     */
    public function selfReport()
    {
        $client = Auth::user()->account->client;
        $start = date('Y-m-d', strtotime('-30 day'));
        $end = date('Y-m-d', strtotime('-1 day'));
        $statsCharts = StatService::findSelfTrafficker(
            $start,
            $end,
            'days',
            $client->affiliateid,
            $client->clientid
        );
        $eData = [];
        foreach ($statsCharts as $val) {
            $item = $val;
            $item['id'] = $val['campaignid'];
            $item['name'] = $val['app_name'];
            $item['icon'] = $val['app_show_icon'];
            $eData[] = $item;
        }
        $list = StatService::regroupAdvertiserReport($eData, $start);
        return $this->success(null, null, $list);
    }
}
