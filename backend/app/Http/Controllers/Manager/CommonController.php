<?php

namespace App\Http\Controllers\Manager;

use App\Components\Formatter;
use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Http\Controllers\Controller;
use App\Models\AccountSubType;
use App\Models\Affiliate;
use App\Models\AppInfo;
use App\Models\AttachFile;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\OperationLog;
use App\Services\CampaignService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Components\Config;
use App\Components\Helper\StringHelper;
use App\Services\AccountService;
use App\Models\Account;

class CommonController extends Controller
{
    /**
     * 账号余额
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | balance |  | decimal  | 余额 |  | 是 |
     */
    public function balanceValue()
    {
        $account = Auth::user()->account;
        $balance = $account->balance;//账户
        $balance = $balance ? $balance->balance : 0;//推广金账户余额

        return $this->success(
            [
                'balance' => Formatter::asDecimal($balance),
            ]
        );
    }

    /**
     * 获取销售顾问
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | account_type |  | integer  | 账号类型 |  | 是 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | user_id |  | integer  | 用户ID |  | 是 |
     * | contact_name |  | string  | 联系人 |  | 是 |
     */
    public function sales(Request $request)
    {
        $account_type = (Account::TYPE_TRAFFICKER == $request->account_type) ?
        [AccountSubType::ACCOUNT_DEPARTMENT_MEDIA, AccountSubType::ACCOUNT_DEPARTMENT_OPERATION] :
        [AccountSubType::ACCOUNT_DEPARTMENT_SALES];
        $obj = $this->getUserList($account_type);
        return $this->success($obj, null, null);
    }
    

    /**
     * 获取销售顾问,运营顾问
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | account_department |  | integer  | 账号类型 |  | 是 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | user_id |  | integer  | 用户ID |  | 是 |
     * | contact_name |  | string  | 联系人 |  | 是 |
     */
    public function operation(Request $request)
    {
        $obj = $this->getUserList(AccountSubType::ACCOUNT_DEPARTMENT_OPERATION);
        return $this->success($obj, null, null);
    }

