<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpBalanceLogAddGift extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = "ALTER TABLE `up_balance_log`
                ADD COLUMN `gift`  decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `amount`";
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
