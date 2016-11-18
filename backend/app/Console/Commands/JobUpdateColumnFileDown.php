<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Components\Helper\LogHelper;

class JobUpdateColumnFileDown extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_update_column_file_down';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update column file_down.';

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
        // 查询记录下载完成的数据表up_download_accomplished
        $downloads = DB::table('download_accomplished')->where('status', 0)
            ->groupBy('bannerid', 'zoneid', 'date_time')
            ->get([
            'bannerid',
            'zoneid',
            DB::raw("DATE_FORMAT(start_time,'%Y-%m-%d %H:00:00') as date_time"),
            DB::raw('COUNT(1) as file_down')
            ]);

        foreach ($downloads as $d) {
            $bannerId = $d->bannerid;
            $zoneId = $d->zoneid;
            $dateTime = $d->date_time;
            $fileDown = $d->file_down;
            $sql = DB::table('data_summary_ad_hourly')->where('ad_id', $bannerId)
                ->where('zone_id', $zoneId)
                ->where('date_time', $dateTime);
            $count = $sql->count();

            DB::beginTransaction();
            try {
                if ($count > 0) {
                    $sql->update(['file_down' => DB::raw("file_down+{$fileDown}")]);
                } else {
                    DB::table('data_summary_ad_hourly')->insert([
                        'ad_id' => $bannerId,
                        'zone_id' => $zoneId,
                        'date_time' => $dateTime,
                        'file_down' => $fileDown,
                    ]);
                }

                DB::table('download_accomplished')->where('bannerid', $bannerId)
                    ->where('zoneid', $zoneId)
                    ->whereRaw("DATE_FORMAT(start_time,'%Y-%m-%d %H:00:00') = '{$dateTime}'")
                    ->update([
                        'status' => 1
                    ]);
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Unkown Error: " . $e->getMessage());
                LogHelper::error("Unkown Error: " . $e->getMessage());
            }
            DB::commit();
        }
    }
}
