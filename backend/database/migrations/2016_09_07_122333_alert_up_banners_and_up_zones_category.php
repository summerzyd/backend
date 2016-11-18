<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertUpBannersAndUpZonesCategory extends Migration
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
  ALTER TABLE up_banners MODIFY COLUMN category varchar(250) NULL DEFAULT '';
  ALTER TABLE up_zones MODIFY COLUMN category varchar(250) NULL DEFAULT '';
  ALTER TABLE up_zones MODIFY COLUMN oac_category_id varchar(250) NULL DEFAULT '';
SQL;
        DB::connection()->getPdo()->exec($sql);
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
