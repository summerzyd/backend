<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpCampaignsStatusProcess extends Migration
{
    /**
     * Run the migrations.
     * 把状态为待投放,待传包的状态转成投放中
     * @return void
     */
    public function up()
    {
        $sql = "UPDATE up_campaigns SET `status` = 0 WHERE 1 AND `status` IN(12,13)";
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
