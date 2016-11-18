<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterOperationDetailsModifyUniqueIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = "ALTER TABLE `up_operation_details`
                    DROP INDEX `up_operation_details_day_time_unique` ,
                    ADD UNIQUE INDEX `up_operation_details_day_time_unique` (`day_time`, `agencyid`) USING BTREE
                ";
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
