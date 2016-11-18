<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAffilatesAddAdxClass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //sandbox
//        $sql = <<<SQL
//UPDATE up_affiliates ua SET ua.adx_class='Youku',ua.mode=4,ua.affiliate_type=2 where ua.affiliateid=97;
//SQL;

        //线上
        $sql = <<<SQL
UPDATE up_affiliates ua SET ua.adx_class='Youku',ua.mode=4,ua.affiliate_type=2 where ua.affiliateid=108;
UPDATE up_affiliates ua SET ua.adx_class='Iqiyi',ua.mode=4,ua.affiliate_type=2 where ua.affiliateid=16;
UPDATE up_affiliates ua SET ua.adx_class='Sohu',ua.mode=4,ua.affiliate_type=2 where ua.affiliateid=116;
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
