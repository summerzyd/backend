<?php
namespace App\Http\Controllers\Manager;

use App\Models\Affiliate;
use App\Models\ExpenseLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Http\Controllers\Controller;
use App\Models\OperationDetail;
use App\Models\OperationClient;
use App\Models\Campaign;
use App\Models\DeliveryLog;
use App\Models\ManualDeliveryData;
use App\Models\Client;
use App\Components\Config;

class AuditController extends Controller
{
    
    /**
     * 设置该控制器内的时间为中国时间
     */
    private $agencyId;


    public function __construct()
    {
        parent::__construct();
        $this->agencyId = Auth::user()->agencyid;
        date_default_timezone_set('PRC');
    }

    /**
     * 媒体商数据审计
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  |  |  | -status降序，降序在字段前加- | 否 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | clients | decimal | 实时 广告主消耗 |  | 是 |
     * | traffickers | decimal | 实时 媒体分成 |  | 是 |
     * | partners | decimal | 实时 联盟收益 |  | 是 |
     * | manual_clients | decimal | 人工 广告主消耗 |  | 是 |
     * | manual_traffickers | decimal | 人工 媒体分成 |  | 是 |
     * | manual_partners | decimal | 人工 联盟收益 |  | 是 |
     * | audit_clients | decimal | 程序化 广告主消耗 |  | 是 |
     * | audit_traffickers | decimal | 程序化 媒体分成 |  | 是 |
     * | audit_partners | decimal | 程序化 联盟收益 |  | 是 |
     * | clients_total | decimal | 实时 广告主消耗汇总 | obj字段 | 是 |
     * | traffickers_total | decimal | 实时 媒体分成汇总 | obj字段 | 是 |
     * | partners_total | decimal | 实时 联盟收益汇总 | obj字段 | 是 |
     * | manual_clients_total | decimal | 人工 广告主消耗汇总 | obj字段 | 是 |
     * | manual_traffickers_total | decimal | 人工 媒体分成汇总 | obj字段 | 是 |
     * | manual_partners_total | decimal | 人工 联盟收益汇总 | obj字段 | 是 |
     * | audit_traffickers_total | decimal | 程序化 媒体分成汇总 | obj字段 | 是 |
     * | audit_partners_total | decimal | 程序化 联盟收益汇总 | obj字段 | 是 |
     * | id | integer | 自增 |  | 是 |
     * | status | integer | 0-待审计、1-待审核|  | 是|
     * |  |  | 2-驳回、6-审核通过（待生成审计报表数据）|  | |
     * |  |  | 7-审核通过（生成审计收入报表数据 且 待生成媒体结算数据）|  |  |
     * |  |  | 8-审核通过（结算数据生成完成）|  |  |
     * | day_time | string | 日期 |  | 是 |
     */
    public function traffickerIndex(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'pageNo' => 'numeric',
            'pageSize' => 'numeric'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $sort = $request->input('sort');
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        
        $sql = OperationDetail::where('day_time', '<>', date('Y-m-d'))
                               ->where('agencyid', $this->agencyId);
        
        if ($sort) {
            $sortType = 'asc';
            if (strncmp($sort, '-', 1) === 0) {
                $sortType = 'desc';
            }
            $sortAttr = str_replace('-', '', $sort);
            $sql->orderBy($sortAttr, $sortType);
        } else {
            $sql->orderBy('day_time', 'desc');
        }
        
        $total = $sql->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $data = $sql->skip($offset)
            ->take($pageSize)
            ->get();
        
        $list = [];
        foreach ($data as $row) {
            $list[] = $row->toArray();
        }
        
        $obj = OperationDetail::where('day_time', '<>', date('Y-m-d'))
            ->select(
                DB::raw('SUM(clients) AS clients_total'),
                DB::raw('SUM(traffickers) AS traffickers_total'),
                DB::raw('SUM(partners) AS partners_total'),
                DB::raw('SUM(manual_clients) AS manual_clients_total'),
                DB::raw('SUM(manual_traffickers) AS manual_traffickers_total'),
                DB::raw('SUM(manual_partners) AS manual_partners_total'),
                DB::raw('SUM(audit_traffickers) AS audit_traffickers_total'),
                DB::raw('SUM(audit_partners) AS audit_partners_total')
            )
            ->where('agencyid', $this->agencyId)
            ->whereIn('status', [
                OperationDetail::STATUS_ACCEPT_PENDING_REPORT,
                OperationDetail::STATUS_ACCEPT_REPORT_DONE,
                OperationDetail::STATUS_ACCEPT_DONE
            ])
            ->first();

        return $this->success($obj->toArray(), [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $total
        ], $list);
    }

