<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Campaign;
use App\Models\Zone;
use App\Models\ManualDeliveryData;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use App\Components\Config;
use App\Models\Affiliate;
use App\Models\Banner;
use App\Models\Product;
use App\Components\Helper\LogHelper;
use App\Models\ManualClientData;
use App\Models\Account;
use App\Models\OperationClient;

class ManualService
{
    /**
     * 导入人工投放数据
     */
    public static function import($count, $dataType, $importExcelData)
    {
        try {
            //检查EXCEL数据是否正确
            $warnings = self::validateImportData($count, $dataType, $importExcelData);
            //有错误，提示
            if (count($warnings) > 0) {
                return ['errorCode' => 1, 'message' => $warnings];
            }

            //保存EXCEL数据
            DB::beginTransaction();
            $tips = [];
            for ($i = 1; $i < $count; $i++) {
                //获取导入的数据
                $manualExcelData = self::getManualExcelData($dataType, $importExcelData, $i);
                //如果没有使用媒体商数据，如A-AD，不用检查媒体商信息
                if (!in_array($dataType, Config::get('biddingos.manualWithoutCheckAffiliate'))) {
                    /*如果是D2D，C2C，T2T则使用Android*/
                    /*否则根据广告平台自动获取android或者 iphone正版*/
                    if (in_array($dataType, Config::get('biddingos.manualAffiliate'))) {
                        $platForm = Campaign::PLATFORM_ANDROID;
                    } else {
                        //根据媒体商名称获取可用的Android,iPhone正版流量广告位
                        $campaignInfo = self::getCampaignRow(
                            $manualExcelData['campaignName'],
                            $manualExcelData['clientId']
                        );
                        if (empty($campaignInfo)) {
                            return ['errorCode' => 5257, 'message' => self::formatWaring(5257, $k, $i)];
                        }
                    
                        $platForm = (Campaign::PLATFORM_ANDROID == $campaignInfo['platform']) ?
                        Campaign::PLATFORM_ANDROID : Campaign::PLATFORM_IPHONE_COPYRIGHT;
                    }
                    $flowZoneId = self::getZoneId($manualExcelData['affiliateName'], $platForm);
                    //获取投放到 manual_deliveryData表的投放数据，如媒体id,bannerid,campaignid
                    $deliverData = self::getDeliveryDataToInsert(
                        $manualExcelData['campaignName'],
                        $manualExcelData['clientId'],
                        $manualExcelData['affiliateName'],
                        $flowZoneId
                    );
                    
                    //如果有符合条件的数据
                    if (count($deliverData) > 0) {
                        //如果已经存在数据，且已经执行过(flag=1),直接跳过
                        $result = ManualDeliveryData::whereMulti([
                            'date' => $manualExcelData['date'],
                            'affiliate_id' => $deliverData['affiliateid'],
                            'zone_id' => $deliverData['zoneid'],
                            'banner_id' => $deliverData['bannerid'],
                            'campaign_id' => $deliverData['campaignid'],
                            'flag' => ManualDeliveryData::FLAG_ASSIGNED
                        ])
                        ->count();
                        if ($result > 0) {
                            $tips[] = self::formatWaring(5251, $i, $i);
                            LogHelper::info(self::formatWaring(5251, $i, $i));
                            continue;
                        }
        
                        $manualInsertData = [
                            'data_type' => $dataType,
                            'date' => date('Y-m-d', strtotime($manualExcelData['date'])),
                            'affiliate_id' => $deliverData['affiliateid'],
                            'zone_id' => $deliverData['zoneid'],
                            'banner_id' => $deliverData['bannerid'],
                            'campaign_id' => $deliverData['campaignid'],
                            'views' => $manualExcelData['impressions'],
                            'conversions' => $manualExcelData['conversions'],
                            'revenues' => $manualExcelData['revenues'],
                            'expense' => $manualExcelData['payment'], //支出
                            'cpa' => $manualExcelData['cpa'], //CPA量
                            'flag' => ManualDeliveryData::FLAG_UNTREATED,
                            'update_time' => date('Y-m-d H:i:s'),
                            'clicks' => $manualExcelData['clicks'],
                            'is_manual' => ManualDeliveryData::MANUAL_YES,
                        ];
                        //保存数据
                        $checkResult = ManualDeliveryData::store($manualInsertData);
                        if (!$checkResult) {
                            $tips[] = self::formatWaring(5250, $i, $i);
                            LogHelper::info(self::formatWaring(5250, $i, $i));
                        }
                    } else {
                        //2016-07-08，如果查找不到媒体，广告的信息
                        $tips[] = self::formatWaring(5249, $i, $i);
                        LogHelper::info(self::formatWaring(5249, $i, $i));
                    }
                } else {
                    //导入A-AD数据
                    $result = ManualDeliveryData::whereMulti([
                        'data_type' => $dataType,
                        'date' => date('Y-m-d', strtotime($manualExcelData['date'])),
                        'campaign_id' => self::getCampaignId(
                            trim($manualExcelData['campaignName']),
                            trim($manualExcelData['clientId'])
                        ),
                        'flag' => ManualDeliveryData::FLAG_ASSIGNED
                    ])
                    ->count();
                    if ($result > 0) {
                        $tips[] = self::formatWaring(5251, $i, $i);
                        LogHelper::info(self::formatWaring(5251, $i, $i));
                        continue;
                    }
                    $manualInsertData = [
                        'data_type' => $dataType,
                        'date' => date('Y-m-d', strtotime($manualExcelData['date'])),
                        'campaign_id' => self::getCampaignId(
                            trim($manualExcelData['campaignName']),
                            trim($manualExcelData['clientId'])
                        ),
                        'views' => $manualExcelData['impressions'],
                        'conversions' => $manualExcelData['conversions'],
                        'revenues' => $manualExcelData['revenues'],
                        'expense' => $manualExcelData['payment'], //支出
                        'cpa' => $manualExcelData['cpa'], //CPA量
                        'flag' => ManualDeliveryData::FLAG_UNTREATED,
                        'update_time' => date('Y-m-d H:i:s'),
                        'clicks' => $manualExcelData['clicks'],
                        'is_manual' => ManualDeliveryData::MANUAL_YES,
                    ];
                    //保存数据
                    $checkResult = ManualDeliveryData::store($manualInsertData);
                    if (!$checkResult) {
                        $tips[] = self::formatWaring(5250, $i, $i);
                        LogHelper::info(self::formatWaring(5250, $i, $i));
                    }
                }
            }//end for
        } catch (\Exception $e) {
            DB::rollBack();
            $error = Config::get('error');
            return ['errorCode' => 5005, 'message' => $error[5005]];
        }
        
        if (0 == count($tips)) {
            DB::commit();
            return ['errorCode' => 0, 'message' => ''];
        } else {
            return ['errorCode' => 1, 'message' => $tips];
        }
    }
    
