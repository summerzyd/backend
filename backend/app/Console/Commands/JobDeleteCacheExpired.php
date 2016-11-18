<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class JobDeleteCacheExpired extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_delete_cache_expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        // 一周前的时间起点 604800 一周的时间戳单位秒 两天的时间戳 172800
        $pre_week_time = date('Y-m-d H:i:s', (time() - 172800));
        try {
            for ($i = 0; $i < 100; $i ++) {
                $table = "data_cache_dump_" . str_pad($i, 3, 0, STR_PAD_LEFT);
                $sql = "delete from {$table} where addtime <= '{$pre_week_time}'";
                DB::connection('redisCacheMysql')->delete($sql);
            }
        } catch (\Exception $e) {
            $this->error('command:delete-cache-expired ' . $e->getMessage());
        }
    }
}
