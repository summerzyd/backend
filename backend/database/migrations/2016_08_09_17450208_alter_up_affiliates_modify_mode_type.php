<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAffiliatesModifyModeType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $sql = <<<SQL
            ALTER TABLE `up_affiliates`
            MODIFY COLUMN `mode` int(11) NOT NULL DEFAULT 0;
            ALTER TABLE `up_manual_deliverydata`
            MODIFY COLUMN `flag` int(11) NOT NULL DEFAULT 0;
            ALTER TABLE `up_manual_clientdata`
            MODIFY COLUMN `flag` int(11) NOT NULL DEFAULT 0;
            UPDATE up_affiliates SET `mode` = `mode` -1;
            UPDATE up_manual_deliverydata SET `flag` = `flag` -1;
            UPDATE up_manual_clientdata SET `flag` = `flag` -1;
SQL;
        DB::getPdo()->exec($sql);

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
