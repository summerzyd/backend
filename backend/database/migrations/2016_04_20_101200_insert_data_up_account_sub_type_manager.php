<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpAccountSubTypeManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_account_sub_type` VALUES ('1001', '销售账号', 'MANAGER', '1', '1001', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('1002', '媒介账号', 'MANAGER', '2', '1002', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('1003', '财务账号', 'MANAGER', '3', '1003', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('1004', '管理员账号', 'MANAGER', '4', '1004', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('1005', '运营账号', 'MANAGER', '5', '1005', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('1006', '审计员账号', 'MANAGER', '6', '1006', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('1007', '总经理', 'MANAGER', '7', '1007', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('1008', 'CEO', 'MANAGER', '8', '1008', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
        INSERT INTO `up_account_sub_type` VALUES ('1009', 'COO', 'MANAGER', '9', '1009', '0000-00-00 00:00:00', '0000-00-00 00:00:00');
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
