<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpAccountSubType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_account_sub_type` VALUES ('101', '广告主运营', 'ADVERTISER', '5', '8', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('102', '广告主财务', 'ADVERTISER', '3', '8', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
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
