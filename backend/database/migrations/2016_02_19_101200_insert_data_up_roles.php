<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_roles` VALUES ('7', '广告主主账户', '广告主主账户', '1', 'advertiser-account,advertiser-profile,advertiser-password,advertiser-message,advertiser-campaign,advertiser-stat,advertiser-balance', '6', '6');
        INSERT INTO `up_roles` VALUES ('8', '广告主子账户', '广告主子账户', '1', 'advertiser-profile,advertiser-password,advertiser-message,advertiser-campaign,advertiser-stat,advertiser-balance', '1', '0');
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
