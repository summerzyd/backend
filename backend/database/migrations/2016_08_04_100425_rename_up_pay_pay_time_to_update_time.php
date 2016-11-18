<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameUpPayPayTimeToUpdateTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = <<<SQL
        ALTER TABLE up_pay CHANGE pay_time update_time timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
        UPDATE up_pay up SET up.updated_time=up.create_time WHERE up.updated_time IS NULL;
        UPDATE up_pay_tmp upt SET upt.updated_time=upt.create_time where upt.updated_time IS NULL;
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
