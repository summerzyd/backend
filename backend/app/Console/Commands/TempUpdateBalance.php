<?php
namespace App\Console\Commands;

use App\Models\Balance;
use Illuminate\Support\Facades\DB;

class TempUpdateBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
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
        $res = DB::select("SELECT up_accounts.account_id from up_accounts
            LEFT JOIN up_balances ON up_balances.account_id = up_accounts.account_id
            WHERE up_balances.balance is null;");
        foreach ($res as $row) {
            $balance = Balance::find($row->account_id);
            if (!$balance) {
                $result = Balance::create([
                    'account_id' => $row->account_id,
                    'balance' => 0,
                    'gift' => 0,
                ]);
                if (!$result) {
                    return 5001;
                }
            }
        }
    }
}
