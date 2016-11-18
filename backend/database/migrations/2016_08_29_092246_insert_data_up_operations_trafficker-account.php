<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpOperationsTraffickerAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('20100', 'trafficker-overview', '运营概览', 'TRAFFICKER', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('25101', 'trafficker-self-profile', '修改资料', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('25102', 'trafficker-self-password', '修改密码', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('25103', 'trafficker-self-message', '通知消息', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('25104', 'trafficker-self-overview', '运营概览', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('26100', 'trafficker-self-advertiser', '广告主管理', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('26101', 'trafficker-self-campaign', '广告管理', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('26102', 'trafficker-self-stat', '统计报表', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('26103', 'trafficker-self-balance', '财务管理', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('26104', 'trafficker-self-zone', '广告位管理', 'TRAFFICKER-SELF', NULL);
        INSERT INTO `up_operations` (`id`, `name`, `description`, `account_type`, `updated_time`) VALUES ('26105', 'trafficker-self-account', '账号管理', 'TRAFFICKER-SELF', NULL);

        UPDATE `up_roles` SET `operation_list`='trafficker-overview,trafficker-profile,trafficker-password,trafficker-message,trafficker-campaign,trafficker-stat,trafficker-balance,trafficker-zone,trafficker-sdk,trafficker-self-profile,trafficker-self-password,trafficker-self-message,trafficker-self-overview,trafficker-self-advertiser,trafficker-self-campaign,trafficker-self-stat,trafficker-self-balance,trafficker-self-zone,trafficker-self-account' WHERE (`id`='6');

        ALTER TABLE up_roles ADD COLUMN `account_id` mediumint(9) NOT NULL DEFAULT '0' AFTER `operation_list`;;

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