    // @codeCoverageIgnoreStart
    // 导入、导出函数跳过单元测试
    /**
     *  媒体商导出明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | date | string | 日期 | 2016-05-05  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function traffickerExport(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
                'date' => 'required|date'
            ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $date = $request->input('date');
        $startTime = date('Y-m-d H:i:s', strtotime($date . ' -8 hour'));
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +1 days'));
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        set_time_limit(0);

        $res = ExpenseLog::leftJoin('campaigns', 'expense_log.campaignid', '=', 'campaigns.campaignid')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->leftJoin('appinfos', 'appinfos.app_id', '=', 'campaigns.campaignname')
            ->leftJoin('zones', 'expense_log.zoneid', '=', 'zones.zoneid')
            ->leftJoin('affiliates', 'zones.affiliateid', '=', 'affiliates.affiliateid')
            ->where('actiontime', '>=', $startTime)
            ->where('actiontime', '<', $endTime)
            ->where('source', '<>', 1)
            ->where('clients.agencyid', $this->agencyId)
            ->where('clients.affiliateid', $defaultAffiliateid)
            ->where('expense_log.zoneid', '>', 0)
            ->get([
                'expense_log.expenseid',
                'expense_log.actiontime',
                'affiliates.name',
                'zones.zonename',
                'clients.clientname',
                'appinfos.app_name',
                'expense_log.price',
                'expense_log.af_income',
                'expense_log.target_type',
                'expense_log.target_cat',
                'expense_log.target_id',
                'expense_log.status'
            ]);
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . iconv('utf-8', 'gbk', '投放明细') . "-{$date}.csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $cols = [
            '投放id',
            '投放时间',
            '媒体商',
            '广告位',
            '广告主',
            '广告',
            '广告主出价',
            '媒体商分成价',
            '设备类型',
            '设备来源',
            '设备id',
            '有效标识(默认0，1表示无效数据)'
        ];
        $data_str = iconv(
            'utf-8',
            'gbk',
            implode(',', $cols)
        );
        foreach ($res as $row) {
            $data = array();
            $data[] = $row->expenseid;
            $data[] = $H = date('Y-m-d H:i:s', strtotime('+8 hour', strtotime($row->actiontime)));
            $data[] = iconv('utf-8', 'gbk', $row->name);
            $data[] = iconv('utf-8', 'gbk', $row->zonename);
            $data[] = iconv('utf-8', 'gbk', $row->clientname);
            $data[] = iconv('utf-8', 'gbk', $row->app_name);
            $data[] = number_format($row->price, 2);
            $data[] = number_format($row->af_income, 2);
            $data[] = $row->target_type;
            $data[] = $row->target_cat;
            $data[] = "'" . str_replace(",", "-", $row->target_id);
            $data[] = $row->status;
            $data_str .= "\n";
            $data_str .= implode(",", $data);
            $data_str = rtrim($data_str, ',');
        }
        echo $data_str;
        exit();
    }

    /**
     * 媒体商导入明细
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | file | file | 导入excel文件 |  | 是 |
     * | date | string | 日期 | 2016-05-05  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function traffickerImport(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'date' => 'required|date',
            'file' => 'required'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        set_time_limit(0);
        $date = $request->input('date');
        $startTime = date('Y-m-d H:i:s', strtotime($date . ' -8 hour'));
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +1 days'));
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        $excel = $request->file('file');
        $path = $excel->getRealPath();
        $content = file_get_contents($path);
        $arr = explode("\n", $content);
        if (count($arr) > 1) {
            $element = explode(',', $arr[1]);
            $import_date = date('Y-m-d', strtotime($element[1]));
            if ($date != $import_date) {
                return $this->errorCode(5220);
            }
            try {
                DB::beginTransaction();
                for ($i = 1; $i < count($arr); $i ++) {
                    $element = explode(',', $arr[$i]);
                    if (count($element) < 12) {
                        continue;
                    }
                    
                    //输入的投放ID是否属于当前操作平台的，如果不属于，则不能修改
                    $checkResult = $this->checkAgency($element[0]);
                    if (false == $checkResult) {
                        continue;
                    }
                    
                    $toStatus = intval($element[11]);
                    $toStatus = (1 < $toStatus) ? 1 : $toStatus;
                    ExpenseLog::where('expenseid', '=', $element[0])
                        ->update([
                            'status' => $toStatus
                        ]);
                }
                DB::commit();
                
            } catch (Exception $e) {
                DB::rollBack();
                return $this->errorCode(5221);
            }
        }
        
        $updateTime = $endTime -1;
        $this->process($defaultAffiliateid, $startTime, $updateTime, $date);
        return $this->success();
    }
    // @codeCoverageIgnoreEnd

    /**
     * 媒体商审计更新
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 记录ID |  | 是 |
     * | field | string | 字段 | status  | 是 |
     * | value | integer | 值 | 1提交 6审核通过 2驳回 | |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function traffickerUpdate(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'id' => 'required|numeric',
            'field' => 'required',
            'value' => 'required|numeric|in:1,2,6'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $id = $request->input('id');
        // $field = $request->input('field');
        $value = $request->input('value');
        
        OperationDetail::where('id', $id)
        ->where('agencyid', $this->agencyId)
        ->update([
            "status" => $value
        ]);
        return $this->success();
    }

    /**
     *广告主数据审计
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序| 否 |
     * |  |  |  |  -status降序，降序在字段前加- | 否 |
     * | start | date | 开始时间 |  | 否 |
     * | end | date | 结束时间 |  | 否 |
     * | status | integer | 筛选状态 |  | 否 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | id | integer | 自增 |  | 是 |
     * | date | string | 日期 |  | 是 |
     * | app_name | string | 广告名 |  | 是 |
     * | campaignid | decimal | 推广计划ID |  | 是 |
     * | check_date | date | 生效日期 |  | 是 |
     * | clientname | string | 广告主名称 |  | 是 |
     * | icon | string | 产品图标 |  | 是 |
     * | products_name | string | 产品名称 |  | 是 |
     * | products_type | decimal | 产品类型 |  | 是 |
     * | type | decimal | 类型 |  | 是 |
     * | status | integer | 0-未更新、1-待审核、2-已通过，生效、3-未通过 |  |  |
     */
    public function advertiserIndex(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'pageNo' => 'numeric',
            'pageSize' => 'numeric',
            'status' => 'numeric'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $search = $request->input('search');
        $sort = $request->input('sort');
        $start = $request->input('start');
        $end = $request->input('end');
        $status = $request->input('status');
        $platform = $request->input('platform');
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        $revenueType = $request->input('revenueType', [Campaign::REVENUE_TYPE_CPC, Campaign::REVENUE_TYPE_CPD]);
        if (!is_array($revenueType)) {
            $revenueType = json_decode($revenueType, true);
        }

        $prefix = DB::getTablePrefix();
        $sql = DB::table('operation_clients')
            /*
            ->whereExists(function ($query) use ($prefix) {
                $query  ->select(DB::raw(1))
                        ->from('data_hourly_daily')
                        ->whereRaw(
                            "{$prefix}data_hourly_daily.campaign_id = {$prefix}operation_clients.campaign_id"
                        )
                        ->whereRaw(
                            "{$prefix}data_hourly_daily.date = {$prefix}operation_clients.date"
                        )
                        ->whereRaw(
                            "({$prefix}data_hourly_daily.impressions > 0
                             OR {$prefix}data_hourly_daily.total_revenue > 0
                             OR {$prefix}data_hourly_daily.clicks > 0
                             OR {$prefix}data_hourly_daily.conversions > 0
                             OR {$prefix}data_hourly_daily.af_income > 0)
                            "
                        );
            })*/
            ->leftJoin('campaigns', 'operation_clients.campaign_id', '=', 'campaigns.campaignid')
            ->leftJoin('appinfos', 'appinfos.app_id', '=', 'campaigns.campaignname')
            ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
            ->leftJoin('products', 'products.id', '=', 'campaigns.product_id')
            ->join('data_hourly_daily', function ($query) {
                $query->on('data_hourly_daily.campaign_id', '=', 'operation_clients.campaign_id')
                      ->on('data_hourly_daily.date', '=', 'operation_clients.date');
            })
             ->whereRaw(
                 "({$prefix}data_hourly_daily.impressions > 0
                 OR {$prefix}data_hourly_daily.total_revenue > 0
                 OR {$prefix}data_hourly_daily.clicks > 0
                 OR {$prefix}data_hourly_daily.conversions > 0
                 OR {$prefix}data_hourly_daily.af_income > 0)
                 "
             )
            ->whereIn('campaigns.revenue_type', $revenueType)
            ->where('clients.agencyid', $this->agencyId)
            ->where('clients.affiliateid', $defaultAffiliateid)
            ->select(
                'operation_clients.id',
                'operation_clients.date',
                'clients.clientname',
                'products.icon',
                'campaigns.campaignid',
                'appinfos.app_name',
                'products.name as products_name',
                'products.type as products_type',
                'operation_clients.status',
                'operation_clients.check_date',
                'operation_clients.type'
            );
        
