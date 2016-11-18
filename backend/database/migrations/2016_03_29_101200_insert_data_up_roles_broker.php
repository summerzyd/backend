<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpRolesBroker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_roles` VALUES ('5', '代理商主账户', '代理商主账户', '1', 'broker-profile,broker-password,broker-message,broker-advertiser,broker-campaign,broker-stat,broker-balance', '6', '6');
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
