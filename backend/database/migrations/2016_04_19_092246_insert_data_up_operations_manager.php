<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpOperationsManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_operations` VALUES ('14113', 'manager-profile', '修改资料', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14115', 'manager-password', '修改密码', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('1460', 'manager-campaign', '广告管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('1410', 'manager-advertiser', '广告主管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14103', 'manager-broker', '代理商管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('1475', 'manager-trafficker', '媒体商管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('1461', 'manager-stat', '统计报表', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('1473', 'manager-balance', '财务管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14104', 'manager-audit', '审计管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14309', 'manager-package', '渠道包管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('1470', 'manager-account', '账号管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('1471', 'manager-message', '活动与通知', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14111', 'manager-sdk', 'SDK下载', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('141002', 'manager-super-account-all', '广告主管理-All', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('141003', 'manager-super-account-self', '广告主管理-Self', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14117', 'manager-add_delivery_data', '人工投放，添加数据', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14118', 'manager-add_client_data', '添加广告主结算数据', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14300', 'manager-trafficker-audit', '媒体商数据审计管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14301', 'manager-audit_check', '媒体商数据审计审核', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14307', 'manager-client_audit', '广告主数据审计管理', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14308', 'manager-client_audit-check', '广告主数据审计审核', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14302', 'manager-pub-all', '查看数据-pub-all', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14303', 'manager-pub-self', '查看数据-pub-self', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14304', 'manager-bd-all', '查看数据-bd-all', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14305', 'manager-bd-self', '查看数据-bd-self', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14306', 'manager-home', '查看概览', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14401', 'manager-db-view', '数据-展示量', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14402', 'manager-db-request-download', '数据-下载请求(监控)', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14403', 'manager-db-up-download', '数据-下载量(上报)', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14404', 'manager-db-download-rate', '数据-下载转化率', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14414', 'manager-db-recharge-income', '数据-广告主消耗（充值金）', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14415', 'manager-db-gift-income', '数据-广告主消耗（赠送金）', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14416', 'manager-db-recharge-out', '数据-媒体支出（充值金）', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14417', 'manager-db-gift-out', '数据-媒体支出（赠送金）', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14407', 'manager-db-avg-client-price', '数据-平均单价(广告主)', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14408', 'manager-db-avg-trafficker-price', '数据-平均单价(媒体商)', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14409', 'manager-db-ecpm', '数据-eCPM', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14410', 'manager-db-rate', '数据-转化量', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14411', 'manager-db-used', '数据-广告主结算金额', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14412', 'manager-db-cpc-request-download', '数据-点击量', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14413', 'manager-db-cpc-up-download', '数据-点击转化率', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14450', 'manager-stats-income-trafficker', '收入报表-媒体商', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14451', 'manager-stats-income-client', '收入报表-广告主', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14452', 'manager-stats-audit-trafficker', '审计收入-媒体商', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14453', 'manager-stats-audit-client', '审计收入-广告主', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14454', 'manager-db-download-complete', '数据-下载完成(监控)', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14108', 'manager-recharge-audit', '充值审核', 'MANAGER');
        INSERT INTO `up_operations` VALUES ('14109', 'manager-recharge', '充值申请', 'MANAGER');
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
