<?php
namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Daily;
use Illuminate\Support\Facades\DB;

class JobDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_daily_report {--agencyid=}';

    /**
     * The console command description.
     *获取每日报表邮件
     * @var string
     */
    protected $description = 'Command for send email about daily report';

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
     * @return mixed
     */
    public function handle()
    {
        $agencyId = $this->option('agencyid') ? $this->option('agencyid') : 0;
        if ($agencyId > 0) {
            $model = Agency::find($agencyId);
            if ($model) {
                $this->finish($model);
            }
        } else {
            $models = Agency::get();
            foreach ($models as $model) {
                $this->finish($model);
            }
        }
    }

    public function finish(Agency $agency)
    {
        //生成当天的发送记录
        $date = date('Y-m-d');
        $obj = DB::table('daily')
            ->where('date', $date)
            ->where('agencyid', $agency->agencyid)
            ->get();
        
        if (!$obj) {
            $daily = new Daily();
            $daily->agencyid = $agency->agencyid;
            $daily->date = $date;
            $daily->save();
        }
    }
}
