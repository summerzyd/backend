<?php
namespace App\Http\Controllers\Manager;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Components\Helper\UrlHelper;
use App\Components\Symbol\SymbolFactory;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AppInfo;
use App\Models\AttachFile;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\CampaignLog;
use App\Models\CampaignRevenueHistory;
use App\Models\CampaignVideo;
use App\Models\OperationLog;
use App\Models\Product;
use App\Services\BannerService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Components\Config;
use Illuminate\Support\Facades\DB;
use App\Services\CampaignService;

class MaterialController extends Controller
{
    /**
     * 获取素材列表信息
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | pageNo  |  | integer | 请求页数 |  | 是 |
     * | pageSize |  | integer | 请求每页数量 |  | 是 |
     * | search |  | string | 搜索关键字 |  | 是 |
     * | sort |  | string | 排序字段 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | client_name |  | string | 请求页数 |  | 是 |
     * | campaignid |  | integer | 请求每页数量 |  | 是 |
     * | products_name |  | string | 搜索关键字 |  | 是 |
     * | products_show_name |  | string | 排序字段 |  | 是 |
     * | products_type |  | string | 排序字段 |  | 是 |
     * | app_name |  | string | 排序字段 |  | 是 |
     * | ad_type |  | string | 排序字段 |  | 是 |
     * | app_show_icon |  | string | 排序字段 |  | 是 |
     * | approve_user |  | string | 排序字段 |  | 是 |
     * | status |  | integer | 排序字段 |  | 是 |
     * | update_at |  | datetime | 排序字段 |  | 是 |
     * | platform |  | integer | 排序字段 |  | 是 |
     * | materials_data |  | array | 排序字段 |  | 是 |
     * |  | app_show_name | string | 排序字段 |  | 是 |
     * |  | package_name | string | 排序字段 |  | 是 |
     * |  | download_url | string | 排序字段 |  | 是 |
     * |  | package_md5 | string | 排序字段 |  | 是 |
     * |  | app_show_icon | string | 排序字段 |  | 是 |
     * |  | description | string | 排序字段 |  | 是 |
     * |  | profile | string | 排序字段 |  | 是 |
     * |  | update_desc | string | 排序字段 |  | 是 |
     * |  | images | array | 排序字段 |  | 是 |
     * |  | link_name | string | 排序字段 |  | 是 |
     * |  | link_url | string | 排序字段 |  | 是 |
     * |  | title | string | 排序字段 |  | 是 |
     * |  | rank | string | 排序字段 |  | 是 |
     * | materials_new |  | array | 新素材 |  | 是 |
     * |  | app_show_name | string | 排序字段 |  | 是 |
     * |  | package_name | string | 排序字段 |  | 是 |
     * |  | download_url | string | 排序字段 |  | 是 |
     * |  | package_md5 | string | 排序字段 |  | 是 |
     * |  | app_show_icon | string | 排序字段 |  | 是 |
     * |  | description | string | 排序字段 |  | 是 |
     * |  | profile | string | 排序字段 |  | 是 |
     * |  | update_desc | string | 排序字段 |  | 是 |
     * |  | images | array | 排序字段 |  | 是 |
     * |  | link_name | string | 排序字段 |  | 是 |
     * |  | link_url | string | 排序字段 |  | 是 |
     * |  | title | string | 排序字段 |  | 是 |
     * |  | rank | string | 排序字段 |  | 是 |
     */
    public function index(Request $request)
    {
        $params = $request->all();
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认10
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1
        $filter = json_decode($request->input('filter'), true);
        $when = " CASE ";
        foreach (AppInfo::getStatusSort() as $k => $v) {
            $when .= ' WHEN materials_status = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_status ";

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('appinfos')
            ->join('campaigns', function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('appinfos.platform', '=', 'campaigns.platform');
            })
            ->join('products', 'campaigns.product_id', '=', 'products.id')
            ->leftJoin('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->leftJoin('users', 'users.user_id', '=', 'appinfos.check_user')
            ->select(
                'clients.clientname',
                'appinfos.app_id',
                'appinfos.app_name',
                'appinfos.profile',
                'appinfos.materials_status',
                'appinfos.platform',
                'appinfos.updated_at',
                'appinfos.check_date',
                'appinfos.check_user',
                'appinfos.check_msg',
                'appinfos.materials_data',
                'appinfos.app_show_name',
                'appinfos.description',
                'appinfos.update_des',
                'products.icon',
                'appinfos.images',
                'appinfos.star',
                'products.type',
                'products.name',
                'products.show_name',
                'products.link_name',
                'products.link_url',
                'appinfos.title',
                'campaigns.campaignid',
                'campaigns.ad_type',
                'campaigns.revenue',
                'campaigns.total_limit',
                'campaigns.day_limit',
                'campaigns.revenue_type',
                'users.username',
                DB::raw($when)
            )
            ->whereIn('appinfos.materials_status', [
                AppInfo::MATERIAL_STATUS_APPROVAL,
                AppInfo::MATERIAL_STATUS_PENDING_APPROVAL,
                AppInfo::MATERIAL_STATUS_REJECT
            ]);
        //SAAS平台过滤
        $select->where('clients.agencyid', Auth::user()->agencyid);

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'products_type') {
                        $select->where('products.type', $v);
                    } elseif ($k == 'status') {
                        $select->where('appinfos.materials_status', $v);
                    }
                }
            }
        }

        //素材查询单个信息
        if (!empty($params['campaignid'])) {
            $select->where('campaigns.campaignid', $params['campaignid']);
        }
        //===================搜索==========================
        if (!empty($params['search'])) {
            $select->where('appinfos.app_name', 'like', "%{$params['search']}%");
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
            switch ($sortAttr) {
                case 'updated_at':
                    $select->orderBy('sort_status', 'ASC')->orderBy('appinfos.updated_at', $sortType);
                    break;
                case 'approve_time':
                    $select->orderBy('sort_status', 'ASC')->orderBy('appinfos.check_date', $sortType);
                    break;
                default:
                    $select->orderBy('sort_status', $sortType)->orderBy('updated_at', 'desc');
                    break;
            }
        } else {
            //默认排序
            $select->orderBy('sort_status', 'ASC')->orderBy('updated_at', 'desc');
        }
        //获取数据
        $rows = $select->get();
        $list = [];

        //获取总消耗
        $result = CampaignService::getTotalConsume(array_column($rows, 'campaignid'));

        $campaigns_total_revenue = [];
        foreach ($result as $v) {
            $campaigns_total_revenue[$v['campaignid']] = $v['total_revenue'];
        }

        foreach ($rows as $item) {
            $row['client_name'] = $item['clientname'];
            $row['campaignid'] = $item['campaignid'];
            $row['products_name'] = $item['name'];
            $row['products_show_name'] = $item['show_name'];
            $row['products_type'] = $item['type'];
            $row['app_name'] = $item['app_name'];
            $row['ad_type'] = $item['ad_type'];
            $row['revenue_type'] = $item['revenue_type'];
            $row['icon'] = $item['icon'];
            $row['approve_user'] = $item['username'];
            $row['approve_time'] = $item['check_date'];
            $row['status'] = $item['materials_status'];
            $row['platform'] = $item['platform'];
            if ($row['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $row['platform'] = Campaign::PLATFORM_IOS;
            }
            $row['total_consume'] = Formatter::asDecimal(
                isset($campaigns_total_revenue[$item['campaignid']]) ?
                    $campaigns_total_revenue[$item['campaignid']] : 0,
                Config::get('biddingos.jsDefaultInit.' . $item['revenue_type'] . '.decimal')
            );
            $row['updated_at'] = $item['updated_at'];
            //将新旧素材加入列表
            $materialData = $this->getMaterialData($item['campaignid']);
            $row['materials_data'] = $materialData['materials_data'];
            $row['materials_new'] = $materialData['materials_new'];
            $list[] = $row;
        }

        return $this->success(null, [
            'pageSize' => $pageSize,
            'pageNo' => $pageNo,
            'count' => $total,
        ], $list);
    }


    /**
     * 素材审核
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | status |  | integer | 状态 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function check(Request $request)
    {
        $action = ArrayHelper::getRequiredIn(Campaign::getActionMaterialLabels());
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'status' => "required|in:{$action}",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = $request->input('campaignid');
        $status = $request->input('status');
        $campaign = Campaign::find($campaignId);
        $appId = $campaign->campaignname;
        //获取appInfo信息
        $app = AppInfo::find($appId);
        if (!$app) {
            return $this->errorCode(5001);
        }
        //如果素材不是待审核状态
        if ($app->materials_status != AppInfo::MATERIAL_STATUS_PENDING_APPROVAL) {
            return $this->errorCode(5028);
        }
        //反序列化素材数据
        $data = json_decode($app->materials_data, true);
        //获取产品类型和ID
        $product = DB::table('products')
            ->Join('campaigns', 'products.id', '=', 'campaigns.product_id')
            ->Join('appinfos', 'campaigns.campaignname', '=', 'appinfos.app_id')
            ->where('campaignname', '=', $appId)
            ->select('products.id as id', 'products.type')
            ->first();
        $packageMd5 = $product->type == Product::TYPE_APP_DOWNLOAD ?
            $data['package']['md5'] : ''; //新包的 md5 值
        $md5_file = $campaign->ad_type == Campaign::AD_TYPE_VIDEO ?
            $data['video']['md5_file'] : '';//视频MD5值

        //获取推广计划ID和广告类型
        if ($status == Campaign::ACTION_MATERIAL_APPROVAL) {
            //审核通过
            if ($data) {
                if ($product->type == Product::TYPE_LINK) {
                    $app->app_show_icon = isset($data['icon']) ? $data['icon'] : $data['app_show_icon'];
                }
                //覆盖值
                $app->old_materials_data = json_encode($this->getOldMaterialData($app, $campaign));
                $app->app_show_name = $data['app_show_name'];
                $app->app_name = $data['app_name'];
                $app->description = isset($data['description']) ? $data['description'] : '';
                $app->profile = $data['profile'];
                $app->title = isset($data['title']) ? $data['title'] : '';
                $app->star = isset($data['star']) ? $data['star'] : 0;
                $app->update_des = isset($data['update_des']) ? $data['update_des'] : '';
                $app->materials_data = '';
                $app->application_id = isset($data['application_id']) ? $data['application_id'] : '';
                if ($campaign['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    $app->appstore_info = CampaignService::getAppIdInfo($data['application_id']);
                }
                $app->check_user = Auth::user()->user_id;
                $app->check_date = date('Y-m-d H:i:s');
                $app->materials_status = AppInfo::MATERIAL_STATUS_APPROVAL;
                //如果是 Banner 广告或者 feeds广告, 审核通过，
                //更新 campaign_images 表中的图片，
                //应用下载更新appinfo 表中的images
                if (!empty($campaign)) {
                    //在配置的类型里要保存到 images
                    if ($campaign['ad_type'] == Campaign::AD_TYPE_APP_MARKET
                        || $campaign['ad_type'] == Campaign::AD_TYPE_APP_STORE
                    ) {
                        $app->images = serialize($data['images']);
                    } else {
                        if (isset($data['images'])) {
                            $this->storeCampaignImages($campaign['campaignid'], $data['images']);
                        }
                    }
                }
                //视频素材处理
                if ($campaign['ad_type'] == Campaign::AD_TYPE_VIDEO) {
                    $campaignVideo = CampaignVideo::where('campaignid', $campaign['campaignid'])
                        ->where('status', CampaignVideo::STATUS_USING)
                        ->select('id', 'url', 'md5_file')
                        ->first();
                    if ($campaignVideo) {
                        if (trim($md5_file) != trim($campaignVideo->md5_file)) {
                            CampaignVideo::where('id', $campaignVideo->id)->update([
                                'status' => CampaignVideo::STATUS_ABANDON,
                            ]);
                        }
                        CampaignVideo::where('campaignid', $campaign['campaignid'])
                            ->where('md5_file', $md5_file)->update([
                                'status' => CampaignVideo::STATUS_USING,
                            ]);
                    }
                }

                //应用下载才有安装包，如果包发生变化，则更新
                if ($product->type == Product::TYPE_APP_DOWNLOAD) {
                    //根据campaign查找到当前最近使用的安装包
                    $attachData = $this->getAttachData($campaign['campaignid']);
                    //如果有在使用中的安装包
                    if (!empty($attachData)) {
                        if (trim($packageMd5) != trim($attachData['md5'])) {
                            //更新  banners 表的使用的包ID
                            $newAttData = $this->getNewAttachData($campaign['campaignid'], $packageMd5);
                            if (!empty($newAttData['id'])) {//更新
                                //替换包给媒体发送
                                MessageService::sendPackageChangeMail($campaign['campaignid'], $attachData['id']);

                                if ($attachData['flag'] == AttachFile::FLAG_USING) {
                                    //把旧包替换成新包的ID
                                    DB::table('banners')
                                        ->where('attach_file_id', $attachData['id'])
                                        ->update([
                                            'attach_file_id' => $newAttData['id'],
                                            'download_url' => CampaignService::attachFileLink($newAttData['id'])
                                        ]);

                                    //新包被替换之后要刷新 BannerText
                                    $bannerData = Banner::where('attach_file_id', $newAttData['id'])
                                        ->select('bannerid')
                                        ->get()
                                        ->toArray();
                                    if (!empty($bannerData)) {
                                        foreach ($bannerData as $bannerKey => $bannerObj) {
                                            $banner = Banner::find($bannerObj['bannerid']);
                                            $banner->buildBannerText();
                                            $banner->save();
                                        }
                                    }
                                    //把当前包置为丢弃
                                    AttachFile::where('id', $attachData['id'])
                                        ->update(['flag' => AttachFile::FLAG_ABANDON]);
                                    //新包设置为使用中
                                    AttachFile::where('id', $newAttData['id'])
                                        ->update([
                                            'flag' => AttachFile::FLAG_USING,
                                            'channel' => $attachData['channel']
                                        ]);
                                } else {
                                    //把当前包置为丢弃
                                    AttachFile::where('id', $attachData['id'])
                                        ->update(['flag' => AttachFile::FLAG_ABANDON]);
                                    //新包设置为使用中
                                    AttachFile::where('id', $newAttData['id'])
                                        ->update([
                                            'flag' => AttachFile::FLAG_NOT_USED,
                                            'channel' => $attachData['channel']
                                        ]);
                                }
                            }
                        }
                    } else {
                        $newAttData = $this->getNewAttachData($campaign['campaignid'], $packageMd5);
                        if (!empty($newAttData)) {
                            $count = AttachFile::where('id', $newAttData['id'])
                                ->whereNull('channel')
                                ->count();
                            if ($count > 0) {
                                //渠道号为空时，填充上一个未使用包的渠道号
                                $ret = AttachFile::where('flag', AttachFile::FLAG_NOT_USED)
                                    ->whereNotNull('channel')
                                    ->select('channel')
                                    ->orderBy('created_at', 'DESC')
                                    ->first();

                                AttachFile::where('id', $newAttData['id'])
                                    ->update(['flag' => AttachFile::FLAG_NOT_USED,
                                        'channel' => $ret['channel']]);
                            } else {
                                AttachFile::where('id', $newAttData['id'])
                                    ->update(['flag' => AttachFile::FLAG_NOT_USED]);
                            }
                        }
                    }
                }
                // 增加修改up_products
                $newIcon = $product->type == Product::TYPE_LINK ? '' :
                    (isset($data['icon']) ? $data['icon'] : $data['app_show_icon']);
                Product::where('id', $product->id)
                    ->update([
                        'show_name' => $data['app_show_name'],
                        'icon' => $newIcon,
                        'link_name' => $data ['link_name'],
                        'link_url' => $data ['link_url'],
                        'name' => $data ['name']
                    ]);

                //审核素材时修改出价
                $oldRevenue = $campaign->revenue;//获取旧的出价
                $oldTotalLimit = $campaign->total_limit;//获取旧的总预算
                $oldDayLimit = $campaign->day_limit;//获取旧日预算
                $campaign->revenue = $data['revenue'];
                if (isset($data['total_limit'])) {
                    $campaign->total_limit = $data['total_limit'];
                }
                if (isset($data['day_limit'])) {
                    $campaign->day_limit = $data['day_limit'];
                }
                if ($campaign->save()) {
                    LogHelper::info('campaign ' . $campaign->campaignid . ' revenue change from ' .
                        $oldRevenue . ' to ' . $data['revenue']);
                    $params = [
                        'campaignid' => $campaign->campaignid,
                        'time' => gmdate('Y-m-d H:i:s'),
                        'history_revenue' => $oldRevenue,
                        'current_revenue' => $data['revenue']
                    ];

                    //保存推广计划历史出价
                    CampaignRevenueHistory::storeCampaignHistoryRevenue($params);
                    $res = CampaignService::findBanners($campaign->ad_type, $campaign->campaignid);
                    if (count($res) > 0) {
                        //广告主出价是否比设定的出价低则,更新为广告主最新的出价
                        DB::table('banners')->where('campaignid', $campaign->campaignid)
                            ->where('revenue_price', '>', $data['revenue'])
                            ->where('revenue_price', '>', 0)
                            ->update([
                                'revenue_price' => $data['revenue']
                            ]);
                        foreach ($res as $r) {
                            //if ($r->revenue_price > $data['revenue']) {
                            //同步计算广告计费价及媒体价
                            CampaignService::updateBannerBilling($r->bannerid, true);
                            //}
                        }
                    }

                    if (isset($data['total_limit'])) {
                        //启动因总预算暂停的广告
                        if ($data['total_limit'] == 0 ||
                            ($data['total_limit'] > 0 && $data['total_limit'] > $oldTotalLimit)
                        ) {
                            if ($campaign->status == Campaign::STATUS_SUSPENDED
                                && $campaign->pause_status == Campaign::PAUSE_STATUS_EXCEED_TOTAL_LIMIT
                            ) {
                                $ret = CampaignService::modifyStatus(
                                    $campaign->campaignid,
                                    Campaign::STATUS_DELIVERING,
                                    ['pause_status' => Campaign::PAUSE_STATUS_PLATFORM],
                                    false
                                );
                                if ($ret !== true) {
                                    return $this->errorCode($ret);
                                }
                                //写一条审计日志
                                $clientRow = CampaignService::getClientInfoByClientID($campaign->clientid);
                                $clientName = !empty($clientRow) ? $clientRow['clientname'] : '';
                                $message = CampaignService::formatWaring(
                                    6005,
                                    [
                                        $clientName,
                                        $oldTotalLimit == 0 ? '不限' : sprintf("%.2f", $oldTotalLimit),
                                        $data['total_limit'] == 0 ? '不限' : sprintf("%.2f", $data['total_limit'])
                                    ]
                                );
                                OperationLog::store([
                                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                                    'target_id' => $campaign->campaignid,
                                    'operator' => Config::get('error')[6000],
                                    'type' => OperationLog::TYPE_SYSTEM,
                                    'message' => $message,
                                ]);
                                LogHelper::info("total_limit modify,
                        campaign {$campaign->campaignid} status from {$oldTotalLimit} to {$data['total_limit']} ");
                            }
                        }
                    }

                    if (isset($data['day_limit'])) {
                        if ($data['day_limit'] > $oldDayLimit) {
                            //广告主日预算=程序化日预算
                            if ($oldDayLimit == $campaign->day_limit_program) {
                                $campaign->day_limit_program = $data['day_limit'];
                                $campaign->save();
                            }

                            if ($campaign->status == Campaign::STATUS_SUSPENDED
                                && $campaign->pause_status == Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT
                            ) {
                                $ret = CampaignService::modifyStatus(
                                    $campaign->campaignid,
                                    Campaign::STATUS_DELIVERING,
                                    ['pause_status' => Campaign::PAUSE_STATUS_PLATFORM],
                                    false
                                );
                                if ($ret !== true) {
                                    return $this->errorCode($ret);
                                }
                                //写一条审计日志
                                $clientRow = CampaignService::getClientInfoByClientID($campaign->clientid);
                                $clientName = !empty($clientRow) ? $clientRow['clientname'] : '';
                                $message = CampaignService::formatWaring(
                                    6007,
                                    [
                                        $clientName,
                                        sprintf("%.2f", $oldDayLimit),
                                        sprintf("%.2f", $data['day_limit'])
                                    ]
                                );
                                OperationLog::store([
                                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                                    'target_id' => $campaign->campaignid,
                                    'operator' => Config::get('error')[6000],
                                    'type' => OperationLog::TYPE_SYSTEM,
                                    'operator' => Config::get('error')[6000],
                                    'message' => $message,
                                ]);
                                LogHelper::info("day_limit modify,
                        campaign {$campaign->campaignid} status from {$oldDayLimit} to {$data['day_limit']} ");

                                //启动因日预算暂停的媒体广告
                                BannerService::recoverBanner($campaign->campaignid);
                            }
                        }
                    }
                }
            }

            //如果大唐有有使用此素材，则也更新（调用大唐接口进行更新）
            $affiliateId = Affiliate::where('symbol', 'datang')->pluck('affiliateid');
            if (!empty($affRow)) {
                /*根据 appid从 campaigns 表中取得 campaignid,
                //因为 campaigns 表中的一个 appid 对应 appinfo 表中的多个，
                //所以根据任何一个 appid 都能定位 campaignid*/
                $campaignId = Campaign::where('campaignname', $appId)->pluck('campaignid');
                if (!empty($campaignRow)) {
                    $bannerId = Banner::where('campaignid', $campaignId)
                        ->where('affiliateid', $affiliateId)
                        ->pluck('bannerid');
                    if (!empty($bannerRow)) {
                        SymbolFactory::getClass('datang')->getValue(array('key' => $bannerId, 'platForm' => 3));
                    }
                }
            }

            //审核通过之后都需要刷新一下 bannerText
            $banners = Banner::where('campaignid', $campaignId)->get();
            if (!empty($banners)) {
                foreach ($banners as $k => $b) {
                    $b->buildBannerText();
                    $b->save();
                }
            }

            //等价包关联
            $key = $campaign->equivalence;
            if ($key) {
                CampaignService::attachEquivalencePackageName($key);
            }

            if ($campaign->ad_type == Campaign::AD_TYPE_APP_STORE) {
                $product = Product::find($campaign->product_id);
                if (!empty($product->link_url)) {
                    $ret = CampaignService::validURL($product->link_url);
                    $product->link_status = $ret == true ?
                        Product::LINK_STATUS_ENABLE : Product::LINK_STATUS_DISABLE;
                    $product->save();
                }
            }
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'target_id' => $campaignId,
                'type' => OperationLog::TYPE_MANUAL,
                'operator' => Auth::user()->contact_name,
                'message' => CampaignService::formatWaring(6017),
            ]);
        } else {
            //审核不通过
            if (!empty($data)) {
                if ($product->type == Product::TYPE_APP_DOWNLOAD) {
                    $newAttData = $this->getNewAttachData($campaign['campaignid'], $packageMd5);
                    $attachData = $this->getAttachData($campaign['campaignid']);
                    /* 广告主修改素材时候，上传是旧包
                     *（已经上传过）
                     * 素材审核不通过不更改包状态。如果上传的新包
                     * （没有上传过的包）
                     * 素材审核不通过更改新包状态为不通过审核
                     **/
                    if ($newAttData['flag'] == AttachFile::FLAG_PENDING_APPROVAL) {
                        if (!empty($attachData)) {
                            $channel = $attachData['channel'];
                        } else {
                            $channel = !empty($newAttData['channel']) ? $newAttData['channel'] : '';
                        }
                        //把包的状态修改为不通过审核
                        AttachFile::where('id', $newAttData['id'])
                            ->update(['flag' => AttachFile::FLAG_REJECTED, 'channel' => $channel]);
                    }
                }
                //视频素材审核不通过
                if ($campaign->ad_type == Campaign::AD_TYPE_VIDEO) {
                    $newVideo = CampaignVideo::where('campaignid', $campaign['campaignid'])
                        ->where('md5_file', $md5_file)
                        ->first();
                    if ($newVideo && $newVideo->status == CampaignVideo::STATUS_PENDING_APPROVAL) {
                        CampaignVideo::where('id', $newVideo->id)
                            ->update(['status' => CampaignVideo::STATUS_REJECTED]);
                    }
                }
            }
            $app->materials_status = AppInfo::MATERIAL_STATUS_REJECT;
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'target_id' => $campaign['campaignid'],
                'type' => OperationLog::TYPE_MANUAL,
                'operator' => Auth::user()->contact_name,
                'message' => CampaignService::formatWaring(6018),
            ]);
        }
        if (!$app->save()) {
            return $this->errorCode(5001);
        }
        return $this->success();
    }

    /**
     * 保存广告的图片
     * @param $campaignId
     * @param $images
     * @codeCoverageIgnore
     */
    private function storeCampaignImages($campaignId, $images)
    {
        //当前需要修改素材规格
        $adSpec = array_column($images, 'spec');
        //删除移除的图片
        CampaignImage::where('campaignid', $campaignId)
            ->whereNotIn('ad_spec', $adSpec)
            ->delete();

        foreach ($images as $item) {
            if (!isset($item['spec']) || !isset($item['imgURL'])) {
                continue;
            }
            $size = explode(',', $item['size']);
            if (count($size) != 2) {
                continue;
            }
            $width = $size[0];
            $height = $size[1];
            $result = CampaignImage::whereMulti(['campaignid' => $campaignId, 'ad_spec' => $item['spec']])->first();
            if (count($result)) {
                $campaignImage = CampaignImage::find($result->id);
                $campaignImage->campaignid = $campaignId;
                $campaignImage->ad_spec = $item['spec'];
                $campaignImage->url = $item['imgURL'];
                $campaignImage->scale = round($width / $height, 2);
                $campaignImage->width = $width;
                $campaignImage->height = $height;
                $campaignImage->type = CampaignImage::getImageType($item['imgURL']);
                $campaignImage->save();
            } else {
                $campaignImage = new CampaignImage();
                $campaignImage->campaignid = $campaignId;
                $campaignImage->ad_spec = $item['spec'];
                $campaignImage->url = $item['imgURL'];
                $campaignImage->scale = round($width / $height, 2);
                $campaignImage->width = $width;
                $campaignImage->height = $height;
                $campaignImage->type = CampaignImage::getImageType($item['imgURL']);
                $campaignImage->save();
            }
        }
    }

    /**
     * 获取旧包信息
     * @param $campaignId
     * @param $md5
     * @return mixed
     * @codeCoverageIgnore
     */
    private function getAttachData($campaignId)
    {
        $attachData = AttachFile::where('campaignid', $campaignId)
            ->whereIn('flag', [AttachFile::FLAG_USING])
            ->select('id', 'hash AS md5', 'channel', 'file as download_url', 'real_name', 'flag')
            ->orderBy('created_at', 'DESC')
            ->first();
        if ($attachData) {
            return $attachData->toArray();
        }
        return null;
    }

    /**
     * 获取新包信息
     * @param $campaignId
     * @param $md5
     * @return mixed
     * @codeCoverageIgnore
     */
    private function getNewAttachData($campaignId, $md5)
    {
        $newAttData = AttachFile::where('campaignid', $campaignId)
            ->where('hash', $md5)
            ->select('id', 'channel', 'file', 'real_name', 'reserve', 'package_name', 'flag')
            ->first();
        if ($newAttData) {
            return $newAttData->toArray();
        }
        return null;
    }

    /**
     * 获取旧素材信息
     * @param $app
     * @param $campaign
     * @return string
     */
    private function getOldMaterialData($app, $campaign)
    {
        //处理旧appInfo信息
        $oldMaterialData = [
            'app_show_name' => $app->app_show_name,
            'icon' => $campaign->product->type == Product::TYPE_APP_DOWNLOAD ?
                $campaign->product->icon : $app->app_show_icon,
            'description' => $app->description,
            'profile' => $app->profile,
            'update_desc' => $app->update_des,
            'rank' => $app->star,
            'link_name' => $campaign->product->link_name,
            'link_url' => $campaign->product->link_url,
            'title' => $app->title,
            'revenue' => Formatter::asDecimal(
                $campaign->revenue,
                Config::get('biddingos.jsDefaultInit.' . $campaign->revenue_type . '.decimal')
            ),
            'total_limit' => intval($campaign->total_limit),
            'day_limit' => intval($campaign->day_limit),
        ];
        $flag = $app->materials_status == AppInfo::MATERIAL_STATUS_REJECT ?
            [AttachFile::FLAG_REJECTED] : [AttachFile::FLAG_USING];
        //处理旧包信息
        $attachData = AttachFile::where('campaignid', $campaign->campaignid)
            ->whereIn('flag', $flag)
            ->select('hash', 'file', 'real_name')
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!empty($attachData)) {
            $oldMaterialData['real_name'] = $attachData->real_name;
            $oldMaterialData['download_url'] = UrlHelper::fileFullUrl($attachData->file, $attachData->real_name);
            $oldMaterialData['package_md5'] = $attachData->hash;
        } else {
            if ($app->materials_status == AppInfo::MATERIAL_STATUS_APPROVAL) {
                $materialData = json_decode($app->old_materials_data, true);
                $oldMaterialData['real_name'] = isset($materialData['real_name'])
                    ? $materialData['real_name'] : '';
                $oldMaterialData['download_url'] = isset($materialData['download_url']) ?
                    $materialData['download_url'] : '';
                $oldMaterialData['package_md5'] = isset($materialData['package_md5']) ?
                    $materialData['package_md5'] : '';
            } else {

                $materialData = json_decode($app->materials_data, true);
                $oldMaterialData['real_name'] = isset($materialData['package']['real_name'])
                    ? $materialData['package']['real_name'] : '';
                $oldMaterialData['download_url'] = isset($materialData['package']['path']) ?
                    UrlHelper::fileFullUrl(
                        $materialData['package']['path'],
                        $materialData['package']['real_name']
                    ) : '';
                $oldMaterialData['package_md5'] = isset($materialData['package']['md5']) ?
                    $materialData['package']['md5'] : '';
            }
        }
        //处理视频
        $status = $app->materials_status == AppInfo::MATERIAL_STATUS_REJECT ?
            [CampaignVideo::STATUS_REJECTED] : [CampaignVideo::STATUS_USING];
        $campaignVideo = CampaignVideo::where('campaignid', $campaign->campaignid)
            ->whereIn('status', $status)
            ->select('url', 'md5_file')
            ->orderBy('created_time', 'DESC')
            ->first();
        if (!empty($campaignVideo)) {
            $oldMaterialData['url'] = $campaignVideo->url;
            $oldMaterialData['md5_file'] = $campaignVideo->md5_file;
        } else {
            if ($app->materials_status == AppInfo::MATERIAL_STATUS_APPROVAL) {
                $materialData = json_decode($app->old_materials_data, true);
                $oldMaterialData['url'] = isset($materialData['url'])
                    ? $materialData['url'] : '';
                $oldMaterialData['md5_file'] = isset($materialData['md5_file'])
                    ? $materialData['md5_file'] : '';
            } else {
                $materialData = json_decode($app->materials_data, true);
                $oldMaterialData['url'] = isset($materialData['video']['url'])
                    ? $materialData['video']['url'] : '';
                $oldMaterialData['md5_file'] = isset($materialData['video']['md5_file']) ?
                    $materialData['video']['md5_file'] : '';
            }
        }

        //处理旧图片
        if ($campaign->ad_type == Campaign::AD_TYPE_APP_MARKET || $campaign->ad_type == Campaign::AD_TYPE_APP_STORE) {
            //旧应用素材图片
            if (!empty($app->images)) {
                $images = unserialize($app->images);
                if (ArrayHelper::arrayLevel($images) == 1) {
                    $sizeArr = explode("*", Config::get('ad_spec.' . $campaign->ad_type . '.1'));
                    $oldMaterialData['images'] = [
                        1 => [
                            'ad_spec' => 1,
                            'url' => $images,
                            'height' => intval($sizeArr[1]),
                            'width' => intval($sizeArr[0]),
                        ],
                    ];
                } else {
                    foreach ($images as $ik => $iv) {
                        $sizeArr = explode("*", Config::get('ad_spec.' . $campaign->ad_type . '.' . $ik));
                        $oldMaterialData['images'][$ik] = [
                            'ad_spec' => $ik,
                            'url' => $iv,
                            'height' => intval($sizeArr[1]),
                            'width' => intval($sizeArr[0]),
                        ];
                    }
                }
            }
        } else {
            $sizeArr = Config::get('ad_spec.' . $campaign->ad_type);
            //旧素材图片
            $images = CampaignImage::where('campaignid', $campaign->campaignid)
                ->select('ad_spec', 'url', 'width', 'height')
                ->get()
                ->toArray();
            if (!empty($images)) {
                foreach ($images as $ik) {
                    //非配置文件中图片不显示
                    if (in_array($ik['width'] . '*' . $ik['height'], $sizeArr)) {
                        $oldMaterialData['images'][] = $ik;
                    }
                }
            }
        }

        return $oldMaterialData;
    }

    /**
     * 查看素材信息
     * @param $campaignId
     * @return array
     */
    private function getMaterialData($campaignId)
    {
        $campaign = Campaign::find($campaignId);
        $appInfo = $campaign->appinfo;
        $newMaterialData = [];
        $oldMaterialData = [];

        //未通过审核
        if ($appInfo->materials_status == AppInfo::MATERIAL_STATUS_REJECT) {
            $oldMaterialData = $this->getOldMaterialData($appInfo, $campaign);
            $materialData = json_decode($appInfo->materials_data, true);
            $newMaterialData = [
                'app_show_name' => $materialData['app_show_name'],
                'icon' => isset($materialData['icon']) ? $materialData['icon'] : $materialData['app_show_icon'],
                'description' => isset($materialData['description']) ? $materialData['description'] : '',
                'profile' => isset($materialData['profile']) ? $materialData['profile'] : '',
                'update_desc' => isset($materialData['update_des']) ? $materialData['update_des'] : '',
                'star' => isset($materialData['star']) ? $materialData['star'] : 0,
                'link_name' => isset($materialData['link_name']) ? $materialData['link_name'] : '',
                'link_url' => isset($materialData['link_url']) ? $materialData['link_url'] : '',
                'title' => isset($materialData['title']) ? $materialData['title'] : '',
                'revenue' => Formatter::asDecimal(
                    $materialData['revenue'],
                    Config::get('biddingos.jsDefaultInit.' . $campaign->revenue_type . '.decimal')
                ),
                'total_limit' => isset($materialData['total_limit']) ? intval($materialData['total_limit']) : 0,
                'day_limit' => isset($materialData['day_limit']) ? intval($materialData['day_limit']) : 0,
                'real_name' => isset($materialData['package']['real_name']) ?
                    $materialData['package']['real_name'] : '',
                'download_url' => isset($materialData['package']['path']) ?
                    UrlHelper::fileFullUrl(
                        $materialData['package']['path'],
                        $materialData['package']['real_name']
                    ) : '',
                'package_md5' => isset($materialData['package']['md5']) ? $materialData['package']['md5'] : '',
                'url' => isset($materialData['video']['url']) ? $materialData['video']['url'] : '',
                'md5_file' => isset($materialData['video']['md5_file']) ? $materialData['video']['md5_file'] : '',
            ];

            if ($campaign->ad_type == Campaign::AD_TYPE_APP_MARKET ||
                $campaign->ad_type == Campaign::AD_TYPE_APP_STORE
            ) {
                $newMaterialData['images'] = $this->getMaterialImage($materialData, $campaign->ad_type);
            } else {
                //新素材图片
                if (isset($materialData['images'])) {
                    foreach ($materialData['images'] as $itemImg) {
                        $size = explode(",", $itemImg['size']);
                        $newMaterialData['images'][] = [
                            'ad_spec' => intval($itemImg['spec']),
                            'url' => UrlHelper::imageFullUrl($itemImg['imgURL']),
                            'width' => intval($size[0]),
                            'height' => intval($size[1]),
                        ];
                    }
                }
            }
        } elseif ($appInfo->materials_status == AppInfo::MATERIAL_STATUS_APPROVAL) {
            $newMaterialData = $this->getOldMaterialData($appInfo, $campaign);
            if ($appInfo->old_materials_data) {
                $oldMaterialData = json_decode($appInfo->old_materials_data, true);
                $oldMaterialData['total_limit'] = isset($oldMaterialData['total_limit']) ?
                    intval($oldMaterialData['total_limit']) : 0;
                $oldMaterialData['day_limit'] = isset($oldMaterialData['day_limit']) ?
                    intval($oldMaterialData['day_limit']) : 0;
            } else {
                $oldMaterialData = [];
            }
        } elseif ($appInfo->materials_status == AppInfo::MATERIAL_STATUS_PENDING_APPROVAL) {
            $materialData = json_decode($appInfo->materials_data, true);
            $newMaterialData = [
                'app_show_name' => $materialData['app_show_name'],
                'real_name' => isset($materialData['package']['real_name'])
                    ? $materialData['package']['real_name'] : '',
                'download_url' => isset($materialData['package']['path']) ?
                    UrlHelper::fileFullUrl(
                        $materialData['package']['path'],
                        $materialData['package']['real_name']
                    ) : '',
                'package_md5' => isset($materialData['package']['md5']) ?
                    $materialData['package']['md5'] : '',
                'icon' => isset($materialData['icon']) ?
                    $materialData['icon'] : $materialData['app_show_icon'],
                'description' => isset($materialData['description']) ? $materialData['description'] : '',
                'profile' => isset($materialData['profile']) ? $materialData['profile'] : '',
                'update_desc' => isset($materialData['update_des']) ? $materialData['update_des'] : '',
                'rank' => isset($materialData['star']) ? intval($materialData['star']) : 0,
                'link_name' => isset($materialData['link_name']) ? $materialData['link_name'] : '',
                'link_url' => isset($materialData['link_url']) ? $materialData['link_url'] : '',
                'title' => isset($materialData['title']) ? $materialData['title'] : '',
                'revenue' => Formatter::asDecimal(
                    $materialData['revenue'],
                    Config::get('biddingos.jsDefaultInit.' . $campaign->revenue_type . '.decimal')
                ),
                'total_limit' => isset($materialData['total_limit']) ? intval($materialData['total_limit']) : 0,
                'day_limit' => isset($materialData['day_limit']) ? intval($materialData['day_limit']) : 0,
                'url' => isset($materialData['video']['url'])
                    ? $materialData['video']['url'] : '',
                'md5_file' => isset($materialData['video']['md5_file'])
                    ? $materialData['video']['md5_file'] : '',
            ];

            $oldMaterialData = [
                'app_show_name' => $appInfo->app_show_name,
                'icon' => $campaign->product->type == Product::TYPE_APP_DOWNLOAD ?
                    $campaign->product->icon : $appInfo->app_show_icon,
                'description' => $appInfo->description,
                'profile' => $appInfo->profile,
                'update_desc' => $appInfo->update_des,
                'rank' => $appInfo->star,
                'link_name' => $campaign->product->link_name,
                'link_url' => $campaign->product->link_url,
                'title' => $appInfo->title,
                'revenue' => Formatter::asDecimal(
                    $campaign->revenue,
                    Config::get('biddingos.jsDefaultInit.' . $campaign->revenue_type . '.decimal')
                ),
                'total_limit' => intval($campaign->total_limit),
                'day_limit' => intval($campaign->day_limit),
            ];

            //取得最近更新过未使用，使用中的包信息
            $data = AttachFile::where('campaignid', $campaignId)
                ->whereIn('flag', [AttachFile::FLAG_USING])
                ->select('hash', 'file', 'real_name')
                ->orderBy('created_at', 'DESC')
                ->first();
            if (!empty($data)) {
                $data = $data->toArray();
                $oldMaterialData['real_name'] = $data['real_name'];
                $oldMaterialData['download_url'] = UrlHelper::fileFullUrl($data['file'], $data['real_name']);
                $oldMaterialData['package_md5'] = $data['hash'];
            } else {
                $oldMaterialData['real_name'] = isset($materialData['package']['real_name'])
                    ? $materialData['package']['real_name'] : '';
                $oldMaterialData['download_url'] = isset($materialData['package']['path']) ?
                    UrlHelper::fileFullUrl(
                        $materialData['package']['path'],
                        $materialData['package']['real_name']
                    ) : '';
                $oldMaterialData['package_md5'] = isset($materialData['package']['md5']) ?
                    $materialData['package']['md5'] : '';
            }
            //获取视频信息
            $camaginVideo = CampaignVideo::where('campaignid', $campaignId)
                ->where('status', CampaignVideo::STATUS_USING)
                ->select('url', 'md5_file')
                ->orderBy('created_time', 'DESC')
                ->first();
            if (!empty($camaginVideo)) {
                $oldMaterialData['url'] = $camaginVideo->url;
                $oldMaterialData['md5_file'] = $camaginVideo->md5_file;
            } else {
                $oldMaterialData['url'] = isset($materialData['video']['url'])
                    ? $materialData['video']['url'] : '';
                $oldMaterialData['md5_file'] = isset($materialData['video']['md5_file'])
                    ? $materialData['video']['md5_file'] : '';
            }

            if ($campaign->ad_type == Campaign::AD_TYPE_APP_MARKET ||
                $campaign->ad_type == Campaign::AD_TYPE_APP_STORE
            ) {
                $newMaterialData['images'] = $this->getMaterialImage($materialData, $campaign->ad_type);
                //旧应用素材图片
                if (!empty($appInfo->images)) {
                    $images = unserialize($appInfo->images);
                    if (ArrayHelper::arrayLevel($images) == 1) {
                        $sizeArr = explode("*", Config::get('ad_spec.' . $campaign->ad_type . '.1'));
                        $oldMaterialData['images'] = [
                            1 => [
                                'ad_spec' => 1,
                                'url' => $images,
                                'height' => intval($sizeArr[1]),
                                'width' => intval($sizeArr[0]),
                            ],
                        ];
                    } else {
                        foreach ($images as $ik => $iv) {
                            $sizeArr = explode("*", Config::get('ad_spec.' . $campaign->ad_type . '.' . $ik));
                            $oldMaterialData['images'][$ik] = [
                                'ad_spec' => intval($ik),
                                'url' => $iv,
                                'height' => intval($sizeArr[1]),
                                'width' => intval($sizeArr[0]),
                            ];
                        }
                    }
                }
            } else {
                //新素材图片
                if (isset($materialData['images'])) {
                    foreach ($materialData['images'] as $itemImg) {
                        $size = explode(",", $itemImg['size']);
                        $newMaterialData['images'][] = [
                            'ad_spec' => intval($itemImg['spec']),
                            'url' => UrlHelper::imageFullUrl($itemImg['imgURL']),
                            'width' => intval($size[0]),
                            'height' => intval($size[1]),
                        ];
                    }
                }

                $sizeArr = Config::get('ad_spec.' . $campaign->ad_type);
                //旧素材图片
                $images = CampaignImage::where('campaignid', $campaignId)
                    ->select('ad_spec', 'url', 'width', 'height')
                    ->get()->toArray();
                if (!empty($images)) {
                    foreach ($images as $ik) {
                        //非配置文件中图片不显示
                        if (in_array($ik['width'] . '*' . $ik['height'], $sizeArr)) {
                            $oldMaterialData['images'][] = $ik;
                        }
                    }
                }
            }
        }

        return [
            'materials_data' => $oldMaterialData,
            'materials_new' => $newMaterialData,
        ];
    }


    private function getMaterialImage($materialData, $adType)
    {
        $newMaterialData = [];
        if (!empty($materialData['images'])) {
            //新应用素材图片
            if (ArrayHelper::arrayLevel($materialData['images']) == 1) {
                $sizeArr = explode("*", Config::get('ad_spec.' . $adType . '.1'));
                $newMaterialData = [
                    1 => [
                        'ad_spec' => 1,
                        'url' => $materialData['images'],
                        'height' => intval($sizeArr[1]),
                        'width' => intval($sizeArr[0]),
                    ],
                ];
            } else {
                foreach ($materialData['images'] as $ik => $iv) {
                    $sizeArr = explode("*", Config::get('ad_spec.' . $adType . '.' . $ik));
                    if (count($sizeArr) == 2) {
                        $newMaterialData[$ik] = [
                            'ad_spec' => intval($ik),
                            'url' => $iv,
                            'height' => intval($sizeArr[1]),
                            'width' => intval($sizeArr[0]),
                        ];
                    }
                }
            }
        }

        return $newMaterialData;
    }
}
