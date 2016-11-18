<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpRolesTrafficker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_roles` VALUES ('6', '媒体商主账户', '媒体商主账户', '1', 'trafficker-profile,trafficker-password,trafficker-message,trafficker-campaign,trafficker-stat,trafficker-balance,trafficker-zone,trafficker-sdk', '6', '6');
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