    /**
     * 获取媒体excel
     * @param $importExcelData
     * @param $i
     * @return array
     */
    private static function getManualExcelData($dataType, $importExcelData, $i)
    {
        switch ($dataType) {
            //调用A2A的数转换关系
            case 'A2A':
                $excelData = self::getA2A($importExcelData, $i);
                break;
            case 'A-AD':
                $excelData = self::getA2DForAd($importExcelData, $i);
                break;
            case 'A2D-AF':
                $excelData = self::getA2DForAf($importExcelData, $i);
                break;
            case 'A2C-AF':
                $excelData = self::getA2CForAf($importExcelData, $i);
                break;
            case 'T2T':
                $excelData = self::getT2T($importExcelData, $i);
                break;
            case 'D2D':
                $excelData = self::getD2D($importExcelData, $i);
                break;
            case 'C2C':
                $excelData = self::getC2C($importExcelData, $i);
                break;
            case 'S2S':
                $excelData = self::getS2S($importExcelData, $i);
                break;
            default:
                $excelData = self::getDefault($importExcelData, $i);
                break;
        }
        return $excelData;
    }
    
    /**
     * 获取CPS表格的数据
     *
     */
    private static function getS2S($importExcelData, $i)
    {
        if (Account::TYPE_MANAGER == Auth::user()->account->account_type) {
            return [
                'clientId'=>trim($importExcelData[$i][0]),
                'campaignName' => trim($importExcelData[$i][1]),
                'affiliateName' => trim($importExcelData[$i][2]),
                'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][3]))),
                'channel' => '',
                'clicks' => 0,
                'conversions' => 0,
                'conSum' => $importExcelData[$i][4], //广告主消耗
                'payment' => $importExcelData[$i][5], //支出
                'revenues' => $importExcelData[$i][4], //广告收入
                'cpa' => 0, //CPA量
                'impressions' => 0 //展示
            ];
        } else {
            return [
                'clientId'=>trim($importExcelData[$i][0]),
                'campaignName' => trim($importExcelData[$i][1]),
                'affiliateName' => Auth::user()->account->affiliate->name,
                'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][2]))),
                'channel' => '',
                'clicks' => 0,
                'conversions' => 0,
                'conSum' => trim($importExcelData[$i][3]), //广告主消耗
                'payment' => trim($importExcelData[$i][3]), //支出
                'revenues' => trim($importExcelData[$i][3]), //广告收入
                'cpa' => 0,
                'impressions' => 0 //展示
            ];
        }
    }
    
    /**
     * 获取CPA表格的数据
     *
     */
    private static function getA2A($importExcelData, $i)
    {
        if (Account::TYPE_MANAGER == Auth::user()->account->account_type) {
            return [
                'clientId'=>trim($importExcelData[$i][0]),
                'campaignName' => trim($importExcelData[$i][1]),
                'affiliateName' => trim($importExcelData[$i][2]),
                'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][3]))),
                'channel' => '',
                'clicks' => 0,
                'conversions' => 0,
                'conSum' => $importExcelData[$i][5], //广告主消耗
                'payment' => $importExcelData[$i][6], //支出
                'revenues' => $importExcelData[$i][5], //广告收入
                'cpa' => $importExcelData[$i][4], //CPA量
                'impressions' => 0 //展示
            ];
        } else {
            return [
                'clientId'=>trim($importExcelData[$i][0]),
                'campaignName' => trim($importExcelData[$i][1]),
                'affiliateName' => Auth::user()->account->affiliate->name,
                'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][2]))),
                'channel' => '',
                'clicks' => 0,
                'conversions' => 0,
                'conSum' => trim($importExcelData[$i][3]),//广告主消耗
                'payment' => trim($importExcelData[$i][3]), //支出
                'revenues' => trim($importExcelData[$i][3]),//广告收入
                'cpa' => trim($importExcelData[$i][4]),//CPA量
                'impressions' => 0 //展示
            ];
        }
    }
    
    /**
     * 获取A-AD广告主表格的数据
     */
    private static function getA2DForAd($importExcelData, $i)
    {
        return [
            'clientId'=>trim($importExcelData[$i][0]),
            'campaignName' => trim($importExcelData[$i][1]),
            'affiliateName' => '',
            'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][2]))),
            'channel' => '',
            'clicks' => 0,
            'conversions' => 0,
            'conSum' => $importExcelData[$i][4], //广告主消耗
            'payment' => 0, //支出
            'revenues' => $importExcelData[$i][4], //广告收入
            'cpa' => $importExcelData[$i][3], //CPA量
            'impressions' => 0 //展示
        ];
    }
    
    
    /**
     * 获取A2D-AF媒体商表格的数据
     */
    private static function getA2DForAf($importExcelData, $i)
    {
        return [
            'clientId'=>trim($importExcelData[$i][0]),
            'campaignName' => trim($importExcelData[$i][1]),
            'affiliateName' => trim($importExcelData[$i][2]),
            'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][3]))),
            'channel' => '',
            'clicks' => 0,
            'conversions' => trim($importExcelData[$i][4]),
            'conSum' => 0, //广告主消耗
            'payment' => $importExcelData[$i][5], //支出
            'revenues' => 0, //广告收入
            'cpa' => 0, //CPA量
            'impressions' => 0 //展示
        ];
    }
    
