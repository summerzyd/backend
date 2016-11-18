<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Components\Helper\LogHelper;

class Command extends \Illuminate\Console\Command
{

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->notice($this->input->__toString() . " Begin:");
        $ret = parent::execute($input, $output);
        $this->notice($this->getName() . " End.");
        return $ret;
    }

    public function info($string)
    {
        parent::info($string);
        LogHelper::info($string, 'logs', false, 2);
    }

    public function error($string)
    {
        parent::error($string);
        LogHelper::error($string, 'logs', false, 2);
        LogHelper::notice($string, 'logs', false, 2); //在notice中再写一次，便于发现
    }

    public function warn($string)
    {
        parent::warn($string);
        LogHelper::warning($string, 'logs', false, 2);
    }

    public function notice($string)
    {
        parent::info($string);
        LogHelper::notice($string, 'logs', false, 2);
    }
}