    /**
     * 选择渠道包
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | affiliateid |  | integer  | 媒体ID |  | 是 |
     * | campaignid |  | integer  | 推广计划ID |  | 是 |
     * | attach_id |  | integer  | 附件ID |  | 是 |
     * | ad_type |  | integer  | 广告类型 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function choosePackage(Request $request)
    {
        if (($ret = $this->validate($request, [
                'affiliateid' => 'required|integer',
                'campaignid' => 'required|integer',
                'attach_id' => 'required|integer',
                'ad_type' => 'required|integer'
            ], [], Affiliate::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $affiliateId = Input::get('affiliateid');
        $ad_type = Input::get('ad_type');
        $campaignId = Input::get('campaignid');
        $attachId = Input::get('attach_id');
        $oldAttachId = Input::get('old_attach_id');

        $attachFile = AttachFile::find($attachId);
        if (empty($attachFile)) {
            return $this->errorCode(5200);// @codeCoverageIgnoreEnd
        }
        $reserve = $attachFile->reserve;

        //检查 Banner是否存在，不存在创建一条 banner
        $mode = Affiliate::find($affiliateId)->mode;
        $bannerStatus = (Affiliate::MODE_PROGRAM_DELIVERY_STORAGE == $mode) ?
            Banner::STATUS_PENDING_MEDIA : Banner::STATUS_PENDING_PUT;
        $params['status'] = $bannerStatus;
        $params['storagetype'] = (Campaign::AD_TYPE_APP_MARKET == $ad_type) ? 'app' : 'url';
        $banner = Banner::getBannerOrCreate($campaignId, $affiliateId, $params);

        $affiliate = $banner->affiliate()->first();
        $mode = $affiliate->mode;
        $symbol = $affiliate->symbol;
        //给媒体发送替换包邮件
        MessageService::sendPackageChangeMail($campaignId, $attachId);

        //如果不是程序化投放入库的，则要生成一个 appid
        if (Affiliate::MODE_PROGRAM_DELIVERY_STORAGE != $mode) {
            $this->createAppId($banner, $attachFile, $symbol);
        }
        $banner->attach_file_id = $attachFile->id;
        //把下载链接加入到banner表download_url 更新bannerText
        if ($banner->checkMode([
            Affiliate::MODE_PROGRAM_DELIVERY_STORAGE,
            Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE
        ])
        ) {
            $banner->download_url = CampaignService::attachFileLink($attachId);
            $banner->buildBannerText();
            $banner->save();
        }
        if (!$banner->save()) {
            return $this->errorCode(5201);// @codeCoverageIgnore
        }
        $attach = AttachFile::where('campaignid', $campaignId)
            ->where('flag', AttachFile::FLAG_NOT_USED)
            ->where('id', $attachId)
            ->select('id')
            ->first();
        if (!empty($attach->id)) {
            AttachFile::processPackage($campaignId, $attachId, AttachFile::FLAG_USING);// @codeCoverageIgnore
        }// @codeCoverageIgnore
        // 查询是否有使用旧包,没有使用旧包就设置旧包位未使用
        $attach_id = DB::table('attach_files')
            ->leftJoin('banners', 'banners.attach_file_id', '=', 'attach_files.id')
            ->where('id', $oldAttachId)
            ->where('banners.attach_file_id', '>', 0)
            ->pluck('id');
        if (!$attach_id) {
            // @codeCoverageIgnoreStart
            AttachFile::where('id', $oldAttachId)
                ->update(array('flag' => AttachFile::FLAG_NOT_USED, 'updated_at' => date('Y-m-d H:i:s')));
            // @codeCoverageIgnoreEnd
        }

        $key = $banner->campaign->equivalence;
        if ($key) {
            CampaignService::attachEquivalencePackageName($key);
        }

        return $this->success();
    }

    /**
     * @param $banner
     * @param $pack
     * @param $symbol
     * @codeCoverageIgnore
     */
    public function createAppId($banner, $pack, $symbol)
    {
        $app_id = $banner->app_id;
        if (empty($app_id)) {
            if ($symbol == 'uucun') {
                $app = DB::table('campaigns as c')
                    ->leftJoin('appinfos as a', function ($join) {
                        $join->on('c.campaignname', '=', 'a.app_id');
                        $join->on('c.platform', '=', 'a.platform');
                    })
                    ->where('c.campaignid', $banner->campaignid)
                    ->first();
                if (!empty($app->app_id) && !empty($app->app_name) && !empty($pack->package_name)) {
                    $dataArr = Config::get(strtolower($symbol) . '.helper')->getValue(
                        [
                            'bid' => urlencode($app->app_id),
                            'nm' => urlencode($app->app_name),
                            'pkgnm' => urlencode($pack->package_name)
                        ]
                    );
                    if (1 == $dataArr['st']) {
                        $banner->app_id = $dataArr['id'];
                        $banner->save();
                    }
                }
            } else {
                if ($banner->affiliate()->first()->type == 2) {
                    $banner->app_id = date('Hi4') . str_random(8);
                    $banner->save();
                }
            }
        }
    }

