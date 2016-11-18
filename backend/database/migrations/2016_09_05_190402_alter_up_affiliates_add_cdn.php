<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAffiliatesAddCdn extends Migration
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
Alter table `up_affiliates` add `cdn` INTEGER NOT NULL DEFAULT 1;
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
            $table->dropColumn('cdn');
        });
    }
}
