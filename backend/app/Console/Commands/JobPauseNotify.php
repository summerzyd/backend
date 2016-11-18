<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Models\Banner;

class JobPauseNotify extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'job_pause_notify {--afid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify pause signal to private api';

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
        // 紫贝壳afid为85
        $id = $this->option('afid') ? $this->option('afid') : 85;
        $this->pauseNotify($id);
    }

    private function pauseNotify($afId)
    {
        $connPmp = DB::connection('pmp');
        
        // 通过afid和暂停状态查询 PMP 的包名, 再通过包名查询ADN的appid
        $rows = DB::table('banners as b')
            ->join('attach_files as f', 'b.attach_file_id', '=', 'f.id')
            ->where('b.affiliateid', $afId)
            ->where('b.status', Banner::STATUS_SUSPENDED)
            ->select('f.package_name')
            ->get();
        
        if (count($rows) <= 0) {
            return "Get packageName by afid={$afId} from adn Error...";
        }
        foreach ($rows as $row) {
            $app = $connPmp->table('appinfos')
                ->where('package', $row->package_name)
                ->select('app_id')
                ->first();
            if ($app == null) {
                $this->error("Get app_id by package={$row->package_name} from pmp Error...");
                continue;
            }
            $appId = $app->app_id;
            
            $this->httpRequest("http://md.25fz.com/index.php?m=Mendian&c=Pinxiaotong&a=downline_app&tagid={$appId}");
        }
    }

    private function httpRequest($url)
    {
        $this->notice($url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        
        $this->notice($output);
        return $output;
    }
}
