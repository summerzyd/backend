<?php
namespace App\Console\Commands;

class TempUpdateDailyClient extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_daily_client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update data_hourly_daily_client';

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
        $rows = \DB::table('clients')->select('clientid')->get();
        $count = count($rows);
        $this->notice("{$count} clients need to update.");
        foreach ($rows as $row) {
            $clientId = $row->clientid;
            $this->notice("Client {$clientId} begin to update...");
            \DB::table('data_hourly_daily_client as dc')
                ->join('campaigns as c', 'c.campaignid', '=', 'dc.campaign_id')
                ->where('c.clientid', $clientId)
                ->update(['dc.clientid' => $clientId]);
            $this->notice("Client {$clientId} update end.");
        }
    }
}
