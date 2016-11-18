<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAdZoneKeywordAddType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //新增关键字类型，及广告位创建时间
        $sql = <<<SQL
        ALTER TABLE up_ad_zone_keywords ADD COLUMN `type` tinyint not NULL DEFAULT 1 AFTER `status`;
        ALTER TABLE up_ad_zone_price ADD COLUMN created_time datetime NULL AFTER price_up;
        ALTER TABLE up_clients ADD COLUMN affiliateid int(11) NOT NULL DEFAULT 0 AFTER broker_id;
        ALTER TABLE up_ad_zone_price ADD COLUMN `id` int NOT NULL UNIQUE AUTO_INCREMENT FIRST;
        ALTER TABLE up_ad_zone_price DROP PRIMARY KEY;
        ALTER TABLE up_ad_zone_price ADD PRIMARY KEY(`id`);
        ALTER TABLE up_ad_zone_price ADD UNIQUE INDEX up_ad_zone_price_zone_id_ad_id (zone_id, ad_id);
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
