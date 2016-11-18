<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAffiliatesAddKind extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //新增关键字类型，及广告位创建时间
        $sql = <<<SQL
        ALTER TABLE up_affiliates ADD COLUMN `kind` int(11) not NULL DEFAULT 1 AFTER `mode`;
        Alter table `up_affiliates` add `self_income_amount` decimal(10,2) DEFAULT '0.00' AFTER `income_amount`;
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
