<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class TempRepairUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_repair_user_role';
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
        $mainRole = Role::find(7);
        $users = User::all();
        $result = DB::transaction(
            function () use ($users, $mainRole) {
                $mainRole->operation_list = $mainRole->operation_list.',advertiser-account';
                $mainRole->save();
                foreach ($users as $user) {
                    $account = $user->account;
                    $role = Role::find($user->role_id);
                    if ($account == null) {
                        continue;
                    }
                    if ($account->manager_userid == $user->user_id && $account->isAdvertiser()) {
                        $role->operation_list = $role->operation_list.',advertiser-account';
                        $role->save();
                    }


                }
            }
        );
        if ($result instanceof Exception) {
            $this->comment('error script!');
        }
    }
}
