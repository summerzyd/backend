<?php
namespace App\Jobs;

use App\Services\MessageService;

class MessageJob extends Job
{
    
    public function __construct($role, $msgContent, $contentClass = null)
    {
        $this->role = $role;
        $this->msgContent = $msgContent;
        $this->contentClass = $contentClass;
    }

    public function handle()
    {
        MessageService::sendWebMessage($this->role, $this->msgContent, $this->contentClass);
    }
}
