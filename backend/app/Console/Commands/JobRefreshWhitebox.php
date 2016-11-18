<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\Redis;

class JobRefreshWhitebox extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_refresh_whitebox {--afid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For refresh whitebox...';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $afId = $this->option('afid') ? $this->option('afid') : 103;
        $redis = Redis::connection();
        // MGMT-1778 应用汇afid=103，刷新白名单至adn_whitebox
        $redis->select(0); // bos-adn-up项目redis使用默认0
        $ret_val = $this->httpRequestGet('http://api.union.appchina.com/whitebox/api');
        $redis->del('adn_whitebox');
        $redis->hSet('adn_whitebox', $afId, $ret_val);
        $redis->disconnect();
    }

    private function httpRequestGet($url)
    {
        $this->notice('URL==' . $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        
        $this->notice('RET==' . $output);
        return $output;
    }
}