        if ($start) {
            $sql->where('operation_clients.date', '>=', $start);
        }
        if ($end) {
            $sql->where('operation_clients.date', '<=', $end);
        }
        if ($platform) {
            //$sql->whereRaw("({$prefix}campaigns.platform & {$platform}) > 0");
            $sql->where('campaigns.platform', '=', $platform);
        }
        // 增加状态筛选
        if ($status !== '') {
            $sql->where('operation_clients.status', $status);
        }
        // ===================搜索==========================
        if ($search) {
            $sql->where(function ($where) use ($search) {
                $where->where('clients.clientname', 'like', "%{$search}%")
                    ->orWhere('appinfos.app_name', 'like', "%{$search}%");
            });
        }
        
        // ====================排序========================
        $sql->groupBy(['data_hourly_daily.campaign_id'], ['data_hourly_daily.date']);
        if ($sort) {
            $sortType = 'asc';
            if (strncmp($sort, '-', 1) === 0) {
                $sortType = 'desc';
            }
            $sortAttr = str_replace('-', '', $sort);
            $sql->orderBy('operation_clients.' . $sortAttr, $sortType);
        } else {
            $sql->orderBy('operation_clients.id', 'desc');
        }
       // echo $sql->toSql(); exit;
        //$total = $sql->count();
        $total = count($sql->get());
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $data = $sql->skip($offset)
            ->take($pageSize)
            ->get();
        $list = [];
        foreach ($data as $row) {
            $list[] = $row;
        }
        
