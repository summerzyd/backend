<?php
namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Message;
use App\Models\PromotionActivity;
use App\Models\User;
use App\Models\Client;
use App\Components\Helper\EmailHelper;
use App\Services\MessageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NoticeController extends Controller
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
     * 获取通知列表
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
     * | id |  | string | 消息ID |  | 是 |
     * | title |  | string | 标题 |  | 是 |
     * | create_time |  | datetime | 添加时间 |  | 是 |
     * | content |  | string | 内容 |  | 是 |
     * | contact_name |  | string | 发布人员 |  | 是 |
     * | total |  | integer | 总数 |  | 是 |
     * | read_cnt |  | integer | 已读 |  | 是 |
     */
    public function index(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'role' => 'in:A,M',
            'pageNo' => 'numeric',
            'pageSize' => 'numeric'
        ], [], PromotionActivity::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $role = $request->input('role', 'A');
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        $agencyId = Auth::user()->agencyid;
        
        $messages = Message::leftJoin('users', 'users.user_id', '=', 'message.operator_userid')
            ->where('message.type', Message::TYPE_WEB)
            ->where('users.agencyid', $agencyId)
            ->select(
                'message.id',
                'message.title',
                'message.create_time',
                'message.content',
                'message.status',
                'users.contact_name',
                DB::raw('COUNT(IF(up_message.`status`=1,1,NULL)) as read_count')
            )
            ->groupBy(
                'message.operator_accountid',
                'message.operator_userid',
                'message.agencyid',
                'message.content',
                'message.type'
            )
            ->orderBy('create_time', 'desc')
            ->get();
        $notes = [
            'A' => [],
            'M' => []
        ];
        
        foreach ($messages as $row) {
            $content = json_decode($row->content);
            
            if ($content->class == 'note') {
                $tmp = [
                    'id' => $row->id,
                    'title' => $row->title,
                    'create_time' => $row->create_time->toDateTimeString(),
                    'content' => $content->body,
                    'status' => $row->status,
                    'contact_name' => $row->contact_name,
                    'total' => $content->total,
                    'read_cnt' => $row->read_count
                ];
                $notes[$content->role][] = $tmp;
            }
        }
        if ($role == 'A' || $role == 'M') {
            return $this->success(null, [
                'count' => count($notes[$role]),
                'pageNo' => $pageNo,
                'pageSize' => $pageSize
            ], array_slice($notes[$role], ($pageNo - 1) * $pageSize, $pageSize));
        } else {
            return $this->errorCode(5214);
        }
    }

    /**
     * 发送站内信
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | title |  | string | 标题 |  | 是 |
     * | content |  | string | 内容 |  | 是 |
     * | role |  | string | A：广告主,M： 媒体商 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
            'title' => 'required',
            'content' => 'required',
            'role' => 'required|in:A,M'
        ], [], PromotionActivity::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $title = $request->input('title');
        $content = $request->input('content');
        $role = $request->input('role');
        
        $user = Auth::user();
        $agency = $user->account->agency;
        $agencyId = $agency->agencyid;
        $operatoAccountId = $user->account->account_id;
        $operatorUserId = $user->user_id;
        
        $msgContent = [
            'operator_accountid' => $operatoAccountId,
            'operator_userid' => $operatorUserId,
            'agencyid' => $agencyId,
            'title' => $title,
            'create_time' => date('Y-m-d H:i:s'),
            'end_time' => '',
            'content' => $content,
            'type' => Message::TYPE_WEB,
            'status' => Message::STATUS_SENT,
            'retry_times' => 0,
            'error_code' => ''
        ];
        
        MessageService::sendWebMessage($role, $msgContent, 'note');
        return $this->success();
    }

    /**
     * 获取email列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer  | emailID |  | 是 |
     * | title |  | string | 标题 |  | 是 |
     * | content |  | string | 内容 |  | 是 |
     * | create_time |  | string | 创建时间 |  | 是 |
     * | status |  | integer  | 状态 | 0：已发送，1：已读 | 是 |
     * |  |  |   |  | 2：未发送，3：发送中，4：发送失败 | 是 |
     * | type |  | string  |  | draft:草稿，sent:已发送 | 是 |
     * | total |  | integer | 发送总数 |  | 是 |
     * | contact_name |  | string | 操作人员 |  | 是 |
     * | clients |  | array |  |  | 是 |
     * |  | user_id | integer  | 广告主id |  | 是 |
     * |  | email_address | string | 广告主邮箱 |  | 是 |
     * |  | clientname | string |  | 广告主名称 | 是 |
     * |  | account_id | integer  | 广告主账户id |  | 是 |
     */
    public function emailIndex(Request $request)
    {
        // 判断输入是否合法
        if (($ret = $this->validate($request, [
            'pageNo' => 'numeric',
            'pageSize' => 'numeric'
        ], [], PromotionActivity::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        
        $pageNo = $request->input('pageNo', DEFAULT_PAGE_NO);
        $pageSize = $request->input('pageSize', DEFAULT_PAGE_SIZE);
        
        $user = Auth::user();
        

        $agency = $user->account->agency;
        $agencyId = $agency->agencyid;
        
        $rows = Message::leftJoin('users', 'users.user_id', '=', 'message.operator_userid')
            ->where('message.agencyid', $agencyId)
            ->where('message.type', Message::TYPE_EMAIL)
            ->where('message.target_userid', 0)
            ->orderBy('update_time', 'desc')
            ->get([
                'message.id',
                'message.title',
                'message.content',
                'message.create_time',
                'message.status',
                'users.contact_name'
            ]);
        
        $mails = [];
        foreach ($rows as $row) {
            $content = json_decode($row->content);
            if ($content->type == 'sent' || $content->type == 'draft') {
                $mails[] = [
                    'id' => $row->id,
                    'title' => $row->title,
                    'content' => $content->body,
                    'create_time' => $row->create_time->toDateTimeString(),
                    'status' => $row->status,
                    'type' => $content->type,
                    'total' => $content->total,
                    'clients' => $this->getClients($content->clients),
                    'contact_name' => $row->contact_name
                ];
            } else {
                continue;
            }
        }
        
        return $this->success(null, [
            'count' => count($mails),
            'pageNo' => $pageNo,
            'pageSize' => $pageSize
        ], array_slice($mails, ($pageNo - 1) * $pageSize, $pageSize));
    }

    /**
     * 获取广告主列表
     * @param Request $request
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | user_id |  | integer  | 广告主id |  | 是 |
     * | email_address |  | string | 广告主邮箱 |  | 是 |
     * | clientname |  | string | 广告主名称 |  | 是 |
     * | account_id |  | integer | 广告主账户id |  | 是 |
     *
     */
    public function emailClient(Request $request)
    {
        $user = Auth::user();
        
        $agency = $user->account->agency;
        $agencyId = $agency->agencyid;
        
        $clients = Client::leftJoin('accounts', 'clients.account_id', '=', 'accounts.account_id')
            ->leftJoin('users', 'users.user_id', '=', 'accounts.manager_userid')
            ->where('clients.agencyid', $agencyId)
            ->where('clients.clients_status', Client::STATUS_ENABLE)
            ->get([
                'users.user_id',
                'users.email_address',
                'clients.brief_name as clientname',
                'clients.account_id'
            ]);
        $data = [];
        foreach ($clients as $client) {
            $data[] = $client->toArray();
        }
        return $this->success(null, null, $data);
    }

    /**
     * 邮件发送或保存草稿
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer  | emailID |  | 是 |
     * | title |  | string | 标题 |  | 是 |
     * | content |  | string | 内容 |  | 是 |
     * | type |  | string  |  | draft:草稿，sent:已发送 | 是 |
     * | clients |  | array |  |  | 是 |
     * |  | user_id | integer  | 广告主id |  | 是 |
     * |  | email_address | string | 广告主邮箱 |  | 是 |
     * |  | clientname | string |  | 广告主名称 | 是 |
     * |  | account_id | integer  | 广告主账户id |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function emailStore(Request $request)
    {
        if (($ret = $this->validate($request, [
            'id' => 'numeric',
            'title' => 'required',
            'content' => 'required',
            'clients' => 'required',
            'type' => 'required|in:sent,draft'
        ], [], Message::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $user = Auth::user();
        
        $title = $request->input('title');
        $id = $request->input('id');
        $content = $request->input('content');
        $clients = json_decode($request->input('clients'));
        $type = $request->input('type');
        
        $content = json_encode([
            'class' => 'mail',
            'body' => $content,
            'clients' => $clients,
            'total' => count($clients),
            'type' => $type
        ]);

        $operatorUserId = $user->user_id;
        $agency = $user->account->agency;
        $agencyId = $agency->agencyid;
        $operatorAccountId = $user->account->account_id;
        
        if ($id > 0) {
            $mail_message = Message::find($id);
            $mail_message->title = $title;
            $mail_message->create_time = date('Y-m-d H:i:s');
            $mail_message->content = $content;
            $mail_message->status = Message::STATUS_NOT_SEND;
            $mail_message->save();
        } else {
            $mailData = [
                'operator_accountid' => $operatorAccountId,
                'operator_userid' => $operatorUserId,
                'target_accountid' => 0,
                'target_userid' => 0,
                'agencyid' => $agencyId,
                'title' => $title,
                'create_time' => date('Y-m-d H:i:s'),
                'end_time' => '',
                'content' => $content,
                'type' => Message::TYPE_EMAIL,
                'status' => Message::STATUS_NOT_SEND,
                'retry_times' => 0,
                'error_code' => ''
            ];
            $message = new Message($mailData);
            $message->save();
        }
        $clients = $this->getClients($clients);
        if ($type == 'sent') {
            foreach ($clients as $adv) {
                $toAddress = $adv['email_address'];
                $mail['subject'] = $title;
                $mail['msg']['clientname'] = $adv['clientname'];
                $mail['msg']['content'] = $request->input('content');
                EmailHelper::sendEmail('emails.message.msgMail', $mail, $toAddress);
            }
        }
        return $this->success();
    }
    
    private function getClients($arr)
    {
        $users = [];
        foreach ($arr as $v) {
            $users[] = $v->user_id;
        }
        $clients = Client::leftJoin('accounts', 'clients.account_id', '=', 'accounts.account_id')
            ->leftJoin('users', 'users.user_id', '=', 'accounts.manager_userid')
            ->where('clients.clients_status', Client::STATUS_ENABLE)
            ->whereIn('users.user_id', $users)
            ->get([
                'users.user_id',
                'users.email_address',
                'clients.brief_name as clientname',
                'clients.account_id'
            ]);
        $data = [];
        foreach ($clients as $client) {
            $data[] = $client->toArray();
        }
        return $data;
    }

    /**
     * 删除邮件
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer  | emailID |  | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function emailDelete(Request $request)
    {
        if (($ret = $this->validate($request, [
            'id' => 'required|numeric'
        ], [], Message::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        $id = $request->input('id');
        Message::where('id', $id)->delete();
        return $this->success();
    }
}
