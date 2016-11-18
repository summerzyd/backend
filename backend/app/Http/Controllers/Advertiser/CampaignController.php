<?php

namespace App\Http\Controllers\Advertiser;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Components\HouseAd\HouseAdFactory;
use App\Models\AdZoneKeyword;
use App\Models\AdZonePrice;
use App\Models\Affiliate;
use App\Models\AppInfo;
use App\Models\AttachFile;
use App\Models\Banner;
use App\Models\CampaignImage;
use App\Models\CampaignVideo;
use App\Models\OperationLog;
use App\Models\Product;
use App\Models\User;
use App\Models\Zone;
use App\Services\ZoneService;
use Auth;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Components\Config;
use App\Services\CampaignService;
use App\Http\Controllers\Controller;
use App\Components\Helper\UrlHelper;

class CampaignController extends Controller
{
    /**
     * 2.6.2 获取出价/日预算限制
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue_min |  | decimal | 广告主出价最小值 |  | 是 |
     * | revenue_max |  | decimal | 广告主出价最大值 |  | 是 |
     * | revenue_step |  | integer | 出价加减价金额 |  | 是 |
     * | day_limit_min |  | integer | 日预算最小值 |  | 是 |
     * | day_limit_max |  | integer | 日预算最大值 |  | 是 |
     * | day_limit_step |  | integer | 日预算加减金额 |  | 是 |
     * | total_limit_min |  | integer | 总预算最小值 |  | 是 |
     * | total_limit_max |  | integer | 总预算最大值 |  | 是 |
     * | total_limit_step |  | integer | 总预算加减金额 |  | 是 |
     * | key_min |  | integer | 最小值 |  | 是 |
     * | key_max |  | integer | 最大值 |  | 是 |
     *
     */
    public function moneyLimit()
    {
        $client = Auth::user()->account->client;
        if ($client->affiliateid > 0) {
            //获取相应产品类型配置
            $config = Config::get('biddingos.selfDefaultInit');
        } else {
            //获取相应产品类型配置
            $config = Config::get('biddingos.jsDefaultInit');
        }
        //过滤数组，去其中一部分
        $result = [];
        foreach ($config as $k => $v) {
            $result = array_add(
                $result,
                $k,
                array_only(
                    $v,
                    [
                        'revenue_min',
                        'revenue_max',
                        'revenue_step',
                        'day_limit_min',
                        'day_limit_max',
                        'day_limit_step',
                        'total_limit_min',
                        'total_limit_max',
                        'total_limit_step',
                        'key_min',
                        'key_max'
                    ]
                )
            );
        }
        //配置为空时返回错误
        if (empty($result)) {
            // @codeCoverageIgnoreStart
            LogHelper::warning('revenue/day_limit not configured');
            return $this->errorCode(5002);
            // @codeCoverageIgnoreEnd
        }

        return $this->success(null, null, $result);
    }

    /**
     * 2.6.3 获取Banner广告尺寸和插屏广告尺寸
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | key |  | string | Banner广告尺寸ID |  | 是 |
     * | label |  | string | Banner广告尺寸 |  | 是 |
     *
     */
    public function demand()
    {
        $obj =  Config::get('ad_spec');
        return $this->success($obj, null, null);
    }

    /**
     * 推广页表格显示字段
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | field |  | string | 列名 |  | 是 |
     * | title |  | string | 列显示名称 |  | 是 |
     * | column_set |  | string | 列排序 |  | 是 |
     */
    public function columnList()
    {
        $fields = Campaign::getColumnListFields();
        $list = CampaignService::getColumnList($fields);
        return $this->success(null, ['count' => count($list)], $list);
    }

