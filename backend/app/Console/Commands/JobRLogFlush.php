<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\Redis;

class JobRLogFlush extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_rlog_flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush redis log.';

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
        for ($i = 0; $i < 10; $i++) {
            sleep(5); // 每分钟执行10次，间隔5秒

            $redis = Redis::connection('RLog');
            if (!$redis) {
                return;
            }
            $redis->select(1);
            $keys = $redis->keys('RLog::*');
            foreach ($keys as $key) {
                //日志文件名
                $fileName = str_replace('RLog::', '', $key);
                $fileName = storage_path($fileName);
                $path = dirname($fileName);
                if (!file_exists($path) && !is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                //获取数据条数
                $len = $redis->llen($key);
                //分次拉取，避免一次取出过多数据，导致内存溢出
                $per = 1000;
                if (file_exists($fileName) && !is_writable($fileName)) {
                    $this->notice("RLog: file({$fileName}) is not writable");
                    //文件存在，但不可写，则不处理
                    continue;
                }
                $rs = fopen($fileName, 'ab');
                while ($len > 0) {
                    $data = $redis->lrange($key, 0, $per - 1);
                    $redis->ltrim($key, count($data), -1);
                    $len -= $per;
                    $content = '';
                    foreach ($data as $temp) {
                        $content .= trim($temp) . "\r\n";
                    }
                    fwrite($rs, $content);
                }
                fclose($rs);
            }
        }
    }
}