    /**
     * 统计有更新渠道包数量
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | count |  | integer  | 代更新包数量 |  | 是 |
     */
    public function packageNotLatest()
    {
        $product_type = Config::get('biddingos.withoutPackage');
        $ad_type = Campaign::AD_TYPE_OTHER;
        $delivering = Campaign::STATUS_DELIVERING;
        $flag = AttachFile::FLAG_USING;
        $suspended = Campaign::STATUS_SUSPENDED;
        $pause_status_platform = Campaign::PAUSE_STATUS_PLATFORM;
        $agencyId = Auth::user()->agencyid;
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        //广告未审核c.status != 10 审核不通过c.status != 11 、平台暂停不显示 c.pause_status != 0
        $sql ="SELECT
                    COUNT(1) AS count
                FROM up_campaigns
                LEFT JOIN up_appinfos ON up_campaigns.campaignname = up_appinfos.app_id
                LEFT JOIN up_clients ON up_clients.clientid = up_campaigns.clientid
                LEFT JOIN up_products ON up_products.id = up_campaigns.product_id
                LEFT JOIN(
                    SELECT
                        a.campaignid,
                        b.market_version,
                        attach_file_version_compare(b.market_version_code, a.version_code) as compare_version
                    FROM up_attach_files a
                    LEFT JOIN up_attach_files b ON a.package_name = b.package_name
                    WHERE a.flag = $flag and a.package_name !='' and b.market_version_code != a.version_code
                    and attach_file_version_compare(b.market_version_code, a.version_code) > 0
                    GROUP BY a.campaignid
                ) AS att ON att.campaignid = up_campaigns.campaignid
                WHERE up_products.type NOT IN (".implode(',', $product_type).")
                AND up_appinfos.media_id = $agencyId
                AND up_campaigns.ad_type <> $ad_type
                AND up_campaigns.status = $delivering
                AND up_clients.affiliateid = 0
                AND att.compare_version = 1
                AND (up_campaigns.status = $delivering
                OR (up_campaigns.status = $suspended AND up_campaigns.pause_status <> $pause_status_platform ))";
        $res = DB::Select($sql);
        if (!$res) {
            LogHelper::warning('user failed to load data');// @codeCoverageIgnore
            return $this->errorCode(5001); // @codeCoverageIgnore
        }
        return $this->success($res[0], null, null);
    }


    /**
     * 素材审核数和广告审核数
     * @return \Illuminate\Http\Response
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | camcnt |  | integer | 待审核广告数量 |  | 是 |
     * | meters_count |  | integer | 待审核素材数量 |  | 是 |
     *
     */
    public function campaignPendingAudit()
    {
        //待审核广告需要排除自营广告
        $materialCount = 0;
        $select = Campaign::where('status', Campaign::STATUS_PENDING_APPROVAL)
            ->join('appinfos', function ($join) {
                $join->on('appinfos.app_id', '=', 'campaigns.campaignname')
                    ->on('appinfos.platform', '=', 'campaigns.platform');
            })
            ->join('clients', 'campaigns.clientid', '=', 'clients.clientid')
            ->where('appinfos.media_id', Auth::user()->agencyid);

        if (Auth::user()->account->isManager()) {
            if (Auth::user()->user_id == Auth::user()->account->manager_userid) {
                $materialCount = AppInfo::where('materials_status', AppInfo::MATERIAL_STATUS_PENDING_APPROVAL)
                    ->where('media_id', Auth::user()->agencyid)
                    ->count();
            } else {
                $materialCount = AppInfo::where('materials_status', AppInfo::MATERIAL_STATUS_PENDING_APPROVAL)
                    ->where('media_id', Auth::user()->agencyid)
                    ->join('campaigns', function ($join) {
                        $join->on('campaigns.campaignname', '=', 'appinfos.app_id')
                            ->on('campaigns.platform', '=', 'appinfos.platform');
                    })
                    ->leftJoin('clients', function ($join) {
                        $join->on('clients.clientid', '=', 'campaigns.clientid')
                            ->where('clients.creator_uid', '=', Auth::user()->user_id);
                    })
                    ->count();
            }

            //平台联盟待审核广告数量
            $select->where('clients.affiliateid', 0);
        } elseif (Auth::user()->account->isTrafficker()) {
            //自营待审核广告数量
            $select->where(
                'clients.affiliateid',
                Auth::user()->account->affiliate->affiliateid
            );
        }

        $campaignCount = $select->count();

        return $this->success([
            'camcnt' => $campaignCount,
            'meters_count' => $materialCount,
        ]);
    }

