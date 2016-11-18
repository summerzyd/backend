<?php
namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Daily;

class JobWeeklyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_weekly_report {--agencyid=}';

    /**
     * The console command description.
     *获取每日报表邮件
     * @var string
     */
    protected $description = 'Command for send email about weekly report';

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
        $w = date('w');
        //周一才生成
        if ($w == 1) {
            $dateStart = date('Y-m-d', strtotime('-7 days'));
            $dateEnd = date('d', strtotime('-1 days'));
            $date = $dateStart . '~' . $dateEnd;
            $obj = \DB::table('daily')
                ->where('date', $date)
                ->where('agencyid', $agency->agencyid)
                ->get();
            if (!$obj) {
                $daily = new Daily();
                $daily->agencyid = $agency->agencyid;
                $daily->date = $date;
                $daily->type = Daily::TYPE_WEEKLY;
                //日报手动发送，不自动发送
                $daily->save();
            }
        }
    }
}
