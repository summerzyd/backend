<?php
namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Daily;
use App\Models\Operation;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\PromotionActivity;
use App\Components\Helper\UrlHelper;
use App\Models\User;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    /**
     * 设置该控制器内的时间为中国时间
     */
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('PRC');
    }

    /**
     * 获取活动列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | role |  | string | 用户权限 | A：广告主,M： 媒体商 | 是 |
     * | pageNo |  | integer | 请求页数 |  | 是 |
     * | pageSize |  | integer | 请求每页数量 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 自增 |  | 是 |
     * | agencyid |  | integer | 联盟id |  | 是 |
     * | contact_name |  | string | 操作者名字 |  | 是 |
     * | content |  | string | 活动规则说明 |  | 是 |
     * | enddate |  | datetime | 活动结束日期 |  | 是 |
     * | filename |  | string | 活动图片存储文件名 |  | 是 |
     * | imageurl |  | string | 活动图片存储路径 |  | 是 |
     * | operator_accountid |  | integer | 操作人account_id |  | 是 |
     * | operator_userid |  | integer | 操作人user_id |  | 是 |
     * | publishtime |  | datetime | 活动发布时间 |  | 是 |
     * | role |  | integer | 已读 | A：广告主,M： 媒体商 | 是 |
     * | startdate |  | datetime | 活动开始日期 |  | 是 |
     * | status |  | integer | 已读 | 1： 已发布状态,0： 下线状态 | 是 |
     * | title |  | string | 已读 | 活动名称 | 是 |
     */
    public function index(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
                'role' => 'in:A,M',
                'pageNo' => 'numeric',
                'pageSize' => 'numeric'
            ], [], PromotionActivity::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $role = $request->input('role', 'A');
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);

        $ret = PromotionActivity::getActivityList($role, $pageNo, $pageSize);

        // 兼容本地图片和七牛图片展示
        foreach ($ret['data'] as &$row) {
            $row->imageurl = UrlHelper::imageFullUrl($row->imageurl);
        }
        return $this->success(null, [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $ret['total']
        ], $ret['data']);
    }

    /**
     * 获取单个活动
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 活动ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | type | description | restraint | required|
     * | :--:| :--: | :--------: | :-------: | :-----: |
     * |id|integer|自增| |是|
     * |title|string|活动名称| |是|
     * |content|string|活动规则说明| |是|
     * |imageurl|string|活动图片存储路径| |是|
     * |filename|string|活动图片存储文件名 | |是|
     * |startdate|date|活动开始日期| |是|
     * |enddate|date|活动结束日期| |是|
     * |publishtime|datetime|活动发布时间| |是|
     * |operator_accountid|integer|操作人account_id| |是|
     * |operator_userid|integer|操作人user_id| |是|
     * |agencyid|integer|联盟id| |是|
     * | status |integer| 1： 已发布状态,0： 下线状态 | |是|
     * | role |integer|联盟id| A：广告主,M： 媒体商 |是|
     */
    public function get(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
                'id' => 'required|numeric'
            ])) !== true
        ) {
            return $this->errorCode(5000);
        }

        $id = $request->input('id');
        $activity = PromotionActivity::find($id);

        if (!$activity) {
            return $this->errorCode(5210);
        }
        $activity->imageurl = UrlHelper::imageFullUrl($activity->imageurl);
        return $this->success($activity->toArray());
    }

    /**
     * 修改或新建活动
     *
     * | name | type | description | restraint | required|
     * | :--:| :--: | :--------: | :-------: | :-----: |
     * |id|integer|活动id(新建传0)| |是|
     * |title|string|活动名称| |是|
     * |imageurl|string|活动图片存储路径| |是|
     * |startdate|date|活动开始日期| |是|
     * |enddate|date|活动结束日期| |是|
     * |content|string|活动规则说明| |是|
     * |role|string|A：广告主,M： 媒体商| |是|
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
                'id' => 'numeric',
                'title' => 'required|max:30',
                'imageurl' => 'required',
                'startdate' => 'date|required',
                'enddate' => 'date|required',
                'content' => 'required',
                'role' => 'required|in:A,M'
            ], [], PromotionActivity::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $user = Auth::user();

        $id = $request->input('id');
        $title = $request->input('title');
        $content = $request->input('content');
        $startDate = $request->input('startdate');
        $endDate = $request->input('enddate');
        $imageUrl = $request->input('imageurl');
        $role = $request->input('role');
        $operatorUserid = $user->user_id;
        $agency = $user->account->agency;
        $agencyId = $agency->agencyid;
        $operatorAccountId = $user->account->account_id;

        // 根据传入的id判断是新建还是修改
        if ($id > 0) {
            // 修改
            $activity = PromotionActivity::find($id);
            if ($activity->agencyid == $agencyId) {
                $activity->title = $title;
                $activity->content = $content;
                $activity->startdate = $startDate;
                $activity->enddate = $endDate;
                $activity->imageurl = $imageUrl;
                $activity->filename = '';
                if ($activity->save()) {
                    return $this->success();
                } else {
                    return $this->errorCode(5011);
                }
            } else {
                return $this->errorCode(5012);
            }
        } else {
            // 新建
            $activityData = array(
                'agencyid' => $agencyId,
                'title' => $title,
                'content' => $content,
                'imageurl' => $imageUrl,
                'filename' => '',
                'role' => $role,
                'startdate' => $startDate,
                'enddate' => $endDate,
                'publishtime' => date('Y-m-d H:i:s'),
                'operator_accountid' => $operatorAccountId,
                'operator_userid' => $operatorUserid,
                'status' => PromotionActivity::STATUS_PUBLISH
            );
            $activity = PromotionActivity::create($activityData);
            $activityId = $activity->id;
            if (empty($activityId)) {
                return $this->errorCode(5013);
            } else {
                return $this->active($activityId);
            }
        }
    }

    /**
     * 发布和下线处理
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 活动ID |  | 是 |
     * | status |  | integer | 1： 已发布状态,0： 下线状态 |  | 是 |
     * | role |  | integer | A：广告主,M： 媒体商 |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function deal(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
                'id' => 'required|numeric',
                'status' => 'required|numeric',
                'role' => 'required|in:A,M'
            ], [], PromotionActivity::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $id = $request->input('id');
        $status = $request->input('status');
        $role = $request->input('role');


        if ($status == PromotionActivity::STATUS_OFFLINE) {
            PromotionActivity::where('id', '=', $id)->where('role', '=', $role)->update([
                'status' => PromotionActivity::STATUS_OFFLINE,
            ]);
            return $this->success();
        } elseif ($status == PromotionActivity::STATUS_PUBLISH) {
            return $this->active($id);
        } else {
            return $this->errorCode(5214);
        }
    }

    private function active($id)
    {
        $activity = PromotionActivity::find($id);

        if (!$activity) {
            return $this->errorCode(5210);
        }

        $activity->status = PromotionActivity::STATUS_PUBLISH;
        $activity->publishtime = date('Y-m-d H:i:s');
        $activity->save();

        PromotionActivity::where('id', '<>', $id)
            ->where('role', '=', $activity->role)
            ->update([
                'status' => PromotionActivity::STATUS_OFFLINE
            ]);

        $content = [
            'class' => 'activity',
            'body' => $activity->content,
            'from_id' => $activity->id
        ];

        $msgContent = [
            'operator_accountid' => $activity->operator_accountid,
            'operator_userid' => $activity->operator_userid,
            'agencyid' => $activity->agencyid,
            'title' => $activity->title,
            'create_time' => date('Y-m-d H:i:s'),
            'end_time' => $activity->enddate,
            'content' => $content,
            'type' => Message::TYPE_WEB,
            'status' => Message::STATUS_SENT,
            'retry_times' => 0,
            'error_code' => ''
        ];

        MessageService::sendWebMessage($activity->role, $msgContent);
        return $this->success();
    }


    /**
     * 日报信息列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | type |  | integer | 日报类型 | 1.日报,2.周报  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 日报ID |   | 是 |
     * | date |  | datetime | 日报日期 |  | 是 |
     * | status |  | integer | 日报状态 | 1,待发送,2.暂停发送,3.已发送,4.发送失败  | 是 |
     * | send_time |  | datetime | 发送日期 |   | 是 |
     * | receiver |  | string | 邮件发送名称 | | 是 |
     */
    public function reportList(Request $request)
    {
        if (($ret = $this->validate($request, [
                'type' => 'required',
            ], [], Daily::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);//每页条数,默认25
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);//当前页数，默认1
        $type = $request->input('type');
        $agencyId = Auth::user()->agencyid;

        $select = \DB::table('daily')
            ->where('agencyid', $agencyId)
            ->where('type', $type)
            ->select('id', 'date', 'status', 'send_time', 'receiver');
        //总记录数
        $total = $select->count();

        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $select->skip($offset)->take($pageSize);

        $rows = $select->orderBy('date', 'DESC')->get();

        return $this->success(null, [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $total
        ], $rows);
    }

    /**
     * 重新发送日报邮件
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 日报ID |   | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @codeCoverageIgnore
     */
    public function resendMail(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required',
            ], [], Daily::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }

        $id = $request->input('id');

        $result = Daily::where('id', $id)
            ->select('id', 'type')
            ->first();

        if ($result->type == Daily::TYPE_DAILY) {
            //发送邮件
            $ret = MessageService::getDailyReport($id);
            if ($ret !== true) {
                return $this->errorCode($ret);
            }
        } else {
            //周报
            $ret = MessageService::getWeeklyReport($id);
            if ($ret !== true) {
                return $this->errorCode($ret);
            }
        }

        return $this->success();
    }
}
