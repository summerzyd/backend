<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAffiliatesAddIncomeAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('up_affiliates', function(Blueprint $table)
        {
            $sql = <<<SQL
Alter table `up_affiliates` add `income_amount` decimal(10,2) DEFAULT '0.00' AFTER `income_rate`;
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
        //
        Schema::table('up_affiliates', function (Blueprint $table) {
            $table->dropColumn('income_amount');
        });
    }
}
