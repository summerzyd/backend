<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpDataHourlyDailyAfAddAffiliateid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = "Alter table `up_data_hourly_daily_af` add `affiliateid` mediumint(9) NOT NULL DEFAULT '0';
                Alter table `up_data_hourly_daily_af` add `pay` tinyint(4) NOT NULL DEFAULT '0';";
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