    /**
     * 获取备忘录列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | category |  | string | 分类 |  | 是 |
     * | target_id |  | integer | 目标ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | string | 消息ID |  | 是 |
     * | message |  | string | 日志消息 |  | 是 |
     * | operator |  | string | 操作人 |  | 是 |
     * | type |  | integer | 类型 |  | 是 |
     * | created_time |  | datetime | 创建时间 |  | 是 |
     */
    public function logIndex(Request $request)
    {
        if (($ret = $this->validate($request, [
                'category' => 'required|integer',
                'target_id' => 'required|integer',
            ], [], OperationLog::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $category = $request->input('category');
        $targetId = $request->input('target_id');
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = $request->input('search');
        $sort = $request->input('sort');
        $filter = json_decode($request->input('filter'), true);

        $rows = $this->getLogList($category, $targetId, $pageNo, $pageSize, $search, $sort, $filter);
        return $this->success(null, $rows['map'], $rows['list']);
    }

    /**
     * 获取日志列表
     * @param integer $category
     * @param integer $targetId
     * @param int $pageNo
     * @param int $pageSize
     * @param null $search
     * @param null $sort
     * @param string $filter
     * @return array
     */
    private function getLogList(
        $category,
        $targetId,
        $pageNo = 1,
        $pageSize = 10,
        $search = null,
        $sort = null,
        $filter = null
    ) {
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $select = DB::table('operation_log')
            ->select(
                'id',
                'type',
                'operator',
                'message',
                'created_time'
            )
            ->where('category', $category)
            ->where('target_id', $targetId);

        // 搜索
        if (!is_null($search) && trim($search)) {
            $select->where(function ($select) use ($search) {
                $select->where('operator', 'like', '%' . $search . '%');
                $select->where('message', 'like', '%' . $search . '%');
            });
        }

        // 筛选
        if (!is_null($filter) && !empty($filter)) {
            foreach ($filter as $k => $v) {
                if (!StringHelper::isEmpty($v)) {
                    if ($k == 'type' && $v == 2) {
                        $select->where('type', '=', OperationLog::TYPE_REMARK);
                    } else {
                        $select->where($k, $v);
                    }
                }
            }
        }

        // 分页
        $total = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        //排序
        if ($sort) {
            $sortType = 'asc';
            if (strncmp($sort, '-', 1) === 0) {
                $sortType = 'desc';
            }
            $sortAttr = str_replace('-', '', $sort);
            $select->orderBy($sortAttr, $sortType);
        } else {
            $select->orderBy('created_time', 'desc');
        }

        $rows = $select->get();
        $list = [];
        foreach ($rows as $row) {
            $list[] = $row;
        }
        return [
            'map' => [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => $total,
            ],
            'list' => $list,
        ];
    }

    /**
     * 存储日志
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | category |  | string | 分类 |  | 是 |
     * | target_id |  | integer | 目标ID |  | 是 |
     * | message |  | string | 日志信息 |  | 是 |
     * @param Request $request
     * @return Response
     */
    public function logStore(Request $request)
    {
        if (($ret = $this->validate($request, [
                'category' => "required|integer",
                'target_id' => "required|integer",
                'message' => 'required|min:2|max:1024',
            ], [], OperationLog::attributeLabels()))!== true) {
            return $this->errorCode(5000, $ret);
        }

        $result = OperationLog::store([
            'category' => $request->input('category'),
            'type' => OperationLog::TYPE_REMARK,
            'target_id' => $request->input('target_id'),
            'message' => $request->input('message'),
        ]);
        if (!$result) {
            return $this->errorCode(5001);
        }

        return $this->success();
    }
    
    /**
     * 根据账户类型，返回所有符合要求的用户
     * @param array $accountType
     * @return array $obj
     */
    private function getUserList($accountType)
    {
        if ($this->can('manager-account')) {
            $result = AccountService::getAccountList($accountType);
            $obj = [];
            if (!empty($result)) {
                $obj = ArrayHelper::map($result, 'user_id', 'contact_name');
            }
        } else {
            $obj = [Auth::user()->user_id => Auth::user()->contact_name];// @codeCoverageIgnore
        }
        
        return $obj;
    }
}
