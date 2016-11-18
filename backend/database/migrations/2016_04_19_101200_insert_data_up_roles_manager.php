<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpRolesManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_roles` VALUES ('4', '管理员', '管理员', '1', 'manager-profile,manager-password,manager-campaign,manager-advertiser,manager-broker,manager-trafficker,manager-stat,manager-balance,manager-audit,manager-package,manager-message,manager-account,manager-sdk', '6', '6');
        INSERT INTO `up_roles` VALUES ('1001','销售账号', '销售账号', '1', 'manager-profile,manager-password,manager-advertiser,manager-home,manager-stats-audit-client', '6', '6');
        INSERT INTO `up_roles` VALUES ('1002','媒介账号', '媒介账号', '1', 'manager-profile,manager-password,manager-home,manager-stats-audit-trafficker', '6', '6');
        INSERT INTO `up_roles` VALUES ('1003','财务账号', '财务账号', '1', 'manager-profile,manager-password,manager-home,manager-balance,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client', '6', '6');
        INSERT INTO `up_roles` VALUES ('1004','管理员账号', '管理员账号', '1', 'manager-profile,manager-password,manager-home,manager-balance,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-advertiser,manager-campaign,manager-balance,manager-trafficker,manager-account,manager-message,manager-stat,manager-add_delivery_data,manager-add_client_data', '6', '6');
        INSERT INTO `up_roles` VALUES ('1005','运营账号', '运营账号', '1', 'manager-profile,manager-password,manager-home,manager-balance,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-campaign,manager-message,manager-stat,manager-trafficker', '6', '6');
        INSERT INTO `up_roles` VALUES ('1006','审计员账号', '审计员账号', '1', 'manager-profile,manager-password,manager-home,manager-balance,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-trafficker-audit', '6', '6');
        INSERT INTO `up_roles` VALUES ('1007','总经理', '总经理', '1', 'manager-advertiser,manager-campaign,manager-stat,manager-account,manager-message,manager-balance,manager-trafficker,manager-broker,manager-audit,manager-recharge-audit,manager-recharge,manager-sdk,manager-profile,manager-password,manager-add_delivery_data,manager-add_client_data,manager-trafficker-audit,manager-audit_check,manager-pub-all,manager-pub-self,manager-bd-all,manager-bd-self,manager-home,manager-client_audit,manager-client_audit-check,manager-package,manager-db-view,manager-db-request-download,manager-db-up-download,manager-db-download-rate,manager-db-avg-client-price,manager-db-avg-trafficker-price,manager-db-ecpm,manager-db-rate,manager-db-used,manager-db-cpc-request-download,manager-db-cpc-up-download,manager-db-recharge-income,manager-db-gift-income,manager-db-recharge-out,manager-db-gift-out,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-db-download-complete,manager-super-account-all,manager-super-account-self', '6', '6');
        INSERT INTO `up_roles` VALUES ('1008','CEO', 'CEO', '1', 'manager-advertiser,manager-campaign,manager-stat,manager-account,manager-message,manager-balance,manager-trafficker,manager-broker,manager-audit,manager-recharge-audit,manager-recharge,manager-sdk,manager-profile,manager-password,manager-add_delivery_data,manager-add_client_data,manager-trafficker-audit,manager-audit_check,manager-pub-all,manager-pub-self,manager-bd-all,manager-bd-self,manager-home,manager-client_audit,manager-client_audit-check,manager-package,manager-db-view,manager-db-request-download,manager-db-up-download,manager-db-download-rate,manager-db-avg-client-price,manager-db-avg-trafficker-price,manager-db-ecpm,manager-db-rate,manager-db-used,manager-db-cpc-request-download,manager-db-cpc-up-download,manager-db-recharge-income,manager-db-gift-income,manager-db-recharge-out,manager-db-gift-out,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-db-download-complete,manager-super-account-all,manager-super-account-self', '6', '6');
        INSERT INTO `up_roles` VALUES ('1009','COO', 'COO', '1', 'manager-advertiser,manager-campaign,manager-stat,manager-account,manager-message,manager-balance,manager-trafficker,manager-broker,manager-audit,manager-recharge-audit,manager-recharge,manager-sdk,manager-profile,manager-password,manager-add_delivery_data,manager-add_client_data,manager-trafficker-audit,manager-audit_check,manager-pub-all,manager-pub-self,manager-bd-all,manager-bd-self,manager-home,manager-client_audit,manager-client_audit-check,manager-package,manager-db-view,manager-db-request-download,manager-db-up-download,manager-db-download-rate,manager-db-avg-client-price,manager-db-avg-trafficker-price,manager-db-ecpm,manager-db-rate,manager-db-used,manager-db-cpc-request-download,manager-db-cpc-up-download,manager-db-recharge-income,manager-db-gift-income,manager-db-recharge-out,manager-db-gift-out,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-db-download-complete,manager-super-account-all,manager-super-account-self', '6', '6');
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
