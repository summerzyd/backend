<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpCampaignsVideo extends Migration
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
        CREATE TABLE up_campaigns_video (
      id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      campaignid int(11) NOT NULL,
      url varchar(255) NOT NULL,
      scale decimal(10, 2) NOT NULL,
      duration int(11) NOT NULL,
      type tinyint(4) NOT NULL,
      created_time date NOT NULL,
      updated_time timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE INDEX up_campaigns_video_campaignid_unique (campaignid)
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci;
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
