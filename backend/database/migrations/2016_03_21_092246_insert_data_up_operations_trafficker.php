<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpOperationsTrafficker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_operations` VALUES ('20101', 'trafficker-profile', '修改资料', 'TRAFFICKER');
        INSERT INTO `up_operations` VALUES ('20102', 'trafficker-password', '修改密码', 'TRAFFICKER');
        INSERT INTO `up_operations` VALUES ('20103', 'trafficker-message', '通知消息', 'TRAFFICKER');
        INSERT INTO `up_operations` VALUES ('21101', 'trafficker-campaign', '广告管理', 'TRAFFICKER');
        INSERT INTO `up_operations` VALUES ('21102', 'trafficker-stat', '统计报表', 'TRAFFICKER');
        INSERT INTO `up_operations` VALUES ('21103', 'trafficker-balance', '账户明细', 'TRAFFICKER');
        INSERT INTO `up_operations` VALUES ('21104', 'trafficker-zone', '广告位管理', 'TRAFFICKER');
        INSERT INTO `up_operations` VALUES ('21106', 'trafficker-sdk', 'SDK下载', 'TRAFFICKER');
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