    /**
     * 获取A2C-AF媒体商表格的数据，媒体商的C数据
     */
    private static function getA2CForAf($importExcelData, $i)
    {
        return [
            'clientId'=>trim($importExcelData[$i][0]),
            'campaignName' => trim($importExcelData[$i][1]),
            'affiliateName' => trim($importExcelData[$i][2]),
            'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][3]))),
            'channel' => '',
            'clicks' => trim($importExcelData[$i][4]),
            'conversions' => 0,
            'conSum' => 0, //广告主消耗
            'payment' => $importExcelData[$i][5], //支出
            'revenues' => 0, //广告收入
            'cpa' => 0, //CPA量
            'impressions' => 0 //展示
        ];
    }
    
    /**
     * 获取T2T表格的数据
     */
    private static function getT2T($importExcelData, $i)
    {
        return [
            'clientId'=>trim($importExcelData[$i][0]),
            'campaignName' => trim($importExcelData[$i][1]),
            'affiliateName' => trim($importExcelData[$i][2]),
            'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][3]))),
            'channel' => '',
            'clicks' => 0,
            'conversions' => 0,
            'conSum' => $importExcelData[$i][4], //广告主消耗
            'payment' => $importExcelData[$i][5], //支出
            'revenues' => $importExcelData[$i][4], //广告收入
            'cpa' => 0, //CPA量
            'impressions' => 0 //展示
        ];
    }
    
    /**
     * 获取D2D表格的数据
     */
    private static function getD2D($importExcelData, $i)
    {
        return [
            'clientId'=>trim($importExcelData[$i][0]),
            'campaignName' => trim($importExcelData[$i][1]),
            'affiliateName' => trim($importExcelData[$i][2]),
            'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][3]))),
            'channel' => '',
            'clicks' => 0,
            'conversions' => $importExcelData[$i][4],
            'conSum' => $importExcelData[$i][5], //广告主消耗
            'payment' => $importExcelData[$i][6], //支出
            'revenues' => $importExcelData[$i][5], //广告收入
            'cpa' => 0, //CPA量
            'impressions' => $importExcelData[$i][4]*mt_rand(1000, 3000) //展示
        ];
    }
    
    /**
     * 获取C2C表格的数据
     */
    private static function getC2C($importExcelData, $i)
    {
        return [
            'clientId'=>trim($importExcelData[$i][0]),
            'campaignName' => trim($importExcelData[$i][1]),
            'affiliateName' => trim($importExcelData[$i][2]),
            'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][3]))),
            'channel' => '',
            'clicks' => trim($importExcelData[$i][4]),
            'conversions' => 0,
            'conSum' => $importExcelData[$i][5], //广告主消耗
            'payment' => $importExcelData[$i][6], //媒体支出
            'revenues' => $importExcelData[$i][5], //广告收入
            'cpa' => 0, //CPA量
            'impressions' => $importExcelData[$i][4]*mt_rand(1000, 3000) //展示
        ];
    }
    
    /**
     *
     * 默认获取的数据，旧的表
     */
    private static function getDefault($importExcelData, $i)
    {
        $clicks = $importExcelData[$i][6];
        $conversions = $importExcelData[$i][7];
        $impressions = 0;
        //判断下载量是否大于0
        if ($conversions > 0) {
            $impressions = $conversions * mt_rand(1000, 3000);
        }
        //判断点击量是否大于0
        if ($clicks > 0) {
            $impressions = $clicks * mt_rand(1000, 3000);
        }
    
        return [
            'clientId'=>trim($importExcelData[$i][0]),
            'campaignName' => trim($importExcelData[$i][1]),
            'date' => date('Y-m-d', strtotime(trim($importExcelData[$i][2]))),
            'affiliateName' => trim($importExcelData[$i][3]),
            'zoneId' => $importExcelData[$i][4],
            'channel' => trim($importExcelData[$i][5]),
            'clicks' => $clicks,
            'conversions' => $conversions,
            'payment' => $importExcelData[$i][8], //支出
            'cpa' => $importExcelData[$i][9], //CPA量
            'conSum' => $importExcelData[$i][10], //广告主消耗
            'impressions' => $impressions, //展示
            'revenues' => $importExcelData[$i][10]
        ];
    }
    
