<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableManualDeliverydata extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('up_manual_deliverydata', function(Blueprint $table)
        {
        $sql = <<<SQL
ALTER TABLE `up_manual_deliverydata` ADD COLUMN `data_type` char(16) NULL DEFAULT NULL AFTER `id`;
ALTER TABLE `up_manual_deliverydata` DROP INDEX `ukey` , ADD UNIQUE INDEX `ukey` (`date`, `affiliate_id`, `zone_id`, `banner_id`, `campaign_id`, `data_type`) USING BTREE;
UPDATE up_manual_deliverydata SET data_type = 'D2D' WHERE conversions > 0;
UPDATE up_manual_deliverydata SET data_type = 'C2C' WHERE clicks > 0;
UPDATE up_manual_deliverydata SET data_type = 'D2D' WHERE data_type IS NULL;
SQL;
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
        Schema::table('up_manual_deliverydata', function(Blueprint $table)
        {
            $table->dropColumn('data_type');
        });
    }
}
