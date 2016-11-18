<?php
namespace App\Jobs;

use App\Components\Config;
use App\Components\Helper\LogHelper;
use Illuminate\Support\Facades\View;

class EmailJob extends Job
{
    private $agencyId;
    private $toAddress;
    private $info;
    private $view;

    /**
     * 发送邮件(带视图)
     * @param $agencyId 联盟ID
     * @param $view 指定视图
     * @param $toAddress  发送的信息
     * @param $info 收件人
     */
    public function __construct($view, $info, $toAddress, $agencyId = 0)
    {
        $this->agencyId = $agencyId;
        $this->toAddress = $toAddress;
        $this->info = $info;
        $this->view = $view;
    }

    public function handle()
    {
        LogHelper::notice('agency:' . $this->agencyId
            . ' subject:' . json_encode($this->info));

        sleep(3);
        if (!is_null($this->toAddress)) {
            if ($this->agencyId > 0) {
                $host = Config::get('mail_host', $this->agencyId);
                $port = Config::get('mail_port', $this->agencyId);
                $username = Config::get('mail_username', $this->agencyId);
                $password = Config::get('mail_password', $this->agencyId);
                $fromAddress = Config::get('mail_from_address', $this->agencyId);
                $fromName = Config::get('mail_from_name', $this->agencyId);
            } else { // 如果最终没有指定agencyid，使用默认的配置
                $host = Config::get('mail.host');
                $port = Config::get('mail.port');
                $username = Config::get('mail.username');
                $password = Config::get('mail.password');
                $fromAddress = Config::get('mail.from.address');
                $fromName = Config::get('mail.from.name');
            }
            $transport = \Swift_SmtpTransport::newInstance($host, $port)
                ->setUsername($username)
                ->setPassword($password);
            $transport->setEncryption('tls');
            $mailer = \Swift_Mailer::newInstance($transport);

            $message = \Swift_Message::newInstance();
            $message->setTo($this->toAddress);
            $message->setSubject($this->info['subject']);
            $message->setFrom($fromAddress, $fromName);

            //添加邮件附件
            if (!empty($this->info['attach']['icon']) && file_exists($this->info['attach']['icon'])) {
                $message->attach(\Swift_Attachment::fromPath($this->info['attach']['icon']));
            }

            if (isset($this->info['attach']['images'])) {
                foreach ($this->info['attach']['images'] as $k => $image) {
                    $k += 1;
                    $parts = pathinfo($image);
                    $fileName = 'screen_shot' . $k . '.' . $parts['extension'];
                    if (file_exists($image)) {
                        $message->attach(\Swift_Attachment::fromPath($image)->setFilename($fileName));
                    }
                }
            }

            //添加发送 excel的附件 add by Arke
            if (!empty($this->info['attach']['excel']) && file_exists($this->info['attach']['excel'])) {
                $parts = pathinfo($this->info['attach']['excel']);
                $attachName = str_replace(",", "", $this->info['name']);
                $fileName = $attachName.".".$parts['extension'];
                $message->attach(\Swift_Attachment::fromPath($this->info['attach']['excel'])->setFilename($fileName));
            }

            $body = View::make($this->view, $this->info['msg'])->render();
            $message->setBody($body, 'text/html', 'utf-8');
            $type = $message->getHeaders()->get('Content-Type');
            $type->setValue('text/html');
            $type->setParameter('charset', 'utf-8');
            $result = $mailer->send($message);

            //记录日志
            $address = is_array($this->toAddress) ? implode(',', $this->toAddress) : $this->toAddress;
            LogHelper::notice('agency:' . $this->agencyId .' send mail to:' . $address
                . ' subject:' . $this->info['subject'] . " result:" . $result);
        } else {
            LogHelper::notice('no email address to send, subject:' .$this->info['subject']);
        }
    }
}
