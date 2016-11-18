<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpOperations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_operations` VALUES ('30101', 'advertiser-profile', '修改资料', 'ADVERTISER');
        INSERT INTO `up_operations` VALUES ('30102', 'advertiser-password', '修改密码', 'ADVERTISER');
        INSERT INTO `up_operations` VALUES ('30103', 'advertiser-message', '通知消息', 'ADVERTISER');
        INSERT INTO `up_operations` VALUES ('31101', 'advertiser-campaign', '我的推广', 'ADVERTISER');
        INSERT INTO `up_operations` VALUES ('31102', 'advertiser-stat', '统计报表', 'ADVERTISER');
        INSERT INTO `up_operations` VALUES ('31103', 'advertiser-balance', '账户明细', 'ADVERTISER');
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
