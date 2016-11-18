<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpOperationsBroker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_operations` VALUES ('40101', 'broker-profile', '修改资料', 'BROKER');
        INSERT INTO `up_operations` VALUES ('40102', 'broker-password', '修改密码', 'BROKER');
        INSERT INTO `up_operations` VALUES ('40103', 'broker-message', '通知消息', 'BROKER');
        INSERT INTO `up_operations` VALUES ('41100', 'broker-advertiser', '广告主管理', 'BROKER');
        INSERT INTO `up_operations` VALUES ('41101', 'broker-campaign', '广告管理', 'BROKER');
        INSERT INTO `up_operations` VALUES ('41102', 'broker-stat', '统计报表', 'BROKER');
        INSERT INTO `up_operations` VALUES ('41103', 'broker-balance', '账户明细', 'BROKER');
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
