<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUpOpertionId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = <<<SQL
        UPDATE up_operations uo SET uo.id=12101 WHERE uo.name='manager-advertiser';
        UPDATE up_operations uo SET uo.id=12102 WHERE uo.name='manager-super-account-all';
        UPDATE up_operations uo SET uo.id=12103 WHERE uo.name='manager-super-account-self';
        UPDATE up_operations uo SET uo.id=13101 WHERE uo.name='manager-trafficker';
        UPDATE up_operations uo SET uo.id=13102 WHERE uo.name='manager-trafficker-account-all';
        UPDATE up_operations uo SET uo.id=13103 WHERE uo.name='manager-trafficker-account-self';
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
