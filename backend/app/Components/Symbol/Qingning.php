<?php
namespace App\Components\Symbol;

class Qingning extends Symbol
{
    public function getUrl()
    {
        return 'http://api.apps.mycheering.com/seachbdos.aspx?pi=1&ps=100&t=' . rand(1, 9999);
    }
}