        return $this->success(null, [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $total
        ], $list);
    }

    /**
     * 广告主审计更新
     *
     * | name | type | description | restraint | required
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | campaignid | integer | 推广计划ID |  | 是 |
     * | field | string | 字段 | status | 是 |
     * | value | integer | 值 | status：0-未更新、1-待审核、2-已通过，生效、3-未通过 | |
     * | date | integer | 日期 | 2016/5/5 | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function advertiserUpdate(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'campaignid' => 'required|numeric',
            'field' => 'required|in:status',
            'value' => 'numeric',
            'date' => 'required'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = $request->input('campaignid');
        $value = intval($request->input('value'));
        $date = $request->input('date');
        $result = $this->checkDeliveryData([$date => [$campaignId]]);
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        if (true == $result) {
            return $this->errorCode(5312);
        }
        
        //检查输入的 campaignid是否与 agencyid匹配
        $check = OperationClient::leftJoin('campaigns', 'operation_clients.campaign_id', '=', 'campaigns.campaignid')
                ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
                ->where('operation_clients.date', $date)
                ->where('clients.agencyid', $this->agencyId)
                ->where('clients.affiliateid', $defaultAffiliateid)
                ->select('id')
                ->first();
        if (!$check) {
            return $this->errorCode(4001);
        }
        
        $sql = OperationClient::where("campaign_id", $campaignId)->where("date", $date);
        switch ($value) {
            case OperationClient::STATUS_PENDING_ACCEPT:
                if (!OperationDetail::isAudit($date)) {
                    return $this->errorCode(5310);
                }
                
                if (!ManualDeliveryData::checkManualCampanginStatus($campaignId, $date)) {
                    $info = $this->formatWaring(5255, $date);
                    return $this->errorCode(1, $info);
                }
                
                $sql->update([
                    "status" => OperationClient::STATUS_PENDING_ACCEPT
                ]);
                break;
            case OperationClient::STATUS_ACCEPT:
                $sql->update([
                    "status" => OperationClient::STATUS_ACCEPT,
                    "issue" => OperationClient::ISSUE_APPROVAL,
                    "check_date" => date("Y-m-d H:i:s")
                ]);
                break;
            case OperationClient::STATUS_REJECTED:
                $sql->update([
                    "status" => OperationClient::STATUS_REJECTED,
                    "issue" => OperationClient::ISSUE_NOT_APPROVAL
                ]);
                break;
        }
        return $this->success();
    }

    /**
     * 批量更新
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | data | json数组 |  | [{"campaignid":1,"field":"status","value":2,"date":"2016-05-05"} | |
     * |  |  |  | {"campaignid":1,"field":"status","value":2,"date":"2016-05-05"}] | |
     * | campaignid | integer | 推广计划ID |  | 是 |
     * | field | string | 字段 | status | 是 |
     * | value | integer | 值 | status：2-已通过，生效 | |
     * | date | integer | 日期 | 2016/5/5 | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function advertiserUpdateBatch(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'data' => 'required'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        
        $data = $request->input('data');
        $arrData = json_decode($data);
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        try {
            //检查是否还有未分发的广告
            $campaigns = [];
            foreach ($arrData as $k => $v) {
                $campaigns[$v->date][] = $v->campaignid;
            }
            $result = $this->checkDeliveryData($campaigns);
            if (true == $result) {
                return $this->errorCode(5311);
            }

            foreach ($arrData as $d) {
                if (!OperationDetail::isAudit($d->date)) {
                    return $this->errorCode(5310);
                }
                
                //检查输入的 campaignid是否与 agencyid匹配
                $check = OperationClient::leftJoin(
                    'campaigns',
                    'operation_clients.campaign_id',
                    '=',
                    'campaigns.campaignid'
                )
                        ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
                        ->where("campaign_id", $d->campaignid)
                        ->where('operation_clients.date', $d->date)
                        ->where('clients.agencyid', $this->agencyId)
                        ->where('clients.affiliateid', $defaultAffiliateid)
                        ->select('id')
                        ->first();
                if (!$check) {
                    return $this->errorCode(4001);
                }
                
                OperationClient::where("campaign_id", $d->campaignid)
                    ->where("date", $d->date)
                    ->update([
                        "status" => $d->value,
                        "issue" => OperationClient::ISSUE_APPROVAL,
                        "check_date" => date("Y-m-d H:i:s")
                    ]);
            }
            
            return $this->success();
        } catch (Exception $e) {
            return $this->errorCode(5001);
        }
    }

    /**
     * 查看广告主投放数据
     *
     * | name | type | description | restraint | required |
     * | :--: | :------: | :--: | :--------: | :-------: |
     * | pageNo | integer | 请求页数，默认1 |  | 否 |
     * | pageSize | integer | 请求每页数量，默认25 |  | 否 |
     * | search | string | 搜索关键字，默认空 |  | 否 |
     * | sort | string | 排序字段，后台默认为创建时间 | status 升序 | 否 |
     * |  |  | | -status降序，降序在字段前加- | 否 |
     * | campaignid | integer | 推广计划ID |  | 是 |
     * | date | date | 日 * 期 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function advertiserDelivery(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'pageNo' => 'numeric',
            'pageSize' => 'numeric',
            'campaignid' => 'required',
            'date' => 'required'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $search = $request->input('search');
        $sort = $request->input('sort');
        $campaignid = $request->input('campaignid');
        $date = $request->input('date');
        $startTime = date('Y-m-d H:i:s', strtotime($date . ' -8 hour'));
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +1 days'));
        
        $sql = Campaign::join('banners', 'campaigns.campaignid', '=', 'banners.campaignid')
            ->join('data_summary_ad_hourly', 'data_summary_ad_hourly.ad_id', '=', 'banners.bannerid')
            ->join('affiliates', 'affiliates.affiliateid', '=', 'banners.affiliateid')
            ->select(
                'affiliates.name',
                'affiliates.affiliateid',
                'campaigns.platform',
                'campaigns.campaignid',
                'campaigns.ad_type',
                'affiliates.mode',
                DB::raw('IFNULL(cast(SUM(up_data_summary_ad_hourly.impressions) as decimal(10,2)),0) as impressions'),
                DB::raw('IFNULL(cast(SUM(up_data_summary_ad_hourly.conversions) as decimal(10,2)),0) as conversions'),
                DB::raw('IFNULL(cast(SUM(up_data_summary_ad_hourly.af_income) as decimal(10,2)),0) as af_income'),
                DB::raw('IFNULL(cast(SUM(up_data_summary_ad_hourly.cpa) as decimal(10,2)),0) as cpa'),
                DB::raw(
                    'IFNULL(cast(SUM(up_data_summary_ad_hourly.total_revenue) as decimal(10,2)),0) as total_revenue'
                )
            )
            ->where('campaigns.campaignid', '=', $campaignid)
            ->where('data_summary_ad_hourly.date_time', '>=', $startTime)
            ->where('data_summary_ad_hourly.date_time', '<', $endTime)
            ->where('affiliates.agencyid', $this->agencyId)
            ->groupBy('affiliates.affiliateid')
            ->groupBy('campaigns.platform')
            ->groupBy('affiliates.mode');
        
        // ===================搜索==========================
        if ($search) {
            $sql->where('affiliates.name', 'like', "%{$search}%");
        }
        
        // ====================排序========================
        if ($sort) {
            $sortType = 'asc';
            if (strncmp($sort, '-', 1) === 0) {
                $sortType = 'desc';
            }
            $sortAttr = str_replace('-', '', $sort);
            $sql->orderBy('affiliates.' . $sortAttr, $sortType);
        } else {
            $sql->orderBy('affiliates.name');
        }
        
        $total = $sql->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $data = $sql->skip($offset)
            ->take($pageSize)
            ->get();
        $list = [];
        foreach ($data as $row) {
            if ($row['ad_type'] == Campaign::AD_TYPE_APP_STORE) {
                $row['platform'] = Campaign::PLATFORM_IOS;
            }
            $list[] = $row->toArray();
        }
        
        return $this->success(null, [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $total
        ], $list);
    }
    
    /**
     * 获取需要审计的数据
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function expenseData(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'date' => 'required'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $date = $request->input('date');
        $startTime = gmdate('Y-m-d H:i:s', strtotime($date));
        $endTime = date('Y-m-d H:i:s', strtotime("$startTime +1 day")-1);
        $prefix = DB::getTablePrefix();
        $rows = DB::table('expense_log AS e')
            ->leftJoin('zones AS z', 'e.zoneid', '=', 'z.zoneid')
            ->leftJoin('affiliates AS a', 'a.affiliateid', '=', 'z.affiliateid')
            ->leftJoin('campaigns AS c', 'e.campaignid', '=', 'c.campaignid')
            ->leftJoin('clients AS cli', 'c.clientid', '=', 'cli.clientid')
            ->leftJoin('appinfos AS ap', function ($join) {
                $join->on('c.campaignname', '=', 'ap.app_id')
                    ->on('c.platform', '=', 'ap.platform');
            })
            ->where('a.kind', '<>', Affiliate::KIND_SELF)
            ->where('cli.affiliateid', 0)
            ->where('cli.agencyid', Auth::user()->agencyid)
            ->whereBetween('e.actiontime', [$startTime, $endTime])
            ->select(
                'z.affiliateid',
                'a.name',
                'ap.app_name',
                'e.campaignid',
                'z.zoneid',
                'z.zonename',
                DB::raw("count({$prefix}z.zoneid) AS total")
            )
            ->groupBy(['z.zoneid', 'e.campaignid'])
            ->orderBy('affiliateid')
            ->get();
        return $this->success(null, null, $rows);
    }

    /*
     * 提交通过审核
     */
    public function pass(Request $request)
    {
        if (($ret = $this->validate($request, [
            'date' => 'required'
        ])) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $date = $request->input('date');
        $arr  = $request->input('arr');
        $startTime = gmdate('Y-m-d H:i:s', strtotime($date));
        $endTime = date('Y-m-d H:i:s', strtotime("$startTime +1 day")-1);
        $defaultAffiliateid = Client::DEFAULT_AFFILIATE_ID;
        $arr = !empty($arr) ? json_decode($arr, true) : [];
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                if (0 < $v['num']) {
                    DB::table('expense_log')
                        ->whereBetween('actiontime', [$startTime, $endTime])
                        ->where('campaignid', $v['campaignid'])
                        ->where('zoneid', $v['zoneid'])
                        ->limit($v['num'])
                        ->update(['status' => 1]);
                }
            }
        }
        $this->process($defaultAffiliateid, $startTime, $endTime, $date);
        return $this->success();
        
    }
    
    
    /**
     * 检测是否还有未分发的人工数据
     * @param array $campaigns
     * @return boolean
     */
    private function checkDeliveryData($campaigns)
    {
        foreach ($campaigns as $date => $ids) {
            $row = DB::table('manual_deliverydata')
                    ->where('date', $date)
                    ->whereIn('campaign_id', $ids)
                    ->where(function ($query) {
                        $query->where('revenues', '>', 0)
                            ->orWhere('expense', '>', 0);
                    })
                    ->select('id', 'flag')
                    ->get();
            //如果有数据，碰到有一个flag为0，则返回
            if (!empty($row)) {
                foreach ($row as $k => $v) {
                    //如果存在未分发的数据，则提示
                    if (ManualDeliveryData::FLAG_UNTREATED == $v->flag) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    
    private function checkAgency($deliveryId)
    {
        $count = DB::table('expense_log')
                ->leftJoin('campaigns', 'expense_log.campaignid', '=', 'campaigns.campaignid')
                ->leftJoin('clients', 'clients.clientid', '=', 'campaigns.clientid')
                ->where('expense_log.expenseid', $deliveryId)
                ->where('clients.agencyid', Auth::user()->agencyid)
                ->count();

        return 0 < $count;
    }
    
    /**
     * 格式化提示信息
     * @param $code
     * @param $k
     * @param $i
     * @return string
     */
    private function formatWaring($code, $param = null)
    {
        $msg = Config::get('error');
        if (empty($param)) {
            return sprintf($msg[$code]);
        } else {
            return sprintf($msg[$code], $param);
        }
    }
    
    /**
     * 处理提交之后审计的数据
     */
    private function process($defaultAffiliateid, $startTime, $endTime, $date)
    {
        $clients = 0;
        $partners = 0;
        $affiliates = 0;
        
        //获取媒体商的支出
        $res = ExpenseLog::leftJoin('campaigns', 'expense_log.campaignid', '=', 'campaigns.campaignid')
                ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
                ->where('source', '<>', 1)
                ->where('zoneid', '>', 0)
                ->where('clients.agencyid', $this->agencyId)
                ->where('clients.affiliateid', $defaultAffiliateid)
                ->whereBetween('actiontime', [$startTime, $endTime])
                ->select(
                    DB::raw('sum(af_income) as affiliates')
                )
                ->first();
        if (!empty($res)) {
            $affiliates =  number_format($res->affiliates, 2, '.', '');
        }
        
        //获取广告主的支出
        $query = DeliveryLog::leftJoin('campaigns', 'delivery_log.campaignid', '=', 'campaigns.campaignid')
                ->join('clients', 'clients.clientid', '=', 'campaigns.clientid')
                ->where('source', '<>', 1)
                ->where('zoneid', '>', 0)
                ->where('clients.agencyid', $this->agencyId)
                ->where('clients.affiliateid', $defaultAffiliateid)
                ->whereBetween('actiontime', [$startTime, $endTime])
                ->select(
                    DB::raw('sum(price) as clients')
                )
                ->first();
        if (!empty($query)) {
            $clients = number_format($query->clients, 2, '.', '');
        }
        
        //平台
        $partners = number_format(($clients - $affiliates), 2, '.', '');
        
        OperationDetail::where('day_time', '=', $date)
            ->where('agencyid', $this->agencyId)
            ->update(array(
                'audit_clients' => $clients,
                'audit_traffickers' => $affiliates,
                'audit_partners' => $partners,
                'status' => OperationDetail::STATUS_ACCEPT_PENDING_REPORT
            ));
    }
}
