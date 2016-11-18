<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Account;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class TempAssignUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_assign_user_role';

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

        $users = User::all();
        $mainRole = Role::find(7);
        $subRole = Role::find(8);
        $traffickerRole = Role::find(6);
        $brokerRole = Role::find(5);

        $result = DB::transaction(
            function () use ($users, $mainRole, $subRole, $traffickerRole, $brokerRole) {
                foreach ($users as $user) {
                    $account = $user->account;
                    $role = $user->role;

                    if ($account == null) {
                        //$this->comment($user->user_id . ' ' . $user->username . ' get account failed');
                        continue;
                    }

                    if ($role != null && $role->type == Role::TYPE_USER) {
                        $this->comment($user->user_id . ' ' . $user->username . ' role exist');
                        continue;
                    }


                    //广告主主帐户
                    if ($account->manager_userid == $user->user_id && $account->isAdvertiser()) {
                        $mainRoleTemp = $this->createRole($mainRole);

                        if ($mainRoleTemp) {
                            $user->role_id = $mainRoleTemp->id;
                            if ($user->save()) {
                                $this->comment('create a role type and assoc to user');
                            }
                        }

                    }

                    //广告主子帐户
                    if ($account->manager_userid != $user->user_id && $account->isAdvertiser()) {
                        $subRoleTemp = $this->createRole($subRole);

                        if ($subRoleTemp) {
                            $user->role_id = $subRoleTemp->id;
                            $user->account_sub_type_id = 101;
                            if ($user->save()) {
                                $this->comment('create a role type and assoc to user' . $user->username);
                            }
                        }
                    }

                    //媒体商账户
                    if ($account->isTrafficker()) {
                        $traffickerRoleTemp = $this->createRole($traffickerRole);

                        if ($traffickerRoleTemp) {
                            $user->role_id = $traffickerRoleTemp->id;
                            if ($user->save()) {
                                $this->comment('create a trafficker role type and assoc to user' . $user->username);
                            }
                        }
                    }

                    //代理商
                    if ($account->isBroker()) {
                        $brokerRoleTemp = $this->createRole($brokerRole);

                        if ($brokerRoleTemp) {
                            $user->role_id = $brokerRoleTemp->id;
                            if ($user->save()) {
                                $this->comment('create a broker role type and assoc to user' . $user->username);
                            }
                        }
                    }

                    //admin
                    if ($account->isAdmin()) {
                        $user->role_id = 1;
                        if ($user->save()) {
                            $this->comment('create an admin role type and assoc to user' . $user->username);
                        }
                    }
                }
            }
        );

        if ($result instanceof Exception) {
            $this->comment('error script!');
        }
    }

    private function createRole($role)
    {
            return Role::create([
                'name'              => $role->name,
                'description'       => $role->description,
                'type'              => 2,
                'operation_list'    => $role->operation_list,
                'created_by'        => $role->created_by,
                'updated_by'        => $role->updated_by,
            ]);
    }
}
