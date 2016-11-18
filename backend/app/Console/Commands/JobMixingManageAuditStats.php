<?php
namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\OperationDetail;

class JobMixingManageAuditStats extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_mixing_manage_audit_stats {--build-date=} {--agencyid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADN 媒体的下载审计功能';

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
    /**
     * Execute the console command with agency
     * @param $agency
     * @return mixed
     */
    public function finish(Agency $agency)
    {
        //获取审计的修改记录
        $rows = OperationDetail::where('status', OperationDetail::STATUS_ACCEPT_PENDING_REPORT)
                ->where('agencyid', $agency->agencyid)
                ->get()->toArray();
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                //修改状态为成功
                OperationDetail::where('id', $row['id'])->update(
                    [
                        'status' => OperationDetail::STATUS_ACCEPT_DONE
                    ]
                );
                //调用修复数据审计的job
                $this->call('job_recover_daily_data', [
                    '--start-date' => $row['day_time'],
                    '--end-date' => $row['day_time'],
                    '--role' => 'af',
                    '--agencyid' => $agency->agencyid
                ]);
            }
        }
    }
}