    /**
     *
     * @param string $affiliateName
     * @return boolean $zoneId | false
     */
    private static function getZoneId($affiliateName, $platFrom = Campaign::PLATFORM_ANDROID)
    {
        if (empty($affiliateName)) {
            return false;
        }
        $zoneId = DB::table('zones')
                ->leftJoin('affiliates', 'zones.affiliateid', '=', 'affiliates.affiliateid')
                ->where('affiliates.name', $affiliateName)
                ->where('platform', $platFrom) //根据广告的平台获取相应平台的广告位
                ->where('zonetype', Zone::TYPE_FLOW) //流量广告位
                ->where('zones.type', Zone::TYPE_FLOW) //流量广告位
                ->where('affiliates.agencyid', Auth::user()->agencyid)
                ->pluck('zoneid');
        if (!empty($zoneId)) {
            return $zoneId;
        }
        return false;
    }
    
    
    /**
     * 获取要插入到人工投放表(up_manual_deliverydata)的数据
     * @param $campaignName
     * @param $clientId
     * @param $affiliateName
     * @param $zoneId
     * @return mixed
     */
    private static function getDeliveryDataToInsert($campaignName, $clientId, $affiliateName, $zoneId)
    {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $affiliates = DB::table('affiliates AS a')
                    ->join('zones AS z', 'a.affiliateid', '=', 'z.affiliateid')
                    ->join('banners AS b', 'b.affiliateid', '=', 'a.affiliateid')
                    ->join('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
                    ->join('appinfos AS app', 'app.app_id', '=', 'c.campaignname')
                    ->leftJoin('clients AS adv', 'adv.clientid', '=', 'c.clientid')
                    ->select(
                        'a.name',
                        'a.affiliateid',
                        'z.zoneid',
                        'z.zonename',
                        'b.bannerid',
                        'b.campaignid',
                        'c.campaignname',
                        'app.app_name'
                    )
                    ->whereIn('b.status', [Banner::STATUS_PUT_IN, Banner::STATUS_SUSPENDED])
                    ->where('a.name', $affiliateName)
                    ->where('a.agencyid', Auth::user()->agencyid)
                    ->where('app.app_name', $campaignName)
                    ->where('z.zoneid', $zoneId)
                    ->where('adv.clientid', $clientId)
                    ->first();
        return $affiliates;
    }
    
    /**
     * 格式化导入提示信息
     * @param $code
     * @param $k
     * @param $i
     * @return string
     */
    public static function formatWaring($code, $k, $i, $date = null)
    {
        $msg = Config::get('error');
        if (empty($date)) {
            return sprintf($msg[$code], $k, $i);
        } else {
            return sprintf($msg[$code], $k, $i, $date);
        }
    }
    
    
    /**
     * 验证导入的数据是否正确
     * @param integer $count
     * @param string $dataType
     * @param array $importExcelData
     * @return multitype:string
     */
    private static function validateImportData($count, $dataType, $importExcelData)
    {
        $k = 1;
        $warnings = [];
        for ($i = 1; $i < $count; $i++) {
            //读取excel表格中的数据
            $manualExcelData = self::getManualExcelData($dataType, $importExcelData, $i);
            //验证广告名称是否为空
            if (empty($manualExcelData['campaignName'])) {
                $warnings[] = self::formatWaring(5243, $k, $i);
                $k++;
            }

            //验证广告主名称是否为空
            if (empty($manualExcelData['clientId'])) {
                $warnings[] = self::formatWaring(5243, $k, $i);
                $k++;
            }

            //检测广告是否符合要求
            $checkType = self::checkCampaignRevenueType(
                $dataType,
                $manualExcelData['campaignName'],
                $manualExcelData['clientId']
            );

            if (false == $checkType) {
                $revenueInfo = self::revenueType($dataType);
                $msg = Config::get('error');
                $errMsg = $msg[5248];
                $warnings[] = sprintf(
                    $errMsg,
                    $k,
                    $i,
                    $revenueInfo
                );
                $k++;
            }

            //如果导入的是A2A，A2C-AF，A2D-AF，CPA的，则检查媒体是否匹配
            if (in_array($dataType, Config::get('biddingos.manualCPACampaignAffiliate'))) {
                $checkResult = self::checkAffiliateCampaignReveuneType(
                    [
                        'dataType' => $dataType,
                        'campaignName' => trim($manualExcelData['campaignName']),
                        'clientId' => trim($manualExcelData['clientId']),
                        'affiliateName' => trim($manualExcelData['affiliateName'])
                    ]
                );

                if (false == $checkResult) {
                    $revenueTypeInfo = self::affiliateRevenueType($dataType);
                    $msg = Config::get('error');
                    $errMsg = $msg[5253];
                    $warnings[] = sprintf(
                        $errMsg,
                        $k,
                        $i,
                        $revenueTypeInfo
                    );
                    $k++;
                }
            }

            //验证日期是否为空
            if (empty($manualExcelData['date'])) {
                $warnings[] = self::formatWaring(5243, $k, $i);
                $k++;
            }
    
            //验证广告主是否存在
            if (!self::validateClient($manualExcelData['clientId'])) {
                $warnings[] = self::formatWaring(5224, $k, $i);
                $k++;
            }
    
            //验证广告主，广告名称是否匹配
            if (!self::validateClientAssocCampaign(
                $manualExcelData['clientId'],
                $manualExcelData['campaignName']
            )
            ) {
                $warnings[] = self::formatWaring(5225, $k, $i);
                $k++;
            }
    
            //如果是媒体商导入，验证广告主是否为自营媒体自己的广告主，
            //如果不是不允许导入
            if (Account::TYPE_TRAFFICKER == Auth::user()->account->account_type) {
                if (!self::validateClientCampaign(
                    $manualExcelData['clientId'],
                    $manualExcelData['campaignName']
                )
                ) {
                    $warnings[] = self::formatWaring(5256, $k, $i);
                    $k++;
                }
            }

            //检查日期时间是否正确
            if (!self::validateDate(date('Y-m-d', strtotime($manualExcelData['date'])), $dataType)) {
                $warnings[] = self::formatWaring(5226, $k, $i);
                $k++;
            }
    
            //除了广告主A-AD的数据，其它都要检查媒体商
            if (!in_array($dataType, Config::get('biddingos.manualWithoutCheckAffiliate'))) {
                //检查媒体是否存在
                if (!empty($manualExcelData['affiliateName'])) {
                    //检查媒体商是否存在
                    if (!self::validateAffiliate($manualExcelData['affiliateName'])) {
                        $warnings[] = self::formatWaring(5227, $k, $i);
                        $k++;
                    }
                } else {
                    $warnings[] = self::formatWaring(5243, $k, $i);
                    $k++;
                }
    
                //如果是D2D,C2C,T2T,S2S等检查是否为人工投放的媒体
                if (in_array($dataType, Config::get('biddingos.manualAffiliate'))) {
                    if (!self::validateAffiliateMode($manualExcelData['affiliateName'])) {
                        $warnings[] = self::formatWaring(5228, $k, $i);
                        $k++;
                    }
                }
    
                //获取媒体商的流量广告位
                $campaignInfo = self::getCampaignRow(
                    $manualExcelData['campaignName'],
                    $manualExcelData['clientId']
                );
                if (empty($campaignInfo)) {
                    $warnings[] = self::formatWaring(5257, $k, $i);
                    $k++;
                } else {
                    //如果是D2D，C2C，T2T则使用Android，否则根据广告平台自动获取
                    //android或者 iphone正版
                    if (in_array($dataType, Config::get('biddingos.manualAffiliate'))) {
                        $platForm = Campaign::PLATFORM_ANDROID;
                    } else {
                        //根据媒体商名称获取可用的Android,iPhone正版流量广告位
                        $platForm = (Campaign::PLATFORM_ANDROID == $campaignInfo['platform']) ?
                        Campaign::PLATFORM_ANDROID : Campaign::PLATFORM_IPHONE_COPYRIGHT;
                    }
                    $manualExcelData['zoneId'] = self::getZoneId($manualExcelData['affiliateName'], $platForm);
                    if (false == $manualExcelData['zoneId']) {
                        $warnings[] = self::formatWaring(5229, $k, $i);
                        $k++;
                    }
                    
                    //判断广告跟媒体是否建立投放关系
                    if (!self::validateCampaignDeliveryAffiliate(
                        $manualExcelData['campaignName'],
                        $manualExcelData['clientId'],
                        $manualExcelData['affiliateName'],
                        $manualExcelData['zoneId']
                    )
                    ) {
                        $warnings[] = self::formatWaring(5232, $k, $i);
                        $k++;
                    }
                }
            }
    
    
            //如果是D2D，验证输入的广告消耗是否大于下载量*广告主历史出价
            $campaignId = self::getCampaignId(
                trim($manualExcelData['campaignName']),
                trim($manualExcelData['clientId'])
            );
            if (0 < $campaignId) {
                $history = self::getCampaignHistoryPrice(
                    $campaignId,
                    date('Y-m-d', strtotime($manualExcelData['date']))
                );
            } else {
                $history = [];
            }
    
            //如果是D2D，C2C计算价格*下载（点击）不能大于总消耗
            if (empty($history)) {
                $warnings[] = self::formatWaring(5247, $k, $i);
                $k++;
            } else {
                $historyPrice = $history['current_revenue'];
                if (Config::get('biddingos.manualD2D') == $dataType) {
                    $useRevenues = sprintf("%.2f", round($historyPrice * $manualExcelData['conversions'], 2));
                    if ($useRevenues < $manualExcelData['conSum']) {
                        $warnings[] = self::formatWaring(5244, $k, $i, $useRevenues);
                        $k++;
                    }
                }
    
                //如果是C2C，验证输入的广告消耗是否大于点击量*广告主历史出价
                if (Config::get('biddingos.manualC2C') == $dataType) {
                    $useRevenues = sprintf("%.2f", round($historyPrice * $manualExcelData['clicks'], 2));
                    if ($useRevenues < $manualExcelData['conSum']) {
                        $warnings[] = self::formatWaring(5245, $k, $i, $useRevenues);
                        $k++;
                    }
                }
            }
    
            //验证数据格式是否正确
            if (!self::validateDeliveryData(
                $manualExcelData['impressions'],
                $manualExcelData['clicks'],
                $manualExcelData['conversions'],
                $manualExcelData['revenues'],
                $manualExcelData['payment'],
                $manualExcelData['cpa'],
                $manualExcelData['conSum']
            )
            ) {
                $warnings[] = self::formatWaring(5233, $k, $i);
                $k++;
            }
    
            $productTypeInfo = self::getProductType(
                $manualExcelData['campaignName'],
                $manualExcelData['clientId']
            );
    
            if (in_array($productTypeInfo, [Product::TYPE_LINK, Product::TYPE_APP_DOWNLOAD])) {
                //如果是连接推广的，下载数不能大于0
                if ($productTypeInfo == Product::TYPE_LINK && $manualExcelData['conversions'] > 0) {
                    $warnings[] = self::formatWaring(5234, $k, $i);
                    $k++;
                }
    
                //如果是应用市场，点击数不能大于0
                if ($productTypeInfo == Product::TYPE_APP_DOWNLOAD) {
                    //如果不是CPA的计费方式，点击数不允许大于0
                    $campaignRow = self::getCampaignInfo(
                        $manualExcelData['campaignName'],
                        $manualExcelData['clientId']
                    );
    
                    if (!empty($campaignRow)) {
                        if (!in_array($campaignRow['revenue_type'], [Campaign::REVENUE_TYPE_CPA])) {
                            if ($manualExcelData['clicks'] > 0) {
                                $warnings[] = self::formatWaring(5235, $k, $i);
                                $k++;
                            }
                        }
                    } else {
                        $warnings[] = self::formatWaring(5225, $k, $i);
                        $k++;
                    }
                }
            } else {
                $warnings[] = self::formatWaring(5252, $k, $i);
                $k++;
            }
    
            //如果不是A-AD数据
            if (!in_array($dataType, Config::get('biddingos.manualWithoutCheckAffiliate'))) {
                if (!empty($manualExcelData['zoneId'])) {
                    //获取投放的媒体信息
                    $deliverData = self::getDeliveryDataToInsert(
                        $manualExcelData['campaignName'],
                        $manualExcelData['clientId'],
                        $manualExcelData['affiliateName'],
                        $manualExcelData['zoneId']
                    );
        
                    if (count($deliverData) > 0) {
                        $check = self::validateDateCampaign(
                            date('Y-m-d', strtotime($manualExcelData['date'])),
                            $deliverData['campaignid']
                        );
                        if (false == $check) {
                            $msg = Config::get('error');
                            $errMsg = $msg[5241];
                            $warnings[] = sprintf(
                                $errMsg,
                                $k,
                                $i,
                                date('Y-m-d', strtotime($manualExcelData['date'])),
                                $manualExcelData['campaignName']
                            );
                            $k++;
                        }
                    }
                } else {
                    $warnings[] = self::formatWaring(5236, $k, $i);
                    $k++;
                }
            } else {
                //检查是否已导入媒体商数据
                $manualExcelData['date'] = date('Y-m-d', strtotime($manualExcelData['date']));
                $campaignId = self::getCampaignId(
                    trim($manualExcelData['campaignName']),
                    trim($manualExcelData['clientId'])
                );
    
                //从 manual_deliverydata 表中获取A2D-AF或者A2C-AF已经发分的数据
                $affiliateDeliveryData = self::getAffiliateDeliveryData(
                    trim($manualExcelData['campaignName']),
                    $manualExcelData['date'],
                    trim($manualExcelData['clientId'])
                );
                if (0 == count($affiliateDeliveryData)) {
                    //检查hourly表中是否有投放记录
                    $hourlyData = self::getDeliveryLog(
                        $manualExcelData['date'],
                        $campaignId
                    );
                    if (0 == count($hourlyData)) {
                        $msg = Config::get('error');
                        $warnings[] = sprintf(
                            $msg[5242],
                            $k,
                            $i,
                            $manualExcelData['date'],
                            $manualExcelData['campaignName']
                        );
                        $k++;
                    }
                }
    
                //如果已经结算，则不能导入
                $check = self::validateDateCampaign(
                    $manualExcelData['date'],
                    $campaignId
                );
    
                if (false == $check) {
                    $msg = Config::get('error');
                    $errMsg = $msg[5241];
                    $warnings[] = sprintf(
                        $errMsg,
                        $k,
                        $i,
                        date('Y-m-d', strtotime($manualExcelData['date'])),
                        $manualExcelData['campaignName']
                    );
                    $k++;
                }//end if
            }//end else
        }// end for
        return $warnings;
    }
    
    /**
     *
     * @param string $dataType
     * @param string $campaignName
     * @param string $clientId
     * @return boolean
     */
    private static function checkCampaignRevenueType($dataType, $campaignName, $clientId)
    {
        if (!empty($campaignName)) {
            $revenue_type = DB::table('campaigns')
            ->leftJoin('appinfos', function ($join) {
                $join->on('appinfos.app_id', '=', 'campaigns.campaignname')
                ->on('appinfos.platform', '=', 'campaigns.platform');
            })
            ->leftJoin('clients', function ($leftJoin) {
                $leftJoin->on('clients.clientid', '=', 'campaigns.clientid');
            })
            ->where('appinfos.app_name', '=', $campaignName)
            ->where('clients.clientid', $clientId)
            ->pluck('campaigns.revenue_type');
            switch ($dataType) {
                case 'A2A':
                    if ($revenue_type == Campaign::REVENUE_TYPE_CPA) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'A-AD':
                    if ($revenue_type == Campaign::REVENUE_TYPE_CPA) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'A2D-AF':
                    if ($revenue_type == Campaign::REVENUE_TYPE_CPA) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'A2C-AF':
                    if ($revenue_type == Campaign::REVENUE_TYPE_CPA) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'C2C':
                    if ($revenue_type == Campaign::REVENUE_TYPE_CPC) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'D2D':
                    if ($revenue_type == Campaign::REVENUE_TYPE_CPD) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'T2T':
                    if ($revenue_type == Campaign::REVENUE_TYPE_CPT) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 'S2S':
                    if ($revenue_type == Campaign::REVENUE_TYPE_CPS) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                default:
                    return false;
                    break;
            }
        }
        return false;
    }
    
    /**
     * 媒体的计费类型
     * @param array $params
     * @return boolean
     */
    private static function checkAffiliateCampaignReveuneType($params)
    {
        $dataType = $params['dataType'];
        $campaignName = $params['campaignName'];
        $clientId = $params['clientId'];
        $affiliateName = $params['affiliateName'];
    
        $revenue_type = DB::table('banners')
        ->leftJoin('affiliates', function ($join) {
            $join->on('banners.affiliateid', '=', 'affiliates.affiliateid');
        })
        ->leftJoin('campaigns', function ($join) {
            $join->on('banners.campaignid', '=', 'campaigns.campaignid');
        })
        ->leftJoin('appinfos', function ($join) {
            $join->on('appinfos.app_id', '=', 'campaigns.campaignname')
            ->on('appinfos.platform', '=', 'campaigns.platform');
        })
        ->leftJoin('clients', function ($leftJoin) {
            $leftJoin->on('clients.clientid', '=', 'campaigns.clientid');
        })
        ->where('appinfos.app_name', '=', $campaignName)
        ->where('clients.clientid', $clientId)
        ->where('name', $affiliateName)
        ->pluck('banners.revenue_type');
    
        if (!empty($revenue_type)) {
            switch ($dataType) {
                case 'A2A':
                    return ($revenue_type == Campaign::REVENUE_TYPE_CPA);
                    break;
                case 'A2D-AF':
                    return ($revenue_type == Campaign::REVENUE_TYPE_CPD);
                    break;
                case 'A2C-AF':
                    return ($revenue_type == Campaign::REVENUE_TYPE_CPC);
                    break;
                case 'CPA':
                    return ($revenue_type == Campaign::REVENUE_TYPE_CPA);
                    break;
                default:
                    break;
            }
        }
        return false;
    }
    
    /**
     * 返回媒体的计费类型
     *
     */
    private static function affiliateRevenueType($dataType)
    {
        $revenueType = [
            'A2A' => 'CPA',
            'A2D-AF' => 'CPD',
            'A2C-AF' => 'CPC',
            'CPA' => 'CPA',
        ];
        return $revenueType[$dataType];
    }
    
    /**
     * 验证导入excel中的广告主是否创建
     * @param $clientId
     * @return bool
     */
    private static function validateClient($clientId)
    {
        if ($clientId == '') {
            return false;
        }
        $count = Client::where('clientid', $clientId)
                ->where('agencyid', Auth::user()->agencyid)
                ->count();
        return $count > 0 ? true : false;
    }
    
    
    /**
     * 返回当前的所有计费类型
     */
    private static function revenueType($dataType)
    {
        $revenueType = [
            'A2A' => 'CPA',
            'A-AD' => 'CPA',
            'A2D-AF' => 'CPA',
            'A2C-AF' => 'CPA',
            'C2C' => 'CPC',
            'D2D' => 'CPD',
            'T2T' => 'CPT',
            'S2S' => 'CPS',
        ];
        return $revenueType[$dataType];
    }
    
    /**
     * 验证导入excel中的广告主和广告名称是否对应
     * @param $clientId
     * @param $campaignName
     * @return bool
     */
    private static function validateClientAssocCampaign($clientId, $campaignName)
    {
        if ($clientId == '' || $campaignName == '') {
            return false;
        }
        $count = DB::table('campaigns')
            ->leftJoin('appinfos', 'appinfos.app_id', '=', 'campaigns.campaignname')
            ->leftJoin('clients', 'campaigns.clientid', '=', 'clients.clientid')
            ->where('clients.clientid', '=', $clientId)
            ->where('appinfos.app_name', '=', $campaignName)
            ->where('clients.agencyid', Auth::user()->agencyid)
            ->count();
        return $count > 0 ? true : false;
    }
    
    /**
     * 验证广告，广告主是否是自营的，自营的不允许导入
     * @param $clientId
     * @param $campaignName
     */
    private static function validateClientCampaign($clientId, $campaignName)
    {
        if ($clientId == '' || $campaignName == '') {
            return false;
        }
        $count = DB::table('campaigns')
                ->leftJoin('appinfos', 'appinfos.app_id', '=', 'campaigns.campaignname')
                ->leftJoin('clients', 'campaigns.clientid', '=', 'clients.clientid')
                ->where('clients.clientid', '=', $clientId)
                ->where('appinfos.app_name', '=', $campaignName)
                ->where('clients.agencyid', Auth::user()->agencyid)
                ->where('clients.affiliateid', Auth::user()->account->affiliate->affiliateid)
                ->count();
        return $count > 0 ? true : false;
    }
    
    /**
     *验证日期
     * @param $date
     * @return bool
     *
     * 如果最后一个参数不输入，默认为D2D，需要验证
     */
    private static function validateDate($date, $dataType = 'D2D')
    {
        if ($date == '' || substr_count($date, '-') != 2) {
            return false;
        }
    
        $manualD2D = Config::get('biddingos.manualD2D');
        $manualC2C = Config::get('biddingos.manualC2C');
        if (in_array($dataType, [$manualD2D, $manualC2C])) {
            $input = strtotime($date);
            $today = strtotime(date("Y-m-d"));
            $tenAgo = strtotime('-15 day', $today);
            if ($input >= $today || $input <= $tenAgo) {
                return false;
            }
        } else {
            $input = strtotime($date);
            $today = strtotime(date("Y-m-d"));
            if ($input >= $today) {
                return false;
            }
        }
    
        list($y, $m, $d) = explode('-', $date);
        return checkdate($m, $d, $y);
    }
    
    
    /**
     * 验证导入excel中的媒体商是否创建
     * @param $affiliateName
     * @return bool
     */
    private static function validateAffiliate($affiliateName)
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
     * 验证导入excel中的媒体商是否是人工媒体
     * @param $affiliateName
     * @return bool
     */
    private static function validateAffiliateMode($affiliateName)
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
     *验证导入excel中媒体商和广告之间是否建立投放关系
     * @param $campaignName
     * @param $channel
     * @param $clientId
     * @param $affiliateName
     * @param $zoneId
     * @return bool
     */
    private static function validateCampaignDeliveryAffiliate($campaignName, $clientId, $affiliateName, $zoneId)
    {
        if ($campaignName == '' || $clientId == '' || $affiliateName == ''
            || $zoneId == '' || !is_numeric($zoneId)
        ) {
            return false;
        }
    
        $select = DB::table('banners as b')
            ->leftJoin('campaigns as c', 'b.campaignid', '=', 'c.campaignid')
            ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'b.affiliateid')
            ->join('zones as z', 'z.affiliateid', '=', 'aff.affiliateid')
            ->leftJoin('clients as adv', 'adv.clientid', '=', 'c.clientid')
            ->whereIn('b.status', [Banner::STATUS_PUT_IN, Banner::STATUS_SUSPENDED])
            ->where('app.app_name', $campaignName)
            ->where('adv.clientid', $clientId)
            ->where('aff.name', $affiliateName)
            ->where('aff.agencyid', Auth::user()->agencyid)
            ->where('z.zoneid', $zoneId);
        $count = $select->count();
        return $count > 0 ? true : false;
    }
    
