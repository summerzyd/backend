<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpCampaignAddDayLimitTotal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        ALTER TABLE up_campaigns ADD COLUMN day_limit_program decimal(10,4) DEFAULT 0 AFTER day_limit;
        UPDATE up_campaigns uc SET uc.day_limit_program = uc.day_limit;
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
