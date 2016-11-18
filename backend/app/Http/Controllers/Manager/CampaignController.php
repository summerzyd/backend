<?php
namespace App\Http\Controllers\Manager;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Components\Helper\UrlHelper;
use App\Http\Controllers\Controller;
use App\Models\AdZoneKeyword;
use App\Models\Affiliate;
use App\Models\AttachFile;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignVideo;
use App\Models\EquivalenceAssoc;
use App\Models\CampaignImage;
use App\Models\CampaignRevenueHistory;
use App\Models\Client;
use App\Models\OperationLog;
use App\Models\Product;
use App\Models\User;
use App\Services\BannerService;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use App\Components\Config;
use Illuminate\Support\Facades\DB;
use Auth;
use Illuminate\Support\Facades\Redis;
use App\Components\Helper\HttpClientHelper;

class CampaignController extends Controller
{
    /**
     * 获取广告管理列表
     *
     * | name | sub name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | pageNo |  | integer | 请求页数 |  | 是 |
     * | pageSize |  | integer | 请求每页数量 |  | 是 |
     * | search |  | string | 搜索关键字 |  | 是 |
     * | sort |  | integer | 排序字段 |  | 是 |
     * | filter |  | integer | 过滤 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * | products_name |  | string | 应用名称 |  | 是 |
     * | products_show_name |  | integer | 应用显示名称 |  | 是 |
     * | products_type |  | integer | 应用类型 |  | 是 |
     * | appinfos_app_name |  | string | 广告名称 |  | 是 |
     * | appinfos_app_show_icon |  | string | 应用图标 |  | 是 |
     * | approve_comment |  | string | 审核说明/暂停说明 |  | 是 |
     * | opertion_time |  | datetime | 操作时间 |  | 是 |
     * | updated_user |  | string | 操作人 |  | 是 |
     * | platform |  | integer | 平台类型 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * | revenue |  | integer | 广告主出价 |  | 是 |
     * | keyword_price_up_count |  | integer | 加价关键字数量 |  | 是 |
     * | rate |  | integer | 流量变现比例 |  | 是 |
     * | status |  | integer | 状态 |  | 是 |
     * | pause_status |  | integer | 暂停状态 |  | 是 |
     * | brief_name |  | string | 广告主名称 |  | 是 |
     * | contact |  | string | 联系人 |  | 是 |
     * | contact_phone |  | string | 联系方式 |  | 是 |
     * | email |  | string | 邮件 |  | 是 |
     * | qq |  | string | QQ |  | 是 |
     * | broker_brief_name |  | string | 代理商名称 |  | 是 |
     * | broker_contact |  | string | 联系人 |  | 是 |
     * | broker_contact_phone |  | string | 联系方式 |  | 是 |
     * | broker_email |  | string | 邮件 |  | 是 |
     * | broker_qq |  | string | QQ |  | 是 |
     * | broker_id |  | integer | 代理商ID |  | 是 |
     * | compare_version |  | integer | 是否需要更新包 |  | 是 |
     * | day_limit |  | integer | 日限额 |  | 是 |
     * | total_limit |  | integer | 总限额 |  | 是 |
     * | equivalence |  | integer | 等价关系 |  | 是 |
     * | business_type | | tinyint | 业务类型 | | 是 |
     * | creator_name | | string | 销售顾问 | | 是 |
     * | operation_name | | datetime | 创建时间 | | 是 |
     * | created_at | | datetime | 创建时间 | | 是 |
     *
     */
    public function index(Request $request)
    {
        $params = $request->all();
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认25
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1

        $filter = json_decode($request->input('filter'), true);

        //每页条数
        $data = $this->getCampaignByManager([
            Campaign::STATUS_DELIVERING,
            Campaign::STATUS_SUSPENDED,
            Campaign::STATUS_PENDING_APPROVAL,
            Campaign::STATUS_REJECTED,
            Campaign::STATUS_STOP_DELIVERING,
        ], $pageNo, $pageSize, $filter, $params);

        if (count($data['data'])) {
            //获取关键字
            $campaignIds = array_column($data['data'], 'campaignid');
            $kwpTmp = CampaignService::getCampaignKeywordsCount($campaignIds);
            $kwpCount = [];
            foreach ($kwpTmp as $up) {
                $kwpCount[$up['campaignid']] = $up['cnt'];
            }

            //统计投放中媒体移出循环
            $launchedMedia = Banner::whereIn('campaignid', $campaignIds)
                ->join('affiliates', 'affiliates.affiliateid', '=', 'banners.affiliateid')
                ->where('affiliates.affiliates_status', Affiliate::STATUS_ENABLE)
                ->where('affiliates.agencyid', Auth::user()->agencyid)
                ->select(DB::raw('COUNT(1) as cnt'), 'campaignid')
                ->where(function ($query) {
                    $query->where('banners.status', Banner::STATUS_PUT_IN)
                        ->orWhere(function ($query) {
                            $query->where('banners.status', Banner::STATUS_SUSPENDED)
                                ->where('banners.pause_status', Banner::PAUSE_STATUS_EXCEED_DAY_LIMIT);
                        });
                })
                ->groupBy('campaignid')
                ->get()
                ->toArray();
            $launchedMedia = ArrayHelper::map($launchedMedia, 'campaignid', 'cnt');

            foreach ($data['data'] as &$item) {
                $item['keyword_price_up_count'] = isset($kwpCount[$item['campaignid']]) ?
                    $kwpCount[$item['campaignid']] : 0;
                $item['revenue'] = Formatter::asDecimal($item['revenue']);
                $item['day_limit'] = Formatter::asDecimal($item['day_limit'], 0);
                $item['total_limit'] = Formatter::asDecimal($item['total_limit']);
                if ($item['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                    $item['platform'] = Campaign::PLATFORM_IOS;
                }
                //查找等价广告
                if ($item['ad_type'] == Campaign::AD_TYPE_APP_MARKET && !empty($item['equivalence'])) {
                    $item['equivalence'] = Campaign::where('equivalence', $item['equivalence'])->count() - 1;
                } else {
                    $item['equivalence'] = 0;
                }

                // 找出campaign使用的所有包 --->  对使用的包比对 --->  不是最新包就提醒
                $item['compare_version'] = 0;
                $package_name = DB::table('attach_files as a')
                    ->where('campaignid', $item['campaignid'])
                    ->whereIn('flag', [AttachFile::FLAG_NOT_USED, AttachFile::FLAG_USING])
                    ->get(['package_name', 'id']);
                foreach ($package_name as $val) {
                    // 存在需要提醒的包就退出其他包对比操作
                    if ($item['compare_version']) {
                        break;// @codeCoverageIgnore
                    }
                    $maxVersion = AttachFile::where('package_name', $val->package_name)->max('market_version_code');
                    if (!$maxVersion) {
                        $item['compare_version'] = 0;// @codeCoverageIgnore
                    } else {// @codeCoverageIgnore
                        $count = DB::table('attach_files AS attach')
                            ->leftJoin('banners AS b', 'b.attach_file_id', '=', 'attach.id')
                            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
                            ->leftJoin('affiliates AS a', 'a.affiliateid', '=', 'b.affiliateid')
                            ->where('b.attach_file_id', $val->id)
                            ->where('c.campaignid', $item['campaignid'])
                            ->whereIn('a.mode', [
                                Affiliate::MODE_PROGRAM_DELIVERY_STORAGE,
                                Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE
                            ])->where('attach.version_code', '<', $maxVersion)->count();
                        $item['compare_version'] = $count > 0 ? 1 : 0;
                    }
                }
                if ($item['ad_type'] == Campaign::AD_TYPE_APP_STORE &&
                    $item['link_status'] == Product::LINK_STATUS_DISABLE) {
                    $item['compare_version'] = 2;
                }
                //投放，暂停，停止投放时计算投放媒体和可投放媒体
                if (in_array($item['status'], [
                    Campaign::STATUS_DELIVERING,
                    Campaign::STATUS_SUSPENDED,
                    Campaign::STATUS_STOP_DELIVERING
                ])) {
                    $item['launched_media'] = isset($launchedMedia[$item['campaignid']]) ?
                        $launchedMedia[$item['campaignid']] : 0;
                    $campaign = Campaign::find($item['campaignid'])->toArray();
                    $prefix = DB::getTablePrefix();
                    $select = DB::table('affiliates as aff')
                        ->join('accounts AS ac', 'ac.account_id', '=', 'aff.account_id')
                        ->join('users AS u', function ($query) {
                            $query->on('u.default_account_id', '=', 'ac.account_id')
                                ->on('u.user_id', '=', 'ac.manager_userid');
                        })
                        ->join('affiliates_extend AS ae', 'aff.affiliateid', '=', 'ae.affiliateid')
                        ->select('aff.affiliateid')
                        ->where(DB::raw("({$prefix}aff.app_platform & {$campaign['platform']})"), '>', 0)
                        ->where(DB::raw("FIND_IN_SET({$campaign['ad_type']},{$prefix}aff.ad_type)"), '>', 0)
                        ->where('aff.affiliates_status', Affiliate::STATUS_ENABLE)
                        ->where('aff.agencyid', Auth::user()->agencyid)
                        ->where('ae.ad_type', $campaign['ad_type'])
                        ->whereIn('ae.revenue_type', Campaign::getCRevenueTypeToARevenueType($campaign['revenue_type']))
                        ->groupBy('aff.affiliateid');
                    if ($campaign['ad_type'] == Campaign::AD_TYPE_APP_MARKET) {
                        $select = $select->whereIn('aff.mode', [
                            Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE,
                            Affiliate::MODE_PROGRAM_DELIVERY_STORAGE,
                            Affiliate::MODE_ARTIFICIAL_DELIVERY,
                        ]);
                    } elseif ($campaign['ad_type'] == Campaign::AD_TYPE_OTHER) {
                        $select = $select->where('aff.mode', Affiliate::MODE_ARTIFICIAL_DELIVERY);
                    } elseif (($campaign['ad_type'] == Campaign::AD_TYPE_FULL_SCREEN
                            || $campaign['ad_type'] == Campaign::AD_TYPE_HALF_SCREEN)
                        && Campaign::REVENUE_TYPE_CPM == $campaign['revenue_type']
                    ) {
                        $select = $select->whereIn('aff.mode', [
                            Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE,
                            Affiliate::MODE_ADX,
                        ]);
                    } else {
                        $select = $select->whereIn('aff.mode', [
                            Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE,
                            Affiliate::MODE_ARTIFICIAL_DELIVERY,
                            Affiliate::MODE_ADX,
                        ]);
                    }
                    $allMedia = $select->get();
                    $item['all_media'] = count($allMedia);
                }
            }
        }
        return $this->success(null, [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $data['total'],
        ], $data['data']);
    }

    /**
     * 审核推广计划,不通过审核，继续投放，暂停
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | status |  | integer | 修改后的状态 |11：未通过审核,15：停止投放 | 是 |
     * |  |  |  |  | 11：未通过审核,15：停止投放 |  |
     * | channel |  | string | 渠道 |  | 是 |
     * | rate |  | decimal | 广告系数 |  | 是 |
     * | approve_comment |  | string | 描述 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request)
    {
        $status = ArrayHelper::getRequiredIn(Campaign::getStatusLabels());
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'status' => "required|in:{$status}",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        //获取所有参数
        $status = $request->input('status');
        $campaignId = $request->input('campaignid');
        $campaign = Campaign::find($campaignId);
        $productType = $campaign->product->type;
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
                        'rate' => 'required|numeric',
                    ], [], Campaign::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
                $param['rate'] = $request->input('rate');

                $business_type = ArrayHelper::getRequiredIn(Campaign::getBusinessType());
                if (($ret = $this->validate($request, [
                        'business_type' => "required|in:{$business_type}",
                    ], [], Campaign::attributeLabels())) !== true
                ) {
                    return $this->errorCode(5000, $ret);
                }
                $param['business_type'] = $request->input('business_type');

                if ($productType == Product::TYPE_APP_DOWNLOAD) {
                    if (($ret = $this->validate($request, [
                            'channel' => 'required',
                        ], [], AttachFile::attributeLabels())) !== true
                    ) {
                        return $this->errorCode(5000, $ret);
                    }
                }

                CampaignService::approveLog($campaignId);
            }
            // @codeCoverageIgnoreEnd
        }
        DB::beginTransaction();//事务开始
        $ret = CampaignService::modifyStatus($campaignId, $status, isset($param) ? $param : []);
        // @codeCoverageIgnoreStart
        if ($ret !== true) {
            DB::rollback();
            return $this->errorCode($ret);
        }
        //其他类型CPA,CPT无安装包
        if ($productType == Product::TYPE_APP_DOWNLOAD && $campaign->ad_type != Campaign::AD_TYPE_OTHER) {
            $attachId = CampaignService::getAttachFileId($campaignId);
            if ($attachId > 0) {
                AttachFile::processPackage(
                    $campaignId,
                    $attachId,
                    $status == Campaign::STATUS_REJECTED ?
                        AttachFile::FLAG_REJECTED : AttachFile::FLAG_PENDING_APPROVAL,
                    ['channel' => $request->input('channel')]
                );
            }
        }
        if ($campaign->ad_type == Campaign::AD_TYPE_VIDEO) {
            CampaignVideo::where('campaignid', $campaignId)
                ->where('status', CampaignVideo::STATUS_PENDING_APPROVAL)
                ->update([
                    'status' => $status == Campaign::STATUS_REJECTED ?
                        Campaign::STATUS_REJECTED : CampaignVideo::STATUS_USING,
                ]);
        }
        // @codeCoverageIgnoreEnd
        DB::commit();//事务结束
        return $this->success();
    }

    /**
     *获取历史出价
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue_no |  | strubg | 出价序号 |  | 是 |
     * | current_revenue |  | decimal | 当前出价 |  | 是 |
     * | time |  | datetime | 出价时间 |  | 是 |
     */
    public function revenueHistory(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required|integer',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $campaignId = $request->input('campaignid');

        //获取所有记录
        $result = CampaignRevenueHistory::where('campaignid', $campaignId)
            ->orderBy('time', 'DESC')
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        $total = $result->total();
        $list = [];
        foreach ($result as $item) {
            $list[] = [
                'revenue_no' => '',
                'current_revenue' => $item->current_revenue,
                'time' => date('Y-m-d H:i:s', strtotime($item->time) + 3600 * CampaignRevenueHistory::TIME_ZONE_HOUR),
            ];
        }

        return $this->success(null, [
            'pageSize' => $pageSize,
            'count' => $total,
            'pageNo' => $pageNo,
        ], $list);
    }

    /**
     * 广告审核信息
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | product_id |  | integer | 产品id |  | 是 |
     * | product_name |  | string | 产品名称 |  | 是 |
     * | product_type |  | integer | 产品类型 |  | 是 |
     * | product_icon |  | string | 产品图标 |  | 是 |
     * | campaignid |  | integer | 推广计划id |  | 是 |
     * | app_name |  | string | 广告名称 |  | 是 |
     * | app_show_icon |  | string | 图标 |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * | real_name |  | string | 包名称 |  | 是 |
     * | download_url |  | string | 下载地址 |  | 是 |
     * | platform |  | integer | 目标平台 |  | 是 |
     * | url |  | string | 视频地址 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * | revenue |  | integer | 广告主出价 |  | 是 |
     * | day_limit |  | integer | 日预算 |  | 是 |
     * | keywords |  | array | 关键字 |  | 是 |
     * |  | id | integer | 关键字id |  | 是 |
     * |  | keyword | string | 关键字 |  | 是 |
     * |  | price_up | decimal | 加价额 |  | 是 |
     * | description |  | string | 应用介绍 |  | 是 |
     * | profile |  | string | 一句话简介/广告文案 |  | 是 |
     * | update_des |  | string | 更新说明 |  | 是 |
     * | images |  | array | 图片 |  | 是 |
     * |  | ad_spec | string | 图片ID |  | 是 |
     * |  | url | string | 图片url |  | 是 |
     * |  | height | integer | 图片高度 |  | 是 |
     * |  | width | integer | 图片高度 |  | 是 |
     * | clientname |  | string | 广告主 |  | 是 |
     * | link_name |  | string | 链接名称 |  | 是 |
     * | link_url |  | string | 链接地址 |  | 是 |
     * | title |  | string | 标题 |  | 是 |
     * | rank |  | string | 星级 |  | 是 |
     * | business_type | | integer | 业务类型 | | 是 |
     */
    public function info(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required|integer',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = $request->input('campaignid');//获取参数
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $data = DB::table('campaigns as c')
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
            ->leftJoin('attach_files as att', 'att.campaignid', '=', 'c.campaignid')
            ->leftJoin('category as t', 't.category_id', '=', 'a.category')
            ->where('c.campaignid', $campaignId)
            ->orderBy('att.created_at', 'DESC')
            ->select(
                'a.app_show_name',
                'a.profile',
                'a.title',
                'a.star as rank',
                'c.status',
                'c.campaignid',
                'c.revenue',
                'c.revenue_type',
                'c.day_limit',
                'c.total_limit',
                'c.platform',
                'c.business_type',
                'a.app_name',
                'a.description',
                'a.update_des',
                'p.icon',
                'a.app_show_icon',
                'a.app_show_icon AS app_icon',
                'a.app_show_name',
                'a.images',
                'c.ad_type',
                'p.name as product_name',
                'p.show_name as product_show_name',
                'p.type as product_type',
                'p.link_name',
                'p.link_url',
                'p.icon as product_icon',
                's.clientname',
                'c.product_id',
                'att.file AS download_url',
                'att.real_name'
            )->first();
        if (!$data) {
            return $this->errorCode(5001);// @codeCoverageIgnore
        }
        if ($data['product_type'] == Product::TYPE_LINK) {
            $data['icon'] = $data['app_show_icon'];
        }
        $data['download_url'] = UrlHelper::fileFullUrl($data['download_url'], $data['real_name']);
        $data['revenue'] = Formatter::asDecimal($data['revenue']);
        $data['day_limit'] = Formatter::asDecimal($data['day_limit'], 0);
        $data['total_limit'] = intval($data['total_limit']);
        if ($data['ad_type'] == Campaign::AD_TYPE_VIDEO) {
            $data['url'] = CampaignVideo::where('campaignid', $campaignId)
                ->where('status', '<>', CampaignVideo::STATUS_ABANDON)
                ->orderBy('created_time', 'DESC')
                ->pluck('url');
        }
        if ($data['ad_type'] == Campaign::AD_TYPE_APP_MARKET || $data['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
            $images = $data['images'] ? unserialize($data['images']) : '';
            if (!empty($images)) {
                if (ArrayHelper::arrayLevel($images) == 1) {
                    $sizeArr = explode("*", Config::get('ad_spec.' . $data['ad_type'] . '.1'));
                    if (count($sizeArr) == 2) {
                        $data['images'] = [
                            1 => [
                                'ad_spec' => 1,
                                'url' => $images,
                                'height' => $sizeArr[1],
                                'width' => $sizeArr[0],
                            ],
                        ];
                    }
                } else {
                    // @codeCoverageIgnoreStart
                    $listImg = [];
                    foreach ($images as $k => $v) {
                        $sizeArr = explode("*", Config::get('ad_spec.' . $data['ad_type'] . '.' . $k));
                        if (count($sizeArr) != 2) {
                            continue;
                        }
                        $listImg[$k] = [
                            'ad_spec' => $k,
                            'url' => $v,
                            'height' => $sizeArr[1],
                            'width' => $sizeArr[0],
                        ];
                    }
                    $data['images'] = $listImg;
                    // @codeCoverageIgnoreEnd
                }
            }

            $keywords = AdZoneKeyword::where('campaignid', $campaignId)
                ->select('id', 'keyword', DB::raw('convert(price_up, DECIMAL(10, 1)) as price_up'))
                ->get();
            if (!empty($keywords)) {
                $data['keywords'] = $keywords->toArray();
            }
        } else {
            //文字链，其他不包含图片，排除
            if (!in_array($data['ad_type'], [
                Campaign::AD_TYPE_BANNER_TEXT_LINK,
                Campaign::AD_TYPE_OTHER,
                Campaign::AD_TYPE_VIDEO,
            ])
            ) {
                $images = CampaignImage::where('campaignid', $campaignId)
                    ->select('ad_spec', 'url', 'width', 'height')
                    ->get();
                if ($images) {
                    $newBannerImages = [];
                    $imagesTypeList = Config::get('ad_spec.' . $data['ad_type']);
                    foreach ($imagesTypeList as $ke => $va) {
                        foreach ($images as $k => $v) {
                            $sizeArr = explode("*", $va);
                            if ($ke != $v->ad_spec) {
                                if (!isset($newBannerImages[$ke]['url'])) {
                                    $newBannerImages[$ke] = ['ad_spec' => $ke, 'url' => '',
                                        'width' => $sizeArr[0], 'height' => $sizeArr[1]];
                                } else {
                                    //@codeCoverageIgnoreStart
                                    if ('' == $newBannerImages[$ke]['url']) {
                                        $newBannerImages[$ke] = ['ad_spec' => $ke, 'url' => '',
                                            'width' => $sizeArr[0], 'height' => $sizeArr[1]];
                                    }
                                    //@codeCoverageIgnoreEnd
                                }
                            } else {
                                $newBannerImages[$ke] = ['ad_spec' => $ke, 'url' => $v->url,
                                    'width' => $sizeArr[0], 'height' => $sizeArr[1]];
                            }
                        }
                    }
                    $data['images'] = array_values($newBannerImages);
                }
            }
        }
        return $this->success(null, null, $data);
    }

    /**
     * 获取所有广告主出价
     * @return \Illuminate\Http\Response
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue |  | integer | 广告主出价 |  | 是 |
     */
    public function revenue()
    {
        //获取所有出价
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $data = DB::table('campaigns')->orderBy('revenue', 'ASC')->distinct()->get(['revenue']);
        return $this->success(null, null, $data);
    }

    /**
     * 获取所有广告日限额
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | day_limit |  | integer | 日预算 |  | 是 |
     */
    public function dayLimit()
    {
        //获取日限额
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $data = DB::table('campaigns')->orderBy('day_limit', 'ASC')->distinct()->get(['day_limit']);
        return $this->success(null, null, $data);
    }

    /**
     * 广告列表修改
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | field |  | string | 字段名 | rate,condition,day_limit_program | 是 |
     * | value |  | string | 字段值 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required|integer',
                'field' => 'required|in:rate,condition,day_limit_program,business_type',
                'value' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $params = $request->all();
        if ($params['field'] == 'rate') {
            if (($ret = $this->validate($request, [
                    'value' => 'min:0|max:100',
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }

            $campaign = Campaign::find($params['campaignid']);
            $oldValue = $campaign->rate;

            //修改广告系数
            $ret = Campaign::where('campaignid', $params['campaignid'])->update([
                'rate' => $params['value'],
                'updated_uid' => Auth::user()->user_id,
            ]);
            if (!$ret) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }

            //刷新媒体价及广告主出价
            $banners = Banner::where('campaignid', $params['campaignid'])
                ->select('bannerid')
                ->get();
            if ($banners) {
                foreach ($banners as $item) {
                    CampaignService::updateBannerBilling($item->bannerid);
                }
            }

            $oldValue = Formatter::asDecimal($oldValue, 0);
            $value = Formatter::asDecimal($params['value'], 0);
            $message = CampaignService::formatWaring(6024, [$oldValue, $value]);
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'target_id' => $params['campaignid'],
                'type' => OperationLog::TYPE_MANUAL,
                'operator' => Auth::user()->contact_name,
                'message' => $message,
            ]);
        } elseif ($params['field'] == 'condition') {
            $data = json_decode($params['value'], true);
            if (is_null($data)) {
                return $this->errorCode(5000);
            }
            $model = Campaign::where('campaignid', $params['campaignid'])->first();
            if (!$model) {
                return $this->errorCode(5000);// @codeCoverageIgnore
            }
            $model->condition = $params['value'];
            $model->updated_uid = Auth::user()->user_id;
            if (!$model->save()) {
                return $this->errorCode(5001);
            }
            
            $list = [];
            foreach ($data as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if (!in_array($k, ['imei_imp'])) {
                        $data = [];
                        if (!is_array($v)) {
                            $v = explode(",", $v);
                        }
                        foreach ($v as $key => $val) {
                            $data[strtolower($key)] = strtolower($val);
                        }
                        $list[$k] = $data;
                    } else {
                        $list[$k] = $v;
                    }
                }
            }
            /*
             $list['os'] =
             ($model->platform & Campaign::PLATFORM_ANDROID == Campaign::PLATFORM_ANDROID) ? 'android' : 'iOS';
             */
            $redisData = json_encode($list);
            $redis = Redis::connection('redis_delivery');
            $redis->set('dir_filter_campaign_' . $params['campaignid'], $redisData);
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'target_id' => $params['campaignid'],
                'type' => OperationLog::TYPE_MANUAL,
                'operator' =>  Auth::user()->contact_name,
                'message' => CampaignService::formatWaring(6025),
            ]);
            return $this->success();
        } elseif ($params['field'] == 'business_type') {
            $ret = Campaign::where('campaignid', $params['campaignid'])->update([
                'business_type' => $params['value'],
                'updated_uid' => Auth::user()->user_id,
            ]);
            if (!$ret) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
            OperationLog::store([
                'category' => OperationLog::CATEGORY_CAMPAIGN,
                'target_id' => $params['campaignid'],
                'type' => OperationLog::TYPE_MANUAL,
                'operator' => Auth::user()->contact_name,
                'message' => CampaignService::formatWaring(6052),
            ]);
        } elseif ($params['field'] == 'day_limit_program') {
            if (($ret = $this->validate($request, [
                    'value' => 'min:0',
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            $campaign = Campaign::find($params['campaignid']);
            $oldValue = $campaign->day_limit_program;
            $campaign->day_limit_program = $params['value'];
            $campaign->updated_uid = Auth::user()->user_id;
            $campaign->save();

            //提高程序化日预算启动广告
            if ($params['value'] > $oldValue) {
                if ($campaign->status == Campaign::STATUS_SUSPENDED
                    && $campaign->pause_status == Campaign::PAUSE_STATUS_EXCEED_DAY_LIMIT_PROGRAM
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
                    //写一条审计日志;
                    $message = CampaignService::formatWaring(
                        6050,
                        [
                            Auth::user()->contact_name,
                            sprintf("%.2f", $oldValue),
                            sprintf("%.2f", $params['value'])
                        ]
                    );
                    OperationLog::store([
                        'category' => OperationLog::CATEGORY_CAMPAIGN,
                        'target_id' => $campaign->campaignid,
                        'operator' => Config::get('error')[6000],
                        'type' => OperationLog::TYPE_SYSTEM,
                        'message' => $message,
                    ]);
                    LogHelper::info("day_limit modify,
                        campaign {$campaign->campaignid} status from {$oldValue} to {$params['value']} ");

                    //启动因日预算暂停的媒体广告
                    BannerService::recoverBanner($campaign->campaignid);
                }
            }
        }
        return $this->success();
    }

    /**
     * CPA/CPT推广类型新增
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * | clientid |  | integer | 广告主ID |  | 是 |
     * | products_id |  | integer | 产品ID |  | 是 |
     * | platform |  | integer | 平台 |  | 是 |
     * | products_name |  | string | 产品名称 |  | 是 |
     * | products_icon |  | string | 产品图标 |  | 是 |
     * | appinfos_app_name |  | string | 应用名称 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $revenueType = implode(',', [Campaign::REVENUE_TYPE_CPA,
            Campaign::REVENUE_TYPE_CPT, Campaign::REVENUE_TYPE_CPS]);
        $platform = ArrayHelper::getRequiredIn(Campaign::getPlatformLabels(null));
        if (($ret = $this->validate($request, [
                'revenue_type' => "required|in:{$revenueType}",
                'clientid' => 'required',
                'products_id' => 'required',
                'platform' => "required|in:{$platform}",
                'products_name' => 'required',
                'products_icon' => 'required',
                'appinfos_app_name' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $params = $request->all();
        //CPA/CPT广告类型为其他
        $params['ad_type'] = Campaign::AD_TYPE_OTHER;

        //获取广告主代理id
        $client = Client::find($params['clientid']);
        $params['agencyid'] = $client->agencyid;
        $params['action'] = Campaign::ACTION_APPROVAL;

        $ret = CampaignService::campaignStore($params);
        if ($ret !== true) {
            return $this->errorCode($ret);// @codeCoverageIgnore
        }
        return $this->success();
    }

    /**
     *  CPA/CPT 广告主列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientid |  | integer | 广告主ID |  | 是 |
     * | clientname |  | string | 广告主名称 |  | 是 |
     */
    public function clientList(Request $request)
    {
        $revenueType = ArrayHelper::getRequiredIn(Campaign::getRevenueTypeLabels());
        if (($ret = $this->validate($request, [
                'revenue_type' => "required|in:{$revenueType}",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $revenueType = $request->input('revenue_type');
        $agencyId = Auth::user()->agencyid;
        $result = Client::whereRaw("(revenue_type & {$revenueType}) > 0")
            ->where('agencyid', $agencyId)
            ->select('clientid', 'clientname')
            ->get();

        if ($result) {
            $result = $result->toArray();
        }

        return $this->success($result);
    }

    /**
     * 广告主产品列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientid |  | integer | 广告主ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clientid |  | integer | 广告主ID |  | 是 |
     * | name |  | string | 广告主名称 |  | 是 |
     * | platform |  | integer | 目标平台 |  | 是 |
     * | icon |  | string | 产品图标 |  | 是 |
     */
    public function productList(Request $request)
    {
        if (($ret = $this->validate($request, [
                'clientid' => "required",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $clientId = $request->input('clientid');

        $result = Product::where('clientid', $clientId)
            ->select('id', 'name', 'platform', 'icon')
            ->where('type', Product::TYPE_APP_DOWNLOAD)
            ->get();

        if ($result) {
            $result = $result->toArray();
        }
        return $this->success($result);
    }

    /**
     * 等价管理列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | platform |  | integer | 目标平台 |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | clientname |  | string | 广告主 |  | 是 |
     * | name |  | string | 推广产品 |  | 是 |
     * | icon |  | string | 推广产品图标 |  | 是 |
     * | app_name |  | string | 广告名称 |  | 是 |
     * | revenue |  | decimal | 出价 |  | 是 |
     * | revenue_type |  | integer | 出价类型 |  | 是 |
     * | day_limit |  | integer | 日预算 |  | 是 |
     * | total_limit |  | integer | 总预算 |  | 是 |
     * | status |  | integer | 投放状态 |  | 是 |
     * | relation |  | integer | 等价关系 | 1.本广告自身，2.等价广告，3.- | 是 |
     */
    public function equivalenceList(Request $request)
    {
        $platform = ArrayHelper::getRequiredIn(Campaign::getPlatformLabels());
        $adType = ArrayHelper::getRequiredIn(Campaign::getAdTypeLabels());
        $revenueType = ArrayHelper::getRequiredIn(Campaign::getRevenueTypeLabels());
        if (($ret = $this->validate($request, [
                'campaignid' => "required",
                'platform' => "required|in:{$platform}",
                'ad_type' => "required|in:{$adType}",
                'revenue_type' => "required|in:{$revenueType}",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $params = $request->all();

        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认25
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1

        $campaign = Campaign::find($params['campaignid']);
        $prefix = \DB::getTablePrefix();
        $select = \DB::table('campaigns AS c')
            ->leftJoin('appinfos AS a', function ($join) {
                $join->on('c.campaignname', '=', 'a.app_id')
                    ->on('a.platform', '=', 'c.platform');
            })->leftJoin('products AS p', 'c.product_id', '=', 'p.id')
            ->leftJoin('clients AS uc', 'c.clientid', '=', 'uc.clientid')
            ->select(
                'c.campaignid',
                'uc.clientname',
                'p.name',
                'p.icon',
                'a.app_name',
                'c.revenue',
                'c.revenue_type',
                'c.day_limit',
                'c.total_limit',
                'c.status',
                'c.pause_status',
                'c.equivalence',
                \DB::raw("IF({$prefix}c.campaignid = {$params['campaignid']} , 1,
                IF({$prefix}c.equivalence = '', 3,
                IF({$prefix}c.equivalence='{$campaign->equivalence}', 2, 3))) AS relation")
            )
            ->whereIn('c.status', [
                Campaign::STATUS_DELIVERING,
                Campaign::STATUS_SUSPENDED,
            ])->where('c.platform', $params['platform'])
            ->where('c.ad_type', $params['ad_type'])
            ->where('c.revenue_type', $params['revenue_type'])
            ->where('uc.agencyid', Auth::user()->agencyid);
        //搜索
        if (!empty($params['search'])) {
            $select->where(function ($query) use ($params, $campaign) {
                $query->where('p.name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('a.app_name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('uc.clientname', 'like', '%' . $params['search'] . '%');
                //已关联的等价广告包含等价广告
                if (!empty($campaign->equivalence)) {
                    $query->orWhere('c.equivalence', $campaign->equivalence);
                }
            });
        }

        //总记录数
        $total = $select->count();

        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        //获取数据
        $rows = $select->orderBy('relation')->orderBy('c.status')->get();

        //处理小数位
        foreach ($rows as &$item) {
            $item->revenue = Formatter::asDecimal(
                $item->revenue,
                Config::get('biddingos.jsDefaultInit.' . $params['revenue_type'])['decimal']
            );
            $item->day_limit = Formatter::asDecimal($item->day_limit);
            $item->total_limit = Formatter::asDecimal($item->total_limit);
            if ($item->campaignid == $params['campaignid']) {
                continue;
            } elseif ($item->equivalence == $campaign->equivalence && !empty($item->equivalence)) {
                $item->relation = 2;
            } else {
                $item->relation = 3;
            }
        }

        return $this->success(null, [
            'pageSize' => $pageSize,
            'count' => $total,
            'pageNo' => $pageNo,
        ], $rows);
    }

    /**
     * 建立和删除等价关系
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | campaignid_relation |  | integer | 要建立管理的广告 |  | 是 |
     * | action |  | integer | 操作 | 1.建立等价关系，2.删除等价关系 | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function equivalence(Request $request)
    {
        $action = implode(',', [
            Campaign::ACTION_EQUIVALENCE_RELATION,
            Campaign::ACTION_EQUIVALENCE_DELETE,
        ]);
        if (($ret = $this->validate($request, [
                'campaignid' => "required",
                'action' => "required|in:{$action}"
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $action = $request->input('action');
        $campaignId = $request->input('campaignid');
        $campaignIdRelation = $request->input('campaignid_relation');
        if ($action == Campaign::ACTION_EQUIVALENCE_RELATION) {
            //本身广告
            $campaign = Campaign::find($campaignId);
            if (!$campaign) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
            //要建立等价广告
            $campaignRelation = Campaign::find($campaignIdRelation);
            if (!$campaign) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }

            //第一次建立等价关系
            if (empty($campaign->equivalence) && empty($campaignRelation->equivalence)) {
                //根据时间生成32位MD5码
                $key = md5(date('Y-m-d h:i:s') . rand(1, 99999));
                //讲当前广告与要关联广告设置相同的key
                Campaign::whereIn('campaignid', [$campaignId, $campaignIdRelation])->update([
                    'equivalence' => $key
                ]);
            } elseif (empty($campaign->equivalence) && $campaignRelation->equivalence) {
                $key = $campaignRelation->equivalence;
                Campaign::where('campaignid', $campaignId)->update([
                    'equivalence' => $key
                ]);
            } elseif ($campaign->equivalence && empty($campaignRelation->equivalence)) {
                $key = $campaign->equivalence;
                Campaign::where('campaignid', $campaignIdRelation)->update([
                    'equivalence' => $key
                ]);
            } elseif ($campaign->equivalence && $campaignRelation->equivalence) {
                $key = $campaign->equivalence;
                Campaign::where('equivalence', $campaignRelation->equivalence)->update([
                    'equivalence' => $key
                ]);
            }

            CampaignService::attachEquivalencePackageName($key);

        } else {
            $campaign = Campaign::find($campaignId);
            if ($campaign->equivalence) {
                //将等价广告关系置空
                Campaign::where('campaignid', $campaignIdRelation)->update([
                    'equivalence' => ''
                ]);
                //所有的关联关系都删除了，将本身广告置空
                $count = Campaign::where('campaignid', '<>', $campaignId)
                    ->where('equivalence', $campaign->equivalence)
                    ->count();
                if ($count == 0) {
                    Campaign::where('campaignid', $campaignId)->update([
                        'equivalence' => ''
                    ]);

                    //删除等价关联包
                    EquivalenceAssoc::where('equivalence', $campaign->equivalence)->delete();
                } else {
                    CampaignService::attachEquivalencePackageName($campaign->equivalence);
                }
            }
        }
        return $this->success();
    }

    /**
     * 媒体增加消耗
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | day_consume |  | decimal | 日消耗 |  | 是 |
     * | total_consume |  | decimal | 总消耗 |  | 是 |
     */
    public function consume(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => "required",
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $campaignId = $request->input('campaignid');
        $campaign = Campaign::find($campaignId);

        //查询campaign的总消费，用于总预算检测
        $result = CampaignService::getTotalConsume($campaignId);
        $consumeToday = CampaignService::getDailyConsume($campaignId, $campaign->revenue_type);
        
        return $this->success([
            'day_consume' => Formatter::asDecimal($consumeToday),
            'total_consume' => Formatter::asDecimal($result->total_revenue),
        ]);
    }

    /**
     * 获取广告
     * @param $status
     * @param $pageNo
     * @param $pageSize
     * @param array $params
     * @return array
     */
    private function getCampaignByManager($status, $pageNo, $pageSize, $filter, $params = [])
    {
        $prefix = DB::getTablePrefix();
        $when = " CASE ";
        foreach (Campaign::getStatusSort() as $k => $v) {
            $when .= ' WHEN ' . Campaign::getTableFullName() . '.status = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_status ";
        $select = DB::table("campaigns")
            ->leftJoin("appinfos", function ($join) {
                $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                    ->on('campaigns.platform', '=', 'appinfos.platform');
            })
            ->leftJoin('products', 'campaigns.product_id', '=', 'products.id')
            ->leftJoin('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->leftJoin('accounts', 'accounts.account_id', '=', 'clients.account_id')
            ->leftJoin('users', 'accounts.manager_userid', '=', 'users.user_id')
            ->leftJoin('brokers', 'brokers.brokerid', '=', 'clients.broker_id')
            ->leftJoin('accounts AS a', 'a.account_id', '=', 'brokers.account_id')
            ->leftJoin('users AS u', 'u.user_id', '=', 'a.manager_userid')
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
                'campaigns.day_limit_program',
                'campaigns.operation_time',
                'campaigns.approve_comment',
                'campaigns.rate',
                'campaigns.equivalence',
                'campaigns.condition',
                'campaigns.business_type',
                'appinfos.app_name AS appinfos_app_name',
                'appinfos.created_at',
                'products.icon',
                'clients.brief_name',
                'clients.clientname',
                'brokers.brief_name AS broker_brief_name',
                'clients.contact',
                'brokers.contact AS broker_contact',
                'clients.email',
                'brokers.email AS broker_email',
                'users.contact_phone',
                'u.contact_phone AS broker_contact_phone',
                'users.qq',
                'u.qq AS broker_qq',
                'clients.broker_id',
                'products.name AS products_name',
                'products.show_name AS products_show_name',
                'products.type AS products_type',
                'products.link_status',
                DB::raw("(select username from {$prefix}users as u
                        where u.user_id = {$prefix}campaigns.updated_uid) as approve_user"),
                DB::raw("(select contact_name from {$prefix}users AS u
                        where u.user_id = {$prefix}clients.creator_uid) AS creator_name"),
                DB::raw("(select contact_name from {$prefix}users AS u
                        where u.user_id = {$prefix}clients.operation_uid) AS operation_name"),
                DB::raw($when)
            );
        //过滤平台
        $select->where('clients.agencyid', Auth::user()->agencyid);

        //获取当前登录用户是媒体商,联盟账号过滤掉自营媒体
        $select->where('clients.affiliateid', 0);

        if (!is_null($status)) {
            $select->whereIn('campaigns.status', $status);
        }

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'platform') {
                        //如果是平台，则运算
                        if ($v == Campaign::PLATFORM_IOS) {
                            $select->where(function ($select) use ($v) {
                                $select->where('campaigns.platform', $v);
                                $select->orWhere(function ($select) {
                                    $select->where('campaigns.platform', Campaign::PLATFORM_IOS_COPYRIGHT)
                                        ->where('campaigns.ad_type', Campaign::AD_TYPE_APP_STORE);
                                });
                            });
                        } else {
                            $select->where("campaigns.platform", $v);
                        }
                    } elseif ($k == 'revenue_type') {
                        $select->where('campaigns.revenue_type', '=', $v);
                    } elseif ($k == 'ad_type') {
                        $adType = Campaign::getAdTypeToAdType($v);
                        //插屏（半屏，全屏）
                        $select->whereIn('campaigns.ad_type', $adType);
                    } elseif ($k == 'business_type') {
                        $select->where('campaigns.business_type', '=', $v);
                    } elseif ($k == 'status') {
                        $select = CampaignService::getFilterCondition($select, ['status' => $v]);
                    } elseif ($k == 'revenue') {
                        $select = CampaignService::getFilterCondition($select, ['revenue' => $v]);
                    } elseif ($k == 'day_limit') {
                        $select = CampaignService::getFilterCondition($select, ['day_limit' => $v]);
                    } elseif ($k == 'creator_uid') {
                        $select = $select->where('clients.creator_uid', $v);
                    } elseif ($k == 'operation_uid') {
                        $select = $select->where('clients.operation_uid', $v);
                    } elseif ($k == 'business_type') {
                        $select = $select->where('campaigns.business_type', $v);
                    } elseif ($k == 'created_at') {
                        if (!StringHelper::isEmpty($v[0])) {
                            $select = $select->where('appinfos.created_at', '>', $v[0]);
                        }
                        if (!StringHelper::isEmpty($v[1])) {
                            $select = $select->where('appinfos.created_at', '<', $v[1]);
                        }
                    } elseif ($k == 'operation_time') {
                        if (!StringHelper::isEmpty($v[0])) {
                            $select = $select->where('campaigns.operation_time', '>', $v[0]);
                        }
                        if (!StringHelper::isEmpty($v[1])) {
                            $select = $select->where('campaigns.operation_time', '<', $v[1]);
                        }
                    } elseif ($k == 'status_stop') {
                        //1不显示，2显示
                        if ($v == 1) {
                            $select = $select->whereIn('campaigns.status', [
                                Campaign::STATUS_DELIVERING,
                                Campaign::STATUS_PENDING_APPROVAL,
                                Campaign::STATUS_REJECTED,
                                Campaign::STATUS_SUSPENDED,
                            ]);
                        }
                    } elseif ($k == 'products_type') {
                        $select = $select->where('products.type', $v);
                    }
                }
            }
        }

        // ===================搜索==========================
        if (!empty($params['search'])) {
            //增加广告主搜索的功能
            $select->where(function ($query) use ($params) {
                $query->where('appinfos.app_name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('clients.brief_name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('brokers.brief_name', 'like', '%' . $params['search'] . '%');
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
            switch ($sortAttr) {
                case 'clientname':
                    $select->orderBy('clients.clientname', $sortType);
                    break;
                case 'app_name':
                    $select->orderBy('appinfos.app_name', $sortType);
                    break;
                case 'platform':
                    $select->orderBy('campaigns.platform', $sortType);
                    break;
                case 'revenue':
                    $select->orderBy('campaigns.revenue', $sortType);
                    break;
                case 'day_limit':
                    $select->orderBy('campaigns.day_limit', $sortType);
                    break;
                case 'ad_type':
                    $select->orderBy('campaigns.ad_type', $sortType);
                    break;
                case 'rate':
                    $select->orderBy('campaigns.rate', $sortType);
                    break;
                case 'operation_time':
                    $select->orderBy('campaigns.operation_time', $sortType);
                    break;
                default:
                    $select->orderBy('status', 'desc');
                    break;
            }
        } else {
            //默认排序
            $select->orderBy('sort_status', 'desc')->orderBy('operation_time', 'desc');
        }
        //$select->orderBy('operation_time', 'desc');
        //获取数据
        $rows = $select->get();
        $data = !empty($rows) ? json_decode(json_encode($rows), true) : [];
        return [
            'data' => $data,
            'total' => $total
        ];
    }
    
    /**
     * 获取广告文本列表
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function wordList(Request $request)
    {
        $campaignId = $request->input('campaignid', false);
        $list = $this->getWordList($campaignId);
        return $this->success(null, null, $list);
    }
    
    /**
     * 修改广告文本
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function wordNew(Request $request)
    {
        $campaignId = $request->input('campaignid', false);
        $cid = $request->input('cid', false);
        $content = $request->input('content', '');
        $limit = $request->input('limit', '');
        $attr = [
            'campaignid' => $campaignId,
            'content' => $content,
            'vec' => $this->getVec($content),
            'limit' => $limit
        ];
        DB::table("campaigns_word_list")->insert($attr);
        //写redis
        $this->setVecRedis($campaignId);
        $list = $this->getWordList($campaignId);
        return $this->success(null, null, $list);
    }
    
    /**
     * 修改广告文本
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function wordModify(Request $request)
    {
        $campaignId = $request->input('campaignid', false);
        $cid = $request->input('cid', false);
        $field = $request->input('field', '');
        $value = $request->input('value', '');
        switch ($field) {
            case 'content':
                $vec = $this->getVec($value);
                DB::table("campaigns_word_list")
                ->where('campaignid', $campaignId)
                ->where('cid', $cid)
                ->update([
                    'content' => $value,
                    'vec' => $vec
                ]);
                break;
            case 'limit':
                DB::table("campaigns_word_list")
                ->where('campaignid', $campaignId)
                ->where('cid', $cid)
                ->update([
                    'limit' => $value,
                ]);
                break;
        }
        //写redis
        $this->setVecRedis($campaignId);
        $list = $this->getWordList($campaignId);
        return $this->success(null, null, $list);
    }
    
    /**
     * 删除广告文本
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function wordDelete(Request $request)
    {
        $campaignId = $request->input('campaignid', false);
        $cid = $request->input('cid', false);
        DB::table("campaigns_word_list")
            ->where('campaignid', $campaignId)
            ->where('cid', $cid)
            ->delete();
        $list = $this->getWordList($campaignId);
        return $this->success(null, null, $list);
    }
    
    private function getWordList($campaignId)
    {
        $list = [];
        $rows = DB::table("campaigns_word_list")
            ->where('campaignid', $campaignId)
            ->select('cid', 'content', 'limit')
            ->get();
        foreach ($rows as $row) {
            $list[] = [
                'cid' => $row->cid,
                'content' => $row->content,
                'limit' => $row->limit
            ];
        }
        return $list;
    }
    
    private function setVecRedis($campaignId)
    {
        $v = [];
        $rows = DB::table("campaigns_word_list")
            ->where('campaignid', $campaignId)
            ->select('campaignid', 'vec', 'limit')
            ->get();
        foreach ($rows as $row) {
            $v[] = [
                'vec' => json_decode($row->vec, true),
                'limit' => $row->limit / 100,
            ];
        }
        
        $key = 'adtext_'.$campaignId;
        $redis = Redis::connection('redis_pika_target');
        $redis->set($key, json_encode($v));
    }
    private function getVec($content)
    {
        $vec = [];
        if ($content && $content != '') {
            $url = env('WORD2VEC_URL');
            $post_data = ['text' => $content];
            $resp = HttpClientHelper::call($url, json_encode($post_data), ['Content-Type:application/json']);
            if ($resp) {
                $data = json_decode($resp, true);
                if (!empty($data['vec'])) {
                    $vec = $data['vec'];
                }
            }
        }
        return json_encode($vec);
    }

  /**
   * 获取广告管理列表消耗趋势
   *
   * | name | sub name | type | description | restraint | required |
   * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
   * | campaignid |  | integer | 广告ID |  | 是 |
   * @param Request $request
   *
   * @return \Illuminate\Http\Response
   * | name | sub name |  sub name |type | description | restraint | required |
   * | :--: | :------: | :--: | :--: | :--------: | :-------: | :-----: |
   * | data |  |  |array | 媒体数据 |  | 是 |  |
   * |  | child |  |  |array | 具体数据 |  | 是 |  |
   * |  |  | time | date | 时间 |  | 是 | |
   * |  |  | revenue | decimal | 消耗 |  | 是 | |
   * |  |  brief_name | | string | 简称 |  | 是 | |
   * |  |  created_user | | string | 销售顾问 |  | 是 | |
   * |  | summary | | decimal | 媒体总消耗 |  | 是 | |
   * | summary |  |  |array | 时间 |  | 是 | |
   * |  | time |  |date | 时间 |  | 是 | |
   * |  | revenue |  |decimal | 消耗 |  | 是 | |
   */
    public function trend(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = $request->input('campaignid', false);
        $start = $start = date('Y-m-d', strtotime("-30 days"));
        $end = date('Y-m-d', strtotime("-1 days"));
        //获取广告数据
        $prefix = DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $res = DB::table("data_hourly_daily as h")
            ->join('banners as b', 'b.bannerid', '=', 'h.ad_id')
            ->join('affiliates as aff', 'aff.affiliateid', '=', 'b.affiliateid')
            ->join('campaigns as c', 'h.campaign_id', '=', 'c.campaignid')
            ->join('clients as cli', 'cli.clientid', '=', 'c.clientid')
            ->where('h.campaign_id', $campaignId)
            ->whereBetween('h.date', [$start, $end])
            ->select(
                DB::raw('IFNULL(SUM(' .$prefix . 'h.total_revenue),0) as revenue'), //广告主消耗
                'h.date',
                'aff.affiliateid',
                'aff.brief_name',
                'aff.creator_uid'
            )
            ->groupBy('h.date', 'aff.affiliateid')
            ->orderBy('h.date')
            ->get();
        $list = [];
        //重组数据
        foreach ($res as $val) {
            if (isset($list['data'][$val['affiliateid']])) {
                $list['data'][$val['affiliateid']]['child'][] = [
                    'revenue'=> $val['revenue'],
                    'date'=> $val['date'],
                ];
                $list['data'][$val['affiliateid']]['summary'] += $val['revenue'];
            } else {
                $list['data'][$val['affiliateid']]['child'][] = [
                    'revenue'=> $val['revenue'],
                    'date'=> $val['date'],
                ];
                $list['data'][$val['affiliateid']]['summary'] = $val['revenue'];
                $list['data'][$val['affiliateid']]['brief_name'] = $val['brief_name'];
                $list['data'][$val['affiliateid']]['created_user'] = User::find($val['creator_uid'])->contact_name;
            }

            if (isset($list['summary'][$val['date']])) {
                $list['summary'][$val['date']]['revenue'] += $val['revenue'];
            } else {
                $list['summary'][$val['date']]['revenue'] = $val['revenue'];
                $list['summary'][$val['date']]['date'] = $val['date'];
            }
        }
        //按照消耗排序
        if (!empty($list['data'])) {
            foreach ($list['data'] as $key => $val) {
                $revenue[$key] = $val['summary'];
            }
            array_multisort($revenue, SORT_DESC, $list['data']);
            array_slice($list['data'], 0, 5);//截取前五
        }
        //去键值
        if (!empty($list['summary'])) {
            $list['summary'] = array_values($list['summary']);
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
}
