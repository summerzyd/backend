<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableIndex extends Migration
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
        ALTER TABLE `up_data_hourly_daily` ADD INDEX `up_data_hourly_daily_campaign_id_date` (`campaign_id`,`date`);
        ALTER TABLE `up_data_hourly_daily_client` ADD INDEX `up_data_hourly_daily_client_campaign_id_date` (`campaign_id`,`date`);
        ALTER TABLE `up_data_hourly_daily_af` ADD INDEX `up_data_hourly_daily_af_campaign_id_date` (`campaign_id`,`date`);
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
