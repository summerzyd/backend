<?php
namespace App\Http\Controllers\Trafficker;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\EmailHelper;
use App\Components\Helper\StringHelper;
use App\Components\Helper\UrlHelper;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AppInfo;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\CampaignTracker;
use App\Models\Category;
use App\Models\OperationLog;
use App\Models\Product;
use App\Models\Tracker;
use App\Models\Zone;
use App\Services\CampaignService;
use App\Services\CategoryService;
use App\Services\MessageService;
use App\Services\ZoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Components\Config;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    /**
     * 获取广告列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | pageNo |  | integer  | 请求页数 |  | 是 |
     * | pageSize |  | integer  | 请求页数 |  | 是 |
     * | search |  | string  | 搜索关键字 |  | 是 |
     * | sort |  | string  | 排序字段 |  | 是 |
     * | filter |  | string  | 筛选内容 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |integer|媒体ID| |是|
     * | campaignid|integer|推广计划ID| |是|
     * | bannerid|integer|广告ID| |是|
     * | ad_type|integer|广告类型| |是|
     * | products_name|string|应用名称| |是|
     * | products_show_name|string|应用显示名称| |是|
     * | products_type|integer|应用类型| |是|
     * | products_type_label|string|应用类型标签| |是|
     * | appinfos_app_name|string|广告名称| |是|
     * | appinfos_app_show_icon|string|应用图标| |是|
     * | approve_comment|string|审核说明/暂停说明| |是|
     * | | |0：平台暂停| | |
     * | | |1：因超过日限额暂停| | |
     * | | |2：因余额不足暂停| | |
     * | | |3：达到媒体日预算暂停| | |
     * | affiliate_checktime|string|审核时间| |是|
     * | platform|string|平台类型| |是|
     * | platform_label|string|平台类型标签| |是|
     * | revenue_type|integer|计费类型| |是|
     * | revenue_type_label|string|计费类型标签| |是|
     * | af_income|decimal|出价| |是|
     * | keyword_price_up_count|integer|加价关键字数量，用于控制前端标签| |是|
     * | category_label|string|分类标签| |否|
     * | category|integer|分类id| |否|
     * | parent|integer|类别| |否|
     * | app_rank|integer|等级| |是|
     * | app_rank_label|string|等级标签| |是|
     * | flow_ratio|string|流量变现比例| |是|
     * | status|integer|状态| |是|
     * | pause_status|integer|暂停状态| |是|
     * | campaign_status|integer|广告状态| |是|
     * | package_file_id|integer|安装包id| |否|
     * | app_id|integer|应用ID| |是|
     * | download_url|string|下载地址| |是|
     * | mode|integer|接入方式| |是|
     * | | |1 程序化投放（入库）| | |
     * | | |2 人工投放| | |
     * | | |3 程序化投放（不入库）| | |
     * | clientname|string|广告主名称| |是|
     * | appinfos_images|array|素材| | 是|
     * | link_name|string|链接名称| |否|
     * | link_url|string|链接| |否|
     * | star|integer|星级| |否|
     * | profile|string|一句话描述| |否|
     * | description|string|应用介绍| |否|
     * | title|string|标题| |否|
     * | package_name|string|包名称|com.android.pinxiaotong|否|
     */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'sort' => 'string',
                'status' => 'numeric',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();//获取所有参数
        $pageNo = (intval($params['pageNo']) - 1) >= 0 ? intval($params['pageNo']) - 1 : 0;
        //每页条数
        $pageSize = intval($params['pageSize']) > 0 ? intval($params['pageSize']) : DEFAULT_PAGE_SIZE;

        $affiliateId = Auth::user()->account->affiliate->affiliateid;//媒体商ID

        $filter = json_decode($request->input('filter'), true);

        //获取媒体商的广告列表
        $prefix = DB::getTablePrefix();
        $when = " CASE ";
        foreach (Banner::getStatusSort() as $k => $v) {
            $when .= ' WHEN ' . $prefix . 'b.status = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS ostatus ";

        //哪是是Banner,插屏这类，则选用第一个默认的就可以了
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('banners AS b')
            ->leftJoin('campaigns AS c', 'b.campaignid', '=', 'c.campaignid')
            ->leftJoin('appinfos AS a', function ($join) {
                $join->on('c.campaignname', '=', 'a.app_id')
                    ->on('c.platform', '=', 'a.platform');
            })
            ->leftJoin('affiliates AS aff', 'b.affiliateid', '=', 'aff.affiliateid')
            ->leftjoin('products AS p', 'p.id', '=', 'c.product_id')
            ->leftJoin('category AS cat', 'b.category', '=', 'cat.category_id')
            ->leftJoin('clients AS cl', 'c.clientid', '=', 'cl.clientid')
            ->leftJoin('banners_billing AS bb', 'bb.bannerid', '=', 'b.bannerid')
            ->leftJoin('attach_files AS af', 'af.id', '=', 'b.attach_file_id')
            ->select(
                "b.affiliateid",
                "b.campaignid",
                "b.bannerid",
                "b.revenue_type",
                "b.app_id",
                "b.app_rank",
                "b.download_url",
                "b.category",
                "b.status",
                DB::raw($when),
                "b.pause_status",
                "b.an_status",
                "p.type AS products_type",
                "p.name AS products_name",
                "p.show_name AS products_show_name",
                "p.link_name",
                "p.link_url",
                "b.flow_ratio",
                "b.affiliate_checktime",
                "b.attach_file_id",
                "c.status AS campaigns_status",
                "c.approve_comment",
                "c.ad_type",
                'c.revenue',
                "c.rate / 100 AS rate",
                "cat.parent",
                "cat.name",
                "p.icon",
                'a.app_show_icon',
                "a.app_name AS appinfos_app_name",
                "a.platform",
                "a.images",
                "a.star",
                "a.profile",
                "a.title",
                "a.description",
                "cl.clientname",
                "aff.mode",
                "bb.af_income",
                "af.package_name"
            )
            ->where('aff.affiliateid', $affiliateId)
            ->where('cl.affiliateid', 0)
            ->where(function ($query) {
                $query->whereIn('b.status', [
                    Banner::STATUS_PUT_IN,
                    Banner::STATUS_SUSPENDED,
                    Banner::STATUS_APP_ID,
                    Banner::STATUS_NOT_ACCEPTED,
                ])->orWhere(function ($query) {
                    $query->where('b.status', Banner::STATUS_PENDING_MEDIA)
                        ->whereNotIn('c.status', [Campaign::STATUS_SUSPENDED, Campaign::STATUS_STOP_DELIVERING]);
                });
            });

        //搜索
        if (!empty($params['search'])) {
            $params['search'] = e($params['search']);
            $select->where('app_name', 'LIKE', '%' . $params['search'] . '%');
        }

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'platform') {
                        if ($v == Campaign::PLATFORM_IOS) {
                            $select->where(function ($select) use ($v) {
                                $select->where('c.platform', $v);
                                $select->orWhere(function ($select) {
                                    $select->where('c.platform', Campaign::PLATFORM_IOS_COPYRIGHT)
                                        ->where('c.ad_type', Campaign::AD_TYPE_APP_STORE);
                                });
                            });
                        } else {
                            $select->where('c.platform', $v);
                        }
                    } elseif ($k == 'status') {
                        $select->where(function ($select) use ($v) {
                            $select->where('b.status', $v);
                            //暂停状态时，查询平台暂停，及暂停投放的广告
                            if ($v == Banner::STATUS_SUSPENDED) {
                                $select->orWhere(function ($query) {
                                    $query->whereIn('c.status', [Campaign::STATUS_SUSPENDED,
                                        Campaign::STATUS_STOP_DELIVERING])
                                        ->where('b.status', Banner::STATUS_PUT_IN);
                                });
                            } elseif ($v == Banner::STATUS_PUT_IN) {
                                $select->where('c.status', Campaign::STATUS_DELIVERING);
                            }
                        });
                    } elseif ($k == 'app_rank') {
                        $select->where('b.app_rank', $v);
                    } elseif ($k == 'category') {
                        $select->whereRaw("FIND_IN_SET({$v}, {$prefix}b.category)");
                    } elseif ($k == 'ad_type') {
                        $select->whereIn('c.ad_type', Campaign::getAdTypeToAdType($v));
                    }
                }
            }
        }

        //统计
        $total = $select->count();
        //排序
        if (isset($params['sort']) && strlen($params['sort']) > 0) {
            $sortType = 'ASC';
            if (strncmp($params['sort'], '-', 1) === 0) {
                $sortType = 'DESC';
            }
            $sortAttr = str_replace('-', '', $params['sort']);
            $select = $select->orderBy('ostatus', 'ASC')->orderBy($sortAttr, $sortType);
        } else {
            $select = $select->orderBy('ostatus', 'ASC');
        }
        //分页
        $select = $select->skip($pageNo * $pageSize)->take($pageSize);
        $result = $select->get();

        //重构广告数据
        foreach ($result as &$item) {
            $item['products_type_label'] = Product::getTypeLabels($item['products_type']);
            if (!empty($item['icon'])) {
                $item['appinfos_app_show_icon'] = UrlHelper::imageFullUrl($item['icon']);
            } else {
                $item['appinfos_app_show_icon'] = UrlHelper::imageFullUrl($item['app_show_icon']);
            }
            if ($item['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $item['platform'] = Campaign::PLATFORM_IOS;
            }
            $item['revenue_type_label'] = Campaign::getRevenueTypeLabels($item['revenue_type']);
            $item['category_label'] = CategoryService::getCategories(
                $item['category'],
                $item['affiliateid']
            )['category_label'];
            if (in_array($item['app_rank'], array_keys(AppInfo::getRankStatusLabels()))) {
                $item['app_rank_label'] = AppInfo::getRankStatusLabels($item['app_rank']);
            } else {
                $item['app_rank_label'] = '-';
            }
            $decimal = Config::get('biddingos.jsDefaultInit.' . $item['revenue_type'] . '.decimal');
            $item['revenue'] = Formatter::asDecimal($item['revenue'], $decimal);
            $item['flow_ratio_label'] = $item['mode'] == Affiliate::MODE_ARTIFICIAL_DELIVERY ?
                '-' : $item['flow_ratio'] . '%';
            $item['download_url'] = UrlHelper::fileTraffickerFullUrl($item['download_url']);
            if ($item['ad_type'] == Campaign::AD_TYPE_APP_MARKET || $item['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $images = $item['images'] ? unserialize($item['images']) : [];
                if (ArrayHelper::arrayLevel($images) == 1) {
                    $sizeArr = explode("*", Config::get('ad_spec.' . $item['ad_type'] . '.1'));
                    if (count($sizeArr) == 2) {
                        $item['appinfos_images'] = [
                            1 => [
                                'ad_spec' => 1,
                                'url' => $images,
                                'height' => $sizeArr[1],
                                'width' => $sizeArr[0],
                            ],
                        ];
                    }
                } else {
                    $listImg = [];
                    foreach ($images as $k => $v) {
                        $sizeArr = explode("*", Config::get('ad_spec.' . $item['ad_type'] . '.' . $k));
                        if (count($sizeArr) != 2) {
                            continue;// @codeCoverageIgnore
                        }
                        $listImg[$k] = [
                            'ad_spec' => $k,
                            'url' => $v,
                            'height' => $sizeArr[1],
                            'width' => $sizeArr[0],
                        ];
                    }
                    $item['appinfos_images'] = $listImg;
                }
                $keyPrice = CampaignService::getCampaignKeywords($item['campaignid']);
                $item['keyword_price_up_count'] = count($keyPrice);
            } else {
                $imagesTypeList = Config::get('biddingos.ad_spec.' . $item['ad_type']);
                $campaignImages = CampaignImage::getCampaignImages($item['campaignid']);
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
                $item['appinfos_images'] = array_values($newBannerImages);
                $item['keywords'] = [];
                $item['keyword_price_up_count'] = 0;
            }
        }

        return $this->success(null, [
            'pageSize' => $pageSize,
            'count' => $total,
            'pageNo' => $pageNo,
        ], $result);
    }

    /**
     * 获取广告等级
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | rank |  | integer  | 等级 |  | 是 |
     */
    public function rank()
    {
        $rank = AppInfo::getRankStatusLabels();
        return $this->success($rank);
    }

    /**
     * 获取广告状态
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | status |  | integer  | 状态 |  | 是 |
     */
    public function status()
    {
        $status = Banner::getStatusLabels();
        return $this->success($status);
    }

    /**
     * 获取广告分类
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | ad_type |  | integer  | 广告类型 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function category(Request $request)
    {
        $adTypes =ArrayHelper::getRequiredIn(Zone::getAdTypeLabels());
        if (($ret = $this->validate($request, [
                'ad_type' => "required|integer|in:{$adTypes}",
            ], [], Category::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        //查询此媒体下的所有分类，包括子分类
        $parentCategory = Category::getParentLabels();
        if (!$parentCategory) {
            return $this->errorCode(5002);// @codeCoverageIgnore
        }

        $affiliateId = Auth::user()->account->affiliate->affiliateid;//媒体商ID
        $category = Auth::user()->account->affiliate->agency->categories()
                    ->getQuery()
                    ->select('parent', 'category_id', 'name')
                    ->where('platform', Campaign::PLATFORM_IPHONE_COPYRIGHT)
                    ->where('affiliateid', '=', $affiliateId)
                    ->where('ad_type', '=', $request->ad_type)
                    //->whereIn('parent', array_keys($parentCategory))
                    ->distinct()
                    ->get()
                    ->toArray();

        $parents = [];
        if (!empty($category)) {
            foreach ($category as $k => $v) {
                $category[$k]['already_used'] = Category::USED_NO;
                //检查 zones 表里是否有在使用中的，如果有则提示使用中，不能删除
                $zoneResult = $this->checkZones($v['category_id']);
                if ($zoneResult) {
                    //@codeCoverageIgnoreStart
                    $category[$k]['already_used'] = Category::USED_YES;
                    //把父分类的ID记下，设置父分类的
                    $parents[$v['parent']] = Category::USED_YES;
                    //@codeCoverageIgnoreEnd
                } else {
                    //如果广告位中没有使用，再检查banners中是否有使用
                    $bannerResult = $this->checkBanners($v['category_id']);
                    if ($bannerResult) {
                        $category[$k]['already_used'] = Category::USED_YES;
                        //把父分类的ID记下，设置父分类的
                        $parents[$v['parent']] = Category::USED_YES;
                    }
                }
            }
        }
        if (!empty($parents)) {
            foreach ($parents as $parent => $val) {
                foreach ($category as $key => $value) {
                    //@codeCoverageIgnoreStart
                    if ($parent == $value['category_id']) {
                        $category[$key]['already_used'] = Category::USED_YES;
                    }
                    //@codeCoverageIgnoreEnd
                }
            }
        }

        return $this->success([
            'parent' => $parentCategory,
            'category' => $category
        ]);
    }

    /**
     * 更新广告列名
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | bannerid | integer | 媒体广告ID |  | 是 |
     * | field | string | 字段 | status,appinfos_app_rank,category | 是 |
     * | value | string | 值 | status on off | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //判断输入是否合法
        if (($ret = $this->validate($request, [
                'bannerid' => 'required|integer',
                'field' => 'required|in:status,app_rank,category',
                'value' => 'required',
            ], [], Banner::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $appRank = ArrayHelper::getRequiredIn(AppInfo::getRankStatusLabels());

        $field = $request->input('field');
        $value = $request->input('value');
        $bannerId = $request->input('bannerid');
        $banner = Banner::find($bannerId);
        $affiliateName = Auth::user()->account->affiliate->name;
        $appName = $banner->campaign->appinfo->app_name;
        $affiliateId = Auth::user()->account->affiliate->affiliateid;//媒体商ID
        switch ($field) {
            case 'status':
                if (($ret = $this->validate($request, [
                        'value' => 'required|in:on,off',
                    ], [], Banner::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
                break;//@codeCoverageIgnore
            case 'app_rank':
                if (($ret = $this->validate($request, [
                        'value' => "required|in:{$appRank}",
                    ], [], Banner::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
                break;
            default://@codeCoverageIgnore
                break;//@codeCoverageIgnore
        }//@codeCoverageIgnore

        if ($field == 'app_rank' || $field == 'category') {
            //修改广告等级
            $oldValue = $banner->$field;
            $ret = CampaignService::bannerModify($field, $value, $bannerId, $affiliateId);
            if ($ret !== true) {
                return $this->errorCode($ret);//@codeCoverageIgnore
            }
            $obj = [];
            if ($field === 'category') {
                $obj = CategoryService::getCategories($value, $affiliateId);
                if ($oldValue != $value) {
                    //修改分类操作日志
                    if ($oldValue == 0) {
                        $categoryName = '无';
                    } else {
                        $categoryName = CategoryService::getCategories($oldValue, $affiliateId)['category_label'];
                    }
                    $message = CampaignService::formatWaring(6031, [$affiliateName, $appName,
                        $categoryName, $obj['category_label']]);
                }
            } else {
                if ($oldValue != $value) {
                    //修改等级
                    if (empty($oldValue)) {
                        $oldValue = '无';//@codeCoverageIgnore
                    } else {//@codeCoverageIgnore
                        $oldValue = AppInfo::getRankStatusLabels($oldValue);
                    }

                    $message = CampaignService::formatWaring(6032, [$affiliateName, $appName,
                        $oldValue,
                        AppInfo::getRankStatusLabels($value),
                    ]);
                }
            }
            if (isset($message)) {
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_BANNER,
                    'type' => OperationLog::TYPE_MANUAL,
                    'target_id' => $bannerId,
                    'operator' => OperationLog::TRAFFICKER,
                    'message' => $message,
                ]);
            }
            return $this->success($obj);
            // @codeCoverageIgnoreStart
        } elseif ($field == 'status') {

            if ($value == 'on') {
                $message = CampaignService::formatWaring(6030, [$affiliateName, $appName]);
                $status = Banner::STATUS_PUT_IN;
            } else {
                $message = CampaignService::formatWaring(6029, [$affiliateName, $appName]);
                $status = Banner::STATUS_SUSPENDED;
            }
            $ret = CampaignService::modifyBannerStatus($bannerId, $status);
            if ($ret !== true) {
                return $this->errorCode($ret);
            }

            OperationLog::store([
                'category' => OperationLog::CATEGORY_BANNER,
                'type' => OperationLog::TYPE_MANUAL,
                'target_id' => $bannerId,
                'operator' => OperationLog::TRAFFICKER,
                'message' => $message,
            ]);

            //发送邮件通知相关人员
            if ($value == 'off') {
                $banner = Banner::find($bannerId);
                $affiliateName = Auth::user()->account->affiliate->name;
                $app = DB::table('campaigns')
                    ->leftJoin('appinfos', 'campaigns.campaignname', '=', 'appinfos.app_id')
                    ->where('campaigns.campaignid', '=', $banner->campaignid)->first();
                //发邮件给联盟运营
                $users = MessageService::getPlatUserInfo([Auth::user()->agencyid]);
                $mail = [];
                $mail['subject'] = "{$app->app_name}被{$affiliateName}暂停";
                $mail['msg']['app_name'] = $app->app_name;
                $mail['msg']['affiliate_name'] = $affiliateName;
                $mail['msg']['target'] = 'platform';
                $allEmailAddresses = array_column($users, 'email_address');
                EmailHelper::sendEmail('emails.trafficker.pauseCampaign', $mail, $allEmailAddresses);

                //发邮件给对应媒体商的媒介经理
                $mail['msg']['target'] = 'affiliate_manager';
                $managers = MessageService::getAffiliateManagerUsersInfo($affiliateId);
                $managers = array_column($managers, 'email_address');
                EmailHelper::sendEmail(
                    'emails.trafficker.pauseCampaign',
                    $mail,
                    array_diff($managers, $allEmailAddresses)
                );
            }
        }
        // @codeCoverageIgnoreEnd
        return $this->success();
    }

    /**
     * 接受，暂不接受
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | bannerid | integer | 媒体广告ID |  | 是 |
     * | action | integer | 操作码 |  | 是 |
     * | category | string | 分类 | 接受投放时必填 | 是 |
     * | appinfos_app_rank | string | 等级 | 接受投放时必填 | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function check(Request $request)
    {
        $action = ArrayHelper::getRequiredIn(Campaign::getMediaActionLabels());
        if (($ret = $this->validate($request, [
                'bannerid' => 'required|integer',
                'action' => "required|integer|in:{$action}",
            ], [], Banner::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        $affiliateId = Auth::user()->account->affiliate->affiliateid;//媒体商ID
        $affiliates = Affiliate::where('affiliateid', $affiliateId)->first();//媒体商信息
        $initStatus = $params['action'];
        if ($params['action'] == Campaign::ACTION_ACCEPT &&
            $affiliates->mode == Affiliate::MODE_PROGRAM_DELIVERY_STORAGE
        ) {
            if (($ret = $this->validate($request, [
                    'category' => "required",
                    'appinfos_app_rank' => 'required|integer',
                ], [], Banner::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }
        $banner = Banner::find($params['bannerid']);
        $params['campaignid'] = $banner->campaignid;//推广计划ID
        $campaign = Campaign::find($params['campaignid']);
        if (empty($campaign)) {
            return $this->errorCode(5002);// @codeCoverageIgnore
        }
        $params['updated'] = date("Y-m-d H:i:s");
        $params['updated_uid'] = Auth::user()->user_id;
        $params['affiliate_checktime'] = date("Y-m-d H:i:s");
        $result = Banner::whereMulti(['campaignid' => $params['campaignid'], 'affiliateid' => $affiliateId])
            ->first();
        if (empty($result)) {
            return $this->errorCode(5001);
        }

        if (($affiliates->mode == Affiliate::MODE_PROGRAM_DELIVERY_STORAGE ||
                $affiliates->mode == Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE) &&
            $params['action'] == Campaign::ACTION_ACCEPT
        ) {
            // 如果审核通过,插入up_trackers和up_campaigns_trackers
            CampaignService::tracker($params['campaignid'], $affiliateId, $campaign->campaignname);
            //入库媒体审核后待选AppID
            //不入库媒体审核后投放中
            if ($campaign->status == Campaign::STATUS_DELIVERING) {
                $params['status'] = $affiliates->mode == Affiliate::MODE_PROGRAM_DELIVERY_STORAGE ?
                    Banner::STATUS_APP_ID : Banner::STATUS_PUT_IN;

                Banner::updateBanner($params, $affiliateId, $result->bannerid);
                $banners = Banner::whereMulti(['campaignid' => $params['campaignid'],
                    'affiliateid' => $affiliateId
                ])->first();
                $banners->buildBannerText();
                $banners->save();
                /*
                 * 当媒体商是平台下载，且已具备投放条件时
                 * 广告-广告位绑定
                 * 广告计划-广告位绑定
                 */
                $bannerId = $result->bannerid;
                DB::transaction(function ($bannerId) use ($bannerId) {
                    $b = Banner::find($bannerId);

                    $b->zones()->detach();
                    CampaignService::campaignZonesDetach($b->bannerid);

                    CampaignService::attachBannerRelationChain($b->bannerid);
                });

                if ($result instanceof Exception) {
                    return $this->errorCode(5001);// @codeCoverageIgnore
                }
            } else {
                /*
                 * 当campaign status不为<投放中-0>或者<待投放-12>时
                 * 正常情况下不会显示待审核的广告供媒体商审核
                 * 故返回错误信息
                 */
                return $this->errorCode(5029);// @codeCoverageIgnore
            }
            //接受投放
            $message = CampaignService::formatWaring(6027, [$affiliates->name,
                $banner->campaign->appinfo->app_name]);
            OperationLog::store([
                'category' => OperationLog::CATEGORY_BANNER,
                'type' => OperationLog::TYPE_MANUAL,
                'target_id' => $params['bannerid'],
                'operator' => OperationLog::TRAFFICKER,
                'message' => $message,
            ]);
        } else {
            // 如果审核通过,插入up_trackers和up_campaigns_trackers
            if ($initStatus == Campaign::ACTION_ACCEPT) {
                CampaignService::tracker($params['campaignid'], $affiliateId, $campaign->campaignname);
            }

            $banners = DB::table('banners')
                ->where('campaignid', $params['campaignid'])
                ->where("affiliateid", $affiliateId)
                ->update([
                    'status' => Banner::STATUS_NOT_ACCEPTED,
                    'bannerid' => $params['bannerid'],
                    'category' => isset($params['category']) ? $params['category'] : 0,
                    'app_rank' => isset($params['appinfos_app_rank']) ? $params['appinfos_app_rank'] : 0,
                    'campaignid' => $params['campaignid'],
                    'updated' => $params['updated'],
                    'updated_uid' => $params['updated_uid'],
                    'affiliate_checktime' => $params['affiliate_checktime']
                ]);

            //不接受投放
            $message = CampaignService::formatWaring(6028, [$affiliates->name,
                $banner->campaign->appinfo->app_name]);
            OperationLog::store([
                'category' => OperationLog::CATEGORY_BANNER,
                'type' => OperationLog::TYPE_MANUAL,
                'target_id' => $params['bannerid'],
                'operator' => OperationLog::TRAFFICKER,
                'message' => $message,
            ]);
        }

        if (!$banners) {
            return $this->errorCode(5030);// @codeCoverageIgnore
        }
        $affiliateName = Auth::user()->account->affiliate->name;
        $app = DB::table('campaigns')
            ->leftJoin('appinfos', 'campaigns.campaignname', '=', 'appinfos.app_id')
            ->where('campaigns.campaignid', '=', $params['campaignid'])
            ->pluck('app_name');
        //发邮件给联盟运营
        $users = MessageService::getPlatUserInfo([Auth::user()->agencyid]);
        $mail = [];
        if ($initStatus == Campaign::ACTION_ACCEPT) {
            $mail['subject'] = "{$app}通过{$affiliateName}审核";
            $mail['msg']['result'] = 'pass';
        } else {
            $mail['subject'] = "{$app}未通过{$affiliateName}审核";
            $mail['msg']['result'] = 'reject';
        }
        $mail['msg']['app_name'] = $app;
        $mail['msg']['affiliate_name'] = $affiliateName;
        $mail['msg']['target'] = 'platform';
        $allEmailAddresses = array_column($users, 'email_address');
        EmailHelper::sendEmail('emails.trafficker.checkCampaign', $mail, $allEmailAddresses);

        //发邮件给对应媒体商的媒介经理
        $mail['msg']['target'] = 'affiliate_manager';
        $managers =MessageService::getAffiliateManagerUsersInfo($affiliateId);
        $managers = array_column($managers, 'email_address');
        EmailHelper::sendEmail('emails.trafficker.checkCampaign', $mail, array_diff($managers, $allEmailAddresses));
        return $this->success();
    }

    /**
     * 获取广告管理列表
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | pageSize | integer | 页面 |  | 否 |
     * | pageNo | integer | 当前页码 |  | 否 |
     * | sort | string | 排序 | status 升序 -status降序，降序在字段前加- | 否 |
     * | search | string | 排序 |  | 否 |
     * | filter | string | 筛选 | Json格式：{"status":3, } | 否 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * | appinfos_app_name |  | string | 广告名称 |  | 是 |
     * | appinfos_app_show_icon |  | string | 应用图标 |  | 是 |
     * | opertion_time |  | datetime | 操作时间 |  | 是 |
     * | approve_user |  | integer | 操作人 |  | 是 |
     * | approve_comment |  | string | 审核原因 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * | revenue |  | decimal | 出价 |  | 是 |
     * | day_limit |  | integer | 日限额 |  | 是 |
     * | total_limit |  | integer | 总限额 |  | 是 |
     * | keyword_price_up_count |  | integer | 加价关键字数量 |  | 是 |
     * | zone_price_up_count |  | integer | 广告位加价数量 |  | 是 |
     * | category_label |  | string | 分类标签 |  | 是 |
     * | category |  | string | 分类 |  | 是 |
     * | app_rank |  | string | 等级 |  | 是 |
     * | status |  | tinyint | 状态 | 0：运行中，1：已暂停，10：待审核， | 是 |
     * |  |  |  |  | 11：未通过审核，15：停止投放 |  |
     * | pause_status |  | tinyint | 暂停状态 | 0 ：运营暂停，1：日限额暂停， | 是 |
     * |  |  |  |  | 2：余额不足暂停，3： 广告主暂停，5：达到总限额暂停 |  |
     * | clientname |  | string | 广告主全称 |  | 是 |
     * | brief_name |  | string | 广告主简称 |  | 是 |
     * | contact |  | string | 联系人 |  | 是 |
     * | contact_phone |  | string | 联系电话 |  | 是 |
     * | email |  | string | 邮件 |  | 是 |
     */
    public function selfIndex(Request $request)
    {
        $params = $request->all();
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认25
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1
        $affiliateId = Auth::user()->account->affiliate->affiliateid;//媒体商ID
        $filter = json_decode($request->input('filter'), true);

        $prefix = DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $when = " CASE ";
        foreach (Campaign::getStatusSort() as $k => $v) {
            $when .= ' WHEN ' . Campaign::getTableFullName() . '.status = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_status, CASE ";
        foreach (Campaign::getPauseStatusSort() as $k => $v) {
            $when .= ' WHEN ' . Campaign::getTableFullName() . '.pause_status = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_pause_status ";
        $select = DB::table("campaigns")
            ->leftJoin("appinfos", function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('campaigns.platform', '=', 'appinfos.platform');
            })
            ->leftJoin('products', 'campaigns.product_id', '=', 'products.id')
            ->leftJoin('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->leftJoin('accounts', 'accounts.account_id', '=', 'clients.account_id')
            ->leftJoin('users', 'accounts.manager_userid', '=', 'users.user_id')
            ->leftJoin('banners', 'banners.campaignid', '=', 'campaigns.campaignid')
            ->select(
                'campaigns.campaignid',
                'campaigns.revenue',
                'campaigns.status',
                'campaigns.pause_status',
                'campaigns.platform',
                'campaigns.ad_type',
                'campaigns.total_limit',
                'campaigns.revenue_type',
                'campaigns.day_limit',
                'campaigns.operation_time',
                'campaigns.approve_comment',
                'appinfos.app_name AS appinfos_app_name',
                'products.icon AS appinfos_app_show_icon',
                'clients.brief_name',
                'clients.clientname',
                'clients.contact',
                'clients.email',
                'users.contact_phone',
                'banners.app_rank',
                'banners.category',
                DB::raw("(select username from {$prefix}users as u
                        where u.user_id = {$prefix}campaigns.updated_uid) as approve_user"),
                DB::raw($when)
            )->where('clients.agencyid', Auth::user()->agencyid)
            ->where('clients.affiliateid', $affiliateId)
            ->whereIn('campaigns.status', [
                Campaign::STATUS_DELIVERING,
                Campaign::STATUS_SUSPENDED,
                Campaign::STATUS_PENDING_APPROVAL,
                Campaign::STATUS_REJECTED,
                Campaign::STATUS_STOP_DELIVERING,
            ]);

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'status') {
                        //出价，状态，日预算过滤
                        $select = CampaignService::getFilterCondition($select, ['status' => $v]);
                    } elseif ($k == 'platform') {
                        $select = $select->where('campaigns.platform', $v);
                    }
                }
            }
        }

        // ===================搜索==========================
        if (!empty($params['search'])) {
            //增加广告主搜索的功能
            $select->where(function ($query) use ($params) {
                $query->where('appinfos.app_name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('clients.brief_name', 'like', '%' . $params['search'] . '%');
            });
        }

        //===================分页==========================
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        //====================排序========================
        if (isset($params['sort']) && strlen($params['sort']) > 0) {
            $sortType = 'ASC';
            if (strncmp($params['sort'], '-', 1) === 0) {
                $sortType = 'DESC';
            }
            $sortAttr = str_replace('-', '', $params['sort']);
            $select->orderBy('sort_status', 'desc')
                ->orderBy('sort_pause_status', 'asc')
                ->orderBy($sortAttr, $sortType);
        } else {
            //默认排序
            $select->orderBy('sort_status', 'desc')
                ->orderBy('sort_pause_status', 'asc')
                ->orderBy('operation_time', 'desc');
        }

        //获取数据
        $data = $select->get();

        if (count($data) > 0) {
            //获取关键字
            $campaignIds = array_column($data, 'campaignid');
            $kwpTmp = CampaignService::getCampaignKeywordsCount($campaignIds);
            $kwpCount = [];
            foreach ($kwpTmp as $up) {
                $kwpCount[$up['campaignid']] = $up['cnt'];
            }

            //获取广告位加价
            $azpTmp = DB::table('ad_zone_price AS azp')
                ->join('banners AS b', 'azp.ad_id', '=', 'b.bannerid')
                ->whereIn('b.campaignid', $campaignIds)
                ->select(DB::raw("SUM(1) AS cnt"), 'b.campaignid')
                ->groupBy('b.campaignid')
                ->get();
            $azpCount = [];
            foreach ($azpTmp as $azp) {
                $azpCount[$azp['campaignid']] = $azp['cnt'];
            }
            \DB::setFetchMode(\PDO::FETCH_CLASS);
            foreach ($data as &$item) {
                $item['category_label'] = CategoryService::getCategories(
                    $item['category'],
                    $affiliateId
                )['category_label'];
                $item['keyword_price_up_count'] = isset($kwpCount[$item['campaignid']]) ?
                    $kwpCount[$item['campaignid']] : 0;
                $item['zone_price_up_count'] = isset($azpCount[$item['campaignid']]) ?
                    $azpCount[$item['campaignid']] : 0;
            }
        }
        return $this->success(null, [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $total,
        ], $data);
    }

    /**
     * 审核广告，暂停，投放，继续投放，停止投放
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | campaignid | integer | 推广计划ID |  | 是 |
     * | status | tinyint | 状态 | 0通过审核，11不通过审核 | 是 |
     * | app_rank | string | 等级 | 审核时必填 | 否 |
     * | category | string | 分类 | 审核时必填 | 否 |
     * | approve_comment | 审核原因 |  | 驳回时必填 } | 否 |
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function selfCheck(Request $request)
    {
        $status = ArrayHelper::getRequiredIn(Campaign::getStatusLabels());
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'status' => "required|in:{$status}",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $affiliateId = Auth::user()->account->affiliate->affiliateid;
        //获取所有参数
        $status = $request->input('status');
        $campaignId = $request->input('campaignid');
        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            return $this->errorCode(5001);// @codeCoverageIgnore
        }
        $campaignStatus = $campaign->status;
        if ($status == Campaign::STATUS_REJECTED) {
            if (($ret = $this->validate($request, [
                    'approve_comment' => 'required',
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            $param['approve_comment'] = $request->input('approve_comment');

            CampaignService::rejectApproveLog($campaignId, $param['approve_comment']);
        } elseif ($status == Campaign::STATUS_DELIVERING) {
            // @codeCoverageIgnoreStart
            if ($campaignStatus == Campaign::STATUS_PENDING_APPROVAL ||
                $campaignStatus == Campaign::STATUS_REJECTED
            ) {
                //审核需要广告系数及渠道号
                if (($ret = $this->validate($request, [
                        'app_rank' => 'required',
                        'category' => 'required',
                    ], [], Banner::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
                //审核修改广告分类及等级，并改为投放中
                $appRank = $request->input('app_rank');
                $category = $request->input('category');
                //修改banner状态
                $banner = Banner::where('campaignid', $campaignId)->first();

                //检查是否有符合要求的广告位
                $prefix = \DB::getTablePrefix();
                $count = Zone::whereRaw("(platform & {$campaign->platform}) > 0")
                    ->where('rank_limit', '>=', $banner->app_rank)
                    ->where('status', '=', Zone::STATUS_OPEN_IN)
                    ->where('ad_type', $campaign->ad_type)
                    ->where('affiliateid', $affiliateId)
                    ->whereRaw("has_intersection({$prefix}zones.oac_category_id, '{$category}') > 0")
                    ->count();
                if ($count == 0) {
                    return $this->errorCode(5073);
                }

                if ($banner) {
                    //加上 tackerId
                    $tracker = $banner->tracker()->first();
                    if (empty($tracker)) {
                        $tracker = Tracker::store($campaign->campaignid, $campaign->clientid, $banner->bannerid);
                        $tracker->campaigns()->attach($campaign->campaignid, [
                            'status' => CampaignTracker::STATUS_CONFIRM,
                        ]);
                    }
                }
                $banner->app_rank = $appRank;
                $banner->category = $category;
                $banner->status = Banner::STATUS_PUT_IN;
                $banner->pause_status = Banner::PAUSE_STATUS_MEDIA_MANUAL;
                $banner->affiliate_checktime = date('Y-m-d h:i:s');
                $banner->updated_uid = Auth::user()->user_id;

                //修改bannerText
                $banner->buildBannerText();
                $banner->save();

                $campaign->rate = 100;
                $campaign->operation_time = date('Y-m-d h:i:s');
                $campaign->checkor_uid = Auth::user()->user_id;
                $campaign->updated_uid = Auth::user()->user_id;
                $campaign->save();

                //投放
                CampaignService::attachBannerRelationChain($banner->bannerid);

                CampaignService::approveLog($campaignId);
            }
            // @codeCoverageIgnoreEnd
        }
        DB::beginTransaction();//事务开始
        //更改广告主状态
        $ret = CampaignService::modifyStatus($campaignId, $status, isset($param) ? $param : []);
        if ($ret !== true) {
            // @codeCoverageIgnoreStart
            DB::rollback();
            return $this->errorCode($ret);
            // @codeCoverageIgnoreEnd
        }

        DB::commit();//事务结束
        return $this->success();
    }

    /**
     * 查看广告位加价列表
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | campaignid | integer | 推广计划ID |  | 是 |
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 广告位加价ID |  | 是 |
     * | zonename |  | string | 广告位名称 |  | 是 |
     * | description |  | string | 示意图 |  | 是 |
     * | impressions |  | integer | 曝光数 |  | 是 |
     * | price_up |  | decimal | 加价金额 |  | 是 |
     * | rank |  | integer | 等级 |  | 是 |
     *
     */
    public function zoneList(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $campaignId = $request->input('campaignid');

        $list = ZoneService::getZonesList($campaignId);

        return $this->success(null, null, $list);
    }

    /**
     * 修改分类及等级
     *
     * | name | type | description | restraint | required|
     * | :--: | :--: | :--------: | :-------: | :-----: |
     * | campaignid | integer | 推广计划ID |  | 是 |
     * | app_rank | string | 等级 |  | 是 |
     * | category | string | 分类 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function selfUpdate(Request $request)
    {
        $rank = ArrayHelper::getRequiredIn(AppInfo::getRankStatusLabels());
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'field' => "required|in:app_rank,category",
            ], [], Banner::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $campaignId = $request->input('campaignid');
        $field = $request->input('field');
        $value = $request->input('value');

        if ($field == 'app_rank') {
            if (($ret = $this->validate($request, [
                    'value' => "required|in:{$rank}",
                ], [], Banner::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
        }

        $banner = Banner::where('campaignid', $campaignId)->first();
        $banner->$field = $value;
        $banner->updated_uid = Auth::user()->user_id;
        $banner->save();

        $campaign = Campaign::find($campaignId);
        $campaign->operation_time = date('Y-m-d h:i:s');
        $campaign->updated_uid = Auth::user()->user_id;
        $campaign->save();

        /**
         * 如果 banner处于投放中或者暂停中
         * 修改类别会影响投放，投放关联关系也要处理
         */
        if (in_array($banner->status, [Banner::STATUS_SUSPENDED, Banner::STATUS_PUT_IN])) {
            CampaignService::deAttachBannerRelationChain($banner->bannerid);
            CampaignService::attachBannerRelationChain($banner->bannerid);
        }
        return $this->success();
    }

    /**
     * 检查广告位中是否有在使用此分类
     * @param $categoryid
     * @return boolean
     */
    private function checkZones($categoryId)
    {
        $count = DB::table('zones')
                    ->whereRaw("FIND_IN_SET('{$categoryId}',oac_category_id)")
                    ->count();
        return 0 < $count;
    }

    /**
     * 检查广告中是否有在使用此分类的广告
     * @param $categoryid
     * @return bool
     */
    private function checkBanners($categoryId)
    {
        $count = DB::table('banners')
                    ->whereRaw("FIND_IN_SET('{$categoryId}',category)")
                    ->count();

        return 0 < $count;
    }
}
