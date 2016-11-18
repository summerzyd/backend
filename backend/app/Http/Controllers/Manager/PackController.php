<?php
namespace App\Http\Controllers\Manager;

use App\Components\Helper\LogHelper;
use App\Models\Affiliate;
use App\Models\AttachFile;
use App\Models\Banner;
use App\Models\Campaign;
use App\Components\Config;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use App\Components\Helper\ArrayHelper;
use Qiniu\json_decode;

class PackController extends Controller
{
    /**
     * Auth认证，设置为全部要认证，如果不需要认证请在子类中覆盖该函数
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['uploadCallback']]);
        $this->middleware('permission');
    }

    /**
     * 安装包管理列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | pageNo |  | integer | 请求页数 |  | 是 |
     * | pageSize |  | integer | 请求每页数量 |  | 是 |.
     * | search |  | string | 搜索关键字 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer  | 推广计划ID |  | 是 |
     * | clientid |  | integer  | 广告主ID |  | 是 |
     * | clientname |  | string  | 广告主名称 |  | 是 |
     * | ad_type |  | integer  | 广告类型 |  | 是 |
     * | ad_type_label |  | string  | 广告类型标签 |  | 是 |
     * | app_name |  | string  | 应用名称 |  | 是 |
     * | app_show_icon |  | string  | 应用图标 |  | 是 |
     * | compare_version |  | integer  | 比较版本 |  | 是 |
     * | max_version |  | string  | 最新版本 |  | 是 |
     * | package_num |  | integer  | 包名 |  | 是 |
     * | pause_status |  | integer  | 暂停状态 |  | 是 |
     * | platform |  | integer  | 目标平台 |  | 是 |
     * | platform_label |  | string  | 平台名称 |  | 是 |
     * | status |  | integer  | 状态 |  | 是 |
     */
    public function index(Request $request)
    {
        $pageNo = intval($request->input('pageNo')) <= 1 ? 1 : intval($request->input('pageNo'));
        $pageSize = intval($request->input('pageSize')) <= 1 ? DEFAULT_PAGE_SIZE : intval($request->input('pageSize'));
        $search = e($request->input('search'));
        $filter = json_decode($request->input('filter'), true);

        $filterWhere = '';
        if (!empty($filter['business_type'])) {
            $filterWhere = " AND up_campaigns.business_type = {$filter['business_type']}";
        }
        //获取不用传包的产品类型
        
        $product_type = Config::get('biddingos.withoutPackage');
        $ad_type = Campaign::AD_TYPE_OTHER;
        $delivering = Campaign::STATUS_DELIVERING;
        $suspended = Campaign::STATUS_SUSPENDED;
        $pause_status_platform = Campaign::PAUSE_STATUS_PLATFORM;
        $flag = AttachFile::FLAG_USING;
        $agencyId = Auth::user()->agencyid;
        //  广告未审核c.status != 10 审核不通过c.status != 11 、平台暂停不显示 c.pause_status != 0
        $sql ="SELECT
              att.market_version as max_version,
              att.compare_version,
              up_campaigns.campaignid,
              up_campaigns.platform,
              up_clients.clientname,
              up_clients.clientid,
              up_campaigns.ad_type,
              up_products.icon AS app_show_icon,
              up_appinfos.app_name,
              up_campaigns.status,
              up_campaigns.pause_status,
              up_campaigns.business_type
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
                LEFT JOIN up_campaigns c ON c.campaignid=a.campaignid
                LEFT JOIN up_clients cl ON cl.clientid=c.clientid
                WHERE a.flag = {$flag} and a.package_name !=''
                AND cl.agencyid = {$agencyId}
                and b.market_version_code != a.version_code
                and attach_file_version_compare(b.market_version_code, a.version_code) > 0
                GROUP BY a.campaignid
            ) as att ON att.campaignid = up_campaigns.campaignid
            WHERE up_products.type NOT IN (".implode(',', $product_type).")
                {$filterWhere}
                AND up_appinfos.media_id = $agencyId
                AND up_campaigns.ad_type <> $ad_type
                AND up_clients.affiliateid = 0
                AND (up_campaigns.status = $delivering 
                OR (up_campaigns.status = $suspended AND up_campaigns.pause_status <> $pause_status_platform ))";
        //搜索广告主或广告名称
        if (!empty($search)) {
            $sql .= " AND (up_appinfos.app_name like '%{$search}%' OR up_clients.clientname like '%{$search}%') ";
        }

