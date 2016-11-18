<?php
namespace App\Http\Controllers\Trafficker;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Models\AffiliateExtend;
use App\Models\Campaign;
use App\Models\Product;
use Auth;
use App\Services\StatService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Components\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class StatController extends Controller
{
    const SPAN_HOURS = 1;
    const SPAN_DAYS = 2;
    const SPAN_MONTH = 3;
    /**
     * $axis 分组方式
     * $zoneOffset 时差；
     * $period_start 开始时间
     * $period_end 结束时间
     * $revenue_type 计费类型
     */
    public $axis;
    public $zoneOffset;
    public $period_start;
    public $period_end;
    public $revenue_type;

    /**
     * @param $request
     * @return bool
     */
    private function transFormInput($request)
    {
        $this->zoneOffset = $request->input('zone_offset');
        $this->revenue_type = $request->input('revenue_type');
        $span = $request->input('span');
        $this->period_start = $request->input('period_start');
        $this->period_end = $request->input('period_end');

        //按小时，天，月查询
        if (self::SPAN_HOURS == $span) {
            $this->axis = 'hours';
        }
        if (self::SPAN_DAYS == $span) {
            $this->axis = 'days';
        }
        if (self::SPAN_MONTH == $span) {
            $this->axis = 'month';
        }

        return true;
    }

    protected function getTimePoint($type)
    {
        switch ($type) {
            case 1:
                $this->period_start = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y")));
                $this->period_end = date('Y-m-d');
                break;
            case 2:
                $this->period_start = date('Y-m-d', mktime(
                    0,
                    0,
                    0,
                    date("m"),
                    date("d") - date("w") + 1 - 7,
                    date("Y")
                ));
                $this->period_end = date('Y-m-d', mktime(23, 59, 59, date("m"), date("d") - date("w"), date("Y")));
                break;
            case 3:
                $this->period_start = date('Y-m-d', mktime(0, 0, 0, date("m"), 1, date("Y")));
                $this->period_end = null;
                break;
            case 4:
                $this->period_start = date('Y-m-d', mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
                $this->period_end = date('Y-m-d', mktime(23, 59, 59, date("m"), 0, date("Y")));
                break;
            case 5:
                $this->period_start = null;
                $this->period_end = null;
                break;
            default:
                return $this->errorCode(5000);

        }
        return true;
    }

    /**
     * 获取头部导航栏菜单
     * @return \Illuminate\Http\Response
     *
     * name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * revenue_type |  integer |  计费类型类型 |  | 是 |
     * revenue_type_label | string | CPD或者CPC |  | 是 |
     */
    public function menu()
    {
        $affiliate_id = Auth::user()->account->affiliate->affiliateid;
        $obj = [Campaign::REVENUE_TYPE_CPD, Campaign::REVENUE_TYPE_CPC, Campaign::REVENUE_TYPE_CPA];
        foreach ($obj as $key => $val) {
            $list[$val]['revenue_type'] = $val;
            $list[$val]['revenue_type_label'] = Campaign::getRevenueTypeLabels($val);

        }
        $list = array_values($list);
        return $this->success(null, null, $list);
    }

    /**
     * 获取图表，报表显示字段
     *
     * name | type | description | restraint | required
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * revenue_type | integer |  数据类型 | 10：CPD 2：CPC|  是 |
     * item_num | integer | 统计类型 | 1：广告位；2：广告 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * name | sub name | type | description | restraint | required |
     * | :--: | :--: |:--: | :--------: | :-------: | :-----: |
     *  chart_item |  | array | 报表下拉框选择字段 |  json数组 | 是 |
     * | | field | sting | 对应的字段 |  | 是 |
     * | | name | string | 字段名称 |  | 是 |
     * | | default | integer | 默认显示 | 0：非默认 | 是 |
     * | | chart_type | string | 图示类型 | ‘column’: 柱状图 'line':现状图| 是 |
     * | | format | string | 格式化类型 | ‘n2’: 数字 'p2': 百分比| 是 |
     * | |  |  |  | 注：第一个字符表示数据类型，第一个字符表示保留小数点位数 | |
     * | | field_type | string | 数据类型 | ‘basics’： 基础数据 ；calculation’： 算数数据| 是 |
     * | | arithmetic | array | 算数表达式数字 | ["sum_clicks", "/", "sum_views"] | |
     * table_column_item |  | array | table显示列数组 |  | 是 |
     * | | field | sting | 对应的字段 |  | 是 |
     * | | name | string | 字段名称 |  | 是 |
     * | | menu | integer | 是否可影藏 | 0：不能影藏 | 是 |
     * | |  |  |  | 1：可以影藏 | |
     * | | summary | integer | 是否汇总 | 0：不汇总 | 是 |
     * | |  |  |  | 1：汇总 | |
     * | | format | string | 格式化类型 | ‘n2’: 数字 'p2': 百分比 | 否 |
     * | |  |  |  | 注：第一个字符表示数据类型，第一个字符表示保留小数点位数 | |
     * | | width | string | 列宽度 |  | 否 |
     */
    public function columnList(Request $request)
    {
        if (($ret = $this->validate($request, [
                'revenue_type' => 'required',
                'item_num' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $list = [];
        //获取计费类型及报表类型广告或者广告位
        $revenue_type = $request->input('revenue_type');
        $type = $request->input('item_num');
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $affArr = Config::get('biddingos.affiliate_download_complete');
        $chart_item = Config::get('biddingos.trf_chart_fields');
        if ($type == 1) {
            $table_column_item = Config::get('biddingos.trf_cpd_zone_fields');
        } else {
            $table_column_item = Config::get('biddingos.trf_cpd_cam_fields');
        }
        //CPC广告应该将下载量改为点击量 下载转化率改为点击转化率

        if ($revenue_type == Campaign::REVENUE_TYPE_CPC) {
            //print_r($revenue_type);exit;
            foreach ($chart_item as $key => $val) {
                if ($val['field'] == 'sum_clicks') {
                    $chart_item[$key]['name'] = '点击量';
                }
                if ($val['field'] == 'ctr') {
                    $chart_item[$key]['name'] = '点击转化率';
                }
            }
            Array_push($chart_item, [
                'field' => 'ecpm',
                'name' => 'eCPM',
                'chart_type' => 'column',
                'format' => 'n2',
                'default' => 0,
                'field_type' => 'calculation',
                'arithmetic' => ["sum_revenue", "*", 1000, "/", "sum_views"],
            ]);
            foreach ($table_column_item as $ke => $va) {
                if ($va['field'] == 'sum_clicks') {
                    $table_column_item[$ke]['name'] = '点击量';
                }
                if ($va['field'] == 'ctr') {
                    $table_column_item[$ke]['name'] = '点击转化率%';
                }
            }
        } elseif ($revenue_type == Campaign::REVENUE_TYPE_CPA) {
            foreach ($chart_item as $key => $val) {
                if ($val['field'] == 'sum_clicks') {
                    $chart_item[$key]['name'] = 'cpa';
                }
                if ($val['field'] == 'ctr') {
                    $chart_item[$key]['name'] = '激活转化率';
                }
                if ($val['field'] == 'media_cpd') {
                    $chart_item[$key]['name'] = 'CPA均价';
                }
            }
            Array_push($chart_item, [
                'field' => 'ecpm',
                'name' => 'eCPM',
                'chart_type' => 'column',
                'format' => 'n2',
                'default' => 0,
                'field_type' => 'calculation',
                'arithmetic' => ["sum_revenue", "*", 1000, "/", "sum_views"],
            ]);
            foreach ($table_column_item as $ke => $va) {
                if ($va['field'] == 'sum_clicks') {
                    $table_column_item[$ke]['name'] = 'cpa';
                }
                if ($va['field'] == 'ctr') {
                    $table_column_item[$ke]['name'] = '激活转化率%';
                }
            }
        }
        if (in_array($affiliateId, $affArr)) {
            array_push($table_column_item, [
                'field'   => 'sum_download_complete',
                'name'  => '下载量监控',
                'format' => 'n0',
                'menu' => 1,
                'summary'   => 1,
            ]);
            array_push($chart_item, [
                'field'   => 'sum_download_complete',
                'name'  => '下载量监控',
                'chart_type' => 'column',
                'format'   => 'n0',
                'default'   => 0,
                'field_type' => 'basics',
            ]);
        }
        $list['chart_item'] = $chart_item;
        $list['table_column_item'] = array_values($table_column_item);
        return $this->success(null, null, $list);

    }

    /**
     * @param $request
     * @param int $type
     * @param int $label
     * @return \Illuminate\Http\Response|mixed
     */
    protected function statChart($request, $label, $type = 0)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required',
                'zone_offset' => 'required',
                'revenue_type' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        if ($this->transFormInput($request)) {
            $statChart = StatService::getTraffickerData(
                Auth::user()->account->affiliate->affiliateid,
                $this->period_start,
                $this->period_end,
                $this->axis,
                $this->zoneOffset,
                $this->revenue_type
            );
            $chart = [];
            $eData = [];
            foreach ($label as $value) {
                $eData[$value] = 0;
            }
            //汇总报表数据
            foreach ($statChart as $val) {
                foreach ($label as $value) {
                    $eData[$value] += $val[$value];
                }
                $item = $val;
                $item['ad_type'] = Campaign::getAdTypeLabels($val['ad_type']);
                // $item['platform'] = Campaign::getPlatformLabels($val['platform']);
                if ($item['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    $item['platform'] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                } else {
                    //如果是广告位报表则显示的是广告位的平台，广告显示的是广告的平台
                    if ($type) {
                        $item['platform'] = Campaign::getPlatformLabels($val['cam_platform']);
                    } else {
                        $item['platform'] = Campaign::getPlatformLabels($val['platform']);
                    }
                }
                $item['product_type'] = Product::getTypeLabels($val['product_type']);
                $item['zone_type'] = $val['ad_type'];
                $item['zone_type_label'] = $item['ad_type'];
                $chart[] = $item;
            }
            $eData['chart'] = $chart;
        }
        return $eData;
    }

    /**
     * 获取广告位数据
     *
     *  name | type | description | restraint | required |
     *  | :--: | :--: | :--------: | :-------: | :-----: |
     *  revenue_type | integer |  数据类型 | 10：CPD  2：CPC|  是 |
     *  period_start | date | 起始时间 |  | 是 |
     *  period_end | date | 终止时间 |  | 是 |
     *  span | integer | 数据分组类型 | 1：hours 2：days 3：month | 是 |
     * zone_offset | string | 时区 | -8 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * name | sub name | sub name | type | description | restraint | required |
     * | :--: | :--: |  :--: | :--: |:--------: | :-------: | :-----: |
     * statChart |  |  | array | 图表数据 |  | |
     * | | zone_type |  | integer | 广告位类别 |  |  是 |
     * | | zone_type_label |  | string | 广告位类别名称 | 应用市场 Banner Feeds | 是 |
     * | | ad_list |  | array | json对象数组 |  | 是 |
     * | |  | zone_id | integer | 广告位id |  | 是 |
     * | |  | zone_name | string | 广告位名称 |  | 是 |
     * | |  | sum_views | integer | 展示量 |  | 是 |
     * | |  | sum_clicks | integer | 下载量/点击量 |  | 是 |
     * | |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  | time | date | 时间 |  | 是 |
     *  statData |  |  | array | 表格数据 |  | 是 |
     * | | zone_id |  | integer |  广告位id |  | 是 |
     * | | zone_name |  | string | 广告位名称 |  | 是 |
     * | | platform |  | string | 平台 |  | 是 |
     * | |  sum_views |  | integer | 展示量 |  | 是 |
     * | |  sum_clicks |  | integer | 下载量 |  | 是 |
     * | |  sum_revenue |  | decimal | 收入 |  | 是 |
     * | |  ctr |  | decimal | 下载转化率/点击转化率 |  | 是 |
     * | |  media_cpd |  | decimal | 平均单价 |  | 是 |
     * | |  ecpm |  | decimal | ecpm |  | 是 |
     * | |  zone_type |  | integer | 广告位类别 |  | 是 |
     * | | zone_type_label |  | string | 广告位类别名称 |  | 是 |
     * | | child |  | array |  |  | 是 |
     * | |  | ad_id | integer |  广告id |  | 是 |
     * | |  | ad_name | string | 广告名称 |  | 是 |
     * | |  | ad_icon | string | 广告图标地址 |  | 是 |
     * | |  | ad_type | string | 广告类型 |  | 是 |
     * | |  | product_id | integer | 推广id |  | 是 |
     * | |  | product_name | string | 推广名称 |  | 是 |
     * | |  | product_icon | string | 推广图标 |  | 是 |
     * | |  | product_type | string | 推广类型 |  | 是 |
     * | |  | sum_views | integer | 展示量 |  | 是 |
     * | |  | sum_clicks | integer | 下载量/点击量 |  | 是 |
     * | |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  | ctr | decimal | 下载转化率/点击转化率 |  | 是 |
     * | |  | media_cpd | decimal | 平均单价 |  | 是 |
     * | |  | ecpm | decimal | ecpm |  | 是 |
     * obj |  |  | object | 汇总信息 |  | 是 |
     * | | sum_views |  | integer | 展示量 |  | 是 |
     * | | sum_clicks |  | integer | 下载量/点击量 |  | 是 |
     * | | sum_revenue |  | decimal | 收入 |  | 是 |
     */
    public function zone(Request $request)
    {
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $affArr = Config::get('biddingos.affiliate_download_complete');
        $label = ['sum_views', 'sum_clicks', 'sum_revenue'];
        $column = [
            'zone_id',
            'zone_name',
            'platform',
            'sum_views',
            'sum_clicks',
            'sum_revenue',
            'zone_type',
            'zone_type_label',
            'sum_download_complete'
        ];
        $child_column = [
            'ad_id',
            'ad_name',
            'ad_type',
            'product_id',
            'product_name',
            'product_type',
            'sum_views',
            'sum_clicks',
            'sum_revenue',
            'sum_download_complete'
        ];
        if (in_array($affiliateId, $affArr)) {
            array_push($column, 'sum_download_complete');
            array_push($child_column, 'sum_download_complete');
            array_push($label, 'sum_download_complete');
        }
        $chart = $this->statChart($request, $label);
        $eChart = [];
        if (!empty($chart['chart'])) {
            foreach ($chart['chart'] as $key => $val) {
                $eChart[$val['zone_type']]['ad_list'][] = $val;
                $eChart[$val['zone_type']]['zone_type'] = $val['zone_type'];
                $eChart[$val['zone_type']]['zone_type_label'] = $val['zone_type_label'];
                if (!in_array($affiliateId, $affArr)) {
                    unset($eChart[$val['zone_type']]['ad_list']['sum_download_complete']);
                }
            }
        }

        $eChart = array_values($eChart);
        //报表一级数据显示字段
        $list = [];
        
        //根据广告位进行分组，得到该广告位下的广告数据
        $eData = StatService::TraffickerDataSummary(
            $chart['chart'],
            $column,
            $child_column,
            'zone_id',
            'ad_id',
            $label
        );
        $list['statChart'] = $eChart;
        $list['statData'] = $eData;
        $sum_revenue = Formatter::asDecimal($chart['sum_revenue'], 2);
        if (in_array($affiliateId, $affArr)) {
            return $this->success(
                [
                    'sum_views' => $chart['sum_views'],
                    'sum_clicks' => $chart['sum_clicks'],
                    'sum_download_complete' => $chart['sum_download_complete'],
                    'sum_revenue' => $sum_revenue,
                ],
                null,
                $list
            );
        } else {
            return $this->success(
                [
                    'sum_views' => $chart['sum_views'],
                    'sum_clicks' => $chart['sum_clicks'],
                    'sum_revenue' => $sum_revenue,
                ],
                null,
                $list
            );
        }
    }

    /**
     *获取广告数据
     *
     * name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * revenue_type | integer |  数据类型 | 10：CPD   2：CPC |  是 |
     * period_start | date | 起始时间 |  | 是 |
     * period_end | date | 终止时间 |  | 是 |
     * span | integer | 数据分组类型 | 1：hours 2：days  3：month| 是 |
     * zone_offset | string | 时区 | -8 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * name | sub name | sub name | type | description | restraint | required |
     * | :--: | :--: | :--: |:--: |:--------: | :-------: | :-----: |
     * statChart |  |  | array | 图表数据 |  | |
     * | | product_id |  | integer | 推广id |  | 是 |
     * | | product_name |  | string | 推广名称 |  | 是 |
     * | | product_type |  | string | 推广类型 |  | 是 |
     * | | ad_list |  | array | json对象数组 |  | 是 |
     * | |  | ad_id | integer | 广告位id |  | 是 |
     * | |  | ad_name | string | 广告位名称 |  | 是 |
     * | |  | sum_views | integer | 展示量 |  | 是 |
     * | |  | sum_clicks | integer | 下载量/点击量 |  | 是 |
     * | |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  | time | date | 时间 |  | 是 |
     * statData |  |  | array | 表格数据 |  | 是 |
     * | | ad_id |  | integer |  广告id |  | 是 |
     * | | ad_name |  | string | 广告名称 |  | 是 |
     * | | ad_type |  | string | 广告类型 |  | 是 |
     * | | platform |  | string | 平台 |  | 是 |
     * | | product_id |  | integer | 推广id |  | 是 |
     * | | product_name |  | string | 推广名称 |  | 是 |
     * | | product_icon |  | string | 推广图标 |  | 是 |
     * | | product_type |  | string | 推广类型 |  | 是 |
     * | |  sum_views |  | integer | 展示量 |  | 是 |
     * | |  sum_clicks |  | integer | 下载量/点击量 |  | 是 |
     * | |  sum_revenue |  | decimal | 收入 |  | 是 |
     * | |  ctr |  | decimal | 下载转化率/点击转化率 |  | 是 |
     * | |  media_cpd |  | decimal | 平均单价 |  | 是 |
     * | |  ecpm |  | decimal | ecpm |  | 是 |
     * | | child |  | array |  |  | 是 |
     * | |  | zone_id | integer | 广告位id |  | 是 |
     * | |  | zone_name | string | 广告位名称 |  | 是 |
     * | |  | sum_views | integer | 展示量 |  | 是 |
     * | |  | sum_clicks | integer | 下载量/点击量 |  | 是 |
     * | |  | sum_revenue | decimal | 收入 |  | 是 |
     * | |  | ctr | decimal | 下载转化率/点击转化率 |  | 是 |
     * | |  | media_cpd | decimal | 平均单价 |  | 是 |
     * | |  | ecpm | decimal | ecpm |  | 是 |
     * | |  | zone_type | integer | 广告位类别id |  | 是 |
     * | |  | zone_type_label | string | 广告位类别名称 |  | 是 |
     * | obj |  |  | object | 汇总信息 |  | 是 |
     * | | sum_views |  | integer | 展示量 |  | 是 |
     * | | sum_clicks |  | integer | 下载量/点击量 |  | 是 |
     * | | sum_cpc_clicks |  | integer | 点击量 |  | 是 |
     * | | sum_revenue |  | decimal | 收入 |  | 是 |
     */
    public function client(Request $request)
    {
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $affArr = Config::get('biddingos.affiliate_download_complete');
        //报表一级数据显示字段
        $column = [
            'ad_id',
            'ad_name',
            'platform',
            'ad_type',
            'sum_views',
            'sum_clicks',
            'sum_revenue',
            'product_id',
            'product_name',
            'product_icon',
            'product_type'
        ];
        $child_column = [
            'ad_id',
            'zone_name',
            'zone_type',
            'zone_type_label',
            'sum_views',
            'sum_clicks',
            'sum_revenue',
        ];
        $label = ['sum_views', 'sum_clicks', 'sum_revenue'];
        if (in_array($affiliateId, $affArr)) {
            array_push($column, 'sum_download_complete');
            array_push($child_column, 'sum_download_complete');
            array_push($label, 'sum_download_complete');
        }
        $chart = $this->statChart($request, $label, 1);

        $eChart = [];
        if (!empty($chart['chart'])) {
            foreach ($chart['chart'] as $key => $val) {
                $eChart[$val['product_id']]['ad_list'][] = $val;
                $eChart[$val['product_id']]['product_name'] = $val['product_name'];
                $eChart[$val['product_id']]['product_type'] = $val['product_type'];
                $eChart[$val['product_id']]['product_id'] = $val['product_id'];
                if (!in_array($affiliateId, $affArr)) {
                    unset($eChart[$val['product_id']]['ad_list']['sum_download_complete']);
                }
            }
        }

        $eChart = array_values($eChart);
        //根据广告位进行分组，得到该广告位下的广告数据
        $eData = StatService::TraffickerDataSummary(
            $chart['chart'],
            $column,
            $child_column,
            'ad_id',
            'zone_id',
            $label
        );
        
        $list['statChart'] = $eChart;
        $list['statData'] = $eData;
        $sum_revenue = Formatter::asDecimal($chart['sum_revenue'], 2);

        if (in_array($affiliateId, $affArr)) {
            return $this->success(
                [
                    'sum_views' => $chart['sum_views'],
                    'sum_clicks' => $chart['sum_clicks'],
                    'sum_download_complete' => $chart['sum_download_complete'],
                    'sum_revenue' => $sum_revenue,
                ],
                null,
                $list
            );
        } else {
            return $this->success(
                [
                    'sum_views' => $chart['sum_views'],
                    'sum_clicks' => $chart['sum_clicks'],
                    'sum_revenue' => $sum_revenue,
                ],
                null,
                $list
            );
        }
    }

    /**
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | time | integer | 日期 |  | 是 |
     * | cpc_revenue | integer  | cpc收入  |  | 是 |
     * | cpd_revenue | integer  | cpd收入  |  | 是 |
     * | revenue | integer | 总收入 |  | 是 |
     */
    public function report()
    {
        $affiliate_id = Auth::user()->account->affiliate->affiliateid;
        $this->period_start = date('Y-m-d', strtotime('-30 day'));
        $this->period_end = date('Y-m-d', strtotime('-1 day'));
        $list = self::getTraffickerReport(
            $affiliate_id,
            $this->period_start,
            $this->period_end
        );
        return $this->success(null, null, $list);
    }

    /**
     * @param $res
     * @return mixed
     */
    protected function getRank($res)
    {
        $list = [];
        $sum_revenue = 0;

        //将排名前六项的数据取出，并汇总其他项数据$sum_revenue
        if (!empty($res)) {
            foreach ($res as $key => $val) {
                if ($key < 6) {
                    $list[] = $val;
                } else {
                    $sum_revenue += $val['sum_revenue'];
                }
            }
            $sum_revenue = round($sum_revenue, 2);
        }
        $rank['list'] = $list;
        $rank['sum_revenue'] = $sum_revenue;
        return $rank;
    }
    /**
     *  运营概览-广告位收入
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | date_type | integer | 日期类型 |1： 本周2：上周3：本月4：上月5：累计 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | sum_revenue | decimal | 收入（元） |  | 是 |
     * | zone_id | integer | 广告位id |  | 是 |
     * | zone_name | string | 广告位名称 |  | 是 |
     */
    public function zoneReport(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date_type' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //获取日期类型
        $day_type = $request->input('date_type');
        if ($this->getTimePoint($day_type)) {
            $affiliateId = Auth::user()->account->affiliate->affiliateid;
            $query = DB::table('data_hourly_daily_af AS h')
                ->join('banners as b', 'b.bannerid', '=', 'h.ad_id')
                ->join('zones AS z', 'h.zone_id', '=', 'z.zoneid')
                ->join('campaigns as c', 'c.campaignid', '=', 'h.campaign_id')
                ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
                ->select(
                    DB::raw('IFNULL(SUM(af_income),0) AS sum_revenue'),
                    'z.zonename as zone_name',
                    'z.zoneid as zone_id'
                )
                ->where('b.affiliateid', '=', $affiliateId)
                ->where('cli.affiliateid', 0)
                ->groupBy('zone_id')
                ->orderBy('sum_revenue', 'DESC');
            //开始时间和结束时间
            if ($this->period_end) {
                $query->where('date', '<=', $this->period_end);
            }
            if ($this->period_start) {
                $query->where('date', '>=', $this->period_start);
            }
            $res = $query->get();
            $res = json_decode(json_encode($res), true);
            $rankData = $this->getRank($res);
            $list = $rankData['list'];
            $count = count($res);
            //赋值其他项数据
            if ($count > 6) {
                $list[] = ['sum_revenue' => $rankData['sum_revenue'], 'zone_name' => '其他项', 'zone_id' => -1];
            }
        }
        return $this->success(null, null, $list);
    }
    /**
     *  运营概览-广告收入
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | date_type | integer | 日期类型 |1： 本周2：上周3：本月4：上月5：累计 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | sum_revenue | decimal | 消耗 |  | 是 |
     * | client_id | integer | 广告主id |  | 是 |
     * | client_name | string | 广告名称 |  | 是 |
     */
    public function clientReport(Request $request)
    {
        if (($ret = $this->validate($request, [
                'date_type' => 'required|integer'
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $day_type = $request->input('date_type');
        if ($this->getTimePoint($day_type)) {
            $query = DB::table('data_hourly_daily_af AS udhda')
                ->join('banners as b', 'b.bannerid', '=', 'udhda.ad_id')
                ->join('campaigns AS uc', 'udhda.campaign_id', '=', 'uc.campaignid')
                ->join('appinfos AS ua', 'ua.app_id', '=', 'uc.campaignname')
                ->join('clients as cli', 'cli.clientid', '=', 'uc.clientid')
                ->select(
                    DB::raw('IFNULL(SUM(af_income),0) AS sum_revenue'),
                    'ua.app_name as client_name',
                    'uc.campaignid as client_id'
                )
                ->where('b.affiliateid', '=', $affiliateId)
                ->where('cli.affiliateid', 0)
                ->groupBy('uc.campaignid')
                ->orderBy('sum_revenue', 'DESC');
            if ($this->period_start) {
                $query->where('date', '>=', $this->period_start);
            }

            if ($this->period_end) {
                $query->where('date', '<=', $this->period_end);
            }
            $res = $query->get();
            $res = json_decode(json_encode($res), true);
            $rankData = $this->getRank($res);
            $count = count($res);
            $list = $rankData['list'];
            //赋值其他项数据
            if ($count > 6) {
                $list[] = [
                    'sum_revenue' => $rankData['sum_revenue'],
                    'client_name' => '其他项',
                    'client_id' => '-1'
                ];
            }
        }
        return $this->success(null, null, $list);
    }

    /**
     * 导出EXCEL
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | zone_offset | string | 时区 |  | 是 |
     * | revenue_type | integer | 计费类型 |  | 是 |
     * | item_num | integer | 统计类型（1：广告位，2：广告） |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function campaignExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'item_num' => 'required|integer|in:1,2',
                'zone_offset' => 'required',
                'revenue_type' => 'required|integer',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $revenue_type = $request->input('revenue_type');
        $zoneOffset = $request->input('zone_offset');
        $period_start = $request->input('period_start');
        $period_end = $request->input('period_end');
        $item_num = $request->input('item_num');
        $firstCondition = $request->input('first_condition');
        $secondCondition = $request->input('second_condition');
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $affArr = Config::get('biddingos.affiliate_download_complete');
        $statChart = StatService::getTraffickerData(
            $affiliateId,
            $period_start,
            $period_end,
            null,
            $zoneOffset,
            $revenue_type,
            $item_num,
            $firstCondition,
            $secondCondition
        );
        $item = [];
        $i = 0;
        $sum_view = 0;
        $sum_clicks = 0;
        $sum_revenue = 0;
        $sum_download_complete = 0;
        $affiliate_name = Auth::user()->account->affiliate->name;
        if (!empty($statChart)) {
            foreach ($statChart as $row) {
                if ($item_num == 2) {
                    $item[$i][] = $row['product_name'];
                    $item[$i][] = Campaign::getAdTypeLabels($row['ad_type']);
                    $item[$i][] = $row['ad_name'];
                    $item[$i][] = Campaign::getAdTypeLabels($row['ad_type']);
                    //$item[$i][] = Campaign::getPlatformLabels($row['platform']);
                    if (($row['ad_type'] == Campaign::AD_TYPE_APP_STORE) &&
                        ($row['platform'] == Campaign::PLATFORM_IOS_COPYRIGHT)
                    ) {
                        $item[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                    } else {
                        $item[$i][] = Campaign::getPlatformLabels($row['platform']);
                    }
                    $item[$i][] = $row['zone_name'];
                } else {
                    $item[$i][] = $affiliate_name;
                    $item[$i][] = Campaign::getAdTypeLabels($row['ad_type']);
                    $item[$i][] = $row['zone_name'];
                    // $item[$i][] = Campaign::getPlatformLabels($row['platform']);
                    if (($row['ad_type'] == Campaign::AD_TYPE_APP_STORE) &&
                        ($row['platform'] == Campaign::PLATFORM_IOS_COPYRIGHT)
                    ) {
                        $item[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                    } else {
                        $item[$i][] = Campaign::getPlatformLabels($row['platform']);
                    }
                    $item[$i][] = $row['product_name'];
                    $item[$i][] = Product::getTypeLabels($row['product_type']);
                    $item[$i][] = $row['ad_name'];
                }
                $item[$i][] = Campaign::getAdTypeLabels($row['ad_type']);
                $item[$i][] = $row['sum_views'];
                $item[$i][] = $row['sum_clicks'];
                $ctr = $row['sum_views'] ? $row['sum_clicks'] / $row['sum_views'] * 100 : 0;
                if ($ctr > '0.00') {
                    $ctr = number_format($ctr, 2);
                    $item[$i][] = "$ctr%";
                } else {
                    $item[$i][] = '<0.01%';
                };
                $item[$i][] = number_format($row['sum_revenue'], 2);
                $item[$i][] = number_format(
                    $row['sum_clicks'] ? $row['sum_revenue'] / $row['sum_clicks'] : 0,
                    2
                );
                $item[$i][] = number_format(
                    $row['sum_views'] ? $row['sum_revenue'] / $row['sum_views'] * 1000 : 0,
                    2
                );
                if (in_array($affiliateId, $affArr)) {
                    $item[$i][] = $row['sum_download_complete'];
                }
                $sum_view += $row['sum_views'];
                $sum_clicks += $row['sum_clicks'];
                $sum_revenue += $row['sum_revenue'];
                $sum_download_complete += $row['sum_download_complete'];
                $i++;
            }
        }

        if ($item_num == 2) {
            $sum_arr = array(
                '汇总',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                $sum_view,
                $sum_clicks,
                '--',
                Formatter::asDecimal($sum_revenue),
                '--',
                '--'
            );
            //excel文件名称
            $excelName = '广告报表-' . str_replace('-', '', $period_start) . '_' .
                str_replace('-', '', $period_end);
            //excel标题
            $sheetRow = [
                '推广名称',
                '推广类型',
                '广告名称',
                '广告类型',
                '所属平台',
                '广告位',
                '广告位类别',
                '展示量',
                '下载量',
                '下载转化率',
                '收入',
                '平均单价',
                'eCPM'
            ];

        } else {
            $sum_arr = array(
                '汇总',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                $sum_view,
                $sum_clicks,
                '--',
                Formatter::asDecimal($sum_revenue),
                '--',
                '--'
            );
            //excel文件名称
            $excelName = '广告位报表-' . str_replace('-', '', $period_start) . '_' .
                str_replace('-', '', $period_end);
            //excel标题
            $sheetRow = [
                '媒体商',
                '广告位类别',
                '广告位',
                '所属平台',
                '推广产品',
                '推广类型',
                '广告名称',
                '广告类型',
                '展示量',
                '下载量',
                '下载转化率',
                '收入',
                '平均单价',
                'eCPM'
            ];

        }

        if ($revenue_type == Campaign::REVENUE_TYPE_CPC) {
            foreach ($sheetRow as $key => $val) {
                if ($val == '下载量') {
                    $sheetRow[$key] = '点击量';
                }
                if ($val == '下载转化率') {
                    $sheetRow[$key] = '点击转化率';
                }
            }
        } elseif ($revenue_type == Campaign::REVENUE_TYPE_CPA) {
            foreach ($sheetRow as $key => $val) {
                if ($val == '下载量') {
                    $sheetRow[$key] = 'cpa';
                }
                if ($val == '下载转化率') {
                    $sheetRow[$key] = '激活转化率';
                }
            }
        }

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
            'N' => 15,
            'O' => 15,
        ];
        $rows = count($item) + 1;
        if ($item_num == 2) {
            $sheetBorder = "A1:M{$rows}";
            if (in_array($affiliateId, $affArr)) {
                array_push($sum_arr, $sum_download_complete);
                array_push($sheetRow, '下载量监控');
                $sheetBorder = "A1:N{$rows}";
            }
        } else {
            $sheetBorder = "A1:N{$rows}";
            if (in_array($affiliateId, $affArr)) {
                array_push($sum_arr, $sum_download_complete);
                array_push($sheetRow, '下载量监控');
                $sheetBorder = "A1:O{$rows}";
            }
        }

        array_push($item, $sum_arr);
        if (!StatService::downloadExcel($excelName, $sheetRow, $sheetWidth, $sheetBorder, $item)) {
            return $this->errorCode(5027);
        }
        return $this->success();//@codeCoverageIgnoreEnd
    }

    /**
     * @param $affiliate_id
     * @param $start
     * @param $end
     * @return array
     * 媒体商概览报表
     */
    protected function getTraffickerReport($affiliate_id, $start, $end)
    {
        $zoneOffset = -8;
        $hourly = DB::getTablePrefix() . 'data_hourly_daily_af';
        if (0 > $zoneOffset) {
            $zoneOffset = 0 - $zoneOffset;
            $dateQuery = "DATE_ADD";
        } else {
            $dateQuery = "DATE_SUB";
        }
        $query = DB::table('data_hourly_daily_af')
            ->join('zones', 'zones.zoneid', '=', 'data_hourly_daily_af.zone_id')
            ->join('affiliates', 'affiliates.affiliateid', '=', 'zones.affiliateid')
            ->join('banners', 'banners.bannerid', '=', 'data_hourly_daily_af.ad_id')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_af.campaign_id')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->where('data_hourly_daily_af.affiliateid', $affiliate_id)
            ->where('clients.affiliateid', 0)
            ->whereBetween('data_hourly_daily_af'. '.date', array($start, $end))
            ->select(
                DB::raw('IFNULL(SUM(' . $hourly . '.`af_income`),0) AS `sum_revenue`'),
                'data_hourly_daily_af.date as time'
            )
            ->addSelect('banners.revenue_type')
            ->groupBy('banners.revenue_type')
            ->groupBy('time');
        $result = $query->get();
        //重组数据 按照天数 计费类型组成二维数组
        foreach ($result as $key) {
            $eData[$key->time][$key->revenue_type] = $key->sum_revenue;
        }
        $list = [];
        $cpd = Campaign::REVENUE_TYPE_CPD;
        $cpc = Campaign::REVENUE_TYPE_CPC;
        //对天数进行循环，将存在的cpc，cpd收入直接赋值，不存在补全为零；
        if (!empty($eData)) {
            for ($i = 0; $i < 30; $i++) {
                foreach ($eData as $k => $value) {
                    $list[$start]['time'] = $start;
                    if (isset($eData[$start][$cpc])) {
                        $list[$start]['cpc_revenue'] = $eData[$start][$cpc];
                    } else {
                        $list[$start]['cpc_revenue'] = 0;
                    }
                    if (isset($eData[$start][$cpd])) {
                        $list[$start]['cpd_revenue'] = $eData[$start][$cpd];
                    } else {
                        $list[$start]['cpd_revenue'] = 0;
                    }
                    $list[$start]['revenue'] = $list[$start]['cpd_revenue'] + $list[$start]['cpc_revenue'];
                }
                $start = date("Y-m-d", strtotime('+1 day', strtotime($start)));
            }
            $list = array_values($list);
        }

        return $list;
    }

    /**
     * @媒体商报表-导出每日报表（广告位）
     * 根据广告的类型及广告位ID筛选数据
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | revenue_type | integer | 计费类型 |  | 是 |
     * | ad_type | string | 广告类型 |  | 是 |
     * | zone_id | integer | 广告位id |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function timeZoneExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required|in:2,3',
                'revenue_type' => "required",
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        if ($this->transFormInput($request)) {
            $revenueType = $request->input('revenue_type');
            $adType = $request->input('ad_type');
            if ($adType== Campaign::AD_TYPE_BANNER_IMG) {
                $adType = [Campaign::AD_TYPE_BANNER_IMG , Campaign::AD_TYPE_BANNER_TEXT_LINK];
            } elseif ($adType == Campaign::AD_TYPE_HALF_SCREEN) {
                $adType = [Campaign::AD_TYPE_HALF_SCREEN , Campaign::AD_TYPE_FULL_SCREEN];
            } else {
                $adType = [$adType];
            }
            $statsCharts = StatService::findTrafficTimeExcelStat(
                $this->period_start,
                $this->period_end,
                $this->axis,
                Auth::user()->account->affiliate->affiliateid,
                1,
                $revenueType,
                $adType,
                $request->input('zone_id')
            );
            if ($revenueType == Campaign::REVENUE_TYPE_CPC) {
                $type = 1;
            } elseif ($revenueType == Campaign::REVENUE_TYPE_CPA) {
                $type = Campaign::REVENUE_TYPE_CPA;
            } else {
                $type = 0;
            }
            $data = StatService::timeCampaignExcel(
                $statsCharts,
                $this->axis,
                $request->input('period_start'),
                $request->input('period_end'),
                $type,
                1
            );
            //excel文件名称
            if (!StatService::downloadExcel($data[0], $data[1], $data[2], $data[3], $data[4])) {
                return $this->errorCode(5027);
            }
            return $this->success();//@codeCoverageIgnoreEnd
        }
    }

    /**
     * @媒体商报表-导出每日报表（广告）
     * 根据产品及CampaignID筛选数据
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | revenue_type | integer | 计费类型 |  | 是 |
     * | product_id | integer | 广告id |  | 是 |
     * | campaign_id | integer | 广告id |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function timeCampaignExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required|in:2,3',
                'revenue_type' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $productId = $request->input('product_id');
        $campaignId = $request->input('campaign_id');
        if ($this->transFormInput($request)) {
            $revenueType = $request->input('revenue_type');
            $statsCharts = StatService::findTrafficTimeExcelStat(
                $this->period_start,
                $this->period_end,
                $this->axis,
                Auth::user()->account->affiliate->affiliateid,
                0,
                $revenueType,
                $productId,
                $campaignId
            );
            if ($revenueType == Campaign::REVENUE_TYPE_CPC) {
                $type = 1;
            } elseif ($revenueType == Campaign::REVENUE_TYPE_CPA) {
                $type =  Campaign::REVENUE_TYPE_CPA;
            } else {
                $type = 0;
            }
            $data = StatService::timeCampaignExcel(
                $statsCharts,
                $this->axis,
                $request->input('period_start'),
                $request->input('period_end'),
                $type,
                1
            );
            //excel文件名称
            if (!StatService::downloadExcel($data[0], $data[1], $data[2], $data[3], $data[4])) {
                return $this->errorCode(5027);
            }
            return $this->success();//@codeCoverageIgnoreEnd
        }
    }

    /**
     *导出每日报表-广告位
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | revenue_type | integer | 计费类型 |  | 是 |
     * | ad_type | string | 广告类型 |  | 是 |
     * | zone_id | integer | 广告位id |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function dailyZoneExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'revenue_type' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        if ($this->transFormInput($request)) {
            $adType = $request->input('ad_type');
            if ($adType != '') {
                if ($adType == Campaign::AD_TYPE_BANNER_IMG) {
                    $adType = [Campaign::AD_TYPE_BANNER_IMG , Campaign::AD_TYPE_BANNER_TEXT_LINK];
                } elseif ($adType == Campaign::AD_TYPE_HALF_SCREEN) {
                    $adType = [Campaign::AD_TYPE_HALF_SCREEN , Campaign::AD_TYPE_FULL_SCREEN];
                } else {
                    $adType = [$adType];
                }
            }
            $statsCharts = StatService::findTrafficDailyZoneExcel(
                $this->period_start,
                $this->period_end,
                Auth::user()->account->affiliate->affiliateid,
                $this->revenue_type,
                $adType,
                $request->input('zone_id')
            );
            $eData = [];
            foreach ($statsCharts as $row) {
                $eData[$row['campaignid']][$row['zone_id']]['info'] = $row;
                $eData[$row['campaignid']][$row['zone_id']]['days'][$row['time']] = $row;
            }
            $dayCount = (strtotime($this->period_end) - strtotime($this->period_start)) / 86400;
            foreach ($eData as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $_start = $this->period_start;
                    for ($i = 0; $i <= $dayCount; $i++) {
                        $eData[$k][$kk]['days']  = array_add($eData[$k][$kk]['days'], $_start, [
                            "sum_revenue" => 0,
                            "sum_views" => 0,
                            "sum_clicks" => 0,
                            "sum_download_complete" => 0,
                        ]);
                        $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
                    }
                }
            }
            $sum_view = 0;
            $sum_clicks = 0;
            $sum_revenue = 0;
            $sum_download_complete = 0;
            $affiliate_name = Auth::user()->account->affiliate->name;
            $affiliateId = Auth::user()->account->affiliate->affiliateid;
            $affArr = Config::get('biddingos.affiliate_download_complete');
            $i = 0;
            $item = [];
            foreach ($eData as $cam => $value) {
                foreach ($value as $zone => $val) {
                    foreach ($val['days'] as $k_day => $row) {
                        $item[$i][] = $affiliate_name;
                        $item[$i][] = Campaign::getAdTypeLabels($eData[$cam][$zone]['info']['ad_type']);
                        $item[$i][] = $eData[$cam][$zone]['info']['zone_name'];
                        if (($eData[$cam][$zone]['info']['ad_type'] == Campaign::AD_TYPE_APP_STORE) &&
                            ($eData[$cam][$zone]['info']['platform'] == Campaign::PLATFORM_IOS_COPYRIGHT)
                        ) {
                            $item[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                        } else {
                            $item[$i][] = Campaign::getPlatformLabels($eData[$cam][$zone]['info']['platform']);
                        }
                        $item[$i][] = $eData[$cam][$zone]['info']['product_name'];
                        $item[$i][] = Product::getTypeLabels($eData[$cam][$zone]['info']['product_type']);
                        $item[$i][] = $eData[$cam][$zone]['info']['app_name'];
                        $item[$i][] = Campaign::getAdTypeLabels($eData[$cam][$zone]['info']['ad_type']);
                        $item[$i][] = $k_day;
                        $item[$i][] = $row['sum_views'];
                        $item[$i][] = $row['sum_clicks'];
                        $item[$i][] = StatService::getCtr($row['sum_views'], $row['sum_clicks']);
                        $item[$i][] = Formatter::asDecimal($row['sum_revenue']);
                        $item[$i][] = StatService::getCpd($row['sum_revenue'], $row['sum_clicks']);
                        $item[$i][] = StatService::getEcpm($row['sum_revenue'], $row['sum_views']);
                        if (in_array($affiliateId, $affArr)) {
                            $item[$i][] = $row['sum_download_complete'];
                        }
                        $sum_view += $row['sum_views'];
                        $sum_clicks += $row['sum_clicks'];
                        $sum_revenue += $row['sum_revenue'];
                        $sum_download_complete += $row['sum_download_complete'];
                        $i++;
                    }
                }
            }
            $sheetRow = [
                '媒体商',
                '广告位类别',
                '广告位',
                '所属平台',
                '推广产品',
                '推广类型',
                '广告名称',
                '广告类型',
                '日期',
                '展示量',
                '下载量',
                '下载转化率',
                '收入',
                '平均单价',
                'eCPM'
            ];
            $sum_arr = array(
                '汇总',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                $sum_view,
                $sum_clicks,
                '--',
                Formatter::asDecimal($sum_revenue),
                '--',
                '--'
            );
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
                'N' => 15,
                'L' => 15,
                'M' => 15,
                'N' => 15,
                'O' => 15,
                'P' => 15,
            ];
            if ($this->revenue_type == Campaign::REVENUE_TYPE_CPC) {
                foreach ($sheetRow as $key => $val) {
                    if ($val == '下载量') {
                        $sheetRow[$key] = '点击量';
                    }
                    if ($val == '下载转化率') {
                        $sheetRow[$key] = '点击转化率';
                    }
                }
            }
            if ($this->revenue_type == Campaign::REVENUE_TYPE_CPA) {
                foreach ($sheetRow as $key => $val) {
                    if ($val == '下载量') {
                        $sheetRow[$key] = 'cpa';
                    }
                    if ($val == '下载转化率') {
                        $sheetRow[$key] = '激活转化率';
                    }
                }
            }
            $rows = count($item) + 1;
            $sheetBorder = "A1:O{$rows}";
            if (in_array($affiliateId, $affArr)) {
                array_push($sum_arr, $sum_download_complete);
                array_push($sheetRow, '下载量监控');
                $sheetBorder = "A1:P{$rows}";
            }
            array_push($item, $sum_arr);
            $excelName = '广告位报表-' . str_replace('-', '', $this->period_start) . '_' .
                str_replace('-', '', $this->period_end);
            if (!StatService::downloadExcel($excelName, $sheetRow, $sheetWidth, $sheetBorder, $item)) {
                return $this->errorCode(5027);
            }
            return $this->success();
        }
    }//@codeCoverageIgnoreEnd

    /**
     * 导出每日报表-广告
     *
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start | date | 开始时间 |  | 是 |
     * | period_end | date |  结束时间 |  | 是 |
     * | revenue_type | integer | 计费类型 |  | 是 |
     * | product_id | integer | 广告id |  | 是 |
     * | campaign_id | integer | 广告id |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function dailyCampaignExcel(Request $request)
    {
        //@codeCoverageIgnoreStart
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'revenue_type' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        if ($this->transFormInput($request)) {
            $productId = $request->input('product_id');
            $campaignId = $request->input('campaign_id');
            $statsCharts = StatService::findTrafficDailyClientExcel(
                $this->period_start,
                $this->period_end,
                Auth::user()->account->affiliate->affiliateid,
                $this->revenue_type,
                $productId,
                $campaignId
            );
            $eData = [];
            foreach ($statsCharts as $row) {
                $eData[$row['campaignid']][$row['zone_id']]['info'] = $row;
                $eData[$row['campaignid']][$row['zone_id']]['days'][$row['time']] = $row;
            }
            $dayCount = (strtotime($this->period_end) - strtotime($this->period_start)) / 86400;
            foreach ($eData as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $_start = $this->period_start;
                    for ($i = 0; $i <= $dayCount; $i++) {
                        $eData[$k][$kk]['days']  = array_add($eData[$k][$kk]['days'], $_start, [
                            "sum_views" => 0,
                            "sum_clicks" => 0,
                            "sum_revenue" => 0,
                            "sum_download_complete" => 0,
                        ]);
                        $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
                    }
                }
            }
            $item = [];
            $i = 0;
            $sum_view = 0;
            $sum_clicks = 0;
            $sum_revenue = 0;
            $sum_download_complete = 0;
            $affiliate_name = Auth::user()->account->affiliate->name;
            $affiliateId = Auth::user()->account->affiliate->affiliateid;
            $affArr = Config::get('biddingos.affiliate_download_complete');
            foreach ($eData as $cam => $value) {
                foreach ($value as $zone => $val) {
                    foreach ($val['days'] as $k_day => $value) {
                        $item[$i][] = $eData[$cam][$zone]['info']['product_name'];
                        $item[$i][] = Product::getTypeLabels($eData[$cam][$zone]['info']['product_type']);
                        $item[$i][] = $eData[$cam][$zone]['info']['app_name'];
                        $item[$i][] = Campaign::getAdTypeLabels($eData[$cam][$zone]['info']['ad_type']);
                        if (($eData[$cam][$zone]['info']['ad_type'] == Campaign::AD_TYPE_APP_STORE) &&
                            ($eData[$cam][$zone]['info']['platform'] == Campaign::PLATFORM_IOS_COPYRIGHT)
                        ) {
                            $item[$i][] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                        } else {
                            $item[$i][] = Campaign::getPlatformLabels($eData[$cam][$zone]['info']['platform']);
                        }
                        $item[$i][] = $eData[$cam][$zone]['info']['zone_name'];
                        $item[$i][] = Campaign::getAdTypeLabels($eData[$cam][$zone]['info']['ad_type']);
                        $item[$i][] = $k_day;
                        $item[$i][] = $value['sum_views'];
                        $item[$i][] = $value['sum_clicks'];
                        $item[$i][] = StatService::getCtr($value['sum_views'], $value['sum_clicks']);
                        $item[$i][] = Formatter::asDecimal($value['sum_revenue']);
                        $item[$i][] = StatService::getCpd($value['sum_revenue'], $value['sum_clicks']);
                        $item[$i][] = StatService::getEcpm($value['sum_revenue'], $value['sum_views']);
                        if (in_array($affiliateId, $affArr)) {
                            $item[$i][] = $value['sum_download_complete'];
                        }
                        $sum_view += $value['sum_views'];
                        $sum_clicks += $value['sum_clicks'];
                        $sum_revenue += $value['sum_revenue'];
                        $sum_download_complete += $value['sum_download_complete'];
                        $i++;
                    }
                }
            }
            $sheetRow = [
                '推广名称',
                '推广类型',
                '广告名称',
                '广告类型',
                '所属平台',
                '广告位',
                '广告位类别',
                '日期',
                '展示量',
                '下载量',
                '下载转化率',
                '收入',
                '平均单价',
                'eCPM'
            ];
            $sum_arr = array(
                '汇总',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                '--',
                $sum_view,
                $sum_clicks,
                '--',
                Formatter::asDecimal($sum_revenue),
                '--',
                '--'
            );
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
                'N' => 15,
                'O' => 15
            ];
            if ($this->revenue_type == Campaign::REVENUE_TYPE_CPC) {
                foreach ($sheetRow as $key => $val) {
                    if ($val == '下载量') {
                        $sheetRow[$key] = '点击量';
                    }
                    if ($val == '下载转化率') {
                        $sheetRow[$key] = '点击转化率';
                    }
                }
            }
            if ($this->revenue_type == Campaign::REVENUE_TYPE_CPA) {
                foreach ($sheetRow as $key => $val) {
                    if ($val == '下载量') {
                        $sheetRow[$key] = 'cpa';
                    }
                    if ($val == '下载转化率') {
                        $sheetRow[$key] = '激活转化率';
                    }
                }
            }
            $rows = count($item) + 1;
            $sheetBorder = "A1:N{$rows}";
            if (in_array($affiliateId, $affArr)) {
                array_push($sum_arr, $sum_download_complete);
                array_push($sheetRow, '下载量监控');
                $sheetBorder = "A1:O{$rows}";
            }
            array_push($item, $sum_arr);
            $excelName = '广告报表-' . str_replace('-', '', $this->period_start) . '_' .
                str_replace('-', '', $this->period_end);
            if (!StatService::downloadExcel($excelName, $sheetRow, $sheetWidth, $sheetBorder, $item)) {
                return $this->errorCode(5027);
            }
            return $this->success();
        }
    }//@codeCoverageIgnoreEnd

    protected static function attributeLabels()
    {
        return [
            'revenue_type' => '计费类型',
            'item_num' => '统计类型',
            'period_start' => '起始时间',
            'period_end' => '结束时间',
            'span' => '数据分组类型',
            'date_type' => '数据类型',
            'zone_offset' => '时区',
        ];
    }
    /**
     * 获取自营媒体概览报表
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | sum_views | array | 展示量 |  | 是 |
     * | sum_clicks | array  | 下载量  |  | 是 |
     * | sum_revenue | array  | 消耗  |  | 是 |
     */
    public function selfIndex()
    {
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $yesterday = date('Y-m-d', strtotime("-1 days"));
        $before = date(date('Y-m-d', strtotime("-2 days")));
        $prefix = DB::getTablePrefix();
        $row = DB::table('data_hourly_daily as h')
            ->join('campaigns as c', 'c.campaignid', '=', 'h.campaign_id')
            ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
            ->whereBetween('date', [$before, $yesterday])
            ->where('cli.affiliateid', $affiliateId)
            ->select(
                DB::raw('IFNULL(SUM(' . $prefix . 'h.`impressions`),0) as `sum_views`'),
                DB::raw('IFNULL(SUM(' . $prefix . 'h.`conversions`),0) as `sum_clicks`'),
                DB::raw('IFNULL(SUM(' . $prefix . 'h.`total_revenue`),0)as `sum_revenue`'),
                'h.date'
            )
            ->groupBy('h.date')
            ->get();
        $res= [];
        if (!empty($row)) {
            foreach ($row as $val) {
                $res[$val->date] = [
                    $val->sum_views,
                    $val->sum_clicks,
                    $val->sum_revenue,
                ];
            }
        }
        if (!isset($res[$yesterday])) {
            $res = array_add($res, $yesterday, [0, 0, 0]);
        }
        if (!isset($res[$before])) {
            $res = array_add($res, $before, [0, 0, 0]);
        }
        $list = [];
        $list['sum_views'] = StatService::getRate($res[$yesterday][0], $res[$before][0]);
        $list['sum_clicks'] = StatService::getRate($res[$yesterday][1], $res[$before][1]);
        $list['sum_revenue'] = StatService::getRate($res[$yesterday][2], $res[$before][2]);
        return $this->success(null, null, $list);
    }
    /**
     * 媒体概览 30 7天趋势 展示量、下载量、广告主消耗
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function selfTrend(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required|in:0,1',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $agencyId = Auth::user()->agencyid;
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $type = Input::get('type');
        if ($type == 0) {
            $start = date('Y-m-d', strtotime("-29 days"));
        } else {
            $start = date('Y-m-d', strtotime("-6 days"));
        }
        $end = date('Y-m-d');

        $label = ['revenue', 'clicks', 'views'];
        $table = 'data_hourly_daily';
        $res = StatService::getTrendData($start, $end, $table, $agencyId, $affiliateId, 0);
        $list = StatService::recombinantData($label, $res);
        $list['cpd'] = StatService::getValidAd($start, $end, $table, $agencyId, $affiliateId, 'conversions');
        $list['recharge'] = StatService::getValidRecharge($start, $end, $agencyId, $affiliateId);
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
     * 媒体自营报表
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
     * | brief_name | string | 广告主 |  | 是|
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
    public function selfZone(Request $request)
    {
        if (($ret = $this->validate($request, [
                'period_start' => 'required',
                'period_end' => 'required',
                'span' => 'required',
                'zone_offset' => 'required',
            ], [], $this->attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $start = Input::get('period_start');
        $end = Input::get('period_end');
        $zoneOffset = Input::get('zone_offset');
        if (Input::get('span') == self::SPAN_MONTH) {
            $axis = StatService::AXIS_MONTH;
        } else {
            $axis = StatService::AXIS_DAYS;
        }
        if ($start == $end) {
            list($start, $end, $axis) = StatService::dateConversion($start, $end, $zoneOffset);
        }
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $statsCharts = StatService::findSelfTrafficker(
            $start,
            $end,
            $axis,
            $affiliateId
        );
        return $this->success(
            [
                'start' => Input::get('period_start'),
                'end' =>Input::get('period_end')
            ],
            null,
            $statsCharts
        );
    }

    /**
     * 自营媒体广告主-报表数据
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | period_start |  integer | 开始时间|  | 是 |
     * | period_end |  string | 结束时间 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | brief_name | string | 广告主 |  | 是|
     * | app_name | string | 广告名称 |  | 是|
     * | zonename | string | 广告位名称 |  | 是|
     * | sum_views | integer | 展示量 |  | 是|
     * | sum_clicks | integer | 下载量 |  | 是|
     * | sum_revenue | decimal | 消耗总数 |  | 是|
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
        $campaignId = Input::get('campaignid');
        $zoneId = Input::get('zoneid');
        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        $cpa_revenue_type = AffiliateExtend::whereMulti(
            [
                'revenue_type' => Campaign::REVENUE_TYPE_CPA,
                'affiliateid' => $affiliateId
            ]
        )
            ->first();
        $statsCharts = StatService::findSelfTrafficker(
            $start,
            $end,
            'days',
            $affiliateId,
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
                    "sum_cpc_clicks" => 0,
                    "sum_cpa" => 0,
                    "cpd" => 0,
                    "sum_revenue" => 0,
                    "sum_revenue_gift" => 0,
                    "eCPM" => 0,
                ]);
                $_start = date("Y-m-d", strtotime('+24 hour', strtotime($_start)));
            }
        }
        $column = [
            'brief_name' => '广告主',
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
            'ecpm' => 'eCPM',
        ];
        $data = [];
        $i = 0;
        $sum_views = 0;
        $sum_clicks = 0;
        $sum_revenue = 0;
        $sum_revenue_gift = 0;
        $sum_cpa = 0;
        foreach ($eData as $bannerId => $val) {
            foreach ($eData[$bannerId]['days'] as $k_day => $item) {
                $data[$i][] = $eData[$bannerId]['info']['brief_name'];
                $data[$i][] = $eData[$bannerId]['info']['app_name'];
                $data[$i][] = $eData[$bannerId]['info']['zonename'];
                $data[$i][] = $k_day;
                $data[$i][] = Formatter::asDecimal($item['sum_views']);
                $sum_views += $item['sum_views'];
                $data[$i][] = Formatter::asDecimal($item['sum_clicks']);
                $sum_clicks += $item['sum_clicks'];
                $data[$i][] = StatService::getCtr($item['sum_views'], $item['sum_clicks']);
                $data[$i][] = Formatter::asDecimal($item['sum_revenue']);
                $sum_revenue += $item['sum_revenue'];
                $data[$i][] = Formatter::asDecimal($item['sum_revenue'] - $item['sum_revenue_gift']) ;
                $data[$i][] = Formatter::asDecimal($item['sum_revenue_gift']);
                $sum_revenue_gift += $item['sum_revenue_gift'];
                $data[$i][] = StatService::getCpd($item['sum_revenue'], $item['sum_clicks']);
                $data[$i][] = StatService::getEcpm($item['sum_revenue'], $item['sum_views']);
                if ($cpa_revenue_type) {
                    $data[$i][] = Formatter::asDecimal($item['sum_cpa']);
                    $data[$i][] = StatService::getCpd($item['sum_revenue'], $item['sum_cpa']);
                    $sum_cpa += $item['sum_cpa'];
                }
                $i ++;
            }

        }
        $excelName = '媒体自营报表-'. str_replace('-', '', $start) . '_' .
            str_replace('-', '', $end);
        $sum_arr = [
            '汇总',
            '--',
            '--',
            '--',
            Formatter::asDecimal($sum_views),
            Formatter::asDecimal($sum_clicks),
            '--',
            Formatter::asDecimal($sum_revenue),
            Formatter::asDecimal($sum_revenue - $sum_revenue_gift),
            Formatter::asDecimal($sum_revenue_gift),
            '--',
            '--',
        ];
        if ($cpa_revenue_type) {
            array_push($column, 'CPA量');
            array_push($column, 'CPA平均单价');
            array_push($sum_arr, $sum_cpa);
        }
        array_push($data, $sum_arr);
        StatService::downloadCsv($excelName, $column, $data);//@codeCoverageIgnoreEnd
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
     * | campaign_id | string | 广告ID |  | 是 |
     * | app_name | string | 游戏 |  | 是 |
     * | game_client_usernum | int | 新增用户数 |  | 是 |
     * | game_charge | float | 消费者充值 |  | 是 |
     * | game_client_price | float | 广告主单价 |  | 是 |
     * | game_client_amount | string | 广告主分成金额 |  | 是 |
     * | game_af_price | string | 渠道单价 |  | 是 |
     * | game_af_amount | string | 结算金额 |  | 是 |
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
        $param['period_start'] = date('Y-m-d', strtotime("{$param['period_start']} -1 day"));
        $param['affiliateid'] = Auth::user()->account->affiliate->affiliateid;
        $statData = StatService::findManagerGameData($param);
        $app = DB::table('appinfos')->get();
        $appName = ArrayHelper::map(
            $app,
            'app_id',
            'app_name'
        );
        $list = [];
        foreach ($statData as $val) {
            $val['app_name'] = $appName[$val['campaignname']];
            $list[$val['campaignid']][$val['date']] = $val;
        }
        //前一天的用户数量及充值金
        foreach ($list as $cam => $val) {
            foreach ($val as $date => $value) {
                $before = date('Y-m-d', strtotime("$date -1 day"));
                $after = date('Y-m-d', strtotime("$date 1 day"));
                if (isset($list[$cam][$before])) {
                    $list[$cam][$date]['before_game_af_usernum'] =
                        $list[$cam][$before]['game_af_usernum'];
                    $list[$cam][$date]['before_game_charge'] =
                        $list[$cam][$before]['game_charge'];
                    $list[$cam][$date]['before_game_client_amount'] =
                        $list[$cam][$before]['game_client_amount'];
                    $list[$cam][$date]['before_game_af_amount'] =
                        $list[$cam][$before]['game_af_amount'];
                } else {
                    $list[$cam][$date]['before_game_af_usernum'] = 0;
                    $list[$cam][$date]['before_game_charge'] = 0;
                    $list[$cam][$date]['before_game_client_amount'] = 0;
                    $list[$cam][$date]['before_game_af_amount'] = 0;
                }
                if ($after <= $param['period_end'] && !isset($list[$cam][$after])) {
                    $list[$cam][$after] = [
                        'campaignid' => $value['campaignid'],
                        'campaignname' => $value['campaignname'],
                        'affiliateid' => $value['affiliateid'],
                        'date' => $after,
                        'app_name' => $value['app_name'],
                        'before_game_charge' => $value['game_charge'],
                        'before_game_af_amount' => $value['game_af_amount'],
                        'before_game_af_usernum' => $value['game_af_usernum'],
                        'before_game_client_amount' => $value['game_client_amount'],
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
                    ];
                }
            }
        }
        //去键值
        $eData = [];
        foreach ($list as $ke => $va) {
            foreach ($va as $kk => $vv) {
                if ($kk <> $param['period_start']) {
                    $eData[] = $vv;
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
     *  campaign_id | int | 广告ID |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | campaign_id | string | 广告ID |  | 是 |
     * | app_name | string | 游戏 |  | 是 |
     * | game_client_usernum | int | 新增用户数 |  | 是 |
     *  | before_client_usernum | int | 前一天新增用户数 |  | 是 |
     * | game_charge | float | 消费者充值 |  | 是 |
     * | before_game_charge | float | 前一天充值金额 |  | 是 |
     * | game_client_price | float | 广告主单价 |  | 是 |
     * | game_client_amount | string | 广告主分成金额 |  | 是 |
     * | game_af_price | string | 渠道单价 |  | 是 |
     * | game_af_amount | string | 结算金额 |  | 是 |
     * | date | date | 日期 | | 是 |
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
        $param['affiliateid'] = Auth::user()->account->affiliate->affiliateid;
        $statData = StatService::findManagerGameData($param);
        $app = DB::table('appinfos')->get();
        $appName = ArrayHelper::map(
            $app,
            'app_id',
            'app_name'
        );
        $list = [];
        foreach ($statData as $val) {
            $val['app_name'] = $appName[$val['campaignname']];
            $list[$val['campaignid']][$val['date']] = $val;
        }
        $i = 0;
        $data = [];
        $param['affiliateid'] = Auth::user()->account->affiliate->affiliateid;
        $cps_revenue_type = DB::table('affiliates_extend')
            ->where('affiliateid', $param['affiliateid'])
            ->where('ad_type', Campaign::AD_TYPE_APP_MARKET)
            ->where('revenue_type', Campaign::REVENUE_TYPE_CPS)
            ->get();
        foreach ($list as $cam => $val) {
            foreach ($val as $date => $value) {
                if ($date > $param['period_start']) {
                    $before = date('Y-m-d', strtotime("$date -1 day"));
                    $data[$i][] = $date;
                    $data[$i][] = $value['app_name'];
                    $data[$i][] = $value['game_af_usernum'];
                    //新增用户环比
                    if (isset($list[$cam][$before])) {
                        $data[$i][] = StatService::loopRate(
                            $value['game_af_usernum'],
                            $list[$cam][$before]['game_af_usernum']
                        ) . '%';
                    } else {
                        $data[$i][] = '100%' ;
                    }
                    if ($cps_revenue_type) {
                        $data[$i][] = $value['game_charge'];
                        if (isset($list[$cam][$before])) {
                            $data[$i][] = StatService::loopRate(
                                $value['game_charge'],
                                $list[$cam][$before]['game_charge']
                            ) . '%';
                        } else {
                            $data[$i][] = '100%' ;
                        }
                        $data[$i][] = $value['game_client_amount'];
                        if (isset($list[$cam][$before])) {
                            $data[$i][] = StatService::loopRate(
                                $value['game_client_amount'],
                                $list[$cam][$before]['game_client_amount']
                            ) . '%';
                        } else {
                            $data[$i][] = '100%' ;
                        }
                    }
                    $data[$i][] = $value['game_af_amount'];
                    if (isset($list[$cam][$before])) {
                        $data[$i][] = StatService::loopRate(
                            $value['game_af_amount'],
                            $list[$cam][$before]['game_af_amount']
                        ) . '%';
                    } else {
                        $data[$i][] = '100%' ;
                    }
                    $i++;
                }
            }
        }
        $sheetRow = [
            'date' => '日期',
            'app_name' => '游戏',
            'game_client_usernum' => '新增用户数',
            'game_client_usernum_rate' => '新增环比',
        ];
        if ($cps_revenue_type) {
            $addColumn = [
                'game_charge' => '消费者充值',
                'game_charge_rate' => '充值环比',
                'game_client_amount' => '广告主分成金额',
                'game_client_amount_rate' => '广告主分成环比',
            ];
            $sheetRow = array_merge($sheetRow, $addColumn);
        }
        $afColumn = [
            'game_af_amount' => '结算金额',
            'game_af_amount_rate' => '结算环比',
        ];
        $sheetRow = array_merge($sheetRow, $afColumn);

        $excelName = '渠道游戏报表-'. str_replace('-', '', Input::get('period_start')) . '_' .
            str_replace('-', '', $param['period_end']);
        StatService::downloadCsv($excelName, $sheetRow, $data) ;

    }
}
