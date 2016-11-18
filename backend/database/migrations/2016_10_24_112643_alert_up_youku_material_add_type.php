<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertUpYoukuMaterialAddType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
ALTER TABLE up_youku_material_manager ADD COLUMN `type`  tinyint DEFAULT 1 AFTER `status`;
ALTER TABLE up_iqiyi_material_manager ADD COLUMN `type` tinyint DEFAULT 1 AFTER `status`;
ALTER TABLE up_campaigns_video ADD COLUMN real_name varchar(100) AFTER path;
ALTER TABLE up_youku_material_manager ADD COLUMN upload_status tinyint DEFAULT 1 AFTER `status`;
ALTER TABLE up_youku_material_manager ADD COLUMN source_url varchar(255) AFTER url;
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
