<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpZoneListTypeAddUniqueIndex extends Migration
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
        ALTER TABLE `up_zone_list_type`
        DROP INDEX `up_zone_list_type_af_id_listtypeid_unique` ,
        ADD UNIQUE INDEX `up_zone_list_type_af_id_listtypeid_unique` (`af_id`, `listtypeid`, `ad_type`, `type`) USING BTREE
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