    /**
     * 验证导入excel中的广告名称获取广告的 campaignid
     * @param $clientId
     * @param $campaignName
     * @return bool
     */
    private static function getCampaignId($campaignName, $clientId)
    {
        $campaignId = DB::table('campaigns')
        ->leftJoin('appinfos', function ($join) {
            $join->on('appinfos.app_id', '=', 'campaigns.campaignname')
            ->on('appinfos.platform', '=', 'campaigns.platform');
        })
        ->leftJoin('clients', function ($leftJoin) {
            $leftJoin->on('clients.clientid', '=', 'campaigns.clientid');
        })
        ->where('appinfos.app_name', '=', $campaignName)
        ->where('clients.clientid', '=', $clientId)
        ->where('clients.agencyid', Auth::user()->agencyid)
        ->pluck('campaigns.campaignid');
        return $campaignId > 0 ? $campaignId : 0;
    }
    
    /**
     * 验证导入excel中的广告名称获取广告的信息
     * @param $clientId
     * @param $campaignName
     * @return bool
     */
    public static function getCampaignRow($campaignName, $clientId)
    {
        $row = DB::table('campaigns')
            ->leftJoin('appinfos', function ($join) {
                $join->on('appinfos.app_id', '=', 'campaigns.campaignname')
                ->on('appinfos.platform', '=', 'campaigns.platform');
            })
            ->leftJoin('clients', function ($leftJoin) {
                $leftJoin->on('clients.clientid', '=', 'campaigns.clientid');
            })
            ->where('appinfos.app_name', '=', $campaignName)
            ->where('clients.clientid', '=', $clientId)
            ->where('clients.agencyid', Auth::user()->agencyid)
            ->select(
                'campaigns.campaignid',
                'campaigns.revenue_type',
                'appinfos.app_show_name',
                'campaigns.platform'
            )
            ->first();
        return !empty($row) ? json_decode(json_encode($row), true) : [];
    }
    
