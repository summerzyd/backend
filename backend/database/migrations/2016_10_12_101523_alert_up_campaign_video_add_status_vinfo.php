<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertUpCampaignVideoAddStatusVinfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
    ALTER TABLE up_campaigns_video ADD COLUMN `status` tinyint DEFAULT 1 AFTER type;
    ALTER TABLE up_campaigns_video ADD COLUMN reserve text NULL AFTER type;
  ALTER TABLE up_campaigns_video ADD COLUMN md5_file char(32) NULL AFTER type;
ALTER TABLE up_campaigns_video DROP INDEX up_campaigns_video_campaignid_unique;
ALTER TABLE up_campaigns_video ADD INDEX up_campaigns_video_campaignid_unique(campaignid,md5_file);
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
