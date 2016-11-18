<?php

namespace App\Services;

use App\Components\Formatter;
use App\Models\Affiliate;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Tests\Controller;
use Auth;
use App\Components\Config;

/**
 * Created by PhpStorm.
 * User: angela
 * Date: 2016/1/30
 * Time: 18:03
 */
class StatService
{
    const AXIS_HOURS = 'hours';
    const AXIS_DAYS = 'days';
    const AXIS_MONTH = 'month';
    /**
     * 广告主端报表查询
     * @return
     */
    public static function getQuery()
    {
        $query = DB::table('data_hourly_daily_client')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_client.campaign_id')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->join('banners', 'data_hourly_daily_client.ad_id', '=', 'banners.bannerid')
            ->join('zones', 'zones.zoneid', '=', 'data_hourly_daily_client' . '.zone_id')
            ->leftjoin('attach_files', function ($join) {
                $join->on('banners.attach_file_id', '=', 'attach_files.id');
            })
            ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
            ->join('appinfos', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('campaigns.platform', '=', 'appinfos.platform');
            });
        return $query;
    }

    /**
     * 广告主端报表查询
     * @param int $clientId
     * @param date $start_date
     * @param date $end_date
     * @param int $axis
     * @param int $zoneOffset
     * @param int $productType
     * @return mixed
     */
    public static function getCampaignsData(
        $clientId,
        $start_date,
        $end_date,
        $axis,
        $zoneOffset,
        $productType = Product::TYPE_LINK
    ) {
        $selectInfo = array(
            'clients.clientname',
            'clients.clientid',
            'campaigns.campaignid as id',
            'appinfos.app_name as name',
            'campaigns.ad_type',
            'appinfos.platform',
            'products.name as product_name',
            'products.icon as product_icon',
            'products.id as product_id',
            'products.type',
            'attach_files.channel',
            'campaigns.revenue_type'
        );
        $groupBy = array(
            'attach_files.channel',
            'campaigns.campaignid',
        );
        $search = array(
            'clients.clientid' => $clientId
        );
        $clientFlag = true;
        $statsCharts = self::searchSummaryData(
            $selectInfo,
            $groupBy,
            $search,
            $start_date,
            $end_date,
            $axis,
            $zoneOffset,
            null,
            $clientFlag,
            $productType
        );
        return $statsCharts;
    }

    /**
     * 广告主报表查询公共方法
     * @param array $selectInfo
     * @param array $groupBy
     * @param array $search
     * @param date $start_date
     * @param date $end_date
     * @param int $axis
     * @param int $zoneOffset
     * @param array $orderBy
     * @param int $clientFlag
     * @param int $productType
     * @param string $report
     * @param string $broker
     * @return mixed
     */
    public static function searchSummaryData(
        $selectInfo,
        $groupBy,
        $search,
        $start_date,
        $end_date,
        $axis,
        $zoneOffset,
        $orderBy = "",
        $clientFlag = false,
        $productType = null,
        $report = false,
        $broker = ""
    ) {
        $hourly_table = DB::getTablePrefix() . 'data_hourly_daily_client';
        $campaign_table = DB::getTablePrefix() . 'campaigns';
        $query = self::getQuery();
//        $cpc_revenue = Campaign::REVENUE_TYPE_CPC;
//        $cpd_revenue =  Campaign::REVENUE_TYPE_CPD;
        //汇总展示量，下载量，点击量，及支出
        $query->select(DB::raw('IFNULL(SUM(' .$hourly_table . '.`impressions`),0) as `sum_views`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`total_revenue`),0) as `sum_revenue`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`file_down`),0) as `sum_download_complete`'));
        //1.如果是广告主概览报表，根据广告的计费类型，
        //计算出点击量/下载量，其他则统计出点击量和下载量
        $query->addSelect(DB::raw('IFNULL(SUM(' .$hourly_table . '.`conversions`),0) as `sum_clicks`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`clicks`),0) as `sum_cpc_clicks`'));

        $query->addSelect($selectInfo);
        if ($broker) {
            $query->join('brokers', 'brokers.brokerid', '=', 'clients.broker_id');
        }
        foreach ($groupBy as $val) {
            $query->groupBy($val);
        }

        foreach ($search as $key => $val) {
            $query->where($key, '=', $val);
        }

        $query->whereBetween('data_hourly_daily_client'. '.date', array($start_date, $end_date));

        if (isset($productType)) {
            $query->where('products.type', $productType);
        }
        switch ($axis) {
            case 'month':
                $query->addSelect(DB::raw("DATE_FORMAT({$hourly_table}.`date`,'%Y-%m') AS time"))->groupBy('time');
                break;
            case 'days':
            case 'report':
                $query->addSelect(DB::raw("DATE_FORMAT({$hourly_table}.`date`,'%Y-%m-%d') AS time"))->groupBy('time');
                break;
        }
        if ("" != $orderBy) {
            foreach ($orderBy as $Order) {
                $query->orderBy($Order['name'], $Order['sort']);
            }
        }
        $summary = $query->get();
        return json_decode(json_encode($summary), true);
    }

    /**
     * 获取广告主推广计划EXCEL信息
     * @param $clientId
     * @param $startDate
     * @param $endDate
     * @param $axis
     * @param $zoneOffset
     * @param int $type
     * @return mixed
     */
    public static function getCampaignsExcelData(
        $clientId,
        $startDate,
        $endDate,
        $axis,
        $zoneOffset,
        $type = 0,
        $productId = 0,
        $campaignId = 0
    ) {
        $selectInfo = [
            'appinfos.app_name',
            'appinfos.platform',
            'zones.type as zone_type',
            'products.type',
            'products.name as product_name',
            'products.icon as product_icon',
            'products.id as product_id',
            'campaigns.ad_type',
            'campaigns.revenue_type as client_revenue_type',
            'attach_files.channel'
        ];

        if ($type == Product::TYPE_APP_DOWNLOAD) {
            $groupBy = array(
                'attach_files.channel',
                'campaigns.campaignid'
            );
        } elseif ($type == Product::TYPE_LINK) {
            $groupBy = array(
                'campaigns.campaignid'
            );
        }

        $search = [
            'clients.clientid' => $clientId
        ];
        //增加筛选条件
        if ($productId) {
            $search = array_merge(['products.id' => $productId]);
        }
        if ($campaignId) {
            $search = array_merge(['data_hourly_daily_client.campaign_id' => $campaignId]);
        }

        $orderBy[0]['name'] = 'appinfos.app_name';
        $orderBy[0]['sort'] = 'DESC';

        $statsCharts = self::searchSummaryData(
            $selectInfo,
            $groupBy,
            $search,
            $startDate,
            $endDate,
            $axis,
            $zoneOffset,
            $orderBy,
            true,
            $type
        );

        return $statsCharts;
    }

    /**
     * 按照时间导出广告主报表数据
     * @param $clientId
     * @param $startDate
     * @param $endDate
     * @param $axis
     * @param $zoneOffset
     * @param int $type
     * @return mixed
     */
    public static function getTimeCampaignsExcelData(
        $clientId,
        $startDate,
        $endDate,
        $axis,
        $zoneOffset,
        $campaignId,
        $productId,
        $type = 0
    ) {
        $selectInfo = [];
        $search = [
            'clients.clientid' => $clientId,
        ];
        $groupBy = ['campaigns.revenue_type'];
        if ($productId) {
            $groupBy = array_merge($groupBy, [ 'products.id']);
            $search = [
                'clients.clientid' => $clientId,
                'products.id' => $productId,
            ];
        }
        if ($campaignId) {
            $groupBy = array_merge($groupBy, [ 'campaigns.campaignid']);
            $search = [
                'clients.clientid' => $clientId,
                'campaigns.campaignid' => $campaignId,
            ];
        }
        $statsCharts = self::searchSummaryData(
            $selectInfo,
            $groupBy,
            $search,
            $startDate,
            $endDate,
            $axis,
            $zoneOffset,
            null,
            true,
            $type,
            true
        );

        return $statsCharts;
    }

    /**
     * 通用数据汇总等处理
     * @param array $statsCharts
     * @return multitype:
     */
    public static function summaryData($statsCharts)
    {
        $entitiesData = [];
        //获取需要汇总的列名 展示量，点击量，下载量及支出
        $label= self::getSummaryLabel();
        if (count($statsCharts) > 0) {

            foreach ($statsCharts as $key => $value) {
                //按广告计划进行汇总
                if (isset($entitiesData[$value['id']])) {
                    foreach ($label as $k => $v) {
                        $entitiesData[$value['id']][$v] += $value[$v];
                    }
                } else {
                    $entitiesData[$value['id']] = $value;
                }
                //计算广告计划的ctr 和 cpd
                $entitiesData[$value['id']]['ctr'] = self::getCtr(
                    $entitiesData[$value['id']]['sum_views'],
                    $entitiesData[$value['id']]['sum_clicks']
                );
                if ($entitiesData[$value['id']]['revenue_type'] == Campaign::REVENUE_TYPE_CPC) {
                    $entitiesData[$value['id']]['cpd'] = self::getCpd(
                        $entitiesData[$value['id']]['sum_revenue'],
                        $entitiesData[$value['id']]['sum_cpc_clicks']
                    );
                } else {
                    $entitiesData[$value['id']]['cpd'] = self::getCpd(
                        $entitiesData[$value['id']]['sum_revenue'],
                        $entitiesData[$value['id']]['sum_clicks']
                    );
                }
                $entitiesData[$value['id']]['cpc_ctr'] =  self::getCtr(
                    $entitiesData[$value['id']]['sum_views'],
                    $entitiesData[$value['id']]['sum_cpc_clicks']
                );
                //按照渠道号进行汇总
                if (isset($entitiesData[$value['id']]['child'][$value['channel']])) {
                    foreach ($label as $k => $v) {
                        $entitiesData[$value['id']]['child'][$value['channel']][$v] += $value[$v];
                    }
                } else {
                    foreach ($label as $k => $v) {
                        $entitiesData[$value['id']]['child'][$value['channel']][$v] = $value[$v];
                        $entitiesData[$value['id']]['child'][$value['channel']]['channel'] = $value['channel'];
                    }
                }
                //计算按照渠道号的ctr 和 cpd
                $entitiesData[$value['id']]['child'][$value['channel']]['ctr'] =
                    self::getCtr(
                        $entitiesData[$value['id']]['child'][$value['channel']]['sum_views'],
                        $entitiesData[$value['id']]['child'][$value['channel']]['sum_clicks']
                    );
                if ($entitiesData[$value['id']]['revenue_type'] == Campaign::REVENUE_TYPE_CPC) {
                    $entitiesData[$value['id']]['child'][$value['channel']]['cpd'] =
                        self::getCpd(
                            $entitiesData[$value['id']]['child'][$value['channel']]['sum_revenue'],
                            $entitiesData[$value['id']]['child'][$value['channel']]['sum_cpc_clicks']
                        );
                } else {
                    $entitiesData[$value['id']]['child'][$value['channel']]['cpd'] =
                        self::getCpd(
                            $entitiesData[$value['id']]['child'][$value['channel']]['sum_revenue'],
                            $entitiesData[$value['id']]['child'][$value['channel']]['sum_clicks']
                        );
                }
                $entitiesData[$value['id']]['child'][$value['channel']]['cpc_ctr'] =
                    self::getCtr(
                        $entitiesData[$value['id']]['child'][$value['channel']]['sum_views'],
                        $entitiesData[$value['id']]['child'][$value['channel']]['sum_cpc_clicks']
                    );
            }

        }
        //格式化支出
        foreach ($entitiesData as $k => $v) {
            $entitiesData[$k]['sum_revenue'] = Formatter::asDecimal($entitiesData[$k]['sum_revenue']);
            foreach ($v['child'] as $key => $val) {
                $entitiesData[$k]['child'][$key]['sum_revenue'] =
                    Formatter::asDecimal($entitiesData[$k]['child'][$key]['sum_revenue']);
            }
        }
        return array_values($entitiesData);
    }

    /**
     * @param $sum_view
     * @param $sum_clicks
     * @return float|int
     */
    public static function getCtr($sum_view, $sum_clicks)
    {
        $ctr = $sum_view ? $sum_clicks / $sum_view : 0;
        $ctr = floatval(Formatter::asDecimal($ctr * 100));

        return $ctr;
    }

    /**
     * @param $sum_revenue
     * @param $sum_clicks
     * @return float|int|string
     */
    public static function getCpd($sum_revenue, $sum_clicks)
    {
        $cpd = $sum_clicks ? floatval($sum_revenue) / floatval($sum_clicks) : 0;
        $cpd = Formatter::asDecimal($cpd);

        return $cpd;
    }
    public static function getEcpm($sum_revenue, $sum_view)
    {
        $ecpm =   $sum_view > 0 ? $sum_revenue * 1000 / $sum_view : 0;
        $ecpm = Formatter::asDecimal($ecpm);
        return $ecpm;
    }
    private static function getSummaryLabel()
    {
        return [
            'sum_views',
            'sum_clicks',
            'sum_cpc_clicks',
            'sum_revenue',
        ];
    }
    /**
     * 计算ctr等信息
     *
     * @param unknown $campaigns
     * @return unknown
     */
    public static function makeData(&$campaigns)
    {
        if (count($campaigns) > 0) {
            foreach ($campaigns as &$campaign) {
                $ctr = $campaign['sum_views'] ? $campaign['sum_clicks'] / $campaign['sum_views'] : 0;
                $ctr = floatval(Formatter::asDecimal($ctr * 100));
                if ($ctr > '0.00') {
                    $ctr = Formatter::asDecimal($ctr);
                    $campaign['ctr'] = "$ctr%";
                } else {
                    $campaign['ctr'] = '<0.01%';
                }
                $cpcCtr = $campaign['sum_views'] ? $campaign['sum_cpc_clicks'] / $campaign['sum_views'] : 0;
                $cpcCtr = floatval(Formatter::asDecimal($cpcCtr * 100));
                if ($cpcCtr > '0.00') {
                    $cpcCtr = Formatter::asDecimal($cpcCtr);
                    $campaign['cpc_ctr'] = "$cpcCtr%";
                } else {
                    $campaign['cpc_ctr'] = '<0.01%';
                }

                if ($campaign['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    $campaign['platform'] = Campaign::getPlatformLabels(Campaign::PLATFORM_IOS);
                } else {
                    $campaign['platform'] = Campaign::getPlatformLabels($campaign['platform']);
                }
                $campaign['ad_type'] = Campaign::getAdTypeLabels($campaign['ad_type']);
                if (isset($campaign['zone_type'])) {
                    $campaign['zone_type'] = Campaign::getAdTypeLabels($campaign['zone_type']);
                }
                $campaign['type'] = Product::getTypeLabels($campaign['type']);

                // 广告主平均价格client_revenue_type
                if ($campaign['client_revenue_type'] == Campaign::REVENUE_TYPE_CPD) {
                    $campaign['cpd'] = $campaign['sum_clicks'] > 0 ?
                        $campaign['sum_revenue'] / $campaign['sum_clicks'] : 0;
                } elseif ($campaign['client_revenue_type'] == Campaign::REVENUE_TYPE_CPC) {
                    $campaign['cpd'] = $campaign['sum_cpc_clicks'] > 0 ?
                        $campaign['sum_revenue'] / $campaign['sum_cpc_clicks'] : 0;
                } else {
                    $campaign['cpd'] = 0;
                }
            }
        }
        return $campaigns;
    }

    /**
     * 生成EXCEL
     * @param $excelMame
     * @param $sheetRow
     * @param $sheetWidth
     * @param $sheetBorder
     * @param $data
     * @return bool
     */
    public static function downloadExcel($excelMame, $sheetRow, $sheetWidth, $sheetBorder, $data)
    {
        Excel::create(iconv('utf-8', 'gbk', $excelMame), function ($excel) use (
            $sheetRow,
            $sheetWidth,
            $sheetBorder,
            $data
        ) {
            $excel->sheet('Sheet', function ($sheet) use ($sheetRow, $sheetWidth, $sheetBorder, $data) {
                $sheet->row(1, $sheetRow);
                $sheet->rows($data);
                $sheet->setWidth($sheetWidth);

                $sheet->setBorder($sheetBorder, 'thin');
                $sheet->cells($sheetBorder, function ($cells) {
                    $cells->setFont([
                        'family' => 'Calibri',
                        'size' => '10',
                        'bold' => false
                    ]);
                    $cells->setAlignment('center');
                    $cells->setValignment('middle');
                });
            });
        })->download('xls');
        return true;
    }

    /**
     * 广告主概览报表
     * @param int $clientId
     * @param date $start
     * @param date $end
     * @param int $axis
     * @return multitype:
     */
    public static function getReport($clientId, $start, $end, $axis)
    {
        //需要查询的字段值
        $selectInfo =[
            'products.type',
            'appinfos.app_name as name',
            'products.icon as icon',
            'campaigns.campaignid as id',
            'campaigns.ad_type',
            'campaigns.revenue_type',
        ];
        $groupBy = array('campaigns.campaignid','time');
        $search = ['clients.clientid' => $clientId];
        $flag = true;
//        $startDate = date('Y-m-d H:i:s', strtotime($start.' 00:00:00') - 8 * 3600);
//        $endDate = date('Y-m-d H:i:s', strtotime($end.' 23:59:59') - 8 * 3600);
        //获取广告主数据
        $result = self::searchSummaryData(
            $selectInfo,
            $groupBy,
            $search,
            $start,
            $end,
            $axis,
            '-8',
            '',
            true,
            null,
            $flag
        );
        $res = self::regroupAdvertiserReport($result, $start);
        return $res;
    }

    /**
     * 媒体商段报表公共查询
     * @param $start_date
     * @param $end_date
     * @param $axis
     * @param $zoneOffset
     * @param $selectInfo
     * @param $groupBy
     * @param $search
     * @return mixed
     */
    public static function searchTraffickerData(
        $start_date,
        $end_date,
        $axis,
        $zoneOffset,
        $selectInfo,
        $groupBy,
        $search,
        $type = 0,
        $firstCondition = '',
        $secondCondition = ''
    ) {
        //查询主表审计表V2.7.2版本改成查询表up_data_hourly_daily_af
        $hourly_table = DB::getTablePrefix() . 'data_hourly_daily_af';
        $banner_table = DB::getTablePrefix() . 'banners';
        $campaign_table = DB::getTablePrefix() . 'campaigns';
        $cpc_revenue = Campaign::REVENUE_TYPE_CPC;
        $cpd_revenue =  Campaign::REVENUE_TYPE_CPD;
        $cpa_revenue =  Campaign::REVENUE_TYPE_CPA;
        $ad_type_half_screen = Campaign::AD_TYPE_HALF_SCREEN;
        $ad_type_full_screen =  Campaign::AD_TYPE_FULL_SCREEN;
        $ad_type_banner_img = Campaign::AD_TYPE_BANNER_IMG;
        $ad_type_banner_text_link =  Campaign::AD_TYPE_BANNER_TEXT_LINK;

        $query = DB::table('data_hourly_daily_af')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_af.campaign_id')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->join('banners', 'data_hourly_daily_af.ad_id', '=', 'banners.bannerid')
            ->join('zones', 'zones.zoneid', '=', 'data_hourly_daily_af.zone_id')
            ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
            ->join('appinfos', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('campaigns.platform', '=', 'appinfos.platform');
            })
            ->where('clients.affiliateid', 0);
        //汇总出展示量及媒体商收入
        $query->select(DB::raw('IFNULL(SUM(' .$hourly_table . '.`impressions`),0) as `sum_views`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`af_income`),0) as `sum_revenue`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`file_down`),0) as `sum_download_complete`'));
        //更加计费类型汇总出点击量或展示量为sum_clicks
        $query->addSelect(
            DB::raw('CASE ' .$banner_table . '.`revenue_type`
            WHEN '.$cpd_revenue.'
            THEN IFNULL(SUM(' .$hourly_table . '.`conversions`), 0)
            WHEN '.$cpc_revenue.'
            THEN IFNULL(SUM(' .$hourly_table . '.`clicks`), 0)
            WHEN '.$cpa_revenue.'
            THEN IFNULL(SUM(' .$hourly_table . '.`cpa`), 0)
            ELSE 0
            END as `sum_clicks`')
        );
        //如果是广告为插屏，半屏和全屏值都为3，
        //如果是banner 值为1
        $AdType = Campaign::getReportAdType();
        $AdType = implode(',', $AdType);
        $query->addSelect(
            DB::raw('CASE WHEN ' . $campaign_table . '.ad_type = ' . $ad_type_full_screen .
                ' THEN ' . $ad_type_half_screen .
                ' WHEN ' . $campaign_table . '.ad_type = ' . $ad_type_banner_text_link .
                ' THEN ' . $ad_type_banner_img .
                ' WHEN (' . $campaign_table . '.ad_type IN ('.$AdType.
                ')) THEN ' . $campaign_table .
                '.ad_type END AS `ad_type`')
        );
        $query->addSelect($selectInfo);
        if ($type == 1) {
            //广告位 按照广告类型及广告位ID
            if ($firstCondition <> '') {
                if ($firstCondition== Campaign::AD_TYPE_BANNER_IMG) {
                    $adType = [Campaign::AD_TYPE_BANNER_IMG , Campaign::AD_TYPE_BANNER_TEXT_LINK];
                } elseif ($firstCondition == Campaign::AD_TYPE_HALF_SCREEN) {
                    $adType = [Campaign::AD_TYPE_HALF_SCREEN , Campaign::AD_TYPE_FULL_SCREEN];
                } else {
                    $adType = [$firstCondition];
                }
                $query->whereIn('campaigns.ad_type', $adType);
            }
            if ($secondCondition <> '') {
                $query->where('data_hourly_daily_af.zone_id', $secondCondition);
            }
        } else {
            //广告 按照产品和广告
            if ($firstCondition <> '') {
                $query->where('campaigns.product_id', $firstCondition);
            }
            if ($secondCondition <> '') {
                $query->where('data_hourly_daily_af.campaign_id', $secondCondition);
            }
        }
        foreach ($groupBy as $val) {
            $query->groupBy($val);
        }
        foreach ($search as $key => $val) {
            $query->where($key, '=', $val);
        }
        $query->whereBetween('data_hourly_daily_af.date', array($start_date, $end_date));

        //分组类型 按照月 日 及小时汇总
        switch ($axis) {
            case 'month':
                $query->addSelect(DB::raw("DATE_FORMAT({$hourly_table}.`date`,'%Y-%m-01') AS time"))->groupBy('time');
                break;
            case 'days':
                $query->addSelect(DB::raw("DATE_FORMAT({$hourly_table}.`date`,'%Y-%m-%d') AS time"))->groupBy('time');
                break;
        }
        $summary = $query->get();
        return json_decode(json_encode($summary), true);
    }

    /**
     * 媒体商的四个tab页查询
     * @param $affiliateId
     * @param $start_date
     * @param $end_date
     * @param $axis
     * @param $zoneOffset
     * @param $revenueType
     * @param $revenueType
     * @param $revenueType
     * @param $revenueType
     * @return mixed
     */
    public static function getTraffickerData(
        $affiliateId,
        $start_date,
        $end_date,
        $axis,
        $zoneOffset,
        $revenueType,
        $item_num = 0,
        $firstCondition = '',
        $secondCondition = ''
    ) {
        //按照广告位和广告分组
        $groupBy = ['zones.zoneid','campaigns.campaignid'];
        //媒体商ID和计费类型
        $search = [
            'data_hourly_daily_af.affiliateid' => $affiliateId,
            'banners.revenue_type' => $revenueType,
        ];

        $selectInfo = [
            'zones.zoneid as zone_id',
            'zones.zonename as zone_name',
            'zones.type as zone_type',
            'zones.platform',
            'banners.bannerid',
            'products.id as product_id',
            'products.name as product_name',
            'products.type as product_type',
            'products.icon as product_icon',
            'banners.revenue_type',
            'campaigns.campaignid as ad_id',
            'campaigns.platform as cam_platform',
            'appinfos.app_name as ad_name',
        ];
        $statChart = self::searchTraffickerData(
            $start_date,
            $end_date,
            $axis,
            $zoneOffset,
            $selectInfo,
            $groupBy,
            $search,
            $item_num,
            $firstCondition,
            $secondCondition
        );
        return $statChart;
    }

    /**
     * 媒体商报表N级展开数据
     * @param $statChart 基础数据类型
     * @param $column 报表数据一级数据展示的字段
     * @param $first_index 一级分组 广告位按照zone_id 广告按照ad_id
     * @param $second_index 二级分组 广告位按照ad_id 广告按照zone_id
     * @return array
     */


    public static function traffickerDataSummary(
        $statChart,
        $column,
        $child_column,
        $first_index,
        $second_index,
        $label
    ) {
        $list = [];
        foreach ($statChart as $key => $val) {
            if (isset($list[$val[$first_index]])) {
                //汇总一级数据的展示量，点击量/下载量，收入 并计算cpd、ctr及ecpm
                foreach ($label as $k => $v) {
                    $list[$val[$first_index]][$v] += $val[$v];
                }
            } else {
                foreach ($column as $k => $v) {
                    $list[$val[$first_index]][$v] = $val[$v];
                }

            }
            $list[$val[$first_index]]['ctr'] = self::getCtr(
                $list[$val[$first_index]]['sum_views'],
                $list[$val[$first_index]]['sum_clicks']
            );
            $list[$val[$first_index]]['media_cpd'] = self::getCpd(
                $list[$val[$first_index]]['sum_revenue'],
                $list[$val[$first_index]]['sum_clicks']
            );
            $list[$val[$first_index]]['ecpm'] = self::getEcpm(
                $list[$val[$first_index]]['sum_revenue'],
                $list[$val[$first_index]]['sum_views']
            );
            //将二级数据进行赋值
            if (isset($list[$val[$first_index]]['child'][$val[$second_index]])) {
                foreach ($label as $k => $v) {
                    $list[$val[$first_index]]['child'][$val[$second_index]][$v] += $val[$v];
                }
            } else {
                foreach ($child_column as $ke => $va) {
                    $list[$val[$first_index]]['child'][$val[$second_index]][$va] = $val[$va];
                }
            }
            $list[$val[$first_index]]['child'][$val[$second_index]]['ctr'] = self::getCtr(
                $list[$val[$first_index]]['child'][$val[$second_index]]['sum_views'],
                $list[$val[$first_index]]['child'][$val[$second_index]]['sum_clicks']
            );
            $list[$val[$first_index]]['child'][$val[$second_index]]['media_cpd'] = self::getCpd(
                $list[$val[$first_index]]['child'][$val[$second_index]]['sum_revenue'],
                $list[$val[$first_index]]['child'][$val[$second_index]]['sum_clicks']
            );
            $list[$val[$first_index]]['child'][$val[$second_index]]['ecpm'] = self::getEcpm(
                $list[$val[$first_index]]['child'][$val[$second_index]]['sum_revenue'],
                $list[$val[$first_index]]['child'][$val[$second_index]]['sum_views']
            );
        }
        //去掉数组中的键值$first_index $second_index，对收入进行format
        foreach ($list as $ke => &$va) {
            $list[$ke]['sum_revenue'] = Formatter::asDecimal($list[$ke]['sum_revenue']);
            $list[$ke]['child']=array_values($va['child']);
        }
        $list= array_values($list);
        return $list;
    }

    /**
     * 广告主或代理商媒体商报表导出
     * @param array $res
     * @param int $axis
     * @param date $period_start
     * @param date $period_end
     * @param int $type
     * @param int $flag 值为1时，代表媒体商
     * @return multitype:string multitype:number  multitype:string  unknown
     */
    public static function timeCampaignExcel($res, $axis, $period_start, $period_end, $type, $flag = 0)
    {
        $item = [];
        foreach ($res as $row) {
            if (isset($item[$row['time']])) {
                $item[$row['time']]['sum_views'] += $row['sum_views'];
                $item[$row['time']]['sum_clicks'] += $row['sum_clicks'];
                $item[$row['time']]['sum_revenue'] += $row['sum_revenue'];
                $item[$row['time']]['sum_download_complete'] += $row['sum_download_complete'];
            } else {
                $item[$row['time']] = $row;
            }

            $ctr = $item[$row['time']]['sum_views'] ?
                $item[$row['time']]['sum_clicks'] / $item[$row['time']]['sum_views'] : 0;

            if ($ctr > '0.00') {
                $ctr =floatval(Formatter::asDecimal($ctr * 100));
                $ctr = "$ctr%";
            } else {
                $ctr = '0';
            }
            $cpd = $item[$row['time']]['sum_clicks'] > 0 ?
                $item[$row['time']]['sum_revenue'] / $item[$row['time']]['sum_clicks'] : 0;
            $cpd = Formatter::asDecimal($cpd);
            $item[$row['time']]['ctr'] = $ctr;
            $item[$row['time']]['cpd'] = $cpd;
        }
        //按照天数，如果数据为零，补全为零的天数
        if ($axis == 'days') {
            $start = date('Y-m-d', strtotime($period_start));
            $end = date('Y-m-d', strtotime($period_end));
            $count =round((strtotime($end) - strtotime($start)) / 86400) + 1;
            for ($i = 0; $i < $count; $i++) {
                if (!isset($item[$start])) {
                    $item = array_add($item, $start, array('sum_clicks'=>0, 'ctr' => 0,'sum_download_complete'=> 0,
                        'sum_revenue'=> 0, 'sum_views'=> 0,'time' => $start,'cpd' => 0));
                };
                $start=date("Y-m-d", strtotime('+1 day', strtotime($start)));
            }
        }
        //按照月份，如果数据为零，补全为零的月份
        if ($axis == 'month') {
            $start = date('Y-m', strtotime($period_start));
            $end = date('Y-m', strtotime($period_end));
            for ($start; $start <= $end; $start = date("Y-m", strtotime('+1 months', strtotime($start)))) {
                if (!isset($item[$start])) {
                    $item = array_add($item, $start, array('sum_clicks'=>0, 'ctr' => 0,'sum_download_complete'=> 0,
                        'sum_revenue'=> 0, 'sum_views'=> 0,'time' => $start,'cpd' => 0));
                };
            }
        }
        //按照日期排序
        ksort($item);
        $i = 0;
        $sum_view = 0;
        $sum_revenue = 0;
        $sum_clicks = 0;
        $sum_download_complete = 0;
        //如果是媒体商
        if ($flag > 0) {
            $affiliateId = Auth::user()->account->affiliate->affiliateid;
            $affArr = Config::get('biddingos.affiliate_download_complete');
        }
        foreach ($item as $key => $val) {
            $data[$i][] = $val['time'];
            $data[$i][] = $val['sum_views'];
            $data[$i][] = $val['sum_clicks'];
            $data[$i][] = $val['ctr'];
            $data[$i][] = $val['sum_revenue'];
            $data[$i][] = $val['cpd'];
            $sum_view += $val['sum_views'];
            $sum_revenue += $val['sum_revenue'];
            $sum_clicks += $val['sum_clicks'];
            $sum_download_complete += $val['sum_download_complete'];
            if ($flag > 0) {
                if (in_array($affiliateId, $affArr)) {
                    $data[$i][] = $val['sum_download_complete'];
                }
            }
            $i++;
        }
        $excelName = '报表-'. str_replace('-', '', $period_start) . '_' .
            str_replace('-', '', $period_end);
        //excel标题
        $sheetRow = [
            '时间',
            '展示量',
            '下载量',
            '下载转化率',
            '投放消耗',
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
        ];
        $rows = count($data) + 1;
        //excel统计
        $sheetBorder = "A1:F{$rows}" ;
        //应用下载需要加入推广类型,渠道
        if ($type == Product::TYPE_LINK) {
            $sheetRow[2] = '点击量';
            $sheetRow[3] = '点击转化率';
        }
        if ($type == Campaign::REVENUE_TYPE_CPA) {
            $sheetRow[2] = 'cpa';
            $sheetRow[3] = '激活转化率';
        }
        if ($flag > 0) {
            $sum_arr = [
                '汇总',
                $sum_view,
                $sum_clicks,
                '--',
                $sum_revenue,
                '--'
            ];
            if (in_array($affiliateId, $affArr)) {
                array_push($sum_arr, $sum_download_complete);
                array_push($sheetRow, '下载量监控');
                $sheetBorder = "A1:G{$rows}" ;
            }
            array_push($data, $sum_arr);
        }
        return [$excelName, $sheetRow, $sheetWidth, $sheetBorder, $data];
    }

    /**
     * 查询平台媒体商-广告位报表，对应平台【统计报表】-【收入报表-媒体商】
     * @param date $startDate 开始日期 若$axis值是'hours',则传utc时间, $offset=-8
     * @param date $endDate 结束日期
     * @param string $axis 分组类型 按照月 日 或小时汇总, 'month', 'days', 'hours'
     * @param integer $offset 时区偏移量
     * @param integer $agencyId
     * @param $audit 0:审计前数据, 1:审计后数据
     * @param $creator_uid 平台用户的create_uid,默认是全部媒体商
     * @param $obj 区分媒体商，还是广告主的数据, 'affiliate', 'client'
     * @author ben
     */
    public static function findManagerZoneStat(
        $startDate,
        $endDate,
        $axis,
        $offset,
        $agencyId,
        $audit = 0,
        $creator_uid = 0,
        $obj = 'affiliate'
    ) {
        $dataTable = self::decideSearchDataTable($axis, $audit, $obj);
        if (empty($dataTable)) {
            return json_decode(json_encode(
                array('error'=>'统计报表无法确定要查询的数据表, 请检查参数是否正确')
            ), true);
        } else {
            $select_fields = [
                'z.zoneid',
                'z.zonename',
                'z.description',
                'z.affiliateid',
                'c.business_type',
                'b.revenue_type as media_revenue_type',
                'c.revenue_type as client_revenue_type',
            ];

            $groupBy = ['z.zoneid','c.campaignid'];
            $select = DB::table($dataTable)
                ->join('banners as b', 'b.bannerid', '=', "{$dataTable}.ad_id")
                ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
                ->join('zones as z', 'z.zoneid', '=', "{$dataTable}.zone_id")
                ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
                ->where('cli.affiliateid', 0);

            $select = self::commonSelect($select, $select_fields, $dataTable, $startDate, $endDate, $axis, $offset);

            foreach ($groupBy as $v) {
                $select->groupBy($v);
            }

            DB::setFetchMode(\PDO::FETCH_ASSOC);
            $stats = $select->get();
            return $stats;
        }

    }

    /**
     * 查询平台媒体商-广告位对应的campaign数据
     * 对应平台【统计报表】-【收入报表-媒体商】-选择媒体商-选择广告
     * @param date $startDate 开始日期 若$axis值是'hours',则传utc时间, $offset=-8
     * @param date $endDate 结束日期
     * @param string $axis 分组类型 按照月 日 或小时汇总, 'month', 'days', 'hours'
     * @param integer $offset 时区偏移量
     * @param integer $agencyId
     * @param integer $affiliateid 媒体商ID
     * @param $audit 0:审计前数据, 1:审计后数据
     * @param $creator_uid 平台用户的create_uid,默认是全部媒体商
     * @param $obj 区分媒体商，还是广告主的数据, 'affiliate', 'client'
     * @author ben
     */
    public static function findManagerZoneCampaignStat(
        $startDate,
        $endDate,
        $axis,
        $offset,
        $agencyId,
        $affiliateid,
        $audit = 0,
        $creator_uid = 0,
        $obj = 'affiliate'
    ) {
        $dataTable = self::decideSearchDataTable($axis, $audit, $obj);
        if (empty($dataTable)) {
            return json_decode(json_encode(
                array('error'=>'统计报表无法确定要查询的数据表, 请检查参数是否正确')
            ), true);
        } else {
            $select_fields = [
                'z.zoneid',
                'z.zonename',
                'z.ad_type as zone_type',
                'z.description',
                'z.platform',
                'aff.brief_name',
                'aff.affiliateid',
                'c.revenue_type as client_revenue_type',
                'c.campaignid',
                'c.business_type',
                'c.ad_type',
                'b.revenue_type as media_revenue_type',
                'app.app_name',
                'b.bannerid',
                'p.name as product_name',
                'p.type as product_type',
                'af.channel',
                'c.revenue as cpd',
            ];

            $groupBy = ['z.zoneid', 'b.bannerid'];
            $select = DB::table($dataTable)
                ->join('banners as b', 'b.bannerid', '=', "{$dataTable}.ad_id")
                ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
                ->join('zones as z', 'z.zoneid', '=', "{$dataTable}.zone_id")
                ->join('affiliates as aff', 'aff.affiliateid', '=', 'z.affiliateid')
                ->join('appinfos as app', function ($join) {
                    $join->on('c.campaignname', '=', 'app.app_id')
                        ->on('c.platform', '=', 'app.platform')
                        ->on('aff.agencyid', '=', 'app.media_id');
                })
                ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
                ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
                ->leftJoin('attach_files as af', 'af.id', '=', 'b.attach_file_id')
                ->where('cli.affiliateid', 0);

            $select = self::commonSelect($select, $select_fields, $dataTable, $startDate, $endDate, $axis, $offset);

            $select->where('aff.agencyid', $agencyId);

            if ($affiliateid > 0) {
                $select->where('aff.affiliateid', $affiliateid);
            }

            if ($creator_uid > 0) {
                $select->where('aff.creator_uid', $creator_uid);
            }

            foreach ($groupBy as $v) {
                $select->groupBy($v);
            }

            DB::setFetchMode(\PDO::FETCH_ASSOC);
            $stats = $select->get();
            return $stats;
        }

    }

    /**
     * 查询导出平台媒体商-收入报表，
     * 对应平台【统计报表】-【导出报表】or【导出每日报表】
     * @param date $startDate 开始日期 若$axis值是'hours',则传utc时间, $offset=-8
     * @param date $endDate 结束日期
     * @param string $axis 分组类型 按照月 日 或小时汇总, 'month', 'days', 'hours'
     * @param integer $offset 时区偏移量
     * @param integer $agencyId
     * @param integer $affiliateid 媒体商ID
     * @param integer $bannerid 投放广告ID
     * @param integer $media_revenue_type  媒体计费类型
     * @param integer $client_revenue_type 广告主计费类型
     * @param integer $business_type 业务类型
     * @param $audit 0:审计前数据, 1:审计后数据
     * @param $creator_uid 平台用户的create_uid,默认是全部媒体商
     * @param $obj 区分媒体商，还是广告主的数据, 'affiliate', 'client'
     * @param $everyday 区分是导出报表，还是导出每日报表
     * @author ben
     */
    public static function findManagerZoneExcelStat(
        $startDate,
        $endDate,
        $axis,
        $offset,
        $agencyId,
        $affiliateid,
        $bannerid,
        $media_revenue_type,
        $client_revenue_type,
        $business_type,
        $audit = 0,
        $creator_uid = 0,
        $obj = 'affiliate',
        $everyday = false
    ) {
        $dataTable = self::decideSearchDataTable($axis, $audit, $obj);
        if (empty($dataTable)) {
            return json_decode(json_encode(
                array('error'=>'统计报表无法确定要查询的数据表, 请检查参数是否正确')
            ), true);
        } else {
            $select_fields = [
                'app.app_name',
                'app.platform',
                'aff.affiliateid',
                'aff.brief_name as affiliatesname',
                'aff.name',
                'z.zonename',
                'z.zoneid',
                'z.ad_type as zone_type',
                'cli.clientname',
                'p.name as product_name',
                'p.icon as product_icon',
                'p.id as product_id',
                'p.type',
                'c.ad_type',
                'c.revenue_type as client_revenue_type',
                'c.business_type',
                'c.revenue',
                'b.revenue_type as media_revenue_type',
                'b.bannerid',
                'af.channel',
                'u.contact_name'
            ];

            $groupBy = ['b.bannerid', 'z.zoneid'];
            $select = DB::table($dataTable)
                ->join('banners as b', 'b.bannerid', '=', "{$dataTable}.ad_id")
                ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
                ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
                ->join('appinfos as app', function ($join) {
                    $join->on('c.campaignname', '=', 'app.app_id')
                        ->on('c.platform', '=', 'app.platform')
                        ->on('cli.agencyid', '=', 'app.media_id');
                })
                ->join('zones as z', 'z.zoneid', '=', "{$dataTable}.zone_id")
                ->join('affiliates as aff', 'aff.affiliateid', '=', 'z.affiliateid')
                ->leftJoin('users as u', 'u.user_id', '=', 'aff.creator_uid')
                ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
                ->leftJoin('attach_files as af', 'af.id', '=', 'b.attach_file_id')
                ->where('cli.affiliateid', 0);

            $select = self::commonSelect(
                $select,
                $select_fields,
                $dataTable,
                $startDate,
                $endDate,
                $axis,
                $offset,
                $everyday
            );

            $select->where('aff.agencyid', $agencyId);
            $select->where('cli.agencyid', $agencyId);

            if ($affiliateid > 0) {
                $affiliateId = explode(",", $affiliateid);
                $select->whereIn('aff.affiliateid', $affiliateId);
            }

            if ($bannerid > 0) {
                $select->where('b.bannerid', $bannerid);
            }

            if ($creator_uid > 0) {
                $select->where('aff.creator_uid', $creator_uid);
            }

            if ($media_revenue_type > 0) {
                $select->where('b.revenue_type', $media_revenue_type);
            }

            if ($client_revenue_type > 0) {
                $select->where('c.revenue_type', $client_revenue_type);
            }

            if ($business_type <> 'all') {
                $select->where('c.business_type', $business_type);
            }

            foreach ($groupBy as $v) {
                $select->groupBy($v);
            }

            DB::setFetchMode(\PDO::FETCH_ASSOC);

            $stats = $select->get();

            return $stats;
        }

    }

    /**
     * 查询平台广告主-收入报表，对应平台【统计报表】-收入报表
     * @param date $startDate 开始日期 若$axis值是'hours',则传utc时间, $offset=-8
     * @param date $endDate 结束日期
     * @param string $axis 分组类型 按照月 日 或小时汇总, 'month', 'days', 'hours'
     * @param integer $offset 时区偏移量
     * @param integer $agencyId
     * @param $audit 0:审计前数据, 1:审计后数据
     * @param $creator_uid 平台用户的create_uid,默认是全部媒体商
     * @param $obj 区分媒体商，还是广告主的数据, 'affiliate', 'client'
     * @author ben
     */
    public static function findManagerCampaignStat(
        $startDate,
        $endDate,
        $axis,
        $offset,
        $agencyId,
        $audit = 0,
        $creator_uid = 0,
        $obj = 'client'
    ) {
        $dataTable = self::decideSearchDataTable($axis, $audit, $obj);
        if (empty($dataTable)) {
            return json_decode(json_encode(
                array('error'=>'统计报表无法确定要查询的数据表, 请检查参数是否正确')
            ), true);
        } else {
            $select_fields = [
                'c.campaignid',
                'c.ad_type',
                'c.revenue_type as client_revenue_type',
                'b.revenue_type as media_revenue_type',
                'c.clientid',
                'c.product_id',
                'c.campaignname',
                'c.business_type',
                'c.revenue as cpd',
            ];

            switch ($axis) {
                case 'month':
                case 'days':
                    $groupBy = [$dataTable.'.campaign_id', $dataTable . '.ad_id'];
                    $select = DB::table($dataTable)
                        ->join('banners as b', 'b.bannerid', '=', $dataTable . '.ad_id')
                        ->join('campaigns as c', 'c.campaignid', '=', $dataTable . '.campaign_id');
                    break;
                case 'hours':
                    $groupBy = ['c.campaignid', $dataTable . '.ad_id'];
                    $select = DB::table($dataTable)
                        ->join('banners as b', 'b.bannerid', '=', $dataTable . '.ad_id')
                        ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid');
                    break;
            }

            $select = self::commonSelect($select, $select_fields, $dataTable, $startDate, $endDate, $axis, $offset);

            foreach ($groupBy as $v) {
                $select->groupBy($v);
            }
            DB::setFetchMode(\PDO::FETCH_ASSOC);
            $stats = $select->get();
            return $stats;
        }

    }

    /**
     * 查询平台广告主-收入报表，查询广告在每个媒体广告位的投放数据
     * @param date $startDate 开始日期 若$axis值是'hours',则传utc时间, $offset=-8
     * @param date $endDate 结束日期
     * @param string $axis 分组类型 按照月 日 或小时汇总, 'month', 'days', 'hours'
     * @param integer $offset 时区偏移量
     * @param integer $campaignid
     * @param $audit 0:审计前数据, 1:审计后数据
     * @param $creator_uid 平台用户的create_uid,默认是全部媒体商
     * @param $obj 区分媒体商，还是广告主的数据, 'affiliate', 'client'
     * @author ben
     */
    public static function findManagerCampaignZoneStat(
        $startDate,
        $endDate,
        $axis,
        $offset,
        $campaignid,
        $audit = 0,
        $creator_uid = 0,
        $obj = 'client'
    ) {
        $dataTable = self::decideSearchDataTable($axis, $audit, $obj);
        if (empty($dataTable)) {
            return json_decode(json_encode(
                array('error'=>'统计报表无法确定要查询的数据表, 请检查参数是否正确')
            ), true);
        } else {
            $select_fields = [
                'z.zoneid',
                'z.zonename',
                'z.ad_type as zone_type',
                'z.description',
                'z.affiliateid',
                'b.attach_file_id',
                'b.bannerid',
                'b.revenue_type as media_revenue_type',
                'c.campaignid',
                'c.revenue_type as client_revenue_type'
            ];

            $groupBy = ['z.zoneid'];
            $select = DB::table($dataTable)
                ->join('banners as b', 'b.bannerid', '=', "{$dataTable}.ad_id")
                ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
                ->join('zones as z', 'z.zoneid', '=', "{$dataTable}.zone_id")
                ->join('clients as cli', 'cli.clientid', '=', "c.clientid")
                ->where('cli.affiliateid', 0);

            $select = self::commonSelect($select, $select_fields, $dataTable, $startDate, $endDate, $axis, $offset);

            if ($campaignid > 0) {
                $select->where('c.campaignid', $campaignid);
            }

            foreach ($groupBy as $v) {
                $select->groupBy($v);
            }

            DB::setFetchMode(\PDO::FETCH_ASSOC);
            $stats = $select->get();
            return $stats;
        }

    }

    /**
     * 导出平台广告主-收入报表, 导出报表或者导出每日报表
     * @param date $startDate 开始日期 若$axis值是'hours',则传utc时间, $offset=-8
     * @param date $endDate 结束日期
     * @param string $axis 分组类型 按照月 日 或小时汇总, 'month', 'days', 'hours'
     * @param integer $offset 时区偏移量
     * @param integer $agencyId
     * @param integer $campaignid
     * @param integer $bannerid
     * @param integer $productId
     * @param integer $media_revenue_type
     * @param integer $client_revenue_type
     * @param integer $business_type
     * @param $audit 0:审计前数据, 1:审计后数据
     * @param $creator_uid 平台用户的create_uid,默认是全部媒体商
     * @param $obj 区分媒体商，还是广告主的数据, 'affiliate', 'client'
     * @param $everyday 区分导出报表还是每日报表
     * @author ben
     */
    public static function findManagerCampaignExcelStat(
        $startDate,
        $endDate,
        $axis,
        $offset,
        $agencyId,
        $productId,
        $campaignid,
        $bannerid,
        $media_revenue_type,
        $client_revenue_type,
        $business_type,
        $audit = 0,
        $creator_uid = 0,
        $obj = 'client',
        $everyday = false
    ) {
        $dataTable = self::decideSearchDataTable($axis, $audit, $obj);
        if (empty($dataTable)) {
            return json_decode(json_encode(
                array('error'=>'统计报表无法确定要查询的数据表, 请检查参数是否正确')
            ), true);
        } else {
            if ($everyday) {
                $select_fields = [
                    'app.app_name',
                    'app.platform',
                    'aff.brief_name',
                    'aff.name',
                    'z.zoneid',
                    'z.zonename',
                    'z.ad_type as zone_type',
                    'b.bannerid',
                    'b.revenue_type as media_revenue_type',
                    'b.bannerid',
                    'c.revenue_type as client_revenue_type',
                    'c.revenue',
                    'c.ad_type',
                    'af.channel',
                   // 'cli.clientname',
                    'cli.brief_name as clientname',
                    'cli.broker_id',
                    'p.name as product_name',
                    'p.icon as product_icon',
                    'p.id as product_id',
                    'p.type',
                    'u.contact_name',
                    'c.business_type'
                ];
                $groupBy = ['z.zoneid', 'b.bannerid'];
                $select = DB::table($dataTable)
                    ->join('banners as b', 'b.bannerid', '=', "{$dataTable}.ad_id")
                    ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
                    ->join('clients as cli', 'c.clientid', '=', 'cli.clientid')
                    ->join('appinfos as app', function ($join) {
                        $join->on('c.campaignname', '=', 'app.app_id')
                            ->on('c.platform', '=', 'app.platform')
                            ->on('cli.agencyid', '=', 'app.media_id');
                    })
                    ->join('users as u', 'u.user_id', '=', 'cli.creator_uid')
                    ->join('zones as z', 'z.zoneid', '=', "{$dataTable}.zone_id")
                    ->join('affiliates as aff', 'aff.affiliateid', '=', 'z.affiliateid')
                    ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
                    ->leftJoin('attach_files as af', 'af.id', '=', 'b.attach_file_id')
                    ->where('cli.affiliateid', 0);
            } else {
                $select_fields = [
                    'app.app_name',
                    'app.platform',
                    'aff.brief_name',
                    'aff.name',
                    'b.revenue_type as media_revenue_type',
                    'c.ad_type',
                    'c.revenue',
                    'c.revenue_type as client_revenue_type',
                    'cli.clientname',
                    'cli.broker_id',
                    'z.zonename',
                    'z.ad_type as zone_type',
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.icon as product_icon',
                    'p.type',
                    'af.channel',
                    'u.contact_name',
                    'c.business_type'
                ];
                $groupBy = ['z.zoneid', 'b.bannerid', 'cli.creator_uid', 'cli.clientid'];
                $select = DB::table($dataTable)
                    ->join('banners as b', 'b.bannerid', '=', "{$dataTable}.ad_id")
                    ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
                    ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
                    ->join('appinfos as app', function ($join) {
                        $join->on('c.campaignname', '=', 'app.app_id')
                            ->on('c.platform', '=', 'app.platform')
                            ->on('cli.agencyid', '=', 'app.media_id');
                    })
                    ->join('zones as z', 'z.zoneid', '=', "{$dataTable}.zone_id")
                    ->join('affiliates as aff', 'aff.affiliateid', '=', 'z.affiliateid')
                    ->join('users as u', 'u.user_id', '=', 'cli.creator_uid')
                    ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
                    ->leftJoin('attach_files as af', 'af.id', '=', 'b.attach_file_id')
                    ->where('cli.affiliateid', 0);
            }

            $select = self::commonSelect(
                $select,
                $select_fields,
                $dataTable,
                $startDate,
                $endDate,
                $axis,
                $offset,
                $everyday
            );


            if ($agencyId > 0) {
                $select->where('cli.agencyid', $agencyId);
            }

            if ($productId > 0) {
                $productId = explode(',', $productId);
                $select->whereIn('p.id', $productId);
            }

            if ($campaignid > 0) {
                $select->where('c.campaignid', $campaignid);
            }

            if ($bannerid > 0) {
                $select->where('b.bannerid', $bannerid);
            }

            if ($creator_uid > 0) {
                $select->where('cli.creator_uid', $creator_uid);
            }

            if ($media_revenue_type > 0) {
                $select->where('b.revenue_type', $media_revenue_type);
            }

            if ($client_revenue_type > 0) {
                $select->where('c.revenue_type', $client_revenue_type);
            }
            if ($business_type <> 'all') {
                $select->where('c.business_type', $business_type);
            }
            foreach ($groupBy as $v) {
                $select->groupBy($v);
            }

            DB::setFetchMode(\PDO::FETCH_ASSOC);

            $stats = $select->get();

            return $stats;
        }

    }

    /**
     * @媒体商-导出每日报表
     * @param $startDate 开始时间
     * @param $endDate 结束时间
     * @param $axis 分组类型（按照 年或者天）
     * @param $affiliateId 媒体商ID
     * @param $type 广告位/广告
     * @param $revenueType 计费类型 CPC/CPD
     * @param $firstCondition广告类型（广告位）/产品类型（广告）
     * @param $secondCondition广告位ID（广告位）/CampaignID（广告）
     * @return array
     */
    public static function findTrafficTimeExcelStat(
        $startDate,
        $endDate,
        $axis,
        $affiliateId,
        $type,
        $revenueType,
        $firstCondition,
        $secondCondition
    ) {
        $prefix = DB::getTablePrefix();
        $real_table = $prefix . 'data_hourly_daily_af';
        $select = DB::table('data_hourly_daily_af')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_af.campaign_id')
            ->join('clients', 'campaigns.clientid', '=', 'clients.clientid')
            ->join('banners', 'banners.bannerid', '=', 'data_hourly_daily_af.ad_id')
            ->where('data_hourly_daily_af.affiliateid', $affiliateId)
            ->where('banners.revenue_type', $revenueType)
            ->where('clients.affiliateid', 0);
        //获取展示量、 支出、 点击量/下载量
        $select->select(DB::raw('IFNULL(SUM(' . $real_table . '.`impressions`),0) as `sum_views`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $real_table . '.`af_income`),0) as `sum_revenue`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $real_table . '.`file_down`),0) as `sum_download_complete`'));
        if ($revenueType == Campaign::REVENUE_TYPE_CPC) {
            $select ->addSelect(DB::raw('IFNULL(SUM(' . $real_table . '.`clicks`),0) as `sum_clicks`'));
        } elseif ($revenueType == Campaign::REVENUE_TYPE_CPA) {
            $select ->addSelect(DB::raw('IFNULL(SUM(' . $real_table . '.`cpa`),0) as `sum_clicks`'));
        } else {
            $select ->addSelect(DB::raw('IFNULL(SUM(' . $real_table . '.`conversions`),0) as `sum_clicks`'));
        }

        switch ($axis) {
            case 'month':
                $select->addSelect(DB::raw("DATE_FORMAT({$real_table}.`date`,'%Y-%m') AS time"))
                    ->groupBy('time');
                $select->whereBetween('data_hourly_daily_af'. '.date', array($startDate, $endDate));
                break;
            case 'hours':
            case 'days':
                $select->addSelect(DB::raw("DATE_FORMAT({$real_table}.`date`,'%Y-%m-%d') AS time"))
                    ->groupBy('time');
                $select->whereBetween('data_hourly_daily_af'. '.date', array($startDate, $endDate));
                break;
        }
        if ($type == 1) {
            //广告位 按照广告类型及广告位ID
            if ($firstCondition <> ['all']) {
                $select->whereIn('campaigns.ad_type', $firstCondition);
            }
            if ($secondCondition <> 'all') {
                $select->where('data_hourly_daily_af.zone_id', $secondCondition);
            }
        } else {
            //广告 按照产品和广告
            if ($firstCondition <> 'all') {
                $select->where('campaigns.product_id', $firstCondition);
            }
            if ($secondCondition <> 'all') {
                $select->where('data_hourly_daily_af.campaign_id', $secondCondition);
            }
        }

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $stats = $select->get();
        return $stats;
    }

    /**
     * 确定要查询的数据表
     * @param string $axis
     * @param integer $audit
     * @param string $obj
     */
    private static function decideSearchDataTable($axis, $audit, $obj)
    {
        $dataTable = '';
        //如果是审计前按每小时显示的数据，则查询hourly表,
        //审计后媒体商的数据查询data_hourly_daily_af,
        //广告主查询up_data_hourly_daily_client
        if ($axis == 'hours' && $audit == 0) {
            $dataTable = 'data_summary_ad_hourly';
        } elseif ($axis == 'hours' && $audit == 1 && $obj == 'affiliate') {
            $dataTable = 'data_hourly_daily_af';
        } elseif (($axis == 'days' || $axis == 'month') && $audit == 0) {
            $dataTable = 'data_hourly_daily';
        } elseif (($axis == 'days' || $axis == 'month') && $audit == 1 && $obj == 'affiliate') {
            $dataTable = 'data_hourly_daily_af';
        } elseif (($axis == 'days' || $axis == 'month' || $axis == 'hours') && $audit == 1 && $obj == 'client') {
            $dataTable = 'data_hourly_daily_client';
        } else {
            $dataTable = '';
        }
        return $dataTable;
    }

    /**
     * 平台统计报表要查询的数据字段
     * @param string $table
     */
    private static function commonSearchDataFields($table)
    {
        $dataFields = [
            DB::raw('IFNULL(SUM(' . $table . '.`impressions`),0) as `sum_views`'),
            DB::raw('IFNULL(SUM(' . $table . '.`conversions`),0) as `sum_clicks`'),
            DB::raw('IFNULL(SUM(' . $table . '.`clicks`),0) as `sum_cpc_clicks`'),
            DB::raw('IFNULL(SUM(' . $table . '.`file_click`),0) as `sum_download_requests`'),
            DB::raw('IFNULL(SUM(' . $table . '.`file_down`),0) as `sum_download_complete`'),
            DB::raw('IFNULL(SUM(' . $table . '.`cpa`),0) as `sum_cpa`'),
            DB::raw('IFNULL(SUM(' . $table . '.`consum`),0) as `sum_consum`'),
            DB::raw('IFNULL(SUM(' . $table . '.`af_income`),0) - IFNULL( SUM(' . $table . '.`af_income` * ' . $table .
                '.`total_revenue_gift` / ' . $table . '.`total_revenue`), 0) as `sum_payment`'),
            DB::raw('IFNULL(SUM(' . $table . '.`af_income` * ' . $table . '.`total_revenue_gift` / ' . $table .
                '.`total_revenue`),0) as `sum_payment_gift`'),
            DB::raw('IFNULL(SUM(' . $table . '.`total_revenue`),0) - IFNULL(SUM(' . $table .
                '.`total_revenue_gift`),0) as `sum_revenue`'),
            DB::raw('IFNULL(SUM(' . $table . '.`total_revenue_gift`),0) as `sum_revenue_gift`')
        ];

        return $dataFields;
    }

    /**
     * 广告主角色-导出每日报表
     * @param $startDate 开始时间
     * @param $endDate 结束时间
     * @param $axis 分组类型
     * @param $type 广告类型（安装包/链接推广）
     * @param $clientId 广告主ID
     * @param $productId 产品ID
     * @param $campaignId 广告ID
     * @return array()
     */
    public static function findAdvertiserDailyExcelData(
        $startDate,
        $endDate,
        $axis,
        $type,
        $clientId,
        $productId,
        $campaignId
    ) {
        $select_field = [
            'app.app_name',
            'app.platform',
            'p.type',
            'p.name as product_name',
            'p.icon as product_icon',
            'p.id as product_id',
            'cam.ad_type',
            'cam.revenue_type as client_revenue_type',
            'att.channel',
            'cam.campaignid'
        ];
        if ($type == Product::TYPE_APP_DOWNLOAD) {
            $groupBy = array(
                'att.channel',
                'cam.campaignid'
            );
        } elseif ($type == Product::TYPE_LINK) {
            $groupBy = array(
                'cam.campaignid'
            );
        }
        $hourly_table = DB::getTablePrefix() . 'data_hourly_daily_client';
        $select = DB::table('data_hourly_daily_client')
            ->join('campaigns as cam', 'data_hourly_daily_client.campaign_id', '=', 'cam.campaignid')
            ->join('banners as b', 'data_hourly_daily_client.ad_id', '=', 'b.bannerid')
            ->join('products as p', 'cam.product_id', '=', 'p.id')
            ->join('appinfos as app', 'app.app_id', '=', 'cam.campaignname')
            ->leftjoin('attach_files as att', 'att.id', '=', 'b.attach_file_id');
        //汇总展示量、支出、下载量、点击量
        $select->select(DB::raw('IFNULL(SUM(' . $hourly_table . '.`impressions`),0) as `sum_views`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`total_revenue`),0) as `sum_revenue`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`conversions`),0) as `sum_clicks`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`clicks`),0) as `sum_cpc_clicks`'));

        $select->addSelect(DB::raw("DATE_FORMAT({$hourly_table}.`date`,'%Y-%m-%d') AS time"))
            ->groupBy('time');
        $select->where('p.type', $type);
        if ($clientId > 0) {
            $select->where('data_hourly_daily_client.clientid', $clientId);
        }

        if ($productId > 0) {
            $select->where('p.id', $productId);
        }

        if ($campaignId > 0) {
            $select->where('c.campaignid', $campaignId);
        }
        $select->whereBetween('data_hourly_daily_client.date', array($startDate, $endDate));
        $select->addSelect($select_field);

        foreach ($groupBy as $v) {
            $select->groupBy($v);
        }
        DB::setFetchMode(\PDO::FETCH_ASSOC);

        $stats = $select->get();

        return $stats;
    }
    /**
     * 代理商角色-导出每日报表
     * @param $startDate 开始时间
     * @param $endDate 结束时间
     * @param $axis 分组类型
     * @param $type 广告类型（安装包/链接推广）
     * @param $clientId 广告主ID
     * @param $productId 产品ID
     * @param $campaignId 广告ID
     * @return array()
     */
    public static function findBrokerDailyExcelData(
        $startDate,
        $endDate,
        $type,
        $brokerId,
        $clientId,
        $productId,
        $campaignId
    ) {
        $select_field = [
            'app.app_name',
            'app.platform',
            'p.type',
            'p.name as product_name',
            'p.icon as product_icon',
            'p.id as product_id',
            'cam.ad_type',
            'cam.revenue_type as client_revenue_type',
            'att.channel',
            'cam.campaignid',
            'cli.clientname'
        ];
        if ($type == Product::TYPE_APP_DOWNLOAD) {
            $groupBy = array(
                'att.channel',
                'cam.campaignid'
            );
        } elseif ($type == Product::TYPE_LINK) {
            $groupBy = array(
                'cam.campaignid'
            );
        }
        $table = 'data_hourly_daily_client';
        if (Auth::user()->account->broker->affiliateid > 0) {
            if (Auth::user()->account->broker->affiliateid > 0) {
                if ($startDate == $endDate) {
                    list($startDate, $endDate, $axis) = StatService::dateConversion(
                        $startDate,
                        $endDate
                    );
                    $table = 'data_summary_ad_hourly';
                } else {
                    $table =  'data_hourly_daily';
                }
            }
        }
        $hourly_table = DB::getTablePrefix() . $table;
        $select = DB::table("{$table}")
            ->join('campaigns as cam', 'data_hourly_daily_client.campaign_id', '=', 'cam.campaignid')
            ->join('banners as b', 'data_hourly_daily_client.ad_id', '=', 'b.bannerid')
            ->join('products as p', 'cam.product_id', '=', 'p.id')
            ->join('clients as cli', 'data_hourly_daily_client.clientid', '=', 'cli.clientid')
            ->join('appinfos as app', function ($join) {
                $join->on('cam.campaignname', '=', 'app.app_id')
                    ->on('cam.platform', '=', 'app.platform')
                    ->on('cli.agencyid', '=', 'app.media_id');
            })
            ->leftjoin('attach_files as att', 'att.id', '=', 'b.attach_file_id');
        //汇总展示量、支出、下载量、点击量
        $select->select(DB::raw('IFNULL(SUM(' . $hourly_table . '.`impressions`),0) as `sum_views`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`total_revenue`),0) as `sum_revenue`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`conversions`),0) as `sum_clicks`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`clicks`),0) as `sum_cpc_clicks`'));

        $select->addSelect(DB::raw("DATE_FORMAT({$hourly_table}.`date`,'%Y-%m-%d') AS time"))
            ->groupBy('time');
        $select->where('cli.broker_id', $brokerId)
            ->where('p.type', $type);
        if ($clientId > 0) {
            $select->where('data_hourly_daily_client.clientid', $clientId);
        }

        if ($productId > 0) {
            $select->where('p.id', $productId);
        }

        if ($campaignId > 0) {
            $select->where('c.campaignid', $campaignId);
        }

        $select->whereBetween('data_hourly_daily_client.date', array($startDate, $endDate));
        $select->addSelect($select_field);

        foreach ($groupBy as $v) {
            $select->groupBy($v);
        }
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $stats = $select->get();
        return $stats;
    }

    /**
     * 媒体商角色-导出每日报表广告位
     * @param $startDate
     * @param $endDate
     * @param $affiliateId
     * @param $revenueType
     * @param $adType
     * @param $zoneId
     * @return array()
     */
    public static function findTrafficDailyZoneExcel(
        $startDate,
        $endDate,
        $affiliateId,
        $revenueType,
        $adType,
        $zoneId
    ) {
        $campaign_table = DB::getTablePrefix() . 'campaigns';
        $select_field = [
            'products.id as product_id',
            'products.name as product_name',
            'products.type as product_type',
            'products.icon as product_icon',
            'banners.revenue_type',
            'campaigns.campaignid',
            'appinfos.app_name',
            'zones.zoneid as zone_id',
            'zones.zonename as zone_name',
            'zones.type as zone_type',
            'zones.platform',
            'banners.bannerid',
        ];
        $groupBy = ['zones.zoneid','campaigns.campaignid'];
        $hourly_table = DB::getTablePrefix() . 'data_hourly_daily_af';
        $select = DB::table('data_hourly_daily_af')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_af.campaign_id')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->join('zones', 'zones.zoneid', '=', 'data_hourly_daily_af.zone_id')
            ->join('banners', 'data_hourly_daily_af.ad_id', '=', 'banners.bannerid')
            ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
            ->join('appinfos', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('campaigns.platform', '=', 'appinfos.platform');
            })
            ->where('clients.affiliateid', 0);

        //汇总展示量、支出、下载量、点击量
        $select->select(DB::raw('IFNULL(SUM(' . $hourly_table . '.`impressions`),0) as `sum_views`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`af_income`),0) as `sum_revenue`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`file_down`),0) as `sum_download_complete`'));
        //如果是cpd查询下载量 、如果是cpc查询点击量
        if ($revenueType == Campaign::REVENUE_TYPE_CPC) {
            $select->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`clicks`),0) as `sum_clicks`'));
        } elseif ($revenueType == Campaign::REVENUE_TYPE_CPA) {
            $select->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`cpa`),0) as `sum_clicks`'));
        } else {
            $select->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`conversions`),0) as `sum_clicks`'));
        }


        $select->addSelect(DB::raw("DATE_FORMAT({$hourly_table}.`date`,'%Y-%m-%d') AS time"))
            ->groupBy('time');
        
        $AdType = implode(',', Campaign::getReportAdType());
        $select->addSelect(
            DB::raw('CASE WHEN ' . $campaign_table . '.ad_type = ' . Campaign::AD_TYPE_FULL_SCREEN .
                ' THEN ' . Campaign::AD_TYPE_HALF_SCREEN .
                ' WHEN ' . $campaign_table . '.ad_type = ' . Campaign::AD_TYPE_BANNER_TEXT_LINK .
                ' THEN ' . Campaign::AD_TYPE_BANNER_IMG .
                ' WHEN (' . $campaign_table . '.ad_type IN ('.$AdType.
                ')) THEN ' . $campaign_table .
                '.ad_type END AS `ad_type`')
        );

        $select->whereBetween('data_hourly_daily_af.date', [$startDate, $endDate])
            ->where('banners.revenue_type', $revenueType)
            ->where('data_hourly_daily_af.affiliateid', $affiliateId);

        $select->addSelect($select_field);

        if ($adType) {
            $select->whereIn('campaigns.ad_type', $adType);
        }

        if ($zoneId) {
            $select->where('data_hourly_daily_af.zone_id', $zoneId);
        }

        foreach ($groupBy as $v) {
            $select->groupBy($v);
        }
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $stats = $select->get();
        return $stats;
    }
    /**
     * 媒体商角色-导出每日报表广告位
     * @param $startDate
     * @param $endDate
     * @param $affiliateId
     * @param $revenueType
     * @param $productId
     * @param $campaignId
     * @return array()
     */
    public static function findTrafficDailyClientExcel(
        $startDate,
        $endDate,
        $affiliateId,
        $revenueType,
        $productId,
        $campaignId
    ) {
        $campaign_table = DB::getTablePrefix() . 'campaigns';
        $select_field = [
            'zones.zoneid as zone_id',
            'zones.zonename as zone_name',
            'zones.type as zone_type',
            'zones.platform',
            'banners.bannerid',
            'products.id as product_id',
            'products.name as product_name',
            'products.type as product_type',
            'products.icon as product_icon',
            'banners.revenue_type',
            'campaigns.campaignid',
            'appinfos.app_name',
        ];
        $groupBy = ['zones.zoneid','campaigns.campaignid'];
        $hourly_table = DB::getTablePrefix() . 'data_hourly_daily_af';
        $select = DB::table('data_hourly_daily_af')
            ->join('campaigns', 'campaigns.campaignid', '=', 'data_hourly_daily_af.campaign_id')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->join('banners', 'data_hourly_daily_af.ad_id', '=', 'banners.bannerid')
            ->join('zones', 'zones.zoneid', '=', 'data_hourly_daily_af.zone_id')
            ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
            ->join('appinfos', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('campaigns.platform', '=', 'appinfos.platform');
            });

        //汇总展示量、支出、下载量、点击量
        $select->select(DB::raw('IFNULL(SUM(' . $hourly_table . '.`impressions`),0) as `sum_views`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`af_income`),0) as `sum_revenue`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`file_down`),0) as `sum_download_complete`'));
        //如果是cpd查询下载量 、如果是cpc查询点击量
        if ($revenueType == Campaign::REVENUE_TYPE_CPD) {
            $select->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`conversions`),0) as `sum_clicks`'));
        } elseif ($revenueType == Campaign::REVENUE_TYPE_CPA) {
            $select->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`cpa`),0) as `sum_clicks`'));
        } else {
            $select->addSelect(DB::raw('IFNULL(SUM(' . $hourly_table . '.`clicks`),0) as `sum_clicks`'));
        }

        $AdType = implode(',', Campaign::getReportAdType());
        $select->addSelect(
            DB::raw('CASE WHEN ' . $campaign_table . '.ad_type = ' . Campaign::AD_TYPE_FULL_SCREEN .
                ' THEN ' . Campaign::AD_TYPE_HALF_SCREEN .
                ' WHEN ' . $campaign_table . '.ad_type = ' . Campaign::AD_TYPE_BANNER_TEXT_LINK .
                ' THEN ' . Campaign::AD_TYPE_BANNER_IMG .
                ' WHEN (' . $campaign_table . '.ad_type IN ('.$AdType.
                ')) THEN ' . $campaign_table .
                '.ad_type END AS `ad_type`')
        );

        $select->addSelect(DB::raw("DATE_FORMAT({$hourly_table}.`date`,'%Y-%m-%d') AS time"))
            ->groupBy('time');

        $select->whereBetween('data_hourly_daily_af.date', [$startDate, $endDate])
            ->where('data_hourly_daily_af.affiliateid', $affiliateId)
            ->where('banners.revenue_type', $revenueType);

        $select->addSelect($select_field);

        if ($productId) {
            $select->where('products.id', $productId);
        }

        if ($campaignId) {
            $select->where('data_hourly_daily_af.campaign_id', $campaignId);
        }

        foreach ($groupBy as $v) {
            $select->groupBy($v);
        }
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $stats = $select->get();
        return $stats;
    }

    /**
     * 公共的查询字段和条件
     * @param mixed $select
     * @param array $fields
     * @param string $dataTable
     * @param date $startDate
     * @param date $endDate
     * @param string $axis
     * @param int $offset
     * @param boolean $everyday
     */
    public static function commonSelect(
        $select,
        $fields,
        $dataTable,
        $startDate,
        $endDate,
        $axis,
        $offset,
        $everyday = true
    ) {
        $prefix = DB::getTablePrefix();
        $real_table = $prefix.$dataTable;
        $select->addSelect($fields);
        $select->addSelect(self::commonSearchDataFields($real_table));
        if (0 > $offset) {
            $offset = 0 - $offset;
            $dateQuery = "DATE_ADD";
        } else {
            $dateQuery = "DATE_SUB";
        }
        if ($everyday) {
            switch ($axis) {
                case 'month':
                    $select->addSelect(DB::raw("DATE_FORMAT({$real_table}.`date`,'%Y-%m-01') AS time"))
                        ->groupBy('time');
                    $select->whereBetween($dataTable. '.date', array($startDate, $endDate));
                    break;
                case 'days':
                    $select->addSelect(DB::raw("DATE_FORMAT({$real_table}.`date`,'%Y-%m-%d') AS time"))
                        ->groupBy('time');
                    $select->whereBetween($dataTable. '.date', array($startDate, $endDate));
                    break;
                case 'hours':
                    $select->addSelect(DB::raw("DATE_FORMAT($dateQuery({$real_table}.`date_time`,
                    INTERVAL $offset HOUR),'%Y-%m-%d %H:00:00') AS time"))->groupBy('time');
                    $select->whereBetween($dataTable. '.date_time', array($startDate, $endDate));
                    break;
            }
        } else {
            switch ($axis) {
                case 'month':
                case 'days':
                    $select->whereBetween($dataTable. '.date', array($startDate, $endDate));
                    break;
                case 'hours':
                    $select->whereBetween($dataTable. '.date_time', array($startDate, $endDate));
                    break;
            }
        }
        return $select;
    }

    /**
     * 生成csv文件
     * @param string $filename 文件名
     * @param $column 列名
     * @param string $data 源数据
     */
    public static function downloadCsv($filename, $column, $data)
    {
        $newFileName = iconv('utf-8', 'gbk', $filename);
        $title = implode(',', $column);
        $data_str = '';
        $data_str .= iconv('utf-8', 'gbk', $title) ."\n";
        foreach ($data as $k => $row) {
            $tmp_str = '';
            $tmp_str = implode("___", $row);
            $tmp_str = rtrim($tmp_str, '___');
            $tmp_str = str_replace('•', '', $tmp_str);
            $tmp_str = iconv('utf-8', 'gbk', $tmp_str);
            $tmp_str = str_replace(',', '', $tmp_str);
            $tmp_str = str_replace('___', ',', $tmp_str);
            $data_str .= $tmp_str;
            $data_str .= "\n";

        }
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=". $newFileName .".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data_str;
        exit;
    }
    /**
     * 销售概览-获取广告排行数据
     * @param $start
     * @param $end
     * @param $agencyId
     * @param $table
     * @param $creator_uid
     * @return mixed
     */
    public static function findSaleRankData($start, $end, $table, $agencyId, $creator_uid = 0)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = DB::getTablePrefix();
        $query = DB::table("{$table} as h")
            ->join('campaigns as c', 'c.campaignid', '=', 'h.campaign_id')
            ->join('clients as cli', 'c.clientid', '=', 'cli.clientid')
            ->join('appinfos as app', function ($join) {
                $join->on('c.campaignname', '=', 'app.app_id')
                    ->on('c.platform', '=', 'app.platform')
                    ->on('cli.agencyid', '=', 'app.media_id');
            })
            ->select(
                'h.campaign_id',
                'app.app_name',
                DB::raw('IFNULL(SUM(' .$prefix . 'h.conversions),0) as sum_clicks'), //下载量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.impressions),0) as sum_views'), //展示量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.clicks),0) as sum_cpc_clicks'), //点击量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.total_revenue),0) as sum_revenue'), //广告主消耗
                DB::raw('IFNULL(SUM(' .$prefix . 'h.cpa),0) as sum_cpa'), //cpa量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.total_revenue),0) -
                IFNULL(SUM(' .$prefix . 'h.af_income),0) as profit') //平台毛利
            )
            ->where('cli.agencyid', $agencyId)
            ->where('cli.affiliateid', 0)
            ->whereBetween('h.date', [$start, $end])
            ->groupBy('h.campaign_id')
            ->orderBy('sum_revenue', 'desc');
        if ($creator_uid) {
            $query->where('cli.creator_uid', $creator_uid);
        }
        $res = $query->get();
        return $res;
    }
    /**
     * 运营概览-计算环比
     * @param $yesterday
     * @param $before
     * @return array
     */
    public static function getRate($yesterday, $before)
    {
        if (($yesterday - $before) != 0) {
            $rate = Formatter::asDecimal(floatval($before) ? ($yesterday - $before) * 100 / $before : 100, 2);
        } else {
            $rate = 0;
        }
        return [
            $yesterday,
            $yesterday - $before,
            $rate
        ];
    }
    /**
     * 运营概览:获取展示量、下载量、点击量、广告主消耗、及平台毛利
     * 媒介概览：获取展示量、下载量、点击量及媒体收入
     * @param $start
     * @param $end
     * @param $table 媒介查询daily_af表，运营查询daily表
     * @param $agencyId
     * @param $affiliateId
     * @param $type
     * @param $creator_uid
     * @return array
     */
    public static function getTrendData($start, $end, $table, $agencyId, $affiliateId, $type, $creator_uid = 0)
    {
        $prefix = DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $query = DB::table("$table as h")
            ->join('banners as b', 'b.bannerid', '=', 'h.ad_id')
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'b.affiliateid')
            ->join('campaigns as c', 'h.campaign_id', '=', 'c.campaignid')
            ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
            ->where('cli.affiliateid', $affiliateId)
            ->whereBetween('h.date', [$start, $end])
            ->where('aff.agencyid', $agencyId)
            ->select(
                DB::raw('IFNULL(SUM(' .$prefix . 'h.impressions),0) as views'), //展示量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.af_income),0) as payment'), //媒体商收入
                DB::raw('IFNULL(SUM(' .$prefix . 'h.conversions),0) as clicks'), //下载量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.clicks),0) as cpc_clicks'), //点击量
                DB::raw('IFNULL(SUM(' .$prefix . 'h.total_revenue),0) as revenue'), //广告主消耗
                DB::raw('IFNULL(SUM(' .$prefix . 'h.total_revenue),0) -
                IFNULL(SUM(' .$prefix . 'h.af_income),0) as income'), //平台毛利
                'h.date'
            )
            ->groupBy('h.date')
            ->orderBy('h.date');
        if ($creator_uid) {
            $query->where('aff.creator_uid', $creator_uid);
        }
        if ($type == 1) {
            $query->addSelect('aff.brief_name', 'aff.affiliateid')
                ->groupBy('aff.affiliateid');
        } else {
            $query->join('appinfos as app', function ($join) {
                $join->on('c.campaignname', '=', 'app.app_id')
                    ->on('c.platform', '=', 'app.platform')
                    ->on('cli.agencyid', '=', 'app.media_id');
            })
                ->addSelect('app.app_name as brief_name', 'c.campaignid as affiliateid')
                ->groupBy('c.campaignid');
        }
        $res = $query->get();
        return $res;
    }
    /**
     * 运营概览-重组数据（汇总30天总数）
     * @param $label
     * @param $res
     * @return array
     */
    public static function recombinantData($label, $res)
    {
        $list = [];
        if (empty($res)) {
            foreach ($label as $v) {
                $list[$v] = [];
            }
        } else {
            foreach ($label as $v) {
                foreach ($res as $val) {
                    if (isset($list[$v]['summary'][$val['date']])) {
                        $list[$v]['summary'][$val['date']][$v] += $val[$v];
                    } else {
                        $list[$v]['summary'][$val['date']] = [
                            'time' => $val['date'],
                            $v => $val[$v]
                        ];
                    }
                    $list[$v]['data'][$val['affiliateid']]['child'][$val['date']] = [
                        $v => $val[$v],
                        'time' => $val['date']
                    ];
                    $list[$v]['data'][$val['affiliateid']]['brief_name'] = $val['brief_name'];
                }
                $list[$v]['summary'] = array_values($list[$v]['summary']);
                foreach ($list[$v]['data'] as $rows => $value) {
                    $list[$v]['data'][$rows]['child'] = array_values($list[$v]['data'][$rows]['child']);
                }
            }
        }
        return $list;
    }
    /**
     * 获取有效的cpd\cpc的广告
     * @param $start
     * @param $end
     * @param $table
     * @param $agencyId
     * @param $affiliateId
     * @param $condition
     * @return mixed
     */
    public static function getValidAd($start, $end, $table, $agencyId, $affiliateId, $condition)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = DB::getTablePrefix();
        $hourly = $prefix . $table;
        $res = DB::table(DB::raw(
            " (SELECT SUM(h.{$condition}) as clicks,h.date
                FROM {$hourly} as h
                INNER JOIN {$prefix}campaigns as c ON c.campaignid = h.campaign_id
                INNER JOIN {$prefix}clients as cli ON cli.clientid = c.clientid
                WHERE h.date >= '{$start}'
                AND h.date <=  '{$end}'
                AND cli.agencyid = {$agencyId}
                AND cli.affiliateid = {$affiliateId}
                GROUP BY
                h.date,h.campaign_id
            ) as t"
        ))
            ->where('clicks', '>', 10)
            ->select(
                DB::raw('COUNT(*) as sum_ad'),
                'date as time'
            )
            ->groupBy('date')
            ->get();
        return $res;
    }
    /**
     * 获取有效的充值 包括代理商和广告主
     * @param $start
     * @param $end
     * @param $agencyId
     * @param $affiliateId
     * @return mixed
     */
    public static function getValidRecharge($start, $end, $agencyId, $affiliateId)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = DB::getTablePrefix();
        $res = DB::table('recharge as r')
            ->join('clients as cli', 'r.target_accountid', '=', 'cli.account_id')
            ->where('cli.affiliateid', $affiliateId)
            ->whereBetween('r.date', [$start, $end])
            ->where('r.status', '=', 2)
            ->where('cli.agencyid', $agencyId)
            ->select(
                DB::raw('sum(' . $prefix . 'r.amount) as amount'),
                'date as time'
            )
            ->groupBy('date')
            ->get();
        return $res;
    }

    /**
     * 获取自营报表数据-自营媒体及广告主报表数据
     * @param $start
     * @param $end
     * @param $affiliateId
     * @param $axis
     * @param $zoneOffset
     * @param $clientId
     * @param $campaignId
     * @param $zoneId
     * @return mixed
     */
    public static function findSelfTrafficker(
        $start,
        $end,
        $axis,
        $affiliateId,
        $clientId = 0,
        $campaignId = 0,
        $zoneId = 0,
        $zoneOffset = -8
    ) {
        $prefix = DB::getTablePrefix();
        $table = self::decideSearchDataTable($axis, 0, '');
        $query = DB::table("{$table} as h")
            ->join('banners as b', 'b.bannerid', '=', 'h.ad_id')
            ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
            ->join('zones as z', 'z.zoneid', '=', 'h.zone_id')
            ->join('appinfos as app', function ($join) {
                $join->on('c.campaignname', '=', 'app.app_id')
                    ->on('c.platform', '=', 'app.platform')
                    ->on('cli.agencyid', '=', 'app.media_id');
            })
            ->select(
                'z.zoneid',
                'z.zonename',
                'c.campaignid',
                'app.app_name',
                'app.app_show_icon',
                'h.ad_id',
                'cli.brief_name',
                'b.revenue_type'
            )
            ->addselect(DB::raw('IFNULL(SUM(' .$prefix . 'h.`impressions`),0) as `sum_views`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'h.`total_revenue`),0) as `sum_revenue`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'h.`total_revenue_gift`),0) as `sum_revenue_gift`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'h.`conversions`),0) as `sum_clicks`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'h.`clicks`),0) as `sum_cpc_clicks`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'h.`cpa`),0) as `sum_cpa`'))
            ->where('cli.affiliateid', $affiliateId)
            ->groupBy('z.zoneid', 'c.campaignid');
        if ($clientId) {
            $query->where('cli.clientid', $clientId);
        }
        if ($campaignId) {
            $query->where('c.campaignid', $campaignId);
        }
        if ($zoneId) {
            $query->where('h.zone_id', $zoneId);
        }
        if (0 > $zoneOffset) {
            $zoneOffset = 0 - $zoneOffset;
            $dateQuery = "DATE_ADD";
        } else {
            $dateQuery = "DATE_SUB";
        }
        switch ($axis) {
            case 'month':
                $query->addSelect(DB::raw("DATE_FORMAT({$prefix}h.`date`,'%Y-%m-01') AS time"))
                    ->whereBetween('h.date', [$start, $end])->groupBy('time');
                break;
            case 'days':
                $query->addSelect(DB::raw("DATE_FORMAT({$prefix}h.`date`,'%Y-%m-%d') AS time"))
                    ->whereBetween('h.date', [$start, $end])->groupBy('time');
                break;
            case 'hours'://小时数查询up_data_summary_ad_hourly表
                $query->addSelect(DB::raw("DATE_FORMAT($dateQuery({$prefix}h.`date_time`,
                    INTERVAL $zoneOffset HOUR),'%Y-%m-%d %H:00:00') AS time"))
                    ->whereBetween('h.date_time', [$start, $end])->groupBy('time');
                break;
        }
        $res = $query->get();
        return json_decode(json_encode($res), true);
    }
    public static function regroupAdvertiserReport($result, $start)
    {
        $label = ['sum_views', 'sum_clicks', 'sum_revenue', 'price'];
        $list = [];
        //将获取的数据按照广告计划进行汇总
        $res = [];
        foreach ($result as $row) {
            $item = $row;
            if (isset($row['type']) && ($row['type'] == Product::TYPE_LINK)) {
                $item['sum_clicks'] = $row['sum_cpc_clicks'];
            }
            $res[] = $item;
        }
        foreach ($res as $key => &$val) {
            $val['price'] = $val['sum_clicks'] ? $val['sum_revenue'] / $val['sum_clicks'] : 0;
            if (isset($list[$val['id']])) {
                foreach ($label as $k => $v) {
                    $list[$val['id']][$v] += $val[$v];
                    if ($list[$val['id']]['sum_clicks'] > 0) {
                        $list[$val['id']]['price'] = $list[$val['id']]['sum_revenue']
                            /$list[$val['id']]['sum_clicks'];
                    } else {
                        $list[$val['id']]['price'] = 0;
                    }
                }
            } else {
                $list[$val['id']] = $val;
            }
            $list[$val['id']]['data'][$val['time']] = array('sum_views' => $val['sum_views'] ,
                'sum_clicks' => $val['sum_clicks'] , 'sum_revenue' => $val['sum_revenue'],
                'time' => $val['time'], 'price' => $val['price']);

        }
        //补全数据为零的天数
        for ($i = 0; $i < 30; $i++) {
            foreach ($list as $k => $value) {
                if (!isset($list[$k]['data'][$start])) {
                    $list[$k]['data'] = array_add($list[$k]['data'], $start, array('sum_clicks'=>0,
                        'sum_revenue'=> 0, 'sum_view'=> 0,'time' => $start,'price' => 0));
                } ;
                $list[$k]['data'][$start]['sum_revenue'] =
                    Formatter::asDecimal($list[$k]['data'][$start]['sum_revenue']);
            }
            $start=date("Y-m-d", strtotime('+1 day', strtotime($start)));
        }
        foreach ($list as $ke => &$va) {
            $list[$ke]['sum_revenue'] = Formatter::asDecimal($list[$ke]['sum_revenue']);
            $list[$ke]['data']=array_values($va['data']);
        }
        //对消耗进行排序
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $revenue[$k] = $v['sum_revenue'];
            }
            array_multisort($revenue, SORT_DESC, $list);
        }
        $list= array_values($list);
        return $list;
    }

    /**
     * 获取adx报表数据-adx部分
     * @param $period_start
     * @param $period_end
     * @param $affiliateId
     * @param $axis
     * @return mixed
     */
    public static function findAdxData($period_start, $period_end, $affiliateId, $axis)
    {
        $prefix = DB::getTablePrefix();
        $query = DB::table('data_summary_adx_daily as d')
            ->join('affiliates as aff', 'd.affiliateid', '=', 'aff.affiliateid')
            ->select(
                'd.external_zone_id',
                'external_zone_id',
                'd.affiliateid',
                'aff.brief_name'
            )
            ->addselect(DB::raw('IFNULL(SUM(' .$prefix . 'd.`impressions`),0) as `impressions`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'd.`af_income`),0) as `af_income`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'd.`bid_number`),0) as `bid_number`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'd.`win_number`),0) as `win_number`'))
            ->addSelect(DB::raw('IFNULL(SUM(' . $prefix . 'd.`clicks`),0) as `clicks`'))
            ->whereBetween('d.date', [$period_start, $period_end])
            ->where('aff.agencyid', Auth::user()->agencyid)
            ->groupBy('d.external_zone_id', 'd.affiliateid');
        if ($affiliateId > 0) {
            $query->where('d.affiliateid', $affiliateId);
        }
        switch ($axis) {
            case 'hours':
                $query->addSelect(DB::raw("DATE_FORMAT({$prefix}d.`date`,'%Y-%m-%d 00:00:00') AS time"))
                    ->groupBy('time');
                break;
            case 'days':
                $query->addSelect(DB::raw("DATE_FORMAT({$prefix}d.`date`,'%Y-%m-%d') AS time"))
                    ->groupBy('time');
                break;
        }
        $res = $query->get();
        return json_decode(json_encode($res), true);
    }

    /**
     * @param $period_start
     * @param $period_end
     * @param $affiliateId
     * @param $axis
     * @param $zoneOffset
     * @return mixed
     */
    public static function findClientData($period_start, $period_end, $affiliateId, $axis, $zoneOffset)
    {
        $prefix = DB::getTablePrefix();
        if ($axis == 'hours') {
            $dataTable = 'data_summary_ad_hourly';
        } else {
            $dataTable = 'data_hourly_daily';
        }
        $query = DB::table($dataTable)
            ->join('banners as b', 'b.bannerid', '=', "{$dataTable}.ad_id")
            ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->join('affiliates as aff', 'b.affiliateid', '=', 'aff.affiliateid')
            ->join('appinfos as app', function ($join) {
                $join->on('c.campaignname', '=', 'app.app_id')
                    ->on('c.platform', '=', 'app.platform')
                    ->on('aff.agencyid', '=', 'app.media_id');
            })
            ->select(
                'app.app_name',
                'c.revenue_type as client_revenue_type',
                'b.revenue_type as media_revenue_type',
                'c.campaignid',
                'b.bannerid',
                'b.affiliateid',
                'aff.brief_name',
                DB::raw('IFNULL(SUM(' . $prefix . $dataTable . '.`impressions`),0) as `sum_views`'),
                DB::raw('IFNULL(SUM(' . $prefix . $dataTable . '.`win_count`),0) as `w_number`'),
                DB::raw('IFNULL(SUM(' . $prefix . $dataTable . '.`clicks`),0) as `sum_cpc_clicks`'),
                DB::raw('IFNULL(SUM(' . $prefix . $dataTable . '.`file_down`),0) as `sum_download_complete`'),
                DB::raw('IFNULL(SUM(' . $prefix . $dataTable . '.`cpa`),0) as `sum_cpa`'),
                DB::raw('IFNULL(SUM(' . $prefix . $dataTable . '.`total_revenue`),0) as `sum_revenue`')
            )
            ->where('aff.agencyid', Auth::user()->agencyid)
            ->where('aff.affiliate_type', Affiliate::TYPE_ADX)
            ->groupBy("{$dataTable}.ad_id", 'b.affiliateid');
        if ($affiliateId > 0) {
            $query->where('b.affiliateid', $affiliateId);
        }
        if (0 > $zoneOffset) {
            $dateQuery = "DATE_ADD";
        } else {
            $dateQuery = "DATE_SUB";
        }
        switch ($axis) {
            case 'hours':
                $period_start = date('Y-m-d H:i:s', strtotime($period_start . ' 00:00:00') +
                    $zoneOffset * 3600);
                $period_end = date('Y-m-d H:i:s', strtotime($period_end . ' 23:59:59') +
                    $zoneOffset * 3600);
                $zoneOffset = abs($zoneOffset);
                $query->addSelect(DB::raw("DATE_FORMAT({$dateQuery}({$prefix}{$dataTable}.`date_time`,
                    INTERVAL {$zoneOffset} HOUR),'%Y-%m-%d %H:00:00') AS time"))
                    ->whereBetween("$dataTable.date_time", [$period_start, $period_end])
                    ->groupBy('time');
                break;
            case 'days':
                $query->addSelect(DB::raw("DATE_FORMAT({$prefix}{$dataTable} .`date`,'%Y-%m-%d') AS time"))
                    ->whereBetween("$dataTable.date", [$period_start, $period_end])
                    ->groupBy('time');
                break;
        }
        $res = $query->get();
        return json_decode(json_encode($res), true);
    }

    /**
     * @param $start
     * @param $end
     * @param $zoneOffset
     * @return array
     */
    public static function dateConversion($start, $end, $zoneOffset = -8)
    {
        $axis = self::AXIS_HOURS;
        $start = date('Y-m-d H:i:s', strtotime($start . ' 00:00:00') + $zoneOffset * 3600);
        $end = date('Y-m-d H:i:s', strtotime($end . ' 23:59:59') + $zoneOffset * 3600);
        return [$start, $end, $axis];
    }
    /*
     * $start, $end, $clientId, $campaignId, $affiliateId, $client_revenue_type, $af_revenue_type
     */
    public static function findManagerGameData($param)
    {
        $agency = Auth::user()->agencyid;
        $prefix = DB::getTablePrefix();
        $table = 'data_hourly_daily';
        $query = DB::table("{$table} as h")
            ->join('campaigns as c', 'h.campaign_id', '=', 'c.campaignid')
            ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
            ->where('c.delivery_type', 2)
            ->where('h.affiliateid', '>', 0)
            ->where('cli.agencyid', $agency)
            ->whereBetween('h.date', [$param['period_start'], $param['period_end']])
        ;
        $groupBy = ['h.date','h.campaign_id', 'h.affiliateid'];
        foreach ($groupBy as $val) {
            $query->groupBy($val);
        }
        $selectInfo =[
            'c.campaignid',
            'c.campaignname',
            'h.affiliateid',
            'h.date',
            'cli.clientname',
            'cli.clientid',
            'cli.brief_name as client_brief_name',
            'h.game_af_revenue_type',
            'h.game_client_revenue_type',
            'h.game_client_price',
            'h.game_af_price',
            DB::raw('IFNULL(SUM(' . $prefix . 'h.`game_client_usernum`),0) as `game_client_usernum`'),
            DB::raw('IFNULL(SUM(' . $prefix . 'h.`game_charge`),0) as `game_charge`'),
            DB::raw('IFNULL(SUM(' . $prefix . 'h.`game_client_amount`),0) as `game_client_amount`'),
            DB::raw('IFNULL(SUM(' . $prefix . 'h.`game_af_usernum`),0) as `game_af_usernum`'),
            DB::raw('IFNULL(SUM(' . $prefix . 'h.`game_af_amount`),0) as `game_af_amount`')
        ];
        $query->addSelect($selectInfo);
        if (isset($param['clientid']) && $param['clientid']) {
            $clientId = explode(",", $param['clientid']);
            $query->whereIn('cli.clientid', $clientId);
        }
        if (isset($param['campaignid']) && $param['campaignid']) {
            $campaignId = explode(",", $param['campaignid']);
            $query->whereIn('h.campaign_id', $campaignId);

        }
        if (isset($param['affiliateid']) && $param['affiliateid']) {
            $affiliateId = explode(",", $param['affiliateid']);
            $query->whereIn('h.affiliateid', $affiliateId);
        }
        if (isset($param['client_revenue_type']) && $param['client_revenue_type']) {
            $query->where('h.game_client_revenue_type', $param['client_revenue_type']);
        }
        if (isset($param['af_revenue_type']) && $param['af_revenue_type']) {
            $query->where('h.game_af_revenue_type', $param['af_revenue_type']);
        }
        $res = $query->get();//var_dump($query->toSql());exit;

        return json_decode(json_encode($res), true);
    }

    /**
     * 计算比例
     * @param $current
     * @param $before
     * @return float|int|string
     */
    public static function loopRate($current, $before)
    {
        if ($current > 0) {
            $rate = $before > 0 ? ($current - $before)/ $before : 100;
            $rate = Formatter::asDecimal($rate * 100);
        } else {
            $rate = 0;
        }
        return $rate;
    }
}