    /**
     * 推广列表
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 推广计划ID |  | 是 |
     * | products_name |  | string | 应用名称 |  | 是 |
     * | appinfos_app_show_icon |  | string | 应用图标 |  | 是 |
     * | products_type |  | integer | 应用类型 |  | 是 |
     * | products_type_label |  | string | 应用类型标签 |  | 是 |
     * | appinfos_app_name |  | string | 广告名称 |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * | ad_type_label |  | string | 广告类型标签 |  | 是 |
     * | platform |  | string | 目标平台 |  | 是 |
     * | platform_label |  | string | 目标平台标签 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * | revenue_type_label |  | string | 计费类型标签 |  | 是 |
     * | revenue |  | decimal | 出价 |  | 是 |
     * | keyword_price_up_count |  | integer | 加价关键字数量 |  | 是 |
     * | zone_price_up_count |  | integer | 广告位加价数量 |  | 是 |
     * | day_limit |  | int | 日预算 |  | 是 |
     * | total_limit |  | int | 总预算 |  | 是 |
     * | status |  | int | 状态 |  | 是 |
     * | status_label |  | string | 状态标签 |  | 是 |
     * | pause_status |  | string | 暂停状态 |  | 是 |
     * | approve_time |  | datetime | 审核时间 |  | 是 |
     * | approver_user |  | string | 审核人 |  | 是 |
     * | approve_comment |  | string | 审核说明 |  | 是 |
     * | materials_status |  | integer | 素材状态 |  | 是 |
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $pageNo = intval($request->input('pageNo')) <= 1 ? DEFAULT_PAGE_NO : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);

        $info = $this->getCampaignList($user->user_id, $pageNo, $pageSize, $filter, $search, $sort);
        if (!$info) {
            // @codeCoverageIgnoreStart
            LogHelper::warning('campaign failed to load data');
            return $this->errorCode(5001);
            // @codeCoverageIgnoreEnd
        }
        return $this->success(null, $info['map'], $info['list']);
    }

    /**
     * 删除推广计划
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function delete(Request $request)
    {
        $user = Auth::user();
        $campaignId = intval($request->input('campaignid'));
        if ($campaignId <= 0) {
            return $this->errorCode(5000);
        }

        $client = $user->account->client;
        $clientId = $client->clientid;
        $campaign = Campaign::whereMulti(['campaignid' => $campaignId, 'clientid' => $clientId])->first();
        if (!$campaign) {
            // @codeCoverageIgnoreStart
            LogHelper::warning('campaign failed to load data');
            return $this->errorCode(5002);
            // @codeCoverageIgnoreEnd
        }

        $result = DB::transaction(
            function () use ($campaign, $client) {
                //删除应用信息
                AppInfo::where(
                    [
                    'media_id' => $client->agencyid,
                    'platform' => $campaign->plateform,
                    'app_id' => $campaign->campaignname,
                    ]
                )->delete();
                LogHelper::info('delete appinfo '.$client->agencyid.' plateform '
                    .$campaign->plateform.' app_id '.$campaign->campaignname);

                //产品没有关联推广计划时，删除产品信息
                $count = Campaign::where('product_id', $campaign->product_id)
                    ->where('campaignid', '<>', $campaign->campaignid)
                    ->count();
                if ($count == 0) {
                    Product::where('id', $campaign->product_id)->delete();
                }
                LogHelper::info('delete product'.$campaign->product_id);

                if ($campaign->ad_type == Campaign::AD_TYPE_BANNER_IMG ||
                    $campaign->ad_type == Campaign::AD_TYPE_HALF_SCREEN ||
                    $campaign->ad_type == Campaign::AD_TYPE_FULL_SCREEN ||
                    $campaign->ad_type == Campaign::AD_TYPE_FEEDS
                ) {
                    //删除图片素材
                    CampaignImage::where('campaignid', '=', $campaign->campaignid)->delete();
                    LogHelper::info('delete campaign_image campaignId'. $campaign->campaignid);
                } elseif ($campaign->ad_type == Campaign::AD_TYPE_APP_MARKET) {
                    //删除关键字
                    AdZoneKeyword::where('campaignid', '=', $campaign->campaignid)->delete();
                    LogHelper::info('delete ad_zone_keyword campaignId'.$campaign->campaignid);
                }
                $campaign->delete();
            }
        );
        if ($result instanceof Exception) {
            return $this->errorCode(5001);
        }

        return $this->success();
    }

    /**
     * 2.8 推广产品列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 产品ID |  | 是 |
     * | icon |  | string | 图标地址 |  | 是 |
     * | icon_code |  | string | 图标地址 |  | 是 |
     * | link_name |  | string | 链接名称 |  | 是 |
     * | link_url |  | string | 链接地址 |  | 是 |
     * | name |  | string | 产品名称 |  | 是 |
     * | platform |  | integer | 目标平台 |  | 是 |
     * | show_name |  | string | 应用显示名称 |  | 是 |
     */
    public function productList(Request $request)
    {
        $type = ArrayHelper::getRequiredIn(Product::getTypeLabels());
        if (($ret = $this->validate($request, [
                'products_type' => "integer|in:{$type}", //推广类型
            ], [], Product::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $productType = $request->input('products_type');
        $client_id = Auth::user()->account->client->clientid;

        $select = Product::where('clientid', $client_id)
            ->select('id', 'name', 'icon', 'platform', 'show_name', 'link_name', 'link_url');
        if (isset($productType)) {
            $select = $select->where('type', $productType);
        }

        $total = $select->count();
        $data = $select->get();

        $ret = [];
        foreach ($data as $v) {
            $v->icon = UrlHelper::imageFullUrl($v->icon);
            $v->icon_code = $v->icon;
            if ($v->type == Product::TYPE_LINK && $v->platform == Campaign::PLATFORM_IOS_COPYRIGHT) {
                $v->platform = Campaign::PLATFORM_IOS;
            }
            $ret[] = $v;
        }

        return $this->success(
            null,
            [
                'pageSize' => 0,
                'pageNo' => 0,
                'count' => $total
            ],
            $ret
        );
    }

    /**
     * 增加/修改推广计划
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 推柜计划ID |  | 是 |
     * | platform |  | integer | 目标平台 | 0:iPhone越狱 1:iPhone正版 2:iPad 3:Android | 是 |
     * | platform_name |  | string | 目标平台名称 |  | 是 |
     * | revenue |  | decimal | 下载地址 |  | 是 |
     * | revenue_type |  | integer | 计费类型 | 1:CPD,2:CPC,4:CPA,8:CPT,16:CPM | 是 |
     * | day_limit |  | decimal | 日预算 |  | 是 |
     * | action |  | integer | 状态 | 1：提交审核，2：保存草稿，3：保存修改 | 是 |
     * | products_id |  | integer | 应用ID |  | 是 |
     * | products_type   |  | integer | 推广类型 |  | 是 |
     * | products_name |  | string | 应用名称 |  | 是 |
     * | products_show_name |  | string | 应用显示名称 |  | 是 |
     * | products_icon |  | string | 应用图标 |  | 是 |
     * | appinfos_app_name |  | string | 广告名称 |  | 是 |
     * | appinfos_description |  | string | 应用介绍 |  | 是 |
     * | appinfos_profile |  | integer | 应用一句话简介 |  | 是 |
     * | appinfos_update_des |  | string | 更新说明 |  | 是 |
     * | ad_type |  | integer | 广告类型 | 0:应用市场  1:Banner  2:Feeds 3:插屏半屏 | 是 |
     * |  |  |  |  |  4:插屏全屏,71:appstore,81:其他 |  |
     * | star |  | integer | 星级 |  | 是 |
     * | link_name |  | string | 链接名称 |  | 是 |
     * | link_url |  | string | 链接地址 |  | 是 |
     * | link_title |  | string | 标题 |  | 是 |
     * | appinfos_images |  | array | 应用截图 |  | 是 |
     * |  | url | string | 图片地址 |  | 是 |
     * |  | ad_spec | integer | 图片规格 |  | 是 |
     * | keywords |  | array | 关键字 |  | 是 |
     * |  | id | integer | 关键字ID |  | 是 |
     * |  | price_up | decimal | 加价金额 |  | 是 |
     * |  | keyword | string | 关键字 |  | 是 |
     * |  | status | integer | 加价状态 |  | 是 |
     * | package_file |  | array | 包 |  | 是 |
     * |  | path | string | 文件路径 |  | 是 |
     * |  | real_name | string | 安装包名称 |  | 是 |
     * |  | md5 | string | MD5码 |  | 是 |
     * |  | reserve | string |  |  | 是 |
     * |  | version_name | string | 版本名称 |  | 是 |
     * |  | version_code | string | 版本号 |  | 是 |
     * |  | package_name | string | 包名称 |  | 是 |
     * |  | package_download_url | string | 包下载地址 |  | 是 |
     * |  | real_name | string | 包名 |  | 是 |
     * |  | package_id | integer | 包ID |  | 是 |
     * |  | status | integer | 包状态 |  | 是 |
     * | application_id |  | integer | app应用ID |  | 是 |
     * | video |  | string | 视频信息 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $params = $request->all();//获取所有参数
        $action = implode(',', [Campaign::ACTION_DRAFT, Campaign::ACTION_APPROVAL, Campaign::ACTION_EDIT]);//状态
        //广告主其他类型广告新增暂时不启用
        if ($params['ad_type'] == Campaign::AD_TYPE_OTHER) {// @codeCoverageIgnore
//            $platform = ArrayHelper::getRequiredIn(Campaign::getPlatformLabels(null));
//            //计费类型
//            $revenueType = implode(',', [Campaign::REVENUE_TYPE_CPA, Campaign::REVENUE_TYPE_CPT]);
//            if (($ret = $this->validate($request, [
//                    'revenue_type' => "required|in:{$revenueType}",
//                    'platform' => "required|in:{$platform}",
//                    'action' => "required|integer|in:{$action}",//状态
//                    'products_name' => 'required',
//                    'products_icon' => 'required',
//                    'appinfos_app_name' => 'required',
//                ], [], Campaign::attributeLabels())) !== true
//            ) {
//                return $this->errorCode(5000, $ret);
//            }
//            $params = $request->all();
//            $client =  Auth::user()->account->client;
//            $params['clientid'] = $client->clientid;
//            $params['agencyid'] = $client->agencyid;
//            $ret = CampaignService::campaignStore($params);
//            if ($ret !== true) {
//                return $this->errorCode($ret);
//            }
        } else {
            $revenueType = ArrayHelper::getRequiredIn(Campaign::getRevenueTypeLabels());//计费类型
            $productType = ArrayHelper::getRequiredIn(Product::getTypeLabels());//推广类型
            if (($ret = $this->validate($request, [
                    'revenue_type' => "required|integer|in:{$revenueType}", //推广类型
                    'products_type' => "required|integer|in:{$productType}", //推广类型
                ], [], Product::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            $revenueMin = Config::get("biddingos.jsDefaultInit." . $params['revenue_type'] . ".revenue_min");
            $revenueMax = Config::get("biddingos.jsDefaultInit." . $params['revenue_type'] . ".revenue_max");
            $dayLimitMin = Config::get("biddingos.jsDefaultInit." . $params['revenue_type'] . ".day_limit_min");
            $dayLimitMax = Config::get("biddingos.jsDefaultInit." . $params['revenue_type'] . ".day_limit_max");

            //目标平台
            $platform = ArrayHelper::getRequiredIn(Campaign::getPlatformLabels(null, $params['products_type']));
            $adType = ArrayHelper::getRequiredIn(Campaign::getAdTypeLabels());//广告类型
            if (($ret = $this->validate($request, [
                    'platform' => "required|integer|in:{$platform}", //目标平台
                    'action' => "required|integer|in:{$action}",//状态
                    'ad_type' => "required|integer|in:{$adType}",//广告类型
                    'appinfos_images' => 'array',
                    'keywords' => 'array',
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }

            if ($params['revenue_type'] != Campaign::REVENUE_TYPE_CPS) {
                if (($ret = $this->validate($request, [
                        'revenue' => "required|numeric|min:{$revenueMin}|max:{$revenueMax}",
                        'day_limit' => "required|numeric|min:{$dayLimitMin}|max:{$dayLimitMax}",
                        'total_limit' => 'min:0',
                    ], [], Campaign::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
            } else {
                $params['revenue'] = 0;
                $params['day_limit'] = 0;
                $params['total_limit'] = 0;
            }
            //应用下载需要验证包
            if ($params['products_type'] == Product::TYPE_APP_DOWNLOAD) {
                if (($ret = $this->validate($request, [
                        'package_file' => 'required|string',
                    ], [], Campaign::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
            }

            //应用截图
            if (!isset($params['appinfos_images'])) {
                $params['appinfos_images'] = array();
            }
            //加价关键字，默认空
            if (!isset($params['keywords'])) {
                $params['keywords'] = array();
            }

            //应用市场 必须上传3张应用截图
            if ($params['ad_type'] == Campaign::AD_TYPE_APP_MARKET
                || $params['ad_type'] == Campaign::AD_TYPE_APP_STORE
            ) {
                if (empty($params['appinfos_images'])) {
                    LogHelper::warning('app_market must submit at least 3 screen shots');
                    return $this->errorCode(5025);
                } else {
                    foreach ($params['appinfos_images'] as $item) {
                        // @codeCoverageIgnoreStart
                        if (count($item) < 3) {
                            LogHelper::warning('app_market must submit at least 3 screen shots');
                            return $this->errorCode(5025);
                        }
                        // @codeCoverageIgnoreEnd
                    }
                }
            }
            //推广类型 产品名称，应用名称，广告名称必输
            if ($params['products_type'] == Product::TYPE_APP_DOWNLOAD) {
                if (($ret = $this->validate($request, [
                        'products_name' => 'required',// 应用名称也要填写
                        'products_show_name' => 'required', // 应用显示名称
                        'appinfos_app_name' => 'required',//广告名称
                        'products_icon' => 'required',//应用图标
                    ], [], Campaign::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }

                //应用市场  应用介绍，一句话介绍必输
                if ($params['ad_type'] == Campaign::AD_TYPE_APP_MARKET) {
                    if (($ret = $this->validate($request, [
                            'appinfos_description' => 'required',// 应用介绍
                            'appinfos_profile' => 'required', // 应用一句话简介
                        ], [], Campaign::attributeLabels())) !== true
                    ) {
                        return $this->errorCode(5000, $ret);
                    }
                } elseif ($params['ad_type'] == Campaign::AD_TYPE_FEEDS) {
                    //feeds广告 一句话，星级必输
                    if (($ret = $this->validate($request, [
                            'appinfos_profile' => 'required',// 应用一句话简介
                            'star' => 'required|integer', // 星级
                        ], [], Campaign::attributeLabels())) !== true
                    ) {
                        return $this->errorCode(5000, $ret);
                    }
                } elseif ($params['ad_type'] == Campaign::AD_TYPE_BANNER_TEXT_LINK) {
                    //文字链广告 广告文案必输
                    if (($ret = $this->validate($request, [
                            'appinfos_profile' => 'required|max:64',// 广告文案
                        ], [], Campaign::attributeLabels())) !== true
                    ) {
                        return $this->errorCode(5000, $ret);
                    }
                }
            } elseif ($params['products_type'] == Product::TYPE_LINK) {
                //链接推广
                if (($ret = $this->validate($request, [
                        'link_name' => 'required',// 链接名称
                        'link_url' => 'required', // 链接地址
                    ], [], Campaign::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
                //feeds 标题，吸引文案必输
                if ($params['ad_type'] == Campaign::AD_TYPE_FEEDS) {
                    if (($ret = $this->validate($request, [
                            'appinfos_profile' => 'required',// 吸引文案
                            'link_title' => 'required', // 标题
                            'products_icon' => 'required',//应用图标
                        ], [], Campaign::attributeLabels())) !== true
                    ) {
                        return $this->errorCode(5000, $ret);
                    }
                }
                //吸引文案必输
                if ($params['ad_type'] == Campaign::AD_TYPE_BANNER_TEXT_LINK) {
                    if (($ret = $this->validate($request, [
                            'appinfos_profile' => 'required',// 吸引文案
                        ], [], Campaign::attributeLabels())) !== true
                    ) {
                        return $this->errorCode(5000, $ret);
                    }
                }

                // application_id必须有效
                if ($params['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    if (($ret = $this->validate($request, [
                            'application_id' => 'required',// apple application_id
                        ], [], Campaign::attributeLabels())) !== true
                    ) {
                        return $this->errorCode(5000, $ret);
                    }
                    // 判断app_id 有效性
                    $result = HttpClientHelper::call(Config::get('biddingos.appStorePrefix') .
                        $params['application_id']);
                    $res = json_decode($result);
                    if (!isset($res->resultCount) || $res->resultCount < 1) {
                        $result = HttpClientHelper::call(
                            Config::get('biddingos.appStoreCnPrefix') . $params['application_id']
                        );
                        $res = json_decode($result);

                        if (!isset($res->resultCount) || $res->resultCount < 1) {
                            return $this->errorCode(5039);
                        }
                    }// @codeCoverageIgnore
                    $params['platform'] = Campaign::PLATFORM_IOS_COPYRIGHT;
                }
            }

            //视频广告
            if ($params['ad_type'] == Campaign::AD_TYPE_VIDEO) {
                if (($ret = $this->validate($request, [
                        'video' => 'required',// 视频素材
                    ], [], Campaign::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
            }

            if (!empty($params['keywords'])) {
                foreach ($params['keywords'] as $key) {
                    if (floatval($key['price_up']) <= 0) {
                        return $this->errorCode(5042);
                    }
                }
            }

            //检测是新增时否存在相同的产品
            $clientId = Auth::user()->account->client->clientid;
            if (empty($params['id']) &&
                (empty($params['products_id']) || intval($params['products_id']) <= 0)
            ) {
                $name = $params['products_type'] == Product::TYPE_LINK
                    ? $params['link_name'] : $params['products_name'];
                $count = Product::where('name', $name)
                    ->where('clientid', $clientId)->count();
                if ($count > 0) {
                    return $this->errorCode(5044);
                }
            }

            //重新构建package参数
            if ($params['products_type'] == Product::TYPE_APP_DOWNLOAD) {
                $params['package_file'] = CampaignService::getPackageParams($params['package_file']);
                if (empty($params['package_file'])) {
                    return $this->errorCode(5060);// @codeCoverageIgnore
                }
            }
            DB::beginTransaction();  //事务开始
            //新增产品
            $products_id = Product::storeProduct($params);
            if (!$products_id) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode(5021);//返回产品修改失败
                // @codeCoverageIgnoreEnd
            }
            $params['products_id'] = $products_id;
            $params['clientid'] = $clientId;
            //加入防JS注入
            $params['appinfos_description'] = e($params['appinfos_description']);
            $params['appinfos_update_des'] = e($params['appinfos_update_des']);
            $params['appinfos_profile'] = e($params['appinfos_profile']);

            //新建推广计划
            $ret_code = CampaignService::storeCampaign($params);
            if ($ret_code !== true) {
                // @codeCoverageIgnoreStart
                DB::rollback();
                return $this->errorCode($ret_code);
                // @codeCoverageIgnoreEnd
            }
            DB::commit(); //事务结束
        }

        //返回结果
        return $this->success();
    }


    /**
     * 查看某个推广计划
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | id | integer | 产品ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 推柜计划ID |  | 是 |
     * | platform |  | integer | 目标平台 | 0:iPhone越狱 1:iPhone正版 2:iPad 3:Android | 是 |
     * | platform_name |  | string | 目标平台名称 |  | 是 |
     * | revenue |  | decimal | 下载地址 |  | 是 |
     * | revenue_type |  | integer | 计费类型 | 1:CPD,2:CPC,4:CPA,8:CPT,16:CPM | 是 |
     * | day_limit |  | decimal | 日预算 |  | 是 |
     * | status |  | tinyint | 状态 | 1：投放中，2：暂停，4：草稿，10：待审核 | 是 |
     * |  |  |  |  | 11：未通过审核，15：停止投放 |  |
     * | products_id |  | integer | 应用ID |  | 是 |
     * | products_type   |  | integer | 推广类型 |  | 是 |
     * | products_name |  | string | 应用名称 |  | 是 |
     * | products_show_name |  | string | 应用显示名称 |  | 是 |
     * | products_icon |  | string | 应用图标 |  | 是 |
     * | appinfos_app_name |  | string | 广告名称 |  | 是 |
     * | appinfos_description |  | string | 应用介绍 |  | 是 |
     * | appinfos_profile |  | integer | 应用一句话简介 |  | 是 |
     * | appinfos_update_des |  | string | 更新说明 |  | 是 |
     * | ad_type |  | integer | 广告类型 | 0:应用市场  1:Banner  2:Feeds 3:插屏半屏 | 是 |
     * |  |  |  |  |  4:插屏全屏,71:appstore,81:其他 |  |
     * | star |  | integer | 星级 |  | 是 |
     * | link_name |  | string | 链接名称 |  | 是 |
     * | link_url |  | string | 链接地址 |  | 是 |
     * | link_title |  | string | 标题 |  | 是 |
     * | appinfos_images |  | array | 应用截图 |  | 是 |
     * |  | url | string | 图片地址 |  | 是 |
     * |  | ad_spec | integer | 图片规格 |  | 是 |
     * | keywords |  | array | 关键字 |  | 是 |
     * |  | id | integer | 关键字ID |  | 是 |
     * |  | price_up | decimal | 加价金额 |  | 是 |
     * |  | keyword | string | 关键字 |  | 是 |
     * |  | status | integer | 加价状态 |  | 是 |
     * | package_file |  | array | 包 |  | 是 |
     * |  | package_download_url | string | 包下载地址 |  | 是 |
     * |  | real_name | string | 包名 |  | 是 |
     * |  | package_id | integer | 包ID |  | 是 |
     * |  | status | integer | 包状态 |  | 是 |
     * | application_id |  | integer | app应用ID |  | 是 |
     */
    public function view(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $id = $request->input('id');

        //获取AppInfo信息
        $row = $this->getCampaignAppInfo($id);
        if (empty($row)) {
            LogHelper::warning('appInfo failed to load data');
            return $this->errorCode(5002);
        }
        $row ['status_label'] = Campaign::getStatusLabels($row['status']);//或者状态名称
        //获取关键字
        $priceUp = CampaignService::getKeywordPrice($id);
        if ($row['ad_type'] == Campaign::AD_TYPE_APP_MARKET || $row['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
            if (ArrayHelper::arrayLevel($row['images']) == 1) {
                $row['images'] = [
                    '1' => $row['images'],
                    '2' => [],
                ];
            }
        }
        if (!empty($priceUp)) {
            foreach ($priceUp as $k => $v) {
                $priceUp[$k]->price_up = Formatter::asDecimal($v->price_up);
            }
            $priceUp = array_values($priceUp);
        }
        //返回结果
        return $this->success(
            [
                'id' => $row['campaignid'],
                'platform' =>
                    $row['ad_type'] == Campaign::AD_TYPE_APP_STORE ? Campaign::PLATFORM_IOS : $row['platform'],
                'platform_name' => $row['platform_name'],
                'revenue' => $row['revenue'],
                'revenue_type' => $row['revenue_type'],
                'day_limit' => $row['day_limit'],
                'total_limit' => $row['total_limit'],
                'status' => $row['status'],
                'status_label' => $row['status_label'],
                'products_id' => $row['product_id'],
                'products_type' => $row['product_type'],
                'products_name' => $row['product_name'],
                'products_show_name' => $row["ad_type"] == Campaign::AD_TYPE_APP_STORE ?
                    $row['app_show_name'] : $row['product_show_name'],
                'products_icon' => $row['product_type'] == Product::TYPE_LINK ? $row['app_show_icon'] : $row['icon'],
                'appinfos_app_name' => $row['app_name'],
                'appinfos_description' => $row['description'],
                'appinfos_profile' => $row['profile'],
                'appinfos_update_des' => $row['update_des'],
                'ad_type' => $row['ad_type'],
                'star' => $row['star'],
                'link_name' => $row['link_name'],
                'link_url' => $row['link_url'],
                'link_title' => $row['title'],
                'appinfos_images' =>
                    ($row['ad_type'] == Campaign::AD_TYPE_APP_MARKET || $row['ad_type'] == Campaign::AD_TYPE_APP_STORE)
                        ? $row['images'] : $row['banner_images'],
                'keywords' => $priceUp,
                'package_file' => $row['package_file'],
                'application_id' => $row['application_id'],
                'video' => $row['video'],
            ]
        );
    }

    /**
     * 查看某个推广计划
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | AppStore应用ID |  | 是 |
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     *
     */
    public function appStoreView(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required',
            ], [], Campaign::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $id = $request->input('id');

        $result = HttpClientHelper::call(Config::get('biddingos.appStoreCnPrefix') . $id);
        $res = json_decode($result);
        if (!isset($res->resultCount) || $res->resultCount < 1) {
            $result = HttpClientHelper::call(Config::get('biddingos.appStorePrefix') . $id);
            $res = json_decode($result);

            if (!isset($res->resultCount) || $res->resultCount < 1) {
                return $this->errorCode(5039);
            }
        }// @codeCoverageIgnore

        $info = (array)($res->results);
        $obj = null;
        if (isset($info[0])) {
            $obj = (array)$info[0];
        }
        return $this->success($obj, ['count' => $res->resultCount]);
    }



    /**
     * 获取广告主计费类型
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue_type |  | array | 计费类型 |  | 是 |
     */
    public function revenueType()
    {
        $affiliateId = Auth::user()->account->client->affiliateid;
        if ($affiliateId > 0) {
            $result = \DB::table('affiliates_extend')
                ->where('affiliateid', $affiliateId)
                ->select('revenue_type')
                ->where('ad_type', Campaign::AD_TYPE_APP_MARKET)
                ->get();
            $list = [];
            foreach ($result as $item) {
                $list[] = $item->revenue_type;
            }
        } else {
            //获取广告主计费类型
            $revenueType = Auth::user()->account->client->revenue_type;
            $list = CampaignService::getRevenueTypeList($revenueType);
        }
        return $this->success(['revenue_type' => $list]);
    }

    /**
     * 检测产品是否存在
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | id | integer | 产品ID |  | 是 |
     * | name | string | 产品名称 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function productExist(Request $request)
    {
        if (($ret = $this->validate($request, [
                'name' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $clientId = Auth::user()->account->client->clientid;
        $name = $request->input('name');
        $id = $request->input('id');

        $select = Product::where('clientid', $clientId)
            ->where('name', $name);

        if (!empty($id)) {
            $select = $select->where('id', '<>', $id);
        }
        $count = $select->count();

        return $this->success(['count' => $count]);
    }

    /**
     * 广告主暂停和启用
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | campaignid | integer | 推广计划ID |  | 是 |
     * | status | tinyint | 状态 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $status = ArrayHelper::getRequiredIn(Campaign::getStatusLabels());
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'status' => "required|in:{$status}",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $campaignId = $request->input('campaignid');
        $status = $request->input('status');

        if ($status == Campaign::STATUS_SUSPENDED) {
            //广告主暂停广告
            $ret = CampaignService::modifyStatus(
                $campaignId,
                $status,
                [
                    'pause_status' => Campaign::PAUSE_STATUS_ADVERTISER_PAUSE,
                ],
                false
            );
            if ($ret !== true) {
                return $this->errorCode($ret);// @codeCoverageIgnore
            }

            //广告主暂停广告记录日志
            $campaign = Campaign::find($campaignId);
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'type' => OperationLog::TYPE_MANUAL,
                'target_id' => $campaignId,
                'operator' => OperationLog::ADVERTISER,
                'message' => CampaignService::formatWaring(6048, [
                    $campaign->client->clientname,
                ])
            ]);
        } elseif ($status == Campaign::STATUS_DELIVERING) {
            //继续投放
            $ret = CampaignService::modifyStatus($campaignId, $status, [
                'pause_status' => Campaign::PAUSE_STATUS_PLATFORM,
            ], false);
            if ($ret !== true) {
                return $this->errorCode($ret);// @codeCoverageIgnore
            }
        }

        return $this->success();
    }

    /**
     * 获取乐视应用接口
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | wd | string | 搜索关键字 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | appinfos_app_name |  | string | 应用名称 |  | 是 |
     * | products_icon |  | string | 应用图标 |  | 是 |
     * | appinfo_images |  | string | 应用图片 | ['url','url'] | 是 |
     * | downloadurl |  | string | 下载地址 |  | 是 |
     * | app_id |  | string | 全局ID |  | 是 |
     * | appinfos_profile |  | string | 一句话简介 |  | 是 |
     * | versionCode |  | string | 版本号 |  | 是 |
     * | versionName |  | string | 版本名称 |  | 是 |
     * | packageName |  | string | 包名 |  | 是 |
     * | filesize |  | integer | 文件大小 |  | 是 |
     * | star |  | integer | 星级 |  | 是 |
     *
     */
    public function appList(Request $request)
    {
        if (($ret = $this->validate($request, [
                'wd' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $wd = $request->input('wd');
        $platform = $request->input('platform');

        $affiliateId = Auth::user()->account->client->affiliateid;
        $symbol = Affiliate::where('affiliateid', $affiliateId)
            ->pluck('symbol');
        $houseAd = HouseAdFactory::getClass(ucfirst($symbol));
        list($total, $list) = $houseAd->getAppList($wd, $platform);

        return $this->success(null, [
            'total' => $total,
        ], $list);
    }

    /**
     * 新增推广，修改推广计划
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID | 新增为空，修改必填 | 是 |
     * | revenue |  | decimal | 出价 |  | 是 |
     * | day_limit |  | integer | 日预算 |  | 是 |
     * | total_limit |  | integer | 总预算 |  | 是 |
     * | products_icon |  | string | 应用图标 |  | 是 |
     * | platform |  | tinyint | 目标平台 |  | 是 |
     * | appinfos_app_name |  | string | 广告名称 |  | 是 |
     * | appinfos_profile |  | string | 一句话简介 |  | 是 |
     * | appinfos_images |  | array | 应用图片 | ['url','url'] | 是 |
     * | versionCode |  | string | 版本号 |  | 是 |
     * | versionName |  | string | 版本名称 |  | 是 |
     * | packageName |  | string | 包名 |  | 是 |
     * | filesize |  | integer | 文件大小 |  | 是 |
     * | downloadurl |  | string | 下载地址 |  | 是 |
     * | app_id |  | string | 全局ID |  | 是 |
     * | star |  | integer | 星级 |  | 是 |
     * | keywords |  | array | 关键字加价 |  | 否 |
     * |  | id | integer | 关键字ID |  | 是 |
     * |  | price_up | decimal | 加价金额 |  | 是 |
     * |  | keyword | string | 关键字 |  | 是 |
     * | zones |  | array | 广告位加价 |  | 否 |
     * |  | id | integer | 广告位加价ID |  | 是 |
     * |  | zoneid | integer | 广告位ID |  | 是 |
     * |  | price_up | decimal | 加价金额 |  | 是 |
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function selfStore(Request $request)
    {
        $params = $request->all();
        $revenueMin = Config::get("biddingos.jsDefaultInit." . $params['revenue_type'] . ".revenue_min");
        $revenueMax = Config::get("biddingos.jsDefaultInit." . $params['revenue_type'] . ".revenue_max");
        $dayLimitMin = Config::get("biddingos.jsDefaultInit." . $params['revenue_type'] . ".day_limit_min");
        $dayLimitMax = Config::get("biddingos.jsDefaultInit." . $params['revenue_type'] . ".day_limit_max");

        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        if ($params['revenue_type'] == Campaign::REVENUE_TYPE_CPA) {
            if (($ret = $this->validate($request, [
                    'revenue' => "required|numeric|min:{$revenueMin}|max:{$revenueMax}",
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }
        if ($params['revenue_type'] == Campaign::REVENUE_TYPE_CPD) {
            if (($ret = $this->validate($request, [
                    'revenue' => "required|numeric|min:{$revenueMin}|max:{$revenueMax}",
                    'day_limit' => "required|numeric|min:{$dayLimitMin}|max:{$dayLimitMax}",
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }
        

        //新增包信息必填，修改非必填
        if (empty($params['campaignid'])) {
            if (($ret = $this->validate($request, [
                    'versionCode' => 'required',
                    'versionName' => 'required',
                    'packageName' => 'required',
                    'filesize' => 'required',
                    'appinfos_profile' => 'required',
                    'products_icon' => 'required',
                    'appinfos_app_name' => 'required',
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }

        //应用截图
        if (!isset($params['appinfos_images'])) {
            $params['appinfos_images'] = array();
        }
        //加价关键字，默认空
        if (!isset($params['keywords'])) {
            $params['keywords'] = array();
        }

        //广告位加价信息
        if (!isset($params['zones'])) {
            $params['zones'] = array();
        }

        \DB::beginTransaction();
        if (empty($params['campaignid'])) {
            //新增推广计划
            $client = Auth::user()->account->client;
            //检测应用是否已经创建
            $count = CampaignService::getCampaignCount(['id' => $params['campaignid'],
                'clientid' => $client->clientid,
                'appinfos_app_name' => $params['appinfos_app_name']]);
            if ($count > 0) {
                LogHelper::info('The same application already exists' . $params['appinfos_app_name']);
                \DB::rollBack();
                return $this->errorCode(5022);
            }
            // @codeCoverageIgnoreStart
            $params['appinfos_profile'] = e($params['appinfos_profile']);

            //保存产品信息
            $product = Product::create([
                'type' => Product::TYPE_APP_DOWNLOAD,
                'platform' => $params['platform'],
                'clientid' => $client->clientid,
                'name' => $params['appinfos_app_name'],
                'show_name' => $params['appinfos_app_name'],
                'icon' => $params['products_icon'],
                'link_url' => isset($params['link_url']) ? $params['link_url'] : '',
            ]);
            if (!$product) {
                DB::rollback();
                return $this->errorCode(5021);//返回产品修改失败
            }

            //保存campaign
            $appId = 'app' . str_random(12);
            $campaign = Campaign::storeCampaign([
                'app_id' => $appId,
                'clientid' => $client->clientid,
                'revenue_type' => $params['revenue_type'],
                'products_id' => $product->id,
                'ad_type' => Campaign::AD_TYPE_APP_MARKET,
                'action' => Campaign::ACTION_APPROVAL,
                'revenue' => $params['revenue'],
                'platform' => $params['platform'],
                'day_limit' => $params['day_limit'],
                'total_limit' => $params['total_limit'],
            ]);
            if (!$campaign) {
                DB::rollback();
                return $this->errorCode(5001);
            }

            //保存appInfo
            AppInfo::storeAppInfo([
                'clientid' => $client->clientid,
                'products_type' => Product::TYPE_APP_DOWNLOAD,
                'app_id' => $appId,
                'appinfos_app_name' => $params['appinfos_app_name'],
                'platform' => $params['platform'],
                'products_show_name' => $params['appinfos_app_name'],
                'appinfos_update_des' => $params['appinfos_update_des'],
                'appinfos_description' => $params['appinfos_description'],
                'appinfos_profile' => $params['appinfos_profile'],
                'star' => $params['star'],
                'ad_type' => Campaign::AD_TYPE_APP_MARKET,
                'link_title' => '',
            ], serialize($params['appinfos_images']));

            //保存安装包信息
            $attachFile = AttachFile::store([
                'path' => '',
                'real_name' => $params['appinfos_app_name'],
                'md5' => md5($params['appinfos_app_name']),
                'reserve' => json_encode([
                    'filesize' => $params['filesize'],
                    'md5' => md5($params['appinfos_app_name']),
                    'versionName' => $params['versionName'],
                    'packageName' => $params['packageName'],
                    'versionCode' => $params['versionCode'],
                ]),
                'version_name' => $params['versionName'],
                'version_code' => $params['versionCode'],
                'package_name' => $params['packageName'],
            ], $campaign->campaignid);
            if (!$attachFile) {
                DB::rollback();
                return $this->errorCode(5001);
            }

            //创建banner
            $banner = CampaignService::getBannerOrCreate(
                $campaign->campaignid,
                $client->affiliateid,
                Banner::STATUS_PENDING_PUT,
                [
                    'app_id' => $params['app_id'],
                    'attach_file_id' => $attachFile->id,
                    'download_url' => $params['downloadurl'],
                ]
            );
            if (!$banner) {
                DB::rollback();
                return $this->errorCode(5001);
            }

            $message = CampaignService::formatWaring(6009, [$campaign->client->clientname,
                $params['appinfos_app_name']]);
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'type' => OperationLog::TYPE_MANUAL,
                'target_id' => $campaign->campaignid,
                'operator' => OperationLog::ADVERTISER,
                'message' => $message,
            ]);
            // @codeCoverageIgnoreEnd
        } else {
            //修改推广计划
            $campaign = Campaign::find($params['campaignid']);
            $banner = Banner::where('campaignid', $campaign->campaignid)->first();
            if ($params['revenue_type'] != Campaign::REVENUE_TYPE_CPS) {
                //修改出价
                if ($campaign->revenue != $params['revenue']) {
                    $decimal = Config::get('biddingos.jsDefaultInit.' . $campaign->revenue_type . '.decimal');
                    $oldValue = Formatter::asDecimal($campaign->revenue, $decimal);
                    $value = Formatter::asDecimal($params['revenue'], $decimal);
                    $message = CampaignService::formatWaring(6010, [
                        $campaign->client->clientname, $oldValue, $value
                    ]);
                    CampaignService::writeAdvertiserLog($campaign, $message);
                }
                if ($params['revenue_type'] == Campaign::REVENUE_TYPE_CPD) {


                    //修改日预算
                    if ($campaign->day_limit != $params['day_limit']) {
                        $oldValue = Formatter::asDecimal($campaign->day_limit, 0);
                        $value = Formatter::asDecimal($params['day_limit'], 0);
                        $message = CampaignService::formatWaring(6011, [
                            $campaign->client->clientname, $oldValue, $value
                        ]);
                        CampaignService::writeAdvertiserLog($campaign, $message);

                        if ($campaign->day_limit < $params['day_limit'] &&
                            $campaign->status == Campaign::STATUS_SUSPENDED
                            && $campaign->pause_status == Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT
                        ) {
                            $ret = CampaignService::modifyStatus(
                                $campaign->campaignid,
                                Campaign::STATUS_DELIVERING,
                                ['pause_status' => Campaign::PAUSE_STATUS_PLATFORM],
                                true
                            );
                            if ($ret !== true) {
                                return $this->errorCode($ret);
                            }
                        }
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
                        CampaignService::writeAdvertiserLog($campaign, $message);

                        if ($campaign->total_limit < $params['total_limit'] &&
                            $campaign->status == Campaign::STATUS_SUSPENDED
                            && $campaign->pause_status == Campaign::PAUSE_STATUS_EXCEED_TOTAL_LIMIT
                        ) {
                            $ret = CampaignService::modifyStatus(
                                $campaign->campaignid,
                                Campaign::STATUS_DELIVERING,
                                ['pause_status' => Campaign::PAUSE_STATUS_PLATFORM],
                                true
                            );
                            if ($ret !== true) {
                                return $this->errorCode($ret);
                            }
                        }// @codeCoverageIgnore
                    }
                    $campaign->total_limit = $params['total_limit'];
                    $campaign->day_limit = $params['day_limit'];
                }
                $campaign->revenue = $params['revenue'];

            }
            // @codeCoverageIgnoreStart
            if ($campaign->status == Campaign::STATUS_REJECTED) {
                $campaign->status = Campaign::STATUS_PENDING_APPROVAL;
            }
            // @codeCoverageIgnoreEnd
            $campaign->save();

            if ($campaign->platform == Campaign::PLATFORM_IPHONE_COPYRIGHT) {
                $product = Product::find($campaign->product_id);
                $product->link_url = $params['link_url'];
                $product->save();
            }
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'target_id' => $campaign->campaignid,
                'type' => OperationLog::TYPE_MANUAL,
                'operator' => OperationLog::ADVERTISER,
                'message' => CampaignService::formatWaring(6013, [$campaign->client->clientname]),
            ]);
        }

        if ($params['revenue_type'] == Campaign::REVENUE_TYPE_CPD) {
            //创建/修改关键字
            if (isset($params['keywords'])) {
                AdZoneKeyword::updateKeyWordAndPrice(
                    empty($campaign) ? $params['campaignid'] : $campaign->campaignid,
                    $params['keywords']
                );
            }

            //创建/修改广告位加价
            if (!empty($params['zones'])) {
                AdZonePrice::updateZoneAndPrice($banner->bannerid, $params['zones']);
            }
        }
        \DB::commit();

        return $this->success();
    }

    /**
     * 自营广告主推广计划查询
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | campaignid | integer | 推广计划ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID | 新增为空，修改必填 | 是 |
     * | revenue |  | decimal | 出价 |  | 是 |
     * | day_limit |  | integer | 日预算 |  | 是 |
     * | total_limit |  | integer | 总预算 |  | 是 |
     * | products_icon |  | string | 应用图标 |  | 是 |
     * | appinfos_app_name |  | string | 广告名称 |  | 是 |
     * | keywords |  | array | 关键字加价 |  | 否 |
     * |  | id | integer | 关键字ID |  | 是 |
     * |  | price_up | decimal | 加价金额 |  | 是 |
     * |  | keyword | string | 关键字 |  | 是 |
     * |  | status | tinyint | 状态 |  | 是 |
     * | zones |  | array | 广告位加价 |  | 否 |
     * |  | id | integer | 广告位加价ID |  | 是 |
     * |  | zoneid | integer | 广告位ID |  | 是 |
     * |  | zonename | string | 广告位名称 |  | 是 |
     * |  | description | string | 示意图 |  | 是 |
     * |  | impressions | int | 曝光量 |  | 是 |
     * |  | price_up | decimal | 加价金额 |  | 是 |
     */
    public function selfView(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $campaignId = $request->input('campaignid');

        \DB::setFetchMode(\PDO::FETCH_ASSOC);
        $ret = \DB::table('campaigns AS c')
            ->join('appinfos AS a', function ($join) {
                $join->on('a.app_id', '=', 'c.campaignname')
                    ->on('a.platform', '=', 'c.platform');
            })->join('products AS p', 'p.id', '=', 'c.product_id')
            ->where('c.campaignid', $campaignId)
            ->select(
                'c.campaignid',
                'c.revenue',
                'c.day_limit',
                'c.total_limit',
                'p.icon AS products_icon',
                'a.app_name AS appinfos_app_name',
                'c.revenue_type',
                'c.platform',
                'p.link_url'
            )
            ->first();

        $keywords = \DB::table('ad_zone_keywords')
            ->where('campaignid', $campaignId)
            ->select('id', 'keyword', 'price_up', 'status')
            ->get();

        $affiliateId = Auth::user()->account->client->affiliateid;
        $banner = Banner::where('campaignid', $campaignId)->first();

        //组合广告位加价信息
        $zones = \DB::table('zones AS z')
            ->where('affiliateid', $affiliateId)
            ->where('platform', $ret['platform'])
            ->where('z.status', Zone::STATUS_OPEN_IN)
            ->where('type', '<>', Zone::TYPE_FLOW)
            ->select('z.zoneid', 'z.zonename', 'z.description', 'z.platform')
            ->get();
        $adZonePrice = \DB::table('ad_zone_price')
            ->where('ad_id', $banner->bannerid)
            ->select('id', 'zone_id', 'price_up')
            ->get();
        $listZonePrice = [];
        foreach ($adZonePrice as $item) {
            $listZonePrice[$item['zone_id']] = [
                'id' => $item['id'],
                'price_up' => $item['price_up'],
            ];
        }
        foreach ($zones as &$zone) {
            $zone['id'] = isset($listZonePrice[$zone['zoneid']]) ?
                $listZonePrice[$zone['zoneid']]['id'] : 0;
            $zone['price_up'] = isset($listZonePrice[$zone['zoneid']]) ?
                $listZonePrice[$zone['zoneid']]['price_up'] : 0;
        }

        $zones = ZoneService::getZonePrice($zones, $banner);

        $ret['keywords'] = $keywords;
        $ret['zones'] = $zones;

        return $this->success($ret);
    }

    /**
     * 获取推广产品的详情
     * @param integer $campaignId
     * @param string $balance
     * @param number $rate
     * @return NULL|mixed
     */
    private function getCampaignAppInfo($campaignId, $rate = 0)
    {
        $query = DB::table('campaigns as c')
            ->leftJoin('clients as s', function ($join) {
                $join->on('s.clientid', '=', 'c.clientid');
            })
            ->leftjoin('products as p', function ($join) {
                $join->on('c.product_id', '=', 'p.id');
            })
            ->leftJoin('appinfos as a', function ($join) {
                $join->on('c.campaignname', '=', 'a.app_id');
                $join->on('c.platform', '=', 'a.platform');
                $join->on('s.agencyid', '=', 'a.media_id');
            })
            ->leftJoin('category as t', 't.category_id', '=', 'a.category')
            ->where('c.campaignid', $campaignId)
            ->select(
                'a.app_show_name',
                'a.profile',
                'a.title',
                'a.star',
                'a.app_show_icon',
                'c.status',
                'c.campaignid',
                'a.app_id',
                'c.revenue',
                'c.revenue_type',
                'c.day_limit',
                'c.total_limit',
                'c.platform',
                'a.app_name',
                'a.description',
                'a.update_des',
                'a.app_show_name',
                'p.icon',
                'a.images',
                'a.application_id',
                'c.ad_type',
                'p.name as product_name',
                'p.show_name as product_show_name',
                'p.type as product_type',
                'p.link_name',
                'p.link_url',
                's.clientname'
            );

        // @codeCoverageIgnoreStart
        if (0 < $rate) {
            $query->addSelect(DB::raw('FORMAT(FLOOR((' . $rate . '*c.revenue)*10)/10, 1) as revenue'));
        }
        // @codeCoverageIgnoreEnd

        //获取推广计划
        $query->addSelect('c.product_id');

        //条件设置完成
        $appInfo = $query->first();
        if (!$appInfo) {
            return null;
        }

        //获取banner广告的图片
        $images = DB::table('campaigns_images as ci')
            ->where('ci.campaignid', $campaignId)
            ->select('ci.ad_spec', 'ci.url')
            ->get();
        $appInfo = json_decode(json_encode($appInfo), true);

        //处理安装包信息
        if ($appInfo['status'] == Campaign::STATUS_DRAFT || $appInfo['status'] == Campaign::STATUS_REJECTED) {
            $attachFile = AttachFile::where('campaignid', $campaignId)
                ->whereIn('flag', [AttachFile::FLAG_PENDING_APPROVAL, AttachFile::FLAG_REJECTED])
                ->select('file', 'real_name', 'id', 'flag')
                ->get();
        } else {
            $first = AttachFile::where('campaignid', $campaignId)
                ->whereIn('flag', [AttachFile::FLAG_USING])
                ->orderBy('created_at', 'DESC')
                ->select('file', 'real_name', 'id', 'flag')
                ->first();
            $attachFile = AttachFile::where('campaignid', $campaignId)
                ->where('flag', AttachFile::FLAG_REJECTED)
                ->select('file', 'real_name', 'id', 'flag')
                ->get()->toArray();

            if ($first) {
                $attachFile = array_merge([$first->toArray()], $attachFile);
            }
        }
        $data = [];
        foreach ($attachFile as $item) {
            $data[] = [
                'package_download_url' => UrlHelper::fileFullUrl($item['file'], $item['real_name']),
                'real_name' => $item['real_name'],
                'package_id' => $item['id'],
                'status' => $item['flag']
            ];
        }
        //处理视频素材
        if ($appInfo['status'] == Campaign::STATUS_DRAFT || $appInfo['status'] == Campaign::STATUS_REJECTED) {
            $status = [CampaignVideo::STATUS_PENDING_APPROVAL, CampaignVideo::STATUS_REJECTED];
        } else {
            $status = [CampaignVideo::STATUS_USING, CampaignVideo::STATUS_REJECTED];
        }
        $videos = CampaignVideo::where('campaignid', $campaignId)
            ->whereIn('status', $status)
            ->select('id', 'reserve', 'status', 'url')
            ->get();
        $avData = [];
        foreach ($videos as $item) {
            $videoInfo = json_decode($item->reserve, true);
            $avData[] = [
                'url' => $item->url,
                'real_name' => $videoInfo['real_name'],
                'id' => $item['id'],
                'status' => $item['status']
            ];
        }

        $appInfo['package_file'] = $data;
        $appInfo['video'] = $avData;
        $appInfo['platform_name'] = Campaign::getPlatformLabels($appInfo['platform']);
        $appInfo['revenue'] = $appInfo['revenue_type'] == Campaign::REVENUE_TYPE_CPC
            ? Formatter::asDecimal($appInfo['revenue']) : Formatter::asDecimal($appInfo['revenue'], 1);
        $appInfo['day_limit'] = Formatter::asDecimal($appInfo['day_limit'], 0);
        $appInfo['total_limit'] = Formatter::asDecimal($appInfo['total_limit'], 0);
        $appInfo['images'] = $appInfo['images'] ? unserialize($appInfo['images']) : array();
        $appInfo['banner_images'] = $images ? $images : array();
        return $appInfo;
    }

    /**
     * 获取广告主的广告计划任务数据
     * @param int $userId 广告主用户ID
     * @param array $arrayStatus 【可选】广告任务的状态
     * @param int $pageNo 【可选】页码
     * @param int $pageSize 【可选】每页数量
     * @param string $search 【可选】搜索关键字
     * @param string $sort 【可选】排序字段
     * @return array
     */
    private function getCampaignList(
        $userId,
        $pageNo = DEFAULT_PAGE_NO,
        $pageSize = DEFAULT_PAGE_SIZE,
        $filter = null,
        $search = null,
        $sort = null
    ) {
    


        $user = User::find($userId);
        if (!$user) {
            return null;// @codeCoverageIgnore
        }

        $when = " CASE ";
        foreach (Campaign::getStatusSort() as $k => $v) {
            $when .= ' WHEN ' . Campaign::getTableFullName() . '.status = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_status ";

        $when .= ", CASE ";
        foreach (Campaign::getAdTypeSort() as $k => $v) {
            $when .= ' WHEN ' . Campaign::getTableFullName() . '.ad_type = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_ad_type ";

        $when .= ", CASE ";
        foreach (Campaign::getPauseStatusSort() as $k => $v) {
            $when .= ' WHEN ' . Campaign::getTableFullName() . '.pause_status = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_pause_status ";

        $client = $user->account->client;
        $agencyId = $client->agency->agencyid;

        $prefix = \DB::getTablePrefix();
        $select = $user->account->client->campaigns()->getQuery()
            ->join('appinfos', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('appinfos.platform', '=', 'campaigns.platform');
            })
            ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
            ->select(
                DB::raw($when),
                'campaigns.campaignid',
                'campaigns.campaignname',
                'campaigns.revenue',
                'campaigns.status',
                'campaigns.pause_status',
                'campaigns.platform',
                'campaigns.revenue_type',
                'campaigns.day_limit',
                'campaigns.approve_time',
                'campaigns.approve_comment',
                'campaigns.ad_type',
                'appinfos.app_name as appinfos_app_name',
                'appinfos.vender as appinfos_vender',
                'appinfos.app_rank as appinfos_app_rank',
                'appinfos.materials_status as appinfos_materials_status',
                'appinfos.check_msg as appinfos_check_msg',
                'products.icon as appinfos_app_show_icon',
                'appinfos.materials_status as appinfos_materials_status',
                'products.name as products_name',
                'products.type as products_type',
                'campaigns.total_limit',
                DB::raw("(select username from {$prefix}users as u
                        where u.user_id = {$prefix}campaigns.checkor_uid) as approve_user")
            )
            ->where('appinfos.media_id', '=', $agencyId);
        // 状态
//        if (!is_null($arrayStatus)) {
//            $select->whereIn('campaigns.status', $arrayStatus);
//        }
        // 搜索
        // @codeCoverageIgnoreStart
        if (!is_null($search) && trim($search)) {
            $select->where('appinfos.app_name', 'like', '%' . $search . '%');
        }
        // @codeCoverageIgnoreEnd
        // 分页
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'platform') {
                        $select->where('campaigns.platform', $v);
                    }
                }
            }
        }

        // 排序
        if ($sort) {
            $sortType = 'asc';
            // @codeCoverageIgnoreStart
            if (0 === strncmp($sort, '-', 1)) {
                $sortType = 'desc';
            }
            // @codeCoverageIgnoreEnd
            $sortAttr = str_replace('-', '', $sort);
            if ($sortAttr == 'status') {
                $select->orderBy('sort_status', $sortType)
                    ->orderBy('sort_pause_status', 'asc');
            } elseif ($sortAttr == 'ad_type') {
                $select->orderBy('sort_ad_type', $sortType);
            } else {
                $select->orderBy($sortAttr, $sortType);
            }
        } else {
            $select->orderBy('sort_status', 'desc')
                ->orderBy('sort_pause_status', 'asc');
        }
        $rows = $select->get()->toArray();
        $list = CampaignService::getCampaignItems($rows);
        return [
            'map' => [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => $total,
            ],
            'list' => $list,
        ];
    }
}
