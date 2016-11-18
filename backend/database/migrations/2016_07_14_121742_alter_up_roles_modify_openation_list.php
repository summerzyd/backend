<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpRolesModifyOpenationList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //修改权限
        $sql = <<<SQL
                    UPDATE up_roles AS r
                    JOIN 
                    (SELECT
                        id
                    FROM
                        `up_roles`
                    WHERE
                        1
                        AND FIND_IN_SET('manager-add_client_data',operation_list)) b
                    ON r.id = b.id
                    SET r.operation_list = replace(r.operation_list, ',manager-add_client_data', '')
SQL;
        DB::getPdo()->exec($sql);
        
        $sql = <<<SQL
                    UPDATE up_roles AS r
                    JOIN
                    (SELECT
                        id
                    FROM
                        `up_roles`
                    WHERE
                        1
                        AND FIND_IN_SET('manager-add_client_data',operation_list)) b
                    ON r.id = b.id
                    SET r.operation_list = replace(r.operation_list, 'manager-add_client_data,', '')
SQL;
        DB::getPdo()->exec($sql);
        
        $sql = <<<SQL
                DELETE FROM `up_operations` WHERE 1 AND `id` = 14118;
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
