<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpManualDeliverydataModifyDataType extends Migration
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
ALTER TABLE `up_manual_deliverydata`
MODIFY COLUMN `data_type` char(16) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'D2D' AFTER `id`
SQL;
            DB::connection()->getPdo()->exec($sql);
        });
        //
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
