<?php

namespace App\Services;

use App\Components\Config;
use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\EmailHelper;
use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\UrlHelper;
use App\Models\Account;
use App\Models\AdZoneAssoc;
use App\Models\AdZoneKeyword;
use App\Models\AdZonePrice;
use App\Models\Affiliate;
use App\Models\AffiliateExtend;
use App\Models\AppInfo;
use App\Models\AttachFile;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\CampaignRevenueHistory;
use App\Models\CampaignTracker;
use App\Models\CampaignVideo;
use App\Models\Category;
use App\Models\Client;
use App\Models\DeliveryLog;
use App\Models\EquivalenceAssoc;
use App\Models\ExpenseLog;
use App\Models\OperationLog;
use App\Models\PlacementZoneAssoc;
use App\Models\Product;
use App\Models\Tracker;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    /**
     * 组装campaign数据
     * @param $rows
     * @return array
     */
    public static function getCampaignItems($rows)
    {
        $list = [];
        foreach ($rows as $row) {
            $item = $row;
            $item['revenue'] = Formatter::asDecimal($item['revenue']);
            $item['day_limit'] = Formatter::asDecimal($item['day_limit']);
            $item['total_limit'] = intval($item['total_limit']);

            if ($item['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $item['platform'] = Campaign::PLATFORM_IOS;
            }
            $item['products_type_label'] = Product::getTypeLabels($row['products_type']);
            $item['ad_type_label'] = Campaign::getAdTypeLabels($row['ad_type']);
            $item['platform_label'] = Campaign::getPlatformLabels($row['platform']);
            $item['status_label'] = Campaign::getStatusLabels($row['status']);
            $item['revenue_type_label'] = Campaign::getRevenueTypeLabels($row['revenue_type']);

            if (Auth::user()->account->isBroker()) {
                //关键字需要过滤平台用户创建
                $account = DB::table('campaigns')
                    ->where('campaignid', $item['campaignid'])
                    ->leftJoin('clients', 'campaigns.clientid', '=', 'clients.clientid')
                    ->leftJoin('accounts', 'clients.account_id', '=', 'accounts.account_id')
                    ->select('accounts.account_id')->first();
                //获取所有用户
                $userId = User::where('default_account_id', $account->account_id)
                    ->select('user_id')
                    ->get()
                    ->toArray();

                $count = AdZoneKeyword::where('campaignid', '=', $row['campaignid'])
                    ->whereIn('created_uid', $userId)
                    ->count();
            } else {
                //关键字需要过滤平台用户创建
                $userId = User::getAllUser();
                $count = AdZoneKeyword::where('campaignid', '=', $row['campaignid'])
                    ->whereIn('created_uid', $userId)
                    ->count();
            }
            $item['keyword_price_up_count'] = $count;

            //广告位加价数量
            $banner = Banner::where('campaignid', $row['campaignid'])->first();
            if ($banner) {
                $count = AdZonePrice::where('ad_id', $banner->bannerid)->count();
                $item['zone_price_up_count'] = $count;
            } else {
                $item['zone_price_up_count'] = 0;
            }
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 取得关键字的加价信息
     * @param $ids
     * @param null $userId
     * @return array
     */
    public static function getKeyWordPriceList($ids, $userId = null)
    {
        $dataArr = array();
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $prefix = DB::getTablePrefix();
        //查询应用下各个关键词的加价，概率
        $keywords_data = DB::table('ad_zone_keywords AS k')
            ->leftJoin('campaigns AS c', 'k.campaignid', '=', 'c.campaignid')
            ->leftJoin('banners AS b', 'k.campaignid', '=', 'b.campaignid')
            ->leftJoin('ad_zone_assoc AS a', 'b.bannerid', '=', 'a.ad_id')
            ->leftJoin('zones AS z', 'z.zoneid', '=', 'a.zone_id')
            ->whereIn('k.campaignid', $ids)
            ->whereIn('created_uid', $userId)
            ->groupBy(array('k.campaignid', 'k.keyword'))
            ->select(
                'k.id',
                'k.campaignid',
                'k.keyword',
                'k.price_up',
                'k.status',
                'a.ad_id',
                'k.operator',
                'k.type',
                DB::raw("SUM({$prefix}a.priority) total_priority"),
                'c.rate'
            )
            ->get();
        $keywords_total_data = array();
        foreach ($keywords_data as $v) {
            if (isset($keywords_total_data[$v->keyword]['total_price_up'])) {
                $keywords_total_data[$v->keyword]['total_price_up'] += $v->price_up;
                $keywords_total_data[$v->keyword]['total_priority'] += $v->total_priority;
            } else {
                $keywords_total_data[$v->keyword]['total_price_up'] = $v->price_up;
                $keywords_total_data[$v->keyword]['total_priority'] = $v->total_priority;
            }
        }
        if (!empty($keywords_data)) {
            $arSortData = array();
            //重算概率
            foreach ($keywords_data as $v) {
                //每个关键词 ( 概率*(1+当前加价/所有加价) ) / ( 总概率*2 )
                $keywords_total_priority = $keywords_total_data[$v->keyword]['total_priority'] > 0 ?
                    $keywords_total_data[$v->keyword]['total_priority'] * 2 : 1;
                $keywords_priority = $keywords_total_data[$v->keyword]['total_price_up'] == 0 ? 0 :
                    ($v->total_priority * (1 + $v->price_up /
                            $keywords_total_data[$v->keyword]['total_price_up'])) / $keywords_total_priority;
                $arSortData[$v->keyword][$v->campaignid] = $keywords_priority; //用于竞价排序
            }
            //排序数组后，根据竞争力排行重组数组
            $keywords_rank_data = array();
            foreach ($arSortData as $k => $v) {
                arsort($v, SORT_NUMERIC);
                $i = 1;
                $count = count($v);
                foreach ($v as $key => $val) {
                    $keywords_rank_data[$k][$key] = array('total' => $count, 'pos' => $i, 'priority' => $val);
                    $i++;
                }
            }
            //竞争力排行
            foreach ($keywords_data as $v) {
                $info = $keywords_rank_data[$v->keyword][$v->campaignid];
                if (!empty($info)) {
                    $info['rank'] = self::calculateRank($info);
                } else {
                    $info['rank'] = 1;
                }
                $v->rank = $info['rank'];
                $dataArr[$v->campaignid][] = $v;
            }
        }
        return $dataArr;
    }

    /**
     * 计算等级排名
     * @param $info
     * @return float|int
     */
    public static function calculateRank($info)
    {
        if ($info['priority'] == 0) {
            $info['rank'] = 1;
        } elseif ($info['total'] == 1 || $info['pos'] == 1) {
            //第一名
            $info['rank'] = 8;
        } elseif ($info['pos'] == $info['total']) {
            //最后一名
            $rank = (1 - round((intval($info['pos']) - 1) / intval($info['total']), 4)) * 100;
            if ($rank >= 10 && (is_float($rank) || $rank % 10 != 0)) {
                $rank = intval($rank - $rank % 10) + 10;
            }
            $info['rank'] = $rank >= 10 ? floor($rank / 10) : 1;
        } else {
            $rank = (1 - round(intval($info['pos']) / intval($info['total']), 4)) * 100;
            if ($rank >= 80) {
                $info['rank'] = 8;
            } else {
                if ($rank >= 10 && (is_float($rank) || $rank % 10 != 0)) {
                    $rank = intval($rank - $rank % 10) + 10;
                }
                $info['rank'] = $rank >= 10 ? floor($rank / 10) : 1;
            }
        }
        return $info['rank'];
    }

    /**
     * 保存推广计划
     * @param integer $campaignid
     * @param array $params
     * @return array|int (code, msg)0=成功；1=失败；2=已存在相同应用;-1=无权限操作;-2=不允许修改
     */
    public static function storeCampaign($params)
    {
        //序列化应用市场图片
        $imagesStr =
            ($params['ad_type'] == Campaign::AD_TYPE_APP_MARKET || $params['ad_type'] == Campaign::AD_TYPE_APP_STORE)
                ? serialize($params['appinfos_images']) : null;
        if (empty($params['id'])) {
            //新增  检测是否已存在相同的推广应用
            $row = self::getCampaignCount($params);
            if ($row > 0) {
                LogHelper::info('The same application already exists' . $params['appinfos_app_name']);
                return 5022;
            }
            //生成AppId
            $params['app_id'] = 'app' . str_random(12);
            //新建推广计划
            $campaign = Campaign::storeCampaign($params);
            if (!$campaign) {
                return 5101;// @codeCoverageIgnore
            }
            //获取最新推广计划ID
            $params['id'] = $campaign->campaignid;

            //新建appInfo
            AppInfo::storeAppInfo($params, $imagesStr);

            //添加附件
            if ($params['products_type'] == Product::TYPE_APP_DOWNLOAD) {
                $attachFile = AttachFile::store($params['package_file'], $params['id']);
                if (!$attachFile) {
                    return 5001;// @codeCoverageIgnore
                }
            }

            //新增feedsImage
            if ($params['ad_type'] == Campaign::AD_TYPE_FEEDS) {
                if (empty($params['appinfos_images'])) {
                    return 5024;
                }
                CampaignImage::storeFeedsImage($params['id'], $params['appinfos_images']);
            }
            //新增banner图片
            if ($params['ad_type'] == Campaign::AD_TYPE_BANNER_IMG
                || $params['ad_type'] == Campaign::AD_TYPE_HALF_SCREEN
                || $params['ad_type'] == Campaign::AD_TYPE_FULL_SCREEN
            ) {
                //campaigns_images
                if (!empty($params['appinfos_images']) && is_array($params['appinfos_images'])) {
                    CampaignImage::storeBannerOrScreenImage(
                        $params['id'],
                        $params['appinfos_images'],
                        $params['ad_type']
                    );
                } else {
                    return 5024;
                }
            }
            if ($params['ad_type'] == Campaign::AD_TYPE_VIDEO) {
                $ret = CampaignVideo::store($params['id'], $params['video']);
                if (!$ret) {
                    return 5001;
                }
            }
        } else {
            if ($params['ad_type'] == Campaign::AD_TYPE_FEEDS) {
                if (empty($params['appinfos_images'])) {
                    return 5024;
                }
            }

            //更新banner图片
            if ($params['ad_type'] == Campaign::AD_TYPE_BANNER_IMG
                || $params['ad_type'] == Campaign::AD_TYPE_HALF_SCREEN
                || $params['ad_type'] == Campaign::AD_TYPE_FULL_SCREEN
            ) {
                //campaigns_images
                if (empty($params['appinfos_images']) || !is_array($params['appinfos_images'])) {
                    return 5024;
                }
            }
            //修改
            $ret_code = self::updateCampaign($params, $imagesStr);
            if ($ret_code !== true) {
                return $ret_code;// @codeCoverageIgnore
            }

            $campaign = Campaign::find($params['id']);
        }
        // 如果有定向关键字加价，则新增或者更新
        if (!empty($params['keywords'])) {
            AdZoneKeyword::updateKeyWordAndPrice(
                empty($campaign) ? $params['id'] : $campaign->campaignid,
                $params['keywords']
            );
        }

        //新增写入操作日志
        if (($params['action'] == Campaign::ACTION_APPROVAL && $campaign->status == Campaign::STATUS_PENDING_APPROVAL)
            || ($params['action'] == Campaign::ACTION_EDIT && ($campaign->status == Campaign::STATUS_DRAFT ||
                    $campaign->status == Campaign::STATUS_REJECTED))
        ) {
            $message = self::formatWaring(6009, [$campaign->client->clientname, $params['appinfos_app_name']]);
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'type' => OperationLog::TYPE_MANUAL,
                'target_id' => $campaign->campaignid,
                'operator' => OperationLog::ADVERTISER,
                'message' => $message,
            ]);
        }
        return true;
    }

    /**
     * 修改推广计划
     * @param $params
     * @param null $imagesStr
     * @return int
     */
    public static function updateCampaign($params, $imagesStr = null)
    {
        $campaign = Campaign::find($params['id']);
        if (!$campaign || $campaign->clientid != $params['clientid']) {
            return 5103;// @codeCoverageIgnore
        }
        if ($campaign->ad_type != $params['ad_type']) {
            return 5023;// @codeCoverageIgnore
        }
        //修改应用信息
        LogHelper::info('appInfo ' . $campaign->campaignname . ' modify appInfo data');
        $appInfo = AppInfo::where('app_id', $campaign->campaignname)
            ->where('platform', $campaign->platform)
            ->first();
        $product = Product::find($params['products_id']);
        if ($campaign->status == Campaign::STATUS_DRAFT ||
            $campaign->status == Campaign::STATUS_REJECTED
        ) {
            //草稿状态修改产品信息
            $product = Product::find($params['products_id']);
            $product->show_name = $params['products_show_name'];
            $product->link_name = $params['link_name'];//链接推广需要保存链接名称和URL
            $product->link_url = $params['link_url'];
            $product->icon = $params['products_type'] == Product::TYPE_APP_DOWNLOAD ?
                $params['products_icon'] : '';

            //草稿状态可直接修改  update 审核未通过时直接修改
            $appInfo->app_name = $params['appinfos_app_name'];
            $appInfo->app_show_icon = $params['products_icon'];
            $appInfo->app_show_name = $params['products_show_name'];
            $appInfo->platform = $params['platform'];
            $appInfo->profile = $params['appinfos_profile'];
            $appInfo->description = $params['appinfos_description'];
            $appInfo->update_des = $params['appinfos_update_des'];
            $appInfo->star = $params['star'];
            $appInfo->title = $params['link_title'];
            $appInfo->application_id = isset($params['application_id']) ? $params['application_id'] : 0;
            if ($params['ad_type'] == Campaign::AD_TYPE_APP_STORE && !empty($params['application_id'])) {
                $appInfo->appstore_info = CampaignService::getAppIdInfo($params['application_id']);
            }
            if (isset($imagesStr)) {
                $appInfo->images = $imagesStr;
            }
            if (isset($params['appinfos_images'])) {
                if ($params['ad_type'] == Campaign::AD_TYPE_BANNER_IMG
                    || $params['ad_type'] == Campaign::AD_TYPE_HALF_SCREEN
                    || $params['ad_type'] == Campaign::AD_TYPE_FULL_SCREEN
                ) {
                    CampaignImage::storeBannerOrScreenImage(
                        $params['id'],
                        $params['appinfos_images'],
                        $params['ad_type']
                    );
                } elseif ($params['ad_type'] == Campaign::AD_TYPE_FEEDS) {
                    CampaignImage::storeFeedsImage($params['id'], $params['appinfos_images']);
                }
            }
            //草稿状态可直接修改包信息
            if ($params['products_type'] == Product::TYPE_APP_DOWNLOAD) {
                /*
                 * 修改素材时候，如果已经上传则更新，
                 * 没有上传则新增，并修改之前包为作废
                 */
                AttachFile::updateAttachFile($params['id'], $params['package_file']);
            }
            if ($params['ad_type'] == Campaign::AD_TYPE_VIDEO) {
                /*
                 * 修改视频素材，如果文件已经存在则更新
                 * 没有上传则新增，并将之前视频素材作废
                 */
                CampaignVideo::updateVideo($params['id'], $params['video']);
            }
            //修改出价
            if ($campaign->revenue != $params['revenue']) {
                $decimal = Config::get('biddingos.jsDefaultInit.' . $campaign->revenue_type . '.decimal');
                $oldValue = Formatter::asDecimal($campaign->revenue, $decimal);
                $value = Formatter::asDecimal($params['revenue'], $decimal);
                $message = CampaignService::formatWaring(6010, [
                    $campaign->client->clientname, $oldValue, $value
                ]);
                self::writeAdvertiserLog($campaign, $message);
            }
            //修改日预算
            if ($campaign->day_limit != $params['day_limit']) {
                $oldValue = Formatter::asDecimal($campaign->day_limit, 0);
                $value = Formatter::asDecimal($params['day_limit'], 0);
                $message = CampaignService::formatWaring(6011, [
                    $campaign->client->clientname, $oldValue, $value
                ]);
                self::writeAdvertiserLog($campaign, $message);
            }
            //修改总预算
            if ($campaign->total_limit != $params['total_limit']) {
                $oldValue = Formatter::asDecimal($campaign->total_limit, 0);
                $value = Formatter::asDecimal($params['total_limit'], 0);
                $message = CampaignService::formatWaring(6012, [
                    $campaign->client->clientname,
                    $oldValue == 0 ? '不限' : $oldValue,
                    $value == 0 ? '不限' : $value,
                ]);

                self::writeAdvertiserLog($campaign, $message);
            }
            $campaign->revenue = isset($params['revenue']) ? $params['revenue'] : 0;
            $campaign->total_limit = isset($params['total_limit']) ? $params['total_limit'] : 0;
            $campaign->day_limit = isset($params['day_limit']) ? $params['day_limit'] : 0;
            $campaign->revenue_type = $params['revenue_type'];

            if ($campaign->status == Campaign::STATUS_REJECTED) {
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaign->campaignid,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => OperationLog::ADVERTISER,
                    'message' => self::formatWaring(6013, [$campaign->client->clientname]),
                ]);
            }
        } else {
            //其他状态需审核
            $material = [
                'icon' => $params['products_icon'],
                'app_name' => $params['appinfos_app_name'],
                'app_show_name' => $params['products_show_name'],
                'revenue' => $params['revenue'],
                'day_limit' => $params['day_limit'],
                'total_limit' => $params['total_limit'],
                'name' => $params['products_type'] == Product::TYPE_APP_DOWNLOAD
                    ? $params['products_name'] : $params['link_name'],
                'profile' => $params['appinfos_profile'],
                'link_name' => $params['link_name'],
                'link_url' => $params['link_url'],
            ];
            if ($params['products_type'] == Product::TYPE_APP_DOWNLOAD) {
                $package = self::getPackageMaterial($params['package_file']);
                $material['package'] = $package;
                $count = AttachFile::whereMulti([
                    'campaignid' => $params['id'],
                    'hash' => $package['md5']
                ])->count();
                if ($count == 0) {
                    $oldAttachFile = AttachFile::whereIn('flag', [
                        AttachFile::FLAG_NOT_USED,
                        AttachFile::FLAG_USING,
                    ])
                        ->where('campaignid', $params['id'])
                        ->whereNotNull('channel')
                        ->select('channel')
                        ->orderBy('created_at', 'DESC')
                        ->first();
                    $package['channel'] = $oldAttachFile->channel;
                    AttachFile::store($package, $params['id']);
                }
            }
            if ($params['ad_type'] == Campaign::AD_TYPE_VIDEO) {
                $vedioInfo = self::getVideoMaterial($params['video']);
                $material['video'] = $vedioInfo;
                $count = CampaignVideo::whereMulti([
                    'campaignid' => $params['id'],
                    'md5_file' => $vedioInfo['md5_file']
                ])->count();
                if ($count == 0) {
                    CampaignVideo::store($params['id'], $params['video']);
                }
            }
            if ($params['ad_type'] == Campaign::AD_TYPE_FEEDS) {
                $width = CampaignImage::FEEDS_DEFAULT_WIDTH;
                $height = CampaignImage::FEEDS_DEFAULT_HEIGHT;
                $feed_img = [[
                    'spec' => CampaignImage::FEEDS_DEFAULT_AD_SPEC,
                    'size' => "{$width},{$height}",
                    'imgURL' => $params['appinfos_images'][0]['url']
                ]];
                $material['star'] = $params['star'];
                $material['images'] = $feed_img;
                $material['title'] = $params['link_title'];
            } elseif ($params['ad_type'] == Campaign::AD_TYPE_APP_MARKET
                || $params['ad_type'] == Campaign::AD_TYPE_APP_STORE
            ) {
                $material['description'] = $params['appinfos_description'];
                $material['platform'] = $params['platform'];
                $material['update_des'] = $params['appinfos_update_des'];
                $material['images'] = $params['appinfos_images'];
                $material['application_id'] = isset($params['application_id']) ? $params['application_id'] : 0;
            } elseif ($params['ad_type'] == Campaign::AD_TYPE_BANNER_IMG
                || $params['ad_type'] == Campaign::AD_TYPE_HALF_SCREEN
                || $params['ad_type'] == Campaign::AD_TYPE_FULL_SCREEN
            ) {
                $bannerImg = [];
                foreach ($params['appinfos_images'] as $img) {
                    $size = str_replace(
                        '*',
                        ',',
                        Config::get('ad_spec.' . $params['ad_type'] . '.' . $img['ad_spec'])
                    );
                    array_push($bannerImg, [
                        'spec' => $img['ad_spec'],
                        'size' => $size,
                        'imgURL' => $img['url'],
                    ]);
                }
                $material['images'] = $bannerImg;
                $material['platform'] = $params['platform'];
            }

            $appInfo->materials_data = json_encode($material);
            $appInfo->app_name = $params['appinfos_app_name'];
            if (self::checkDataIsUpdate($params, $campaign, $appInfo)) {
                $appInfo->materials_status = AppInfo::MATERIAL_STATUS_PENDING_APPROVAL; //物料待审核状态
            }

            //如果出价,日预算，总预算已经修改，需要发送邮件给运营及销售审核
            if ($campaign->revenue != $params['revenue']) {
                self::sendCampaignApprovalMail($campaign, $params, 'revenue');
            }
            if ($campaign->total_limit != $params['total_limit']) {
                self::sendCampaignApprovalMail($campaign, $params, 'total_limit');
            }
            if ($campaign->day_limit != $params['day_limit']) {
                self::sendCampaignApprovalMail($campaign, $params, 'day_limit');
            }
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'target_id' => $campaign->campaignid,
                'type' => OperationLog::TYPE_MANUAL,
                'operator' => OperationLog::ADVERTISER,
                'message' => self::formatWaring(6013, [$campaign->client->clientname]),
            ]);
        }

        $product->name = $params['products_type'] == Product::TYPE_LINK
            ? $params['link_name'] : $params['products_name'];
        if (!$product->save()) {
            LogHelper::info('product ' . $product->name . ' modify product data failed');
            return 5101;
        }

        if (!$appInfo->save()) {
            LogHelper::info('appInfo ' . $campaign->campaignname . ' modify appInfo data failed');
            return 5101;
        }

        //修改推广计划信息
        $campaign->ad_type = $params['ad_type'];
        $campaign->product_id = $params['products_id'];
        if (!empty($params['action']) && ($params['action'] == Campaign::ACTION_APPROVAL ||
                $params['action'] == Campaign::ACTION_EDIT) && ($campaign->status == Campaign::STATUS_DRAFT ||
                $campaign->status == Campaign::STATUS_REJECTED)
        ) {
            $campaign->status = Campaign::STATUS_PENDING_APPROVAL;
        }
        if ($campaign->status == Campaign::STATUS_DRAFT) {
            $campaign->platform = $params['platform'];
        }
        LogHelper::info('campaign ' . $params['id'] . ' modify appInfo data');
        if (!$campaign->save()) {
            LogHelper::info('campaign ' . $params['id'] . ' modify appInfo data failed');
            return 5101;
        }
        return true;
    }

    private static function checkDataIsUpdate($params, $campaign, $appInfo)
    {
        //检测产品信息是否更新
        $product = Product::find($params['products_id']);
        if ($product->icon != $params['products_icon'] ||
            $product->show_name != $params['products_show_name']
        ) {
            return true;
        }
        //检测应用信息
        if ($appInfo->profile != $params['appinfos_profile'] ||
            $appInfo->description != $params['appinfos_description'] ||
            $appInfo->update_des != $params['appinfos_update_des'] ||
            $appInfo->images != serialize($params['appinfos_images'])
        ) {
            return true;
        }
        //检测推广计划
        if ($campaign->day_limit != $params['day_limit'] ||
            $campaign->total_limit != $params['total_limit'] ||
            $campaign->revenue != $params['revenue'] ||
            empty($params['package_file']['package_id'])
        ) {
            return true;
        }
        return false;
    }

    private static function sendCampaignApprovalMail($campaign, $params, $field)
    {
        $appName = $campaign->appinfo->app_name;
        $clientName = $campaign->client->clientname;
        if ($field == 'revenue') {
            $oldValue = Formatter::asDecimal($campaign->revenue);
            $value = Formatter::asDecimal($params['revenue']);
            $message = self::formatWaring(6010, [$campaign->client->clientname,$oldValue,$value]);
        } elseif ($field == 'total_limit') {
            $oldValue = Formatter::asDecimal($campaign->total_limit);
            $value = Formatter::asDecimal($params['total_limit']);

            $message = self::formatWaring(6012, [
                $campaign->client->clientname,
                ($oldValue == 0 ? '不限' : $oldValue),
                ($value == 0 ? '不限' : $value)
            ]);
        } else {
            $oldValue = Formatter::asDecimal($campaign->day_limit);
            $value = Formatter::asDecimal($params['day_limit']);

            $message = self::formatWaring(6011, [
                $campaign->client->clientname,
                $oldValue,$value
            ]);
        }

        self::writeAdvertiserLog($campaign, $message);

        $fieldName = str_replace('(元)', '', Campaign::attributeLabels($field));
        if ($field == 'total_limit') {
            $subject = "{$appName}的{$fieldName}从";
            $subject = $oldValue == 0 ? $subject . '不限改成' : $subject . $oldValue . '元改成';
            $subject = $value == 0 ? $subject . '不限.' : $subject . $value . '元.';
        } else {
            $subject = "{$appName}的{$fieldName}从{$oldValue}元改成{$value}元.";
        }
        $mail['subject'] = $subject;
        $mail['msg']['clientname'] = $clientName;
        $mail['msg']['app_name'] = $appName;
        $mail['msg']['field'] = $fieldName;
        $mail['msg']['old_value'] = $oldValue;
        $mail['msg']['new_value'] = $value;

        $agencies = MessageService::getPlatUserInfo([$campaign->client->agencyid]);
        if (count($agencies) > 0) {
            $agencies = array_column($agencies, 'email_address');
            //分发邮件发送任务
            EmailHelper::sendEmail(
                'emails.advertiser.modifyRevenueApprove',
                $mail,
                $agencies
            );
        }
        //邮件通知联盟运营和对应的广告销售
        $sales = MessageService::getCampaignSaleUsersInfo($campaign->campaignid);
        if (count($sales) > 0) {
            $sales = array_column($sales, 'email_address');
            //分发邮件发送任务
            EmailHelper::sendEmail(
                'emails.advertiser.modifyRevenueApprove',
                $mail,
                array_diff($sales, $agencies)
            );
        }
    }

    /**
     * 写入广告主日预算，出价，总预算日志
     * @param $campaign
     * @param $message
     */
    public static function writeAdvertiserLog($campaign, $message)
    {
        OperationLog::store([
            'category' => OperationLog::CATEGORY_CAMPAIGN,
            'target_id' => $campaign->campaignid,
            'type' => OperationLog::TYPE_MANUAL,
            'operator' => OperationLog::ADVERTISER,
            'message' => $message,
        ]);
    }

    /**
     * 检测是否已存在相同的推广应用
     * @return integer
     */
    public static function getCampaignCount($params)
    {
        $row = DB::table('campaigns')
            ->leftJoin('clients', function ($join) {
                $join->on('campaigns.clientid', '=', 'clients.clientid');
            })
            ->leftJoin('appinfos', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id');
                $join->on('campaigns.platform', '=', 'appinfos.platform');
                $join->on('clients.agencyid', '=', 'appinfos.media_id');
            })
            ->where('appinfos.app_name', '=', $params['appinfos_app_name'])
            ->where('campaigns.clientid', '=', $params['clientid']);
        if (!empty($params['id'])) {
            $row->where('campaigns.campaignid', '<>', $params['id']);
        }
        return $row->count();
    }


    /**
     * CPA/CPT推广计划新增
     * @param $params
     * @return bool|int
     */
    public static function campaignStore($params)
    {
        DB::beginTransaction();  //事务开始

        if (Auth::user()->account->isAdvertiser()) {
            $params['clientid'] = Auth::user()->account->client->clientid;
        }
        //保存产品信息
        if (empty($params['products_id'])) {
            // 新增
            $product = new Product();
            $product->type = Product::TYPE_APP_DOWNLOAD;
            $product->platform = $params['platform'];
            $product->clientid = $params['clientid'];
            $product->name = $params['products_name'];
            $product->show_name = $params['products_name'];
            $product->icon = $params['products_icon'];
            $ret = $product->save();
            if (!$ret) {
                DB::rollback();
                return 5001;
            }
            $params['products_id'] = $product->id;
        } else {
            Product::where('id', $params['products_id'])->update([
                'name' => $params['products_name'],
                'show_name' => $params['products_name'],
                'icon' => $params['products_icon'],
            ]);
        }

        if (empty($params['id'])) {
            //存储campaign信息
            $row = CampaignService::getCampaignCount($params);
            if ($row > 0) {
                DB::rollback();
                LogHelper::info('The same application already exists' . $params['appinfos_app_name']);
                return 5022;
            }

            //生成AppId
            $params['app_id'] = 'app' . str_random(12);
            //新建推广计划
            $campaign = Campaign::storeCampaign($params);
            if (!$campaign) {
                DB::rollback();
                return 5101;// @codeCoverageIgnore
            }

            //存储应用信息
            $appInfo = new AppInfo();
            $appInfo->media_id = $params['agencyid'];
            $appInfo->app_id = $params['app_id'];
            $appInfo->app_name = $params['appinfos_app_name'];
            $appInfo->platform = $params['platform'];
            $appInfo->app_show_name = $params['appinfos_app_name'];
            $appInfo->application_id = isset($params['application_id']) ? $params['application_id'] : 0;
            $ret = $appInfo->save();
            if (!$ret) {
                DB::rollback();
                return 5001;
            }

            //记录新建A,T广告日志
            $message = self::formatWaring(6009, [$campaign->client->clientname, $params['appinfos_app_name']]);
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'target_id' => $campaign->campaignid,
                'type' => OperationLog::TYPE_MANUAL,
                'operator' => Auth::user()->contact_name,
                'message' => $message,
            ]);
        } else {
            //修改CPA,CPT信息campaign
            $campaign = Campaign::find($params['id']);
            $campaign->revenue_type = $params['revenue_type'];
            if (!empty($params['action']) && ($params['action'] == Campaign::ACTION_APPROVAL ||
                    $params['action'] == Campaign::ACTION_EDIT) && ($campaign->status == Campaign::STATUS_DRAFT ||
                    $campaign->status == Campaign::STATUS_REJECTED)
            ) {
                $campaign->status = Campaign::STATUS_DELIVERING;
            }
            if (!$campaign->save()) {
                DB::rollback();
                LogHelper::info('campaign ' . $params['id'] . ' modify appInfo data failed');
                return 5001;
            }

            //修改AppInfo信息
            $ret = AppInfo::where('app_id', $campaign->campaignname)->update([
                'app_name' => $params['appinfos_app_name'],
                'app_show_name' => $params['appinfos_app_name'],
            ]);
            if (!$ret) {
                DB::rollback();
                LogHelper::info('appInfo ' . $campaign->campaignname . ' modify appInfo data failed');
                return 5001;
            }
        }

        DB::commit(); //事务结束

        return true;
    }

    /**
     * 取得关键字加价的信息
     * @param integer $campaignId
     * @return unknown
     */
    public static function getKeywordPrice($campaignId)
    {
        $userId = User::getAllUser();
        $data = DB::table('ad_zone_keywords')
            ->where('campaignid', $campaignId)
            ->whereIn('created_uid', $userId)
            ->select('id', 'keyword', DB::raw('convert(price_up, DECIMAL(10, 1)) as price_up'), 'status')
            ->get();
        return $data;
    }

    public static function findBanners($adType, $campaignId)
    {
        $res = DB::table('banners')
            ->leftJoin('affiliates_extend', function ($join) {
                $join->on('banners.affiliateid', '=', 'affiliates_extend.affiliateid')
                    ->on('banners.revenue_type', '=', 'affiliates_extend.revenue_type');
            })
            ->where('affiliates_extend.ad_type', $adType)
            ->where('banners.campaignid', $campaignId)
            ->select('bannerid', 'af_manual_price', 'num', 'banners.revenue_type', 'banners.revenue_price')
            ->get();
        return $res;
    }

    /**
     * 获取媒体商查询条件
     * @param $params
     * @return string
     */
    public static function getAffiliateCondition($params)
    {
        $condition = '';
        $conditionArr = [];
        //平台类型
        if (!is_null($params['platform']) && $params['platform'] != '') {
            $conditionArr[] = ' a.platform  = ' . $params['platform'];
        }
        //分类
        if (!is_null($params['parent']) && $params['parent'] != '') {
            $conditionArr[] = ' cat.parent = ' . $params['parent'];
        }
        //应用等级
        if (!is_null($params['appinfos_app_rank']) && trim($params['appinfos_app_rank'])) {
            $conditionArr[] = ' b.app_rank = ' . $params['appinfos_app_rank'];
        }
        //广告状态
        $bannerStatus = Banner::STATUS_PENDING_MEDIA;
        if (!is_null($params['status']) && $params['status'] != '') {
            if ($params['status'] == Banner::STATUS_PENDING_MEDIA) {
                $conditionArr[] = " b.bannerid IS NULL OR b.`status` = {$bannerStatus} ";
            } else {
                $conditionArr[] = ' b.`status` = ' . $params['status'];
            }
        }
        if (!empty($conditionArr)) {
            $condition = implode(" AND ", $conditionArr);
        }
        if (!empty($condition)) {
            $condition .= ' AND t.ad_type IN(' . implode(',', explode(",", $params['ad_type'])) . ")";
        } else {
            $condition .= ' t.ad_type IN(' . implode(',', explode(",", $params['ad_type'])) . ")";
        }
        return $condition;
    }


    /**
     * 获取广告应用上的关键字加价的数量
     * @param $campaignIds
     * @return mixed
     */
    public static function getCampaignKeywords($campaignId)
    {
        if (Auth::user()->account->isManager()) {
            $data = AdZoneKeyword::where('campaignid', $campaignId)
                ->select('campaignid', 'keyword', 'price_up')
                ->get();
        } elseif (Auth::user()->account->isTrafficker()) {
            //获取推广计划所有用户
            $createdIds = AdZoneKeyword::getCreatedId($campaignId);
            //获取所有广告主账户
            $userId = [];
            foreach ($createdIds as $cid) {
                $accountType = DB::table('users')->where('user_id', $cid)
                    ->leftJoin('accounts', 'users.default_account_id', '=', 'accounts.account_id')
                    ->pluck('accounts.account_type');
                if ($accountType == Account::TYPE_ADVERTISER) {
                    array_push($userId, $cid['created_uid']);
                }
            }
            $data = AdZoneKeyword:: where('operator', 0)
                ->where('campaignid', $campaignId)
                ->whereIn('created_uid', $userId)
                ->select('campaignid', 'keyword', 'price_up')
                ->get();
        }
        return $data;
    }

    /**
     * 重构广告列表数据
     * @param $result
     * @return array
     */
    public static function getCampaignForAffiliate($result)
    {
        $val = [];
        $data = [];
        foreach ($result as $k => $v) {
            $val['pause_status'] = $v['pause_status'];
            $val['affiliateid'] = $v['affiliateid'];
            $val['campaignid'] = $v['campaignid'];
            $val['bannerid'] = $v['bannerid'];
            $val['products_name'] = $v['products_name'];
            $val['products_show_name'] = $v['products_show_name'];
            $val['products_type'] = $v['products_type'];
            $val['products_type_label'] = Product::getTypeLabels($v['products_type']);
            $val['appinfos_app_name'] = $v['appinfos_app_name'];
            if (!empty($v['appinfos_app_show_icon'])) {
                $val['appinfos_app_show_icon'] = UrlHelper::imageFullUrl($v['appinfos_app_show_icon']);
            } else {
                $val['appinfos_app_show_icon'] = '';
            }
            $val['approve_comment'] = $v['approve_comment'];//审核说明/暂停说明
            // 没有审核时间，显示投放的时间
            if (empty($v['approve_time'])) {
                $val['approve_time'] = $v['updated'];
            } else {
                $val['approve_time'] = $v['approve_time'];
            }
            $val['platform'] = $v['platform'];
            $val['ad_type'] = $v['ad_type'];
            if ($v['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $val['platform'] = Campaign::PLATFORM_IOS;
            }
            $val['revenue_type'] = $v['revenue_type'];
            $val['revenue_type_label'] = Campaign::getRevenueTypeLabels($v['revenue_type']);
            $val['revenue'] = $v['m_revenue'];
            if ($v['name'] != '' && in_array($v['parent'], array_keys(Category::getParentLabels()))) {
                $val['category_label'] = Category::getParentLabels($v['parent']) . '-' . $v['name'];
            } else {
                $val['category_label'] = $v['name'];
            }
            $val['category'] = $v['category'];
            $val['parent'] = $v['parent'];
            $val['appinfos_app_rank'] = $v['app_rank'];
            if (in_array($v['app_rank'], array_keys(AppInfo::getRankStatusLabels()))) {
                if (!isset($v['app_rankLetter'])) {
                    $val['appinfos_app_rank_label'] = AppInfo::getRankStatusLabels($v['app_rank']);
                }
            } else {
                $val['appinfos_app_rank_label'] = '-';
            }
            $val['flow_ratio'] = $v['flow_ratio'];
            $val['flow_ratio_label'] = $v['mode'] == Affiliate::MODE_ARTIFICIAL_DELIVERY ?
                '-' : $v['flow_ratio'] . '%';
            $val['status'] = $v['status'];
            $val['application_id'] = $v['application_id'];
            $val['attach_file_id'] = $v['attach_file_id'];
            $val['download_url'] = UrlHelper::fileTraffickerFullUrl($v['download_url']);
            $val['mode'] = $v['mode'];
            $val['client_name'] = $v['clientname'];
            $val['link_name'] = $v['link_name'];
            $val['link_url'] = $v['link_url'];
            $val['appinfos_profile'] = $v['profile'];
            $val['appinfos_description'] = $v['description'];
            $val['link_title'] = $v['title'];
            $val['star'] = $v['star'];
            $val['campaigns_status'] = $v['campaigns_status'];
            $val['package_name'] = $v['package_name'];

            if ($v['ad_type'] == Campaign::AD_TYPE_APP_MARKET || $v['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $images = $v['images'] ? unserialize($v['images']) : [];
                if (ArrayHelper::arrayLevel($images) == 1) {
                    $val['appinfos_images'] = [
                        '1' => $images,
                        '2' => [],
                    ];
                } else {
                    $val['appinfos_images'] = $images;
                }
                $keyPrice = self::getCampaignKeywords($v['campaignid']);
                $val['keyword_price_up_count'] = count($keyPrice);
            } else {
                $imagesTypeList = Config::get('ad_spec.' . $v['ad_type']);
                $campaignImages = CampaignImage::getCampaignImages($v['campaignid']);
                $newBannerImages = [];
                if (isset($imagesTypeList)) {
                    foreach ($imagesTypeList as $ke => $va) {
                        $sizeArr = explode("*", $va);
                        $newBannerImages[$ke] = ['ad_spec' => $ke, 'url' => '', 'alt_url' => '',
                            'width' => $sizeArr[0], 'height' => $sizeArr[1]];
                        foreach ($campaignImages as $kImg => $vImg) {
                            if ($ke == $vImg['ad_spec']) {
                                $newBannerImages[$ke] = ['ad_spec' => $ke, 'url' => $vImg['url'],
                                    'alt_url' => $vImg['alt_url'], 'width' => $sizeArr[0],
                                    'height' => $sizeArr[1]];
                            }
                        }
                    }
                }
                $val['appinfos_images'] = array_values($newBannerImages);
                $val['keywords'] = [];
                $val['keyword_price_up_count'] = 0;
            }
            $data[] = (array)$val;
        }
        return $data;
    }

    /**
     * 添加追踪信息
     * @param $campaignId
     * @param $affiliateId
     * @param $campaignName
     */
    public static function tracker($campaignId, $affiliateId, $campaignName)
    {
        $campaign = Campaign::find($campaignId);
        $bannerData = Banner::whereMulti(['campaignid' => $campaignId, "affiliateid" => $affiliateId])->first();
        // 添加跟踪器
        $clientId = $campaign->client->clientid;
        $tracker = Tracker::store($campaignName, $clientId, $bannerData->bannerid);
        // 关联广告计划
        $tracker->campaigns()->attach($campaignId, [
            'status' => Tracker::STATUS_CONFIRM,
        ]);
    }


    /**
     * 修改状态
     * @param $campaignId
     * @param $status
     * @param array $params
     * @return bool|int
     */
    public static function modifyStatus($campaignId, $status, $params = [], $noLog = true)
    {
        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            return 5002;
        }
        $oldStatus = $campaign->status;
        //审核,不通过审核,继续投放
        if (!in_array($oldStatus, Campaign::getStatusPrevious($status))) {
            return 5028;
        }

        if (isset(Auth::user()->account)) {
            if ($campaign->old_pause_status == Campaign::PAUSE_STATUS_ADVERTISER_PAUSE
                && Auth::user()->account->isManager()
            ) {
                return 5028;
            }
        }
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                if (!is_numeric($k)) {
                    $campaign->$k = $v;
                }
            }
        }

        //如果是暂停
        if ($status == Campaign::STATUS_SUSPENDED) {
            //如果是暂停
            if ($oldStatus == Campaign::STATUS_SUSPENDED) {//如果现在已经是暂停的
                //不是因为超预算暂停
                //if ($campaign->pause_status != Campaign::PAUSE_STATUS_EXCEED_TOTAL_LIMIT) {
                $campaign->old_pause_status = $campaign->pause_status;//保存上次状态
                if (!array_key_exists('pause_status', $params)) {
                    $campaign->pause_status = Campaign::PAUSE_STATUS_PLATFORM;//平台暂停
                }
                //}
            } elseif ($oldStatus == Campaign::STATUS_DELIVERING) {//如果现在处于投放中的
                $campaign->old_pause_status = $campaign->pause_status;//保存上次状态
                if (!array_key_exists('pause_status', $params)) {
                    $campaign->pause_status = Campaign::PAUSE_STATUS_PLATFORM;//平台暂停
                }
            }
            if ($oldStatus != Campaign::STATUS_SUSPENDED) {
                $campaign->stop_time = date('Y-m-d H:i:s');
            }
        }
        $campaign->status = $status;
        $campaign->old_status = $oldStatus;
        if ($status == Campaign::STATUS_DELIVERING) {
            if (in_array($oldStatus, [Campaign::STATUS_PENDING_APPROVAL, Campaign::STATUS_REJECTED])) {
                //待投放
                $campaign->approve_time = date('Y-m-d H:i:s');
                if (isset(Auth::user()->user_id)) {
                    $campaign->updated_uid = Auth::user()->user_id;
                }

                CampaignRevenueHistory::storeCampaignHistoryRevenue([
                    'campaignid' => $campaignId,
                    'time' => gmdate('Y-m-d H:i:s'),
                    'history_revenue' => $campaign->revenue,
                    'current_revenue' => $campaign->revenue
                ]);
            } elseif ($oldStatus == Campaign::STATUS_STOP_DELIVERING) {
                $campaign->status = Campaign::STATUS_SUSPENDED;
            }
        }
        if ($status == Campaign::STATUS_REJECTED && $oldStatus == Campaign::STATUS_PENDING_APPROVAL) {
            //不通过审核
            $campaign->approve_time = date('Y-m-d H:i:s');
            if (isset(Auth::user()->user_id)) {
                $campaign->updated_uid = Auth::user()->user_id;
            }
        }
        if (isset(Auth::user()->user_id)) {
            $campaign->updated_uid = Auth::user()->user_id;
            $campaign->operation_time = date('Y-m-d H:i:s');
        }
        $effect = $campaign->save();
        LogHelper::info('campaign save status: ' . $effect);
        if (!$effect) {
            return 5001;
        }

        //暂停，继续投放，停止投放，重新投放记录操作日志
        if (isset(Auth::user()->user_id) && true == $noLog) {
            if (($oldStatus == Campaign::STATUS_DELIVERING || $oldStatus == Campaign::STATUS_SUSPENDED)
                && $status == Campaign::STATUS_SUSPENDED
            ) {
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaignId,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => Auth::user()->contact_name,
                    'message' => self::formatWaring(6019),
                ]);
            } elseif ($oldStatus == Campaign::STATUS_SUSPENDED && $status == Campaign::STATUS_STOP_DELIVERING) {
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaignId,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => Auth::user()->contact_name,
                    'message' => self::formatWaring(6021),
                ]);
            } elseif ($oldStatus == Campaign::STATUS_STOP_DELIVERING && $status == Campaign::STATUS_DELIVERING) {
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaignId,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => Auth::user()->contact_name,
                    'message' => self::formatWaring(6022),
                ]);
            } elseif ($oldStatus == Campaign::STATUS_SUSPENDED && $status == Campaign::STATUS_DELIVERING) {
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaignId,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => Auth::user()->contact_name,
                    'message' => self::formatWaring(6020),
                ]);
            }
        }
        return true;
    }

    /**
     * 添加Banner的关系链
     * @param $bannerId
     */
    public static function attachBannerRelationChain($bannerId)
    {
        DB::transaction(function ($bannerId) use ($bannerId) {
            $banner = Banner::find($bannerId);
            $campaign = $banner->campaign()->getQuery()
                ->join('appinfos as app', function ($join) {
                    $join->on('app.app_id', '=', 'campaigns.campaignname');
                    $join->on('app.platform', '=', 'campaigns.platform');
                })
                ->select('campaigns.*', 'app.ad_spec')
                ->first();

            DB::setFetchMode(\PDO::FETCH_ASSOC);
//            $category = DB::table('category')
//                ->where('category_id', $banner->category)
//                ->where('affiliateid', $banner->affiliateid)
//                ->pluck('parent');
            $prefix = \DB::getTablePrefix();
            //先解绑
            $banner->zones()->detach();
            $adType = Campaign::getZoneTypeToAdType($campaign->ad_type);

            //再绑定
            $affiliate = Affiliate::find($banner->affiliateid);
            if ($affiliate->mode == Affiliate::MODE_ARTIFICIAL_DELIVERY) {
                //人工投放关联安卓流量广告位
                $zonesQuery = $affiliate->zones()
                    ->where('status', Zone::STATUS_OPEN_IN)
                    ->where('type', Zone::TYPE_FLOW)
                    ->where('platform', Campaign::PLATFORM_ANDROID);
            } elseif ($affiliate->mode == Affiliate::MODE_ADX) {
                //ADX投放关联流量广告位
                $zonesQuery = $affiliate->zones()
                    ->where('status', Zone::STATUS_OPEN_IN)
                    ->where('type', Zone::TYPE_FLOW)
                    ->whereRaw("(platform & {$campaign->platform}) > 0");
            } else {
                $zonesQuery = $affiliate->zones()
                    ->whereRaw("(platform & {$campaign->platform}) > 0")
                    ->where('rank_limit', '>=', $banner->app_rank)
                    ->where('status', '=', Zone::STATUS_OPEN_IN)
                    ->whereIn('ad_type', $adType);
                //如果是指定类型之外的，则加上分类的限制
                if (in_array($campaign->ad_type, Config::get('biddingos.withCategory'))) {
                    $zonesQuery->whereRaw("has_intersection({$prefix}zones.oac_category_id,
                    '{$banner->category}') > 0");
                }
            }
            $zones = $zonesQuery->lists('zoneid');
            $zones = json_decode(json_encode($zones), true);
            //如果是 AppStore 或者还有其它符合条件的类型，流量广告位也可以使用
            if (in_array($campaign->ad_type, [
                Campaign::AD_TYPE_APP_STORE
            ])) {
                $select = DB::table('zones')
                    ->where('affiliateid', $banner->affiliateid)
                    ->where('type', Zone::TYPE_FLOW);
                switch ($campaign->ad_type) {
                    case Campaign::AD_TYPE_APP_STORE:
                        $select->whereIn('platform', [
                            Campaign::PLATFORM_IPHONE_COPYRIGHT,
                            Campaign::PLATFORM_IPAD
                        ]);
                        break;
                    default:
                        break;
                }

                $other = $select->select('zoneid')->get();
                if (!empty($other)) {
                    $otherData = [];
                    foreach ($other as $key => $val) {
                        $otherData[] = $val['zoneid'];
                    }
                    $zones = array_merge($zones, $otherData);
                    $zones = array_unique($zones);
                }
            }

            $banner->zones()->sync($zones);
            $campaign->zones()->detach($zones);
            $campaign->zones()->sync($zones);
        });
    }

    /**
     * 广告位解除关联
     * @param $bannerId
     * @return bool
     */
    public static function campaignZonesDetach($bannerId)
    {
        $b = Banner::find($bannerId);
        $affiliateId = $b->affiliateid;
        $trafficker = Affiliate::find($affiliateId);
        $banner = DB::table('banners')
            ->leftJoin('campaigns', 'banners.campaignid', '=', 'campaigns.campaignid')
            ->where('banners.affiliateid', $affiliateId)
            ->where('banners.bannerid', $bannerId)
            ->first([
                'campaigns.campaignid',
                'campaigns.platform',
                'banners.app_rank',
                'banners.category']);
        $category = Category::whereMulti(['category_id' => $banner->category,
            'affiliateid' => $affiliateId])->first();
        $zones = $trafficker->zones()
            ->whereIn('oac_category_id', array(
                0,
                $category ? $category->parent : 0,
                $category ? $category->category_id : 0,
            ))
            ->whereRaw("(platform & {$banner->platform}) > 0")
            ->where('status', '=', 0)
            ->lists("zoneid");

        $zones = json_decode(json_encode($zones), true);
        if ($zones) {
            PlacementZoneAssoc::where('placement_id', $banner->campaignid)
                ->whereIn('zone_id', $zones)->delete();
            LogHelper::info('delete PlacementZoneAssoc ' . $banner->campaignid .
                ' zone_id' . implode(',', $zones));
        }
        return true;
    }

    /**
     * 修改广告等级
     * @param $bannerId
     * @param $value
     * @param $affiliateId
     * @return int
     */
    public static function bannerModify($field, $value, $bannerId, $affiliateId)
    {
        $result = Banner::where('bannerid', $bannerId)->update([
            $field => $value
        ]);
        if (!$result) {
            return 5001;
        }
        DB::transaction(function () use ($bannerId, $affiliateId, $field) {
            $b = Banner::find($bannerId);
            $b->zones()->detach();
            self::campaignZonesDetach($b->bannerid);

            $campaign = Campaign::find($b->campaignid);
            if ($b->status == Banner::STATUS_PUT_IN && $campaign->status == Campaign::STATUS_DELIVERING) {
                self::attachBannerRelationChain($b->bannerid);
                if ($field == 'appinfos_app_rank' || $field == 'category') {
                    $banners = Banner::whereMulti(['campaignid' => $b->campaignid,
                        'affiliateid' => $affiliateId
                    ])->first();
                    $banners->buildBannerText();
                    $banners->save();
                }
            }
        });
        if ($result instanceof Exception) {
            return 5001;
        }
        return true;
    }

    /**
     * 修改banner的状态
     * @param $bannerId
     * @param $status
     * @return bool|int
     */
    public static function modifyBannerStatus(
        $bannerId,
        $status,
        $isManager = false,
        $params = []
    ) {
    

        $banner = Banner::find($bannerId);
        if (!$banner) {
            return 5002;
        }
        switch ($status) {
            //待验证|验证失败|暂停|等待投放 -> 验证通过（投放）
            //(人工投放状态为 6待媒体审核的也可以转成投放)
            case Banner::STATUS_PUT_IN:
                if (!in_array($banner->status, [Banner::STATUS_SUSPENDED,
                    Banner::STATUS_PENDING_PUT,
                    Banner::STATUS_PENDING_MEDIA])
                ) {
                    return 5028;
                }
                //广告主不能进行投放或者暂停操作
                if (!$isManager && $banner->status == Banner::STATUS_SUSPENDED
                    && $banner->pause_status != Banner::PAUSE_STATUS_MEDIA_MANUAL
                ) {
                    return 5028;
                }
                break;
            case Banner::STATUS_SUSPENDED://投放中 -> 暂停
                if (!in_array($banner->status, [Banner::STATUS_PUT_IN, Banner::STATUS_SUSPENDED])) {
                    return 5028;
                }
                break;
            case Banner::STATUS_PENDING_MEDIA:
                if (!in_array($banner->status, [Banner::STATUS_PENDING_PUT,
                    Banner::STATUS_NOT_ACCEPTED, Banner::STATUS_PENDING_SUBMIT])
                ) {
                    return 5028;
                }
                break;
            case Banner::STATUS_PENDING_PUT:
                if (!in_array($banner->status, [Banner::STATUS_PENDING_MEDIA])) {
                    return 5028;
                }
                break;
        }

        if ($status == Banner::STATUS_SUSPENDED) {
            //暂停则删除关联
            self::deAttachBannerRelationChain($bannerId);
            $banner->an_status = $banner->status;
            $banner->an_pause_status = $banner->pause_status;
            if ($isManager) {
                //是管理员，则增加pause状态标识
                $banner->pause_status = isset($params['pause_status']) ? $params['pause_status'] :
                    Banner::PAUSE_STATUS_PLATFORM;
            } elseif (Auth::user()->account->isTrafficker() &&
                $banner->pause_status == Banner::PAUSE_STATUS_EXCEED_DAY_LIMIT
            ) {
                //达到日限额后，媒体点击暂停变为媒体暂停
                $banner->pause_status = Banner::PAUSE_STATUS_MEDIA_MANUAL;
            }
            if ($banner->an_status != Banner::STATUS_SUSPENDED) {
                $banner->stop_time = date('Y-m-d H:i:s');
            }
        } elseif ($status == Banner::STATUS_PUT_IN) {
            $campaigns = $banner->campaign;
            //如果是 banner 的话，没有 AppId,则自动生成一个 AppId，还附加上 tackerId
            if ($campaigns) {
//                if (in_array($campaigns->ad_type, Config::get('biddingos.autoAppid')) && empty($banner->app_id)) {
//                    $appId = date('Ymd') . str_random(10);
//                    $banner->app_id = $appId;
//                }

                //加上 tackerId
                $tracker = $banner->tracker()->first();
                if (empty($tracker)) {
                    $tracker = Tracker::store($campaigns->campaignid, $campaigns->clientid, $bannerId);
                    $tracker->campaigns()->attach($campaigns->campaignid, [
                        'status' => CampaignTracker::STATUS_CONFIRM,
                    ]);
                }
            }
            //修改bannerText
            $banner->buildBannerText();
            $banner->save();
            //投放
            self::attachBannerRelationChain($bannerId);
            $banner->pause_status = Banner::PAUSE_STATUS_MEDIA_MANUAL;

        }
        $banner->status = $status;
        //媒体暂停->平台暂停->平台继续投放->媒体暂停
        if ($status == Banner::STATUS_PUT_IN) {
            if ($banner->an_pause_status == Banner::PAUSE_STATUS_MEDIA_MANUAL &&
                $banner->an_status == Banner::STATUS_SUSPENDED
            ) {
                if ($isManager) {
                    $banner->status = Banner::STATUS_SUSPENDED;
                }
            }
        }
        $banner->updated_uid = isset(Auth::user()->user_id) ? Auth::user()->user_id : null;
        $banner->updated = date('Y-m-d H:i:s');
        $effect = $banner->save();
        if (!$effect) {
            return 5001;
        }
        return true;
    }

    /**
     * 删除Banner的关系链
     * @param $bannerId
     */
    public static function deAttachBannerRelationChain($bannerId)
    {
        DB::transaction(function ($bannerId) use ($bannerId) {
            $banner = Banner::find($bannerId);
            $campaign = $banner->campaign()->getQuery()
                ->where('campaignid', $banner->campaignid)
                ->first();
            $banner->zones()->detach();
            $zones = array_fetch($banner->zones()->get(array('zoneid'))->only('zoneid'), 'zoneid');
            if (!empty($zones)) {
                $campaign->zones()->sync($zones);
            }
        });
    }



    /**
     * 获取包参数
     * @param $package
     * @return array|null
     */
    public static function getPackageParams($package)
    {
        $package = json_decode($package, true);
        if ($package) {
            if (empty($package['package_id'])) {
                //修改替换包
                $reserve = [];
                $reserve['filesize'] = $package['filesize'];
                $reserve['md5'] = $package['md5_file'];
                $reserve['versionName'] = $package['versionName'];
                $reserve['packageName'] = $package['packageName'];
                $reserve['versionCode'] = $package['versionCode'];
                $reserve['app_support_os'] = $package['app_support_os'];
                $reserve['app_crc32'] = isset($package['app_crc32']) ? $package['app_crc32'] : null;
                $reserve['app_sign'] = isset($package['app_sign']) ? $package['app_sign'] : null;
                $reserve['h8192_md5'] = isset($package['h8192_md5']) ? $package['h8192_md5'] : null;
                $reserve = json_encode($reserve);
                LogHelper::info($reserve);
                return [
                    'reserve' => $reserve,
                    'package_id' => $package['package_id'],
                    'file_size' => $package['filesize'],
                    'md5' => $package['md5_file'],
                    'version_name' => $package['versionName'],
                    'package_name' => $package['packageName'],
                    'version_code' => $package['versionCode'],
                    'app_support_os' => $package['app_support_os'],
                    'app_crc32' => isset($package['app_crc32']) ? $package['app_crc32'] : null,
                    'app_sign' => isset($package['app_sign']) ? $package['app_sign'] : null,
                    'h8192_md5' => isset($package['h8192_md5']) ? $package['h8192_md5'] : null,
                    'path' => $package['path'],
                    'real_name' => $package['real_name'],
                ];
            } else {
                //修改没有替换包
                return [
                    'package_id' => $package['package_id'],
                ];
            }
        } else {
            return null;
        }
    }

    /**
     * 获取视频素材数据
     * @param $params
     * @return mixed|null
     */
    private static function getVideoMaterial($params)
    {
        $videoInfo = json_decode($params, true);
        if (empty($videoInfo['id'])) {
            return $videoInfo;
        } else {
            $video = CampaignVideo::where('id', $videoInfo['id'])->first();
            if ($video) {
                $reserve = json_decode($video->reserve, true);
                return $reserve;
            }
        }
        return null;
    }

    /**
     * 获取包素材数据
     * @param $params
     * @return array|null
     */
    public static function getPackageMaterial($params)
    {
        if (empty($params['package_id'])) {
            return $params;
        } else {
            $attach = AttachFile::where('id', $params['package_id'])->first();
            if ($attach) {
                $reserve = json_decode($attach->reserve);
                return [
                    'reserve' => $attach->reserve,
                    'package_id' => $attach->id,
                    'file_size' => $reserve->filesize,
                    'md5' => $attach->hash,
                    'version_name' => $reserve->versionName,
                    'package_name' => $attach->package_name,
                    'version_code' => $reserve->versionCode,
                    'app_support_os' => isset($reserve->app_support_os) ?
                        $reserve->app_support_os : "",
                    'app_crc32' => isset($reserve->app_crc32) ?
                        $reserve->app_crc32 : null,
                    'app_sign' => isset($reserve->app_sign) ?
                        $reserve->app_sign : null,
                    'h8192_md5' => isset($reserve->h8192_md5) ?
                        $reserve->h8192_md5 : null,
                    'path' => $attach->file,
                    'real_name' => $attach->real_name,
                ];
            } else {
                return null;
            }
        }
    }

    /**
     * 获取广告列
     * @param $fields
     * @return array
     */
    public static function getColumnList($fields)
    {
        $list = [];
        foreach ($fields as $field => $label) {
            $item['field'] = $field;
            $item['title'] = Campaign::attributeLabels()[$field];
            $item['column_set'] = [];
            $item['label'] = $label;
            $module = Auth::user()->account->isBroker() ? Account::TYPE_BROKER : Account::TYPE_ADVERTISER;
            if (in_array($field, Campaign::getSortableFields($module))) {
                array_push($item['column_set'], 'sortable');
            }
            $defaultSortField = Campaign::getDefaultSortField();
            if (isset($defaultSortField[$field])) {
                array_push($item['column_set'], $defaultSortField[$field]);
            }
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 获取关键字数
     * @param $campaignIds
     * @return mixed
     */
    public static function getCampaignKeywordsCount($campaignIds)
    {
        if (!is_array($campaignIds)) {
            $campaignIds = array($campaignIds);
        }
        if (Auth::user()->account->isManager()) {
            $data = AdZoneKeyword::whereIn('campaignid', $campaignIds)
                ->groupBy('campaignid')
                ->select('campaignid', DB::raw('count(1) as cnt'))
                ->get()->toArray();
        } else {
            $data = AdZoneKeyword::where('operator', 0)
                ->whereIn('campaignid', $campaignIds)
                ->groupBy('campaignid')
                ->select('campaignid', DB::raw('count(1) as cnt'))
                ->get()->toArray();
        }
        return $data;
    }

    /**获取安装包地址
     * @param $attachFileId
     * @return string
     *
     */
    public static function attachFileLink($attachFileId)
    {
        $host = Config::get('filesystems.f_web');
        return $host . '/attach/channel/dl?aid=' . $attachFileId;
    }

    /**
     * 创建banner
     * @param $campaignId
     * @param $affiliateId
     * @param array $otherParams
     * @return static
     */
    public static function getBannerOrCreate(
        $campaignId,
        $affiliateId,
        $status = Banner::STATUS_PENDING_MEDIA,
        $otherParams = []
    ) {
        $campaign = Campaign::find($campaignId);
        $banner = Banner::where('campaignid', $campaignId)->where('affiliateid', $affiliateId)->first();
        if (!$banner) {
            $when = " CASE ";
            foreach (Campaign::getRevenueTypeSort() as $k => $v) {
                $when .= ' WHEN revenue_type = ' . $k . ' THEN ' . $v;
            }
            $when .= " END AS sort_revenue_type ";

            //投放取媒体最大计费类型
            $revenue_type = AffiliateExtend::where('affiliateid', $affiliateId)
                ->where('ad_type', $campaign->ad_type)
                ->whereIn('revenue_type', Campaign::getCRevenueTypeToARevenueType($campaign->revenue_type))
                ->orderBy('sort_revenue_type', 'ASC')
                ->select('revenue_type', DB::raw($when))
                ->first();

            $param = [
                'campaignid' => $campaignId,
                'contenttype' => 'app',
                'storagetype' => 'app',
                'htmltemplate' => '',
                'htmlcache' => '',
                'description' => $campaign->campaignname,
                'width' => 0,
                'height' => 0,
                'url' => '',
                'target' => '',
                'parameters' => 'N;',
                'compiledlimitation' => '',
                'append' => '',
                'prepend' => '',
                'affiliateid' => $affiliateId,
                'ext_bannertype' => 'bannerTypeApp:oxApp:genericApp',
                'revenue_type' => $revenue_type->revenue_type,
                'status' => $status
            ];
            if (!empty($otherParams)) {
                foreach ($otherParams as $k => $v) {
                    if (!in_array($k, ['status'])) {
                        $param[$k] = $v;
                    }
                }
            }
            $banner = Banner::create($param);
        }
        return $banner;
    }

    /**
     * 使因日限额或余额不足等原因导致暂停的应用恢复投放
     * @param int $status 应用暂停状态；1 = 日限额不足, 2 = 余额不足
     * @param int $client_id 【可选】广告主ID；如果传入此参数，则只修改此广告主的应用
     * @return array 返回被操作的campaignid
     */
    public static function recoverActive($status, $client_id = null, $noLog = true)
    {
        $select = DB::table('campaigns as c')
                ->where('status', Campaign::STATUS_SUSPENDED)
                ->where('pause_status', $status)
                ->select('c.campaignid');
        if ($client_id) {
            $select->join('clients as cl', 'cl.clientid', '=', 'c.clientid')
                ->where('cl.clientid', $client_id);
        }
        $data = $select->get();
        $campaignIds = [];
        if (!empty($data)) {
            foreach ($data as $temp) {
                $checkStatus = self::modifyStatus($temp->campaignid, 0, [], $noLog);
                if (true === $checkStatus) {
                    $campaignIds[] = $temp->campaignid;
                }
            }
            //更新pause_status状态
            if (count($campaignIds)) {
                DB::table('campaigns')
                    ->whereIn('campaignid', $campaignIds)
                    ->update([
                        'status' => Campaign::STATUS_DELIVERING,
                        'pause_status' => Campaign::PAUSE_STATUS_PLATFORM,
                    ]);
            }
        }
        return $campaignIds;
    }
    
    /**
     * 更新媒体价及广告主出价
     * @param $bannerId
     */
    public static function updateBannerBilling($bannerId, $mediaPriceRefresh = false)
    {
        //修改计费价，广告主出价时刷新
        if ($mediaPriceRefresh) {
            $banner = Banner::find($bannerId);
            $default_media_price = self::calDefaultPrice($bannerId);
            if ($banner->af_manual_price > $default_media_price) {
                $banner->af_manual_price = 0;
                $banner->save();
            }
        }
        
        $prefix = DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('banners as b')->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('affiliates as aff', 'b.affiliateid', '=', 'aff.affiliateid')
            ->orderBy('b.bannerid')
            ->select(
                'b.bannerid',
                DB::raw("IF({$prefix}b.revenue_price > 0,{$prefix}b.revenue_price,{$prefix}c.revenue) as revenue"),
                'c.revenue_type as c_rtype',
                'b.revenue_type as b_rtype',
                'b.af_manual_price',
                DB::raw("IFNULL({$prefix}aff.income_rate, 100) as income_rate"),
                DB::raw("IFNULL({$prefix}c.rate, 100) as rate")
            );
        if (!empty($bannerId)) {
            if (is_array($bannerId)) {
                $select->whereIn('bannerid', $bannerId);
            } else {
                $select->where('bannerid', $bannerId);
            }
        }
        $rows = $select->get();

        foreach ($rows as $row) {
            // CPC 2位小数，CPD 1位小数
            $decimals = Config::get('biddingos.jsDefaultInit.'.$row['b_rtype'].'.decimal');
            // af_manual_price优先, C转D
            if ($row['af_manual_price'] > 0) {
                $afIncome = $row['af_manual_price'];
                //D->C,A->C计算方式一样
            } elseif ($row['af_manual_price'] == -1) {
                $afIncome = 0;
            } elseif (($row['c_rtype'] == Campaign::REVENUE_TYPE_CPD || $row['c_rtype'] == Campaign::REVENUE_TYPE_CPA)
                && $row['b_rtype'] == Campaign::REVENUE_TYPE_CPC) {
                $afIncome = ($row['income_rate'] * $row['rate'] * $row['revenue'] / 10000) / Affiliate::D_TO_C_NUM;
            } else {
                $afIncome = $row['income_rate'] * $row['rate'] * $row['revenue'] / 10000;
            }

            $afIncome = Formatter::asDecimal($afIncome, $decimals);
            $arr = [
                'bannerid' => $row['bannerid'],
                'revenue' => $row['revenue'],
                'af_income' => $afIncome
            ];
            $sql = DB::table('banners_billing')->where('bannerid', $row['bannerid']);
            $count = $sql->count();
            if ($count > 0) {
                $sql->update($arr);
            } else {
                DB::table('banners_billing')->insert($arr);
            }
        }
    }

    /**
     * 根据 bannerid取得默认的媒体价
     * @param integer $bannerId
     * @return array
     */
    public static function calDefaultPrice($bannerId)
    {
        $prefix = DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('banners as b')->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('affiliates as aff', 'b.affiliateid', '=', 'aff.affiliateid')
            ->orderBy('b.bannerid')
            ->select(
                'b.bannerid',
                'b.revenue_price',
                'c.revenue',
                'c.revenue_type as c_rtype',
                'b.revenue_type as b_rtype',
                'b.af_manual_price',
                DB::raw("IFNULL({$prefix}aff.income_rate, 100) as income_rate"),
                DB::raw("IFNULL({$prefix}c.rate, 100) as rate")
            );
        if (!empty($bannerId)) {
            $select->where('bannerid', $bannerId);
        }
        $row = $select->first();

        $decimals = Config::get('biddingos.jsDefaultInit.'.$row['b_rtype'].'.decimal');
        // af_manual_price优先, D->C,A->C
        if (($row['c_rtype'] == Campaign::REVENUE_TYPE_CPD || $row['c_rtype'] == Campaign::REVENUE_TYPE_CPA)
            && $row['b_rtype'] == Campaign::REVENUE_TYPE_CPC) {
            if ($row['revenue_price'] > 0) {
                $revenuePrice = ($row['income_rate'] * $row['rate'] * $row['revenue_price'] / 10000) *
                    (1 / Affiliate::D_TO_C_NUM);
            } else {
                $revenuePrice = ($row['income_rate'] * $row['rate'] * $row['revenue'] / 10000) *
                    (1 / Affiliate::D_TO_C_NUM);
            }
        } else {
            if ($row['revenue_price'] > 0) {
                $revenuePrice = $row['income_rate'] * $row['rate'] * $row['revenue_price'] / 10000;
            } else {
                $revenuePrice = $row['income_rate'] * $row['rate'] * $row['revenue'] / 10000;
            }
        }

        $revenuePrice = Formatter::asDecimal($revenuePrice, $decimals);
        return $revenuePrice;
    }


    public static function getFilterCondition($select, $params, $isManager = true)
    {
        if (isset($params['status']) && $params['status'] != '') {
            if (in_array($params['status'], array_keys(Campaign::getStatusLabels()))) {
                $select->where('campaigns.status', $params['status']);
            } else {
                $select->where('campaigns.status', Campaign::STATUS_SUSPENDED);
                switch ($params['status']) {
                    case Campaign::FILTER_PAUSE_STATUS_PLATFORM:
                        if ($isManager) {
                            $select->where('campaigns.pause_status', Campaign::PAUSE_STATUS_PLATFORM);
                        } else {
                            $select->whereIn('campaigns.pause_status', [
                                Campaign::PAUSE_STATUS_PLATFORM, Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM
                            ]);
                        }
                        break;
                    case Campaign::FILTER_PAUSE_STATUS_DAY_LIMIT:
                        $select->where('campaigns.pause_status', Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT);
                        break;
                    case Campaign::FILTER_PAUSE_STATUS_NOT_ENOUGH:
                        $select->where('campaigns.pause_status', Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH);
                        break;
                    case Campaign::FILTER_PAUSE_STATUS_TOTAL_LIMIT:
                        $select->where('campaigns.pause_status', Campaign::PAUSE_STATUS_EXCEED_TOTAL_LIMIT);
                        break;
                    case Campaign::FILTER_PAUSE_STATUS_DAY_LIMIT_PROGRAM:
                        $select->where('campaigns.pause_status', Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM);
                        break;
                }
            }
        }

        if (isset($params['revenue']) && $params['revenue'] != "") {
            $select->where('campaigns.revenue', '=', $params['revenue']);
        }

        if (isset($params['day_limit']) && $params['day_limit'] != "") {
            $select->where('campaigns.day_limit', '=', $params['day_limit']);
        }

        return $select;
    }

    /**
     * 根据传入计费类型获取计费类型列表
     * @param $revenueType
     * @return array
     */
    public static function getRevenueTypeList($revenueType)
    {
        //获取所有计费类型
        $revenueTypeList = Campaign::getRevenueTypeLabels();
        //将计费类型二进制并翻转
        $list = [];
        foreach ($revenueTypeList as $k => $v) {
            if ($k & $revenueType) {
                $list[] = $k;
            }
        }
        return $list;
    }

    /**
     * 等价广告包
     * @param $key
     */
    public static function attachEquivalencePackageName($key)
    {
        DB::setFetchMode(\PDO::FETCH_CLASS);
        $campaignIds = Campaign::where('equivalence', $key)->get(['campaignid']);
        $prefix = \DB::getTablePrefix();
        $result = \DB::table('banners AS b')
            ->leftJoin('campaigns AS c', 'b.campaignid', '=', 'c.campaignid')
            ->leftJoin('attach_files AS a', 'b.attach_file_id', '=', 'a.id')
            ->select('equivalence', DB::raw("GROUP_CONCAT(DISTINCT {$prefix}a.package_name) AS package_name"))
            ->whereIn('c.campaignid', $campaignIds)
            ->first();

        $count = EquivalenceAssoc::where('equivalence', $key)->count();
        //生成等价广告包
        if ($count>0) {
            EquivalenceAssoc::where('equivalence', $key)->update([
                'package_name' => $result->package_name,
            ]);
        } else {
            $equivalenceAssoc = new EquivalenceAssoc();
            $equivalenceAssoc->equivalence = $result->equivalence;
            $equivalenceAssoc->package_name = $result->package_name;
            $equivalenceAssoc->save();
        }
    }

    /**
     * 获取未关联广告位
     * @return array
     */
    public static function getAttachRelationBanners()
    {
        \DB::setFetchMode(\PDO::FETCH_ASSOC);

        $bannerIds = \DB::table('ad_zone_assoc')->distinct()->get(['ad_id']);
        //获取所有未关联广告位
        $data = \DB::table('banners AS b')
            ->join('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->join('appinfos AS app', 'app.app_id', '=', 'c.campaignname')
            ->join('affiliates AS aff', 'aff.affiliateid', '=', 'b.affiliateid')
            ->select('b.bannerid', 'b.affiliateid', 'c.campaignid', 'app.app_name', 'aff.brief_name')
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->whereNotIn('b.bannerid', $bannerIds)
            ->get();

        //获取所有应用市场未关联流量广告位
        $results = \DB::table('banners AS b')
            ->join('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->join('appinfos AS app', 'app.app_id', '=', 'c.campaignname')
            ->join('affiliates AS aff', 'aff.affiliateid', '=', 'b.affiliateid')
            ->select(
                'b.bannerid',
                'b.affiliateid',
                'c.campaignid',
                'app.app_name',
                'aff.brief_name',
                'b.category',
                'c.platform',
                'b.app_rank'
            )
            ->where('b.status', Banner::STATUS_PUT_IN)
//            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('c.ad_type', Campaign::AD_TYPE_APP_MARKET)
            ->where('aff.mode', '<>', Affiliate::MODE_ARTIFICIAL_DELIVERY)
            ->get();
        $list = [];
        foreach ($results as $item) {
            $zones = \DB::table('zones')
                ->where('affiliateid', $item['affiliateid'])
                ->whereRaw("(platform & {$item['platform']}) > 0")
                ->where('rank_limit', '>=', $item['app_rank'])
                ->where('status', Zone::STATUS_OPEN_IN)
                ->where('ad_type', Campaign::AD_TYPE_APP_MARKET)
                ->whereIn('oac_category_id', [0, $item['category']])
                ->select('zoneid')
                ->get();

            $count = AdZoneAssoc::whereIn('zone_id', $zones)
                ->where('ad_id', $item['bannerid'])
                ->count();
            if ($count != count($zones)) {
                $list[] = [
                    'bannerid' => $item['bannerid'],
                    'affiliateid' => $item['affiliateid'],
                    'campaignid' => $item['campaignid'],
                    'app_name' => $item['app_name'],
                    'brief_name' => $item['brief_name'],
                ];
            }
        }

        return array_merge($list, $data);
    }

    /**
     * 格式化配置信息
     * @param $code
     * @param array $args
     * @return string
     */
    public static function formatWaring($code, $args = [])
    {
        $msg = Config::get('error');
        return !empty($args) ? vsprintf($msg[$code], $args) : sprintf($msg[$code]);
    }
    
    //通过campaignid获取广告信的信息
    public static function getClientInfoByClientID($clientID)
    {
        $row = Client::where('clientid', $clientID)->first();
        return $row;
    }

    /**
     * 获取每日消耗
     * @param $campaignId
     * @param $revenueType
     * @return int
     */
    public static function getDailyConsume($campaignId, $revenueType)
    {
        $date = date('Y-m-d');
        // 数据库为UTC时间，需减去8小时
        $startDateTime = date("Y-m-d H:i:s", strtotime('-8 hour', strtotime($date)));
        $endDateTime = date("Y-m-d H:i:s", strtotime('+16 hour', strtotime($date)));

        if ($revenueType == Campaign::REVENUE_TYPE_CPA) {
            $expenseLog = ExpenseLog::where('campaignid', $campaignId)
                ->where('actiontime', '>=', $startDateTime)
                ->where('actiontime', '<', $endDateTime)
                ->select(DB::raw("SUM(af_income) AS total_revenue"))
                ->first();
            $revenueToday = $expenseLog->total_revenue ? $expenseLog->total_revenue : 0;
        } else {
            $delivery = DeliveryLog::where('campaignid', $campaignId)
                ->where('actiontime', '>=', $startDateTime)
                ->where('actiontime', '<', $endDateTime)
                ->select(DB::raw("SUM(price) AS total_revenue"))
                ->first();
            $revenueToday = $delivery->total_revenue ? $delivery->total_revenue : 0;
        }
        return $revenueToday;
    }

    /**
     * 获取总消耗，总预算
     * @param null $campaignId
     * @return mixed
     */
    public static function getTotalConsume($campaignId = null)
    {
        $select = DB::table('data_hourly_daily')
            ->select('campaign_id AS campaignid', DB::raw("SUM(total_revenue) AS total_revenue"));
        if ($campaignId) {
            if (is_array($campaignId)) {
                $result = $select->whereIn('campaign_id', $campaignId)
                    ->groupBy('campaignid')
                    ->get();
            } else {
                $result = $select->where('campaign_id', $campaignId)
                    ->first();
            }
        } else {
            $result = $select->groupBy('campaignid')
                ->get();
        }
        return $result;
    }

    /**
     * 拒绝审核记录日志
     * @param $campaignId
     * @param $approveComment
     */
    public static function rejectApproveLog($campaignId, $approveComment)
    {
        OperationLog::store([
            'category' => OperationLog::CATEGORY_CAMPAIGN,
            'target_id' => $campaignId,
            'type' => OperationLog::TYPE_MANUAL,
            'operator' => Auth::user()->contact_name,
            'message' => CampaignService::formatWaring(6016, [$approveComment]),
        ]);
    }

    /**
     * 通过审核记录日志
     * @param $campaignId
     */
    public static function approveLog($campaignId)
    {
        OperationLog::store([
            'category' => OperationLog::CATEGORY_CAMPAIGN,
            'target_id' => $campaignId,
            'type' => OperationLog::TYPE_MANUAL,
            'operator' => Auth::user()->contact_name,
            'message' => CampaignService::formatWaring(6015),
        ]);
    }

    /**
     * 获取附件ID
     * @param $campaignId
     * @param int $flag
     * @param int $attachId
     * @return int
     *
     */
    public static function getAttachFileId($campaignId, $flag = AttachFile::FLAG_PENDING_APPROVAL, $attachId = 0)
    {
        $select = DB::table('attach_files')
            ->where('campaignid', $campaignId)
            ->where('flag', $flag);
        if (0 < $attachId) {
            $select->where('id', $attachId);//@codeCoverageIgnore
        }//@codeCoverageIgnore
        $attachData = $select->select('id')->first();
        if (!empty($attachData)) {
            return $attachData->id;//@codeCoverageIgnore
        }
        return 0;
    }

    /**
     * 检查url是否是有效app_store link
     * @param $url
     * @return bool
     */
    public static function validURL($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);    //返回响应头信息
        curl_setopt($ch, CURLOPT_NOBODY, true);    //返回响应正文
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //返回数据不直接输出
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X)
            AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1');    //指定客户端
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $header = curl_exec($ch);    //执行并存储结果
        curl_close($ch);
        if (empty($header)) {
            return false;
        } else {
            $headArr = explode("\r\n", $header);
            foreach ($headArr as $loop) {
                if (strpos($loop, "Location") !== false) {
                    $location = trim(substr($loop, 10));
                }
            }
            if (isset($location)) {
                if (self::isAppStoreLink($location)) {
                    return true;
                } else {
                    return self::validURL($location);
                }
            } else {
                try {
                    $contents = file_get_contents($url);
                    if (empty($contents)) {
                        return false;
                    } else {
                        if (strpos($contents, 'itunes.apple.com') !== false) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                } catch (Exception $e) {
                    print_r($e->getMessage());
                    return false;
                }
            }
        }
    }

    /**
     * 是否app_store链接
     * @param $url
     * @return bool
     */
    private static function isAppStoreLink($url)
    {
        if (strpos($url, 'itunes.apple.com') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /* 获取AppInfo信息
     * @param $applicationId
     * @return null|string
     */
    public static function getAppIdInfo($applicationId)
    {
        $url = Config::get('biddingos.appid_url') . $applicationId;
        $result = HttpClientHelper::call($url);
        $result = json_decode($result, true);
        if ($result && $result['resultCount'] > 0) {
            $result = $result['results'][0];

            return json_encode([
                'bundleId' => $result['bundleId'],
                'version' => $result['version'],
                'fileSizeBytes' => $result['fileSizeBytes'],
                'minimumOsVersion' => $result['minimumOsVersion'],
            ]);
        }
        return null;
    }
}
