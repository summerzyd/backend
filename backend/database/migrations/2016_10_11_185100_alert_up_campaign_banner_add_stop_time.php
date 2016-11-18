<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertUpCampaignBannerAddStopTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
    ALTER TABLE up_banners ADD COLUMN stop_time datetime AFTER created;
    ALTER TABLE up_campaigns ADD COLUMN stop_time datetime AFTER created;
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
