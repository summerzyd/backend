<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAffiliatesAddBusinessType extends Migration
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
        ALTER TABLE up_affiliates ADD COLUMN `delivery_type` int(11) not NULL DEFAULT 1 AFTER `kind`;
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
