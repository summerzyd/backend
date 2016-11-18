<?php

use Illuminate\Database\Migrations\Migration;

class AlterTableUpOperationManagerId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        UPDATE `up_operations` SET id = 10101 WHERE id = 14113;
        UPDATE `up_operations` SET id = 10102 WHERE id = 14115;
        UPDATE `up_operations` SET id = 10103 WHERE id = 1471;
        UPDATE `up_operations` SET id = 10104 WHERE id = 14306;

        UPDATE `up_operations` SET id = 12101 WHERE id = 1460;
        UPDATE `up_operations` SET id = 12102 WHERE id = 1475;
        UPDATE `up_operations` SET id = 12103 WHERE id = 14111;

        UPDATE `up_operations` SET id = 13101 WHERE id = 1410;
        UPDATE `up_operations` SET id = 13102 WHERE id = 141002;
        UPDATE `up_operations` SET id = 13103 WHERE id = 141003;
        UPDATE `up_operations` SET id = 13104 WHERE id = 14103;

        UPDATE `up_operations` SET id = 15101 WHERE id = 1473;
        UPDATE `up_operations` SET id = 15102 WHERE id = 14108;
        UPDATE `up_operations` SET id = 15103 WHERE id = 14109;

        UPDATE `up_operations` SET id = 16101 WHERE id = 14104;
        UPDATE `up_operations` SET id = 16102 WHERE id = 14300;
        UPDATE `up_operations` SET id = 16103 WHERE id = 14301;
        UPDATE `up_operations` SET id = 16104 WHERE id = 14307;
        UPDATE `up_operations` SET id = 16105 WHERE id = 14308;

        UPDATE `up_operations` SET id = 17101 WHERE id = 14309;
        UPDATE `up_operations` SET id = 17102 WHERE id = 1470;

        UPDATE `up_operations` SET id = 14101 WHERE id = 1461;
        UPDATE `up_operations` SET id = id - 348 WHERE id >= 14450 and id <= 14453;

        UPDATE `up_operations` SET id = 19101 WHERE id = 10104;
        UPDATE `up_operations` SET id = 19102 WHERE id = 12101;
        UPDATE `up_operations` SET id = 19103 WHERE id = 12102;
        UPDATE `up_operations` SET id = 19104 WHERE id = 17101;
        UPDATE `up_operations` SET id = 19106 WHERE id = 17102;
        UPDATE `up_operations` SET id = 19108 WHERE id = 14103;
        UPDATE `up_operations` SET id = 14103 WHERE id = 19108;
        UPDATE `up_operations` SET id = 19108 WHERE id = 12103;
        UPDATE `up_operations` SET id = 19110 WHERE id = 10101;
        UPDATE `up_operations` SET id = 19112 WHERE id = 10102;

        UPDATE `up_operations` SET id = 17101 WHERE id = 10103;
        UPDATE `up_operations` SET id = 17102 WHERE id = 41901;

        UPDATE `up_operations` SET `description` = '运营概览' WHERE id = 19101;
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
