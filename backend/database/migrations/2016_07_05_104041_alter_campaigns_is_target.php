<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCampaignsIsTarget extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // campaigns 添加定向标识字段 is_target 默认 0  、定向 1
        Schema::table("up_campaigns", function(Blueprint $table){
            $sql = "ALTER TABLE `up_campaigns`
                ADD COLUMN `is_target`  tinyint(2) UNSIGNED NULL DEFAULT 0 ";

            DB::connection()->getPdo()->exec($sql);
        });
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
