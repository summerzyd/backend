<?php
namespace App\Console\Commands;

class TempUpdateDailyAf extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_daily_af';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update data_hourly_daily_af';

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
        $rows = \DB::table('affiliates')->select('affiliateid')->get();
        $count = count($rows);
        $this->notice("{$count} affiliates need to update.");
        foreach ($rows as $row) {
            $afId = $row->affiliateid;
            $this->notice("Affiliate {$afId} begin to update...");
            \DB::table('data_hourly_daily_af as daf')
                ->join('banners as b', 'b.bannerid', '=', 'daf.ad_id')
                ->where('b.affiliateid', $afId)
                ->update(['daf.affiliateid' => $afId]);
            $this->notice("Affiliate {$afId} update end.");
        }
        \DB::table('data_hourly_daily_af as daf')->where('date', '<', '2016-05-01')->update(['pay'=>1]);
    }
}