    /**
     * 获取广告历史出价
     */
    private static function getCampaignHistoryPrice($campaignId, $date)
    {
        $daytime = strtotime("$date 00:00:00");
        $price = 0;
        $time = 0;
        for ($i = 0; $i < 24; $i++) {
            $queryTime = gmdate('Y-m-d H:00:00', ($daytime + $time));
            $sql = "SELECT
            c.campaignid,
            IF (
                tmp.current_revenue > 0,
                tmp.current_revenue,
                IF (ntmp.current_revenue > 0,ntmp.current_revenue,c.revenue)
            ) AS current_revenue
            FROM
            up_campaigns c
            LEFT JOIN (
            SELECT
                campaignid,
                current_revenue
            FROM
                `up_campaign_revenue_history`
            WHERE
                campaignid = {$campaignId}
            AND `time` <= DATE_FORMAT(
                '{$queryTime}',
                '%Y-%m-%d %H:59:59'
            )
            ORDER BY
                id DESC
            ) AS tmp ON (
                tmp.campaignid = c.campaignid
            )
            LEFT JOIN (
            SELECT
                campaignid,
                current_revenue
            FROM
                `up_campaign_revenue_history`
            WHERE
                campaignid = {$campaignId}
            ORDER BY
                id ASC LIMIT 1
            ) AS ntmp ON (
                ntmp.campaignid = c.campaignid
            )
            WHERE
                c.campaignid = {$campaignId}
            GROUP BY
                tmp.campaignid;";
            $row = DB::selectOne($sql);
            $row = json_decode(json_encode($row), true);
            $price += $row['current_revenue'];
            $time += 3600;
        }
        $data['current_revenue'] = round($price / 24, 2);
        return json_decode(json_encode($data), true);
    }
    
