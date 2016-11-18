<?php
namespace App\Console\Commands;

use App\Models\Zone;
use Illuminate\Support\Facades\DB;

class TempUpdateZoneType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_zone_type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $zones = DB::table('zones')->whereIn('type', array(6, 7, 8))->get();
        $this->notice('need update zone data :'.count($zones));
        $result = DB::transaction(function () use ($zones) {
            if ($zones) {
                foreach ($zones as $item) {
                    if ($item->type == 6) {
                        Zone::where('type', 6)->update(['type' => 5]);
                    } elseif ($item->type == 7) {
                        Zone::where('type', 7)->update(['type' => 2, 'ad_type' => 5]);
                    } elseif ($item->type == 8) {
                        Zone::where('type', 8)->update(['type' => 2, 'ad_type' => 6]);
                    }
                }
            }
        });
        if ($result instanceof Exception) {
            $this->comment('error script!');
        }
    }
}
