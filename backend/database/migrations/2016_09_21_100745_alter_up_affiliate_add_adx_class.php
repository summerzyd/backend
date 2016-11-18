<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAffiliateAddAdxClass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //新增ADX类存储字段
        $sql = <<<SQL
        ALTER TABLE up_affiliates ADD COLUMN adx_class varchar(100) NULL AFTER symbol;
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