    /**
     * 获取导入广告的产品类型
     * @param $campaignName
     * @return mixed
     */
    private static function getProductType($campaignName, $clientId)
    {
        $productType = DB::table('campaigns as c')
                    ->join('appinfos as app', 'app.app_id', '=', 'c.campaignname')
                    ->leftJoin('clients as adv', 'c.clientid', '=', 'adv.clientid')
                    ->leftJoin('products as p', 'p.id', '=', 'c.product_id')
                    ->where('app.app_name', $campaignName)
                    ->where('adv.clientid', $clientId)
                    ->pluck('p.type');
        return $productType;
    }
    
    
    private static function validateDateCampaign($date, $campaignId)
    {
        //如果广告是CPA， CPT, CPS的，则不验证
        $revenue_type = DB::table('campaigns')
        ->where('campaignid', $campaignId)
        ->pluck('revenue_type');
        //如果是CPA， CPT, CPS则不检查
        if (in_array(
            $revenue_type,
            [Campaign::REVENUE_TYPE_CPA, Campaign::REVENUE_TYPE_CPT, Campaign::REVENUE_TYPE_CPS]
        )) {
            return true;
        }
    
        $oc = DB::table('operation_clients')
        ->select('issue')
        ->where('date', $date)
        ->where('campaign_id', $campaignId)
        ->first();
        if ($oc) {
            $oc = json_decode(json_encode($oc), true);
            if ($oc['issue'] == OperationClient::ISSUE_NOT_APPROVAL) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }
    
    /**
     *获取媒体商的投放数据
     *
     */
    private static function getAffiliateDeliveryData($campaignName, $date, $clientId)
    {
        $data = DB::table('manual_deliverydata AS md')
        ->leftJoin('campaigns AS c', 'md.campaign_id', '=', 'c.campaignid')
        ->leftJoin('appinfos AS ao', function ($join) {
            $join->on('c.campaignname', '=', 'ao.app_id')
            ->on('c.platform', '=', 'ao.platform');
        })
        ->leftJoin('clients', function ($leftJoin) {
            $leftJoin->on('clients.clientid', '=', 'c.clientid');
        })
        ->where('md.date', $date)
        ->where('ao.app_name', $campaignName)
        ->where('md.flag', ManualDeliveryData::FLAG_ASSIGNED)
        ->whereIn('md.data_type', Config::get('biddingos.manualWithAF'))
        ->where('clients.clientid', '=', $clientId)
        ->where('clients.agencyid', Auth::user()->agencyid)
        ->select('id', 'affiliate_id', 'cpa', 'expense')
        ->get();
        return $data;
    }
    
    /**
     * 验证导入excel中的数据，如展示量，点击量，下载量，
     * 收入，支出，CAP量， 广告主消耗等
     * @param $impressions
     * @param $clicks
     * @param $conversions
     * @param $revenue
     * @param $payment
     * @param $CPA
     * @param $conSum
     * @return bool
     */
    private static function validateDeliveryData($impressions, $clicks, $conversions, $revenue, $payment, $CPA, $conSum)
    {
        if (!is_numeric($impressions) || !is_numeric($clicks) || !is_numeric($conversions) || !is_numeric($revenue)
            || !is_numeric($payment) || !is_numeric($CPA) || !is_numeric($conSum)
        ) {
            return false;
        } elseif ($impressions < 0 || $clicks < 0 || $conversions < 0 ||
            $revenue < 0 || $payment < 0 || $CPA < 0 || $conSum < 0
        ) {
            return false;
        } elseif ($conversions > 0 && $clicks > 0) {
            return false;
        }
        return true;
    }
    
    
    //根据广告名称，获取此广告信息的明细
    private static function getCampaignInfo($campaignName, $clientId)
    {
        $row = DB::table('campaigns')
        ->leftJoin('appinfos', function ($join) {
            $join->on('appinfos.app_id', '=', 'campaigns.campaignname')
            ->on('appinfos.platform', '=', 'campaigns.platform');
        })
        ->leftJoin('clients', function ($leftJoin) {
            $leftJoin->on('clients.clientid', '=', 'campaigns.clientid');
        })
        ->where('appinfos.app_name', '=', $campaignName)
        ->where('clients.clientid', '=', $clientId)
        ->select('campaigns.campaignid', 'campaigns.revenue_type', 'appinfos.app_show_name')
        ->first();
    
        return !empty($row) ? json_decode(json_encode($row), true) : [];
    }
    
    /**
     * 从投放记录中查找到当日是否有投放记录了
     *
     */
    private static function getDeliveryLog($dateTime, $campaignId)
    {
        $startTime = gmdate('Y-m-d H:i:s', strtotime($dateTime));
        $sql = "SELECT
        b.affiliateid, SUM(h.af_income) s_af_income
        FROM
        up_data_summary_ad_hourly AS h
        JOIN up_banners b ON (h.ad_id = b.bannerid)
        WHERE 1
        AND h.date_time >= '$startTime'
        AND h.date_time < DATE_ADD('$startTime',INTERVAL 1 DAY)
        AND b.campaignid = {$campaignId}
        AND h.af_income > 0
        GROUP BY b.affiliateid
        ";
        $row = DB::select($sql);
        return json_decode(json_encode($row), true);
    }
}
