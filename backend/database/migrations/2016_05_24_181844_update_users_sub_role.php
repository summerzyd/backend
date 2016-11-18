<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersSubRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
                $sql = <<<SQL
UPDATE up_users u
JOIN up_account_sub_type ast ON ast.account_department = u.user_role
SET u.account_sub_type_id = ast.id
WHERE
	u.account_sub_type_id = 0
AND u.user_role <> 0
SQL;
        DB::getPdo()->exec($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
