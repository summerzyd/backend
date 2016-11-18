<?php
namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class TempUpdateManagerRole extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_manager_role';

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
        // 获取Manager的用户ID
        $ret = DB::select('
            SELECT p.user_id, o.`name` FROM up_account_user_permission_assoc p
            LEFT JOIN up_users u ON p.user_id = u.user_id
            LEFT JOIN up_accounts a ON a.account_id = u.default_account_id
            INNER JOIN up_operations o ON o.id = CONCAT(14,p.permission_id)
            WHERE a.account_type = "MANAGER"
        ');
        $update_list = [];
        foreach ($ret as $row) {
            if (! isset($update_list[$row->user_id])) {
                $update_list[$row->user_id] = [];
            }
            $update_list[$row->user_id][] = $row->name;
        }
        
        foreach ($update_list as $uid => $roles) {
            $user = User::find($uid);
            $operation_list = implode(',', $roles);
            if ($user->role_id != 0) {
                $brokerRoleTemp = Role::find($user->role_id);
                $brokerRoleTemp->operation_list = $operation_list;
                if ($brokerRoleTemp->save()) {
                    $this->comment("update Role {$user->role_id} succ");
                }
            } else {
                $brokerRoleTemp = $this->createRole($operation_list);
                $user->role_id = $brokerRoleTemp->id;
                if ($user->save()) {
                    $this->comment("update user {$uid} succ");
                }
            }
        }
        // 平台账户权限修改
        $admin = User::find(2);
        $role = Role::find($admin->role_id);
        
        $ret = DB::select('SELECT `name` FROM `up_operations` WHERE account_type="MANAGER"');
        $operation_list = [];
        foreach ($ret as $row) {
            $operation_list[] = $row->name;
        }
        $role->operation_list = implode(',', $operation_list);
        $role->save();
    }

    private function createRole($operation_list)
    {
        return Role::create([
            'name' => '管理员子账号',
            'description' => '管理员子账号',
            'type' => 2,
            'operation_list' => $operation_list,
            'created_by' => 6,
            'updated_by' => 6
        ]);
    }
}
