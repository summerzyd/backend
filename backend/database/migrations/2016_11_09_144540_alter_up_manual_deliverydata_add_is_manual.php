<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpManualDeliverydataAddIsManual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //修改表结构（现在不需要添加字段来处理）
        /*$sql = <<<SQL
ALTER TABLE `up_manual_deliverydata` ADD COLUMN `is_manual` tinyint(1) NULL DEFAULT 0 AFTER `clicks`;
UPDATE `up_manual_deliverydata` SET `is_manual` = 1 WHERE 1 AND affiliate_id <> 65 AND zone_id <> 78;
SQL;
        DB::getPdo()->exec($sql);*/
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
