<?php
namespace App\Http\Controllers\Manager;

use App\Components\Adx\AdxFactory;
use App\Components\Config;
use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Components\Helper\UrlHelper;
use App\Components\Symbol\SymbolFactory;
use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateExtend;
use App\Models\AppInfo;
use App\Models\AttachFile;
use App\Models\Banner;
use App\Models\BannerBilling;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\OperationLog;
use App\Models\Product;
use App\Models\Tracker;
use App\Models\Zone;
use App\Services\CampaignService;
use App\Services\CategoryService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BannerController extends Controller
{
    /**
     * 设定媒体商获取
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | int | 推广计划ID |  | 是 |
     * | ad_type |  | int | 广告类型 | 是 |
     * | products_type |  | int | 产品类型 |  |  |
     * | mode |  | int | 媒体模式 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | af_day_limit |  | int | 媒体日限额 |  | 是 |
     * | af_manual_price |  | decimal | 媒体价 | 是 |
     * | affiliateid |  | int | 媒体ID |  |  |
     * | affiliates_name |  | string | 媒体名称 |  | 是 |
     * | affiliates_type |  | integer | 媒体类型 |  | 是 |
     * | app_id |  | string | 应用ID |  | 是 |
     * | app_id_icon |  | string | 应用图表 |  | 是 |
     * | app_rank |  | string | 等级 |  | 是 |
     * | attach_id |  | string | 附件包ID |  | 是 |
     * | bannerid |  | string | bannerid |  | 是 |
     * | brief_name |  | string | 媒体简称 |  | 是 |
     * | category |  | string | 分类 |  | 是 |
     * | category_name |  | string | 分类名称 |  | 是 |
     * | channel |  | string | 渠道 |  | 是 |
     * | compare_version |  | int | 是否最新包 |  | 是 |
     * | condition |  | string | 条件 |  | 是 |
     * | contact |  | string | 联系人 |  | 是 |
     * | contact_phone |  | string | 描述 |  | 是 |
     * | contact_qq |  | string | 联系QQ |  | 是 |
     * | default_media_price |  | decimal | 默认媒体价 |  | 是 |
     * | email |  | string | 邮件 |  | 是 |
     * | file |  | string | 文件路径 |  | 是 |
     * | flow_ratio |  | decimal | 分成比例 |  | 是 |
     * | link_name |  | string | 链接名称 |  | 是 |
     * | link_url |  | string | 链接地址 |  | 是 |
     * | ad_spec |  | string | ADX素材规格 |  | 是 |
     * | media_price |  | decimal | 媒体价 |  | 是 |
     * | revenue_price |  | decimal | 广告主出价 |  | 是 |
     * | bidding_price |  | decimal | 竞价上限 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * | status |  | int | 状态 |  | 是 |
     * | updated |  | datetime | 更新时间 |  | 是 |
     * | comments |  | string | ADX返回信息 |  | 是 |
     * | updated_user |  | string | 更新操作人 |  | 是 |
     */
    public function affiliate(Request $request)
    {
        $adType = ArrayHelper::getRequiredIn(Campaign::getAdTypeLabels());
        $productType = ArrayHelper::getRequiredIn(Product::getTypeLabels());
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'ad_type' => "required|in:{$adType}",
                'products_type' => "required|in:{$productType}",
                'mode' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认25
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1
        $prefix = \DB::getTablePrefix();

        $campaignId = $params['campaignid'];
        $campaign = Campaign::find($campaignId);
        if (empty($campaign)) {
            return $this->errorCode(5002);// @codeCoverageIgnore
        }
        $revenue_type = $campaign->revenue_type; //计费类型
        $platform = $campaign->platform;
        $ad_type = $campaign->ad_type;
        $kind = Affiliate::KIND_ALLIANCE;

        $when = " CASE ";
        foreach (Campaign::getRevenueTypeSort() as $k => $v) {
            $when .= ' WHEN revenue_type = ' . $k . ' THEN ' . $v;
        }
        $when .= " END AS sort_revenue_type ";

        //计费类型映射
        $convertRevenueType = implode(',', Campaign::getCRevenueTypeToARevenueType($revenue_type));
        $agencyId = Auth::user()->agencyid;
        $sql = "(SELECT DISTINCT
                    aff.affiliateid,
                    aff.`name` AS affiliates_name,
                    aff.brief_name,
                    aff.`mode`,
                    aff.type AS affiliates_type,
                    aff.condition_data,
                    aff.adx_class,
                    u.contact_name AS contact,
                    u.email_address AS email,
                    u.qq AS contact_qq,
                    u.contact_phone,
                    {$prefix}users.contact_name AS creator_name
                FROM {$prefix}affiliates AS aff
                INNER JOIN {$prefix}affiliates_extend AS ae ON aff.affiliateid = ae.affiliateid
                INNER JOIN {$prefix}accounts AS ac ON ac.account_id = aff.account_id
                INNER JOIN {$prefix}users AS u ON u.default_account_id = ac.account_id
                INNER JOIN {$prefix}users ON {$prefix}users.user_id = aff.creator_uid
                AND u.user_id = ac.manager_userid
                WHERE 1
                AND aff.agencyid = {$agencyId}
                AND aff.`mode` = '{$params['mode']}'
                AND ae.revenue_type in ({$convertRevenueType})
                AND FIND_IN_SET({$ad_type},aff.ad_type)
                AND ae.ad_type = {$ad_type}
                AND (aff.app_platform & {$platform} > 0)
                AND (aff.kind & {$kind} > 0)
                AND aff.affiliates_status = 1) AS af";

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table(DB::raw($sql));
        $select = $select->leftJoin('banners AS b', function ($join) use ($campaignId) {
            $join->on(DB::raw('af.affiliateid'), '=', 'b.affiliateid')
                ->where('b.campaignid', '=', $campaignId);
        })
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('products AS p', 'c.product_id', '=', 'p.id')
            ->leftJoin('category AS cat', 'b.category', '=', 'cat.category_id')
            ->leftJoin('attach_files AS atf', 'b.attach_file_id', '=', 'atf.id')
            ->select(
                DB::raw("af.affiliateid"),
                DB::raw("af.affiliates_name"),
                DB::raw("af.brief_name"),
                DB::raw("af.contact"),
                DB::raw("af.email"),
                DB::raw("af.contact_qq"),
                DB::raw("af.contact_phone"),
                DB::raw("af.affiliates_type"),
                DB::raw("af.adx_class"),
                DB::raw("af.condition_data"),
                DB::raw("af.creator_name"),
                "b.bannerid",
                "b.updated",
                'b.comments',
                'b.bidding_price',
                DB::raw("CASE
                     WHEN {$prefix}b.`status` = 0 THEN 100
                     WHEN {$prefix}b.`status` = 1 THEN 99
                     WHEN {$prefix}b.`status` = 2 THEN 98
                     WHEN {$prefix}b.`status` = 6 THEN 97
                     WHEN {$prefix}b.`status` = 4 THEN 96
                     WHEN {$prefix}b.`status` = 3 THEN 95
                     WHEN {$prefix}b.`status` = 7 THEN 04
                   END AS `bstatus`"),
                DB::raw("(SELECT
                            username
                        FROM
                            {$prefix}users
                        WHERE
                            user_id = {$prefix}b.updated_uid
                    )AS updated_user"),
                "b.pause_status",
                "b.af_day_limit",
                "b.app_id",
                "b.app_id_icon",
                "b.app_rank",
                "b.af_manual_price",
                "b.revenue_type",
                DB::raw("IFNULL({$prefix}b.status, IF(af.mode=4,8,7)) AS status"),
                "b.category AS category_id",
                "cat.parent as category",
                "b.flow_ratio",
                "b.condition",
                "p.link_name",
                "p.link_url",
                "atf.channel",
                "atf.real_name",
                "atf.id as attach_id",
                "atf.file"
            );

        //===================搜索==========================
        if (isset($params['search']) && $params['search'] != '') {
            $select = $select->where(DB::raw("af.brief_name"), 'like', "%{$params['search']}%");
        }

        //===================分页==========================
        $total = $select->count();
        //====================排序========================
        $sortAttr = '';
        if (isset($params['sort']) && strlen($params['sort']) > 0) {
            $sortType = $this->getSortType($params['sort']);
            $sortAttr = str_replace('-', '', $params['sort']);
            switch ($sortAttr) {
                case 'category_id':
                    $select->orderBy('category_id', $sortType);
                    break;
                case 'app_rank':
                    $select->orderBy('b.app_rank', $sortType);
                    break;
                case 'status':
                    $select->orderBy('b.status', $sortType);
                    break;
                case 'updated':
                    $select->orderBy('b.updated', $sortType);
                    break;
                case 'updated_user':
                    $select->orderBy('updated_user', $sortType);
                    break;
                case 'flow_ratio':
                    $select->orderBy('b.flow_ratio', $sortType);
                    break;
                case 'af_day_limit':
                    $select->orderBy('b.af_day_limit', $sortType);
                    break;
            }
        } else {
            //默认排序
            $select->orderBy(DB::raw('bstatus'), 'desc');
        }

        //按媒体价和结算价排序时，查询所有数据。
        if (empty($sortAttr) || ($sortAttr != 'media_price' && $sortAttr != 'revenue_price')) {
            $offset = (intval($pageNo) - 1) * intval($pageSize);
            $select->skip($offset)->take($pageSize);

        }
        //获取数据
        $rows = $select->get();
        $data = [];
        if (!empty($rows)) {
            foreach ($rows as $k => $v) {
                //重构数组
                foreach ($v as $ik => $iv) {
                    $val[$ik] = $iv;
                }
                $val['num'] = Affiliate::D_TO_C_NUM;
                //拼接下载包地址
                if ($val['file']) {
                    $val['file'] = UrlHelper::fileFullUrl($val['file'], $val['real_name']);
                }
                //计算默认媒体价
                if ($v['bannerid'] > 0) {
                    if ($v['af_manual_price'] > 0) {
                        $calDefaultPrice = CampaignService::calDefaultPrice($v['bannerid']);
                        $val['default_media_price'] = Formatter::asDecimal($calDefaultPrice);
                    } else {
                        $val['default_media_price'] = 0;
                    }
                } else {
                    $val['default_media_price'] = 0;
                }
                $val['ad_spec'] = Affiliate::getAdSpec($params['ad_type'], $v['adx_class']);
                //计算媒体价
                $price = $this->getMediaPriceInfo(
                    $campaignId,
                    $v['affiliateid'],
                    $v['bannerid'],
                    $ad_type,
                    $revenue_type
                );
                if (!empty($val['bidding_price'])) {
                    $decimals = Config::get('biddingos.jsDefaultInit.' . $v['revenue_type'] . '.decimal');
                    $val['bidding_price'] = Formatter::asDecimal($val['bidding_price'], $decimals);
                }
                $val['category_name'] = CategoryService::getCategories(
                    $v['category_id'],
                    $v['affiliateid']
                )['category_label'];
                $val['media_price'] = Formatter::asDecimal(isset($price['media_price'])
                    ? $price['media_price'] : 0);
                $val['revenue_price'] = Formatter::asDecimal(isset($price['revenue_price'])
                    ? $price['revenue_price'] : 0);

                // TODO 一对最大值比较版本的差异
                $val['compare_version'] = 0;
                if ($v['attach_id']) {
                    // 查出投放的渠道包的版本号和包名
                    $attach = AttachFile::where('id', $v['attach_id'])
                        ->select('version_code', 'package_name')
                        ->first()
                        ->toArray();
                    // 根据包名查找最大的版本号
                    $maxVersion = AttachFile::where('package_name', $attach['package_name'])
                        ->max('market_version_code');
                    if ($maxVersion > $attach['version_code']) {
                        // 需要更新包
                        $val['compare_version'] = 1;// @codeCoverageIgnore
                    }// @codeCoverageIgnore
                }
                //计算计费类型
                if (empty($v['revenue_type'])) {
                    $affiliate = AffiliateExtend::where('affiliateid', $v['affiliateid'])
                        ->where('ad_type', $ad_type)
                        ->whereIn('revenue_type', Campaign::getCRevenueTypeToARevenueType($revenue_type))
                        ->orderBy('sort_revenue_type', 'ASC')
                        ->select('revenue_type', DB::raw($when))
                        ->first();

                    $val['revenue_type'] = $affiliate->revenue_type;
                }
                $data[] = $val;
            }
        }

        //如果是媒体价或者结算价，先计算然后排序，然后在分页。
        if (isset($params['sort']) && strlen($params['sort']) > 0) {
            $sortType = $this->getSortType($params['sort']);
            $sortAttr = str_replace('-', '', $params['sort']);
            if ($sortAttr == 'media_price' || $sortAttr == 'revenue_price') {
                $data = ArrayHelper::arraySort($data, $sortAttr, $sortType);
                $data = array_slice($data, ($pageNo - 1) * $pageSize, $pageSize);
            }
        }

        return $this->success(null, [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $total
        ], $data);
    }

    /**
     * 媒体信息修改
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | int | 推广计划ID |  | 是 |
     * | affiliateid |  | int | 媒体ID |  |  |
     * | field |  | string | 字段名称 |  | 是 |
     * | value |  | string | 字段值 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function affiliateUpdate(Request $request)
    {
        $field = 'flow_ratio,media_price,af_day_limit,revenue_type,';
        $field .= 'category_id,app_rank,attach_id,revenue_price,condition,bidding_price';
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'affiliateid' => 'required',
                'field' => "required|in:{$field}",
            ], [], Affiliate::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $params = $request->all();
        $affiliate = Affiliate::find($params['affiliateid']);
        if ($params['field'] == 'flow_ratio' || $params['field'] == 'af_day_limit') {
            if ($params['field'] == 'flow_ratio') {
                $rules = ['value' => 'required|min:0|max:100'];
            } else {
                $rules = ['value' => 'required|min:0|max:1000000'];
            }
            if (($ret = $this->validate($request, $rules, [], Banner::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            $banner = CampaignService::getBannerOrCreate(
                $params['campaignid'],
                $params['affiliateid'],
                $affiliate->mode == Affiliate::MODE_ADX ?
                    Banner::STATUS_PENDING_SUBMIT : Banner::STATUS_PENDING_PUT
            );

            if (!$banner) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
            if ($params['field'] == 'flow_ratio') {
                $oldRatio = $banner->flow_ratio;
                $banner->flow_ratio = $params['value'];
                //流量变现比例
                $this->writeUpdateLog(
                    $banner,
                    6042,
                    Formatter::asDecimal($oldRatio, 0),
                    Formatter::asDecimal($params['value'], 0)
                );
            } else {
                if ($banner->af_day_limit < $params['value']) {
                    //如果是因为日限额不足暂停的，需要重新启动
                    // @codeCoverageIgnoreStart
                    if (Banner::STATUS_SUSPENDED == $banner->status &&
                        Banner::PAUSE_STATUS_EXCEED_DAY_LIMIT == $banner->pause_status
                    ) {
                        $restartResult = CampaignService::modifyBannerStatus(
                            $banner->bannerid,
                            Banner::STATUS_PUT_IN,
                            Auth::user()->account->isManager()
                        );
                        if ($restartResult !== true) {
                            return $this->errorCode($restartResult);
                        }

                        //添加操作日志，提高媒体日预算启动广告的日志
                        $args[] = $banner->campaign->appinfo->app_name; //广告名称
                        $args[] = $banner->affiliate->name; //媒体全称
                        $message = CampaignService::formatWaring(6046, $args);
                        OperationLog::store([
                            'category' => OperationLog::CATEGORY_BANNER,
                            'target_id' => $banner->bannerid,
                            'type' => OperationLog::TYPE_SYSTEM,
                            'operator' => Config::get('error')[6000],
                            'message' => $message,
                        ]);

                    }
                    // @codeCoverageIgnoreEnd
                }
                $oldValue = $banner->af_day_limit;
                if ($oldValue == 0) {
                    $oldValue = $banner->campaign->day_limit;// @codeCoverageIgnore
                }// @codeCoverageIgnore
                $banner->af_day_limit = $params['value'];

                //修改媒体日限额
                $this->writeUpdateLog(
                    $banner,
                    6038,
                    Formatter::asDecimal($oldValue, 0),
                    Formatter::asDecimal($params['value'], 0)
                );
            }
            $result = $banner->save();
            if (!$result) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
        } elseif ($params['field'] == 'condition') {
            $rules = ['value' => 'required'];
            if (($ret = $this->validate($request, $rules, [], Banner::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }

            $data = json_decode($params['value'], true);
            if (is_null($data)) {
                return $this->errorCode(5000);
            }

            $banner = CampaignService::getBannerOrCreate(
                $params['campaignid'],
                $params['affiliateid'],
                Banner::STATUS_PENDING_PUT
            );

            if (!$banner) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }

            $banner->condition = $params['value'];
            $result = $banner->save();
            if (!$result) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }

            $list = [];
            foreach ($data as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    $list[$k] = $v;
                }
            }
            $redisData = json_encode($list);
            $redis = Redis::connection('redis_delivery');
            $redis->set('dir_filter_banner_' . $banner->bannerid, $redisData);

            // 定向投放相关性
            if (isset($data['filter_tag_off_function'])) {
                $redisPika = Redis::connection('redis_pika_target');
                $redisPika->set('dir_filter_banner_' . $banner->bannerid, $redisData);

                $redisPika->srem('filter_tag_off_function_' . $banner->bannerid, 'filter_target_adtext');
                $redisPika->srem('filter_tag_off_function_' . $banner->bannerid, 'filter_target_user');
                if (is_array($data['filter_tag_off_function'])) {
                    foreach ($data['filter_tag_off_function'] as $v) {
                        if ($v == 'filter_target_adtext') {
                            $redisPika->sadd('filter_tag_off_function_' . $banner->bannerid, 'filter_target_adtext');
                        }
                        if ($v == 'filter_target_user') {
                            $redisPika->sadd('filter_tag_off_function_' . $banner->bannerid, 'filter_target_user');
                        }
                    }
                }
            }
        } elseif ($params['field'] == 'category_id' || $params['field'] == 'app_rank') {
            // @codeCoverageIgnoreStart
            $rank = ArrayHelper::getRequiredIn(AppInfo::getRankStatusLabels());
            if ($params['field'] == 'category_id') {
                $rules = ['value' => 'required'];
            } else {
                $rules = ['value' => "required|in:{$rank}"];
            }
            if (($ret = $this->validate($request, $rules, [], Banner::attributeLabels())) !== true) {
                return $this->errorCode(5000, $ret);
            }
            $banner = CampaignService::getBannerOrCreate(
                $params['campaignid'],
                $params['affiliateid'],
                Banner::STATUS_PENDING_PUT
            );
            if ($params['field'] == 'category_id') {
                $oldCategory = $banner->category;
                $banner->category = $params['value'];

                //修改分类操作日志
                if ($oldCategory == 0) {
                    $oldCategory = '无';
                } else {
                    $oldCategory = CategoryService::getCategories(
                        $oldCategory,
                        $params['affiliateid']
                    )['category_label'];
                }
                $category = CategoryService::getCategories(
                    $params['value'],
                    $params['affiliateid']
                )['category_label'];
                $this->writeUpdateLog($banner, 6043, $oldCategory, $category);
            } else {
                $oldRank = $banner->app_rank;
                $banner->app_rank = $params['value'];

                //修改广告等级操作日志
                if (empty($oldRank)) {
                    $oldRank = '无';
                } else {
                    $oldRank = AppInfo::getRankStatusLabels($oldRank);
                }
                $rank = AppInfo::getRankStatusLabels($params['value']);
                $this->writeUpdateLog($banner, 6032, $oldRank, $rank);
            }
            $banner->buildBannerText();
            $result = $banner->save();
            if ($result) {
                /**
                 * 如果 banner处于投放中或者暂停中
                 * 修改类别会影响投放，投放关联关系也要处理
                 */
                if (in_array($banner->status, [Banner::STATUS_SUSPENDED, Banner::STATUS_PUT_IN])) {
                    CampaignService::deAttachBannerRelationChain($banner->bannerid);
                    CampaignService::attachBannerRelationChain($banner->bannerid);
                }
            }
            // @codeCoverageIgnoreEnd
        } elseif ($params['field'] == 'revenue_type') {
            $adType = ArrayHelper::getRequiredIn(Campaign::getAdTypeLabels());
            if (($ret = $this->validate($request, [
                    'ad_type' => "required|in:{$adType}",
                    'value' => 'required',
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            $banner = Banner::whereMulti([
                'campaignid' => $params['campaignid'],
                'affiliateid' => $params['affiliateid'],
            ])->first();
            // @codeCoverageIgnoreStart
            if (empty($banner)) {
                $data['storagetype'] = (Campaign::AD_TYPE_APP_MARKET == $params['ad_type']) ? 'app' : 'url';
                $banner = CampaignService::getBannerOrCreate(
                    $params['campaignid'],
                    $params['affiliateid'],
                    $affiliate->mode == Affiliate::MODE_ADX ?
                        Banner::STATUS_PENDING_SUBMIT : Banner::STATUS_PENDING_PUT,
                    $data
                );
            }
            // @codeCoverageIgnoreEnd
            $oldRevenueType = $banner->revenue_type;
            //更新计费方式
            $result = Banner::where('bannerid', $banner->bannerid)->update([
                'revenue_type' => $params['value'],
                'af_manual_price' => 0
            ]);
            CampaignService::updateBannerBilling($banner->bannerid);
            if (!$result) {
                return $this->errorCode(5001);// @codeCoverageIgnore
            }
            //修改计费类型
            $this->writeUpdateLog(
                $banner,
                6041,
                Campaign::getRevenueTypeLabels($oldRevenueType),
                Campaign::getRevenueTypeLabels($params['value'])
            );
        } elseif ($params['field'] == 'media_price' || $params['field'] == 'revenue_price') {
            if (($ret = $this->validate($request, [
                    'bannerid' => 'required',
                    'value' => 'required',
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            $banner = CampaignService::getBannerOrCreate(
                $params['campaignid'],
                $params['affiliateid'],
                $affiliate->mode == Affiliate::MODE_ADX ?
                    Banner::STATUS_PENDING_SUBMIT : Banner::STATUS_PENDING_PUT
            );

            if ($params['field'] == 'media_price') {
                $mediaPrice = Formatter::asDecimal($params['value']);
                $defaultInit = Config::get('biddingos.jsDefaultInit');
                $defaultPriceInit = $defaultInit[$banner->revenue_type];
                $mediaMaxPrice = Formatter::asDecimal($defaultPriceInit['revenue_max']);
                if ($mediaPrice > $mediaMaxPrice) {
                    return $this->errorCode(5081);// @codeCoverageIgnore
                }

                //修改媒体价
                $oldPrice = $banner->af_manual_price;
                $banner->af_manual_price = $mediaPrice == 0 ? -1 : $mediaPrice;
                LogHelper::info('campaignid:' . $params['campaignid'] . ', affiliateid:' .
                    $params['affiliateid'] . ' media price change:' .
                    $oldPrice . '->' . $mediaPrice . ' by userid ' . Auth::user()->user_id);
                CampaignService::updateBannerBilling($banner->bannerid);
                //修改媒体价
                if ($oldPrice == 0) {
                    $oldPrice = CampaignService::calDefaultPrice($banner->bannerid);
                }
                $decimal = Config::get('biddingos.jsDefaultInit.' . $banner->revenue_type . '.decimal');
                $this->writeUpdateLog(
                    $banner,
                    6040,
                    Formatter::asDecimal($oldPrice, $decimal),
                    Formatter::asDecimal($mediaPrice, $decimal)
                );
            } else {
                //修改广告主计费价
                // @codeCoverageIgnoreStart
                $decimal = Config::get('biddingos.jsDefaultInit.' . $banner->revenue_type . '.decimal');
                $oldPrice = Formatter::asDecimal($banner->revenue_price, $decimal);
                if ($oldPrice == 0) {
                    $oldPrice = Formatter::asDecimal($banner->campaign->revenue, $decimal);
                }

                $revenue = Campaign::where('campaignid', $params['campaignid'])->pluck('revenue');
                $revenuePrice = Formatter::asDecimal($params['value']);
                $revenue = Formatter::asDecimal($revenue);
                if (0 < $revenuePrice && $revenuePrice <= $revenue) {
                    $banner->revenue_price = $revenuePrice;
                    $banner->save(); //先更新，然后再计算
                } else {
                    return $this->errorCode(5045);// @codeCoverageIgnore
                }

                LogHelper::info('campaignid:' . $params['campaignid'] . ', affiliateid:' .
                    $params['affiliateid'] . ' revenue price change:' .
                    $oldPrice . '->' . $revenuePrice . ' by userid ' . Auth::user()->user_id);
                CampaignService::updateBannerBilling($banner->bannerid, true);
                //修改广告主计费价
                $this->writeUpdateLog($banner, 6039, $oldPrice, Formatter::asDecimal($params['value'], $decimal));
                // @codeCoverageIgnoreEnd
            }
            $banner->save();
        } elseif ($params['field'] == 'bidding_price') {
            $banner = CampaignService::getBannerOrCreate(
                $params['campaignid'],
                $params['affiliateid'],
                Banner::STATUS_PENDING_SUBMIT
            );
            $banner->bidding_price = $params['value'] == '' ? null : $params['value'];
            $banner->save();
        } elseif ($params['field'] == 'attach_id') {
            // @codeCoverageIgnoreStart
            //选择包信息
            $adType = ArrayHelper::getRequiredIn(Campaign::getAdTypeLabels());
            if (($ret = $this->validate($request, [
                    'bannerid' => 'required',
                    'value' => 'required',
                    'ad_type' => "required|in:{$adType}",
                ], [], Campaign::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }

            //根据 attach_id 查找到包对应的信息
            $attachFile = AttachFile::find($params['value']);
            if (!$attachFile) {
                return $this->errorCode(5069);// @codeCoverageIgnore
            }
            $affiliate = Affiliate::find($params['affiliateid']);
            //检查 Banner是否存在，不存在创建一条 banner
            if ($affiliate->mode == Affiliate::MODE_ADX) {
                $bannerStatus = Banner::STATUS_PENDING_SUBMIT;
            } else {
                $bannerStatus = Banner::STATUS_PENDING_PUT;
            }
            $params['storagetype'] = (Campaign::AD_TYPE_APP_MARKET == $params['ad_type']) ? 'app' : 'url';
            $banner = CampaignService::getBannerOrCreate(
                $params['campaignid'],
                $params['affiliateid'],
                $bannerStatus,
                $params
            );
            //把下载链接加入到banner表download_url
            if ($banner->checkMode([
                Affiliate::MODE_PROGRAM_DELIVERY_STORAGE,
                Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE
            ])
            ) {
                $banner->download_url = CampaignService::attachFileLink($attachFile->id);
                $banner->buildBannerText();
            }

            $affiliate = $banner->affiliate;
            $mode = $affiliate->mode;
            $symbol = $affiliate->symbol;
            //如果不是程序化投放入库的，则要生成一个 appId
            if (Affiliate::MODE_PROGRAM_DELIVERY_STORAGE != $mode &&
                (Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE && $affiliate->audit == Affiliate::AUDIT_NOT_APPROVAL)
            ) {
                $appId = $banner->app_id;
                if (empty($appId)) {
                    if ($symbol == 'uucun') {
                        $this->uucun($banner, $attachFile->package_name, $symbol);
                    } else {
                        if ($banner->affiliate->type == Affiliate::TYPE_NOT_STORAGE_QUERY) {
                            $banner->app_id = date('Hi') . str_random(8);
                        }
                    }
                }
            }
            //发送替换包邮件给媒体
            MessageService::sendPackageChangeMail($params['campaignid'], $params['value']);

            //把包修改为使用中
            $attachId = CampaignService::getAttachFileId(
                $params['campaignid'],
                AttachFile::FLAG_NOT_USED,
                $params['value']
            );
            if (0 < $attachId) {
                AttachFile::processPackage($params['campaignid'], $attachId, Campaign::ACTION_EDIT);
            }
            $oldAttachFileId = $banner->attach_file_id;
            //设置banner与包关联
            $banner->attach_file_id = $attachFile->id;
            $banner->save();

            // @codeCoverageIgnoreEnd*/
            //生成等价广告关联
            $key = $banner->campaign->equivalence;
            if ($key) {
                CampaignService::attachEquivalencePackageName($banner->campaign->equivalence);
            }

            if ($oldAttachFileId == 0) {
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_BANNER,
                    'type' => OperationLog::TYPE_MANUAL,
                    'target_id' => $banner->bannerid,
                    'operator' => Auth::user()->contact_name,
                    'message' => CampaignService::formatWaring(6033, [$banner->attachfile->real_name]),
                ]);
            } elseif ($oldAttachFileId != $attachFile->id) {
                $oldAttachFile = AttachFile::find($oldAttachFileId)->real_name;
                //更新日志
                $this->writeUpdateLog($banner, 6044, $oldAttachFile, $attachFile->real_name);
            }
        }

        if ($params['field'] == 'bidding_price' || $banner->affiliate->mode == Affiliate::MODE_ADX) {
            //更新ADX竞价上限
            $this->updateBannerBillingAdx($banner->bannerid);
        } else {
            //同步计算广告计费价及媒体价
            CampaignService::updateBannerBilling($banner->bannerid);
        }

        return $this->success();
    }

    /**
     * 投放
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | bannerid |  | integer | 广告ID |  | 是 |
     * | status |  | integer | 状态 |  | 是 |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | affiliateid |  | integer | 媒体ID |  | 是 |
     * | mode |  | integer | 媒体类型 |  | 是 |
     * @param Request $request
     * @return mixed
     * @codeCoverageIgnore
     */
    public function release(Request $request)
    {
        //入库投放
        $bannerStatus = ArrayHelper::getRequiredIn(Banner::getStatusLabels());
        if (($ret = $this->validate($request, [
                'bannerid' => 'required',
                'status' => "required|in:{$bannerStatus}",
            ], [], Banner::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        //获取所有参数
        $params = $request->all();
        $campaignInfo = Campaign::find($params['campaignid']);
        $params['storagetype'] = (Campaign::AD_TYPE_APP_MARKET == $campaignInfo->ad_type) ? 'app' : 'url';
        $isManager = Auth::user()->account->isManager();
        if (empty($params['bannerid'])) {
            //入库投放
            if (($ret = $this->validate($request, [
                    'campaignid' => 'required',
                    'affiliateid' => 'required',
                    'mode' => 'required'
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }
            //检测campaign状态
            $checkResult = $campaignInfo->status == Campaign::STATUS_DELIVERING ? true : false;
            if (!$checkResult) {
                return $this->errorCode(5070);
            }

            DB::beginTransaction();  //事务开始
            //点击投放
            if ($params['action'] == Banner::ACTION_APPROVAL) {
                $audit = Affiliate::find($params['affiliateid'])->audit;
                if (Affiliate::AUDIT_APPROVAL == $audit ||
                    Affiliate::MODE_PROGRAM_DELIVERY_STORAGE == $params['mode']
                ) {
                    $banner_status = Banner::STATUS_PENDING_MEDIA;
                } elseif (Affiliate::MODE_ADX == $params['mode']) {
                    $banner_status = Banner::STATUS_PENDING_SUBMIT;
                } else {
                    $banner_status = in_array(
                        $campaignInfo->ad_type,
                        [Campaign::AD_TYPE_APP_STORE]
                    ) ? Banner::STATUS_PENDING_PUT : Banner::STATUS_PENDING_MEDIA;
                }

                $banner = Banner::where('campaignid', $params['campaignid'])
                    ->where('affiliateid', $params['affiliateid'])
                    ->first();
                if (!$banner) {
                    $banner = CampaignService::getBannerOrCreate(
                        $params['campaignid'],
                        $params['affiliateid'],
                        $banner_status,
                        $params
                    );
                }

                $this->updateAppId($banner, $banner->affiliate, $campaignInfo);

                //如果是人工投放，则简化投放流程
                if (Affiliate::MODE_ARTIFICIAL_DELIVERY == $params['mode']) {
                    $result = $this->artificialDelivery($banner, $params, $campaignInfo->ad_type);
                    if ($result !== true) {
                        return $this->errorCode($result);
                    }
                }

                //如果是程序化投放，不入库，则跳过媒体审核流程
                if (Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE == $params['mode']) {
                    //如果是 app stroe，新建的不处理
                    $result = $this->unStoreDelivery($banner, $params);
                    //投放失败
                    if ($result !== true) {
                        DB::rollback();
                        return $this->errorCode($result);
                    }
                }

                if (Affiliate::MODE_ADX == $params['mode']) {
                    //调用类上传素材
                    $ret = $this->ADXSubmitMaterial($banner, $isManager);
                    if ($ret !== true) {
                        if (is_array($ret)) {
                            return $this->errorCode(1, $ret['msg']);
                        } else {
                            return $this->errorCode($ret);
                        }
                    }
                }
                //同步计算广告计费价及媒体价
                CampaignService::updateBannerBilling($banner->bannerid);

                //人工，不入库投放操作日志
                $this->writeDeliveryLog($banner, 6034);
            }
            DB::commit(); //事务结束
        } else {
            $bannerStatus = ArrayHelper::getRequiredIn(Banner::getStatusLabels());
            if (($ret = $this->validate($request, [
                    'status' => "required|in:{$bannerStatus}",
                ], [], Affiliate::attributeLabels())) !== true
            ) {
                return $this->errorCode(5000, $ret);
            }

            $banner = Banner::find($params['bannerid']);
            if (!$banner) {
                return $this->errorCode(5001);
            }

            //如果是 appstore，则判断级别，类别是否有输入
            if (in_array($campaignInfo->ad_type, [Campaign::AD_TYPE_APP_STORE])) {
                if ($params['action'] == Banner::ACTION_APPROVAL) {
                    if (0 == $banner->app_rank) {
                        return $this->errorCode(5075); //先设置级别
                    }

                    if (0 == $banner->category) {
                        return $this->errorCode(5076); //先设置类别
                    }
                }
            }
            $productType = $campaignInfo->product->type;
            $affiliate = $banner->affiliate;

            DB::beginTransaction();  //事务开始
            //入库
            if (Affiliate::MODE_PROGRAM_DELIVERY_STORAGE == $affiliate->mode) {
                if ($params['action'] == Banner::ACTION_APPROVAL) {
                    if (Banner::STATUS_PENDING_MEDIA == $banner->status) {
                        DB::rollback();
                        return $this->errorCode(5072);
                    }
                }
            } else {
                //不接受投放
                if (Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE == $banner->status
                    && $affiliate->audit == Affiliate::AUDIT_APPROVAL
                ) {
                    if (Banner::STATUS_PENDING_MEDIA == $banner->status) {
                        DB::rollback();
                        return $this->errorCode(5072);
                    }
                }

                $this->updateAppId($banner, $affiliate, $campaignInfo);
            }

            //根据 campaignId 取得 platform
            $campaign = Campaign::find($banner->campaignid);
            //如果Campaign为不是指定的状态，不允许投放
            $checkResult = $campaign->status == Campaign::STATUS_DELIVERING ? true : false;
            if (!$checkResult) {
                DB::rollback();
                return $this->errorCode(5070);
            }
            $platform = AppInfo::where('app_id', $campaign->campaignname)->pluck('platform');

            //如果不是投放中的，增加广告位判断
            if (!in_array($banner->status, [Banner::STATUS_PUT_IN])) {
                //检查是否有符合的广告位
                if ($affiliate->mode == Affiliate::MODE_ARTIFICIAL_DELIVERY) {
                    $count = $this->getArtificialZoneCount($banner->affiliateid);
                } elseif ($affiliate->mode == Affiliate::MODE_ADX) {
                    $count = $this->getAdxZoneCount($banner->affiliateid, $campaign->platform);
                } else {
                    $type = Campaign::getZoneTypeToAdType($campaign->ad_type);
                    $count = $this->getZoneCount($banner->affiliateid, $type, $platform);
                }
                if (0 == $count) {
                    DB::rollback();
                    return $this->errorCode(5073);
                }
            }
            //入库媒体，不入库媒体，需审核投放时待媒体审核
            if (Affiliate::MODE_PROGRAM_DELIVERY_STORAGE == $affiliate->mode
                || (Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE == $affiliate->mode
                    && $affiliate->audit == Affiliate::AUDIT_APPROVAL)
            ) {
                $putInStatus = Banner::STATUS_PENDING_MEDIA;
            } else {
                $putInStatus = Banner::STATUS_PUT_IN;
            }
            switch ($params['status']) {
                case Banner::STATUS_PUT_IN:
                    if ($params['action'] == Banner::ACTION_PUT_IN_SUSPEND) {
                        $ret = CampaignService::modifyBannerStatus(
                            $params['bannerid'],
                            Banner::STATUS_SUSPENDED,
                            $isManager
                        );
                        if ($ret !== true) {
                            DB::rollback();
                            return $this->errorCode($ret);
                        }

                        //暂停投放
                        $this->writeDeliveryLog($banner, 6036);
                    }
                    break;
                case Banner::STATUS_SUSPENDED://暂停
                    if ($params['action'] == Banner::ACTION_PUT_IN_SUSPEND) {
                        $ret = CampaignService::modifyBannerStatus(
                            $params['bannerid'],
                            Banner::STATUS_SUSPENDED,
                            $isManager
                        );
                    } else {
                        $ret = CampaignService::modifyBannerStatus(
                            $params['bannerid'],
                            Banner::STATUS_PUT_IN,
                            $isManager
                        );
                        //继续投放
                        $this->writeDeliveryLog($banner, 6037);
                    }
                    if ($ret !== true) {
                        DB::rollback();
                        return $this->errorCode($ret);
                    }
                    break;
                case Banner::STATUS_APP_ID://继续投放
                    $ret = CampaignService::modifyBannerStatus($params['bannerid'], $putInStatus, $isManager);
                    if ($ret !== true) {
                        DB::rollback();
                        return $this->errorCode($ret);
                    }
                    break;
                case Banner::STATUS_NOT_ACCEPTED:     //验证通过 -> 投放
                    //更新包状态为生效，ADX素材重新提交
                    if (Affiliate::MODE_ADX == $params['mode']) {
                        $ret = $this->ADXSubmitMaterial($banner, $isManager);
                        if ($ret !== true) {
                            if (is_array($ret)) {
                                return $this->errorCode(1, $ret['msg']);
                            } else {
                                return $this->errorCode($ret);
                            }
                        }
                    } else {
                        if ($productType == Product::TYPE_APP_DOWNLOAD) {
                            $ret = $this->isAttachAvailable($params['bannerid']);
                            if ($ret !== true) {
                                DB::rollback();
                                return $this->errorCode($ret);
                            }
                        }
                        $ret = CampaignService::modifyBannerStatus($params['bannerid'], $putInStatus, $isManager);
                        if ($ret !== true) {
                            DB::rollback();
                            return $this->errorCode($ret);
                        }
                    }

                    //如果
                    break;
                case Banner::STATUS_PENDING_MEDIA:
                    $ret = CampaignService::modifyBannerStatus(
                        $params['bannerid'],
                        Banner::STATUS_PENDING_PUT,
                        $isManager
                    );
                    if ($ret !== true) {
                        DB::rollback();
                        return $this->errorCode($ret);
                    }
                    //取消投放
                    $this->writeDeliveryLog($banner, 6035);
                    break;
                case Banner::STATUS_PENDING_PUT:
                    if (Affiliate::MODE_ARTIFICIAL_DELIVERY == $params['mode']) {
                        //如果是人工投放，则简化投放流程
                        $result = $this->artificialDelivery($banner, $params, $campaignInfo->ad_type);
                        if ($result !== true) {
                            return $this->errorCode($result);
                        }
                    } else {
                        //如果不是 appstore
                        if (!in_array($campaign->ad_type, [Campaign::AD_TYPE_APP_STORE]) &&
                            $productType == Product::TYPE_APP_DOWNLOAD
                        ) {
                            $ret = $this->isAttachAvailable($params['bannerid'], $productType);
                            if ($ret !== true) {
                                DB::rollback();
                                return $this->errorCode($ret);
                            }

                            $channel = Banner::find($params['bannerid'])->attachFile->channel;
                            if (!$channel) {
                                DB::rollback();
                                return $this->errorCode(5074);
                            }
                        }
                        //包上传通过了, 还有storagetype
                        Banner::where('bannerid', $params['bannerid'])->update([
                            'storagetype' => $params['storagetype'],
                            'affiliate_checktime' => date("Y-m-d H:i:s")
                        ]);
                        $ret = CampaignService::modifyBannerStatus($params['bannerid'], $putInStatus, $isManager);
                        if ($ret !== true) {
                            DB::rollback();
                            return $this->errorCode($ret);
                        }
                    }

                    //投放广告
                    $this->writeDeliveryLog($banner, 6034);
                    break;
                case Banner::STATUS_PENDING_SUBMIT:
                    //ADX提交素材
                    if (Affiliate::MODE_ADX == $params['mode']) {
                        $ret = $this->ADXSubmitMaterial($banner, $isManager);
                        if ($ret !== true) {
                            if (is_array($ret)) {
                                return $this->errorCode(1, $ret['msg']);
                            } else {
                                return $this->errorCode($ret);
                            }
                        }
                    }
                    break;
            }
            DB::commit(); //事务结束
        }
        return $this->success();
    }

    /**
     * ADX提交素材，并修改广告状态
     * @param $banner
     * @param $isManager
     * @return bool|int
     */
    private function ADXSubmitMaterial($banner, $isManager)
    {
        //调用类上传素材
        $affiliate = Affiliate::find($banner->affiliateid);
        $adxClass = $affiliate->adx_class;
        if (empty($adxClass)) {
            return 5202;
        }
        $adxInstance = AdxFactory::getClass($adxClass);
        $adx = $adxInstance->upload($banner->bannerid);

        $status = Banner::STATUS_PENDING_MEDIA;
        //不符合要求或者上传失败，状态更改为待提交
        if ($adx['code'] == Banner::ADX_NO_REACH_UPLOAD || $adx['code'] == Banner::ADX_UPLOAD_FAIL) {
            $status = Banner::STATUS_PENDING_SUBMIT;
        }
        //提交素材以后状态更改为待审核状态
        $ret = CampaignService::modifyBannerStatus($banner->bannerid, $status, $isManager);
        if ($ret !== true) {
            return $ret;
        }

        $banner->comments = $adx['msg'];
        $banner->updated_uid = Auth::user()->user_id;
        $banner->save();
        if ($adx['code'] == Banner::ADX_NO_REACH_UPLOAD || $adx['code'] == Banner::ADX_UPLOAD_FAIL) {
            return $adx;
        }
        return true;
    }

    /**
     * 获取渠道包信息
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | attach_id |  | integer | 附件ID |  | 是 |
     * | real_name |  | string | 包名 |  | 是 |
     * | channel |  | string | 渠道号 |  | 是 |
     * | date |  | datetime | 创建日期 |  | 是 |
     */
    public function clientPackage(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required|integer',
            ], [], Banner::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = $request->input('campaignid');
        //获取渠道包信息
        $data = AttachFile::where('campaignid', $campaignId)
            ->whereIn('flag', [AttachFile::FLAG_NOT_USED, AttachFile::FLAG_USING])
            ->select(
                'id AS attach_id',
                'real_name',
                'channel',
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date")
            )
            ->orderBy('id', 'DESC')
            ->get()
            ->toArray();

        return $this->success(null, null, $data);
    }

    /**
     * 获取媒体商分类
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |  | integer | 媒体ID |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | category |  | string | 分类 |  | 是 |
     * | parent |  | string | 父级分类 |  | 是 |
     */
    public function category(Request $request)
    {
        $adTypes = implode(',', Zone::getAdTypeCategory());
        if (($ret = $this->validate($request, [
                'affiliateid' => 'required',
                'ad_type' => "required|integer|in:{$adTypes}",
            ], [], Affiliate::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $affiliateId = $request->input('affiliateid');
        $adType = $request->input('ad_type');
        $platform = Campaign::PLATFORM_IPHONE_COPYRIGHT;
        $categories = Category::where('affiliateid', $affiliateId)
            ->where('ad_type', $adType)
            ->whereRaw("(platform & {$platform}) > 0")
            ->select('parent', 'category_id', 'name')
            ->get()->toArray();
        return $this->success([
            'category' => $categories,
            'parent' => Category::getParentLabels(),
        ]);
    }

    /**
     * 获取媒体商等级
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |  | integer | 媒体ID |  | 是 |
     * | platform |  | integer | 目标平台 |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | rank_limit |  | string | 等级 |  | 是 |
     * | rank_limit_label |  | string | 等级标签 |  | 是 |
     */
    public function rank(Request $request)
    {
        $platform = ArrayHelper::getRequiredIn(Campaign::getPlatformLabels(null, -1));
        $adType = ArrayHelper::getRequiredIn(Campaign::getAdTypeLabels());
        if (($ret = $this->validate($request, [
                'affiliateid' => 'required',
                'platform' => "required|in:{$platform}",
                'ad_type' => "required|in:{$adType}",
            ], [], Affiliate::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $affiliateId = $request->input('affiliateid');
        $platform = $request->input('platform');
        $ad_type = $request->input('ad_type');

        $ranks = Zone::where('affiliateid', $affiliateId)
            ->whereRaw("(platform & {$platform}) > 0")
            ->where('status', Zone::STATUS_OPEN_IN)
            //->where('type', '<>', Zone::TYPE_FLOW)
            ->whereIn('ad_type', Campaign::getZoneTypeToAdType($ad_type))
            ->select('rank_limit')
            ->distinct()
            ->get()
            ->toArray();

        if (!empty($ranks)) {
            $list = [];
            foreach ($ranks as $item) {
                $list[] = [
                    'rank_limit' => $item['rank_limit'],
                    'rank_limit_label' => AppInfo::getRankStatusLabels($item['rank_limit']),
                ];
            }
            return $this->success(null, null, $list);
        } else {
            return $this->errorCode(5054);
        }
    }

    /**
     * 获取计费类型
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |  | integer | 媒体ID |  | 是 |
     * | ad_type |  | integer | 广告类型 |  | 是 |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | revenue_type |  | integer | 计费类型 |  | 是 |
     */
    public function revenueType(Request $request)
    {
        $revenueType = ArrayHelper::getRequiredIn(Campaign::getRevenueTypeLabels());
        $adType = ArrayHelper::getRequiredIn(Campaign::getAdTypeLabels());
        $attribute = array_merge(Affiliate::attributeLabels(), Campaign::attributeLabels());
        if (($ret = $this->validate($request, [
                'affiliateid' => 'required',
                'ad_type' => "required|in:{$adType}",
                'revenue_type' => "required|in:{$revenueType}",
            ], [], $attribute)) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $adType = $request->input('ad_type');
        $affiliateId = $request->input('affiliateid');
        $revenueType = $request->input('revenue_type');

        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $result = \DB::table('affiliates_extend AS ae')
            ->leftJoin('affiliates AS a', 'a.affiliateid', '=', 'ae.affiliateid')
            ->where('ae.ad_type', $adType)
            ->where('ae.affiliateid', $affiliateId)
            ->select('ae.revenue_type', 'a.mode')
            ->get();
        $list = [];

        $revenueTypeArr = Campaign::getCRevenueTypeToARevenueType($revenueType);
        foreach ($result as $item) {
            if ($item['mode'] == Affiliate::MODE_ARTIFICIAL_DELIVERY &&
                $item['revenue_type'] == Campaign::REVENUE_TYPE_CPM
            ) {
                continue;// @codeCoverageIgnore
            }
            //获取计费类型映射
            if (in_array($item['revenue_type'], $revenueTypeArr)) {
                $list[] = [
                    $item['revenue_type'] => Campaign::getRevenueTypeLabels($item['revenue_type']),
                ];
            }
        }
        return $this->success($list);
    }

    /**
     * 查找AppId
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |  | integer | 媒体ID |  | 是 |
     * | words |  | string | 关键字 |  | 是 |
     * | platform |  | integer | 目标平台 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function appSearch(Request $request)
    {
        $platform = ArrayHelper::getRequiredIn(Campaign::getPlatformLabels());
        if (($ret = $this->validate($request, [
                'affiliateid' => 'required',
                'words' => 'required',
                'platform' => "required|in:{$platform}"
            ], [], Affiliate::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        $symbol = Affiliate::where('affiliateid', $params['affiliateid'])->pluck('symbol');
        if (empty($symbol)) {
            return $this->errorCode(5061);
        }
        $data = SymbolFactory::getClass($symbol)
            ->getValue(['key' => $params['words'], 'platForm' => $params['platform']]);
        if ($data['result'] == 0) {
            return $this->success(null, null, $data['data']);
        } else {
            return $this->errorCode($data['result']);// @codeCoverageIgnore
        }
    }

    /**
     *更新APPid
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | bannerid |  | integer | 广告ID |  | 是 |
     * | app_id |  | string | 应用ID |  | 是 |
     * | app_icon |  | string | 应用图标 |  | 是 |
     * | app_name |  | string | 应用名称 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function appUpdate(Request $request)
    {
        if (($ret = $this->validate($request, [
                'bannerid' => 'required|integer',
                'app_id' => 'required',
                'app_icon' => 'required|string',
                'app_name' => 'required|string',
            ], [], Banner::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $banner = DB::table('banners')
            ->leftJoin('attach_files', 'banners.attach_file_id', '=', 'attach_files.id')
            ->where('bannerid', $params['bannerid'])
            ->select(
                'banners.status',
                'banners.campaignid',
                'banners.category',
                'banners.app_rank',
                'banners.bannerid',
                'attach_files.file',
                'attach_files.channel'
            )->first();
        if (!$banner) {
            return $this->errorCode(5001);// @codeCoverageIgnore
        }
        $campaign = Campaign::find($banner['campaignid']);
        if ($banner['category'] != '' && $banner['app_rank'] != ''
            && $banner['file'] != '' && $banner['channel'] != ''
            && $banner['status'] == Banner::STATUS_APP_ID && $campaign['status'] == Campaign::STATUS_DELIVERING
        ) {
            // @codeCoverageIgnoreStart
            $b = Banner::find($banner['bannerid']);
            $b->app_id = $params['app_id'];
            $b->app_id_icon = $params['app_icon'];
            $b->app_id_word = $params['app_name'];
            $b->status = Banner::STATUS_PUT_IN;
            $b->buildBannerText();
            $b->save();
            CampaignService::attachBannerRelationChain($params['bannerid']);
            // @codeCoverageIgnoreEnd
        } else {// @codeCoverageIgnore
            $b = Banner::find($banner['bannerid']);
            $b->app_id = $params['app_id'];
            $b->app_id_icon = $params['app_icon'];
            $b->app_id_word = $params['app_name'];
            $b->save();
        }
        return $this->success();
    }

    /**
     * 获取排序类型
     * @param $sort
     * @return string
     */
    private function getSortType($sort)
    {
        $sortType = 'asc';
        if (strncmp($sort, '-', 1) === 0) {
            $sortType = 'desc';
        }
        return $sortType;
    }

    /**
     * 获取媒体价
     * @param $campaignId
     * @param $affiliateId
     * @param int $bannerId
     * @param int $adType
     * @param int $revenueType
     * @return array|null
     * @codeCoverageIgnore
     */
    private function getMediaPriceInfo(
        $campaignId,
        $affiliateId,
        $bannerId = 0,
        $adType = Campaign::AD_TYPE_APP_MARKET,
        $revenueType = Campaign::REVENUE_TYPE_CPD
    ) {
    

        /*
        * 是否在 Banner 表中存在此记录了，则判断是否有手工改价的，
        * 并且有一种情况，广告主发布的广告如果是CPC计费的，则不能转为CPD媒体价
        */
        $prefix = DB::getTablePrefix();
        if ($bannerId > 0) {
            //如果生成banner，则直接取值
            $row = BannerBilling::where('bannerid', $bannerId)->first();
            if (empty($row)) {
                return null;//@codeCoverageIgnore
            }

            return [
                'media_price' => $row->af_income,
                'revenue_price' => $row->revenue,
            ];
        } else {
            //未生成banner直接计算媒体价及广告主出价
            $row = AffiliateExtend::where('affiliateid', $affiliateId)
                ->where('ad_type', $adType)
                ->select('revenue_type', 'num')
                ->orderBy('revenue_type', 'DESC')
                ->first();
            \DB::setFetchMode(\PDO::FETCH_ASSOC);
            $sql = "SELECT IFNULL(c.`rate`, 100) AS rate,IFNULL(aff.`income_rate`, 100) AS income_rate,c.revenue
                                 FROM {$prefix}campaigns AS c,
                                 {$prefix}affiliates AS aff
                                 WHERE 1 AND c.campaignid = {$campaignId} and aff.affiliateid = {$affiliateId}";
            $data = DB::selectOne($sql);

            $decimals = Config::get('biddingos.jsDefaultInit.' . $row->revenue_type . '.decimal');

            if (($revenueType == Campaign::REVENUE_TYPE_CPD || $revenueType == Campaign::REVENUE_TYPE_CPA)
                && $row->revenue_type == Campaign::REVENUE_TYPE_CPC
            ) {
                $mediaPrice = ($data['income_rate'] * $data['rate'] * $data['revenue'] / 10000) /
                    Affiliate::D_TO_C_NUM;
            } else {
                $mediaPrice = $data['income_rate'] * $data['rate'] * $data['revenue'] / 10000;
            }

            return [
                'media_price' => Formatter::asDecimal($mediaPrice, $decimals),
                'revenue_price' => $data['revenue'],
            ];
        }
    }

    /**
     * 媒体更新日志
     * @param $banner
     * @param $code
     * @param $oldValue
     * @param $value
     */
    private function writeUpdateLog($banner, $code, $oldValue, $value)
    {
        $message = CampaignService::formatWaring($code, [
            $banner->campaign->appinfo->app_name,
            $banner->affiliate->name,
            $oldValue,
            $value,
        ]);

        OperationLog::store([
            'category' => OperationLog::CATEGORY_BANNER,
            'type' => OperationLog::TYPE_MANUAL,
            'target_id' => $banner->bannerid,
            'operator' => Auth::user()->contact_name,
            'message' => $message
        ]);
    }

    /**
     * @param $banner
     * @param $affiliate
     * @param $campaignInfo
     * // @codeCoverageIgnore
     */
    private function updateAppId($banner, $affiliate, $campaignInfo)
    {
        //生成 banner 表的 appid
        if (empty($banner->app_id)) {
            //调用UUCUN接口
            if ($affiliate->symbol == 'uucun') {
                DB::setFetchMode(\PDO::FETCH_ASSOC);
                $appInfo = DB::table('campaigns as c')
                    ->leftJoin('appinfos as a', function ($join) {
                        $join->on('c.campaignname', '=', 'a.app_id');
                        $join->on('c.platform', '=', 'a.platform');
                    })
                    ->leftJoin('attach_files as af', 'c.campaignid', '=', 'af.campaignid')
                    ->where('c.campaignid', $banner->campaignid)
                    ->first();

                $package = $banner->attachFile;
                if (!empty($appInfo['app_id']) && !empty($appInfo['app_name']) && !empty($package->package_name)) {
                    $dataArr = SymbolFactory::getClass($affiliate->symbol)
                        ->getValue([
                            'bid' => urlencode($appInfo['app_id']),
                            'nm' => urlencode($appInfo['app_name']),
                            'pkgnm' => urlencode($package->package_name),
                            'version' => urlencode($appInfo['version_code'])
                        ]);
                    if (1 == $dataArr['st']) {
                        $banner->app_id = $dataArr['id'];
                        $banner->save();
                    }
                }
            } else {
                if ($banner->affiliate()->first()->type == Affiliate::TYPE_NOT_STORAGE_QUERY) {
                    $banner->app_id = date('Hi') . str_random(8);
                    $banner->save();
                }

                if (in_array($campaignInfo->ad_type, [Campaign::AD_TYPE_APP_STORE])) {
                    $banner->app_id = $this->appStoreApplicationId($banner->campaignid);
                    $banner->save();
                }
            }
        }
    }

    /**
     * 如果是 AppStore，则把苹果的 appid也更新到 banner
     * @param $campaignId
     * @codeCoverageIgnore
     */
    private function appStoreApplicationId($campaignId)
    {
        $applicationId = Campaign::find($campaignId)->appInfo->application_id;
        return $applicationId;
    }

    /**
     * 人工投放
     * @param $banner
     * @param $params
     * @param $campaignInfo
     * @return bool|int
     * @codeCoverageIgnore
     */
    private function artificialDelivery($banner, $params, $adType)
    {
        //人工投放的，状态改成待投放
        if (in_array($banner->status, [Banner::STATUS_PENDING_MEDIA, Banner::STATUS_PENDING_PUT])) {
            Banner::where('bannerid', $banner->bannerid)->update([
                'status' => Banner::STATUS_PENDING_PUT //状态为待投放
            ]);
        }

        //获取广告位个数
        $count = $this->getArtificialZoneCount($params['affiliateid']);
        if (0 < $count) {
            $this->tracker($params['campaignid'], $params['affiliateid']);
            Banner::where('campaignid', $params['campaignid'])
                ->where("affiliateid", $params['affiliateid'])
                ->update([
                    'affiliate_checktime' => date("Y-m-d H:i:s"),
                    'app_rank' => AppInfo::RANK_S,
                ]);
            //修改状态
            $ret = CampaignService:: modifyBannerStatus(
                $banner->bannerid,
                Banner::STATUS_PUT_IN,
                Auth::user()->account->isManager()
            );
            if ($ret !== true) {
                return $ret;
            }
        } else {
            return 5073;
        }
        return true;
    }
    /**
     * 获取人工投放广告位
     * @param $affiliateId
     * @return mixed
     * @codeCoverageIgnore
     */
    private function getArtificialZoneCount($affiliateId)
    {
        $count = Zone::where('affiliateid', $affiliateId)
            ->where('status', Zone::STATUS_OPEN_IN)
            ->where('type', Zone::TYPE_FLOW)
            ->where('platform', Campaign::PLATFORM_ANDROID)
            ->count();
        return $count;
    }

    /**
     * 校验ADX的广告位
     * @param $affiliateId
     * @param $platform
     * @return mixed
     */
    private function getAdxZoneCount($affiliateId, $platform)
    {
        $count = Zone::where('affiliateid', $affiliateId)
            ->where('status', Zone::STATUS_OPEN_IN)
            ->where('type', Zone::TYPE_FLOW)
            ->whereRaw("(platform & {$platform}) >0")
            ->count();
        return $count;
    }

    /**
     * 写入投放日志
     * @param $banner
     * @codeCoverageIgnore
     */
    private function writeDeliveryLog($banner, $code)
    {
        $message = CampaignService::formatWaring($code, [$banner->campaign->appinfo->app_name,
            $banner->affiliate->name]);
        OperationLog::store([
            'category' => OperationLog::CATEGORY_BANNER,
            'type' => OperationLog::TYPE_MANUAL,
            'target_id' => $banner->bannerid,
            'operator' => Auth::user()->contact_name,
            'message' => $message,
        ]);
    }

    /**
     * uucun接口返回AppId
     * @param $banner
     * @param $package
     * @param $symbol
     * @codeCoverageIgnore
     */
    private function uucun($banner, $package, $symbol)
    {
        $appInfo = DB::table('campaigns as c')
            ->leftJoin('appinfos as a', function ($join) {
                $join->on('c.campaignname', '=', 'a.app_id');
                $join->on('c.platform', '=', 'a.platform');
            })
            ->where('campaignid', $banner->campaignid)
            ->first();
        if (!empty($appInfo->app_id) && !empty($appInfo->app_name) && !empty($package)) {
            $dataArr = SymbolFactory::getClass($symbol)
                ->getValue([
                    'bid' => urlencode($appInfo->app_id),
                    'nm' => urlencode($appInfo->app_name),
                    'pkgnm' => urlencode($package)
                ]);
            if (1 == $dataArr['st']) {
                $banner->app_id = $dataArr['id'];
                $banner->save();
            }
        }
    }

    /**
     * 包状态改变
     * @param $banner
     * @param array $param
     * @return bool|int
     * @codeCoverageIgnore
     */
    private function unStoreDelivery($banner, $params = [])
    {
        $mode = Affiliate::where('affiliateid', $params['affiliateid'])->pluck('mode');
        if (Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE == $mode) {
            $campaign = Campaign::find($params['campaignid']);
            if (empty($campaign)) {
                return 5001;
            }
            $banner->status = Banner::STATUS_PENDING_PUT;
            $banner->save();

            $platform = AppInfo::where('app_id', $campaign->campaignname)->pluck('platform');
            //检查是否有符合的广告位
            $type = Campaign::getZoneTypeToAdType($campaign->ad_type);

            $count = $this->getZoneCount($params['affiliateid'], $type, $platform);
            if (0 == $count) {
                return 5073;
            }

            if (!in_array($campaign->product->type, Config::get('biddingos.withoutPackage'))
                || $campaign->ad_type == Campaign::AD_TYPE_APP_STORE
            ) {
                //判断级别跟类别是否输入
                if (0 == $banner->app_rank) {
                    return 5075;
                }
                if (0 == $banner->category) {
                    return 5076;
                }
                if ($campaign->ad_type != Campaign::AD_TYPE_APP_STORE) {
                    //如果没有上传包
                    $attachFile = AttachFile::where('id', $banner->attach_file_id)->first();
                    if (!empty($attachFile)) {
                        if (!$attachFile->file) {
                            return 5077;
                        }
                    } else {
                        return 5077;
                    }
                }
            } else {
                //如果不用传包的，则级别设置为S级
                $banner->app_rank = AppInfo::RANK_S;
                $banner->affiliate_checktime = date("Y-m-d H:i:s");
                $banner->category = 0; //不限
                //默认待投放
                $banner->save();
            }
            //检查广告素材是否存在
            $banner = Banner::where('campaignid', $params['campaignid'])
                ->where("affiliateid", $params['affiliateid'])
                ->first();
            if (!$banner) {
                return 5001;
            }

            //添加跟踪器
            $this->tracker($params['campaignid'], $params['affiliateid']);
            $audit = Affiliate::find($params['affiliateid'])->audit;
            //不入库媒体，勾选需要审核则状态为待审核，否则投放中
            $ret = CampaignService:: modifyBannerStatus(
                $banner->bannerid,
                $audit == Affiliate::AUDIT_NOT_APPROVAL ? Banner::STATUS_PUT_IN : Banner::STATUS_PENDING_MEDIA,
                Auth::user()->account->isManager()
            );
            if ($ret !== true) {
                return $ret;
            }

            //更新包的状态，当 attach 表的 flag 状态为2审核通过的时候更新
            $attachId = CampaignService::getAttachFileId($params['campaignid'], AttachFile::FLAG_NOT_USED);
            if (0 < $attachId) {
                AttachFile::processPackage($params['campaignid'], $attachId, AttachFile::FLAG_USING);
            }
        } else {
            return 5078;
        }
        return true;
    }

    /**
     * 添加追踪器
     * @param $campaignId
     * @param $affiliateId
     * @codeCoverageIgnore
     */
    private function tracker($campaignId, $affiliateId)
    {
        $campaign = Campaign::find($campaignId);
        $bannerId = Banner::where('campaignid', $campaign->campaignid)
            ->where("affiliateid", $affiliateId)
            ->pluck('bannerid');
        // 添加跟踪器
        $tracker = Tracker::store($campaign->campaignname, $campaign->client->clientid, $bannerId);
        // 关联广告计划
        $tracker->campaigns()->attach($campaign->campaignid, [
            'status' => Campaign::STATUS_DRAFT
        ]);
    }

    /**
     * 检测包是否上传
     * @param $bannerId
     * @return bool|int
     * @codeCoverageIgnore
     */
    private function isAttachAvailable($bannerId)
    {
        $banner = Banner::find($bannerId);
        if (!$banner) {
            return 5001;//@codeCoverageIgnore
        }
        //检测传包状态
        $attachFile = $banner->attachFile;
        if (!$attachFile) {
            return 5001;//@codeCoverageIgnore
        }
        //检测参数是否完整
        if (!$attachFile->file) {
            return 5001;//@codeCoverageIgnore
        }
        return true;
    }


    /**
     * 获取广告位个数
     * @param $affiliateId
     * @param $type
     * @param $platform
     * @return mixed
     * @codeCoverageIgnore
     */
    private function getZoneCount($affiliateId, $type, $platform)
    {
        $count = Zone::where('affiliateid', $affiliateId)
            ->where('status', Zone::STATUS_OPEN_IN)
            //->where('type', '<>', Zone::TYPE_FLOW)
            ->whereIn("ad_type", $type)
            ->whereRaw("(platform & {$platform}) > 0")
            ->count();
        return $count;
    }


    /**
     * 更新ADX竞价上限
     * @param $bannerId
     */
    private function updateBannerBillingAdx($bannerId)
    {
        \DB::setFetchMode(\PDO::FETCH_ASSOC);
        $prefix = \DB::getTablePrefix();
        $ret = DB::table('banners as b')
            ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->orderBy('b.bannerid')
            ->select(
                'b.bannerid',
                DB::raw("IF({$prefix}b.revenue_price > 0,{$prefix}b.revenue_price,{$prefix}c.revenue) as revenue"),
                'b.bidding_price'
            )->where('bannerid', $bannerId)
            ->first();

        $bannerBilling = BannerBilling::where('bannerid', $bannerId)->first();
        if ($bannerBilling) {
            BannerBilling::where('bannerid', $bannerId)->update([
                'af_income' => empty($ret['bidding_price']) ? 0 : $ret['bidding_price'],
                'revenue' => empty($ret['revenue']) ? 0 : $ret['revenue'],
            ]);
        } else {
            $bannerBilling = new BannerBilling();
            $bannerBilling->bannerid = $bannerId;
            $bannerBilling->af_income = empty($ret['bidding_price']) ? 0 : $ret['bidding_price'];
            $bannerBilling->revenue = empty($ret['revenue']) ? 0 : $ret['revenue'];
            $bannerBilling->save();
        }
    }
    /**
     * 获取投放消耗趋势
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
     * |  |  zonename | | string | 广告位名称 |  | 是 | |
     * |  | summary | | decimal | 广告位总消耗 |  | 是 | |
     * | summary |  |  |array | 时间 |  | 是 | |
     * |  | time |  |date | 时间 |  | 是 | |
     * |  | revenue |  |decimal | 消耗 |  | 是 | |
     */
    public function trend(Request $request)
    {
        if (($ret = $this->validate($request, [
                'bannerid' => 'required',
            ], [], Banner::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $bannerId = $request->input('bannerid', false);
        $start = $start = date('Y-m-d', strtotime("-30 days"));
        $end = date('Y-m-d', strtotime("-1 days"));
        //获取广告数据
        $prefix = DB::getTablePrefix();
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $res = DB::table("data_hourly_daily as h")
            ->join('banners as b', 'b.bannerid', '=', 'h.ad_id')
            ->join('zones as z', 'z.zoneid', '=', 'h.zone_id')
            ->where('h.ad_id', $bannerId)
            ->whereBetween('h.date', [$start, $end])
            ->select(
                DB::raw('IFNULL(SUM(' .$prefix . 'h.total_revenue),0) as revenue'), //广告主消耗
                'h.date',
                'h.zone_id',
                'z.zonename'
            )
            ->groupBy('h.date', 'h.zone_id')
            ->orderBy('h.date')
            ->get();
        $list = [];
        //重组数据
        foreach ($res as $val) {
            if (isset($list['data'][$val['zone_id']])) {
                $list['data'][$val['zone_id']]['child'][] = [
                    'revenue'=> $val['revenue'],
                    'date'=> $val['date'],
                ];
                $list['data'][$val['zone_id']]['summary'] += $val['revenue'];
            } else {
                $list['data'][$val['zone_id']]['child'][] = [
                    'revenue'=> $val['revenue'],
                    'date'=> $val['date'],
                ];
                $list['data'][$val['zone_id']]['summary'] = $val['revenue'];
                $list['data'][$val['zone_id']]['zonename'] = $val['zonename'];
            }

            if (isset($list['summary'][$val['date']])) {
                $list['summary'][$val['date']]['revenue'] += $val['revenue'];
            } else {
                $list['summary'][$val['date']]['date'] = $val['date'];
                $list['summary'][$val['date']]['revenue'] = $val['revenue'];
            }
        }
        if (!empty($list['data'])) {
            foreach ($list['data'] as $key => $val) {
                $revenue[$key] = $val['summary'];
            }
            array_multisort($revenue, SORT_DESC, $list['data']);
            array_slice($list['data'], 0, 5);//截取前五
        }
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
