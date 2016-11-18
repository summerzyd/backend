<?php
namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class JobMixingAmount extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_mixing_amount {start_time} {end_time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '把人工录数据zoneid=0的数据分配并扣款';

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
        $start_time = $this->argument('start_time');
        $end_time = $this->argument('end_time');
        $this->notice('call new_proc_balanced_amount("' . $start_time . '", "' . $end_time . '");');
        $pdo = DB::getPdo();
        $stmt = $pdo->query('call new_proc_balanced_amount("' . $start_time . '", "' . $end_time . '");');
        $stmt->closeCursor();
    }
}
