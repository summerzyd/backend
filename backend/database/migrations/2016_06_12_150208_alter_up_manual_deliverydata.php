<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpManualDeliverydata extends Migration
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
ALTER TABLE `up_manual_deliverydata` ADD COLUMN `cpa` int(10) NOT NULL DEFAULT 0 AFTER `expense`
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
        Schema::table('up_manual_deliverydata', function(Blueprint $table)
        {
            $table->dropColumn('cpa');
        });
    }
}
