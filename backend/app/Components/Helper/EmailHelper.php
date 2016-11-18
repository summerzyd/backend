<?php
namespace App\Components\Helper;

use App\Jobs\EmailJob;
use Illuminate\Support\Facades\Queue;

class EmailHelper
{
    /**
     * 队列发送邮件
     * EmailHelper::sendEmail('emails.test', ['subject' => 'subject', 'msg' => ['app_name' => 'name']], '123@qq.com')
     * @param $view
     * @param $info
     * @param $toAddress
     * @param $agencyId
     */
    public static function sendEmail($view, $info, $toAddress, $agencyId = 0)
    {
        //分发邮件发送任务
        Queue::push(new EmailJob($view, $info, $toAddress, $agencyId));
    }
}