        //默认排序
        $sql .= " ORDER BY compare_version DESC, `status` ASC, up_campaigns.created DESC";
        // 分页
        $total = count(DB::select($sql));
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $sql .= " LIMIT $offset,{$pageSize}";
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $rows = DB::select($sql);

        $list = [];
        foreach ($rows as $val) {
            $item = $val;
            $item['ad_type_label'] = Campaign::getAdTypeLabels($val['ad_type']);
            $item['platform_label'] = Campaign::getPlatformLabels($val['platform']);
            $package_num = AttachFile::where('campaignid', $item['campaignid'])
                ->where('flag', AttachFile::FLAG_USING)
                ->count();
            $item['package_num'] = $package_num;
            $list[] = $item;
        }
        return $this->success(
            null,
            [
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
                'count' => $total,
            ],
            $list
        );
    }

    /**
     * 广告主渠道包列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer  | 推广计划ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | attach_id |  | integer  | 附件ID |  | 是 |
     * | channel |  | string  | 渠道号 |  | 是 |
     * | contact_name |  | string  | 联系人 |  | 是 |
     * | created_at |  | datetime  | 创建时间 |  | 是 |
     * | flag |  | integer  | 包状态 |  | 是 |
     * | flag |  | integer  | 包状态 |  | 是 |
     * | max_version |  | string  | 最新版本 |  | 是 |
     * | package_download_url |  | string  | 包下载地址 |  | 是 |
     * | package_name |  | string  | 包名称 |  | 是 |
     * | real_name |  | string  | 包名 |  | 是 |
     * | status |  | integer  | 状态 |  | 是 |
     * | status_label |  | string  | 状态标签 |  | 是 |
     * | unique |  | string  | 唯一码 |  | 是 |
     * | version |  | string  | 版本号 |  | 是 |
     */
    public function clientPackage(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required|integer',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = Input::get('campaignid');
        $flag = AttachFile::FLAG_NOT_USED . ',' . AttachFile::FLAG_USING . ','
            . AttachFile::FLAG_PENDING_APPROVAL . ',' . AttachFile::FLAG_REJECTED;
        $select = DB::table('attach_files')
            ->leftJoin('users', 'users.user_id', '=', 'attach_files.upload_uid')
            ->leftJoin('campaigns', 'campaigns.campaignid', '=', 'attach_files.campaignid')
            ->leftJoin('appinfos', 'appinfos.app_id', '=', 'campaigns.campaignname')
            ->leftJoin('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->where('attach_files.campaignid', $campaignId)
            ->where('clients.agencyid', Auth::user()->agencyid)
            ->where('flag', '>', 0)
            ->select(
                'attach_files.version',
                'attach_files.package_name',
                'attach_files.channel',
                'attach_files.id as attach_id',
                'users.contact_name',
                'attach_files.created_at',
                'attach_files.flag',
                'attach_files.unique',
                'attach_files.real_name'
            )
            ->orderByRaw(DB::raw("FIELD(flag, $flag)"))
            ->groupBy('attach_files.id');
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $packages = $select->get();
        $packages_version = [];
        foreach ($packages as $item) {
            $packages_version[$item['package_name']] = $item['package_name'];
        }
//        $sql = AttachFile::whereIn('package_name', $packages_version)
//            ->groupBy('package_name')
//            ->select('market_version', DB::raw('max(market_version_code) as max_version'));
        $all_market_version_arr = [];
        // 计算出版本最大的版本号
        foreach ($packages_version as $key => $value) {
            $max_version_code = '';
            $all_version = AttachFile::Where('package_name', $value)
                ->leftJoin('campaigns', 'campaigns.campaignid', '=', 'attach_files.campaignid')
                ->leftJoin('clients', 'clients.clientid', '=', 'campaigns.clientid')
                ->where('clients.agencyid', Auth::user()->agencyid)
                ->groupBy('market_version_code')
                ->select('market_version_code', 'market_version')
                ->get();
            if ($all_version) {
                foreach ($all_version as $code => $code_value) {
                    if (empty($max_version_code)) {
                        $max_version_code = $code_value->market_version_code;
                        $market_version = $code_value->market_version;
                    } else {
                        $split_arr = explode('.', $max_version_code);
                        $split_arr_next = explode('.', $code_value->market_version_code);
                        $now_count = count($split_arr);
                        $next_count = count($split_arr_next);
                        $for_arr = [];
                        if ($next_count > $now_count) {
                            $for_arr = $split_arr_next;
                        } else {
                            $for_arr = $split_arr;
                        }
                        foreach ($for_arr as $for => $for_value) {

                            $now_value = isset($split_arr[$for]) ? intval($split_arr[$for]) : 0;
                            $next_value = isset($split_arr_next[$for]) ? intval($split_arr_next[$for]) : 0;

                            if ($now_value < $next_value) {
                                $max_version_code = $code_value->market_version_code;
                                $market_version = $code_value->market_version;
                                break;
                            } elseif ($now_value > $next_value) {
                                break;
                            }
                        }
                    }
                }
                $all_market_version_arr[] = $market_version;
            }
            //最多不超过三个
            if (count($all_market_version_arr) >= 3) {
                break;
            }
        }
        $all_market_version = implode(',', $all_market_version_arr);
        $host = Config::get('filesystems.f_web');
        foreach ($packages as $key => $item) {
            $packages[$key]['package_download_url'] = $host.'/attach/channel/dl?aid=' . $item['attach_id'];
            $packages[$key]['status'] = $item['flag'];
            $packages[$key]['status_label'] = AttachFile::getFlagLabels($item['flag']);
            $packages[$key]['max_version'] = $all_market_version;
        }
        return $this->success(null, null, $packages);
    }

    /**
     * 获取可投放媒体
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | attach_id |  | integer  | 附件ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | attach_id |  | integer  | 附件ID |  | 是 |
     * | affiliateid |  | integer  | 媒体ID |  | 是 |
     * | ad_type |  | integer  | 广告类型 |  | 是 |
     * | brief_name |  | string  | 媒体简称 |  | 是 |
     * | campaignid |  | integer  | 推广计划ID |  | 是 |
     * | old_attach_id |  | integer  | 旧附件ID |  | 是 |
     * | real_name |  | string  | 包名 |  | 是 |
     * | status |  | integer  | 状态 |  | 是 |
     */
    public function deliveryAffiliate(Request $request)
    {
        if (($ret = $this->validate($request, [
                'attach_id' => 'required|integer',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $attachId = Input::get('attach_id');
        $search = Input::get('search');
        $res = AttachFile::where('id', $attachId)->select('real_name', 'campaignid')->first();
        $obj = Campaign::where('campaignid', $res->campaignid)
            ->select('revenue_type', 'ad_type', 'platform')->first();
        if ($obj->ad_type == Campaign::AD_TYPE_APP_MARKET) {
            $mode = [Affiliate::MODE_PROGRAM_DELIVERY_STORAGE, Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE];
        } else {
            $mode = [Affiliate::MODE_PROGRAM_DELIVERY_NO_STORAGE];
        }
        $kind = Affiliate::KIND_ALLIANCE;
        $select = DB::table('affiliates')
            ->leftJoin('affiliates_extend', 'affiliates.affiliateid', '=', 'affiliates_extend.affiliateid')
            ->join('campaigns', 'campaigns.ad_type', '=', 'affiliates_extend.ad_type')
            ->leftJoin('banners', function ($join) {
                $join->on('banners.affiliateid', '=', 'affiliates.affiliateid')
                    ->on('campaigns.campaignid', '=', 'banners.campaignid');
            })
            ->leftJoin('attach_files', 'attach_files.id', '=', 'banners.attach_file_id')
            ->where('campaigns.ad_type', $obj->ad_type)
            ->where('affiliates.agencyid', Auth::user()->agencyid)
            ->where('campaigns.campaignid', $res->campaignid)
            ->whereIn('affiliates.mode', $mode)
            ->where('affiliates.affiliates_status', Affiliate::STATUS_ENABLE)
            ->whereIN('campaigns.status', [Campaign::STATUS_DELIVERING, Campaign::STATUS_SUSPENDED])
            ->whereRaw(' (' . Affiliate::getTableFullName() . ".kind & {$kind}  > 0)")
            ->whereRaw('(' . Affiliate::getTableFullName() .
                '.app_platform &' . Campaign::getTableFullName() . '.platform > 0)')
            ->select(
                'affiliates.affiliateid',
                'affiliates.brief_name',
                'campaigns.campaignid',
                'campaigns.ad_type',
                DB::raw($attachId . " as attach_id"),
                'attach_files.id as old_attach_id',
                'attach_files.real_name',
                DB::raw('IF (' . Banner::getTableFullName() . '.attach_file_id AND ' .
                    AttachFile::getTableFullName() . '.id > 0 ,
                  IF (' . AttachFile::getTableFullName() . '.id = ' . $attachId . ', 2, 1), 0) AS status')
            )
            ->orderBy('banners.app_rank', 'desc')
            ->groupBy('affiliates.affiliateid');
        if (!is_null($search) && trim($search)) {
            $select->where('affiliates.brief_name', 'like', '%' . $search . '%');
        }
        $list = $select->get();
        return $this->success(null, null, $list);
    }

    /**
     * 上传回调
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer  | 推广计划ID |  | 是 |
     * | clientid |  | integer  | 广告主ID |  | 是 |
     * | channel |  | string  | 渠道号 |  | 是 |
     * | data |  | datetime  | 日期 |  | 是 |
     * @param Request $request
     * @return array
     * @codeCoverageIgnore
     */
    public function uploadCallback(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required|integer',
                'clientid' => 'required|integer',
                'channel' => 'required',
                'data' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return ['success' => false, 'msg' => $ret];
        }

        $channel = $request->input('channel');
        $campaignId = $request->input('campaignid');
        $clientId = $request->input('clientid');
        $data = $request->input('data');
        $userId = $request->input('user_id', 0);
        $fileData = json_decode($data);
        //获取安装包信息
        $reserveData = [];
        $reserveData['filesize'] = $fileData->filesize;
        $reserveData['md5'] = $fileData->md5_file;
        $reserveData['versionName'] = $fileData->versionName;
        $reserveData['packageName'] = $fileData->packageName;
        $reserveData['versionCode'] = $fileData->versionCode;
        $reserveData['app_support_os'] = $fileData->app_support_os;
        if (isset($fileData->app_crc32)) {
            $reserveData['app_crc32'] = $fileData->app_crc32;
        }
        if (isset($fileData->app_sign)) {
            $reserveData['app_sign'] = $fileData->app_sign;
        }
        if (isset($fileData->h8192_md5)) {
            $reserveData['h8192_md5'] = $fileData->h8192_md5;
        }

        $version = isset($reserveData['versionName']) ? $reserveData['versionName'] : '';
        $version_code = isset($reserveData['versionCode']) ? $reserveData['versionCode'] : '';
        $package_name = isset($reserveData['packageName']) ? $reserveData['packageName'] : '';

        $reserve = json_encode($reserveData);
        LogHelper::info(json_encode($request->all()) . $reserve);
        $hash = $fileData->md5_file;
        $unique = date('Hi') . str_random(8);
        //保存附件
        AttachFile::create([
            'channel' => $channel,
            'campaignid' => $campaignId,
            'clientid' => $clientId,
            'upload_uid' => $userId,
            'hash' => $hash,
            'unique' => $unique,
            'file' => $fileData->path,
            'real_name' => $fileData->real_name,
            'version' => $version,
            'version_code' => $version_code,
            'market_version' => $version,
            'market_version_code' => $version_code,
            'package_name' => $package_name,
            'reserve' => $reserve,
            'flag' => AttachFile::FLAG_NOT_USED, // 运营平台上传包默认为 未使用状态
        ]);
        return ['success' => true, 'msg' => '上传成功'];
    }

    /**
     * 修改渠道号
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | attach_id |  | integer  | 附件ID |  | 是 |
     * | field |  | string  | 字段名 |  | 是 |
     * | value |  | string  | 字段值 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (($ret = $this->validate($request, [
                'attach_id' => 'required|integer',
                'field' => 'required',
                'value' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $attach_id = Input::get('attach_id');
        $value = Input::get('value');
        $field = Input::get('field');
        $attach = AttachFile::find($attach_id);
        //修改渠道號
        if ($field == 'channel') {
            $attach->channel = $value;
            $result = $attach->save();
        }
        //修改包的狀態
        if ($field == 'status') {
            if ($value == 1) {
                $result = AttachFile::where('id', $attach_id)->delete();
            } else {
                $result =AttachFile::where('id', $attach_id)
                    ->update(['flag' => $value, 'updated_at' => date("Y-m-d H:i:s")]);
            }
        }

        if ($result) {
            return $this->success();
        } else {
            return $this->errorCode(5003);
        }
    }
}
